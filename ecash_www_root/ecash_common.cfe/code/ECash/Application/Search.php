<?php

/**
 * Manages an interface for a central application search and result.
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package Application
 */
class ECash_Application_Search
{
	/**
	 * @var array
	 */
	protected $criteria = array();

	/**
	 * @var array
	 */
	protected $criteria_processed = array();

	/**
	 * @var ECash_DB_QueryBuilder
	 */
	protected $query_builder;

	/**
	 * @var string
	 */
	protected $select_fields = "
		a.application_id,
		c.company_id,
		c.name_short as company_short,
		upper(c.name_short) as display_short,
		c.name_short AS company_short,
		lt.abbreviation AS loan_type_abbreviation,
		IFNULL((
			SELECT SUM(ea.amount)
			FROM event_amount ea
				JOIN event_amount_type AS eat USING (event_amount_type_id)
				JOIN transaction_register tr USING (transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE ea.application_id = a.application_id
				AND eat.name_short != 'irrecoverable'
				AND tt.name_short NOT IN ('refund_3rd_party_fees', 'refund_3rd_party_princ')
				AND (
					transaction_status = 'complete'
					OR (
						transaction_status = 'pending'
						AND tt.name_short = 'loan_disbursement'
					)
				)
			), 0.00
		) AS application_balance,
		(
			EXISTS (SELECT 1
				FROM do_not_loan_flag AS dnlf
				WHERE a.ssn = dnlf.ssn
				AND dnlf.company_id = a.company_id
				AND dnlf.active_status = 'active')
			OR
			(EXISTS (SELECT 1
				FROM do_not_loan_flag AS dnlf
				WHERE a.ssn = dnlf.ssn
				AND dnlf.company_id != a.company_id
				AND dnlf.active_status = 'active')
			AND NOT EXISTS (SELECT 1
					FROM do_not_loan_flag_override AS dnlo
					WHERE a.company_id = dnlo.company_id
					AND a.ssn = dnlo.ssn)
			)
		) AS dnl
	";

	/**
	 * Constructs the database connection or attempts to use the default connection.
	 *
	 * @param DB_IConnection_1|NULL
	 */
	public function __construct(DB_IConnection_1 $db=NULL)
	{
		$this->query_builder = new ECash_DB_QueryBuilder;
		$this->query_builder
			->addSelect($this->select_fields)
			->setFrom('application_id_list AS a')
			->addJoin('company AS c', 'c.company_id = a.company_id')
			->addJoin('loan_type AS lt', 'lt.loan_type_id = a.loan_type_id')
			->addGroupBy('a.application_id')
			->setOrderBy('a.date_created DESC');

		if (!$db)
		{
			$db = ECash::getMasterDb();
		}

		$this->setDb($db);
	}

	public function __clone()
	{
		$this->criteria_processed = array();
		$this->__construct($this->db);
	}

	/**
	 * Sets the database instance to run against.
	 *
	 * @param DB_IConnection_1 $db
	 */
	public function setDb($db)
	{
		$this->db = $db;
		$this->query_builder->setDb($db);
	}

	/**
	 * Add a new criteria match to the list for inclusion in search query.
	 *
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 */
	public function addCriteria($field, $operator, $value)
	{
		$criteria_class = ECash::getFactory()->getClassString('Application_SearchCriteria');
		$this->criteria[] = new $criteria_class($this->query_builder, $field, $operator, $value);
	}

	/**
	 * Call criteria to add parameters needed for query.
	 */
	public function processCriteria()
	{
		foreach ($this->criteria as $key => $criteria)
		{
			if (!empty($this->criteria_processed[$key]))
			{
				continue;
			}

			$error = '';
			if (!$criteria->isValid($error))
			{
				throw new UnexpectedValueException($error);
			}

			$criteria->process($this->query_builder);

			$this->criteria_processed[$key] = TRUE;
		}
	}

	/**
	 * @return ECash_DB_QueryBuilder
	 */
	public function getQueryBuilder()
	{
		return $this->query_builder;
	}

	/**
	 * Return the results of the criteria input.
	 *
	 * @return array
	 */
	public function getResults()
	{
		$this->processCriteria();
		return $this->query_builder->getResults();
	}
}

?>
