<?php

/**
 * Builds a query based on various independent inputs.
 *
 * This is a copy from ECash AMG.
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package DB
 */
class DB_QueryBuilder
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $parts = array();

	/**
	 * @var array
	 */
	protected $parts_init = array(
		'select' => array(),
		'join' => array(),
		'where' => array(),
		'value' => array(),
		'group_by' => array(),
		'having' => array(),
		'order_by' => array(),
		'limit' => NULL,
	);

	/**
	 * Constructs the database connection or attempts to use the default connection.
	 *
	 * @param DB_IConnection_1|NULL
	 */
	public function __construct($db=NULL)
	{
		$this->parts = $this->parts_init;

		if (!$db)
		{
			$db = ECash_Config::getMasterDbConnection();
		}

		$this->setDb($db);
	}

	/**
	 * Sets the database instance to run against.
	 *
	 * @param DB_IConnection_1 $db
	 */
	public function setDb($db)
	{
		$this->db = $db;
	}

	/**
	 * Build the SQL query required.
	 */
	protected function buildQuery()
	{
		$query = 'SELECT ' . $this->getSelect() . "\n";

		$query .= 'FROM ' . $this->getFrom() . "\n";

		if ($part = $this->getJoin())
		{
			$query .= $part . "\n";
		}

		if ($part = $this->getWhere())
		{
			$query .= 'WHERE ' . $part . "\n";
		}

		if ($part = $this->getGroupBy())
		{
			$query .= 'GROUP BY ' . $part . "\n";
		}

		if ($part = $this->getHaving())
		{
			$query .= 'HAVING ' . $part . "\n";
		}

		if ($part = $this->getOrderBy())
		{
			$query .= 'ORDER BY ' . $part . "\n";
		}

		if ($part = $this->getLimit())
		{
			$query .= 'LIMIT ' . $part . "\n";
		}

		return $query;
	}

	/**
	 * Return the query based on the parameters input.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->buildQuery();
	}

	/**
	 * Returns all the values added in order of part occurrence.
	 *
	 * @return array
	 */
	public function getValues()
	{
		$values = array();

		foreach ($this->parts_init AS $part => $empty)
		{
			if (!empty($this->parts['value'][$part]))
			{
				$values = array_merge($values, $this->parts['value'][$part]);
			}
		}

		return $values;
	}

	/**
	 * Return the results of the criteria input.
	 *
	 * @return array
	 */
	public function getResults()
	{
		$query = $this->buildQuery();
		$values = $this->getValues();

		if (empty($values))
		{
			$result = $this->db->query($query);
		}
		else
		{
			$result = $this->db->prepare($query);
			$result->execute($values);
		}

		return $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
	}

	/**
	 * Return the SELECT part of the query.
	 *
	 * @return string
	 */
	protected function getSelect()
	{
		if (empty($this->parts['select']))
		{
			return '*';
		}
		else
		{
			return "\n\t" . implode(', ' . "\t\n", $this->parts['select']);
		}
	}

	/**
	 * Returns the FROM part of the search query.
	 *
	 * Throws an exception if FROM was not set.
	 *
	 * @return string
	 */
	protected function getFrom()
	{
		if (empty($this->parts['from']))
		{
			throw new Exception('Invalid query part: from must be specified');
		}

		return $this->parts['from'];
	}

	/**
	 * Return the JOIN part of the search query.
	 *
	 * @return string
	 */
	protected function getJoin()
	{
		$sql = '';

		foreach ($this->parts['join'] as $table => $join)
		{
			$sql .= "\t";

			if ($join['type'])
			{
				$sql .= $join['type'] . ' ';
			}

			$sql .= 'JOIN ' . $table . ' ON (' . implode("\n", $join['on']) . ')' . "\n";
		}

		return $sql;
	}

	/**
	 * Return the WHERE part of the search query.
	 *
	 * @return string
	 */
	protected function getWhere()
	{
		return implode("\n\tAND ", $this->parts['where']);
	}

	/**
	 * Return the GROUP BY part of the search query.
	 *
	 * @return string
	 */
	protected function getGroupBy()
	{
		return implode(', ', $this->parts['group_by']);
	}

	/**
	 * Return the HAVING part of the search query.
	 *
	 * @return string
	 */
	protected function getHaving()
	{
		return implode("\n\tAND ", $this->parts['having']);
	}

	/**
	 * Return the ORDER BY part of the search query.
	 *
	 * @return string
	 */
	protected function getOrderBy()
	{
		return implode(', ', $this->parts['order_by']);
	}

	/**
	 * Return the LIMIT part of the search query.
	 *
	 * @return integer|NULL
	 */
	protected function getLimit()
	{
		return $this->parts['limit'];
	}

	/**
	 * Adds a part of the query to return.
	 *
	 * @param string $select
	 * @return ECash_DB_QueryBuilder
	 */
	public function addSelect($select)
	{
		$this->parts['select'][] = $select;

		return $this;
	}

	/**
	 * Sets which table is in the initial 'from' statement.
	 *
	 * @param string $table
	 * @return ECash_DB_QueryBuilder
	 */
	public function setFrom($table)
	{
		$this->parts['from'] = $table;

		return $this;
	}

	/**
	 * Attempt to add a specific table join to the list. Merges and only uses unique 'on' clauses.
	 *
	 * If you want to join to the same table twice then specify the second with a different AS value.
	 *
	 * @param string $table
	 * @param string|array $on
	 * @param string|NULL $type
	 * @return ECash_DB_QueryBuilder
	 */
	public function addJoin($table, $on, $type=NULL)
	{
		if (empty($this->parts['join'][$table]))
		{
			$this->parts['join'][$table] = array(
				'on' => (array)$on,
				'type' => $type,
			);
		}
		else
		{
			$this->parts['join'][$table]['on'] = array_unique($this->parts['join'][$table]['on'] + (array)$on);
		}

		return $this;
	}

	/**
	 * Adds a where clause to the list used in search.
	 *
	 * @param string $where
	 * @return ECash_DB_QueryBuilder
	 */
	public function addWhere($where)
	{
		$this->parts['where'][] = $where;

		return $this;
	}

	/**
	 * Adds a value to the list used in value binding.
	 *
	 * @param mixed $value
	 * @return ECash_DB_QueryBuilder
	 */
	public function addValue($value, $part='where')
	{
		if (empty($this->parts['value'][$part]))
		{
			$this->parts['value'][$part] = (array)$value;
		}
		else
		{
			$this->parts['value'][$part] = array_merge($this->parts['value'][$part], (array)$value);
		}

		return $this;
	}

	/**
	 * Adds a group by clause to the list used in the search.
	 *
	 * @param string $group_by
	 * @return ECash_DB_QueryBuilder
	 */
	public function addGroupBy($group_by)
	{
		$this->parts['group_by'][] = $group_by;

		return $this;
	}

	/**
	 * Adds a value to the list used in value binding.
	 *
	 * @param mixed $value
	 * @return ECash_DB_QueryBuilder
	 */
	public function addHaving($having)
	{
		$this->parts['having'][] = $having;

		return $this;
	}

	/**
	 * Sets order_by list.
	 *
	 * @param string|array $value
	 * @return ECash_DB_QueryBuilder
	 */
	public function setOrderBy($order_by)
	{
		$this->parts['order_by'] = (array)$order_by;

		return $this;
	}

	/**
	 * Reset a query part.
	 *
	 * @param string $part
	 * @return ECash_DB_QueryBuilder
	 */
	public function resetPart($part)
	{
		if (!isset($this->parts[$part]))
		{
			throw new Exception('Invalid part to reset');
		}

		$this->parts[$part] = $this->parts_init[$part];

		return $this;
	}

	/**
	 * Add limit to query results.
	 *
	 * @param integer|NULL $limit
	 * @return ECash_DB_QueryBuilder
	 */
	public function setLimit($limit)
	{
		$this->parts['limit'] = $limit;

		return $this;
	}
}

?>
