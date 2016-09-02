<?php
/**
 * Abstract minimum recur rule.
 *
 * This class implements the common functionality for the minimum recur rules.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_MinimumRecur extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
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
	 * Run the mimimum recur rule.
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
		
		$days = $this->getRuleValue();
		$query_date = $this->getQueryDate($days);
		
		$total = $this->runRecurCheck($data, $query_date, $state_data->campaign_name);
		return $total == 0 ? TRUE : FALSE;
	}
	
	/**
	 * Return the date that we'll run our recur queries on.
	 *
	 * @param int $days the number of days this recur rule checks
	 * @return DateTime
	 */
	protected function getQueryDate($days)
	{
		return date_create("-$days days");
	}
	
	/**
	 * Setups the database connection for this rule.
	 *
	 * @return void
	 */
	protected function getDbInstance()
	{
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the database name.
	 *
	 * @return string
	 */
	protected function getDbName()
	{
		return $this->getConfig()->olp_db->db_info['db'];
	}
	
	/**
	 * Runs the recur check.
	 *
	 * @param string $data the data the check will use
	 * @param DateTime $date the date the check will use
	 * @param string $name_short the name of the target we're checking
	 * @return int
	 */
	abstract protected function runRecurCheck($data, DateTime $date, $name_short);
}
?>
