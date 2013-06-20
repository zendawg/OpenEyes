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
    
        $this->addForeignKey('fs_file_src_parent_id_fk', 'fs_file_audit', 'src_parent', 'fs_directory', 'id');
        $this->createIndex('src_parent', 'fs_file_audit', 'src_parent');
        $this->addForeignKey('fs_file_src_child_id_fk', 'fs_file_audit', 'src_child', 'fs_file', 'id');
        $this->createIndex('src_child', 'fs_file_audit', 'src_child');
        $this->addForeignKey('fs_file_dest_parent_id_fk', 'fs_file_audit', 'dest_parent', 'fs_directory', 'id');
        $this->createIndex('dest_parent', 'fs_file_audit', 'dest_parent');
        $this->addForeignKey('fs_file_dest_child_id_fk', 'fs_file_audit', 'dest_child', 'fs_file', 'id');
        $this->createIndex('dest_child', 'fs_file_audit', 'dest_child');
  }

  public function safeDown() {
    $this->dropTable("fs_file_audit");
  }

}