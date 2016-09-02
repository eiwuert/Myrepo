<?php

/**
 * This is a mock class to mimic the functionality of the eCash 
 * authentication API
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class Mock_OLP_ECashClient_AuthenticationAPI
{
	/**
	 * Validates stuff
	 * 
	 * @param int $application_id
	 * @param string $work_phone
	 * @param string $dob
	 * @param bool $is_react
	 * @return string
	 */
	public function validate($application_id, $work_phone, $dob, $is_react)
	{
		return 'pass';
	}
	
	/**
	 * Returns locked status
	 * 
	 * @param int $application_id
	 * @return bool
	 */
	public function isLocked($application_id)
	{
		return FALSE;
	}
}
