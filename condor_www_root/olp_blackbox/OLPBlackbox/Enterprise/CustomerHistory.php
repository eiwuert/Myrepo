<?php

/**
 * A summarized view of a customer's history
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CustomerHistory
{
	const STATUS_BAD = 'bad';
	const STATUS_DENIED = 'denied';
	const STATUS_ACTIVE = 'active';
	const STATUS_PAID = 'paid';
	const STATUS_PENDING = 'pending';
	// Add disagreed statuses to check for number of disagreed apps - GForge #8774 [DW]
	const STATUS_DISAGREED = 'disagreed';
	const STATUS_CONFIRMED_DISAGREED = 'confirmed_disagreed';

	/**
	 * @var int unix timestamp
	 */
	protected $last_denied_date;

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
		self::STATUS_PENDING => array(),
		self::STATUS_DISAGREED => array(),
		self::STATUS_CONFIRMED_DISAGREED => array(),
	);

	/**
	 * @var array
	 */
	protected $dnl = array();

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
	 * Returns customer history for the given company only
	 *
	 * @param string $company
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function getCompanyHistory($company)
	{
		$new = new self();

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
						$loan['date']
					);
				}
			}
		}

		// do not loan history is always
		// retained across companies?
		$new->dnl = $this->dnl;
		$new->dnlo = $this->dnlo;

		return $new;
	}

	/**
	 * Adds a loan to the customer's history
	 *
	 * @param string $company
	 * @param string $status
	 * @param int $app_id
	 * @param int $status_date
	 * @return void
	 */
	public function addLoan($company, $status, $app_id, $status_date = NULL)
	{
		// normalize on lowercase
		$company = strtolower($company);

		if (!isset($this->loans[$status]))
		{
			$this->loans[$status] = array();
		}

		$this->loans[$status][$app_id] = array(
			'company' => $company,
			'date' => $status_date,
		);

		// store a list of companies by status
		$this->company_status[$status][$company] = $company;

		// keep track of the last date we were denied
		if ($status === self::STATUS_DENIED
			&& $status_date >= $this->last_denied_date)
		{
			$this->last_denied_date = $status_date;
		}
	}

	/**
	 * Set an application as expirable
	 *
	 * @param string $company
	 * @param int $app_id
	 * @return void
	 */
	public function setExpirable($company, $app_id)
	{
		$company = strtolower($company);

		$this->expire[$app_id] = array(
			'company' => $company,
			'application_id' => $app_id,
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
		$company = strtolower($company);
		$this->dnl[$company] = $company;
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
		$company = strtolower($company);
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
	 * Get the date of the last application that was denied
	 *
	 * @return int
	 */
	public function getLastDeniedDate()
	{
		return $this->last_denied_date;
	}

	/**
	 * Get the number of denied applications
	 *
	 * @return int
	 */
	public function getCountDenied()
	{
		return isset($this->loans[self::STATUS_DENIED])
			? count($this->loans[self::STATUS_DENIED])
			: 0;
	}

	/**
	 * Get the number of bad accounts
	 *
	 * @return int
	 */
	public function getCountBad()
	{
		return isset($this->loans[self::STATUS_BAD])
			? count($this->loans[self::STATUS_BAD])
			: 0;
	}

	/**
	 * Get the number of pending accounts
	 *
	 * @return int
	 */
	public function getCountPending()
	{
		return isset($this->loans[self::STATUS_PENDING])
			? count($this->loans[self::STATUS_PENDING])
			: 0;
	}

	/**
	 * Get the number of active accounts
	 *
	 * @return int
	 */
	public function getCountActive()
	{
		return isset($this->loans[self::STATUS_ACTIVE])
			? count($this->loans[self::STATUS_ACTIVE])
			: 0;
	}

	/**
	 * Get the number of paid accounts
	 *
	 * @return int
	 */
	public function getCountPaid()
	{
		return isset($this->loans[self::STATUS_PAID])
			? count($this->loans[self::STATUS_PAID])
			: 0;
	}

	/**
	 * Get the number of disagreed accounts
	 *
	 * @return int
	 */
	public function getCountDisagreed()
	{
		return isset($this->loans[self::STATUS_DISAGREED])
			? count($this->loans[self::STATUS_DISAGREED])
			: 0;
	}

	/**
	 * Get the number of confirmed_disagreed accounts
	 *
	 * @return int
	 */
	public function getCountConfirmedDisagreed()
	{
		return isset($this->loans[self::STATUS_CONFIRMED_DISAGREED])
			? count($this->loans[self::STATUS_CONFIRMED_DISAGREED])
			: 0;
	}

	/**
	 * Return the company short of all active loans
	 *
	 * @return array
	 */
	public function getActiveCompanies()
	{
		return $this->company_status[self::STATUS_ACTIVE];
	}

	/**
	 * Return the company shorts with pending loans
	 *
	 * @return array
	 */
	public function getPendingCompanies()
	{
		return $this->company_status[self::STATUS_PENDING];
	}

	/**
	 * Return the company short of all paid loans
	 *
	 * @return array
	 */
	public function getPaidCompanies()
	{
		return $this->company_status[self::STATUS_PAID];
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
		return in_array(strtolower($company), $this->company_status[self::STATUS_PAID]);
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
	 * Returns applications which can be expired
	 *
	 * @return array
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
}

?>
