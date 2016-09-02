<?php

/**
 * Replace columns in the Model with referenced columns.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DB_Models_Decorator_ReferencedWritableModel_1 extends DB_Models_Decorator_WritableModel_1
{
	/**
	 * column_id =>
	 *  array(
	 *    model => DB_Models_ReferencedModel_1
	 *    column_id => string
	 *    column_name => string
	 *    writable => bool
	 *
	 * @var array
	 */
	protected $references = array();
	
	/**
	 * column_name => column_id
	 *
	 * @var array
	 */
	protected $reference_columns = array();
	
	/**
	 * @var array
	 */
	protected $columns = array();
	
	/**
	 * Attaches to a model and keeps a local copy of columns.
	 *
	 * @param DB_Models_WritableModel_1 $model
	 */
	public function __construct(DB_Models_WritableModel_1 $model)
	{
		parent::__construct($model);
		
		$this->columns = $model->getColumns();
	}
	
	/**
	 * Attach a reference table to this model.
	 *
	 * $reference_id and $reference_name are the local names of these columns
	 * in the decorated model. This allows you to rename them from a basic
	 * name (like 'name') to something more descriptive for the model, or to
	 * handle conflicts.
	 *
	 * @param DB_Models_IReferenceTable_1 $reference
	 * @param string $reference_id
	 * @param string $reference_name
	 * @param bool $writable
	 * @return void
	 */
	public function addReferenceTable(DB_Models_IReferenceTable_1 $reference, $reference_id = NULL, $reference_name = NULL, $writable = TRUE)
	{
		if (!$reference_id)
		{
			if ($reference instanceof DB_Models_IReferenceModel_1
				|| $reference instanceof DB_Models_ReferenceTable_1)
			{
				$reference_id = $reference->getColumnID();
			}
			else
			{
				throw new Exception(sprintf(
					"Reference table's reference_id could not be automatically determined. You must pass in the id to addReferenceTable()."
				));
			}
		}
		if (!$reference_name)
		{
			if ($reference instanceof DB_Models_IReferenceModel_1
				|| $reference instanceof DB_Models_ReferenceTable_1)
			{
				$reference_name = $reference->getColumnName();
			}
			else
			{
				throw new Exception(sprintf(
					"Reference table's reference_name could not be automatically determined. You must pass in the name to addReferenceTable()."
				));
			}
		}
		
		if (!in_array($reference_id, $this->columns))
		{
			throw new Exception(sprintf(
				"Reference table's column_id (%s) does not exist in referenced model (%s) or already referenced columns.",
				$reference_id,
				get_class($this->model)
			));
		}
		elseif (in_array($reference_name, $this->columns))
		{
			throw new Exception(sprintf(
				"Reference table's column_name (%s) already exist in reference model.",
				$reference_name
			));
		}
		elseif ($this->model->__isset($reference_id) && !$reference->__isset($this->model->__get($reference_id)))
		{
			throw new Exception(sprintf(
				"When attaching reference '%s' to model '%s', reference id '%s' does not exist in reference table.",
				get_class($reference),
				get_class($this->model),
				$reference->__get($this->model->__get($reference_id))
			));
		}
		
		$this->references[$reference_id] = array(
			'model' => $reference,
			'column_id' => $reference_id,
			'column_name' => $reference_name,
			'writable' => (bool)$writable,
		);
		$this->reference_columns[$reference_name] = $reference_id;
		$this->columns[] = $reference_name;
	}
	
	/**
	 * Returns the wrapped model.
	 *
	 * @return DB_Models_WritableModel_1
	 */
	public function getBaseModel()
	{
		return $this->model;
	}
	
	/**
	 * Magic setter.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (isset($this->references[$name]) && !$this->references[$name]['model']->__isset($value))
		{
			// Is this a variable controlled by a reference table? Verify that it exists
			throw new Exception(sprintf(
				"Attempting to set '%s' with the ID '%s' that does not exist in reference table '%s'.",
				$name,
				$value,
				get_class($this->references[$name])
			));
		}
		elseif (isset($this->reference_columns[$name]))
		{
			// If this value doesn't exist, create it
			$reference_id = $this->reference_columns[$name];
			$value_id = $this->references[$reference_id]['model']->toId($value);
			
			if ($value_id === FALSE)
			{
				if (!$this->references[$reference_id]['writable'])
				{
					throw new Exception(sprintf(
						"This model '%s' is not writable. Attempted to save a new value: %s",
						get_class($this->references[$reference_id]['model']),
						$value
					));
				}
				
				$value_id = $this->newReferenceModel($this->references[$reference_id]['model'], $value);
			}
			
			$this->model->__set($reference_id, $value_id);
		}
		else
		{
			$this->model->__set($name, $value);
		}
	}
	
	/**
	 * Magic getter!
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->reference_columns[$name]))
		{
			// Load the reference columns from reference table instead of model.
			return $this->references[$this->reference_columns[$name]]['model']->toName($this->__get($this->reference_columns[$name]));
		}
		else
		{
			return $this->model->__get($name);
		}
	}
	
	/**
	 * Magic issetter!
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		if (isset($this->reference_columns[$name]))
		{
			return $this->__isset($this->reference_columns[$name]);
		}
		else
		{
			return $this->model->__isset($name);
		}
	}
	
	/**
	 * Unsets a column value
	 *
	 * @param string $name
	 * @return void
	 */
	public function __unset($name)
	{
		if (isset($this->reference_columns[$name]))
		{
			$this->__unset($this->reference_columns[$name]);
		}
		else
		{
			$this->model->__unset($name);
		}
	}
	
	/**
	 * Returns all column data for this model.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = $this->model->getColumnData();
		
		foreach ($this->references AS $reference)
		{
			if ($reference['model']->__isset($column_data[$reference['column_id']]))
			{
				$column_data[$reference['column_name']] = $reference['model']->toName($column_data[$reference['column_id']]);
			}
			else
			{
				$column_data[$reference['column_name']] = NULL;
			}
		}
		
		return $column_data;
	}

	/**
	 * Sets the column data for this model.
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->model->setColumnData($this->convertReferenceData($data));
	}
	
	/** Create a new entry into a reference table and returns the new ID.
	 *
	 * @param DB_Models_ReferenceTable_1 $reference
	 * @param mixed $value
	 * @return int
	 */
	protected function newReferenceModel(DB_Models_ReferenceTable_1 $reference, $value)
	{
		$model = $reference->getNewModel();
		$model->__set($model->getColumnName(), $value);
		$model->save();
		
		$reference->addModel($model);
		
		return $model->__get($model->getColumnID());
	}
	
	/**
	 * Convert all referenced columns in the data to IDs for the model,
	 * creating any rows that do not already exist if allowed.
	 *
	 * @param array $data
	 * @param bool $create
	 * @return array
	 */
	protected function convertReferenceData(array $data, $create = TRUE)
	{
		foreach ($this->references AS $reference)
		{
			if (isset($data[$reference['column_id']]) && $reference['model']->toName($data[$reference['column_id']]) === FALSE)
			{
				throw new Exception(sprintf(
					"Attempting to set '%s' with the ID '%s' that does not exist in reference table '%s'",
					$reference['column_id'],
					$data[$reference['column_id']],
					get_class($reference)
				));
			}
			elseif (isset($data[$reference['column_name']]))
			{
				$data[$reference['column_id']] = $reference['model']->toId($data[$reference['column_name']]);
				
				if ($data[$reference['column_id']] === FALSE)
				{
					if ($create && $reference['writable'])
					{
						$data[$reference['column_id']] = $this->newReferenceModel($reference, $data[$reference['column_name']]);
					}
					else
					{
						// Do we want to return a failed array, or throw an exception?
						return NULL;
						
						throw new Exception(sprintf(
							"Attempting to convert referenced data for '%s' with the value '%s' which does not exist in the reference table '%s' while creating new reference data is turned off.",
							$reference['column_name'],
							$data[$reference['column_name']],
							get_class($reference)
						));
					}
				}
				
				unset($data[$reference['column_name']]);
			}
		}
		
		return $data;
	}
	
	/**
	 * Selects from the model's table based on the where args
	 *
	 * @param array $where_args
	 * @return bool
	 */
	public function loadBy(array $where_args)
	{
		$where_args = $this->convertReferenceData($where_args, FALSE);
		
		if ($where_args !== NULL)
		{
			return $this->model->loadBy($where_args);
		}
		
		return FALSE;
	}
	
	/**
	 * Finds all rows matching the given conditions
	 *
	 * @param array $where_args
	 * @return DB_Models_IterativeModel_1
	 */
	public function loadAllBy(array $where_args = array())
	{
		$where_args = $this->convertReferenceData($where_args, FALSE);
		
		if ($where_args !== NULL)
		{
			$query = "
				SELECT *
				FROM ".$this->model->getTableName()."
				".self::buildWhere($where_args)."
			";
			
			$db = $this->getDatabaseInstance();
			$st = DB_Util_1::queryPrepared(
				$db,
				$query,
				$where_args
			);
			
			return $this->factoryIterativeModel($st, $db);
		}
		
		return FALSE;
	}
	
	/**
	 * Returns the array of column names for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}
}

?>
