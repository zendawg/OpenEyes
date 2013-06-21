<?php

class m130620_065928_file_audit_table extends OEMigration {

  // Use safeUp/safeDown to do migration with transaction
  public function safeUp() {

    $tableName = 'fs_file_audit';
    $this->createTable($tableName, array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'src_size' => 'int(10) unsigned NOT NULL',
                'dest_size' => 'int(10) unsigned',
                'operation' => 'char NOT NULL',
                'type' => 'char NOT NULL',
                'src_parent' => 'int(10) unsigned default NULL',
                'dest_parent' => 'int(10) unsigned default NULL',
                'src_child' => 'int(10) unsigned default NULL',
                'dest_child' => 'int(10) unsigned default NULL'), $this->getDefaults($tableName)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
  }

  public function safeDown() {
    $this->dropTable("fs_file_audit");
  }

}