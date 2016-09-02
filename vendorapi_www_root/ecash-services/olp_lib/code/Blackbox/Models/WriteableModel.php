<?php
/**
 * Blackbox Writable model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class Blackbox_Models_WriteableModel extends DB_Models_WritableModel_1 implements OLP_IModel
{
	/**
	 * Quote a string using PDO.
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		return $this->db->quote($string);
	}
	
	/**
	 * Instance of the Blackbox_Models_DatabaseInstanceHandler class
	 *
	 * @var Blackbox_Models_DatabaseInstanceHandler
	 */
	private $db_instance_handler = NULL;
	
	/**
	 * Construct a new blackbox writable model.
	 * @param DB_IConnection_1 $db The database connection.
	 */
	public function __construct(DB_IConnection_1 $db = NULL)
	{
		if (!$db instanceof DB_IConnection_1)
		{
			throw new InvalidArgumentException(
				'DB_IConnection_1 required to create '.get_class($this)
			);
		}
		parent::__construct($db);
	}
	
	/**
	 * Returns the auto_increment column name for the table this models.
	 *
	 * @return string table name
	 */
	public function getAutoIncrement()
	{
		return $this->getTableName() . '_id';
	}
	
	/**
	 * Returns an array representing the columns in the primary key of the table
	 * that this model represents.
	 *
	 * @return array list of columns
	 */
	public function getPrimaryKey() 
	{
		return array($this->getAutoIncrement());
	}
	
	
	/**
	 * Finds all rows matching the given conditions and orders them by the given columns
	 *
	 * @param array $where_args
	 * @param array $order_by
	 * @return DB_Models_IterativeModel_1
	 */
	public function loadAllBy(array $where_args = NULL, array $order_by = NULL)
	{
		$query = "
			SELECT *
			FROM ".$this->getTableName()."
			".$this->makeWhere($where_args);
		
		if (!empty($order_by))
		{
			$query .= " ORDER BY " . implode(',', $order_by);
		}
		
		$db = $this->getDatabaseInstance();
		
		$st = DB_Util_1::queryPrepared(
			$db,
			$query,
			$this->flattenWhere($where_args)
		);

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}
	
	protected function flattenWhere($where_args)
	{
		$new = array();
		foreach (array_keys($where_args) as $key)
		{
			if (is_array($where_args[$key]))
			{
				$new = array_merge($new, array_values($where_args[$key]));
			}
			else 
			{
				$new[] = $where_args[$key];
			}
		}
		
		return $new;
	}
	
	protected function makeWhere($where_args)
	{
		$append_args = array();
		
		foreach (array_keys($where_args) as $key)
		{
			if (is_array($where_args[$key])) 
			{
				$append_args[] = " $key IN (".implode(', ', array_fill(0, count($where_args[$key]), '?')).") ";
			}
			else
			{
				$append_args[] = " $key = ? ";
			}
		}

		return count($append_args) ? ' WHERE ' . implode(' AND ', $append_args) : ''; 
	}
	
	protected function makeArraySqlSafe(array $values)
	{
		$processed = array();
		
		foreach (array_keys($values) as $key)
		{
			$values[$key] = (is_numeric($values[$key]) ? $values[$key] : $this->quote($values[$key]));
		}
		
		return $processed;
	}
	
	/**
	 * If inserting, add the date_created field automatically if does not exist.
	 * This is here for tables that have date_created and date_modified, while the
	 * database only auto-populates date_modified.
	 *
	 * @return void
	 */
	public function insert()
	{
		if (in_array('date_created', $this->getColumns()) 
			&& in_array('date_modified', $this->getColumns()) 
			&& !isset($this->date_created))
		{
			$this->date_created = time();
		}
		
		parent::insert();
	}

	/**
	 * Finds all rows matching the key arguments provided
	 *
	 * @param array $keysarray a 0-indexed array of primary key values
	 * @return DB_Models_IterativeModel_1
	 *
	 */
	public function loadMultiplePrimaryKeys(array $keysarray = NULL)
	{
		$primary_keys = $this->getPrimaryKey();
		if (count($primary_keys) <> 1)
		{
			throw new Exception("Table must have a single primary key.");
		}

		$query = "
			SELECT *
			FROM " . $this->getTableName() . "
			WHERE " . array_pop($primary_keys) . " IN (" . implode(', ', array_fill(0, count($keysarray), '?')) . ")";


		$db = $this->getDatabaseInstance();
		$st = DB_Util_1::queryPrepared(
			$db,
			$query,
			$keysarray
		);

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}

	/**
	 * Please pay no attention to the man behind the green curtain
	 *
	 * @param string $querystring a raw query
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function loadBySpecificQuery($querystring)
	{
		$db = $this->getDatabaseInstance();
		$st = DB_Util_1::queryPrepared(
			$db,
			$querystring,
			array()
		);

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}

}
