<?php
/**
 * @see DB_Models_IContainer_1
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_Container_1 implements DB_Models_IContainer_1, DB_Models_IWritableModel_1
{
	/**
	 * Array of DB_Models_IContainerObserver_1 objects
	 *
	 * @var array
	 */
	protected $observers = array();

	/**
	 * Array of DB_Models_IContainerValidator_1 objects
	 *
	 * @var array
	 */
	protected $validators = array();
	
	/**
	 * Array of non-authoritative DB_Models_IWritableModel_1 objects
	 *
	 * @var array
	 */
	protected $non_authoritative_models = array();
	
	/**
	 * Authoritative DB_Models_IWritableModel_1
	 *
	 * @var DB_Models_IWritableModel_1
	 */
	protected $authoritative_model;
	
	/**
	 * Has the object changed
	 *
	 * @var bool
	 */
	protected $changed;

	/**
	 * Stack of DB_Models_ContainerValidatorException_1 validation exceptions
	 *
	 * @var array
	 */
	protected $validation_exception_stack = array();

	/**
	 * The last thrown exception from a non-authoritative model
	 *
	 * @var Exception
	 */
	protected $non_authoritative_exception;

	/**
	 * Throw exceptions originating from non-authoritative models
	 *
	 * @var bool
	 */
	protected $throw_non_auth_exception;

	/**
	 * Array of column names to use for identifying matches when matching models
	 * from multi-model loads
	 *
	 * @var array
	 */
	protected $match_columns;

	/**
	 * @param bool $throw_non_auth_exception 
	 * Throw exceptions originating from non-authoritative models
	 */
	public function __construct($throw_non_auth_exception = TRUE)
	{
		$this->throw_non_auth_exception = $throw_non_auth_exception;
	}

	/**
	 * We got a special method call that is likely specific to a model implementation.
	 * We'll just pass it along to all the models to be safe.
	 *
	 * @param method name $name
	 * @param method arguments $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return $this->callAll($name, $arguments);
	}


	/**
	 * Provide clones of the contained models when cloning the container
	 * @return void
	 */
	public function __clone()
	{
		// Clone the authoritative model if it is not empty
		$model = $this->getAuthoritativeModel();
		if (!empty($model))
		{
			$clone = clone $model;
			$this->setAuthoritativeModel($clone);
			unset($clone);
		}

		// Clone the non-authoritative models
		$clones = array();
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			$clones[] = clone $model;
		}
		$this->non_authoritative_models = $clones;
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getColumns
	 * @return array
	 */
	public function getColumns()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getTableName
	 * @return string
	 */
	public function getTableName()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getPrimaryKey
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getAutoIncrement
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#isStored
	 * @return bool
	 */
	public function isStored()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#isAltered
	 * @return bool
	 */
	public function isAltered()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#save
	 * @return bool
	 */
	public function save()
	{
		return $this->callAll(__FUNCTION__, array());
	}

	/**
	 * @see DB_Models_IWritableModel_1#insert
	 * @return int
	 */
	public function insert()
	{
		return $this->callAll(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#update
	 * @return int
	 */
	public function update()
	{
		return $this->callAll(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#delete
	 * @return int
	 */
	public function delete()
	{
		return $this->callAll(__FUNCTION__, array());
	}
	
	/**
	 * @param array $db_row
	 * @param string $column_prefix
	 * @see DB_Models_IWritableModel_1#fromDbRow
	 * @return void
	 */
	public function fromDbRow(array $db_row, $column_prefix = '')
	{
		return $this->callAll(__FUNCTION__, array($db_row, $column_prefix));
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getAlteredColumnData
	 * @return array
	 */
	public function getAlteredColumnData()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#setDataSynched
	 * @return void
	 */
	public function setDataSynched()
	{
		return $this->callAll(__FUNCTION__, array());
	}
	
	/**
	 * @param mixed $key
	 * @see DB_Models_IWritableModel_1#loadByKey
	 * @return bool
	 */
	public function loadByKey($key)
	{
		return $this->callAll(__FUNCTION__, array($key));
	}
	
	/**
	 * @param array $where_args
	 * @see DB_Models_IWritableModel_1#loadBy
	 * @return bool
	 */
	public function loadBy(array $where_args)
	{
		return $this->callAll(__FUNCTION__, array($where_args));
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getAffectedRowCount
	 * @return int
	 */
	public function getAffectedRowCount()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @param bool $state
	 * @see DB_Models_IWritableModel_1#setReadOnly
	 * @return void
	 */
	public function setReadOnly($state = FALSE)
	{
		return $this->callAll(__FUNCTION__, array($state));
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getReadOnly
	 * @return bool
	 */
	public function getReadOnly()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @param bool $delete
	 * @see DB_Models_IWritableModel_1#setDeleted
	 * @return void
	 */
	public function setDeleted($delete)
	{
		return $this->callAll(__FUNCTION__, array($delete));
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getDeleted
	 * @return bool
	 */
	public function getDeleted()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @param int $mode
	 * @see DB_Models_IWritableModel_1#setDeleted
	 * @return void
	 */
	public function setInsertMode($mode = DB_Models_IWritableModel_1::INSERT_STANDARD)
	{
		return $this->callAll(__FUNCTION__, array($mode));
	}
	
	/**
	 * @see DB_Models_IWritableModel_1#getColumnData
	 * @return array
	 */
	public function getColumnData()
	{
		return $this->call(__FUNCTION__, array());
	}
	
	/**
	 * @param array $data
	 * @return void
	 * @see DB_Models_IWritableModel_1#setModelData
	 */
	public function setModelData(array $data)
	{
		return $this->callAll(__FUNCTION__, array($data));
	}
	
	/**
	 * Will perform loadAllBy on all models and create a new
	 * list from those results based on the primary key matching
	 * @param array $where_args
	 * @see DB_Models_IWritableModel_1#loadAllBy
	 * @return DB_Models_IterativeModel_1
	 */
	public function loadAllBy(array $where_args = array())
	{
		// Perform loadAllBy on the models in the container and store them
		// for combining later
		$auth_list = $this->getAuthoritativeModel()->loadAllBy($where_args);
		$non_auth_list = array();
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				$non_auth_list[] = $model->loadAllBy($where_args);
			}
			catch(SoapFault $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
			catch(Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		
		$container_list = array();
		foreach ($auth_list as $auth_model)
		{
			$container_model = new DB_Models_Container_1($this->getThrowNonAuthException());
			$container_model->setAuthoritativeModel($auth_model);
			
			// Add Validators
			foreach ($this->getValidators() as $validator)
			{
				$container_model->addValidator($validator);
			}
			
			// Add Observers
			foreach ($this->getObservers() as $observer)
			{
				$container_model->addObserver($observer);
			}
			
			// Find the correct model in the non-auth model list to add for the
			// current auth_model based on the defined match columns
			$key_cols = $this->getMatchColumns();
			foreach ($non_auth_list as $list)
			{
				// Iterate through the models in the list
				foreach ($list as $model)
				{
					// Match will default to true
					$match = TRUE;
					
					// Iterate through the key columns
					foreach ($key_cols as $key_col)
					{
						// If one of the PK columns doesn't match, update
						// the match check and stop processing the keys
						try
						{
							$non_auth_key_val = $model->{$key_col};
						}
						catch(Exception $e)
						{
							$this->handleNonAuthoritativeModelException($e);	
							$match = FALSE;
							break;
						}
						
						if ($auth_model->{$key_col} != $non_auth_key_val)
						{
							$match = FALSE;
							break;
						}
					}
					
					// If it is a match add the non-auth model to the colection for the
					// container
					if ($match)
					{
						$container_model->addNonAuthoritativeModel($model);
					}
				}
			}
			$container_list[] = $container_model;
		}

		$iterator = new DB_Models_Iterator_1($container_list);

		$this->validate("loadAllBy", array($where_args));
		return $iterator;
	}

	/**
	 * Set data item to value for all models and verify
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				$model->{$name} = $value;
			}
			catch (Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		$model = $this->getAuthoritativeModel();
		$model->{$name} = $value;
		$this->validate(__FUNCTION__, array($name, $value));
	}
	
	/**
	 * Get data item from the authoritative model and verify all
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$return_value = $this->getAuthoritativeModel()->{$name};
		$this->validate(__FUNCTION__, array($name));
		return $return_value;
	}

	/**
	 * Validate the function call and return the isset response
	 * from the authoritative model
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$this->validate(__FUNCTION__, array($name));
		return isset($this->getAuthoritativeModel()->{$name});
	}

	/**
	 * Call unset on every model
	 * from the authoritative model
	 *
	 * @param unknown_type $name
	 * @return void
	 */
	public function __unset($name)
	{
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				unset($model->{$name});
			}
			catch (Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		$model = $this->getAuthoritativeModel();
		unset($model->{$name});
		$this->validate(__FUNCTION__, array($name));
	}

	/**
	 * @see DB_Models_IWritableModel_1#copy
	 * @return DB_Models_IContainer
	 */
	public function copy()
	{
		$new_container = clone($this);
		$auto_increment = $this->getAutoIncrement();
		if (!empty($auto_increment) && (!empty($new_container->{$auto_increment})))
		{
			unset($new_container->{$auto_increment});
		}
	}
	
	/**
	 * @see DB_Models_IContainer#addObserver
	 * @param DB_Models_IContainerObserver $observer
	 * @return void
	 */
	public function addObserver(DB_Models_IContainerObserver_1 $observer)
	{
		$this->observers[] = $observer;
	}

	/**
	 * @see DB_Models_IContainer#getObservers
	 * @return array
	 */
	protected function getObservers()
	{
		return $this->observers;
	}

	/**
	 * @see DB_Models_IContainer#addValidator
	 * @param DB_Models_IContanerValidator $validator
	 * @return void
	 */
	public function addValidator(DB_Models_IContainerValidator_1 $validator)
	{
		$this->validators[] = $validator;
	}

	/**
	 * @see DB_Models_IContainer_1#addNonAuthoritativeModel
	 * @param DB_Models_IWritableModel_1 $model
	 * @return void
	 */
	public function addNonAuthoritativeModel(DB_Models_IWritableModel_1 $model)
	{
		$this->non_authoritative_models[] = $model;
	}

	/**
	 * @see DB_Models_IContainer_1#getNonAuthoritativeModels
	 * @return array
	 */
	public function getNonAuthoritativeModels()
	{
		return $this->non_authoritative_models;
	}
	
	/**
	 * @see DB_Models_IContainer_1#getModels
	 * @return array Array of models
	 */
	public function getModels()
	{
		$models = array_merge(
			array($this->getAuthoritativeModel()),
			$this->getNonAuthoritativeModels());
		return $models;
	}

	
	/**
	 * @see DB_Models_IContainer_1#setAuthoritativeModel
	 * @param DB_Models_WritableModel_1 $model
	 * @return void
	 */
	public function setAuthoritativeModel(DB_Models_IWritableModel_1 $model)
	{
		$this->authoritative_model = $model;
	}

	/**
	 * @see DB_Models_IContainer_1#getAuthoritativeModel
	 * @return DB_Models_WritableModel_1
	 */
	public function getAuthoritativeModel()
	{
		return $this->authoritative_model;
	}

	/**
	 * @see DB_Models_IContainer_1#getValidationExceptionStack
	 * @return array
	 */
	public function getValidationExceptionStack()
	{
		return $this->validation_exception_stack;
	}
	

	/**
	 * @see DB_Models_IContainer_1#getNonAuthoritativeModelException
	 * @return Exception
	 */
	public function getNonAuthoritativeModelException()
	{
		return $this->non_authoritative_exception;
	}
	

	/**
	 * @see DB_Models_IContainer_1#isChanged
	 * @return bool
	 */
	public function isChanged()
	{
		return $this->changed;
	}

	/**
	 * Set the object changed state to TRUE and alert the observers
	 *
	 * @return void
	 */
	protected function setChanged()
	{
		$this->changed = TRUE;
		$this->updateObservers();
		$this->setUnchanged();
	}

	/**
	 * Set the object changed state to FALSE
	 *
	 * @return void
	 */
	protected function setUnchanged()
	{
		$this->changed = FALSE;
	}

	/**
	 * Update the observers
	 *
	 * @return void
	 */
	protected function updateObservers()
	{
		foreach ($this->getObservers() as $observer)
		{
			$observer->update($this);
		}
		
	}

	/**
	 * Get the collection of DB_Models_IContainerValidator_1 validators
	 * as an array
	 * 
	 * @return array Array of DB_Models_IContainerValidator_1 objects
	 */
	protected function getValidators()
	{
		return $this->validators;
	}

	/**
	 * Add a validation exception to tpubliche stack
	 *
	 * @param DB_Models_ContainerValidatorException_1 $e
	 * @return void
	 */
	protected function addValidationExceptionToStack(DB_Models_ContainerValidatorException_1 $e)
	{
		$this->validation_exception_stack[] = $e;
	}

	/**
	 * Reset the validation exception stack
	 *
	 * @return void
	 */
	protected function resetValidationExceptionStack()
	{
		$this->validation_exception_stack = array();
	}

	/**
	 * Set the current non-authoritative exception
	 *
	 * @param Exception $e
	 * @return void
	 */
	protected function setNonAuthoritativeException(Exception $e)
	{
		$this->non_authoritative_exception = $e;
	}

	/**
	 * Reset the non-authoritative exception stack
	 *
	 * @return void
	 */
	protected function resetNonAuthoritativeExceptionStack()
	{
		$this->non_auth_exception_stack = array();
	}

	/**
	 * Validate a function call with args
	 *
	 * @param String $function_name Name of function to validate
	 * @param array $function_args Array of function arguments
	 * @return void
	 */
	protected function validate($function_name, array $function_args)
	{
		foreach ($this->getValidators() as $validator)
		{
			try
			{
				$validator->validate($this, $function, $function_args);
			}
			catch (DB_Models_ContainerValidatorException_1 $e)
			{
				$this->addValidationExceptionToStack($e);
			}
		}
		

		if (count($this->getValidationExceptionStack()) > 0)
		{
			$this->setChanged();
		}
		$this->resetValidationExceptionStack();
	}

	/**
	 * Perform a pass-through call to the authoritative model
	 * with teh supplied function name and argument array
	 *
	 * @param string $function_name
	 * @param array $args
	 * @return mixed
	 */
	protected function call($function_name, $args)
	{
		$return_value = call_user_func_array(
			array($this->getAuthoritativeModel(), $function_name),
			$args);
		$this->validate($function_name, $args);
		return $return_value;
	}

	/**
	 * Perform a pass-through call to the authoritative model,
	 * call the fuinction on all of teh non-authoritative models,
	 * and validate
	 *
	 * @param string $function_name
	 * @param array $args
	 * @return mixed
	 */
	protected function callAll($function_name, array $args)
	{
		$return_value = call_user_func_array(
			array($this->getAuthoritativeModel(), $function_name),
			$args);

		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				call_user_func_array(
					array($model, $function_name),
					$args);
			}
			catch (Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		
		$this->validate($function_name, $args);
		return $return_value;
	}

	/**
	 * Should exceptions originating from non-authoritative
	 * models be thrown
	 *
	 * @return bool
	 */
	protected function getThrowNonAuthException()
	{
		return $this->throw_non_auth_exception;
	}

	/**
	 * Handle exceptions originating from non-authoritative model objects
	 *
	 * @param Exception $e
	 * @return void
	 */
	protected function handleNonAuthoritativeModelException(Exception $e)
	{
		// If non auth exceptions are to be thrown then throw it
		if ($this->getThrowNonAuthException())
		{
			throw $e;
		}
		else
		{
			$this->setNonAuthoritativeException($e);
			$this->setChanged();
		}
	}

	/**
	 * Set the columns to use for identifying matches when matching models
	 * from multi-model loads
	 *
	 * @param array $columns
	 * @return void
	 */
	public function setMatchColumns(array $columns)
	{
		$this->match_columns = $columns;
	}

	/**
	 * Get the columns to use for identifying matches when matching models
	 * from multi-model loads
	 * 
	 * @return array
	 */
	protected function getMatchColumns()
	{
		return $this->match_columns;
	}
}
?>
