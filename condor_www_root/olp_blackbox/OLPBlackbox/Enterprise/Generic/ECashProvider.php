<?php

/**
 * A generic customer history provider from ECash
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_ECashProvider implements OLPBlackbox_Enterprise_ICustomerHistoryProvider
{
	/**
	 * Map of status names to simple names (i.e., 'bad', 'active')
	 * @var array
	 */
	protected $status_map = array(
		// bad
		'*root::customer::collections::arrangements::arrangements_failed' => 'bad',
		'*root::customer::collections::arrangements::current' => 'bad',
		'*root::customer::collections::arrangements::hold' => 'bad',
		'*root::customer::collections::arrangements::amortization' => 'bad',
		'*root::customer::collections::bankruptcy::queued' => 'bad',
		'*root::customer::collections::bankruptcy::dequeued' => 'bad',
		'*root::customer::collections::bankruptcy::unverified' => 'bad',
		'*root::customer::collections::bankruptcy::verified' => 'bad',
		'*root::customer::collections::contact::queued' => 'bad',
		'*root::customer::collections::contact::dequeued' => 'bad',
		'*root::customer::collections::quickcheck::arrangements' => 'bad',
		'*root::customer::collections::quickcheck::ready' => 'bad',
		'*root::customer::collections::quickcheck::return' => 'bad',
		'*root::customer::collections::quickcheck::sent' => 'bad',
		'*root::external_collections::pending' => 'bad',
		'*root::external_collections::sent' => 'bad',
		'*root::customer::servicing::past_due' => 'bad',

		// denied
		'*root::applicant::denied' => 'denied',

		// paid
		'*root::customer::paid' => 'paid',
		'*root::external_collections::recovered' => 'paid',

		// active
		'*root::customer::servicing::active' => 'active',

		// pending is active
		'*root::customer::servicing::approved' => 'pending',
		'*root::customer::servicing::hold' => 'pending',
		'*root::prospect::agree' => 'pending',
		'*root::prospect::pending' => 'pending',
		'*root::applicant::underwriting::queued' => 'pending',
		'*root::applicant::underwriting::dequeued' => 'pending',
		'*root::applicant::underwriting::follow_up' => 'pending',
		'*root::applicant::underwriting::preact' => 'pending',
		'*root::applicant::verification::queued' => 'pending',
		'*root::applicant::verification::dequeued' => 'pending',
		'*root::applicant::verification::follow_up' => 'pending',

		// prospect..?
		/*'*root::prospect::confirmed' => 'prospect',
		'*root::prospect::preact_confirmed' => 'prospect',*/
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
	 * @var array
	 */
	protected $companies;

	/**
	 * @var Util_ObjectStorage_1
	 */
	protected $status_cache;

	/**
	 * @var Util_ObjectStorage_1
	 */
	protected $company_cache;

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
	 * @param array $companies
	 * @param bool $expire
	 * @param bool $preact
	 */
	public function __construct(array $companies, $expire = FALSE, $preact = FALSE)
	{
		$this->companies = $companies;
		$this->ignore_expirable = $expire;
		$this->ignore_preactable = $preact;

		// using ObjectStorage allows us to cache things with the
		// database instance -- if two companies are on the same
		// instance, then they'll automatically use the same cache
		$this->status_cache = new Util_ObjectStorage_1();
		$this->company_cache = new Util_ObjectStorage_1();
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
	 * Gets the history by a set of conditions
	 * If an customer history instance is not provided, a default one will be created
	 * @param array $match
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function getHistoryBy(array $match, OLPBlackbox_Enterprise_CustomerHistory $history = NULL)
	{
		if (!$history) $history = new OLPBlackbox_Enterprise_CustomerHistory();

		// sets this->query and this->params...
		$this->buildQuery($match);

		foreach ($this->companies as $company)
		{
			$db = $this->getCompanyConnection($company);

			// company ID is always the first param, per buildQuery
			$company_id_map = $this->getCompanyMap($db);
			$this->params[0] = $company_id_map[strtolower($company)];

			$st = DB_Util_1::queryPrepared(
				$db,
				$this->query,
				$this->params
			);
			$this->fetchInto($db, $st, $history);
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
				date_application_status_set
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
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param DB_IStatement_1 $st
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected function fetchInto(DB_IConnection_1 $db, DB_IStatement_1 $st, OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		// get a map of status IDs => names
		$status_id_map = $this->getStatusMap($db);
		$company_id_map = $this->getCompanyMap($db);

		while (($row = $st->fetch()) !== FALSE)
		{
			$status = $status_id_map[$row['application_status_id']];
			$company = $company_id_map[$row['company_id']];

			// if we're ignore expirable applications, and the
			// application is expirable, make note and move on
			if ($this->isExpirable($status))
			{
				$history->setExpirable($company, $row['application_id']);
			}
			// if we don't have a mapping for this status, skip it...
			// or if it's preactable and we're ignore preacts, also skip
			elseif (isset($this->status_map[$status])
				&& !$this->isPreactable($row['application_id'], $this->status_map[$status]))
			{
				$history->addLoan(
					$company,
					$this->status_map[$status],
					$row['application_id'],
					strtotime($row['date_application_status_set'])
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
	 * @param DB_IConnection_1 $db
	 * @return array
	 */
	protected function getCompanyMap(DB_IConnection_1 $db)
	{
		if (!isset($this->company_cache[$db]))
		{
			$query = "
				SELECT company_id, name_short
				FROM company
				WHERE active_status = 'active'
			";
			$st = $db->query($query);

			$map = array();

			while (($row = $st->fetch()) !== FALSE)
			{
				$map[$row['company_id']] = strtolower($row['name_short']);
				$map[strtolower($row['name_short'])] = $row['company_id'];
			}

			return $this->company_cache[$db] = $map;
		}

		return $this->company_cache[$db];
	}

	/**
	 * Caches (or fetches) a map of status IDs=>names
	 *
	 * @param DB_IConnection_1
	 * @return array
	 */
	protected function getStatusMap(DB_IConnection_1 $db)
	{
		// cache statuses with the database instance
		if (!isset($this->status_cache[$db]))
		{
			$query = "
				SELECT application_status_id,
					level5, level4, level3, level2, level1, level0
				FROM application_status_flat
			";
			$st = $db->query($query);

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

			return $this->status_cache[$db] = $map;
		}

		return $this->status_cache[$db];
	}

	/**
	 * Gets a connection to the company's database
	 *
	 * @param string $company
	 * @return DB_IConnection_1
	 */
	protected function getCompanyConnection($company)
	{
		$config = OLPBlackbox_Config::getInstance();

		// get a database connection
		$wrapped = Setup_DB::Get_Instance('mysql', $config->mode.'_READONLY', $company);
		return new DB_MySQLiAdapter_1($wrapped->getConnection());
	}
}

?>
