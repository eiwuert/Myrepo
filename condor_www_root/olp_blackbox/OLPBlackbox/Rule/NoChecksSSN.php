<?php
/**
 * OLPBlackbox_Rule_NoChecksSSN class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if a customer's ssn is in a list of socials that shouldnt
 * run additional checks on.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_NoChecksSSN extends OLPBlackbox_Rule
{
	/**
	 * OLP database connection object.
	 *
	 * @var MySQL_4
	 */
	protected $olp_db;
	
	/**
	 * Runs the NoChecksSSN rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Setup the db
		$this->olp_db = $this->getDbInstance();
		$this->olp_db_name = $this->getDbName();
		
		// Get the social out of the data array based on the field name that
		// was specified when the rule was setup.  We expect that it is already
		// encrypted.
		$encrypted_ssn = $this->getDataValue($data);
		
		// Search for ssn in list
		$query = "
			SELECT
				social_security_number
			FROM
				no_checks_ssn_list
			WHERE
				 social_security_number = '" . $encrypted_ssn . "'
			LIMIT 1
		";
		
		try
		{
			$result = $this->olp_db->Query($this->olp_db_name, $query);
		}
		catch (MySQL_Exception $e)
		{
			return FALSE;
		}
		
		if ($this->olp_db->Row_Count($result))
		{
			// Bypass the fraud scan.  Legacy olp set this directly into the session
			// but we are going to rely on the OLP interface to handle that for now.
			$state_data->set_session['is_fraud'] = FALSE;
			
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Setups the database connection for this rule. This function mainly
	 * exists to allow for unit testing to mock the db stuff.
	 *
	 * @return void
	 */
	protected function getDbInstance()
	{
		// TODO: Make sure this is in the correct place
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the database name. This function mainly exists to allow
	 * for unit testing to mock the db stuff.
	 *
	 * @return string
	 */
	protected function getDbName()
	{
		// TODO: Make sure this is in the correct place
		return $this->getConfig()->olp_db_name;
	}
}

?>
