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

class Patient extends Resource
{
	static public function fromModel(\Patient $patient)
	{
		$gp_ref = $patient->gp_id ? new ResourceReference('Service\\Gp', $patient->gp_id) : null;
		$prac_ref = $patient->practice_id ? new ResourceReference('Service\\Practice', $patient->practice_id) : null;

		$cb_refs = array();
		foreach ($patient->commissioningbodies as $cb) {
			$cb_refs[] = new ResourceReference('Service\CommissioningBody', $cb->id);
		}

		$resource = new self(
			array(
				'id' => $patient->id,
				'nhs_num' => $patient->nhs_num,
				'hos_num' => $patient->hos_num,
				'title' => $patient->contact->title,
				'family_name' => $patient->contact->last_name,
				'given_name' => $patient->contact->first_name,
				'gender' => $patient->gender,
				'birth_date' => $patient->dob,
				'date_of_death' => $patient->date_of_death,
				'primary_phone' => $patient->contact->primary_phone,
				'addresses' => array_map(array('Service\\Address', 'fromModel'), $patient->contact->addresses),
				'care_providers' => array_merge(array_filter(array($gp_ref, $prac_ref)), $cb_refs),
				'gp_ref' => $gp_ref,
				'prac_ref' => $prac_ref,
				'cb_refs' => $cb_refs
			)
		);

		return $resource;
	}

	static public function fromFhir(\StdClass $fhirObject)
	{
		$patient = parent::fromFhir($fhirObject);

		foreach ($patient->care_providers as $ref) {
			switch ($ref->getResourceType()) {
				case 'Service\\Gp':
					$patient->gp_ref = $ref;
					break;
				case 'Service\\Practice':
					$patient->prac_ref = $ref;
					break;
				case 'Service\\CommissioningBody':
					$patient->cb_refs[] = $ref;
					break;
			}
		}

		return $patient;
	}

	public $nhs_num;
	public $hos_num;

	public $title;
	public $family_name;
	public $given_name;

	public $gender;

	public $birth_date;
	public $date_of_death;

	public $primary_phone;
	public $addresses;

	public $care_providers = array();

	protected $gp_ref = null;
	protected $prac_ref = null;
	protected $cb_refs = array();

	/**
	 * @return Gp|null
	 */
	public function getGp()
	{
		return $this->gp_ref ? $this->gp_ref->resolve() : null;
	}

	/**
	 * @return Practice|null
	 */
	public function getPractice()
	{
		return $this->prac_ref ? $this->prac_ref->resolve() : null;
	}

	/**
	 * @return CommissioningBody[]
	 */
	public function getCommissioningBodys()
	{
		$cbs = array();
		foreach ($this->cb_refs as $cb_ref) {
			$cbs[] = $cb_ref->resolve();
		}
		return $cbs;
	}

	public function toModel(\Patient $patient)
	{
		$patient->nhs_num = $this->nhs_num;
		$patient->hos_num = $this->hos_num;
		$patient->gender = $this->gender;
		$patient->dob = $this->birth_date;
		$patient->date_of_death = $this->date_of_death;
		$patient->gp_id = $this->gp_ref ? $this->gp_ref->getId() : null;
		$patient->practice_id = $this->prac_ref ? $this->prac_ref->getId() : null;

		if (!($contact = $patient->contact)) {
			$contact = new \Contact;
		}
		$contact->title = $this->title;
		$contact->last_name = $this->family_name;
		$contact->first_name = $this->given_name;
		$contact->primary_phone = $this->primary_phone;
		Service::saveModel($contact);

		$patient->contact_id = $contact->id;
		Service::saveModel($patient);

		$cur_addrs = array();
		foreach ($contact->addresses as $addr) {
			$cur_addrs[$addr->id] = Address::fromModel($addr);
		}

		$add_addrs = array();
		$matched_ids = array();

		foreach ($this->addresses as $new_addr) {
			$found = false;
			foreach ($cur_addrs as $id => $cur_addr) {
				if ($cur_addr->isEqual($new_addr)) {
					$matched_ids[] = $id;
					$found = true;
					unset($cur_addrs[$id]);
					break;
				}
			}
			if (!$found) $add_addrs[] = $new_addr;
		}

		$crit = new \CDbCriteria;
		$crit->compare('parent_class', 'Contact')->compare('parent_id', $contact->id)->addNotInCondition('id', $matched_ids);
		\Address::model()->deleteAll($crit);

		foreach ($add_addrs as $add_addr) {
			$addr = new \Address;
			$addr->parent_class = 'Contact';
			$addr->parent_id = $contact->id;
			$add_addr->toModel($addr);
		}

		$cur_cb_ids = array();
		foreach ($patient->commissioningbodies as $cb) {
			$cur_cb_ids[] = $cb->id;
		}

		$new_cb_ids = array();
		foreach ($this->cb_refs as $cb_ref) {
			$new_cb_ids[] = $cb_ref->getId();
		};

		$add_cb_ids = array_diff($new_cb_ids, $cur_cb_ids);
		$del_cb_ids = array_diff($cur_cb_ids, $new_cb_ids);

		foreach ($add_cb_ids as $cb_id) {
			$cba = new \CommissioningBodyPatientAssignment;
			$cba->commissioning_body_id = $cb_id;
			$cba->patient_id = $patient->id;
			Service::saveModel($cba);
		}

		if ($del_cb_ids) {
			$crit = new \CDbCriteria;
			$crit->compare('patient_id', $patient->id)->addInCondition('commissioning_body_id', $del_cb_ids);
			\CommissioningBodyPatientAssignment::model()->deleteAll($crit);
		}
	}
}
