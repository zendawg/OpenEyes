<?php

/**
 * This is the model class for table "measurement_reference".
 *
 * The followings are the available columns in table 'measurement_reference':
 * @property string $id
 * @property string $episode_id
 * @property string $event_id
 * @property string $patient_measurement_id
 * @property integer $origin
 *
 * The followings are the available model relations:
 * @property Event $event
 * @property Episode $episode
 * @property PatientMeasurement $patientMeasurement
 */
class MeasurementReference extends BaseActiveRecordVersioned {

	public $event;
	public $episode;
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'measurement_reference';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('patient_measurement_id', 'required'),
			array('origin', 'numerical', 'integerOnly' => true),
			array('episode_id, event_id, patient_measurement_id', 'length', 'max' => 10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, episode_id, event_id, patient_measurement_id, origin', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
			'episode' => array(self::BELONGS_TO, 'Episode', 'episode_id'),
			'patientMeasurement' => array(self::BELONGS_TO, 'PatientMeasurement', 'patient_measurement_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'episode_id' => 'Episode',
			'event_id' => 'Event',
			'patient_measurement_id' => 'Patient Measurement',
			'origin' => 'Origin',
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
		$criteria->compare('episode_id', $this->episode_id, true);
		$criteria->compare('event_id', $this->event_id, true);
		$criteria->compare('patient_measurement_id', $this->patient_measurement_id, true);
		$criteria->compare('origin', $this->origin);

		return new CActiveDataProvider($this, array(
					'criteria' => $criteria,
				));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return MeasurementReference the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * Determin if this reference is the source of the measurement.
	 * 
	 * @return type True if the source of the measurement; false otherwise.
	 */
	public function isOrigin() {
		return $this->origin == 1 ? True : False;
	}

	/**
	 * Sets the event; the event may only be set if 
	 * @param type $event
	 * @return boolean
	 */
	public function setOwner($owner) {
		if ($this->$event || $this->episode) {
			return false;
		}
		$this->event = $event;
		return $this->event;
	}

}
