<?php

/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
class MeasurementAPI {

	/**
	 * Adds a new measurement of the specified type. The type is constructed
	 * and returned, and linked to the specifed patient and measurement type.
	 * 
	 * @param Patient $patient the non-null patient to add the measurement for.
	 * 
	 * @param MeasurementType $measurement_type the non-null type of measurement.
	 * 
	 * @return Object if the specified type exists and a new isntance could be
	 * created and saved, that measurement type will be returned; else null
	 * is returned. On success, the new measurement is bound to the specified
	 * patient.
	 */
	public function addMeasurement($patient, $measurement_type) {
		$measurement = new $measurement_type->class_name;
		// TODO check inherits from base class
		$measurement->measurement_type_id = $measurement_type->id;
		$measurement->patient_id = $patient->id;
		$measurement->save();
		return $measurement;
	}

	/**
	 * Add a valid reference (Episode or Event) to a patient measurement.
	 * Any measurement can have an event or episode (but not both) associated
	 * against them. The newly created reference will contain a reference to
	 * the specified patient measurement.
	 * 
	 * All references that are the first reference for a given measurement
	 * will have their origin set to True.
	 * 
	 * @param PatientMeasurement $patient_measurement the non-null measurement
	 * to add the reference to.
	 * 
	 * @param Object $reference a non-null event or episode object.
	 * 
	 * @return MeasurementReference measurement reference if one
	 * were created successfully; null otherwise.
	 */
	public function addReference($patient_measurement, $reference) {
		$measurementRef = null;
		if ($reference instanceof Event) {
			$criteria = new CdbCriteria;
			$criteria->condition = "event_id=:event_id and patient_measurement_id=:pmid";
			$criteria->params = array(':event_id' => $reference->id,
				':pmid' => $patient_measurement->id);
			Yii::import('application.models.MeasurementReference');
			$results = MeasurementReference::model()->find($criteria);
			if (count($results) == 0) {
				$measurementRef = new MeasurementReference;
				$measurementRef->event_id = $reference->id;
			}
		} else if ($reference instanceof Episode) {
			$measurementRef = new MeasurementReference;
			$measurementRef->episode_id = $reference->id;
		}
		if (isset($measurementRef)) {
			// first reference is implicitly the owner:
			if (count($patient_measurement->measurementReferences) == 0) {
				$measurementRef->origin = 1;
			}
			$measurementRef->patient_measurement_id = $patient_measurement->id;
			$measurementRef = $measurementRef->save();
			$patient_measurement->refresh();
		}
		return $measurementRef;
	}

	/**
	 * Get all measurements for the specified patient.
	 * 
	 * @param type $patient patient to obtain the measurements for.
	 * 
	 * @return array an array of PatientMeasurement if there were any;
	 * otherwise the empty list.
	 */
	public function getMeasurements($patient) {
		return PatientMeasurement::model()->findAll("patient_id=:patient_id", array(':patient_id' => $patient->id));
	}

	/**
	 * A measurement can only be deleted if it has no measurement references.
	 * 
	 * @param PatientMeasurement $patient_measurement the measurement to test.
	 * 
	 * @return boolean True if this measurement has no references; False
	 * otherwise.
	 */
	public function canDelete($patient_measurement) {
		return count($patient_measurement->measurementReferences) == 0;
	}

}

?>
