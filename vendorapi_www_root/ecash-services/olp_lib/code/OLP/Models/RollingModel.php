<?php

/**
 * Some tables roll every so often, so we'll need a common way to interact with
 * them.
 *
 * To use this class, you'll need to call setTableNames() with an array of
 * valid table names before calling setTableName() to set the current table
 * name.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_Models_RollingModel extends OLP_Models_WritableModel
{
	/**
	 * The current table that this model is using.
	 *
	 * @var string
	 */
	protected $current_table_name = NULL;
	
	/**
	 * An array of valid tables we can switch to.
	 *
	 * @var array
	 */
	protected $valid_table_names = NULL;
	
	/**
	 * Sets the current table name.
	 *
	 * @param string $table_name
	 * @return void
	 */
	public function setTableName($table_name)
	{
		if (!in_array($table_name, $this->getTableNames()))
		{
			throw new InvalidArgumentException(sprintf(
				"Table name '%s' is not in the list of valid tables for model %s.",
				$table_name,
				get_class($this)
			));
		}
		
		$this->current_table_name = $table_name;
	}
	
	/**
	 * The table name for this model.
	 *
	 * @NOTE If you overload this method, loadBy/loadByAll will break.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		if ($this->current_table_name === NULL)
		{
			throw new InvalidArgumentException(sprintf(
				"Attempting to get the table name for model %s without one being set.",
				get_class($this)
			));
		}
		
		return $this->current_table_name;
	}
	
	/**
	 * Sets the list of valid table names.
	 *
	 * @NOTE The order of this array is important when using the loadBys!!
	 *
	 * @param array $valid_table_names
	 * @return void
	 */
	public function setTableNames(array $valid_table_names)
	{
		if (count($valid_table_names) == 0)
		{
			throw new InvalidArgumentException(sprintf(
				"Attempted to set no table names for model '%s'.",
				get_class($this)
			));
		}
		
		$this->valid_table_names = $valid_table_names;
	}
	
	/**
	 * Gets the list of valid table names.
	 *
	 * @return array
	 */
	public function getTableNames()
	{
		if ($this->valid_table_names === NULL)
		{
			throw new InvalidArgumentException(sprintf(
				"Attempting to get a list of valid table names for model %s before the list is ever set.",
				get_class($this)
			));
		}
		
		return $this->valid_table_names;
	}
	
	/**
	 * Repeats the loadBy over all table names, if so desired.
	 *
	 * This function can change the current table name. When looping over
	 * all tables, it will switch the current table name until it finds a
	 * row. If it does not find one, resets the table name and returns FALSE.
	 *
	 * @param array $where_args
	 * @param bool $search_all_tables
	 * @return bool
	 */
	public function loadBy(array $where_args, $search_all_tables = TRUE)
	{
		if (!$search_all_tables)
		{
			$valid = parent::loadBy($where_args);
		}
		else
		{
			$old_table_name = $this->getTableName();
			$valid = FALSE;
			
			foreach ($this->getTableNames() AS $table_name)
			{
				$this->setTableName($table_name);
				
				$valid = parent::loadBy($where_args);
				
				if ($valid) break;
			}
			
			if (!$valid)
			{
				$this->setTableName($old_table_name);
			}
		}
		
		return $valid;
	}
	
	/**
	 * Finds all rows matching the given conditions.
	 *
	 * @param array $where_args
	 * @param bool $search_all_tables
	 * @return DB_Models_IterativeModel_1
	 */
	public function loadAllBy(array $where_args = array(), $search_all_tables = TRUE)
	{
		if (!$search_all_tables)
		{
			$result = parent::loadAllBy($where_args);
		}
		else
		{
			$old_table_name = $this->getTableName();
			$result = $this->factoryIterativeModelCollection();
			
			foreach ($this->getTableNames() AS $table_name)
			{
				$this->setTableName($table_name);
				
				$result->add(parent::loadAllBy($where_args));
			}
		}
		
		return $result;
	}
	
	/**
	 * Factories the default iterator iterative models.
	 *
	 * @param DB_IConnection_1 $db
	 * @return DB_Models_IterativeModel_1
	*/
	protected function factoryIterativeModelCollection(DB_IConnection_1 $db = NULL)
	{
		if (!$db) $db = $this->getDatabaseInstance();
		return new OLP_DB_IterativeModelCollection($db);
	}
}

?>
