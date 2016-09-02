<?php

/**
 * Encapsulates a Site response
 * 
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @package Site
 */
interface Site_IResponse
{

	/**
	 * Writes the response to stdout or the browser.
	 * 
	 * This method can also be used to perform any 'end of process' processing.
	 * (eg: logging, timers, etc.)
	 */
	public function render();
}

?>
