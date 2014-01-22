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

class Practice extends Resource
{
	static public function fromModel(\Practice $practice)
	{
		return new self(
			array(
				'code' => $practice->code,
				'primary_phone' => $practice->phone,
				'address' => $practice->contact->address ? Address::fromModel($practice->contact->address) : null,
			)
		);
	}

	static public function getFhirType()
	{
		return 'Organization';
	}

	public $code;

	public $primary_phone;
	public $address;

	public function toModel(\Practice $prac)
	{
		$prac->code = $this->code;
		$prac->phone = $this->primary_phone;

		if (!($contact = $prac->contact)) {
			$contact = new \Contact;
		}

		$contact->primary_phone = $this->primary_phone;
		Service::saveModel($contact);

		$prac->contact_id = $contact->id;
		Service::saveModel($prac);

		if ($this->address) {
			if (!($address = $contact->address)) {
				$address = new \Address;
				$address->parent_class = 'Contact';
				$address->parent_id = $contact->id;
			}

			$this->address->toModel($address);
		}
	}
}
