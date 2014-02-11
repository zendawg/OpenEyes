<?php

/**
 * (C) OpenEyes Foundation, 2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

namespace Service;

class Media extends Resource {

	public $name;
	public $content;
	public $mime_type;
	public $width;
	public $height;
	public $dateTime;
	public $type;

	static public function fromModel(\ProtectedFile $file) {
		$path = $file->getPath();
		$image_props = getimagesize($path);
		$width = $image_props[0];
		$height = $image_props[1];
		$contents = file_get_contents($file->getPath());
		$data = base64_encode($contents);
		$resource = new self(
						array(
							'id' => $file->id,
							'name' => $file->name,
							'width' => $width,
							'height' => $height,
							'type' => Media::getMediaType($file->mimetype),
							'content' => $data,
							'title' => $file->name,
							'mime_type' => $file->mimetype,
						)
		);

		return $resource;
	}

	/**
	 * 
	 * @param type $mime_type
	 * @return one of 'photo', 'audio' or 'video', or null.
	 */
	private static function getMediaType($mime_type) {
		$type = null;
		switch ($mime_type) {
			case 'image/tiff':
			case 'image/jpeg':
			case 'image/gif':
			case 'image/png':
			case 'image/bmp':
				$type = 'photo';
				break;
		}
		return $type;
	}

	static public function fromFhir(\StdClass $fhirObject) {
		$file = parent::fromFhir($fhirObject);

		return $file;
	}

	public function toModel(\ProtectedFile $file) {
		$file->mimetype = $this->mime_type;
		$file->name = $this->name;
		Service::saveModel($file);
	}

}
