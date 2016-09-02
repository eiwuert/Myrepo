<?php

/**
 * Interface for a request helper.
 * 
 * The page helpers could be considered partial IRequestProcessor 
 * implementations; they allow moving common functionality (such as request 
 * validation) into a shared class (the helper), without requiring a common 
 * ancestor to access that functionality.
 * 
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @package Site
 */
interface Site_Page_IRequestHelper extends Site_Page_IHelper
{

	/**
	 * Executed to preprocess a given request.
	 *
	 * @param Site_Request $request
	 * @return mixed Site_IResponse or NULL 
	 */
	public function onRequest(Site_Request $request);
}

?>
