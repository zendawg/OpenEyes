<?php /**
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

class ElementScannedDocument extends BaseEventTypeElement
{
	public $service;

	/**
	 * Returns the static model of the specified AR class.
	 * @return the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('event_id, asset_id, ', 'safe'),
			array('asset_id, ', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, event_id, asset_id, ', 'safe', 'on' => 'search'),
		);
	}
	
	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'asset' => array(self::BELONGS_TO, 'Asset', 'asset_id'),
			'user' => array(self::BELONGS_TO, 'User', 'created_user_id'),
			'usermodified' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
			'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'event_id' => 'Event',
			'asset_id' => 'Asset',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('event_id', $this->event_id, true);
		$criteria->compare('asset_id', $this->asset_id);
		
		return new CActiveDataProvider(get_class($this), array(
			'criteria' => $criteria,
		));
	}

	public function getScans($mimetypes=false) {
		$criteria = new CDbCriteria;
		if ($this->asset_id) {
			$criteria->addCondition("new = 1 or id = $this->asset_id");
		} else {
			$criteria->addCondition("new = 1");
		}
		if (is_array($mimetypes)) {
			$criteria->addInCondition("mimetype",$mimetypes);
		}
		return Asset::model()->findAll($criteria);
	}

	public function beforeSave() {
		$model = get_class($this);
		$element = $model::model()->findByPk($this->id);

		if ($element && $element->asset_id != $this->asset_id) {
			$asset = Asset::model()->findByPk($element->asset_id);
			$asset->new = 1;
			if (!$asset->save()) {
				throw new Exception("Unable to mark asset as new: ".print_r($asset->getErrors(),true));
			}
		}

		return parent::beforeSave();
	}

	public function afterSave() {
		$asset = $this->asset;
		$asset->new = 0;
		if (!$asset->save()) {
			throw new Exception("Unable to unmark asset as new: ".print_r($asset->getErrors(),true));
		}

		return parent::afterSave();
	}

	public function getIssueText() {
		return $this->title;
	}
}
