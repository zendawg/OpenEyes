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

abstract class Service
{
	/**
	 * The primary model class for this resource
	 *
	 * @var string
	 */
	static protected $primary_model;

	/**
	 * @param string $resource_type
	 * @return Service
	 */
	static public function get($resource_type)
	{
		$class = static::$primary_model;
		return new static($resource_type, $class::model());
	}

	/**
	 * Save model object and throw a service layer exception on failure
	 *
	 * @param BaseActiveRecord $model
	 */
	static public function saveModel(\BaseActiveRecord $model)
	{
		if (!$model->save()) {
			throw new ValidationFailure("Validation failure on " . get_class($model), $model->errors);
		}
	}

	protected $resource_type;
	protected $model;
	protected $pk;

	/**
	 * @param string $resource_type
	 * @param BaseActiveRecord $model
	 */
	public function __construct($resource_type, \BaseActiveRecord $model)
	{
		$this->resource_type = $resource_type;
		$this->model = $model;
		$this->pk = $model->tableSchema->primaryKey;
	}

	/**
	 * Whether a resource with the specified ID exists
	 *
	 * @param scalar $id
	 * @return bool
	 */
	public function exists($id)
	{
		$crit = new \CDbCriteria;
		$crit->compare($this->pk, $id);
		return $this->model->exists($crit);
	}

	/**
	 * Fetch a single resource by ID
	 *
	 * @param scalar $id
	 * @return Resource
	 */
	public function fetch($id)
	{
		if (!($model = $this->model->findByPk($id))) {
			throw new \Exception("Invalid {$this->resource_type} ID: {$id}");
		}
		return $this->modelToResource($model);
	}

	/**
	 * Search for resources according to the parameters passed
	 *
	 * This default implementation only works with the id parameter
	 *
	 * @param array &$params Search parameters, the array will be modified to remove any that weren't used
	 * @return Resource[]
	 */
	public function search(array &$params)
	{
		$this->setUsedParams($params, 'id');

		if (isset($params['id']) && $this->exists($params['id'])) {
			return array($this->fetch($params['id']));
		}

		return array();
	}

	/**
	 * Whether the service knows how to create new resources
	 *
	 * @return bool
	 */
	public function canCreate()
	{
		return method_exists($this, 'create');
	}

	/**
	 * Whether the service knows how to update the specified resource
	 *
	 * @param string $id
	 * @return bool
	 */
	public function canUpdate($id)
	{
		return method_exists($this, 'update');
	}

	/**
	 * Whether the service is willing to delete the specified resource
	 *
	 * @param scalar $id
	 * @return bool
	 */
	public function canDelete($id)
	{
		return method_exists($this, 'delete');
	}

	/**
	 * Whether this service recognises the supplied FHIR object as one of its own
	 *
	 * @param StdClass $fhirObject
	 * @return boolean
	 */
	public function fhirRecognised(\StdClass $fhirObject)
	{
		return false;
	}

	/**
	 * Create a new resource using the supplied FHIR objet
	 *
	 * @param StdClass $fhirObject
	 * @return ResourceReference
	 */
	public function fhirCreate(\StdClass $fhirObject)
	{
		return new ResourceReference($this->resource_type, $this->create($this->fhirToResource($fhirObject)));
	}

	/**
	 * Update the specified resource using the supplied FHIR object
	 *
	 * @param scalar $id
	 * @param StdClass $fhirObject
	 */
	public function fhirUpdate($id, \StdClass $fhirObject)
	{
		$this->update($id, $this->fhirToResource($fhirObject));
	}

	/**
	 * Get an instance of the model class to fill in with search details
	 *
	 * @return BaseActiveRecord
	 */
	protected function getSearchModel()
	{
		$class = static::$primary_model;
		return new $class(null);
	}

	/**
	 * Remove all but the params specified (by name) from the list
	 *
	 * @param array $params
	 * @param string $param_name...
	 */
	protected function setUsedParams(array &$params)
	{
		$param_names = func_get_args();
		array_shift($param_names);

		$params = array_intersect_key($params, array_flip($param_names));
	}

	/**
	 * Get a list of resources from an AR data provider
	 *
	 * @param CActiveDataProvider $dataProvider
	 * @return Resource[]
	 */
	protected function getResourcesFromDataProvider(\CActiveDataProvider $provider)
	{
		$class = $this->resource_type;
		$resources = array();
		foreach ($provider->getData() as $model) {
			$resources[] = $this->modelToResource($model);
		}
		return $resources;
	}

	/**
	 * @param BaseActiveRecord $model
	 * @return Resource
	 */
	protected function modelToResource(\BaseActiveRecord $model)
	{
		$class = $this->resource_type;
		return $class::fromModel($model);
	}

	/**
	 * @param StdClass $fhirObject
	 * @return Resource
	 */
	protected function fhirToResource(\StdClass $fhirObject)
	{
		$class = $this->resource_type;
		return $class::fromFhir($fhirObject);
	}
}
