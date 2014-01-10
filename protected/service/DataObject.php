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

abstract class DataObject implements FhirCompatible
{
	/**
	 * Convert a FHIR object into a service layer object
	 *
	 * @param StdClass $fhirObject
	 * @return DataObject
	 */
	static public function fromFhir(\StdClass $fhirObject)
	{
		$fhirObject = clone($fhirObject);

		$fhirType = static::getFhirType();
		$schema = \FhirUtil::getSchema($fhirType);

		foreach ($fhirObject as $name => &$value) {
			if ($name == 'resourceType' || $name[0] == '_') continue;

			$valueType = $schema[$name]['type'];
			$class = static::getServiceClass($valueType);
			if (!$class) continue;

			switch (gettype($value)) {
				case "object":
					$value = $class::fromFhir($value);
					break;
				case "array":
					foreach ($value as &$v) {
						$v = $class::fromFhir($v);
					}
			}
		}

		$values = static::getFhirTemplate()->match($fhirObject);
		if (is_null($values)) {
			throw new InvalidStructure("Failed to match object of type '{$fhirType}'");
		}

		return new static($values);
	}

	static protected function getFhirType()
	{
		$class = new \ReflectionClass(get_called_class());
		return $class->getShortName();
	}

	static protected function getServiceClass($fhirType)
	{
		$class = "\Service\\{$fhirType}";
		return @class_exists($class) ? $class : null;
	}

	static protected function getFhirTemplate()
	{
		$class = new \ReflectionClass(get_called_class());
		$path = dirname($class->getFileName()) . '/fhir_templates/' . $class->getShortName() . '.json';

		return \DataTemplate::fromJsonFile($path);
	}

	/**
	 * @param array $values
	 */
	public function __construct(array $values)
	{
		foreach ($values as $name => $value) {
			$this->$name = $value;
		}
	}

	/**
	 * Compare two DataObjects in terms of their public properties
	 *
	 * @param DataObject $object
	 * @return boolean
	 */
	public function isEqual(DataObject $object)
	{
		if (get_class($this) != get_class($object)) {
			return false;
		}

		$rf_obj = new \ReflectionObject($this);
		$rf_props = $rf_obj->getProperties(\ReflectionProperty::IS_PUBLIC);

		foreach ($rf_props as $rf_prop) {
			if ($rf_prop->getValue($this) != $rf_prop->getValue($object)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Convert this object to it's FHIR representation
	 *
	 * @return StdClass
	 */
	public function toFhir()
	{
		$values = get_object_vars($this);
		$this->subObjectsToFhir($values);

		return static::getFhirTemplate()->generate($values);
	}

	private function subObjectsToFhir(&$values)
	{
		foreach ($values as &$value) {
			if ($value instanceof FhirCompatible) {
				$value = $value->toFhir();
			} elseif (is_array($value)) {
				$this->subObjectsToFhir($value);
			}
		}
	}
}
