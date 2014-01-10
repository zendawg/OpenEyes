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

class FhirMap extends \CApplicationComponent
{
	public $map;

	/**
	 * Get a service layer resource reference from a FHIR relative URL
	 *
	 * @param string $url
	 * @return ResourceReference|null
	 */
	public function getReference($url)
	{
		if (!preg_match('|^(\w+)/(\w+)-(\w+)$|', $url, $m)) return null;

		$fhirType = strtolower($m[1]);
		$prefix = $m[2];
		$id = $m[3];

		if (!isset($this->map[$fhirType][$prefix])) return null;

		return new ResourceReference($this->map[$fhirType][$prefix], $m[3]);
	}

	/**
	 * Get a FHIR relative URL for the specified service layer reference
	 *
	 * @param ResourceReference $ref
	 * @return string
	 */
	public function getUrl(ResourceReference $ref)
	{
		$prefix = false;
		foreach ($this->map as $fhir_resource_type => $mappings) {
			if (($prefix = array_search($ref->getResourceType(), $mappings))) {
				break;
			}
		}

		if (!$prefix) {
			throw new \Exception("Failed to find a URL mapping for resource type '{$ref->getResourceType()}'");
		}

		return "{$fhir_resource_type}/{$prefix}-{$ref->getId()}";
	}

	/**
	 * Does the specified FHIR resource type appear in the map?
	 *
	 * @param string $fhir_resource_type
	 * @return bool
	 */
	public function isResourceTypeSupported($fhir_resource_type)
	{
		return isset($this->map[$fhir_resource_type]);
	}

	/**
	 * Get all services corresponding to the specified FHIR resource type
	 *
	 * @param string $fhir_resource
	 * @return Service[]
	 */
	public function getServices($fhir_resource_type)
	{
		if (!isset($this->map[$fhir_resource_type])) {
			return array();
		}

		$services = array();
		foreach ($this->map[$fhir_resource_type] as $resource_type) {
			$services[] = \Yii::app()->serviceLocator->getServiceForResourceType($resource_type);
		}
		return $services;
	}
}
