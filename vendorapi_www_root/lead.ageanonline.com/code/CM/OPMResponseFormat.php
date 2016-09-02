<?php
/**
 * OPM Response Format for their overrides
 * @author Adam Englander <adam.englander@sellingsource.com>
 *
 */
class CM_OPMResponseFormat extends CM_DefaultResponseFormat {

	protected function buildSuccess(CM_ResponseBuilder $builder, CM_Request $request, CM_Response $response) {
		$section = $builder->createSection();
		$builder->appendElement($section, "redirect_url", $response->getToken("online_confirm_redirect_url"));
	}
}