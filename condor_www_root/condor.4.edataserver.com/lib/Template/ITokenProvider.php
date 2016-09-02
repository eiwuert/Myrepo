<?php 

/**
 * Provide tokens to the template?
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
interface Template_ITokenProvider
{
	/**
	 * Get the value for a token
	 *
	 * @param string $token
	 * @return String
	 */
	public function getTokenValue($token);
}