<?php

/**
 * A generic customer history provider from ECash
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_HistoryProvider implements ECash_ICustomerHistoryProvider
{
	const PROVIDER_NAME = 'ECASH';

	/**
	 * Map of status names to simple names (i.e., 'bad', 'active')
	 * @var array
	 */
	protected $status_map = array(
		// bad
		'*root::applicant::fraud::confirmed' => 'bad',
		'*root::customer::collections::arrangements::amortization' => 'bad',
		'*root::customer::collections::arrangements::arrangements_failed' => 'bad',
		'*root::customer::collections::arrangements::current' => 'bad',
		'*root::customer::collections::arrangements::hold' => 'bad',
		'*root::customer::collections::bankruptcy::dequeued' => 'bad',
		'*root::customer::collections::bankruptcy::queued' => 'bad',
		'*root::customer::collections::bankruptcy::unverified' => 'bad',
		'*root::customer::collections::bankruptcy::verified' => 'bad',
		'*root::customer::collections::chargeoff' => 'bad',
		'*root::customer::collections::contact::dequeued' => 'bad',
		'*root::customer::collections::contact::follow_up' => 'bad',
		'*root::customer::collections::contact::queued' => 'bad',
		'*root::customer::collections::deceased::unverified' => 'bad',
		'*root::customer::collections::deceased::verified' => 'bad',
		'*root::customer::collections::indef_dequeue' => 'bad',
		'*root::customer::collections::new' => 'bad',
		'*root::customer::collections::quickcheck::arrangements' => 'bad',
		'*root::customer::collections::quickcheck::ready' => 'bad',
		'*root::customer::collections::quickcheck::pending' => 'bad',
		'*root::customer::collections::quickcheck::return' => 'bad',
		'*root::customer::collections::quickcheck::sent' => 'bad',
		'*root::customer::collections::skip_trace' => 'bad',
		'*root::customer::servicing::past_due' => 'bad',
		'*root::customer::settled' => 'bad',
		'*root::customer::write_off' => 'bad',
		'*root::external_collections::pending' => 'bad',
		'*root::external_collections::sent' => 'bad',

		// denied
		'*root::applicant::denied' => 'denied',

		// disagreed
		'*root::prospect::disagree' => 'disagreed',

		// confirmed_disagreed
		'*root::prospect::confirm_declined' => 'confirmed_disagreed',

		// withdrawn
		'*root::applicant::withdrawn'=>'withdrawn',

		// cancel is withdrawn
		//'*root::customer::servicing::canceled'=>'withdrawn',
                '*root::applicant::canceled'=>'withdrawn',

		// paid
		'*root::customer::paid' => 'paid',
		'*root::external_collections::recovered' => 'paid',

		// settled
		'*root::customer::settled' => ECash_CustomerHistory::STATUS_SETTLED,

		// active
		'*root::customer::servicing::active' => 'active',
        
        // cccs is active
		'*root::customer::collections::cccs' => 'active',

		// pending is active
		'*root::applicant::fraud::dequeued' => 'pending',
		'*root::applicant::fraud::follow_up' => 'pending',
		'*root::applicant::fraud' => 'pending',
		'*root::applicant::fraud::queued' => 'pending',
		'*root::applicant::high_risk::dequeued' => 'pending',
		'*root::applicant::high_risk::follow_up' => 'pending',
		'*root::applicant::high_risk' => 'pending',
		'*root::applicant::high_risk::queued' => 'pending',
		'*root::applicant::underwriting::dequeued' => 'pending',
		'*root::applicant::underwriting::follow_up' => 'pending',
		'*root::applicant::underwriting::preact' => 'pending',
		'*root::applicant::underwriting::queued' => 'pending',
		'*root::applicant::verification::add1' => 'pending',
		'*root::applicant::verification::dequeued' => 'pending',
		'*root::applicant::verification::follow_up' => 'pending',
		'*root::applicant::verification::queued' => 'pending',
		'*root::customer::servicing::approved' => 'pending',
		'*root::customer::servicing::funding_failed' => 'pending',
		'*root::customer::servicing::hold' => 'pending',
		'*root::prospect::agree' => 'pending',
		'*root::prospect::confirmed' => 'pending',
		'*root::prospect::in_process' => 'pending',
		'*root::prospect::pending' => 'pending',
		'*root::prospect::preact_confirmed' => 'pending',
	
		// Allowind - sort of paid
		'*root::external_collections::allowed' => 'allowed',
	
	);

	/**
	 * Statuses that can be expired
	 *
	 * @var array
	 */
	protected $expirable = array(
		'*root::prospect::confirmed',
		'*root::prospect::pending',
		'*root::prospect::preact_confirmed',
	);

	
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * List of statuses to ignore when adding loans to customer history - GForge #12558 [DW]
	 * @var array
	 */
	protected $ignored_statuses;

	/**
	 * @var array
	 */
	protected $companies;

	/**
	 * @var array
	 */
	protected $status_cache = array();

	/**
	 * @var array
	 */
	protected $company_cache = array();

	/**
	 * @var int
	 */
	protected $exclude;

	/**
	 * @var bool
	 */
	protected $ignore_expirable;

	/**
	 * @var bool
	 */
	protected $ignore_preactable;

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * @param DB_IConnection_1 $db Database connection
	 * @param array $companies Property shorts that we run for
	 * @param bool $expire Ignore and mark expirable apps
	 * @param bool $preact Ignore and mark preactable apps
	 */
	public function __construct(DB_IConnection_1 $db, array $companies, $expire = FALSE, $preact = FALSE)
	{
		$this->db = $db;
		$this->companies = $companies;
		$this->ignore_expirable = $expire;
		$this->ignore_preactable = $preact;
	}

	/**
	 * Restricts the history to a specific company
	 *
	 * @param string $company
	 * @return void
	 */
	public function setCompany($company)
	{
		$this->companies = array($company);
	}

	/**
	 * Excludes an application from the generated history
	 *
	 * @param int $app_id
	 * @return void
	 */
	public function excludeApplication($app_id)
	{
		$this->exclude = $app_id;
	}

	/**
	 * Runs the Do Not Loan checks
	 *
	 * @param string $ssn
	 * @param ECash_CustomerHistory $history
	 * @return void
	 */
	public function runDoNotLoan($flag_info, $ssn, ECash_CustomerHistory $history = NULL)
	{
		if (!$history) $history = new ECash_CustomerHistory();
		$company_id_map = $this->getCompanyMap();

		if ($flag_info)
		{
			/**
			 * When pulling data from the app service it will be from all visible
			 * databases, so everything that needs to apply to just the setting company
			 * needs to be filtered by company_id (such as regulatory and overrides).
			 */
			foreach ($this-> companies as $company)
			{
				$company_id = $company_id_map[strtolower($company)];
 
				if (!empty($flag_info->do_not_loan))
				{
					$flags_dnl = (is_array($flag_info->do_not_loan)) 
						? $flag_info->do_not_loan 
						: array($flag_info->do_not_loan);

					foreach ($flags_dnl as $flag_dnl)
					{
						if(! $flag_dnl->active_status)
						{
							continue;
						}
						elseif ($flag_dnl->company_id == $company_id)
						{
							$history->setDoNotLoan($company);
						}
						else
						{
							$history->setDoNotLoanOtherCompany($company);
						}
					}
				}

				if (!empty($flag_info->regulatory))
				{
					$flags_regulatory = (is_array($flag_info->regulatory)) 
						? $flag_info->regulatory 
						: array($flag_info->regulatory);

					foreach ($flags_regulatory as $flag_regulatory)
					{
						if(! $flag_regulatory->active_status)
						{
							continue;
						}
						elseif ($flag_regulatory->companyId == $company_id)
						{
							$history->setDoNotLoan($company);
						}
					}
				}

				if (!empty($flag_info->do_not_loan_override))
				{
					$flags_override = (is_array($flag_info->do_not_loan_override)) 
						? $flag_info->do_not_loan_override 
						: array($flag_info->do_not_loan_override);

					foreach ($flags_override as $flag_override)
					{
						if ($flag_override->companyId == $company_id)
						{
							$history->setDoNotLoanOverride($company);
						}
					}
				}
 			}
		}
		else 
		{
			foreach ($this->companies as $company)
 			{
				$company_id = $company_id_map[strtolower($company)];
 
				if ($this->onDoNotLoanList($ssn, $company_id)
					|| $this->hasRegulatoryFlag($ssn, $company_id))
				{
					$history->setDoNotLoan($company);
				}

				if ($this->onDoNotLoanListOtherCompany($ssn, $company_id))
				{
					$history->setDoNotLoanOtherCompany($company);
				}

				if ($this->onDoNotLoanOverrideList($ssn, $company_id))
				{
					$history->setDoNotLoanOverride($company);
				}
			}
		}
	}

	/**
	 * Gets the history by a set of conditions
	 * If an customer history instance is not provided, a default one will be created
	 * @param array $match
	 * @param ECash_CustomerHistory $history
	 * @param array $ignore_statuses
	 * @return ECash_CustomerHistory
	 */
	public function getHistoryBy(array $match, ECash_CustomerHistory $history = NULL, array $ignore_statuses = array())
	{
		// Set ignored_statuses - GForge #12558 [DW]
		$this->ignored_statuses = $ignore_statuses;

		if (!$history) $history = new ECash_CustomerHistory();

		// sets this->query and this->params...
		$this->buildQuery($match);

		foreach ($this->companies as $company)
		{
			// company ID is always the first param, per buildQuery
			$company_id_map = $this->getCompanyMap();
			$this->params[0] = $company_id_map[strtolower($company)];

			$st = DB_Util_1::queryPrepared(
				$this->db,
				$this->query,
				$this->params
			);
			$this->fetchInto($st, $history);
		}

		return $history;
	}

	/**
	 * Builds a query based on the conditions in $match
	 *
	 * The $match array contains column name => value pairs that
	 * are used to build the WHERE clause. A special exception is
	 * made for bank_account, as it can contain multiple permutations
	 * of the same account, which are built as an IN. Sets
	 * $this->query and $this->params.
	 *
	 * @param array $match
	 * @return void
	 */
	protected function buildQuery(array $match)
	{
		$this->query = '
			SELECT company_id,
				application_id,
				application_status_id,
				date_application_status_set,
				date_created,
				olp_process
			FROM application
			WHERE company_id = ?
		';
		if ($this->exclude)
		{
			$this->query .= ' AND application_id != '.$this->exclude;
		}

		// need a placeholder for the company ID
		$this->params = array(NULL);

		// special exception is made for bank account,
		// because we test every permutation
		if (isset($match['bank_account'])
			&& is_array($match['bank_account']))
		{
			$acct = $match['bank_account'];
			unset($match['bank_account']);

			$this->query .= ' AND bank_account IN (?'.str_repeat(', ?', count($acct) - 1).')';
			$this->params = array_merge($this->params, $acct);
		}

		// special exception is made for dob because it is
		// formatted differently in ecash - GForge #10558 [DW]
		if (isset($match['dob']))
		{
			$match['dob'] = date('Y-m-d', strtotime($match['dob']));
		}

		if ($match)
		{
			$this->query .= ' AND '.implode(' = ? AND ', array_keys($match)).' = ?';
			$this->params = array_merge($this->params, array_values($match));
		}
	}

	/**
	 * Builds a CustomerHistory object from a statement
	 *
	 * @param DB_IStatement_1 $st
	 * @param ECash_CustomerHistory $history
	 * @return ECash_CustomerHistory
	 */
	protected function fetchInto(DB_IStatement_1 $st, ECash_CustomerHistory $history)
	{
		// get a map of status IDs => names
		$status_id_map = $this->getStatusMap();
		$company_id_map = $this->getCompanyMap();
		while (($row = $st->fetch()) !== FALSE)
		{
			$status = $status_id_map[$row['application_status_id']];
			$company = $company_id_map[$row['company_id']];

			// if we're ignore expirable applications, and the
			// application is expirable, make note and move on
			if ($this->isExpirable($status))
			{
				$history->setExpirable($company, $row['application_id'], self::PROVIDER_NAME, $status);
			}
			// if we don't have a mapping for this status, skip it...
			// or if it's a status to ignore, also skip it... - GForge #12558 [DW]
			// or if it's preactable and we're ignore preacts, also skip
			elseif (isset($this->status_map[$status])
				&& !in_array($this->status_map[$status], $this->ignored_statuses)
				&& !$this->isPreactable($row['application_id'], $this->status_map[$status]))
			{
				$purchase_date = ($row['olp_process'] == 'online_confirmation')
					? strtotime($row['date_created'])
					: NULL;

				$history->addLoan(
					$company,
					$this->status_map[$status],
					$row['application_id'],
					strtotime($row['date_application_status_set']),
					$purchase_date
				);
			}
		}
	}

	/**
	 * Indicates whether an application is preactable
	 *
	 * @param int $app_id
	 * @param string $status
	 * @return boolean True if preactable
	 */
	protected function isPreactable($app_id, $status)
	{
		return FALSE;
		/* Preacts are broken code right now, as we don't have the required
		 * database connection for lib/ecash_api.php's functions. At this
		 * time, eCash is not setup to send preacts. If they want to, this
		 * code will need to be updated to pass the correct database
		 * connection, or we need to move away from lib/ecash_api.php.
		 */

		if ($this->ignore_preactable
			&& $status === 'active')
		{
			$payments_left = Scheduled_Payments_Left($app_id, $this->ldb);
			$payment_info = Pending_Payment_Info($app_id, $this->ldb);

			// must have _exactly_ -65.00 pending over _exactly_ two payments?
			return ($payment_info->pending_amount == -65.00
				&& $payment_info->pending_payments == 2
				&& $payments_left == 0);
		}
		return FALSE;
	}

	/**
	 * Indicates whether a status is expirable
	 *
	 * @param string $status
	 * @return bool
	 */
	protected function isExpirable($status)
	{
		return ($this->ignore_expirable
			&& in_array($status, $this->expirable));
	}

	/**
	 * Gets a map of company IDs => names
	 *
	 * @return array
	 */
	protected function getCompanyMap()
	{
		if (!$this->company_cache)
		{
			$query = "
				SELECT company_id, name_short
				FROM company
				WHERE active_status = 'active'
			";
			$st = $this->db->query($query);

			$map = array();

			while (($row = $st->fetch()) !== FALSE)
			{
				$map[$row['company_id']] = strtolower($row['name_short']);
				$map[strtolower($row['name_short'])] = $row['company_id'];
			}

			return $this->company_cache = $map;
		}

		return $this->company_cache;
	}

	/**
	 * Caches (or fetches) a map of status IDs=>names
	 *
	 * @return array
	 */
	protected function getStatusMap()
	{
		// cache statuses with the database instance
		if (!$this->status_cache)
		{
			$query = "
				SELECT application_status_id,
					level5, level4, level3, level2, level1, level0
				FROM application_status_flat
			";
			$st = $this->db->query($query);

			$map = array();

			while (($row = $st->fetch()) !== FALSE)
			{
				$status = '';

				// switches are not identity operators, thus anything
				// that's not ''/FALSE/NULL/0 will be TRUE
				switch (TRUE)
				{
					case ($row['level5']):
						$status .= $row['level5'].'::';
					case ($row['level4']):
						$status .= $row['level4'].'::';
					case ($row['level3']):
						$status .= $row['level3'].'::';
					case ($row['level2']):
						$status .= $row['level2'].'::';
					case ($row['level1']):
						$status .= $row['level1'].'::';
					case ($row['level0']):
						$status .= $row['level0'];
				}

				$map[$row['application_status_id']] = $status;
			}

			return $this->status_cache = $map;
		}

		return $this->status_cache;
	}

	/** Checks to see if this is in the Do Not Loan list for the current company.
	 *
	 * @param string $ssn
	 * @param int $company_id
	 * @return bool
	 */
	protected function onDoNotLoanList($ssn, $company_id)
	{
		$query = "
			SELECT 1
			FROM do_not_loan_flag AS dnl
			WHERE dnl.ssn = ?
				AND dnl.company_id = ?
				AND active_status = 'active'
			LIMIT 1
		";

		$params = array(
			$ssn,
			$company_id,
		);

		return DB_Util_1::execPrepared($this->db, $query, $params) > 0;
	}

	/** Checks to see if this is in the Do Not Loan list for another company.
	 *
	 * @param string $ssn
	 * @param int $company_id
	 * @return bool
	 */
	protected function onDoNotLoanListOtherCompany($ssn, $company_id)
	{
		$query = "
			SELECT 1
			FROM do_not_loan_flag AS dnl
			WHERE dnl.ssn = ?
				AND dnl.company_id != ?
				AND active_status = 'active'
			LIMIT 1
		";

		$params = array(
			$ssn,
			$company_id,
		);

		return DB_Util_1::execPrepared($this->db, $query, $params) > 0;
	}

	/** Checks to see if this is in the Do Not Loan Override list.
	 *
	 * @param string $ssn
	 * @param int $company_id
	 * @return bool
	 */
	protected function onDoNotLoanOverrideList($ssn, $company_id)
	{
		$query = "
			SELECT 1
			FROM do_not_loan_flag_override AS dnlo
			WHERE dnlo.ssn = ?
				AND dnlo.company_id = ?
			LIMIT 1
		";

		$params = array(
			$ssn,
			$company_id,
		);

		return DB_Util_1::execPrepared($this->db, $query, $params) > 0;
	}

	/**
	 * Checks to see if this has the Regulatory Flag.
	 *
	 * @param string $ssn
	 * @param int $company_id
	 * @return bool
	 */
	protected function hasRegulatoryFlag($ssn, $company_id)
	{
		$query = "
			SELECT 1
			FROM regulatory_flag
				JOIN customer USING (customer_id)
			WHERE active_status = 'active'
				AND ssn = ?
				AND company_id = ?
			LIMIT 1
		";

		$params = array(
			$ssn,
			$company_id
		);

		return DB_Util_1::execPrepared($this->db, $query, $params) > 0;
	}
}

?>
