<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

class ImportScannedAssetsCommand extends CConsoleCommand {
	public function run($args) {
		$user = posix_getpwuid(posix_getuid());
		$user = $user['name'];

		if ($user != 'root' && $user != Yii::app()->params['apache_user']) {
			echo "This script must be run as root or as the apache user (".Yii::app()->params['apache_user'].")\n";
			exit;
		}

		if (!isset(Yii::app()->params['scans_directory'])) {
			throw new Exception("scans_directory parameter isn't set.");
		}

		if (!$dh = opendir(Yii::app()->params['scans_directory'])) {
			throw new Exception("Unable to access scans directory: ".Yii::app()->params['scans_directory']);
		}

		foreach (array(
			Yii::app()->basePath."/assets",
			Yii::app()->basePath."/assets/preview",
			Yii::app()->basePath."/assets/thumbnail",
			) as $path) {
			if (!file_exists($path)) {
				throw new Exception("Required directory doesn't exist: $path");
			}
			if (substr(sprintf("%o",fileperms($path)),-4) !== '0777') {
				throw new Exception("Directory must be world-writable: $path");
			}
		}

		$import = array();

		while ($file = readdir($dh)) {
			if (!preg_match('/^\.\.?$/',$file) && is_file(Yii::app()->params['scans_directory']."/$file")) {
				// ignore files modified under 60 seconds ago
				$stat = stat(Yii::app()->params['scans_directory']."/$file");

				if ((time() - $stat['mtime']) >= 60) {
					while(isset($import[$stat['mtime']])) {
						$stat['mtime']++;
					}
					$import[$stat['mtime']] = $file;
				}
			}
		}

		foreach ($import as $file) {
			echo "Importing: $file ... ";

			preg_match('/^(.*)\.([a-zA-Z0-9]+)$/',$file,$filename);

			$asset = new Asset;
			$asset->name = $file;
			$asset->title = $filename[1];
			$asset->mimetype = mime_content_type(Yii::app()->params['scans_directory']."/$file");
			$asset->filesize = filesize(Yii::app()->params['scans_directory']."/$file");
			$asset->extension = $filename[2];

			if (!$asset->save()) {
				throw new Exception("Failed to save asset: ".print_r($asset->getErrors(),true));
			}

			if (!@rename(Yii::app()->params['scans_directory']."/$file",Yii::app()->basePath."/assets/$asset->filename")) {
				$asset->delete();
				throw new Exception("Unable to move asset into place: [".Yii::app()->params['scans_directory']."/$file] => [".Yii::app()->basePath."/assets/$asset->id.$asset->extension]");
			}

			if ($user == 'root') {
				if (!@chown(Yii::app()->basePath."/assets/$asset->filename",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chown file to apache_user: ".Yii::app()->basePath."/assets/$asset->filename");
				}
				if (!@chgrp(Yii::app()->basePath."/assets/$asset->filename",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chgrp file to apache_user: ".Yii::app()->basePath."/assets/$asset->filename");
				}
			}

			shell_exec("convert -flatten -antialias -scale 150x150 -raise 3 \"".Yii::app()->basePath."/assets/$asset->filename\" \"".Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg\" 2>&1");

			if (!file_exists(Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg")) {
				throw new Exception("Failed to create thumbnail: ".Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg");
			}

			if ($user == 'root') {
				if (!@chown(Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chown file to apache_user: ".Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg");
				}
				if (!@chgrp(Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chgrp file to apache_user: ".Yii::app()->basePath."/assets/thumbnail/$asset->id.jpg");
				}
			}

			shell_exec("convert -flatten -antialias -scale 800x800 -raise 3 \"".Yii::app()->basePath."/assets/$asset->filename\" \"".Yii::app()->basePath."/assets/preview/$asset->id.jpg\" 2>&1");

			if (!file_exists(Yii::app()->basePath."/assets/preview/$asset->id.jpg")) {
				throw new Exception("Failed to create preview: ".Yii::app()->basePath."/assets/preview/$asset->id.jpg");
			}

			if ($user == 'root') {
				if (!@chown(Yii::app()->basePath."/assets/preview/$asset->id.jpg",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chown file to apache_user: ".Yii::app()->basePath."/assets/preview/$asset->id.jpg");
				}
				if (!@chgrp(Yii::app()->basePath."/assets/preview/$asset->id.jpg",Yii::app()->params['apache_user'])) {
					throw new Exception("Unable to chgrp file to apache_user: ".Yii::app()->basePath."/assets/preview/$asset->id.jpg");
				}
			}

			echo "OK\n";
		}

		closedir($dh);
	}
}
