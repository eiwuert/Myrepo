<?php
/**
 * A factory class for Blackbox DB models.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_ModelFactory
{
	/** Target Collection is a special-case ReferenceTable. It is built upon
	 * multiple different target types, with priorities over one another. The
	 * NAME is the internal name to to used for the internal cache. The TABLE
	 * is which ReferenceTable to load. And TYPES are, in order of priority
	 * from most important to least important, the BlackboxTypes that we will
	 * use for Id<->Name.
	 */
	const TARGET_COLLECTION_NAME = 'target::collection';
	const TARGET_COLLECTION_TABLE = 'TargetPropertyShort';
	const TARGET_COLLECTION_TYPES = 'CAMPAIGN,TARGET,COLLECTION';
	
	/**
	 * Instance of the database to use
	 *
	 * @var DB_IConnection_1
	 */
	protected $db_instance;
	
	/**
	 * Pointer to itself.
	 *
	 * See the note above getInstance().
	 *
	 * @var Blackbox_ModelFactory
	 */
	protected static $self_instance;
	
	/**
	 * Reference tables
	 *
	 * @var array
	 */
	protected $reference_tables = array();
	
	/**
	 * Sets the database connection if given
	 *
	 * @param DB_IConnection_1 $db_instance
	 */
	public function __construct(DB_IConnection_1 $db_instance)
	{
		$this->db_instance = $db_instance;
		self::$self_instance = $this;
	}
	
	/**
	 * Returns the internal pointer to itself.
	 *
	 * This is exceptionally lame, and I hate it, but in order to not break the entire admin
	 * code base, I have to put this in here. Almost all the code using BBxAdmin_Object needs
	 * to be refactored so it's already got the factories or other objects it needs rather than
	 * using the factories that are currently created in BBxAdmin_Object.
	 *
	 * @return Blackbox_ModelFactory
	 */
	public static function getInstance()
	{
		return self::$self_instance;
	}
	
	/**
	 * Return the database instance for Blackbox for the given environment.
	 *
	 * @throws Blackbox_Exception
	 * @param string $env
	 * @return DB_IConnection_1
	 */
	public function getDbInstance()
	{
		if (isset($this->db_instance) && $this->db_instance instanceof DB_IConnection_1)
		{
			return $this->db_instance;
		}
		
		throw new Blackbox_Exception('database instance not available');
	}
	
	/**
	 * Returns the DBConfigObject
	 *
	 * @param Zend_Config_Xml $config
	 * @param string $environment
	 * @return DB_MySQLConfig_1
	 */
	public static function getDBConfigObject($config, $environment)
	{
		return new DB_MySQLConfig_1(
			$config->database->{$environment}->host,
			$config->database->{$environment}->username,
			$config->database->{$environment}->password,
			$config->database->{$environment}->db,
			$config->database->{$environment}->port
		);
	}
	
	/**
	 * Returns a model
	 *
	 * @param string $model_class
	 * @return mixed
	 */
	private function privateGetModel($model_class)
	{
		if (!class_exists($model_class, TRUE))
		{
			throw new Blackbox_Exception(
				"unable to load unknown model $model_class"
			);
		}
		if ($this->db_instance instanceof DB_IConnection_1)
		{
			return new $model_class($this->db_instance);
		}
		
		return new $model_class();
	}
	
	/**
	 * Returns a Blackbox_Model_* writable object.
	 *
	 * @param string $model_name a string of the model name
	 * @return Blackbox_Models_WriteableModel
	 */
	public function getModel($model_name)
	{
		$model_class = $this->getModelClass(($model_name));
		return $this->privateGetModel($model_class);
	}

	/**
	 * This convenience method is mostly for libraries that use the class name
	 * of a model, such as DB_Models_ModelList_1.
	 *
	 * @param string $model_name a string of the model name
	 * @return string model class name
	 */
	public function getModelClass($model_name)
	{
		return $this->getClass("Models_{$model_name}");
	}

	/**
	 * Returns a reference Blackbox model object.
	 *
	 * @param string $model_name a string of the model name
	 * @return Blackbox_Models_Reference_Model
	 */
	public function getReferenceModel($model_name)
	{
		$model_class = $this->getClass("Models_Reference_{$model_name}");
		return $this->privateGetModel($model_class);
	}
	
	/**
	 * Returns a reference table for the given model
	 *
	 * @param string $model_name
	 * @param boolean $prefetch
	 * @param array $where Where arguments.
	 * @return Blackbox_Models_ReferenceTable
	 */
	public function getReferenceTable($model_name, $prefetch = FALSE, array $where = NULL)
	{
		$model_hash = $this->getModelHash($model_name, $where);
		
		if (!isset($this->reference_tables[$model_hash]))
		{
			// Add in special case exception for Target Collection.
			switch ($model_name)
			{
				case self::TARGET_COLLECTION_NAME:
					$this->reference_tables[$model_hash] = $this->getTargetReferenceCollection($prefetch);
					break;
				
				default:
					$this->reference_tables[$model_hash] = new Blackbox_Models_ReferenceTable($this->getReferenceModel($model_name), $prefetch, $where);
					break;
			}
		}
		
		return $this->reference_tables[$model_hash];
	}
	
	/**
	 * Hashes model names. The where argument is order-specific of parameters.
	 *
	 * @param string $model_name
	 * @param array $where
	 * @return string
	 */
	protected function getModelHash($model_name, array $where = NULL)
	{
		return $model_name . ':' . serialize($where);
	}
	
	/**
	 * Special-case for the target -> property_short reference table.
	 *
	 * @param bool $prefetch
	 * @return DB_Models_IReferenceTable_1
	 */
	protected function getTargetReferenceCollection($prefetch = FALSE)
	{
		$reference_table = new Blackbox_Models_ReferenceTableCollection();
		
		$ref_blackboxtype = $this->getReferenceTable('BlackboxType', $prefetch);
		$blackbox_types = explode(',', self::TARGET_COLLECTION_TYPES);
		foreach ($blackbox_types AS $blackbox_type)
		{
			$where_arg = array(
				'blackbox_type_id' => $ref_blackboxtype->toId(trim($blackbox_type)),
			);
			
			$reference_table->addReferenceTable($this->getReferenceTable(self::TARGET_COLLECTION_TABLE, $prefetch, $where_arg));
		}
		
		return $reference_table;
	}
	
	/**
	 * Returns a view Blackbox model object.
	 *
	 * @param string $model_name
	 * @return Blackbox_Model_View_Base
	 */
	public function getViewModel($model_name)
	{
		$model_class = $this->getClass("Models_View_{$model_name}");
		return $this->privateGetModel($model_class);
	}
	
	/**
	 * Returns the class name.
	 *
	 * @param string $class_name
	 * @return string
	 */
	public function getClass($class_name)
	{
		return "Blackbox_{$class_name}";
	}
}
