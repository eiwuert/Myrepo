<?php
require_once "crypt.3.php";

/**
 * Shared code for eCash AMG/Commercial for providing the Cubis API
 *
 * @package ECash_Loan
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
abstract class ECash_Service_Cubis_API implements ECash_Service_Cubis_IAPI
{
	/**
	 * Logs in a card holder
	 *  
	 * @param string $ssn
	 * @param string $client_ip_address
	 * @param string $remote_fail_url
	 * @param string $add_funds_url
	 * @param string $page
	 * @return stdClass stdClass[
	 * 		boolean	success
	 * 		string	error	(Error Message, can be null)
	 * 		string	result 	(URL, can be null)
	 * ]
	 */
	public function loginCardHolder($ssn, $client_ip_address, $remote_fail_url, $add_funds_url, $page)
	{
		// setup the response object
		$response = new stdClass();
		$response->success = false;
		$response->error = "operation_not_permitted";
		$response->result = NULL;
		
		return $response;
	}
	
	/**
	 * Inserts a message into the log.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	abstract protected function insertLogEntry($message);
}
?>
