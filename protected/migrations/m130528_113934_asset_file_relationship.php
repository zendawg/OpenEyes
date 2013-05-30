<?php
/**
 * Currently, all files have an asset id; this is going to change the other
 * way round, so all assets will have a file id. The temporary values are
 * stored in a temporary database.
 */
class m130528_113934_asset_file_relationship extends OEMigration {

  private $tempTable = "temp_m130528_113934_asset_file_relationship";
  // Use safeUp/safeDown to do migration with transaction
  public function safeUp() {
    $this->createTable($this->tempTable, array('file_id' => 'int(10) unsigned NOT NULL',
        'asset_id' => 'int(10) unsigned NOT NULL'));
    // since asset now references file, and not the other way around,
    // need to make temp table of values:
    $this->execute("insert into " . $this->tempTable . 
            "(file_id, asset_id) select id, asset_id from " . FsFile::model()->tableName());
    // ... then update the XML files and their asset IDs:
//    $this->execute("insert into ophscimagehumphreys_scan_humphrey_xml (asset_id) select asset_id, file_id from temp_m130528_113934_asset_file_relationship where temp_m130528_113934_asset_file_relationship.file_id=file_id;");
//    $this->execute("insert into ophscimagehumphreys_scan_humphrey_image (asset_id) select asset_id, file_id from temp_m130528_113934_asset_file_relationship where temp_m130528_113934_asset_file_relationship.file_id=file_id;");
    
    $this->addColumn(Asset::model()->tableName(), 'file_id', "int(10) unsigned default NULL");
    $this->addForeignKey(Asset::model()->tableName() . '_file_id_fk', Asset::model()->tableName(), 'file_id', FsFile::model()->tableName(), 'id');
  }

  public function safeDown() {
    $this->dropTable($this->tempTable);
    $this->dropForeignKey(Asset::model()->tableName() . '_file_id_fk', Asset::model()->tableName());
    $this->dropColumn(Asset::model()->tableName(), 'file_id');
  }

}