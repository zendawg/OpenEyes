<?php

class m130619_091540_file_tables extends OEMigration { // Use safeUp/safeDown to do migration with transaction

  private $fileTable = 'fs_file';
  private $dirTable = 'fs_directory';

  public function safeUp() {
    $this->createDirectoryTable();
    $this->createFileTable();
  }

  public function safeDown() {
    $this->deleteTableAndData($this->fileTable);
    $this->deleteTableAndData($this->dirTable);
  }

  /**
   * Creates a table to identify unique IDs with patient IDs.
   */
  private function createFileTable() {

    $this->createTable($this->fileTable, array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'name' => 'varchar(128) NOT NULL',
                'modified' => 'bigint signed NOT NULL',
                'length' => 'int(10) signed NOT NULL',
                'deleted' => 'tinyint(1) unsigned default 0',
                'chronological_key' => 'bigint unsigned NOT NULL',
                'dir_id' => 'int(10) unsigned NOT NULL',
                    ), $this->getDefaults($this->fileTable)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
  }

  /**
   * Creates a table to identify unique IDs with patient IDs.
   */
  private function createDirectoryTable() {

    $this->createTable($this->dirTable, array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'modified' => 'bigint signed NOT NULL',
                'path' => 'text NOT NULL',
                    ), $this->getDefaults($this->dirTable)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
  }

}