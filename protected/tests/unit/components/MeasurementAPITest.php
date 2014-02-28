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
class MeasurementReferenceTest extends CDbTestCase {

	public $model;
	public $fixtures = array(
		'episodes' => 'Episode',
		'events' => 'Event',
		'patients' => 'Patient',
		'references' => 'MeasurementReference',
		'measurementTypes' => 'MeasurementType',
		'patientMeasurements' => 'PatientMeasurement'
	);

	public function dataProvider_Search() {
		return array(
			array(array('patient_id' => 1), 1, array('patient_measurement_1')),
			array(array('patient_id' => 2), 1, array('patient_measurement_2')),
			array(array('patient_id' => 2), 1, array('patient_measurement_3')),
		);
	}

	public function setUp() {
		parent::setUp();
		$this->model = new MeasurementAPI;
	}

	public function testModel() {
		$this->assertEquals('PatientMeasurement', get_class(PatientMeasurement::model()), 'Class name should match model.');
	}

	/**
	 * 
	 */
	public function testAddMeasurement() {
		$measurement = $this->model->addMeasurement($this->patients('patient1'),
				$this->measurementTypes('measurement_type3'));
		// patient id and emasurement type id must be appropriately set:
		$this->assertEquals($this->patients('patient1')->id, $measurement->patient_id);
		$this->assertEquals($this->measurementTypes('measurement_type3')->id, $measurement->measurement_type_id);
	}

	/**
	 * 
	 */
	public function testAddMeasurementReference() {
		$patientMeasurement = $this->patientMeasurements('patient_measurement_3');
		$this->assertEquals(0, count($patientMeasurement->measurementReferences));
		$measurementRef = $this->model->addReference($patientMeasurement,
				$this->events('event1'));
		$this->assertNotNull($measurementRef);
		
		$this->assertEquals(1, count($patientMeasurement->measurementReferences));
		$ref = $patientMeasurement->measurementReferences[0];
		$this->assertEquals(1, $ref->origin);
	}

	/**
	 * 
	 */
	public function testAddMultipleMeasurementReference() {
		$patientMeasurement = $this->patientMeasurements('patient_measurement_3');
		$this->assertEquals(0, count($patientMeasurement->measurementReferences));
		for ($i=0; $i<10; $i++) {
			$measurementRef = $this->model->addReference($patientMeasurement,
					$this->events('event1'));
			$this->assertNotNull($measurementRef);

			$this->assertEquals($i+1, count($patientMeasurement->measurementReferences));
			$ref = $patientMeasurement->measurementReferences[$i];
			// only the first owner should have the origin as True (all others False):
			$this->assertEquals($i == 0 ? True : False, $ref->isOrigin());
		}
	}

	/**
	 * 
	 */
	public function testAddMeasurementBadReference() {
		$patientMeasurement = $this->patientMeasurements('patient_measurement_2');
		$measurementRef = $this->model->addReference($patientMeasurement,
				$this->patients('patient1'));
		$this->assertNull($measurementRef);
	}
	
	/**
	 * 
	 */
	public function testGetPatientMeasurements() {
		$this->assertEquals(2, count($this->model->getMeasurements($this->patients('patient2'))));
	}

}
