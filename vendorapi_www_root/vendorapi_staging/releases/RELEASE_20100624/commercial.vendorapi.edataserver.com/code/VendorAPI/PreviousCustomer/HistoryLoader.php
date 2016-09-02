<?php

/**
 * Loads a customer history object with data from the app service using a
 * specified set of criteria.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_HistoryLoader
{
	/**
	 * @var WebServices_Client_AppClient
	 */
	protected $app_service;

	/**
	 * @var bool
	 */
	protected $expire_apps;

	/**
	 * @var VendorAPI_PreviousCustomer_CustomerHistoryStatusMap
	 */
	protected $status_map;

	/**
	 * @var VendorAPI_PreviousCustomer_CriteriaContainer
	 */
	protected $criteria;

	/**
	 * @param WebServices_Client_AppClient $app_service
	 */
	public function __construct(WebServices_Client_AppClient $app_service, VendorAPI_PreviousCustomer_CustomerHistoryStatusMap $status_map, VendorAPI_PreviousCustomer_CriteriaContainer $criteria, $expire_apps = FALSE)
	{
		$this->app_service = $app_service;
		$this->status_map = $status_map;
		$this->criteria = $criteria;
		$this->expire_apps = $expire_apps;
	}

	/**
	 * Loads history from the app service into the customer history object.
	 *
	 * You can pass an application id to the third parameter to prevent that app from being loaded into the object.
	 *
	 * @param ECash_CustomerHistory $customer_history
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $criteria
	 * @param int $excluded_application
	 * @param array $app_data
	 */
	public function loadHistoryObject(ECash_CustomerHistory $customer_history, array $app_data = NULL, $excluded_application = NULL)
	{
		$prev_cust_data = $this->app_service->getPreviousCustomerApps($this->criteria->getAppServiceObject($app_data));
		$apps = $this->criteria->postProcessResults($prev_cust_data);

		//If there eventually winds up being alot of crap that happens in conditional statements
		//below then this should be changed to where a strategy pattern is used to pass in applicable
		//operations. For instance, if I were to do that now I would have an expire strategy class to handle
		//expiring apps and I would have a loan strategy to handle adding loans. Finally I would create a composite
		//strategy to allow me to combine them, then I could optionally leave in or out the expire strategy depending
		//on whether or not that is necessary. Doing that for just two branches I think would be overkill, but we
		//could feasibly have more things being added on in the future at which point that decision should be revisited.
		//
		$react_type = $app_data['react_type'];
		foreach ($apps as $app)
		{
			if (isset($excluded_application) && $app->application_id == $excluded_application)
			{
				continue;
			}
			elseif ($this->appIsExpirable($app))
			{
				$customer_history->setExpirable($app->company, $app->application_id, 'ECASH', $app->application_status);
			}
			elseif (($react_type == 'refi') && ($this->status_map->getStatus($app->application_status) == 'active'))
			{
			        $customer_history->addLoan(
					$app->company,
					'paid',
					$app->application_id,
					strtotime($app->date_application_status_set),
					strtotime($app->date_created),
					(array)$app
				);
			}
			else
			{
				$customer_history->addLoan(
					$app->company,
					$this->status_map->getStatus($app->application_status),
					$app->application_id,
					strtotime($app->date_application_status_set),
					strtotime($app->date_created),
					(array)$app
				);
			}

			if ($app->do_not_loan_in_company || $app->regulatory_flag)
			{
				$customer_history->setDoNotLoan($app->company);
			}

			if ($app->do_not_loan_other_company)
			{
				$customer_history->setDoNotLoanOtherCompany($app->company);
			}

			if ($app->do_not_loan_override && !$app->regulatory_flag)
			{
				$customer_history->setDoNotLoanOverride($app->company);
			}
		}
	}

	protected function appIsExpirable(stdClass $app)
	{
		return ($this->expire_apps && $this->status_map->isStatusExpirable($app->application_status));
	}
}

?>
