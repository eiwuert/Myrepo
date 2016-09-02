<?php

/**
 * A summarized view of a customer's history
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_CustomerHistory
{
	const STATUS_BAD = 'bad';
	const STATUS_DENIED = 'denied';
	const STATUS_ACTIVE = 'active';
	const STATUS_PAID = 'paid';
	const STATUS_SETTLED = 'settled';
	const STATUS_PENDING = 'pending';
	const STATUS_DISAGREED = 'disagreed';
	const STATUS_CONFIRMED_DISAGREED = 'confirmed_disagreed';
	const STATUS_WITHDRAWN = 'withdrawn';
	const STATUS_ALLOWED = 'allowed';

	/**
	 * @var array
	 */
	protected $loans = array();

	/**
	 * Index companies by status
	 * @var array
	 */
	protected $company_status = array(
		self::STATUS_BAD => array(),
		self::STATUS_DENIED => array(),
		self::STATUS_ACTIVE => array(),
		self::STATUS_PAID => array(),
		self::STATUS_SETTLED => array(),
		self::STATUS_PENDING => array(),
		self::STATUS_DISAGREED => array(),
		self::STATUS_CONFIRMED_DISAGREED => array(),
		self::STATUS_ALLOWED => array(),
		self::STATUS_SETTLED => array()
	);

	/**
	 * @var array
	 */
	protected $dnl = array();

	/**
	 * @var array
	 */
	protected $dnl_other = array();

	/**
	 * @var array
	 */
	protected $dnlo = array();

	/**
	 * @var array
	 */
	protected $expire = array();

	/**
	 * @var array
	 */
	protected $results = array();

	/**
	 * Constructor
	 *
	 * @param array $data Customer History
	 */
	public function __construct(array $loans = array())
	{
		foreach($loans as $loan)
		{
			$this->addLoan($loan['company'], $loan['status'], $loan['application_id'], $loan['date'], array_key_exists('purchase_date', $loan) ? $loan['purchase_date'] : NULL, array());
		}
	}

	/**
	 * Returns customer history for the given company only
	 *
	 * @param string $company
	 * @return ECash_CustomerHistory
	 */
	public function getCompanyHistory($company)
	{
		$new = new self();

		$company = $this->normalizeCompany($company);

		// only add loans for the given company
		// we just reuse addLoan() to keep things simple
		foreach ($this->loans as $status=>$loans)
		{
			foreach ($loans as $app_id=>$loan)
			{
				if ($loan['company'] == $company)
				{
					$new->addLoan(
						$loan['company'],
						$status,
						$app_id,
						$loan['date'],
						$loan['purchase_date'],
						$loan['additional_info']
					);
				}
			}
		}

		// do not loan history is always
		// retained across companies?
		$new->dnl = $this->dnl;
		$new->dnl_other = $this->dnl_other;
		$new->dnlo = $this->dnlo;
		$new->results = $this->results;

		return $new;
	}

	/**
	 * Returns the number of Applications in the CustomerHistory Object
	 *
	 * @return Integer
	 */
	public function getApplicationCount()
	{
		$num_applications = 0;


		// only add loans for the given company
		// we just reuse addLoan() to keep things simple
		foreach ($this->loans as $status=>$loans)
		{
			foreach ($loans as $app_id=>$loan)
			{
				$num_applications++;
			}
		}
		return $num_applications;
	}

	/**
	 * Adds a loan to the customer's history
	 *
	 * @param string $company
	 * @param string $status
	 * @param int $app_id
	 * @param int $status_date
	 * @param int $purchase_date
	 * @param array $additional_info
	 * @return void
	 */
	public function addLoan($company, $status, $app_id, $status_date = NULL, $purchase_date = NULL, array $additional_info = NULL)
	{
		$company = $this->normalizeCompany($company);

		$valid = $this->filterPreviousLoans($company, $app_id, $status_date);
		if (!$valid)
		{
			return;
		}

		if (!isset($this->loans[$status]))
		{
			$this->loans[$status] = array();
		}

		if (!isset($this->loans[$status][$app_id])
			|| $this->loans[$status][$app_id]['date'] <= $status_date)
		{
			$this->loans[$status][$app_id] = array(
				'company' => $company,
				'date' => $status_date,
				'purchase_date' => $purchase_date,
				'additional_info' => $additional_info,
			);

			// store a list of companies by status
			$this->company_status[$status][$company][$app_id] = $company;
		}
	}

	/**
	 * Returns an array containing all loans
	 *
	 * @return array
	 */
	public function getLoans()
	{
		$returned_apps = array();

		foreach ($this->loans as $apps)
		{
			foreach ($apps as $app_id => $app)
			{
				$app['application_id'] = $app_id;
				$returned_apps[] = $app;
			}
		}

		return $returned_apps;
	}

	/**
	 * Filters previous loan history if the supplied app_id and company has an older entry.
	 *
	 * @param string $company
	 * @param integer $app_id
	 * @param integer|NULL $status_date
	 * @return boolean
	 */
	protected function filterPreviousLoans($company, $app_id, $status_date)
	{
		$valid = TRUE;

		foreach ($this->loans as $loan_status => $loans)
		{
			if (!isset($loans[$app_id]))
			{
				continue;
			}

			if ($loans[$app_id]['date'] > $status_date)
			{
				$valid = FALSE;
				continue;
			}

			if ($loans[$app_id]['date'] < $status_date)
			{
				$loan_company = $loans[$app_id]['company'];

				unset($this->company_status[$loan_status][$loan_company][$app_id]);

				if (empty($this->company_status[$loan_status][$loan_company]))
				{
					unset($this->company_status[$loan_status][$loan_company]);
				}

				unset($this->loans[$loan_status][$app_id]);

				if (empty($this->loans[$loan_status]))
				{
					unset($this->loans[$loan_status]);
				}
			}
		}

		return $valid;
	}

	/**
	 * Returns applications keyed by company that are in a given status.
	 *
	 * @param string $status
	 * @return array
	 */
	public function getLoanApplicationIds($status)
	{
		if (!array_key_exists($status, $this->loans))
		{
			return array();
		}

		$app_ids = array();
		foreach ($this->loans[$status] as $app_id => $data)
		{
			$app_ids[$data['company']][] = $app_id;
		}

		return $app_ids;
	}

	/**
	 * Set an application as expirable
	 *
	 * @param string $company
	 * @param int $app_id
	 * @param string $provider
	 * @param string $status
	 * @return void
	 */
	public function setExpirable($company, $app_id, $provider, $status)
	{
		$company = $this->normalizeCompany($company);

		$this->expire[$app_id][] = array(
			'company' => $company,
			'application_id' => $app_id,
			'provider' => $provider,
			'status' => $status,
		);
	}

	/**
	 * Indicates whether an application has been marked as expirable
	 *
	 * @param int $app_id
	 * @return bool
	 */
	public function getExpirable($app_id)
	{
		return isset($this->expire[$app_id]);
	}

	/**
	 * Set the DNL flag for the given company
	 *
	 * @param string $company
	 * @return void
	 */
	public function setDoNotLoan($company)
	{
		$company = $this->normalizeCompany($company);
		$this->dnl[$company] = $company;
	}

	/**
	 * Set the DNL flag for another company
	 *
	 * @param string $company
	 * @return void
	 */
	public function setDoNotLoanOtherCompany($company)
	{
		$company = $this->normalizeCompany($company);
		$this->dnl_other[$company] = $company;
	}

	/**
	 * Set a DNL override for the given company
	 *
	 * @param string $company
	 * @return void
	 */
	public function setDoNotLoanOverride($company)
	{
		// unset $this->dnl[$company] ??
		$company = $this->normalizeCompany($company);
		$this->dnlo[$company] = $company;
	}

	/**
	 * Records a result from a specific customer check
	 * These are used later to populate session values that olp depends
	 * on. This is relatively gay.
	 *
	 * @param string $name
	 * @param string $result
	 * @return void
	 */
	public function setResult($name, $result)
	{
		$this->results[$name] = $result;
	}

	/**
	 * Gets the latest loan in a certain status.
	 *
	 * @param string $loan_status One of the STATUS_* constants.
	 * @return string
	 */
	public function getNewestLoanDateInStatus($loan_status)
	{
		$last_date = NULL;

		if (isset($this->loans[$loan_status]) && is_array($this->loans[$loan_status]))
		{
			$last_date = array_reduce($this->loans[$loan_status], array($this, 'reduceToNewest'), $last_date);
		}

		return $last_date;
	}

	/**
	 * Get the number of denied applications
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountDenied($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_DENIED, $filter_date);
	}

	/**
	 * Get the number of bad accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountBad($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_BAD, $filter_date);
	}

	/**
	 * Get the number of pending accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountPending($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_PENDING, $filter_date);
	}

	/**
	 * Get the number of active accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountActive($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_ACTIVE, $filter_date);
	}

	/**
	 * Get the number of paid accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountPaid($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_PAID, $filter_date);
	}

	/**
	 * Get the number of settled accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountSettled($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_SETTLED, $filter_date);
	}

	/**
	 * Get the number of disagreed accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountDisagreed($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_DISAGREED, $filter_date);
	}

	/**
	 * Get the number of confirmed_disagreed accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountConfirmedDisagreed($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_CONFIRMED_DISAGREED, $filter_date);
	}

	/**
	 * Get the number of allowed accounts
	 *
	 * @param int $filter_date
	 * @return int
	 */
	public function getCountAllowed($filter_date = NULL)
	{
		return $this->getLoanCount(self::STATUS_ALLOWED, $filter_date);
	}


	/**
	 * Returns how many loans are in a certain status. Allows filtering by date.
	 *
	 * @param string $loan_status One of the STATUS_* constants.
	 * @param int $filter_date Filter loans that are more recent than this timestamp.
	 * @return int
	 */
	protected function getLoanCount($loan_status, $filter_date = NULL)
	{
		$loan_count = 0;

		if (isset($this->loans[$loan_status])
			&& is_array($this->loans[$loan_status]))
		{
			foreach ($this->loans[$loan_status] AS $application_id => $loan)
			{
				if ($filter_date === NULL
					|| $loan['date'] >= $filter_date)
				{
					$loan_count++;
				}
			}
		}

		return $loan_count;
	}

	/**
	 * Return the company short of all active loans
	 *
	 * @return array
	 */
	public function getActiveCompanies()
	{
		$companies = array_keys($this->company_status[self::STATUS_ACTIVE]);
		return empty($companies) ? $companies : array_combine($companies, $companies);
	}

	/**
	 * Return the company shorts with pending loans
	 *
	 * @return array
	 */
	public function getPendingCompanies()
	{
		$companies = array_keys($this->company_status[self::STATUS_PENDING]);
		return empty($companies) ? $companies : array_combine($companies, $companies);
	}

	/**
	 * Return the company short of all paid loans
	 *
	 * @return array
	 */
	public function getPaidCompanies()
	{
		$companies = array_keys($this->company_status[self::STATUS_PAID]);
		return empty($companies) ? $companies : array_combine($companies, $companies);
	}

	/**
	 * Return the company short of all companies that aren't to be lended to
	 * @return array
	 */
	public function getDoNotLoanCompanies()
	{
		return array_diff(
			$this->dnl,
			$this->dnlo
		);
	}

	/**
	 * Indicates whether the customer is a calculated react for the given company
	 *
	 * @param string $company
	 * @return bool
	 */
	public function getIsReact($company)
	{
		$company = $this->normalizeCompany($company);

		$is_react = !empty($this->company_status[self::STATUS_PAID][$company])
			|| !empty($this->company_status[self::STATUS_ALLOWED][$company]);

		return $is_react;
	}

	/**
	 * Indicates whether the customer should not be loaned to
	 *
	 * @param string $company
	 * @return bool
	 */
	public function getIsDoNotLoan($company)
	{
		$company = $this->normalizeCompany($company);
		return (isset($this->dnl[$company])
			|| isset($this->dnl_other[$company])
			&& !isset($this->dnlo[$company])
			);
	}

	/**
	 * Returns the react application id for the given company.
	 *
	 * @param string $company Property short of company.
	 * @return int Returns FALSE if none found.
	 */
	public function getReactID($company)
	{
		$react_id = FALSE;

		$company = $this->normalizeCompany($company);

		// Available statuses to react from
		$react_statuses = array(	self::STATUS_PAID,
									self::STATUS_ALLOWED
								);

		foreach($react_statuses as $status)
		{
			if (isset($this->loans[$status])
			&& is_array($this->loans[$status]))
			{
				foreach ($this->loans[$status] AS $app_id => $data)
				{
					if (!strcasecmp($data['company'], $company)
						&& $app_id > $react_id)
					{
						// Return the biggest application id
						$react_id = $app_id;
					}
				}
			}

		}


		return $react_id;
	}

	/**
	 * Get the companies that have "Do Not Loan" flags
	 *
	 * @return array
	 */
	public function getDoNotLoan()
	{
		// only return companies without overrides
		return $this->dnl;
	}

	/**
	 * Get the companies that have a "Do Not Loan" override
	 *
	 * @return array
	 */
	public function getDoNotLoanOverride()
	{
		return $this->dnlo;
	}

	/**
	 * Returns applications which can be expired.
	 *
	 * The applications are returned in an array keyed by app id. The contents
	 * of each key is an array of arrays containing the following indexes:
	 *
	 * company, application_id, provider, status
	 *
	 * @return array
	 * @see setExpirable
	 */
	public function getExpirableApplications()
	{
		return $this->expire;
	}

	/**
	 * Gets the recorded results of customer checks
	 *
	 * @return array
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * Normalize company name.
	 *
	 * @param string $company
	 * @return string
	 */
	public function normalizeCompany($company)
	{
		return strtolower($company);
	}

	/**
	 * Reduces loans to the newest date.
	 *
	 * @param int $timestamp
	 * @param array $loan
	 * @return int
	 */
	protected function reduceToNewest($timestamp, array $loan)
	{
		return max($timestamp, $loan['date']);
	}

	/**
	 * Display a pretty string for this class.
	 *
	 * @return string Blank if no loans found.
	 */
	public function __toString()
	{
		$application_strings = array();

		foreach ($this->loans AS $status => $loans)
		{
			foreach ($loans AS $application_id => $loan)
			{
				$application_strings[] = sprintf("%d is %s with %s at %s.",
					$application_id,
					strtoupper($status),
					strtoupper($loan['company']),
					date('Y-m-d h:i:s A', $loan['date'])
				);
			}
		}

		return implode("\n", $application_strings);
	}

	/**
	 * Returns the number of leads purchased using $threshold.
	 *
	 * Threshold is a relative time in the past. For instance '5 days' will look for purchases
	 * within the last 5 days.
	 *
	 * @param string $threshold
	 * @return int
	 */
	public function getPurchasedLeadCount($threshold)
	{
		$check_time = strtotime('-'. $threshold);
		$count = 0;
		foreach ($this->loans as $loans)
		{
			foreach ($loans as $loan)
			{
				if ($loan['purchase_date'] !== NULL && $loan['purchase_date'] > $check_time)
				{
					$count++;
				}
			}
		}

		return $count;
	}

	/** Stores the customer history data to the database.
	 *
	 * @NOTE This does NOT trigger an observable insert event at this time.
	 * If you need to observe the CustomerHistorySearch model for this, the
	 * current work around is to watch the modification event for the auto-
	 * increment column.
	 *
	 * @param int $application_id
	 * @param string $property_short
	 * @return void
	 */
	public function saveToDatabase($application_id, $property_short)
	{
		// Base model, only used to get the database instance.
		/*$factory = OLP_Factory::getInstance();
		$base_model = $factory->getModel('CustomerHistorySearch');

		// Model list, to batch all the customer history status.
		$list = new DB_Models_ModelList_1(get_class($base_model), $base_model->getDatabaseInstance());

		// Normal bucket
		foreach ($this->loans AS $status => $loans)
		{
			foreach ($loans AS $match_application_id => $loan)
			{
				$model = $factory->getReferencedModel('CustomerHistorySearch');
				$model->application_id = $application_id;
				$model->property_short = $property_short;
				$model->match_application_id = $match_application_id;
				$model->match_property_short = $loan['company'];
				$model->match_status = $status;

				$list->add($model->getBaseModel());
			}
		}

		// Expire bucket
		foreach ($this->expire AS $match_application_id => $loan)
		{
			// We only need one of the company names, doesn't matter if
			// from the OLP or eCash side of the expiring list.
			$loan = array_pop($loan);
			if (isset($loan['company']))
			{
				$model = $factory->getReferencedModel('CustomerHistorySearch');
				$model->application_id = $application_id;
				$model->property_short = $property_short;
				$model->match_application_id = $match_application_id;
				$model->match_property_short = $loan['company'];
				$model->match_status = 'expirable';

				$list->add($model->getBaseModel());
			}
		}

		// Write all entries at once.
		$list->save();*/
	}
}

?>
