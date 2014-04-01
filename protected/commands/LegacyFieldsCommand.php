<?php

/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
class LegacyFieldsCommand extends CConsoleCommand {

	public $importDir;
	public $archiveDir;
	public $errorDir;
	public $dupDir;
	public $interval;

	public function getHelp() {
		return "Usage: legacyfields import --interval=<time> --importDir=<dir> --archiveDir=<dir> --errorDir=<dir> --dupDir=<dir>\n\n"
				. "Import Humphrey visual fields into OpenEyes from the given import directory.\n"
				. "Successfully imported files are moved to the given archive directory;\n"
				. "likewise, errored files and duplicate files (already within OE) are moved to\n"
				. "the respective directory. --interval is used to check for tests within\n"
				. "the specified time limit, so PT10M looks for files within 10 minutes of the other to\n"
				. "bind to an existing field.\n\n" 
				. "The import expects to find .XML files in the given directory, two\n"
				. "for each field test, and each file is expected to be in a format\n"
				. "acceptable by OpenEyes (specifically they must conform to the API).\n"
				. "For each pair of files, the first is a patient measurement, the\n"
				. "second a humphrey field test reading.\n"
				. "\n";
	}

	/**
	 * 
	 * @param type $importDir
	 * @param type $archiveDir
	 * @param type $errorDir
	 * @param type $dupDir
	 */
	public function actionImport($importDir, $archiveDir, $errorDir, $dupDir, $interval='PT10M') {
		$this->importDir = $this->checkSeparator($importDir);
		$this->archiveDir = $this->checkSeparator($archiveDir);
		$this->errorDir = $this->checkSeparator($errorDir);
		$this->dupDir = $this->checkSeparator($dupDir);
		$this->interval = $interval;
		$smgr = Yii::app()->service;
		$fhirMarshal = new FhirMarshal;
		$directory = $this->importDir;
		if ($entry = glob($directory . '/*.fmes')) {
			foreach ($entry as $file) {
				echo "Importing " . $file . PHP_EOL;

				// first check the file has not already been imported:
				$field = file_get_contents($file);
				$resource_type = 'MeasurementVisualFieldHumphrey';
				$service = Yii::app()->service->getFhirService($resource_type, array());
				$fieldObject = $fhirMarshal->parseXml($field);
				if (count(ProtectedFile::model()->find("name=:name", array(":name" => $fieldObject->file_reference))) > 0) {
					echo "Moving " . basename($file) . " to duplicates directory; "
					. $fieldObject->file_reference . " already exists within OE" . PHP_EOL;
					$this->move($this->dupDir, $file);
					continue;
				}

				$matches = array();
				preg_match("/__OE_PATIENT_ID_([0-9]*)__/", $field, $matches);
				if (count($matches) < 2) {
					echo "Failed to extract patient ID in " . basename($file) . "; moving to " . $this->errorDir . PHP_EOL;
					$this->move($this->errorDir, $file);
					continue;
				}
				$match = $matches[1];

				$patient = Patient::model()->find("hos_num=?", array($match));
				if (!$patient) {
					echo "Failed to find patient in " . basename($file) . "; moving to " . $this->errorDir . PHP_EOL;
					$this->move($this->errorDir, $file);
					continue;
				}
				$pid = $patient->id;
				$field = preg_replace("/__OE_PATIENT_ID_([0-9]*)__/", $pid, $field);

				// first check the file has not already been imported:

				$resource_type = 'MeasurementVisualFieldHumphrey';
				$service = Yii::app()->service->getFhirService($resource_type, array());
				$fieldObject = $fhirMarshal->parseXml($field);

				$tx = Yii::app()->db->beginTransaction();
				$ref = $service->fhirCreate($fieldObject);
				$tx->commit();
				$refId = $ref->getId();
				$measurement = MeasurementVisualFieldHumphrey::model()->findByPk($refId);
				$measurement->legacy = 1;
				$measurement->save();
				$study_datetime = $measurement->study_datetime;

				// does the user have any legacy field events associated with them?
				$eventType = EventType::model()->find("class_name=?", array("OphInVisualfields"));
				if (!isset($eventType)) {
					echo "Correct event type, OphInVisualfields, is not present; quitting...\n";
					exit(1);
				}
				$legacyEpisode = Episode::model()->find("legacy=1 AND patient_id=" . $pid);
				if (count($legacyEpisode) == 0) {
					$episode = new Episode;
					//					$episode->event_type_id = $eventType->id;
					$episode->legacy = 1;
					$episode->start_date = getdate();
					$episode->patient_id = $pid;
					$episode->save();
					echo "Successfully created new legacy episode for patient in " . basename($file) . "\n";
					$this->newEvent($episode, $eventType, $measurement);
				} else {
					echo "Legacy episode already present for patient in " . basename($file) . "\n";
					// so if we've got a legacy episode that means there's probably an event with the 
					// image bound to it - let's look for it:
					$eye = $fieldObject->eye;
					if ($eye == 'L') {
						// we're looking for the other eye:
						$eye = Eye::RIGHT;
					} else {
						$eye = Eye::LEFT;
					}
					$startCreatedTime = new DateTime($study_datetime);
					$endCreatedTime = new DateTime($study_datetime);
					$criteria = new CdbCriteria;

					$startCreatedTime->sub(new DateInterval($this->interval));
					$endCreatedTime->add(new DateInterval($this->interval));

					if ($interval) {
						$criteria->condition = 'created_date >= "' . $startCreatedTime->format('Y-m-d H:i:s')
								. '" and created_date <= "' . $endCreatedTime->format('Y-m-d H:i:s') . '"';
					}
					$events = Event::model()->findAll($criteria);

					if (count($events) == 1) {

						$image = Element_OphInVisualfields_Image::model()->find("event_id=:event_id", array(":event_id" => $events[0]->id));

						if ($measurement->eye->name == 'Left') {
							$image->left_field_id = $measurement->cropped_image->id;
						} else {
							$image->right_field_id = $measurement->cropped_image->id;
						}
						$image->save();
						$this->move($this->archiveDir, $file);
						echo "Successfully bound " . basename($file) . " to existing event.\n";
					} else if (count($events) == 0) {
						$this->newEvent($legacyEpisode, $eventType, $measurement);
						$this->move($this->archiveDir, $file);
					}
				}
			}
		}
	}

	/**
	 * 
	 * @param type $episode
	 * @param type $eventType
	 * @param type $measurement
	 */
	private function newEvent($episode, $eventType, $measurement) {

		// now bind a new event to the new legacy episode:
		$event = new Event;
		$event->episode_id = $episode->id;
		$event->event_type_id = $eventType->id;
		$event->created_date = $measurement->study_datetime;
		$event->save($allow_overriding = true);
		$event->created_date = $measurement->study_datetime;
		$event->save($allow_overriding = true);

		$image = new Element_OphInVisualfields_Image;
		$image->event_id = $event->id;
		if ($measurement->eye->name == 'Left') {
			$image->left_field_id = $measurement->cropped_image->id;
		} else {
			$image->right_field_id = $measurement->cropped_image->id;
		}
		$image->save();
		echo "Successfully added " . basename($measurement->cropped_image->name) . " to new event.\n";
	}

	/**
	 * Moves both the .pmes and .fmes file.
	 * @param type $toDir
	 * @param type $file
	 */
	private function move($toDir, $file) {
		$file = basename($file);
		rename($this->importDir . $file, $toDir . $file);
	}

	/**
	 * 
	 * @param string $file
	 * @return string
	 */
	private function checkSeparator($file) {
		if (substr($file, -1) != DIRECTORY_SEPARATOR) {
			$file = $file . DIRECTORY_SEPARATOR;
		}
		return $file;
	}

}