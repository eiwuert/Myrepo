<?php

/**
 * Provides a json response based on a given variable
 * 
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 */
class Site_Response_Null implements Site_IResponse
{

	/**
	 * Writes the response to stdout or the browser.
	 * 
	 * Does Nothing
	 */
	public function render()
	{
	}
}

?>