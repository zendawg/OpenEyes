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

class GpService extends Service
{
	static protected $primary_model = 'Gp';

	public function search(array $params)
	{
		$this->setUsedParams($params, 'id', 'identifier');

		$model = $this->getSearchModel();
		if (isset($params['id'])) $model->id = $id;
		if (isset($params['identifier'])) $model->nat_id = $params['identifier'];

		return $this->getResourcesFromDataProvider($model->search());
	}

	/**
	 * @param Gp $resource
	 * @return int
	 */
	public function create(Gp $resource)
	{
		$model = new \Gp;
		$resource->toModel($model);
		return $model->id;
	}

	/**
	 * @param int $id
	 * @param Gp $resource
	 */
	public function update($id, Gp $resource)
	{
		$model = $this->model->findByPk($id);
		$resource->toModel($model);
	}

	/**
	 * Delete the specified GP record, first unassociating it from any patients
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$crit = new \CDbCriteria;
		$crit->compare('gp_id', $id);
		\Patient::model()->updateAll(array('gp_id' => null), $crit);
		\Gp::model()->deleteAllByAttributes(array('id' => $id));
	}

	public function fhirRecognised(\StdClass $fhirObject)
	{
		if (!isset($fhirObject->role)) return false;

		foreach ($fhirObject->role as $role) {
			foreach ($role->coding as $coding) {
				if ($coding->system == 'http://openeyes.org.uk/fhir/vs/practitioner-role' && $coding->code == 'gp') {
					return true;
				}
			}
		}

		return false;
	}
}
