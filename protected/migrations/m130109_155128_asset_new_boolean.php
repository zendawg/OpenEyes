<?php

class m130109_155128_asset_new_boolean extends CDbMigration
{
	public function up()
	{
		$this->addColumn('asset','new','tinyint(1) unsigned NOT NULL DEFAULT 1');
	}

	public function down()
	{
		$this->dropColumn('asset','new');
	}
}
