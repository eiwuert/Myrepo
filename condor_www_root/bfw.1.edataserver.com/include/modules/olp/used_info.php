<?php

/**
 * Performs various rules-checks to keep an applicant from reaching Tier1 too often.
 *
 * The Used_Info checks are a safe stopping point to help us ensure that no applicant
 * can reach NMS too often and thus be denied.  Used_Info checks include the same bank
 * account with a different SSN and the check for whether an applicant has received a loan
 * from a company within the past 30 days.
 *
 * @author Kevin Kragenbrink
 * @version 1.0.1 - Kevin Kragenbrink
 */

class Used_Info
{
	private $event;

	/**
	 * The short name of the database we're opening up, such as ufc or ca.
	 *
	 * @var string
	 */
	protected $prop_short = NULL;

	/**
    * @publicsection
    * @public
    * Prepares the necessary variables and objects, then runs the checks.
    *
    * @param    object      $db         A reference to the mysql connection object.
    * @param    array       $targets    The potential Tier1 targets.
    * @param    int         $aba        The applicant's bank ABA number.
    * @param    int         $account    The applicant's bank account number.
    * @param    int         $ssn        The applicant's social security number.
    * @param    string      $email      The customer's email address.
    * @param    object      $event      A reference to the event logging object.
    *
    * @return   void
    */
	public function __construct( $mode, &$event, $prop_list, $prop_short = NULL )
	{

		$this->event		= &$event;
		$this->properties	= $prop_list;
		$this->prop_short = $prop_short;
		$mode = ($_SESSION['config']->use_new_process) ? $mode . '_READONLY' : $mode;
		$this->sql			= Setup_DB::Get_Instance('mysql', $mode, $prop_short);

		return;
	}

	/*
	* @desc Run all used_info check
	* @return array an array of targets still open
	*/
	public function Run_All($targets, $aba, $account, $ssn, $email, $strict = TRUE)
	{
		$valid = TRUE;
		$this->targets = $targets;

		if($valid && (($aba && $account && $ssn) || $strict))
		{
			$valid = $this->ABA_Check($aba, $account, $ssn);
		}

		if( !$valid )
		{
			$targets = array();
		}
		else
		{
			$targets = $this->targets;
		}

		return $targets;

	}
	
	/**
    * @privatesection
    * @private
    * Checks the database and determines whether this ABA and Account number have been
    	used with a different ssn.
    *
    * @return   void
    */
	public function ABA_Check( $aba, $account, $ssn )
	{
		$continue = TRUE;
		
		
		if(!$aba || !$account || !$ssn)
		{
				$this->targets  = array();
				$this->event->Log_Event( 'ABA_CHECK', 'FAILED_QUERY' );
				$continue = FALSE;			
		} 
		else 
		{
			
			// remove all leading zeros
			$account = preg_replace('/^0*/', '', $account);
			
			if (strlen($account) == 17)
			{
				$acct_array[] = $account;
			}
			else
			{
				// create all possible leading zero combinations for the bank account
				// only if the account number is not 17 digits
				for ($i = strlen($account); $i < 18; $i++)
				{
					$acct_array[] = "'".str_pad($account, $i, '0', STR_PAD_LEFT)."'";
				}
			}
			
			$mysql_query = "
				SELECT
					COUNT(DISTINCT ssn) AS ssn_count
				FROM
					application
				WHERE
					bank_aba = '" . $aba . "'
				AND
					bank_account IN (" . implode(',', $acct_array) . ")
				AND
					ssn <> '" . $ssn . "'
				AND
					date_created > DATE_SUB( CURDATE(), INTERVAL 1 YEAR )
			";
	
			try
			{
				
				$sqli_result = $this->sql->Query( $mysql_query );
				$sqli_count = $sqli_result->Fetch_Object_Row();
	
				if( $sqli_count->ssn_count >= 2 )
				{
					$this->targets  = array();
					$this->event->Log_Event('ABA_CHECK', 'FAIL', $this->prop_short);
					$continue = FALSE;
				}
				else
				{
					$this->event->Log_Event('ABA_CHECK', 'PASS', $this->prop_short);
				}
				
			}
			catch ( Exception $e )
			{
				$this->event->Log_Event( 'ABA_CHECK', 'FAILED_QUERY' );
	
				//$continue =  FALSE;
			}
		}
		return $continue;
	}
}
?>
