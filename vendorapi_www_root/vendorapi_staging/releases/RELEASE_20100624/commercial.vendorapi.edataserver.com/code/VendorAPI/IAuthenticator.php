<?php

/**
 * The interface for authentication adapters.
 *
 * @package VendorApi
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface VendorAPI_IAuthenticator
{
	/**
	 * Authenticates the user to the server. Returns true and success and
	 * false on failure.
	 *
	 * @param string $user
	 * @param string $pass
	 * @param string $section
	 * @return bool
	 */
	public function authenticate($user, $pass, $section = NULL);

	/**
	 * Returns the agent id of the logged in user
	 *
	 * @return int
	 */
	public function getAgentId();
}
?>