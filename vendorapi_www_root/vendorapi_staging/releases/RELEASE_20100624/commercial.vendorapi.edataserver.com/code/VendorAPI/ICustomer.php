<?php
/**
 *
 * @package VendorApi
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 *
 */

interface VendorAPI_ICustomer
{
	/**
	 * Finds customer. Returns a customer model on success and
	 * false on failure.
	 *
	 * @param string $username
	 * @return ECash_Models_Customer
	 */
	public function getCustomer($username);
	
	/**
	 * Authenticates the customer. Returns true and success and
	 * false on failure.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public function credentialsValid($username, $password);
	
	/**
	 * Finds active applications under the customer. Returns an array of application_ids on success and
	 * false on failure.
	 *
	 * @param string $username
	 * @return array
	 */
	public function getApplicationIds($username);
}