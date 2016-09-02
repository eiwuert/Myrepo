<?php

/**
 * Basic response format. In normal circumstances, this isn't actually used,
 * as we currently only accept SOAP requests for Agean, who uses the
 * {@link CM_AgeanResponseFormat}, and OPM, who uses the {@link CM_OPMResponseFormat}.
 * @author Andrew Minerd
 */
class CM_DefaultResponseFormat implements CM_ResponseFormat {
	/**
	 * @var CM_ErrorMessages
	 */
	private $messages;

	public function __construct(CM_ErrorMessages $errors) {
		$this->messages = $errors;
	}

	public function format(CM_Request $request, CM_Response $response) {
		$builder = new CM_ResponseBuilder();
		$builder->createSignature($request, $response);
		$builder->createCollection($response->getTokens());

		$errors = $response->getErrors();
		if (!empty($errors)) {
			$builder->createErrors($errors, $this->messages);
		}

		return $builder->toXML(true);
	}
}