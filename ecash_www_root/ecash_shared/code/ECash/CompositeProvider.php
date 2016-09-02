<?php

/**
 * A composite version of the Customer History Provider
 *
 * Useful when an enterprise is split across multiple databases (i.e., CLK).
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_CompositeProvider implements ECash_ICustomerHistoryProvider
{
	/**
	 * @var array
	 */
	protected $provider = array();

	/**
	 * Add a provider to the composite
	 *
	 * @param ECash_ICustomerHistoryProvider $p
	 * @return void
	 */
	public function addProvider(ECash_ICustomerHistoryProvider $p)
	{
		$this->provider[] = $p;
	}

	/**
	 * Excludes an application from the generated history
	 *
	 * @param int $app_id
	 * @return void
	 */
	public function excludeApplication($app_id)
	{
		foreach ($this->provider as $p)
		{
			$p->excludeApplication($app_id);
		}
	}

	/**
	 * Sets a single company to retrieve results for
	 *
	 * @param string $name
	 * @return void
	 */
	public function setCompany($name)
	{
		foreach ($this->provider as $p)
		{
			$p->setCompany($name);
		}
	}

	/**
	 * Finds customer history by the given conditions
	 *
	 * @param array $conditions
	 * @param ECash_CustomerHistory $history
	 * @return ECash_CustomerHistory
	 */
	public function getHistoryBy(array $conditions, ECash_CustomerHistory $history = NULL, array $ignore_statuses = array())
	{
		foreach ($this->provider as $p)
		{
			$history = $p->getHistoryBy($conditions, $history, $ignore_statuses);
		}
		return $history;
	}

	/**
	 * Executes runDoNotLoan on all providers
	 *
	 * @param string $ssn
	 * @param ECash_CustomerHistory $history
	 * @return void
	 */
	public function runDoNotLoan($flag_info, $ssn, ECash_CustomerHistory $history = NULL)
	{
		foreach ($this->provider as $p)
		{
			$p->runDoNotLoan($flag_info, $ssn, $history);
		}
	}

}

?>