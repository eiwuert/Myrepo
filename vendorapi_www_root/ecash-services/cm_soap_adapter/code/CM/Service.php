<?php

class CM_Service {
	/**
	 * @var SoapClient
	 */
	private $flow;
	/**
	 * @var CM_ErrorMessages
	 */
	private $errors;

	public function __construct(SoapClient $flow, CM_ErrorMessages $errors) {
		$this->flow = $flow;
		$this->errors = $errors;
	}

	public function User_Data($xml) {
		$el = simplexml_load_string($xml);
		if (!$el) {
			throw new Exception("Invalid xml");
		}

		$request = new CM_Request($el);

		$data = new stdClass();
		$data->field_data = $request->getData();
		$data->_SERVER = array();

		$response = $this->flow->nextPage(
			$request->getLicenseKey(),
			$request->getSessionId(),
			$request->getSiteType(),
			$request->getPage(),
			$data
		);

		$format = $this->getResponseFormat($request);
		return $format->format($request, new CM_Response($response));
	}

	private function getResponseFormat(CM_Request $request) {
		$site_type = $request->getSiteType();
		if ($site_type == "soap.mmp") {
			return new CM_OPMResponseFormat($this->errors);
		} else if (strpos($site_type, "agean") != 0) {
			return new CM_AgeanResponseFormat($this->errors);
		}
		return new CM_DefaultResponseFormat($this->errors);
	}
}