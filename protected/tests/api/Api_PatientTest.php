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

class Api_PatientTest extends FhirTestCase
{
	public function testRead()
	{
		$this->get('Patient/10001');
		$this->assertXPathEquals('Patient', 'local-name()');
		$this->assertXPathEquals('1000001', 'string(./fhir:identifier/fhir:label[@value="Hospital Number"]/../fhir:value/@value)');
		$this->assertXPathEquals(
			'3487695836',
			'string(./fhir:identifier/fhir:system[@value="http://www.datadictionary.nhs.uk/data_dictionary/attributes/n/nhs/nhs_number_de.asp"]/../fhir:value/@value)'
		);
		$this->assertXPathEquals('Mrs', 'string(./fhir:name/fhir:prefix/@value)');
		$this->assertXPathEquals('Chay', 'string(./fhir:name/fhir:given/@value)');
		$this->assertXPathEquals('Close', 'string(./fhir:name/fhir:family/@value)');
		$this->assertXPathEquals('04130 4541610', 'string(./fhir:telecom/fhir:system[@value="phone"]/../fhir:value/@value)');
		$this->assertXPathEquals('F', 'string(./fhir:gender/fhir:coding/fhir:code/@value)');
		$this->assertXPathEquals('1920-11-02', 'string(./fhir:birthDate/@value)');
	}

	public function testReadNoCareProviders()
	{
		$this->get('Patient/10001');
		$this->assertXPathCount(0, './fhir:careProvider');
	}

	public function testReadGpOnly()
	{
		$this->get('Patient/10002');
		$this->assertXPathCount(1, './fhir:careProvider');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Practitioner/gp-1"]');
	}

	public function testReadPracticeOnly()
	{
		$this->get('Patient/10005');
		$this->assertXPathCount(1, './fhir:careProvider');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/prac-1"]');
	}

	public function testReadCbOnly()
	{
		$this->get('Patient/10017');
		$this->assertXPathCount(1, './fhir:careProvider');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/cb-1"]');
	}

	public function testReadGpAndPractice()
	{
		$this->get('Patient/10015');
		$this->assertXPathCount(2, './fhir:careProvider');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Practitioner/gp-2"]');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/prac-3"]');
	}

	public function testReadGpPracticeAndTwoCbs()
	{
		$this->get('Patient/10028');
		$this->assertXPathCount(4, './fhir:careProvider');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Practitioner/gp-3"]');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/prac-2"]');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/cb-1"]');
		$this->assertXPathCount(1, './fhir:careProvider/fhir:reference[@value="Organization/cb-2"]');
	}

	public function testUpdate()
	{
		$source = file_get_contents(__DIR__ . '/files/Patient.xml');
		$this->put('Patient/19969', $source);

		$this->get('Patient/19969');
		$this->assertXmlEquals($source);
	}

	public function testCreate()
	{
		$source = file_get_contents(__DIR__ . '/files/Patient.xml');
		$this->post('Patient', $source);

		$this->get($this->response->getLocation());
		$this->assertXmlEquals($source);
	}

	public function testSearchByIdNotFound()
	{
		$this->get('Patient?_id=1');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathCount(0, './atom:entry');
	}

	public function testSearchByIdFound()
	{
		$this->get('Patient?_id=10015');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathEquals($this->client->getBaseUrl(), 'string(./atom:link[@rel="base"]/@href)');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient?_id=10015', 'string(./atom:link[@rel="self"]/@href)');
		$this->assertXPathCount(1, './atom:entry');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient/10015', 'string(./atom:entry/atom:id/text())');
		$this->assertXPathEquals('Patient', 'local-name(./atom:entry/atom:content/*)');
	}

	public function testSearchByHosNum()
	{
		$this->get('Patient?identifier=1000015');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathEquals($this->client->getBaseUrl(), 'string(./atom:link[@rel="base"]/@href)');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient?identifier=1000015', 'string(./atom:link[@rel="self"]/@href)');
		$this->assertXPathCount(1, './atom:entry');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient/10015', 'string(./atom:entry/atom:id/text())');
		$this->assertXPathEquals('Patient', 'local-name(./atom:entry/atom:content/*)');
	}

	public function testSearchByNhsNum()
	{
		$this->get('Patient?identifier=9857188996');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathEquals($this->client->getBaseUrl(), 'string(./atom:link[@rel="base"]/@href)');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient?identifier=9857188996', 'string(./atom:link[@rel="self"]/@href)');
		$this->assertXPathCount(1, './atom:entry');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient/10040', 'string(./atom:entry/atom:id/text())');
		$this->assertXPathEquals('Patient', 'local-name(./atom:entry/atom:content/*)');
	}

	public function testSearchByFamilyName()
	{
		$this->get('Patient?family=Smith');
		$this->assertXPathEquals($this->client->getBaseUrl(), 'string(./atom:link[@rel="base"]/@href)');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient?family=Smith', 'string(./atom:link[@rel="self"]/@href)');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathCount(6, './atom:entry');
	}

	public function testSearchByFullName()
	{
		$this->get('Patient?given=Doreen&family=Smith');
		$this->assertXPathEquals($this->client->getBaseUrl(), 'string(./atom:link[@rel="base"]/@href)');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient?given=Doreen&family=Smith', 'string(./atom:link[@rel="self"]/@href)');
		$this->assertXPathEquals('feed', 'local-name()');
		$this->assertXPathCount(1, './atom:entry');
		$this->assertXPathEquals($this->client->getBaseUrl() . '/Patient/11915', 'string(./atom:entry/atom:id/text())');
		$this->assertXPathEquals('Patient', 'local-name(./atom:entry/atom:content/*)');

	}
}
