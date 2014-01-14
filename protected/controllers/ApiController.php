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

class ApiController extends CController
{
	const JSON_MIMETYPE = 'application/json+fhir; charset=utf-8';
	const XML_MIMETYPE = 'application/xml+fhir; charset=utf-8';
	const ATOM_MIMETYPE = 'application/atom+xml; charset=utf-8';

	protected $output_format;

	public function beforeAction($action)
	{
		// Output format can be selected using a special GET param or by Accept: header
		if (isset($_GET['_format'])) {
			if (preg_match('/json/', $_GET['_format'])) {
				$this->output_format = 'json';
			} elseif (preg_match('/xml/', $_GET['_format'])) {
				$this->output_format = 'xml';
			}
		} else {
			foreach (Yii::app()->request->preferredAcceptTypes as $type) {
				if ($type['baseType'] == 'json' || $type['baseType'] == 'xml') {
					$this->output_format = $type['baseType'];
					break;
				}
				if ($type['subType'] == 'json' || $type['subType'] == 'xml') {
					$this->output_format = $type['subType'];
					break;
				}
			}
		}

		if (!isset($this->output_format)) {
			$this->sendResponse(406);
		}

		// If asked for a resource type we don't support we might as well bail out here
		if (isset($_GET['resource_type'])) {
			$resource_type = $_GET['resource_type'];
			if (!Yii::app()->fhirMap->isResourceTypeSupported($resource_type)) {
				$this->sendResource(
					Service\OperationOutcome::singleIssue(
						FhirValueSet::ISSUESEVERITY_FATAL,
						FhirValueSet::ISSUETYPE_PROCESSING_NOT_FOUND,
						"Resource type '{$resource_type}' not supported"
					),
					404
				);
			}
		}

		Yii::app()->attachEventHandler("onError", array($this, "handleError"));
		Yii::app()->attachEventHandler("onException", array($this, "handleException"));

		return true;
	}

	/**
	 * @param CErrorEvent $event
	 */
	public function handleError(CErrorEvent $event)
	{
		$this->sendResource(
			Service\OperationOutcome::singleIssue(
				FhirValueSet::ISSUESEVERITY_FATAL,
				FhirValueSet::ISSUETYPE_TRANSIENT_EXCEPTION,
				YII_DEBUG ? "{$event->message} in {$event->file}:{$event->line}" : "Internal Error"
			),
			500
		);
	}

	/**
	 * @param CExceptionEvent $event
	 */
	public function handleException(CExceptionEvent $event)
	{
		$e = $event->exception;

		if ($e instanceof Service\ServiceException) {
			$this->sendResource($e->toOperationOutcome(), $e->httpStatus);
		}

		$issue_type = FhirValueSet::ISSUETYPE_TRANSIENT_EXCEPTION;
		$message = 'Internal Error';
		$status = 500;

		if ($e instanceof CDbException && substr($e->errorInfo[0], 0, 2) == '23') {  // SQLSTATE Constraint Violation
			$issue_type = FhirValueSet::ISSUETYPE_PROCESSING_CONFLICT;
			$message = 'Constraint Violation';
			$status = 409;
		}

		$this->sendResource(
			Service\OperationOutcome::singleIssue(
				FhirValueSet::ISSUESEVERITY_FATAL,
				$issue_type,
				YII_DEBUG ? "$e" : $message
			),
			$status
		);
	}

	/**
	 * API root
	 */
	public function actionIndex()
	{
		$this->sendResponse(501);
	}

	// INSTANCE LEVEL INTERACTIONS

	/**
	 * Read (view) resource
	 *
	 * @param string $resource_type
	 * @param string $id
	 */
	public function actionRead($resource_type, $id)
	{
		$ref = $this->getRef($resource_type, $id);
		$this->sendResource($ref->resolve());
	}

	/**
	 * Read (view) previous version of resource
	 *
	 * @param string $resource_type
	 * @param string $id
	 * @param string $vid
	 */
	public function actionVread($resource_type, $id, $vid)
	{
		$this->sendResponse(501);
	}

	/**
	 * Update resource
	 *
	 * @param string $resource_type
	 * @param string $id
	 */
	public function actionUpdate($resource_type, $id)
	{
		$ref = $this->getRef($resource_type, $id, false);

		if (!$ref->isValid()) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
					"Resource not found and client-defined IDs are not supported"
				),
				405
			);
		}

		if (!$ref->canUpdate()) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
					"Cannot update resource '{$resource_type}/{$id}'"
				),
				405
			);
		}

		$tx = Yii::app()->db->beginTransaction();
		$ref->fhirUpdate($this->parseInput());
		$tx->commit();

		header('Location: ' . $this->createAbsoluteUrl('/read', array('resource_type' => $resource_type, 'id' => $id)));
		header('Last-modified: ' . date('r'));

		$this->sendResource(
			Service\OperationOutcome::singleIssue(
				FhirValueSet::ISSUESEVERITY_INFORMATION,
				null,
				"Resource {$resource_type}/{$id} successfully updated"
			),
			200
		);
	}

	/**
	 * Delete resource
	 *
	 * @param string $resource_type
	 * @param string $id
	 */
	public function actionDelete($resource_type, $id)
	{
		$ref = $this->getRef($resource_type, $id);

		if (!$ref->canDelete()) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
					"Cannot delete resource '{$resource_type}/{$id}'"
				),
				405
			);
		}

		$tx = Yii::app()->db->beginTransaction();
		$ref->delete();
		$tx->commit();

		$this->sendResponse(204);
	}

	// TYPE LEVEL INTERACTIONS

	/**
	 * Create resource
	 *
	 * @param string $resource_type
	 */
	public function actionCreate($resource_type)
	{
		$fhirObject = $this->parseInput();
		if (strtolower($fhirObject->resourceType) != strtolower($resource_type)) {
			throw new Service\InvalidValue("Invalid resource type '{$fhirObject->resourceType}', expecting '{$resource_type}'");
		}

		$services = Yii::app()->fhirMap->getServices($resource_type);
		$canCreate = false;
		foreach ($services as $service) {
			if ($service->canCreate()) {
				$canCreate = true;

				if ($service->fhirRecognised($fhirObject)) {
					$serviceChosen = $service;
					break;
				}
			}
		}

		if (!isset($serviceChosen)) {
			if ($canCreate) {
				throw new Service\ProcessingNotSupported("No registered service recognised the provided resource type");
			} else {
				$this->sendResource(
					Service\OperationOutcome::singleIssue(
						FhirValueSet::ISSUESEVERITY_FATAL,
						FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
						"Cannot create resources of type '{$resource_type}'"
					),
					405
				);
			}
		}

		$tx = Yii::app()->db->beginTransaction();
		$ref = $serviceChosen->fhirCreate($fhirObject);
		$tx->commit();

		$url = Yii::app()->fhirMap->getUrl($ref);

		header('Location: ' . Yii::app()->baseUrl . '/api/' . $url);
		$this->sendResource(
			Service\OperationOutcome::singleIssue(
				FhirValueSet::ISSUESEVERITY_INFORMATION,
				null,
				"Resource '{$url}' successfully created"
			),
			201
		);
	}

	/**
	 * Search for resource(s)
	 *
	 * @param $resource_type
	 */
	public function actionSearch($resource_type)
	{
		// We keep track of the search params that were actually used in order to create a self URL for the bundle (http://www.hl7.org/implement/standards/fhir/search.html#conformance)
		$used_params = array('resource_type' => $resource_type);
		foreach (array('_format', '_count') as $param) {
			if (isset($_REQUEST[$param])) $used_params[$param] = $_REQUEST[$param];
		}

		// Get a list of possible services for this resource type
		if (isset($_REQUEST['_id'])) {
			// Special case for when there's a resource ID as part of the search (which doesn't seem very useful...)
			// We grab the relevant service and pass it a service layer ID in the 'id' param
			$used_params['_id'] = $_REQUEST['_id'];
			$ref = Yii::app()->fhirMap->getReference("{$resource_type}/{$_REQUEST['_id']}");
			if ($ref) {
				$services = array($ref->getService());
				$_REQUEST['id'] = $ref->getId();
			} else {
				$services = array();  // Not an error: we support the resource type, you just asked for an ID that doesn't exist
			}
		} else {
			$services = Yii::app()->fhirMap->getServices($resource_type);
		}

		$count = isset($_REQUEST['_count']) ? intval($_REQUEST['_count']) : null;

		$resources = array();
		foreach ($services as $service) {
			if (!is_null($count) && count($resources) >= $count) break;
			$params  = $_REQUEST;  // copy because the service modifies it to let us know which were used
			$resources = array_merge($resources, $service->search($params));  // TODO: track current count and send to service as a limit
			$used_params += $params;
		}

		if (!is_null($count)) $resources = array_slice($resources, 0, $count);

		$used_params = array_intersect_key($_REQUEST, $used_params);  // In case any of the services modified the values
		unset($used_params['id']);  // Service layer ID, not relevant to the API

		$bundle = Service\Bundle::create(
			"Search results",
			$this->createUrl("api/search", $used_params),
			Yii::app()->createAbsoluteUrl('api'),
			$resources
		);

		$this->sendBundle($bundle);
	}

	public function actionBadRequest()
	{
		$this->sendResource(
			Service\OperationOutcome::singleIssue(
				FhirValueSet::ISSUESEVERITY_FATAL,
				FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
				"Invalid URL or method"
			),
			400
		);
	}

	protected function getRef($resource_type, $id, $checkExists = true)
	{
		$ref = Yii::app()->fhirMap->getReference("{$resource_type}/{$id}");

		if (!$ref) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_PROCESSING_NOT_SUPPORTED,
					"Unrecognised ID format: '{$id}' for resource type '{$resource_type}'"
				),
				404
			);
		}

		if ($checkExists && !$ref->isValid()) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_PROCESSING_NOT_FOUND,
					"Resource of type '{$resource_type}' with ID '{$id}' not found"
				),
				404
			);
		}

		return $ref;
	}

	protected function parseInput()
	{
		$body = Yii::app()->request->rawBody;

		if (!$body) {
			$this->sendResource(
				Service\OperationOutcome::singleIssue(
					FhirValueSet::ISSUESEVERITY_FATAL,
					FhirValueSet::ISSUETYPE_INVALID_REQUIRED,
					"No input received"
				),
				400
			);
		}

		if (isset($_SERVER['CONTENT_TYPE']) && preg_match('/json/', $_SERVER['CONTENT_TYPE'])) {
			$input = FhirUtil::parseJson($body);

			if (!$input) {
				$this->sendResource(
					Service\OperationOutcome::singleIssue(
						FhirValueSet::ISSUESEVERITY_FATAL,
						FhirValueSet::ISSUETYPE_INVALID_STRUCTURE,
						"Failed to parse input as JSON"
					),
					400
				);
			}
		} else {
			libxml_use_internal_errors(true);

			$input = FhirUtil::parseXml($body);

			if (!$input) {
				$issues = array();
				if (($errors = libxml_get_errors())) {
					foreach ($errors as $error) {
						$issues[] = new Service\OperationOutcomeIssue(
							array(
								'severity' => FhirUtil::$xml_error_map[$error->level],
								'type' => FhirValueSet::ISSUETYPE_INVALID_STRUCTURE,
								'details' => trim($error->message ?: "XML parse error") . " (line {$error->line})",
							)
						);
					}
					$this->sendResource(new Service\OperationOutcome(array('issues' => $issues)), 400);
				} else {
					$this->sendResource(
						Service\OperationOutcome::singleIssue(
							FhirValueSet::ISSUESEVERITY_FATAL,
							FhirValueSet::ISSUETYPE_INVALID_STRUCTURE,
							"Invalid XML input"
						),
						422
					);
				}
			}
		}

		return $input;
	}

	protected function sendResource(Service\DataObject $resource, $status = 200)
	{
		$data = $resource->toFhir();

		if ($this->output_format == 'xml') {
			header('Content-type: ' . self::XML_MIMETYPE);
			$this->sendResponse($status, FhirUtil::renderXml($data));
		} else {
			header('Content-type: ' . self::JSON_MIMETYPE);
			$this->sendResponse($status, FhirUtil::renderJson($data), self::JSON_MIMETYPE);
		}
	}

	protected function sendBundle(Service\Bundle $bundle)
	{
		$data = $bundle->toFhir();

		if ($this->output_format == 'xml') {
			header('Content-type: ' . self::ATOM_MIMETYPE);
			$this->sendResponse(200, FhirUtil::renderXml($data));
		} else {
			header('Content-type: ' . self::JSON_MIMETYPE);
			$this->sendResponse(200, FhirUtil::renderJson($data));
		}
	}

	/**
	 * Send response
	 *
	 * @param int $status
	 * @param string $body
	 */
	protected function sendResponse($status, $body = '')
	{
		header('HTTP/1.1 ' . $status);
		echo $body;
		Yii::app()->end();
	}
}
