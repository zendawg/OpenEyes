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

class MediaService extends Service
{
	static protected $primary_model = 'ProtectedFile';

	public function search(array &$params)
	{
		$this->setUsedParams($params, 'id');

		$model = $this->getSearchModel();
		if (isset($params['id'])) $model->id = $params['id'];

		$searchParams = array('pageSize' => null);
        
		return $this->getResourcesFromDataProvider($model->search($searchParams));
	}

	/**
	 * @param Patient $resource
	 * @return int
	 */
	public function create(Media $resource)
	{
		$name = $resource->content_title;
		$model = \ProtectedFile::createForWriting($name);
		// all content is base64 encoded, so decode it:
		file_put_contents($model->getPath(), base64_decode($resource->content));
		$resource->toModel($model);
		return $model->id;
	}

	/**
	 * @param int $id
	 * @param Patient $resource
	 */
	public function update($id, ProtectedFile $resource)
	{
		$model = $this->model->findByPk($id);
		$resource->toModel($model);
	}

	/**
	 * We only have one Patient resource so assume it's for us
	 */
	public function fhirRecognised(\StdClass $fhirObject)
	{
		return true;
	}
}
