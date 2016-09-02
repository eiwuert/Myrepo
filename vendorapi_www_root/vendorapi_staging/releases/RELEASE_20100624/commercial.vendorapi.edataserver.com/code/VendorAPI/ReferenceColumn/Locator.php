<?php
/*
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_ReferenceColumn_Locator
{
	/**
	 * @var DB_Models_IWritableModel_1
	 */
	protected $model;
	
	/**
	 * @var Array
	 */
	protected $locate_methods;
	
	/**
	 * 
	 * @var string
	 */
	protected $referenced_column;
	
	/**
	 * 
	 * @param DB_Models_IWritableModel_1 $model
	 * @return unknown_type
	 */
	public function __construct(DB_Models_IWritableModel_1 $model)
	{
		$this->model = $model;
		$this->locate_methods = array();
		$this->setReferencedColumn($model->getAutoIncrement());
	}
	
	/**
	 * l1wKO3rID9g
	 * T-Qn_-F2x1c
	 * Return the model this locator is locating
	 * @return DB_Models_IWritableModel_1
	 */
	public function getModel()
	{
		return $this->model;
	}
	
	/**
	 * 
	 * Runs all of the load by delegates until one of them
	 * in the list returns true. Returns false if none of 
	 * them return true, or true.
	 * @return boolean
	 */
	public function locateModel()
	{
		foreach ($this->locate_methods as $method)
		{
			if ($method->invoke())
			{
				return TRUE;
			}
		}
		return FALSE;	
	}
	
	/**
	 * Adds a method on the model we're referencing a column in to 
	 * be used to attempt to load a new one. Takes a variable number of
	 * arguments, the first being the method, any others being parameters to
	 * that method.
	 * 
	 * @param String $method
	 * @param mixed $arg+
	 * @return VendorAPI_ReferenceColumn_Locator
	 */
	public function addLoadByMethod($method, $arg = NULL)
	{
		$args = func_get_args();
		$method = array_shift($args);
		$callback = array($this->model, $method);
		if (is_callable($callback))
		{
			$this->locate_methods[] = new Delegate_1($callback, $args);
		}
		else
		{
			throw new InvalidArgumentException("Invalid method {$method}");
		}
		return $this;
	}
	
	/**
	 * 
	 * Loads the model, and sets the thing
	 * @return mixed
	 */
	public function resolveReference()
	{
		return $this->locateModel() ? $this->model->{$this->referenced_column} : FALSE;
	}
	
	/**
	 * Set the column in the model we're referencing
	 * @param String $column
	 * @return VendorAPI_ReferenceColumn_Locator
	 */
	public function setReferencedColumn($column)
	{
		if (in_array($column, $this->model->getColumns()))
		{
			$this->referenced_column = $column;
		}
		else
		{
			throw new RuntimeException("Invalid column $column\n");
		}
		return $this;
	}
	
	/**
	 * Forces a database on the model
	 * @param DB_IConnection_1 $db
	 * @return void
	 */
	public function setDatabase(DB_IConnection_1 $db) 
	{
		if (method_exists($this->model, 'setDatabaseInstance'))
		{
			$this->model->setDatabaseInstance($db);
		}
		else
		{
			throw new RuntimeException("Could not set database instance on model.");
		}
	}
}