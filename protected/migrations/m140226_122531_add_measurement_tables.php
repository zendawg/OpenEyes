<?php

class m140226_122531_add_measurement_tables extends CDbMigration {

	// Use safeUp/safeDown to do migration with transaction
	public function safeUp() {
		$this->execute("create table `measurement_type` (`id` INT(10) UNSIGNED NOT NULL primary key AUTO_INCREMENT,
			`class_name` VARCHAR(85) NOT NULL UNIQUE,
			`attachable` tinyint default 0 NOT NULL,
			`deleted` tinyint default 0 NOT NULL,
			`last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			`created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			KEY `measurement_type_last_modified_user_id_fk` (`last_modified_user_id`),
			KEY `measurement_type_created_user_id_fk` (`created_user_id`),
			CONSTRAINT `measurement_type_created_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
			CONSTRAINT `measurement_type_last_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`))
                                                        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
		
		$this->execute("create table `patient_measurement` (`id` INT(10) UNSIGNED NOT NULL primary key AUTO_INCREMENT,
			`patient_id` INT(10) UNSIGNED NOT NULL,
			`measurement_type_id` INT(10) UNSIGNED NOT NULL,
			`deleted` tinyint default 0 NOT NULL,
			`last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			`created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			KEY `patient_measurement_last_modified_user_id_fk` (`last_modified_user_id`),
			KEY `patient_measurement_created_user_id_fk` (`created_user_id`),
			CONSTRAINT `patient_measurement_created_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
			CONSTRAINT `patient_measurement_last_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`))
                                                        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

		$this->dbConnection->createCommand()->addForeignKey('patient_measurement_patient_id_fk', 'patient_measurement', 'patient_id', 'patient', 'id');
		$this->dbConnection->createCommand()->addForeignKey('patient_measurement_measurement_type_id_fk', 'patient_measurement', 'measurement_type_id', 'measurement_type', 'id');

		$this->execute("create table `measurement_reference` (`id` INT(10) UNSIGNED NOT NULL primary key AUTO_INCREMENT,
			`episode_id` INT(10) UNSIGNED DEFAULT NULL,
			`event_id` INT(10) UNSIGNED DEFAULT NULL,
			`patient_measurement_id` INT(10) UNSIGNED NOT NULL,
			`origin` tinyint default 0 NOT NULL,
			`deleted` tinyint default 0 NOT NULL,
			`last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			`created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
			`created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
			KEY `measurement_referencelast_modified_user_id_fk` (`last_modified_user_id`),
			KEY `measurement_referencecreated_user_id_fk` (`created_user_id`),
			CONSTRAINT `measurement_referencecreated_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
			CONSTRAINT `measurement_referencelast_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`))
                                                        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

		$this->dbConnection->createCommand()->addForeignKey('measurement_reference_patient_measurement_id_fk', 'measurement_reference', 'patient_measurement_id', 'patient_measurement', 'id');
		$this->dbConnection->createCommand()->addForeignKey('measurement_reference_episode_id_fk', 'measurement_reference', 'episode_id', 'episode', 'id');
		$this->dbConnection->createCommand()->addForeignKey('measurement_reference_event_id_fk', 'measurement_reference', 'event_id', 'event', 'id');
		return true;
	}

	public function safeDown() {
		$this->dbConnection->createCommand()->dropTable('measurement_reference');

		$this->dbConnection->createCommand()->dropTable('patient_measurement');

		$this->dbConnection->createCommand()->dropTable('measurement_type');
	}

}