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

class FhirUtilTest extends PHPUnit_Framework_TestCase
{
	static private $use_errors;

	static public function setUpBeforeClass()
	{
		self::$use_errors = libxml_use_internal_errors(true);
	}

	static public function tearDownAfterClass()
	{
		libxml_use_internal_errors(self::$use_errors);
	}

	public function xmlDataProvider()
	{
		$data = array();

		foreach (glob(__DIR__ . '/' . __CLASS__ . '/*.xml') as $xml_path) {
			preg_match('|([^/]+)\.xml$|', $xml_path, $m);
			$name = $m[1];

			$json_path = __DIR__ . '/' . __CLASS__ . "/{$name}.json";

			$data[] = array(
				$name,
				file_get_contents($xml_path),
				file_get_contents($json_path),
			);
		}

		return $data;
	}

	/**
	 * @dataProvider xmlDataProvider
	 */
	public function testXmlToJson($name, $xml, $json)
	{
		if ($name == 'bundle') $this->markTestSkipped("Bundle parsing not implemented yet");

		$expected = FhirUtil::parseJson($json);
		$this->assertEquals($expected, FhirUtil::parseXml($xml));
	}

	public function testParseXml_Malformed()
	{
		$this->assertEquals(null, FhirUtil::parseXml('>'));
	}

	/**
	 * @dataProvider xmlDataProvider
	 */
	public function testJsonToXml($name, $xml, $json)
	{
		$expected_doc = new DOMDocument;
		$expected_doc->loadXml($xml);

		$actual = FhirUtil::renderXml(FhirUtil::parseJson($json));
		$actual_doc = new DOMDocument;
		$actual_doc->loadXml($actual);

		$this->assertEqualXMLStructure($expected_doc->documentElement, $actual_doc->documentElement, true);
	}
}
