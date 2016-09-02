<?php

/**
* A class to determine whether an IP is allowed to perform secure functions.
*
* @author Kevin Kragenbrink
* @version 1.0.1
*/
class Security_Allowance
{
	
	/**
	* Determine whether an IP is in the allowed list.
	*
	* @param 	$ip		string:		An IP address to be checked.
	* @return 	bool:				TRUE if secure, else FALSE.
	*/
	public static function Is_Secure( $ip )
	{
		// The list of allowed IPs, with the reference as the key for lookup purposes.
		$allowed_ips = array(
			'sellingsource' => '67.50.205.34',
		);
		
		return in_array( $ip, $allowed_ips );
	}
}

?>