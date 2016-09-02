<?php

/**
 * Interface for a response helper.
 * 
 * The page helpers could be considered partial IRequestProcessor 
 * implementations; they allow moving common functionality (such as request 
 * validation) into a shared class (the helper), without requiring a common 
 * ancestor to access that functionality.
 * 
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @package Site
 */
interface Site_Page_IResponseHelper extends Site_Page_IHelper
{

	/**
	 * Executed to post process a given response.
	 *
	 * @param Site_Request $request
	 * @param Site_IResponse $response
	 * @return mixed Site_IResponse or NULL 
	 */
	public function onResponse(Site_Request $request, Site_IResponse $response);
}

?>
