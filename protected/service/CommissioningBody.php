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

class CommissioningBody extends Resource
{
	static public function fromModel(\CommissioningBody $cb)
	{
		return new self(
			array(
				'code' => $cb->code,
				'name' => $cb->name,
				'address' => $cb->contact->address ? Address::fromModel($cb->contact->address) : null,
			)
		);
	}

	static public function getFhirType()
	{
		return 'Organization';
	}

	public $code;
	public $name;
	public $address;

	public function toModel(\CommissioningBody $cb)
	{
		$cb->code = $this->code;
		$cb->name = $this->name;

		// Hard-coded for now
		$type = \CommissioningBodyType::model()->findByAttributes(array('shortname' => 'CCG'));
		if (!$type) {
			throw new \Exception("Failed to find commissioning body type 'CCG'");
		}
		$cb->commissioning_body_type_id = $type->id;

		if ($this->address) {
			if (!($contact = $cb->contact)) {
				$contact = new \Contact;
			}
			Service::saveModel($contact);
			$cb->contact_id = $contact->id;

			if (!($address = $contact->address)) {
				$address = new \Address;
				$address->parent_class = 'Contact';
				$address->parent_id = $contact->id;
			}

			$this->address->toModel($address);
		}

		Service::saveModel($cb);

		// Associate with any services already in the db
		$crit = new \CDbCriteria;
		$crit->compare('code', $this->code);
		\CommissioningBodyService::model()->updateAll(array('commissioning_body_id' => $cb->id), $crit);
	}
}
