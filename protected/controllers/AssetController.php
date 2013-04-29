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

class AssetController extends BaseController
{
	public function filters()
	{
		return array('accessControl');
	}

	public function accessRules()
	{
		return array(
			array('allow',
				'users' => array('@')
			),
			// non-logged in can't view anything
			array('deny',
				'users' => array('?')
			),
		);
	}

	public function actionDownload($id) {
		if (!$asset = Asset::model()->findByPk($id)) {
			throw new Exception("Asset not found: $id");
		}

		header("Content-Type: ".$asset->mimetype);
		header("Content-Length: ".$asset->filesize);
		header("Content-Disposition: attachment; filename=\"".$asset->name."\"");
		header("Pragma: no-cache");
		header("Expires: 0");

		readfile($asset->path);
	}

	public function actionPreview($id) {
		if (!$asset = Asset::model()->findByPk($id)) {
			throw new Exception("Asset not found: $id");
		}

		$path = Yii::app()->basePath."/assets/preview/".$asset->id.".jpg";

		if (!file_exists($path)) {
			throw new Exception("File not found: $path");
		}

		header("Content-Type: image/jpeg");
		header("Content-Length: ".filesize($path));

		readfile($path);
	}

	public function actionThumbnail($id) {
		if (!$asset = Asset::model()->findByPk($id)) {
			throw new Exception("Asset not found: $id");
		}
		
		$path = Yii::app()->basePath."/assets/thumbnail/".$asset->id.".jpg";

		if (!file_exists($path)) {
			throw new Exception("File not found: $path");
		} 
		
		header("Content-Type: image/jpeg");
		header("Content-Length: ".filesize($path));
	 
		readfile($path);
	}
}
