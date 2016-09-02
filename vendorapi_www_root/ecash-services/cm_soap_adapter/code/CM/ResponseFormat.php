<?php

/**
 * Formats a {@link CM_Response} as an XML string. The string value will
 * be returned from {@link CM_Service#User_Data($xml)}.
 * @author Andrew Minerd
 */
interface CM_ResponseFormat {
	/**
	 * Formats the response as an XML string.
	 * @param CM_Request $request
	 * @param CM_Response $response
	 * @return string
	 */
	public function format(CM_Request $request, CM_Response $response);
}