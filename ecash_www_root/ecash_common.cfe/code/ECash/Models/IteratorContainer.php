<?php
class ECash_Models_IteratorContainer implements ECash_Models_IIterativeModel
{
	/**
	 * Log class
	 *
	 * @var Applog
	 */
	protected $applog;

	/**
	 * Collection of ECash_Models_IIterativeModel objects
	 *
	 * @var DB_Models_Iterator_1
	 */
	protected $iterator = NULL;
	
	/**
	 * Current position of the iterator
	 *
	 * @var int
	 */
	protected $position = 0;
	
	/**
	 * Array of non-authoritative ECash_Models_IIterativeModel objects
	 *
	 * @var array
	 */
	protected $non_authoritative_models = array();
	
	/**
	 * Array of ECash_Models_IIterativeModel objects
	 *
	 * @var ECash_Models_IIterativeModel
	 */
	protected $authoritative_model;
	
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
	 * @param bool $throw_non_auth_exception Throw exceptions originating from non-authoritative models
	 * @return void
	 */
	public function __construct(Applog $applog, $throw_non_auth_exception = TRUE)
	{
		$this->applog = $applog;
		$this->throw_non_auth_exception = $throw_non_auth_exception;
	}

	/**
	 * Add a non-authoritative model to the collection
	 *
	 * @param ECash_Models_IIterativeModel $model
	 * @return void
	 */
	public function addNonAuthoritativeModel(ECash_Models_IIterativeModel $model)
	{
		$this->non_authoritative_models[] = $model;
	}

	/**
	 * Get the authoritative model
	 *
	 * @return ECash_Models_IIterativeModel
	 */
	public function getAuthoritativeModel()
	{
		return $this->authoritative_model;
	}
	
	/**
	 * Get an array containing all models
	 * @return array Array of ECash_Models_IIterativeModel objects
	 */
	public function getModels()
	{
		$models = array_merge(
			array($this->getAuthoritativeModel()),
			$this->getNonAuthoritativeModels());
		return $models;
	}

	/**
	 * Gat an array of non-authoritative models
	 *
	 * @return array Array of ECash_Models_IIterativeModel objects
	 */
	public function getNonAuthoritativeModels()
	{
		return $this->non_authoritative_models;
	}

	/**
	 * Set the authoritative model
	 *
	 * @param ECash_Models_IIterativeModel $model
	 */
	public function setAuthoritativeModel(ECash_Models_IIterativeModel $model)
	{
		$this->authoritative_model = $model;
	}

	/**
	 * @return string
	 * @see ECash_Models_IIterativeModel#getTableName
	 */
	public function getTableName()
	{
		return $this->call(__FUNCTION__, array());
	}

	
	/**
	 * @param array $where_args
	 * @return bool
	 * @see ECash_Models_IIterativeModel#loadBy
	 */
	public function loadBy(array $where_args = array())
	{
		$this->getAuthoritativeModel()->loadBy($where_args);

		$non_auth_list = array();
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				if ($model->loadBy($where_args))
				{
					$non_auth_list[] = $model;
				}
			}
			catch(Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		
		$container_list = array();
		foreach ($this->getAuthoritativeModel() as $auth_model)
		{
			$container_model = new DB_Models_Container_1($this->getThrowNonAuthException());
			$container_model->setAuthoritativeModel($auth_model);
			
			// Find the correct model in the non-auth model list to add for the
			// current auth_model based on matching all but the auto-increment columns
			try
			{
				$key_cols = array_diff($this->getColumns($auth_model), array($this->getAutoIncrement($auth_model)));
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
							// If one of the columns doesn't match, update
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
			}
			catch (InvalidArgumentException $e)
			{
				// Cann't determine the matching keys.  Log it and move on.
				$this->applog->Write("Error processing non-auth model matching in loadBy: " . $e->getMessage());
			}
			$container_list[] = $container_model;
		}

		$this->iterator = new DB_Models_Iterator_1($container_list);
		return TRUE;
	}
	
	/**
	 * @param array $where_args
	 * @return bool
	 * @see ECash_Models_IIterativeModel#loadByModified
	 */
	public function loadByModified(array $where_args = array())
	{
		throw new Exception("loadByModified is not supported by the container");
	}
	
	/**
	 * @param array $order_by
	 * @param integer $limit
	 * @see ECash_Models_IIterativeModel#orderBy
	 */
	public function orderBy(array $order_by)
	{
		return $this->callAll(__FUNCTION__, array($order_by));
	}
	
	/**
	 * @param integer $limit
	 * @return void
	 * @see ECash_Models_IIterativeModel#limit
	 */
	public function setLimit($limit = NULL)
	{
		return $this->callAll(__FUNCTION__, array($limit));
	}
	
	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::currentRawData()
	 */
	public function currentRawData()
	{
		return $this->iterator->currentRawData();
	}
	
	/**
	 * @return string 
	 * @see DB_Models_IIterativeModel_1::getClassName()
	 */
	public function getClassName()
	{
		return "ECash_Models_IIterativeModel";
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::count()
	 */
	public function count()
	{
		return  $this->iterator->count();
	}
	
	/**
	 * @return DB_Models_IIterativeModel_1
	 * @see DB_Models_IIterativeModel_1::current()
	 */
	public function current()
	{
		return $this->iterator->current();
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::key()
	 */
	public function key()
	{
		return $this->iterator->key();
	}
	
	/**
	 * @return DB_Models_IWriteableModel 
	 * @see DB_Models_Iterator_1::next()
	 */
	public function next()
	{
		return $this->iterator->next();
	}
	/**
	 * @return void 
	 * @see DB_Models_IIterativeModel_1::rewind()
	 */
	public function rewind()
	{
		return $this->iterator->rewind();
	}
	
	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::toArray()
	 */
	public function toArray()
	{
		return $this->iterator->toArray();
	}
	
	/**
	 * @return DB_Models_ModelList_1 
	 * @see DB_Models_IIterativeModel_1::toList()
	 */
	public function toList()
	{
		return $this->iterator->toList();
	}
	
	
	/**
	 * @return bool 
	 * @see DB_Models_IIterativeModel_1::valid()
	 */
	public function valid()
	{	
		return $this->iterator->valid();
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
			$this->applog->Write(sprintf(
				"Non-authoritative model exception from container in File: %s Line: %s Message: %s",
				$e->getFile(),
				$e->getLine(),
				$e->getMessage()));
		}
	}
	
	/**
	 * Get columns names from the supplied model
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @return array
	 */
	protected function getColumns(DB_Models_IWritableModel_1 $model)
	{
		return $model->getColumns();
	}
	
	/**
	 * Get the AutoIncrement from the supplied model
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @return string
	 */
	protected function getAutoIncrement(DB_Models_IWritableModel_1 $model)
	{
		return $model->getAutoIncrement();
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
