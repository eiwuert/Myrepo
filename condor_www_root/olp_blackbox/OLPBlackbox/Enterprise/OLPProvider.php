<?php

/**
 * A customer history provider that works off OLP
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_OLPProvider implements OLPBlackbox_Enterprise_ICustomerHistoryProvider
{
	const TYPE_CONFIRMED = 'CONFIRMED';

	const TYPE_PENDING = 'PENDING';

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
	 * @param DB_IConnection_1 $db
	 * @param array $company
	 */
	public function __construct(DB_IConnection_1 $db, array $company)
	{
		$this->db = $db;
		$this->company = $company;
	}

	/**
	 * Finds customer history by SSN
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $ssn
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromSSN(OLPBlackbox_Enterprise_CustomerHistory $history, $ssn)
	{
		// SSNs are stored encrypted
		//$ssn = $this->crypt->encrypt($ssn);

		$target_map = $this->getTargetMap();

		$query = "
			SELECT p.application_id,
				target_id,
				application_type
			FROM personal_encrypted p
				JOIN application a ON (a.application_id = p.application_id)
			WHERE p.social_security_number = ?
				AND target_id IN (".implode(',', array_keys($target_map)).")
		";
		$st = DB_Util_1::queryPrepared($this->db, $query, array($ssn));

		return $this->historyFromStatement($history, $st);
	}

	/**
	 * Finds customer history by email address
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $email
	 * @param string $dob
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromEmailDob(OLPBlackbox_Enterprise_CustomerHistory $history, $email, $dob)
	{
		return $history;
	}

	/**
	 * Finds customer history by bank account and SSN
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $aba
	 * @param string $account
	 * @param string $ssn
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromBankAccount(OLPBlackbox_Enterprise_CustomerHistory $history, $aba, $account, $ssn)
	{
		return $history;
	}

	/**
	 * Finds customer history by home phone and DOB
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $home_phone
	 * @param int $dob timestamp
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromPhoneDob(OLPBlackbox_Enterprise_CustomerHistory $history, $home_phone, $dob)
	{
		return $history;
	}

	/**
	 * Finds customer history by license number
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param string $license_num
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function fromLicense(OLPBlackbox_Enterprise_CustomerHistory $history, $license_num)
	{
		return $history;
	}

	/**
	 * Builds a customer history from a query result
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @param DB_IStatement_1 $st
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected function historyFromStatement(OLPBlackbox_Enterprise_CustomerHistory $history, DB_IStatement_1 $st)
	{
		$target_map = $this->getTargetMap();

		foreach ($st as $row)
		{
			$company = $target_map[$row['target_id']];

			$history->addLoan(
				$company,
				OLPBlackbox_Enterprise_CustomerHistory::STATUS_ACTIVE,
				$row['application_id']
			);

			// pending and confirmed apps can be expired
			if ($row['application_type'] === self::TYPE_CONFIRMED
				|| $row['application_type'] === self::TYPE_PENDING)
			{
				$history->setExpirable($company, $row['application_id']);
			}
		}

		return $history;
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

			foreach ($st as $row)
			{
				$this->target_id_map[$row['target_id']] = $target['property_short'];
			}
		}
		return $this->target_id_map;
	}
}

?>
