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

/**
 * Internally implemented services
 */
abstract class InternalService extends Service
{
	// Available operations (values taken from http://hl7.org/implement/standards/fhir/type-restful-operation.html)
	const OP_READ = 'read';
	const OP_UPDATE = 'update';
	const OP_DELETE = 'delete';
	const OP_CREATE = 'create';
	const OP_SEARCH = 'search-type';

	/**
	 * Operations supported by this service
	 */
	static protected $operations = array();

	/**
	 * Get the name of this service, ie the unqualified class name of the resource type it handles
	 *
	 * @return string
	 */
	static public function getServiceName()
	{
		$class = new \ReflectionClass(static::getResourceClass());
		return $class->getShortName();
	}

	/**
	 * Get the class name of the resource type managed by this service
	 *
	 * @return string
	 */
	static public function getResourceClass()
	{
		return preg_replace('/Service$/', '', get_called_class());
	}

	/**
	 * Get the last modified date for a resource without fetching it
	 *
	 * @param scalar $id
	 * @return int
	 */
	public function getLastModified($id)
	{
		throw new ProcessingNotSupported("Read operation not supported");
	}

	/**
	 * Fetch a single resource by ID
	 *
	 * @param scalar $id
	 * @return Resource
	 */
	public function read($id)
	{
		throw new ProcessingNotSupported("Read operation not supported");
	}

	/**
	 * Update a resource by ID
	 *
	 * @param scalar $id
	 * @param Resource $resource
	 */
	public function update($id, Resource $resource)
	{
		throw new ProcessingNotSupported("Update operation not supported");
	}

	/**
	 * Delete a resource by ID
	 *
	 * @param scalar $id
	 */
	public function delete($id)
	{
		throw new ProcessingNotSupported("Delete operation not supported");
	}

	/**
	 * Create a new resource and return its ID
	 *
	 * @param Resource $resource
	 * @return scalar
	 */
	public function create(Resource $resource)
	{
		throw new ProcessingNotSupported("Create operation not supported");
	}

	/**
	 * Search for resources according to the parameters passed
	 *
	 * @param array &$params Search parameters, the array will be modified to remove any that weren't used
	 * @return Resource[]
	 */
	public function search(array &$params)
	{
		throw new ProcessingNotSupported("Search operation not supported");
	}
	/**
	 * @param string $operation
	 * @return bool
	 */
	public function supportsOperation($operation)
	{
		return in_array($operation, static::$operations);
	}

	/**
	 * Create a new resource using the supplied FHIR object
	 *
	 * @param StdClass $fhirObject
	 * @return ResourceReference
	 */
	public function fhirCreate(\StdClass $fhirObject)
	{
		return new InternalReference($this->getServiceName(), $this->create($this->fhirToResource($fhirObject)));
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
	 * @param StdClass $fhirObject
	 * @return Resource
	 */
	protected function fhirToResource(\StdClass $fhirObject)
	{
		$class = $this->getResourceClass();
		return $class::fromFhir($fhirObject);
	}
}