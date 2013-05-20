<?php

class OEMigration extends CDbMigration {

	/**
	 * Initialise tables with default data
	 * Filenames must to be in the format "nn_tablename.csv", where nn is the processing order
	 */
	protected function initialiseData($migrations_path) {
		$data_path = $migrations_path.'/data/'.get_class($this).'/';
		foreach(glob($data_path."*.csv") as $file_path) {
			$table = substr(substr(basename($file_path), 0, -4), 3);
			echo "Importing $table data...";
			$fh = fopen($file_path, 'r');
			$columns = fgetcsv($fh);
			$lookup_columns = array();
			foreach($columns as &$column) {
				if(strpos($column, '=>') !== false) {
					$column_parts = explode('=>',$column);
					$column = trim($column_parts[0]);
					$lookup_parts = explode('.',$column_parts[1]);
					$model = trim($lookup_parts[0]);
					$field = trim($lookup_parts[1]);
					$lookup_columns[$column] = array('model' => $model, 'field' => $field);
				}
			}
			$row_count = 0;
			$values = array();
			while(($record = fgetcsv($fh)) !== false) {
				$row_count++;
				$data = array_combine($columns, $record);

				// Process lookup columns
				foreach($lookup_columns as $lookup_column => $lookup) {
					$model = $lookup['model'];
					$field = $lookup['field'];
					$lookup_value = $data[$lookup_column];
					$lookup_record = BaseActiveRecord::model($model)->findByAttributes(array($field => $lookup_value));
					$data[$lookup_column] = $lookup_record->id;
				}

				// Process NULLs
				foreach($data as &$value) {
					if($value == 'NULL') {
						$value = null;
					}
				}

				$this->insert($table, $data);
			}
			fclose($fh);
			echo "$row_count records, done.\n";
		}
	}

    /**
     * Returns all the default table array elements that all tables share.
     * This is a convenience method for all table creation.
     * 
     * @param $tableName the table name to use.
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
            'KEY `' . $tableName . '_lmuid_fk' . '`
        (`last_modified_user_id`)',
            'CONSTRAINT `' . $tableName . '_cuid_fk' . '`
        FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`)',
            'CONSTRAINT
        `' . $tableName . '_lmuid_fk' . '` FOREIGN KEY
        (`last_modified_user_id`) REFERENCES `user` (`id`)');
        return $defaults;
    }

    /**
     * Delete data and drop table.
     * 
     * @param table_name the name of the table to delete data from; afterward,
     * drop the table.
     */
    public function deleteTableAndData($table_name) {

        $this->delete($table_name);
        $this->dropTable($table_name);
    }

}