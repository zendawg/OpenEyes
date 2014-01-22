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

class PracticeService extends Service
{
	static protected $primary_model = 'Practice';

	public function search(array $params)
	{
		$this->setUsedParams($params, 'id', 'identifier');

		$model = $this->getSearchModel();
		if (isset($params['id'])) $model->id = $id;
		if (isset($params['identifier'])) $model->code = $params['identifier'];

		return $this->getResourcesFromDataProvider($model->search());
	}

	/**
	 * @param Practice $resource
	 * @return int
	 */
	public function create(Practice $resource)
	{
		$model = new \Practice;
		$resource->toModel($model);
		return $model->id;
	}

	/**
	 * @param int $id
	 * @param Practice $resource
	 */
	public function update($id, Practice $resource)
	{
		$model = $this->model->findByPk($id);
		$resource->toModel($model);
	}

	public function fhirRecognised(\StdClass $fhirObject)
	{
		if (!isset($fhirObject->type)) return false;

		foreach ($fhirObject->type->coding as $coding) {
			if ($coding->system == 'http://openeyes.org.uk/fhir/vs/org-type' && $coding->code == 'practice') {
				return true;
			}
		}

		return false;
	}
}
