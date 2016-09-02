<?php

/**
 * Agean response format. This adds an Agean-specific section to the response,
 * which contains the application ID, customer ID, site ID, and login.
 * @author Andrew Minerd
 */
class CM_AgeanResponseFormat extends CM_DefaultResponseFormat {
	const LOGIN_SALT = "L08N54M3";

	/**
	 * @var CM_ErrorMessages
	 */
	private $errors;

	public function __construct(CM_ErrorMessages $errors) {
		$this->errors = $errors;
	}

	public function format(CM_Request $request, CM_Response $response) {
		$builder = new CM_ResponseBuilder();
		$builder->createSignature($request, $response);

		$page = $response->getPage();
		if ($page == "app_completed") {
			$this->buildSuccess($builder, $request, $response);
		} elseif ($page != "app_declined") {
			$builder->createErrors($response->getErrors(), $this->errors);
			$builder->createCollection($request->getData());
		}

		return $builder->toXML(true);
	}

	private function buildSuccess(CM_ResponseBuilder $builder, CM_Request $request, CM_Response $response) {
		$url = $response->getToken("customer_service_link");
		$app_id = $response->getToken("application_id");

		switch ($url) {
			case "cashbanc.com":
				$site_id = 2;
				break;
			case "jiffy-cash.com":
				$site_id = 1;
				break;
			case "freshstartpaydayloans.com":
				$site_id = 10;
				break;
		}

		$section = $builder->createSection();
		$builder->createData($section, "applicationid", base64_encode($app_id));
		$builder->createData($section, "customerid", $request->getField("customerid"));
		$builder->createData($section, "siteid", $site_id);
		$builder->createData($section, "login", md5($app_id . $site_id . self::LOGIN_SALT));
	}
}