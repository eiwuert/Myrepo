<?php

/**
 * View model to get information directly related to a target.
 * Includes company info and blackbox_type
 *
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class Blackbox_Models_View_TargetCompany extends Blackbox_Models_View_Base implements Blackbox_Models_IReadableTarget
{
	const TYPE_CAMPAIGN = 'CAMPAIGN';
	const TYPE_COLLECTION = 'COLLECTION';
	const TYPE_TARGET = 'TARGET';
	const TYPE_RULE = 'RULE';
	
	/**
	 * Builds the base query
	 *
	 * @return string
	 */
	protected function getBaseQuery()
	{
		return "
			SELECT {$this->getQueryFields()}
			FROM {$this->getQueryTables()}";
	}
	
	/**
	 * Gets the fields for the query
	 *
	 * @return string
	 */
	protected function getQueryFields()
	{
		return "
			t.target_id,
			t.property_short,
			t.name,
			t.lender_id,
			t.active,
			t.deleted,
			t.date_modified,
			t.date_created,
			t.date_effective,
			t.list_mgmt_nosell,
			t.lead_cost,
			t.paydate_minimum,
			t.reference_data,
			t.blackbox_type_id,
			c.company_id,
			c.name AS company_name,
			c.contact_name,
			c.email_address,
			c.phone_number,
			bt.name AS type";
	}
	
	/**
	 * Gets the tables and the joins for the query
	 *
	 * @return string
	 */
	protected function getQueryTables()
	{
		return "
			target AS t
			INNER JOIN company AS c
				ON t.company_id = c.company_id
			INNER JOIN blackbox_type bt
				ON t.blackbox_type_id = bt.blackbox_type_id";
	}
	
	/**
	 * Builds the where clause from a list of paramaters
	 *
	 * @param array $where_args
	 * @return string
	 */
	protected function getQueryWhere(array $where_args = array())
	{
		$columns = $this->getColumns();
		$column_map = $this->getColumnNameMap();
		
		foreach ($where_args as $arg => $value)
		{
			if (in_array($arg, $columns)
				&& !empty($value))
			{
				if (is_array($value))
				{
					$wheres[] = "{$this->resolveColumnName($arg)} IN (".str_repeat('?, ', (count($args)-1))."?) ";
				}
				else 
				{
					$wheres[] = "{$this->resolveColumnName($arg)} = ? ";
				}
			}
		}
		return implode(' AND ', $wheres);
	}
	
	/**
	 * Builds a single level list of parameters to be used with the query
	 *
	 * @param array $where_args
	 * @return array
	 */
	protected function buildParams(array $where_args = array())
	{
		$params = array();
		foreach ($where_args as $arg)
		{
			if (is_array($param))
			{
				$params = array_merge($params, array_values($arg));
			}
			else
			{
				$params[] = $arg;
			}
		}
		return $params;
	}
	
	/**
	 * Returns a map of the column names to their related field
	 *
	 * @return array
	 */
	protected function getColumnNameMap()
	{
		return array(
			'target_id'=> 't.target_id',
			'property_short' => 't.property_short',
			'name' => 't.name',
			'lender_id' => 't.lender_id',
			'active' => 't.active',
			'deleted' => 't.deleted',
			'date_modified' => 't.date_modified',
			'date_created' => 't.date_created',
			'date_effective' => 't.date_effective',
			'list_mgmt_nosell' => 't.list_mgmt_nosell',
			'lead_cost' => 't.lead_cost',
			'paydate_minimum' => 't.paydate_minimum',
			'reference_data' => 't.reference_data',
			'blackbox_type_id' => 't.blackbox_type_id',
			'company_id' => 'c.company_id',
			'company_name' => 'c.name',
			'contact_name' => 'c.contact_name',
			'email_address' => 'c.email_address',
			'phone_number' => 'c.phone_number',
			'type' => 'bt.name',
		);
	}
	
	/**
	 * Resolves the column name to their related field in the query
	 *
	 * @param string $name column name to resolve
	 * @return string field in query for the column name
	 */
	protected function resolveColumnName($name)
	{
		$column_map = $this->getColumnNameMap();
		if (!array_key_exists($name, $column_map))
		{
			throw new InvalidArgumentException('Column '.$name.' does not exist');
		}
		return $column_map[$name];
	}
	
	/**
	 * Gets the gets the target information.
	 *
	 * @param array $where_args list of where arguments in 'column' => 'value' or 'column' => array('value', ...) format
	 * @param string $order_by column to order by
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getTargets(array $where_args = array(), $order_by = 'property_short')
	{
		$db = $this->getDatabaseInstance();
		$params = $this->buildParams($where_args);
		
		$query = $this->getBaseQuery();
		if (!empty($params))
		{
			$query .= "
				WHERE
					{$this->getQueryWhere($where_args)}";
		}
		$query .= "
			ORDER BY {$this->resolveColumnName($order_by)}";
		
		$st = DB_Util_1::queryPrepared($db, $query, $params);
		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}
	
	/**
	 * @return array
	 * @see Blackbox_Models_View_Base::getColumns()
	 */
	public function getColumns()
	{
		return array_keys($this->getColumnNameMap());
	}
	
	/**
	 * @return string
	 * @see Blackbox_Models_View_Base::getTableName()
	 */
	public function getTableName()
	{
		return 'target';
	}
	
	/**
	 * Return the name of the auto increment column in this 'table.'
	 *
	 * @return string column
	 */
	public function getAutoIncrement()
	{
		return 'target_id';
	}
}
