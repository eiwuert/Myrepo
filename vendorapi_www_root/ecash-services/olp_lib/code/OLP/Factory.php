<?php

/**
 * Factory to return OLP Model objects
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Factory extends DB_Models_ModelFactory_1
{
	/**
	 * OLP often needs the TargetPropertyShort reference model. This is ugly,
	 * and needs to be redone when the model factories get refactored.
	 *
	 * @var Blackbox_ModelFactory
	 */
	protected $blackbox_modelfactory;
	
	/**
	 * @var Security_ICrypt_1
	 */
	protected $crypt_object;
	
	/**
	 * Database connection
	 *
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Constructor
	 *
	 * @param DB_IConnection_1 $db
	 * @param Security_ICrypt_1 $crypt_object
	 * @param string $mode
	 */
	public function __construct(DB_IConnection_1 $db, Security_ICrypt_1 $crypt_object = NULL, $mode = NULL)
	{
		$this->db = $db;
		$this->crypt_object = $crypt_object;
		$this->mode = ($mode !== NULL) ? $mode : BFW_MODE;
	}
	
	/**
	 * Sets a common crypt object for all models.
	 *
	 * @param Security_ICrypt_1 $crypt_object
	 * @return void
	 */
	public function setCryptObject(Security_ICrypt_1 $crypt_object)
	{
		$this->crypt_object = $crypt_object;
	}
	
	/**
	 * Sets the mode.
	 *
	 * @param string $mode
	 * @return void
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}
	
	/**
	 * Returns the proper class name for this model.
	 *
	 * @param string $model_name Name of the model
	 * @return string
	 */
	protected function getClassName($model_name)
	{
		$class_name = 'OLP_Models_' . $model_name;
		
		if (!class_exists($class_name))
		{
			throw new RuntimeException("Invalid model specified in OLP_Factory: '{$class_name}' does not exist.");
		}
		
		return $class_name;
	}
	
	/**
	 * Returns an OLP Writable Model
	 *
	 * @param string $model_name Name of the model
	 * @return OLP_Models_WritableModel
	 */
	public function getModel($model_name)
	{
		$class_name = $this->getClassName($model_name);
		
		// Pass the crypt object to CryptWritableModels
		if (is_subclass_of($class_name, 'OLP_Models_CryptWritableModel'))
		{
			if (!$this->crypt_object instanceof Security_ICrypt_1)
			{
				throw new InvalidArgumentException("Model name '{$model_name}' is a crypt model, and no crypt object has been defined.");
			}
			
			$class = new $class_name($this->db, $this->crypt_object);
		}
		else
		{
			$class = new $class_name($this->db);
		}
		
		if ($class instanceof OLP_Models_IAutoSetupRolling)
		{
			$class->autoSetTableNames();
		}
		
		return $class;
	}
	
	/**
	 * Return an instance of the reference table. Prefixes model name with "References_".
	 *
	 * @param string $model_name Name of the model.
	 * @param bool $prefetch TRUE to automatically load the reference table.
	 * @param array $where Where arguments.
	 * @return DB_Models_ReferenceTable_1
	 */
	public function getReferenceTable($model_name, $prefetch = FALSE, array $where = NULL)
	{
		$model = NULL;
		
		switch ($model_name)
		{
			case 'TargetPropertyShort':
			case Blackbox_ModelFactory::TARGET_COLLECTION_NAME:
				$model = $this->getBlackboxModelFactory()->getReferenceTable($model_name, $prefetch, $where);
				break;
			
			default:
				$model = parent::getReferenceTable('References_' . $model_name, $prefetch, $where);
				break;
		}
		
		return $model;
	}
	
	/**
	 * Gets the Blackbox_ModelFactory.
	 *
	 * @return Blackbox_ModelFactory
	 */
	public function getBlackboxModelFactory()
	{
		if (!$this->blackbox_modelfactory)
		{
			$this->blackbox_modelfactory = new Blackbox_ModelFactory(DB_Connection::getInstance('BLACKBOX3', $this->mode));
		}
		
		return $this->blackbox_modelfactory;
	}
}
