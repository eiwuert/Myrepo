<?php

/**
 * Description of ECash_Loan_API
 *
 * @package ECash_Loan
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class ECash_Service_Exception
{
	/**
	 * Invalid Username and/or Password
	 */
	const CREDENTIALS_LOGIN = "A1";
	
	/**
	* No access to api
	*/
	const CREDENTIALS_API_ACCESS = "A2";
}