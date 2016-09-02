<?php
/**
 * @package DB.Models
 */

/**
 * Encapsulates a reference table
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Models_ReferenceTable_1 implements DB_Models_IReferenceTable_1, Iterator, ArrayAccess
{
	const KEY_IDS = 0;
	const KEY_NAMES = 1;

	/**
	 * Whether or not we've read the entire table
	 * @var bool
	 */
	protected $have_all = FALSE;

	/**
	 * Decides which value will be returned as the key
	 * One of the KEY_IDS/KEY_NAMES constants
	 * @var int
	 */
	protected $keys = self::KEY_IDS;

	/**
	 * Maps IDs to model instances
	 * @var array
	 */
	protected $id_map = array();

	/**
	 * Maps names to model instances; names are stored lowercase
	 * @var array
	 */
	protected $name_map = array();

	/**
	 * Empty copy of our model, because we'll need it
	 * @var DB_Models_WritableModel_1 also implements DB_Models_IReferenceModel_1
	 */
	protected $empty;

	/**
	 * Fetch a single row by name
	 * @var PDOStatement
	 */
	protected $st_byname;

	/**
	 * Fetch a single row by ID
	 * @var PDOStatement
	 */
	protected $st_byid;

	/**
	 * @var bool
	 */
	protected $valid = FALSE;

	/**
	 * @var array
	 */
	protected $where;

	/**
	 * @param DB_Models_WritableModel_1 $empty An empty model because of static-binding limitations must also implement DB_Models_IReferenceModel_1
	 * @param bool $prefetch Fetch and cache all records
	 * @param array $where
	 */
	public function __construct(DB_Models_WritableModel_1 $empty, $prefetch = FALSE, array $where = NULL)
	{
		if (!$empty instanceof DB_Models_IReferenceModel_1)
		{
			throw new InvalidArgumentException('First parameter passed to ' . __METHOD__ . 'must implement DB_Models_IReferenceModel_1');
		}

		$this->empty = $empty;
		$this->where = $where;

		if ($prefetch) $this->getAll();
	}

	/**
	 * Rewinds the internal iterator
	 * @return void
	 */
	public function rewind()
	{
		if (!$this->have_all) $this->getAll();

		$this->valid = (count($this->id_map) > 0);
		reset($this->id_map);
	}

	/**
	 * Advances the internal iterator
	 * @return mixed
	 */
	public function next()
	{
		$next = next($this->id_map);
		$this->valid = ($next !== FALSE);

		return $next;
	}

	/**
	 * Return the current item
	 * @return mixed
	 */
	public function current()
	{
		return current($this->id_map);
	}

	/**
	 * Advances the internal iterator
	 * @return bool
	 */
	public function valid()
	{
		return $this->valid;
	}

	/**
	 * Returns the key of the current item
	 * @return mixed
	 */
	public function key()
	{
		if (($cur = $this->current()) !== FALSE)
		{
			return ($this->keys === self::KEY_IDS)
				? $cur->{$this->getColumnID()}
				: $cur->{$this->getColumnName()};
		}

		return FALSE;
	}

	/**
	 * Overloaded access by ID/name
	 * @param mixed $index ID/name
	 * @return mixed
	 */
	public function __get($index)
	{
		$item = $this->getItem($index);

		if (empty($item ))
		{
			throw new Exception('Invalid ID: '.$index);
		}
		return $item;
	}

	/**
	 * Can't be used
	 * @throws Exception
	 * @return void
	 */
	public function __set($index, $value)
	{
		throw new Exception('The reference table cannot be modified');
	}

	/**
	 * @param mixed $index ID/Name
	 * @return bool
	 */
	public function __isset($index)
	{
		return ($this->getItem($index) !== NULL);
	}

	/**
	 * Prepares the instance for serialization
	 * @return void
	 */
	public function __sleep()
	{
		return array(
			'have_all',
			'keys',
			'id_map',
			'name_map',
			'empty',
			'valid',
			'where'
		);
	}

	/**
	 * ArrayAccess to items
	 * @param mixed $index ID/name
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		$item = $this->getItem($index);

		if (empty($item))
		{
			throw new Exception('Invalid ID: '.$index);
		}
		return $item;
	}

	/**
	 * Can't be used
	 * @throws exception
	 * @return void
	 */
	public function offsetSet($index, $value)
	{
		throw new Exception('The reference table cannot be modified');
	}

	/**
	 * Can't be used
	 * @throws exception
	 * @return void
	 */
	public function offsetUnset($index)
	{
		throw new Exception('The reference table cannot be modified');
	}

	/**
	 * ArrayAccess overloading for isset()
	 * @param mixed $index ID/name
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return ($this->getItem($index) !== NULL);
	}

	/**
	 * Returns the name for a given ID
	 * @param int $id
	 * @return string
	 */
	public function toName($id)
	{
		if (!isset($this->id_map[$id])
			&& ($this->have_all || $this->getById($id) === FALSE))
		{
			return FALSE;
		}
		return $this->id_map[$id]->{$this->getColumnName()};
	}

	/**
	 * Returns the id for a given name
	 * @param string $name
	 * @return int
	 */
	public function toId($name)
	{
		$name = strtolower($name);

		if (!isset($this->name_map[$name])
			&& ($this->have_all || $this->getByName($name) === FALSE))
		{
			return FALSE;
		}
		return $this->name_map[$name]->{$this->getColumnID()};
	}

	/**
	 * Returns the column that contains the ID of each item
	 * @return string
	 */
	public function getColumnID()
	{
		return $this->empty->getColumnID();
	}

	/**
	 * Returns the column that contains the name of each item
	 * @return string
	 */
	public function getColumnName()
	{
		return $this->empty->getColumnName();
	}

	/**
	 * Marks the table as "dirty" (not having all rows)
	 * Optionally, dumps all rows that we currently have
	 *
	 * @param bool $clear
	 */
	public function reset($clear = FALSE)
	{
		$this->have_all = FALSE;

		if ($clear)
		{
			$this->name_map = array();
			$this->id_map = array();
		}
	}

	/**
	 * Gets a copy of the reference model in use
	 *
	 * @return DB_Models_WritableModel_1 and implements DB_Models_IReferenceModel_1
	 */
	public function getNewModel()
	{
		return clone $this->empty;
	}

	/**
	 * Adds a model to the table
	 *
	 * @param DB_Models_WritableModel_1 $model Also implements DB_Models_IReferenceModel_1
	 */
	public function addModel(DB_Models_WritableModel_1 $model)
	{
		//The line below should verify that the $model also implements IReferenceModel
		if (!$model instanceof $this->empty)
		{
			throw new InvalidArgumentException();
		}
		elseif (!$model->isStored() || $model->isAltered())
		{
			throw new Exception('Model must be stored and unaltered');
		}

		// save it locally
		$this->name_map[strtolower($model->{$this->getColumnName()})] =
			$this->id_map[$model->{$this->getColumnID()}] = $model;
	}

	/**
	 * Internal convenience function for accessing an item by it's name/ID
	 * Used by __get and offsetGet
	 * @param mixed $index ID/name
	 * @return DB_Models_WritableModel_1 (also implements DB_Models_IReferenceModel_1) or NULL
	 */
	protected function getItem($index)
	{
		if (is_numeric($index))
		{
			if (!isset($this->id_map[$index])
				&& ($this->have_all || $this->getById($index) === FALSE))
			{
				return NULL;
			}
			return $this->id_map[$index];
		}
		else
		{
			$index = strtolower($index);

			if (!isset($this->name_map[$index])
				&& ($this->have_all || $this->getByName($index) === FALSE))
			{
				return NULL;
			}
			return $this->name_map[$index];
		}
	}

	/**
	 * Fetches all rows from the reference table into the cache
	 * @return void
	 */
	protected function getAll()
	{
		$db = $this->empty->getDatabaseInstance(DB_Models_WritableModel_1::DB_INST_READ);

		$query = "
			SELECT *
			FROM {$this->empty->getTableName()}
		";
		if ($this->where)
		{
			$query .= DB_Util_1::buildWhereClause($this->where, FALSE);
			$params = array_values($this->where);
		}
		else
		{
			$params = array();
		}

		$st = DB_Util_1::queryPrepared($db, $query, $params);

		foreach ($st as $row)
		{
			$this->fromRow($row);
		}

		$this->have_all = TRUE;
	}

	/**
	 * Fetches a single row from the table by ID
	 * @param int $id
	 * @return bool
	 */
	protected function getById($id)
	{
		if (!$this->st_byid)
		{
			$db = $this->empty->getDatabaseInstance(DB_Models_WritableModel_1::DB_INST_READ);

			$query = "
				SELECT *
				FROM {$this->empty->getTableName()}
			";
			if ($this->where)
			{
				$query .= DB_Util_1::buildWhereClause($this->where, FALSE).' AND ';
			}
			else
			{
				$query .= ' WHERE ';
			}

			$query .= $this->getColumnID().' = ?';
			$this->st_byid = $db->prepare($query);
		}

		$params = ($this->where)
		? array_values($this->where)
		: array();
		$params[] = $id;

		return $this->fromStatement($this->st_byid, $params);
	}

	/**
	 * Fetches a single row from the table by name
	 * @param string $name
	 * @return bool
	 */
	protected function getByName($name)
	{
		if (!$this->st_byname)
		{
			$db = $this->empty->getDatabaseInstance(DB_Models_WritableModel_1::DB_INST_READ);

			$query = "
			SELECT *
			FROM {$this->empty->getTableName()}
				";
			if ($this->where)
			{
				$query .= DB_Util_1::buildWhereClause($this->where, FALSE).' AND ';
			}
			else
			{
				$query .= ' WHERE ';
			}

			$query .= $this->getColumnName().' = ?';
			$this->st_byname = $db->prepare($query);
		}

		$params = ($this->where)
			? array_values($this->where)
			: array();
		$params[] = $name;

		return $this->fromStatement($this->st_byname, $params);
	}

	/**
	 * Convenience function to execute and instantiate a
	 * model from a single-row statement
	 * @param PDOStatement $st
	 * @param array $params Parameters passed to the execute call
	 * @return bool
	 */
	protected function fromStatement($st, array $params)
	{
		if ($st->execute($params) !== FALSE
			&& ($row = $st->fetch()) !== FALSE)
		{
			$this->fromRow($row);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Instantiates a DB_Models_ReferenceModel_1 from an associative
	 * array (such as returned from PDOStatement::fetch(PDO::FETCH_ASSOC))
	 * @param array $row
	 * @return void
	 */
	protected function fromRow($row)
	{
		$model = clone $this->empty;
		$model->fromDbRow($row);

		// save it locally
		$this->name_map[strtolower($model->{$this->getColumnName()})] =
			$this->id_map[$model->{$this->getColumnID()}] = $model;
	}

}

?>
