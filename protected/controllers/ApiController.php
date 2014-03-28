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
	const FHIR_VERSION = '0.80';

	const JSON_MIMETYPE = 'application/json+fhir; charset=utf-8';
	const XML_MIMETYPE = 'application/xml+fhir; charset=utf-8';
	const ATOM_MIMETYPE = 'application/atom+xml; charset=utf-8';

	static protected $xml_error_map = array(
		LIBXML_ERR_WARNING => FhirValueSet::ISSUESEVERITY_WARNING,
		LIBXML_ERR_ERROR => FhirValueSet::ISSUESEVERITY_ERROR,
		LIBXML_ERR_FATAL => FhirValueSet::ISSUESEVERITY_FATAL,
	);

	protected $output_format;

	protected $general_tags;
	protected $profile_tags;
	protected $security_tags;

	public function beforeAction($action)
	{
		foreach (Yii::app()->log->routes as $route) {
			if ($route instanceof CWebLogRoute) $route->enabled = false;
		}

		// Output format can be selected using a special GET param or by Accept: header
		if (isset($_GET['_format'])) {
			if (preg_match('/json/', $_GET['_format'])) {
				$this->output_format = 'json';
			} elseif (preg_match('/xml/', $_GET['_format'])) {
				$this->output_format = 'xml';
			}
		} else {
			foreach (Yii::app()->request->preferredAcceptTypes as $type) {
				if ($type['baseType'] == 'xml' || $type['subType'] == 'xml' || $type['subType'] == '*') {
					$this->output_format = 'xml';
					break;
				}
				if ($type['baseType'] == 'json' || $type['subType'] == 'json') {
					$this->output_format = 'json';
					break;
				}
			}
		}

		if (!isset($this->output_format)) {
			$this->sendResponse(406);
		}

		// Attach error handlers as soon as we know what format to send the error in
		Yii::app()->attachEventHandler("onError", array($this, "handleError"));
		Yii::app()->attachEventHandler("onException", array($this, "handleException"));

		header('WWW-Authenticate: Basic realm="OpenEyes"');
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$this->sendError("Authentication required", 401, FhirValueSet::ISSUETYPE_SECURITY_LOGIN);
		}
		$identity = new UserIdentity($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		if (!$identity->authenticate()) {
			$this->sendError("Authentication failed", 401, FhirValueSet::ISSUETYPE_SECURITY_UNKNOWN);
		}
		Yii::app()->user->login($identity);
		if (!Yii::app()->user->checkAccess('OprnApi')) {
			$this->sendError("Not authorised", 403, FhirValueSet::ISSUETYPE_SECURITY_FORBIDDEN);
		}

		// Tags, aka HTTP categories: http://hl7.org/implement/standards/fhir/http.html#tags
		$tags = CategoryHeader::load();

		$this->general_tags = $tags->get('http://hl7.org/fhir/tag');
		$this->profile_tags = $tags->get('http://hl7.org/fhir/tag/profile');
		$this->security_tags = $tags->get('http://hl7.org/fhir/tag/security');

		return true;
	}

	/**
	 * @param CErrorEvent $event
	 */
	public function handleError(CErrorEvent $event)
	{
		$this->sendError(
			YII_DEBUG ? "{$event->message} in {$event->file}:{$event->line}" : "Internal Error",
			500, FhirValueSet::ISSUETYPE_TRANSIENT_EXCEPTION
		);
	}

	/**
	 * @param CExceptionEvent $event
	 */
	public function handleException(CExceptionEvent $event)
	{
		$e = $event->exception;

		if ($e instanceof Service\ServiceException) {
			$this->sendResource($e->toFhirOutcome(), $e->httpStatus);
		}

		$issue_type = FhirValueSet::ISSUETYPE_TRANSIENT_EXCEPTION;
		$message = 'Internal Error';
		$status = 500;

		if ($e instanceof CDbException && substr($e->errorInfo[0], 0, 2) == '23') {  // SQLSTATE Constraint Violation
			$issue_type = FhirValueSet::ISSUETYPE_PROCESSING_CONFLICT;
			$message = 'Constraint Violation';
			$status = 409;
		}

		$this->sendError(YII_DEBUG ? "$e" : $message, $status, $issue_type);
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
		$vid = $ref->getVersionId();

		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.26
		$etagMatch = null;
		$modified = null;
		if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			$etagMatch = false;
			foreach (str_getcsv($_SERVER['HTTP_IF_NONE_MATCH']) as $tag) {
				if ($tag == $vid || $tag == '*') {
					$etagMatch = true;
					break;
				}
			}
		}
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if ($since === false) {
                $this->sendError("Failed to parse If-Modified-Since header: {$_SERVER['HTTP_IF_MODIFIED_SINCE']}");
			}
			$modified = ($ref->getLastModified() > $since);
		}
		if (($etagMatch === null && $modified === false) || ($etagMatch === true && $modified !== true)) {
			$this->sendResponse(304);
		}

		header('Content-Location: ' . $this->createAbsoluteUrl('api/') . '/' . Yii::app()->service->referenceToFhirUrl($ref) . "/_history/{$vid}");
		header("ETag: \"{$vid}\"");
		$this->sendResource($ref->resolve());
	}

	/**
	 * Read (view) previous version of resource
	 *
	 * Current implementation uses timestamp as version ID and only supports the current version
	 *
	 * @param string $resource_type
	 * @param string $id
	 * @param string $vid
	 */
	public function actionVread($resource_type, $id, $vid)
	{
		$ref = $this->getRef($resource_type, $id);
		$current_vid = $ref->getVersionId();

		if ($vid != $current_vid) {
			$this->sendError("Only accessing the current version of a resource is supported: latest is '{$current_vid}', attempted to fetch '{$vid}'", 405);
		}
		$this->sendResource($ref->resolve());
	}

	/**
	 * Update resource
	 *
	 * @param string $resource_type
	 * @param string $id
	 */
	public function actionUpdate($resource_type, $id)
	{
		try {
			$ref = $this->getRef($resource_type, $id);

			$vid = $ref->getVersionId();
			$vurl = $this->createAbsoluteUrl('api/') . '/' . Yii::app()->service->referenceToFhirUrl($ref) . "/_history/{$vid}";

			if (isset($_SERVER['HTTP_CONTENT_LOCATION']) && $_SERVER['HTTP_CONTENT_LOCATION'] != $vurl) {
				$this->sendResponse(409);
			}

			if (isset($_SERVER['HTTP_IF_MATCH'])) {
				$match = false;
				foreach (str_getcsv($_SERVER['HTTP_IF_MATCH']) as $tag) {
					if ($tag == $vid || $tag == "*") {
						$match = true;
						break;
					}
				}
				if (!$match) $this->sendResponse(412);
			}

			if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
				$since = strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
				if ($since === false) {
					$this->sendError("Failed to parse If-Unmodified-Since header: {$_SERVER['HTTP_IF_UNMODIFIED_SINCE']}");
				}
				if ($ref->getLastModified() > $since) $this->sendResponse(412);
			}

			$input = $this->parseInput();
			$tx = Yii::app()->db->beginTransaction();
			$ref->fhirUpdate($input);
			$tx->commit();
		} catch(\Service\NotFound $e) {
			$this->sendError("Client defined IDs are not supported ({$e->getMessage()})", 405);
		}

		header("Location: {$vurl}");
		header("Content-Location: {$vurl}");
		header("Last-modified: " . date(DATE_RFC1123, $ref->getLastModified()));
		header("ETag: \"{$vid}\"");

		$this->sendInfo("Resource {$resource_type}/{$id} successfully updated");
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
		$service = Yii::app()->service->getFhirService($resource_type, $this->general_tags);
		$fhirObject = $this->parseInput();

		if (strtolower($fhirObject->resourceType) != strtolower($resource_type)) {
			throw new Service\InvalidValue("Invalid resource type '{$fhirObject->resourceType}', expecting '{$resource_type}'");
		}

		$tx = Yii::app()->db->beginTransaction();
		$ref = $service->fhirCreate($fhirObject);
		$tx->commit();

		$url = Yii::app()->service->referenceToFhirUrl($ref);
		$vid = $ref->getVersionId();

		header('Location: ' . $this->createAbsoluteUrl('api/') . "/{$url}/_history/{$vid}");
		header("ETag: \"{$vid}\"");

		$this->sendInfo("Resource '{$url}' successfully created", 201);
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
		foreach (array('_format', '_count', '_id', '_tag') as $param) {
			if (isset($_REQUEST[$param])) $used_params[$param] = $_REQUEST[$param];
		}

		$tags = isset($_REQUEST['_tag']) ? array($_REQUEST['_tag']) : array();
		$service = Yii::app()->service->getFhirService($resource_type, $tags);

		$params = $_REQUEST;
		unset($params['id']);  // Can't send a service layer ID directly

		// Special case for when there's a resource ID as part of the search (which doesn't seem very useful...)
		if (isset($params['_id'])) {
			if (!$ref = Yii::app()->service->fhirIdToReference($resource_type, $params['_id'])) {
				// Not an error: we support the resource type, you just asked for an ID that doesn't exist
				$service = null;
			}
			$params['id'] = $ref->getId();
			if ($ref->getServiceName() != $service::getServiceName()) {  // service mismatch
				$service = null;
			}
		}

		if ($service) {
			$resources = $service->search($params);
			$used_params += $params;

			if (isset($_REQUEST['_count'])) {
				$resources = array_slice($resources, 0, $count);
			}
		} else {
			$resources = array();
		}

		$used_params = array_intersect_key($_REQUEST, $used_params);  // In case the service modified the values
		$self_url = $this->createAbsoluteUrl("api/search", $used_params);

		$base_url = $this->createAbsoluteUrl('api/');

		$indexed_resources = array();
		foreach ($resources as $resource) {
			$url = $base_url . '/' . Yii::app()->service->serviceAndIdToFhirUrl($service->getServiceName(), $resource->getId());
			$indexed_resources[$url] = $resource;
		}

		$bundle = Service\FhirBundle::create("Search results", $self_url, $base_url, $indexed_resources);

		$this->sendBundle($bundle);
	}

	// WhOLE SYSTEM INTERACTIONS

	public function actionConformance()
	{
		$statement = new \Service\FhirConformanceStatement(
			array(
				'publisher' => Institution::model()->getCurrent()->name,
				'date' => new \Service\Date,
				'description' => 'OpenEyes at ' . Institution::model()->getCurrent()->short_name,
				'url' => $this->createAbsoluteUrl('api/'),
				'fhir_version' => self::FHIR_VERSION,
				'accept_unknown' => true,
				'profiles' => Yii::app()->service->listFhirSupportedProfiles(),
			)
		);
		$this->sendResource($statement);
	}

	public function actionBadRequest()
	{
		$this->sendError("Invalid URL or method");
	}

	protected function getRef($resource_type, $id)
	{
		if (!$ref = Yii::app()->service->fhirIdToReference($resource_type, $id)) {
			throw new \Service\NotFound("Unrecognised resource type or ID: {$resource_type}/{$id}");
		}
		return $ref;
	}

	protected function parseInput()
	{
		$body = Yii::app()->request->rawBody;

		if (!$body) $this->sendError("No input received");

		if (isset($_SERVER['CONTENT_TYPE']) && preg_match('/json/', $_SERVER['CONTENT_TYPE'])) {
			$input = Yii::app()->fhirMarshal->parseJson($body);

			if (!$input) $this->sendError("Failed to parse input as JSON");
		} else {
			libxml_use_internal_errors(true);

			$input = Yii::app()->fhirMarshal->parseXml($body);

			if (!$input) {
				if (($errors = libxml_get_errors())) {
					$issues = array();
					foreach ($errors as $error) {
						$issues[] = new Service\FhirOutcomeIssue(
							array(
								'severity' => self::$xml_error_map[$error->level],
								'type' => FhirValueSet::ISSUETYPE_INVALID_STRUCTURE,
								'details' => trim($error->message ?: "XML parse error") . " (line {$error->line})",
							)
						);
					}
					$this->sendResource(new Service\FhirOutcome(array('issues' => $issues)), 400);
				} else {
					$this->sendError("Invalid XML input");
				}
			}
		}

		return $input;
	}

	protected function sendInfo($message, $status = 200)
	{
		$this->sendResource(Service\FhirOutcome::singleIssue(FhirValueSet::ISSUESEVERITY_INFORMATION, null, $message), $status);
	}

	protected function sendError($message, $status = 400, $type = 0)
	{
		$this->sendResource(Service\FhirOutcome::singleIssue(FhirValueSet::ISSUESEVERITY_FATAL, $type, $message), $status);
	}

	protected function sendResource(Service\DataObject $resource, $status = 200)
	{
		if (($last_modified = $resource->getLastModified())) {
			header("Last-modified: " . date(DATE_RFC1123, $last_modified));
		}

		$data = $resource->toFhir();

		if ($this->output_format == 'xml') {
			header('Content-type: ' . self::XML_MIMETYPE);
			$this->sendResponse($status, Yii::app()->fhirMarshal->renderXml($data));
		} else {
			header('Content-type: ' . self::JSON_MIMETYPE);
			$this->sendResponse($status, Yii::app()->fhirMarshal->renderJson($data), self::JSON_MIMETYPE);
		}
	}

	protected function sendBundle(Service\FhirBundle $bundle)
	{
		$data = $bundle->toFhir();

		if ($this->output_format == 'xml') {
			header('Content-type: ' . self::ATOM_MIMETYPE);
			$this->sendResponse(200, Yii::app()->fhirMarshal->renderXml($data));
		} else {
			header('Content-type: ' . self::JSON_MIMETYPE);
			$this->sendResponse(200, Yii::app()->fhirMarshal->renderJson($data));
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
