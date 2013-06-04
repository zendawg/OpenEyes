<?php

class m130604_083129_scanned_document_uid extends CDbMigration {
  
  public function safeUp() {
    $this->createUidTable();
  }

  public function safeDown() {
    $this->dropTable('scanned_document_uid');
  }

  /**
   * Creates a table to identify unique IDs with patient IDs.
   */
  private function createUidTable() {
    
    $this->createTable('scanned_document_uid', array_merge(array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'pid' => 'varchar(40) NOT NULL',
                    ), $this->getDefaults('scanned_document_uid')), 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
  }

  /**
   * Returns all the default table array elements that all tables share.
   * This is a convenience method for all table creation.
   * 
   * @param $suffix the table name suffix - this is the name of the table
   * without the formal table name 'et_[spec][group][code]_'.
   * 
   * @param useEvent by default, the event type is created as a foreign
   * key to the event table; set this to false to not create this key.
   * 
   * @return an array of defaults to merge in to the table array data required.
   */
  public function getDefaults($tableName) {
    $defaults = array('last_modified_user_id' => 'int(10) unsigned NOT NULL DEFAULT 1',
        'last_modified_date' => 'datetime NOT NULL DEFAULT \'1901-01-01
        00:00:00\'',
        'created_user_id' => 'int(10) unsigned NOT NULL DEFAULT 1',
        'created_date' => 'datetime NOT NULL DEFAULT \'1901-01-01 00:00:00\'',
        'PRIMARY KEY (`id`)',
        'KEY `' . $tableName . '_last_modified_user_id_fk' . '`
        (`last_modified_user_id`)',
        'CONSTRAINT `' . $tableName . '_created_user_id_fk' . '`
        FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`)',
        'CONSTRAINT
        `' . $tableName . '_last_modified_user_id_fk' . '` FOREIGN KEY
        (`last_modified_user_id`) REFERENCES `user` (`id`)');
    return $defaults;
  }

}