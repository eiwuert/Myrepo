<?php
/**
 * LoanAPI interface defines the methods required to support the service's WSDL (Loan.wsdl)
 *
 * @package ECash_Loan
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
interface ECash_Service_Cubis_IAPI
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
	public function loginCardHolder($ssn, $client_ip_address, $remote_fail_url, $add_funds_url, $page);
}
