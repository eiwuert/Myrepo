<?php

/**
 * Represents a Site page
 * 
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @package Site
 */
interface Site_IRequestProcessor
{

	/**
	 * Processes the given request.
	 * 
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request);
}

?>
