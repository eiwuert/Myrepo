<?php

/**
 * Basic response format. In normal circumstances, this isn't actually used,
 * as we currently only accept SOAP requests for Agean, who uses the
 * {@link CM_AgeanResponseFormat}, and OPM, who uses the {@link CM_OPMResponseFormat}.
 * @author Andrew Minerd
 */
class CM_DefaultResponseFormat implements CM_ResponseFormat {
	const TOKEN_REDIRECT_URL = 'online_confirm_redirect_url';
	const TOKEN_NAME_FIRST = 'name_first';

	/**
	 * @var CM_ErrorMessages
	 */
	private $messages;

	public function __construct(CM_ErrorMessages $errors) {
		$this->messages = $errors;
	}

	public function format(CM_Request $request, CM_Response $response) {
		$builder = $this->getBuilder();
		$builder->createSignature($request, $response);
		$builder->createErrors($response->getErrors(), $this->messages);

		if ($response->isSuccess()) {
			$this->buildSuccess($builder, $request, $response);
		} elseif (!$response->hasErrors()) {
			CM_Verbiage_Sorry::build($builder);
		}

		if ($response->hasErrors()) {
			$builder->createCollection($request->getData());
		}

		return $builder->toXML(true);
	}

	/**
	 * Build the portion of the request for a sucessfull response.
	 * @param CM_ResponseBuilder $builder
	 * @param CM_Request $request
	 * @param CM_Response $response
	 * @return void
	 */
	protected function buildSuccess(CM_ResponseBuilder $builder, CM_Request $request, CM_Response $response) {
		$redirect_url = $response->getToken(self::TOKEN_REDIRECT_URL);
		if ($redirect_url !== null) {
			CM_Verbiage_ThanksRedirect::build($builder, $redirect_url);
		} else {
			$name_first = $response->getToken(self::TOKEN_NAME_FIRST);
			CM_Verbiage_ThanksEmail::build($builder, $name_first);
		}
	}

	protected function getBuilder() {
		return new CM_ResponseBuilder();
	}
}