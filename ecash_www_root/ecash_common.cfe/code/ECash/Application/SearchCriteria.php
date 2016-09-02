<?php

/**
 * Contains a list of criteria an application can be searched on.
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package Application
 */
class ECash_Application_SearchCriteria
{
	/**
	 * @var ECash_DB_QueryBuilder
	 */
	protected $query_builder;

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * @var string
	 */
	protected $operator;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * Modified by criteria that handle adding the value themselves.
	 * @var boolean
	 */
	protected $add_value = TRUE;

	/**
	 * Construct the criteria parameters and link back to search.
	 *
	 * @param ECash_DB_QueryBuilder $query_builder
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 */
	public function __construct(ECash_DB_QueryBuilder $query_builder, $field, $operator, $value)
	{
		$this->query_builder = $query_builder;

		$this->field = $field;
		$this->operator = $operator;
		$this->value = $value;
	}

	/**
	 * Check if the criteria options are a valid combination.
	 *
	 * @return boolean
	 */
	public function isValid(&$error)
	{
		if (is_string($this->value) && strlen($this->value) == 0)
		{
			$error = DisplayMessage::get(array('field', 'need primary criterion'));
			return FALSE;
		}

		switch ($this->field)
		{
			case 'email':
				if ($this->operator == 'contains')
				{
					$error = 'Cannot use "contains" with Email search.';
					return FALSE;
				}
				break;

			case 'ssn_last4':
				// The user must think this is limited to 4 characters,
				// but we'll change it to starts_with in processValue.
				if ($this->operator != 'is')
				{
					$error = 'Must use "is" with Last 4 SSN search.';
					return FALSE;
				}

				if (strlen($this->value) != 4)
				{
					$error = 'Incorrect value for Last 4 SSN.';
					return FALSE;
				}
				break;

			case 'customer_id':
			case 'application_id':
				$this->value = preg_replace('#\D#', '', $this->value);

				if ($this->field == 'application_id' && !is_array($this->value) 
					&& (!IsIntegerValue($this->value) || $this->value <= 0))
				{
					if ($this->field == 'customer_id')
					{
						$error = 'Incorrect format for Customer Id.';
					}
					else
					{
						$error = 'Incorrect format for Application Id.';
					}
					return FALSE;
				}
				break;
		}

		return TRUE;
	}

	/**
	 * Process the criteria, adding any options needed to the search.
	 *
	 * @param ECash_DB_QueryBuilder $query_builder
	 */
	public function process(ECash_DB_QueryBuilder $query_builder)
	{
		$this->query_builder = $query_builder;

		$this->processValue();
		$this->processField();

		if ($this->add_value)
		{
			$this->addValue();
		}
	}

	/**
	 * Process the field information.
	 */
	public function processField()
	{
		switch ($this->field)
		{
			case 'application_id':
				$this->addWhere('a.application_id %s');
				break;

			case 'ach_id':
				$this->addJoin('ach', 'ach.application_id = a.application_id');
				$this->addWhere('ach.ach_id %s');
				break;

			case 'card_process_id':
				$this->addJoin('card_process', 'card_process.application_id = a.application_id');
				$this->addWhere('card_process.card_process_id %s');
				break;
						
			/** @TODO: Unsure if we need this **/
			case 'company':
				$this->addWhere('company.company_id %s');
				break;

			case 'balance_accounts':
				$this->query_builder->addHaving('application_balance != 0');
				$this->query_builder->setOrderBy(array(
					'a.ssn ASC',
					'a.date_created DESC',
				));
				break;

			default:
				throw new Exception('Unknown field criteria');
		}
	}

	/**
	 * Process the value information.
	 */
	protected function processValue()
	{
		switch ($this->field)
		{
			case 'balance_accounts':
				$this->add_value = FALSE;
				return;

			case 'email':
			case 'ach_id':
			case 'ecld_id':
				$this->value = strtolower($this->value);
				break;

			case 'phone':
				$this->add_value = FALSE;
				$this->value = preg_replace('#\D#', '', $this->value);
				break;

				case 'ssn_last4':
				$this->operator = 'starts_with';
				$this->value = strrev($this->value);
				break;
		}
	}

	/**
	 * Returns the SQL version of the operator requested. Modifies value as necessary.
	 *
	 * @return string
	 */
	protected function getOperator()
	{
		switch ($this->operator)
		{
			case 'is':
				return '= ?';

			case 'is_not':
				return '!= ?';

			case 'starts_with':
				$this->value .= '%';
				return 'LIKE ?';

			case 'ends_with':
				$this->value = '%' . $this->value;
				return 'LIKE ?';

			case 'contains':
				$this->value = '%' . $this->value . '%';
				return 'LIKE ?';
				
			case 'in':
				return 'IN (' . implode(', ', array_fill(0, count($this->value), '?')) . ')';

			default:
				throw new Exception('Unknown operator.');
		}
	}

	/**
	 * Proxy to query builder addJoin.
	 *
	 * @see ECash_DB_QueryBuilder->addJoin
	 */
	protected function addJoin($table, $on, $type=NULL)
	{
		$this->query_builder->addJoin($table, $on, $type);
	}

	/**
	 * Parses where for value strings and adds operator.
	 *
	 * @param string $where
	 */
	protected function addWhere($where)
	{
		$this->query_builder->addWhere(vsprintf($where, $this->getOperator()));
	}

	/**
	 * Adds criteria value to query builder where clause.
	 */
	protected function addValue()
	{
		if (is_array($this->value))
		{
			foreach ($this->value as $value)
			{
				$this->query_builder->addValue($value);
			}
		}
		else
		{
			$this->query_builder->addValue($this->value);
		}
	}
}

?>
