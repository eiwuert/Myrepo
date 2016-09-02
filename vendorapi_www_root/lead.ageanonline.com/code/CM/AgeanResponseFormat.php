<?php

/**
 * Agean response format. This adds an Agean-specific section to the response,
 * which contains the application ID, customer ID, site ID, and login.
 * @author Andrew Minerd
 */
class CM_AgeanResponseFormat extends CM_DefaultResponseFormat {
	const LOGIN_SALT = "L08N54M3";

	protected function buildSuccess(CM_ResponseBuilder $builder, CM_Request $request, CM_Response $response) {
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
		$builder->appendElement($section, "applicationid", base64_encode($app_id));
		$builder->appendElement($section, "customerid", $request->getField("customer_id"));
		$builder->appendElement($section, "siteid", $site_id);
		$builder->appendElement($section, "winnerurl", $url);
		$builder->appendElement($section, "login", md5($app_id . $site_id . self::LOGIN_SALT));

		$collection = $builder->getCollection();
		$builder->createData($collection, 'client_url_root', $request->getClientUrlRoot());
		$builder->createData($collection, 'client_ip_address', $request->getClientIpAddress());
		$builder->createData($collection, 'customer_id', $request->getField("customer_id"));
	}
}