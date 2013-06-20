<?php

/**
 * This is the model class for table "fs_file_audit".
 *
 * The followings are the available columns in table 'fs_file_audit':
 * @property string $id
 * @property string $src_size
 * @property string $dest_size
 * @property string $operation
 * @property string $type
 * @property string $src_parent
 * @property string $dest_parent
 * @property string $src_child
 * @property string $dest_child
 * @property string $last_modified_user_id
 * @property string $last_modified_date
 * @property string $created_user_id
 * @property string $created_date
 *
 * The followings are the available model relations:
 * @property FsFile $destChild
 * @property User $createdUser
 * @property User $lastModifiedUser
 * @property FsDirectory $destParent
 * @property FsFile $srcChild
 * @property FsDirectory $srcParent
 */
class FsFileAudit extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return FsFileAudit the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'fs_file_audit';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('src_size, operation, type', 'required'),
			array('src_size, dest_size, src_parent, dest_parent, src_child, dest_child, last_modified_user_id, created_user_id', 'length', 'max'=>10),
			array('operation, type', 'length', 'max'=>1),
			array('last_modified_date, created_date', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, src_size, dest_size, operation, type, src_parent, dest_parent, src_child, dest_child, last_modified_user_id, last_modified_date, created_user_id, created_date', 'safe', 'on'=>'search'),
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
			'destChild' => array(self::BELONGS_TO, 'FsFile', 'dest_child'),
			'createdUser' => array(self::BELONGS_TO, 'User', 'created_user_id'),
			'lastModifiedUser' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
			'destParent' => array(self::BELONGS_TO, 'FsDirectory', 'dest_parent'),
			'srcChild' => array(self::BELONGS_TO, 'FsFile', 'src_child'),
			'srcParent' => array(self::BELONGS_TO, 'FsDirectory', 'src_parent'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'src_size' => 'Src Size',
			'dest_size' => 'Dest Size',
			'operation' => 'Operation',
			'type' => 'Type',
			'src_parent' => 'Src Parent',
			'dest_parent' => 'Dest Parent',
			'src_child' => 'Src Child',
			'dest_child' => 'Dest Child',
			'last_modified_user_id' => 'Last Modified User',
			'last_modified_date' => 'Last Modified Date',
			'created_user_id' => 'Created User',
			'created_date' => 'Created Date',
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

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('src_size',$this->src_size,true);
		$criteria->compare('dest_size',$this->dest_size,true);
		$criteria->compare('operation',$this->operation,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('src_parent',$this->src_parent,true);
		$criteria->compare('dest_parent',$this->dest_parent,true);
		$criteria->compare('src_child',$this->src_child,true);
		$criteria->compare('dest_child',$this->dest_child,true);
		$criteria->compare('last_modified_user_id',$this->last_modified_user_id,true);
		$criteria->compare('last_modified_date',$this->last_modified_date,true);
		$criteria->compare('created_user_id',$this->created_user_id,true);
		$criteria->compare('created_date',$this->created_date,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}