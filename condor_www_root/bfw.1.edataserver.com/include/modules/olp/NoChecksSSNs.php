<?php
/**
 * NoChecksSSNs class file
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */

/**
 * Checks customer's social security number to see if it should bypass all rules.
 * 
 * This is used for agean legacy customers so that they can bypass all rules and checks
 * with the ultimate goal of being accepted no matter what. 
 * Originaly created for GForge #5283 moved to its own seperate file for GForge #11375
 * The list of ssns to bypass checks is modified using this tool:
 * 		http://olp_tools.jubilee.tss:8080/no_checks_ssns.php
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class NoChecksSSNs
{
	/**
	 * Check if social is found in the list.
	 *
	 * @param string/int $ssn
	 * @param MySQL_Wrapper &$sql
	 * @return bool
	 */
	private static function runNoCheckSSN($ssn, MySQL_Wrapper &$sql)
	{
		if (strlen($ssn) == 0)
		{
			return FALSE;
		}

		//Signal that the check has been run (if it is not found no event is created)
		$_SESSION['no_checks_ssn'] = FALSE;

		if (is_numeric($ssn))
		{
			$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'], $crypt_config['IV']);
			$encrypted_ssn = $crypt_object->encrypt($ssn);
		}

		// Search for ssn in list
		$query = "
				SELECT 
					social_security_number
				FROM 
					no_checks_ssn_list
				WHERE 
					social_security_number = '$encrypted_ssn'
				LIMIT 1";
		try
		{
			$result = $sql->Query($sql->db_info['db'], $query);
		}
		catch (MySQL_Exception $e)
		{
			return FALSE;
		}

		if (mysql_num_rows($result) > 0)
		{
			$_SESSION['no_checks_ssn'] = TRUE;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get results for the nochecksssns check
	 *
	 * Since results will always come back the same if results already exist
	 * return them instead of re-running the query.
	 *
	 * @param string/int $ssn
	 * @param MySQL_Wrapper &$sql
	 * @return bool
	 */
	public static function getNoChecksSSNResult($ssn, MySQL_Wrapper &$sql)
	{
		if (is_null($_SESSION['no_checks_ssn']))
		{
			$_SESSION['no_checks_ssn'] = self::runNoCheckSSN($ssn,$sql);
		}
		return $_SESSION['no_checks_ssn'];
	}
}
?>
