<?php

require_once ('OLPBlackbox/Rule.php');

/**
 * Abstract Filter rule.
 * 
 * This is the base class for the Filter rules.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_Filter extends OLPBlackbox_Rule
{
	/**
	 * OLP database connection object.
	 *
	 * @var MySQL_4
	 */
	protected $olp_db;
	
	/**
	 * The name of the OLP database.
	 *
	 * @var string
	 */
	protected $olp_db_name;
	
	/**
	 * Run the Filter rule.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Setup the db
		$this->olp_db = $this->getDbInstance();
		$this->olp_db_name = $this->getDbName();
		
		// Existing filters are hardcoded to 30 days
		$query_date = $this->getQueryDate(30);
		
		return $this->runFilter($data, $state_data, $query_date);
	}

	/**
	 * Return the date that we'll run our recur queries on.
	 *
	 * @param int $days the number of days this recur rule checks
	 * @return DateTime
	 */
	protected function getQueryDate($days)
	{
		return date_create("-{$days} days");
	}
	
	/**
	 * Runs the filter.
	 *
	 * @param Blackbox_Data $data the data to run the filter on
	 * @param Blackbox_IStateData $state_data state data to run the filter on
	 * @param DateTime $date the date to use for the query
	 * @return bool
	 */
	abstract protected function runFilter(Blackbox_Data $data, Blackbox_IStateData $state_data, DateTime $date);
	
	/**
	 * Setups the database connection for this rule.
	 *
	 * @return void
	 */
	protected function getDbInstance()
	{
		// TODO: Make sure this is in the correct place
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the database name.
	 *
	 * @return string
	 */
	protected function getDbName()
	{
		// TODO: Make sure this is in the correct place
		return $this->getConfig()->olp_db->db_info['db'];
	}
}

?>
