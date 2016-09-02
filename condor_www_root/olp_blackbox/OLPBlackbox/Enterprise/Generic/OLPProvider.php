<?php

/**
 * A customer history provider that works off OLP
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_OLPProvider implements OLPBlackbox_Enterprise_ICustomerHistoryProvider
{
	const TYPE_CONFIRMED = 'CONFIRMED';
	const TYPE_PENDING = 'PENDING';
	const TYPE_AGREED = 'AGREED';

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $company;

	/**
	 * Map of company ID => name
	 *
	 * @var array
	 */
	protected $target_id_map = NULL;

	/**
	 * @var bool
	 */
	protected $ignore_expirable = FALSE;

	/**
	 * @var int
	 */
	protected $exclude;

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * @param DB_IConnection_1 $db
	 * @param array $company
	 * @param bool $expirable
	 */
	public function __construct(DB_IConnection_1 $db, array $company, $expirable = FALSE)
	{
		$this->db = $db;
		$this->company = $company;
		$this->ignore_expirable = $expirable;
	}

	/**
	 * Sets the company that we're fetching results for
	 * @param array $company
	 * @return void
	 */
	public function setCompany($company)
	{
		$this->company = array($company);
		$this->target_id_map = NULL;
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
		$target_map = $this->getTargetMap();

		$this->buildQuery($match, $target_map);

		$st = DB_Util_1::queryPrepared(
			$this->db,
			$this->query,
			$this->params
		);
		return $this->fetchInto($st, $history);
	}

	/**
	 * Builds the query for the given conditions
	 *
	 * A special exception is made for bank_account, as
	 * multiple permutations are allowed and built as an IN.
	 * Sets $this->query and $this->params.
	 *
	 * @param array $match
	 * @param array $target_map
	 * @return void
	 */
	protected function buildQuery(array $match, array $target_map)
	{
		// only search back an hour...
		// change to use two different date thresholds. - GForge #8774 [DW]
		// - $threshold_pending is last hour for pending apps,
		// - $threshold_disagreed is last 24 hours for disagreed apps
		$threshold_pending = date('Y-m-d H:i:s', strtotime('-1 hour', Blackbox_Utils::getToday()));
		$threshold_disagreed = date('Y-m-d H:i:s', strtotime('-24 hour', Blackbox_Utils::getToday()));

		// still requires forcing an index...
		if (isset($match['social_security_number'])) $index = 'idx_ssn';
		elseif (isset($match['email_primary'])) $index = 'idx_email';
		else $index = NULL;

		// date must be sent as a param to avoid the
		// colons... which are used for named params
		// Add 24 hour threshold to params - GForge #8774 [DW]
		$this->params = array_values($match);
		$this->params[] = $threshold_pending;
		$this->params[] = $threshold_disagreed;

		// Modify query to also search for disagreed and confirmed_disagreed apps within 24 hours - GForge #8774 [DW]
		$this->query = "
			SELECT
				p.application_id,
				target_id,
				application_type
			FROM
				personal_encrypted p ".($index ? "USE INDEX ({$index})" : '')."
				JOIN application a USE INDEX (PRIMARY) ON (a.application_id = p.application_id)
			WHERE
				target_id IN (".implode(',', array_keys($target_map)).")
				AND ".implode(' = ? AND ', array_keys($match))." = ?
				AND ((application_type IN ('ACTIVE', 'PENDING', 'CONFIRMED')
						AND a.created_date > ?)
					OR (application_type IN ('DISAGREED', 'CONFIRMED_DISAGREED')
						AND a.created_date > ?))
		";
		if ($this->exclude)
		{
			$this->query .= ' AND a.application_id != '.$this->exclude;
		}
	}

	/**
	 * Builds a customer history from a query result
	 *
	 * @param DB_IStatement_1 $st
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return void
	 */
	protected function fetchInto(DB_IStatement_1 $st, OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		$target_map = $this->getTargetMap();

		while (($row = $st->fetch(PDO::FETCH_ASSOC)) !== FALSE)
		{
			$company = $target_map[$row['target_id']];

			// pending and confirmed apps can be expired
			if ($this->isExpirable($row['application_type']))
			{
				$history->setExpirable($company, $row['application_id']);
			}
			// expirable loans do not go in history
			// If flagged as pending, send pending status, otherwise, send actual status.
			// Done to allow to check for disagreed apps - GForge #8774 [D
			elseif ($this->isPending($row['application_type']))
			{
				$history->addLoan(
					$company,
					OLPBlackbox_Enterprise_CustomerHistory::STATUS_PENDING,
					$row['application_id']
				);
			}
			else
			{
				$history->addLoan(
					$company,
					strtolower($row['application_type']),
					$row['application_id']
				);
			}
		}
	}

	/**
	 * Fetches a map of target IDs => names
	 *
	 * @return array
	 */
	protected function getTargetMap()
	{
		if (!$this->target_id_map)
		{
			$query = "
				SELECT target_id, property_short
				FROM target
				WHERE property_short IN ('".implode("', '", $this->company)."')
			";
			$st = $this->db->query($query);

			$this->target_id_map = array();

			while (($row = $st->fetch(PDO::FETCH_ASSOC)) !== FALSE)
			{
				$this->target_id_map[$row['target_id']] = strtolower($row['property_short']);
			}
		}
		return $this->target_id_map;
	}

	/**
	 * Indicates whether the application should be flagged for expiration
	 *
	 * @param string $type
	 * @return bool
	 */
	protected function isExpirable($type)
	{
		return ($this->ignore_expirable
			&& ($type === self::TYPE_CONFIRMED
				|| $type === self::TYPE_PENDING));
	}

	/**
	 * Indicates whether the application is flagged as pending
	 *
	 * @param string $type
	 * @return bool
	 */
	protected function isPending($type)
	{
		return ($type === self::TYPE_CONFIRMED
				|| $type === self::TYPE_PENDING
				|| $type === self::TYPE_AGREED);
	}
}

?>