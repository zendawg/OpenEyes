<?php

class m130108_112026_scan_event_group extends CDbMigration
{
	public function up()
	{
		$this->insert('event_group',array('name' => 'Scans', 'code' => 'Sc'));
	}

	public function down()
	{
		$event_group_id = Yii::app()->db->createCommand()->select("id")->from("event_group")->where("name='Scans' and code='Sc'")->queryScalar();

		$this->delete('event_group',"id = $event_group_id");
	}
}
