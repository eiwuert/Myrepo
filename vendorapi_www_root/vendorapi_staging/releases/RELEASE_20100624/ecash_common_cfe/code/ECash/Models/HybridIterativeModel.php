<?php

/**
 * A Hybrid sort of Iterative Model that supports a newish Model that loads
 * from the Application Service, yet has DB access and will write to both.
 * It's really simple and just cursors through an array pointer.
 *
 * Currently this is only used with ECash_Models_ApplicationList which is
 * a list of ECash_Models_Application.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @package Ecash.Models
 */
abstract class ECash_Models_HybridIterativeModel implements Iterator, Countable
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	protected $order_by = array();

	protected $model_list = array();

	protected $cursor;

	protected $current;

	public function __construct(DB_IConnection_1 $db)
	{
		$this->cursor = FALSE;
		$this->db = $db;
	}

	/**
	 * Must be implemented in the child
	 *
	 * @param <array> $where_args
	 */
	public abstract function loadBy($where_args = array());

	/**
	 * Returns the number of items in the Iterator
	 * 
	 * @return <int>
	 */
	public function count()
	{
		return count($this->model_list);
	}

	/**
	 * Returns the current 'key'.
	 *
	 * @return int|false
	 */
	public function key()
	{
		return $this->cursor;
	}

	/**
	 * Returns the item that the cursor is currently pointing at.
	 *
	 * @return DB_Models_ModelBase
	 */
	public function current()
	{
		return $this->cursor === FALSE ? NULL : $this->createInstance($this->model_list[$this->cursor]);
	}

	/**
	 * Updates the cursor
	 *
	 * @return DB_Models_ModelBase
	 */
	public function next()
	{
		if (empty($this->model_list))
			throw new Exception("No models available!");

		++$this->cursor;
	}

	/**
	 * Resets the cursor
	 * @return void
	 */
	public function rewind()
	{
		if (! empty($this->model_list))
		{
			$this->cursor = 0;
		}
	}

	public function orderBy(array $order_by)
	{
		$this->order_by = $order_by;
	}

	/**
	 * Checks to see if we're at the end of the list
	 *
	 * @return bool
	 */
	public function valid()
	{
		return isset($this->model_list[$this->cursor]);
	}

	public function setDatabaseInstance(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}

	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return $this->db;
	}
}

?>
