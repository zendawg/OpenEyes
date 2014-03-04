<?php

class m140303_124517_table_versioning extends CDbMigration
{
	public function up()
	{

		$this->execute("
CREATE TABLE `measurement_reference_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `episode_id` int(10) unsigned DEFAULT NULL,
  `event_id` int(10) unsigned DEFAULT NULL,
  `patient_measurement_id` int(10) unsigned NOT NULL,
  `origin` tinyint(4) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `acv_measurement_referencelast_modified_user_id_fk` (`last_modified_user_id`),
  KEY `acv_measurement_referencecreated_user_id_fk` (`created_user_id`),
  KEY `acv_measurement_reference_patient_measurement_id_fk` (`patient_measurement_id`),
  KEY `acv_measurement_reference_episode_id_fk` (`episode_id`),
  KEY `acv_measurement_reference_event_id_fk` (`event_id`),
  CONSTRAINT `acv_measurement_referencecreated_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_measurement_referencelast_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_measurement_reference_episode_id_fk` FOREIGN KEY (`episode_id`) REFERENCES `episode` (`id`),
  CONSTRAINT `acv_measurement_reference_event_id_fk` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `acv_measurement_reference_patient_measurement_id_fk` FOREIGN KEY (`patient_measurement_id`) REFERENCES `patient_measurement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		$this->alterColumn('measurement_reference_version','id','int(10) unsigned NOT NULL');
		$this->dropPrimaryKey('id','measurement_reference_version');

		$this->createIndex('measurement_reference_aid_fk','measurement_reference_version','id');
		$this->addForeignKey('measurement_reference_aid_fk','measurement_reference_version','id','measurement_reference','id');

		$this->addColumn('measurement_reference_version','version_date',"datetime not null default '1900-01-01 00:00:00'");

		$this->addColumn('measurement_reference_version','version_id','int(10) unsigned NOT NULL');
		$this->addPrimaryKey('version_id','measurement_reference_version','version_id');
		$this->alterColumn('measurement_reference_version','version_id','int(10) unsigned NOT NULL AUTO_INCREMENT');

		$this->execute("
CREATE TABLE `measurement_type_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(85) COLLATE utf8_unicode_ci NOT NULL,
  `attachable` tinyint(4) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name` (`class_name`),
  KEY `acv_measurement_type_last_modified_user_id_fk` (`last_modified_user_id`),
  KEY `acv_measurement_type_created_user_id_fk` (`created_user_id`),
  CONSTRAINT `acv_measurement_type_created_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_measurement_type_last_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		$this->alterColumn('measurement_type_version','id','int(10) unsigned NOT NULL');
		$this->dropPrimaryKey('id','measurement_type_version');

		$this->createIndex('measurement_type_aid_fk','measurement_type_version','id');
		$this->addForeignKey('measurement_type_aid_fk','measurement_type_version','id','measurement_type','id');

		$this->addColumn('measurement_type_version','version_date',"datetime not null default '1900-01-01 00:00:00'");

		$this->addColumn('measurement_type_version','version_id','int(10) unsigned NOT NULL');
		$this->addPrimaryKey('version_id','measurement_type_version','version_id');
		$this->alterColumn('measurement_type_version','version_id','int(10) unsigned NOT NULL AUTO_INCREMENT');

		$this->execute("
CREATE TABLE `patient_measurement_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `measurement_type_id` int(10) unsigned NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  `last_modified_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `last_modified_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  PRIMARY KEY (`id`),
  KEY `acv_patient_measurement_last_modified_user_id_fk` (`last_modified_user_id`),
  KEY `acv_patient_measurement_created_user_id_fk` (`created_user_id`),
  KEY `acv_patient_measurement_patient_id_fk` (`patient_id`),
  KEY `acv_patient_measurement_measurement_type_id_fk` (`measurement_type_id`),
  CONSTRAINT `acv_patient_measurement_created_user_id_fk` FOREIGN KEY (`created_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_patient_measurement_last_modified_user_id_fk` FOREIGN KEY (`last_modified_user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `acv_patient_measurement_measurement_type_id_fk` FOREIGN KEY (`measurement_type_id`) REFERENCES `measurement_type` (`id`),
  CONSTRAINT `acv_patient_measurement_patient_id_fk` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		$this->alterColumn('patient_measurement_version','id','int(10) unsigned NOT NULL');
		$this->dropPrimaryKey('id','patient_measurement_version');

		$this->createIndex('patient_measurement_aid_fk','patient_measurement_version','id');
		$this->addForeignKey('patient_measurement_aid_fk','patient_measurement_version','id','patient_measurement','id');

		$this->addColumn('patient_measurement_version','version_date',"datetime not null default '1900-01-01 00:00:00'");

		$this->addColumn('patient_measurement_version','version_id','int(10) unsigned NOT NULL');
		$this->addPrimaryKey('version_id','patient_measurement_version','version_id');
		$this->alterColumn('patient_measurement_version','version_id','int(10) unsigned NOT NULL AUTO_INCREMENT');
	}

	public function down()
	{	$this->dropTable('measurement_reference_version');
		$this->dropTable('measurement_type_version');
		$this->dropTable('patient_measurement_version');
		}
}
