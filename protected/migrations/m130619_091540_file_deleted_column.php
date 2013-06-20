<?php

class m130619_091540_file_deleted_column extends OEMigration { // Use safeUp/safeDown to do migration with transaction

  public function safeUp() {
    $this->addColumn('fs_file', 'deleted', "tinyint(1) unsigned default 0");
  }

  public function safeDown() {
    $this->dropColumn('fs_file', 'deleted');
  }

}