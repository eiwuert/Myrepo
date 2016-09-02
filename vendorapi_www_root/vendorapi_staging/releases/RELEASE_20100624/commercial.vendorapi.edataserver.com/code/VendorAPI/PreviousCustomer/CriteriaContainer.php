<?php

/**
 * A criteria container various labeled criterion can be stored to and pulled from.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_CriteriaContainer implements VendorAPI_PreviousCustomer_ICriterion
{
	/**
	 * @var array containing VendorAPI_PreviousCustomer_ICriteria objects
	 */
	protected $criteria;

	public function __construct(array $criteria = NULL)
	{
		if ($criteria == NULL)
		{
			$criteria = array();
		}
		
		$this->criteria = $criteria;
	}

	/**
	 * Adds criteria to the container.
	 * 
	 * @param VendorAPI_PreviousCustomer_ICriterion $criterion
	 */
	public function addCriteria(VendorAPI_PreviousCustomer_ICriterion $criterion)
	{
		$this->criteria[] = $criterion;
	}

	/**
	 * Returns an array containing all criteria.
	 *
	 * @return array
	 */
	public function getCriteria()
	{
		return $this->criteria;
	}

	/**
	 * Returns app service criteria that would be usable by ...the app service.
	 * @return array
	 */
	public function getAppServiceObject(array $app_data)
	{
		$app_service_criteria = array();
		foreach ($this->criteria as $key => $criterion)
		{
			$criteria = $criterion->getAppServiceObject($app_data);
			if (!empty($criteria))
			{
				$app_service_criteria[] = (object)array('label' => $key, 'criteria' => $criteria);
			}
		}
		return $app_service_criteria;
	}

	public function postProcessResults(array $applications)
	{
		$apps = array();
		foreach ($applications as $app)
		{
			$criterion = $this->criteria[$app->label];
			if (!empty($app->results))
			{
				$apps = array_merge($apps, $criterion->postProcessResults(is_array($app->results) ? $app->results : array($app->results)));
			}
		}
		return $apps;
	}
}

?>
