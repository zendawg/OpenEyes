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

class Gp extends Resource
{
	static public function fromModel(\Gp $gp)
	{
		return new self(
			array(
				'id' => $gp->id,
				'gnc' => $gp->nat_id,
				'title' => $gp->contact->title,
				'family_name' => $gp->contact->last_name,
				'given_name' => $gp->contact->first_name,
				'primary_phone' => $gp->contact->primary_phone,
				'address' => $gp->contact->address ? Address::fromModel($gp->contact->address) : null,
			)
		);

		return $resource;
	}

	static protected function getFhirType()
	{
		return 'Practitioner';
	}

	public $gnc;

	public $title;
	public $family_name;
	public $given_name;

	public $primary_phone;
	public $address;

	public function toModel(\Gp $gp)
	{
		$gp->nat_id = $this->gnc;
		$gp->obj_prof = $this->gnc;

		if (!($contact = $gp->contact)) {
			$contact = new \Contact;
		}

		$contact->title = $this->title;
		$contact->last_name = $this->family_name;
		$contact->first_name = $this->given_name;
		$contact->primary_phone = $this->primary_phone;

		Service::saveModel($contact);

		$gp->contact_id = $contact->id;
		Service::saveModel($gp);

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
