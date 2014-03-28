<?php

/**
 * This is the model class for table "patient_measurement".
 *
 * The followings are the available columns in table 'patient_measurement':
 * @property string $id
 * @property string $patient_id
 * @property string $measurement_type_id
 * @property integer $deleted
 *
 * The followings are the available model relations:
 * @property MeasurementReference[] $measurementReferences
 * @property OphinvisualfieldsFieldMeasurement[] $ophinvisualfieldsFieldMeasurements
 * @property OphinvisualfieldsFieldMeasurementVersion[] $ophinvisualfieldsFieldMeasurementVersions
 * @property MeasurementType $measurementType
 * @property Patient $patient
 */
class PatientMeasurement extends BaseActiveRecordVersioned {

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return PatientMeasurement the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'patient_measurement';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('patient_id, measurement_type_id', 'required'),
			array('deleted', 'numerical', 'integerOnly' => true),
			array('patient_id, measurement_type_id', 'length', 'max' => 10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, patient_id, measurement_type_id, deleted', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'measurementReferences' => array(self::HAS_MANY, 'MeasurementReference', 'patient_measurement_id'),
			'measurementType' => array(self::BELONGS_TO, 'MeasurementType', 'measurement_type_id'),
			'patient' => array(self::BELONGS_TO, 'Patient', 'patient_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'patient_id' => 'Patient',
			'measurement_type_id' => 'Measurement Type',
			'deleted' => 'Deleted',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('patient_id', $this->patient_id, true);
		$criteria->compare('measurement_type_id', $this->measurement_type_id, true);
		$criteria->compare('deleted', $this->deleted);

		return new CActiveDataProvider($this, array(
					'criteria' => $criteria,
				));
	}

	/**
	 * Determine if this measurement contains any references (that is,
	 * references from episodes or events).
	 * 
	 * @return boolean True if this measurement has any measurement references;
	 * False otherwise.
	 */
	public function isReferenced() {
		return count($this->measurementReferences) > 0 ? True : False;
	}

	/**
	 * Attempts to delete this measurement. Only non-referenced measurements
	 * can be deleted.
	 * 
	 * @return boolean False if the measurement contains any measurement
	 * references associated with it, or there was an internal error;
	 * True otherwise.
	 */
	public function delete() {
		if ($this->isReferenced()) {
			return false;
		}
		return parent::delete();
	}

}
