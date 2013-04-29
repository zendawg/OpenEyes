<?php

class m130109_104058_asset_extension_field extends CDbMigration
{
	public function up()
	{
		$this->addColumn('asset','extension','varchar(5) COLLATE utf8_bin NOT NULL');
	}

	public function down()
	{
		$this->dropColumn('asset','extension');
	}
}
