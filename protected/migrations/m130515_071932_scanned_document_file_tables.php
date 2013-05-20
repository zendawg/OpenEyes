<?php

class m130515_071932_scanned_document_file_tables extends OEMigration {

  private $suffix_uid = 'uid';
  private $suffixDirTable = 'directory';
  private $suffixFileTable = 'file';

  private function getTableName($suffix) {
    return "fs_" . $suffix;
  }

// Use safeUp/safeDown to do migration with transaction
  public function safeUp() {
    $this->createTable($this->getTableName($this->suffix_uid), array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'pid' => 'varchar(40) NOT NULL',
                    ), $this->getDefaults($this->suffix_uid)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');

    $this->createTable($this->getTableName($this->suffixDirTable), array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'modified' => 'bigint signed NOT NULL',
                'path' => 'text NOT NULL',
                    ), $this->getDefaults($this->suffixDirTable)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');

    $this->createTable($this->getTableName($this->suffixFileTable), array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'name' => 'varchar(128) NOT NULL',
                'modified' => 'bigint signed NOT NULL',
                'length' => 'int(10) signed NOT NULL',
                'dir_id' => 'int(10) unsigned NOT NULL',
                    ), $this->getDefaults($this->suffixFileTable)), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');

    $this->createIndex($this->getTableName($this->suffixFileTable) . '_dir_id_fk', $this->getTableName($this->suffixFileTable), 'dir_id');
    $this->addForeignKey($this->getTableName($this->suffixFileTable) . '_dir_id_fk', $this->getTableName($this->suffixFileTable), 'dir_id', $this->getTableName($this->suffixDirTable), 'id');

    $this->addColumn($this->getTableName($this->suffixFileTable), 'asset_id', "int(10) unsigned default NULL");
    $this->addForeignKey($this->getTableName($this->suffixFileTable) . '_asset_id_fk', $this->getTableName($this->suffixFileTable), 'asset_id', 'asset', 'id');
  }

  public function safeDown() {

    $this->dropForeignKey($this->getTableName($this->suffixFileTable) . '_asset_id_fk', $this->getTableName($this->suffixFileTable));
    $this->dropColumn($this->getTableName($this->suffixFileTable), 'asset_id');

    $this->deleteTableAndData($this->getTableName($this->suffixFileTable));
    $this->deleteTableAndData($this->getTableName($this->suffixDirTable));
    $this->deleteTableAndData($this->getTableName($this->suffix_uid));
  }

}