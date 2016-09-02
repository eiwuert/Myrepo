<?php

/**
 * SOAP service that is an adapter from the OLP 'CM_SOAP' interface to
 * the new Customer Flow SOAP service. This implements the single User_Data
 * method which accepts and returns an XML string. The response format used is
 * dependent upon the incoming site type (see {@link #getResponseFormat()}).
 *
 * For futher details on the format of the request and response, see
 * {@link CM_Request}, {@link CM_ResponseFormat}, and {@link CM_ResponseBuilder}.
 *
 * @see CM_Request
 * @see CM_ResponseFormat
 * @author Andrew Minerd
 */
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
		try {
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
		} catch (Exception $e) {
			error_log("Error processing User_Data request: \n" . $e);
			$response_builder = new CM_ResponseBuilder();
			$response_builder->createErrors(array("try_again"), $this->errors);
			return $response_builder->toXML(TRUE);
		}
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