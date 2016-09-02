<?php

class CM_OPMResponseFormat implements CM_ResponseFormat {
	private $messages;

	public function __construct(CM_ErrorMessages $messages) {
		$this->messages = $messages;
	}

	public function format(CM_Request $request, CM_Response $response) {
		$builder = new CM_ResponseBuilder();
		$builder->createSignature($request, $response);

		if ($response->getPage() == 'bb_thanks') {
			$section = $builder->createSection();
			$builder->createElement($section, "redirect_url", $response->getToken("online_confirm_redirect_url"));
		} else {
			$builder->createErrors($response->getErrors(), $this->errors);
			$builder->createCollection($request->getData());
		}
	}
}