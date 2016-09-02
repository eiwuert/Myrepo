<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Abstract
 *
 * @author mikel
 */
abstract class VendorAPI_PreviousCustomer_Criteria_Abstract implements VendorAPI_PreviousCustomer_ICriterion
{
	/**
	 * @var VendorAPI_PreviousCustomer_CustomerHistoryStatusMap
	 */
	protected $status_map;

	public function __construct(VendorAPI_PreviousCustomer_CustomerHistoryStatusMap $status_map)
	{
		$this->status_map = $status_map;
	}

	abstract protected function getCriteriaMapping();

	abstract protected function getIgnoredStatuses();

	abstract protected function overrideDoNotLoanLookup();

	protected function getAdditionalCriteria()
	{
		return array();
	}

	protected function skipCriteria(array $app_data)
	{
		foreach (array_keys($this->getCriteriaMapping()) as $column)
		{
			if (!isset($app_data[$column]))
			{
				return TRUE;
			}
		}
	}

	protected function skipApplication (stdClass $app_data)
	{
		$statuses = $this->getIgnoredStatuses();

		if (!is_array($statuses))
		{
			return FALSE;
		}
		
		return in_array($this->status_map->getStatus($app_data->application_status), $this->getIgnoredStatuses());
	}

	protected function modifyData(stdClass $app_data)
	{
		if ($this->overrideDoNotLoanLookup())
		{
			$app_data->do_not_loan_in_company = FALSE;
			$app_data->do_not_loan_other_company = FALSE;
			$app_data->do_not_loan_override = FALSE;
			$app_data->regulatory_flag = FALSE;
		}
	}

	public function getAppServiceObject(array $app_data)
	{
		if ($this->skipCriteria($app_data))
		{
			return array();
		}

		$mapping = $this->getCriteriaMapping();
		if (!is_array($mapping) || empty($mapping))
		{
			return array();
		}

		$criteria = array();
		foreach ($mapping as $column => $field)
		{
			$criteria[] = $this->createCriterion($field, $app_data[$column]);
		}

		$mapping = $this->getAdditionalCriteria();

		if (is_array($mapping) && !empty($mapping))
		{
			foreach ($mapping as $field => $info)
			{
				list($strategy, $values) = $info;
				$criteria[] = $this->createCriterion($field, $values, $strategy);
			}
		}

		return $criteria;
	}

	protected function createCriterion($field, $value, $strategy = 'is')
	{
		$criterion = new stdClass;
		$criterion->searchCriteria = $value;
		$criterion->field = $field;
		$criterion->strategy = $strategy;

		return $criterion;
	}

	public function postProcessResults(array $apps)
	{
		$statuses = $this->getIgnoredStatuses();

		$processed = array();
		foreach ($apps as $app_data)
		{
			if ($this->skipApplication($app_data))
			{
				continue;
			}

			$this->modifyData($app_data);

			$processed[] = $app_data;
		}

		return $processed;
	}
}
?>
