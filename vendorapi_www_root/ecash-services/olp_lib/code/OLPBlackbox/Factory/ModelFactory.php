<?php

/**
 * Class to provide convenience in getting model factories to children.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Factory
 */
abstract class OLPBlackbox_Factory_ModelFactory
{
	const CAMPAIGN_TYPE = 'CAMPAIGN';
	const TARGET_TYPE = 'TARGET';
	const COLLECTION_TYPE = 'COLLECTION';
	
	/**
	 * Storage area for factories which are not specific to parent factories.
	 *
	 * @var array
	 */
	protected static $factories = array();
	
	/**
	 * Database connection, required for pulling Blackbox models.
	 *
	 * @var DB_Database_1
	 */
	protected static $db = NULL;
	
	/**
	 * The config object used by factories to interpret directives in SiteConfig
	 * @var OLPBlackbox_Factory_Config
	 */
	protected static $factory_config = NULL;
	
	/**
	 * Cache the parent trees of targets by target_id.
	 * @var array
	 */
	protected static $parent_tree = array();
	
	/**
	 * Rule definition models.
	 *
	 * @var array
	 */
	protected static $rule_definitions = array();
	
	/**
	 * A type reference model, does not write. Only reads.
	 * 
	 * @var Blackbox_Models_Reference_BlackboxType
	 */
	protected static $bbx_type_ref;
	
	/**
	 * Mockable accessor to OLPBlackbox_Config for children.
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}

	/**
	 * Returns a key which used to cache Target objects in memcache.
	 * 
	 * To make this work properly for Target caching, all OLPBlackbox_Config
	 * items that affect how targets are assembled must be included.
	 * 
	 * @return string md5 hash.
	 */
	protected function getDebugConfCacheKey()
	{
		static $key = NULL;
		if ($key == NULL)
		{
			$debug = clone OLPBlackbox_Config::getInstance()->debug;
			$debug->unsetFlag(OLPBlackbox_DebugConf::USE_TIER);
			$debug->unsetFlag(OLPBlackbox_DebugConf::EXCLUDE_TIER);
			$debug->unsetFlag(OLPBlackbox_DebugConf::TARGETS_RESTRICT);
			$debug->unsetFlag(OLPBlackbox_DebugConf::TARGETS_EXCLUDE);
			$key = md5(OLPBlackbox_Config::getInstance()->blackbox_mode
				. OLPBlackbox_Config::getInstance()->title_loan
				. OLPBlackbox_Config::getInstance()->is_enterprise
				. OLPBlackbox_Config::getInstance()->react_company
				. print_r($debug, TRUE)
			);
		}
		return $key;
	}
	
	/**
	 * Returns the rule definition model for the given ID.
	 *
	 * Returns FALSE on error, generally because it can't find that rule definition.
	 *
	 * @param int $rule_def_id
	 * @return Blackbox_Models_RuleDefinition|FALSE
	 */
	public function getRuleDefinition($rule_def_id)
	{
		$rule_def_id_hash = md5($rule_def_id);
		
		if (empty(self::$rule_definitions))
		{
			$rule_definition_model = $this->getModelFactory()->getModel('RuleDefinition');
			$rule_definitions = $rule_definition_model->loadAllBy(array());
			
			foreach ($rule_definitions as $rule_def)
			{
				self::$rule_definitions[md5($rule_def->rule_definition_id)] = $rule_def;
			}
		}
		
		if (isset(self::$rule_definitions[$rule_def_id_hash]))
		{
			return self::$rule_definitions[$rule_def_id_hash];
		}
		
		return FALSE;
	}

	/**
	 * Returns the ModelFactory we want to reuse for static functions here.
	 *
	 * @return Blackbox_ModelFactory
	 */
	protected function getModelFactory()
	{
		static $class = 'Blackbox_ModelFactory';
		
		if (!array_key_exists($class, self::$factories))
		{
			self::$factories[$class] = new Blackbox_ModelFactory(
				$this->getDbConnection()
			);
		}
		
		return self::$factories[$class];
	}
	
	/**
	 * Returns the OLP_Factory
	 *
	 * @return OLP_Factory
	 */
	protected function getOLPFactory()
	{
		static $class = 'OLP_Factory';
		
		if (!array_key_exists($class, self::$factories))
		{
			$config = OLPBlackbox_Config::getInstance();
			$connection = $config->olp_db->getConnection();
			
			self::$factories[$class] = new OLP_Factory(
				new DB_MySQL4Adapter_1($connection, $connection->db_info['db']),
				Crypt_Singleton::Get_Instance()
			);
		}
		
		return self::$factories[$class];
	}
	
	/**
	 * Get the database set up by this environment.
	 * @return DB_Database_1
	 */
	protected function getDbConnection()
	{
		if (!self::$db instanceof DB_Database_1)
		{
			$dbinfo = DB_Server::getServer(
				'olpblackbox',
				OLPBlackbox_Config::getInstance()->mode
			);
			if (stristr($dbinfo['host'], ':') !== FALSE)
			{
				list($host, $port) = split(':', $dbinfo['host']);
			}
			else
			{
				$host = $dbinfo['host'];
				$port = empty($dbinfo['port']) ? 3306 : $dbinfo['port'];
			}
			$env_db = new DB_MySQLConfig_1(
				$host,
				$dbinfo['user'],
				$dbinfo['password'],
				$dbinfo['db'],
				$port
			);
			
			self::$db = $env_db->getConnection();
		}
		
		return self::$db;
	}

	/**
	 * Returns the olp site config.
	 * @return OLPBlackbox_DebugConf
	 */
	protected function getDebug()
	{
		return OLPBlackbox_Config::getInstance()->debug;
	}
	
	/**
	 * WARNING: ONLY for unit testing! (Set the database connection to use.)
	 *
	 * Normally, ModelFactory children will simply call {@see getDbConnection}
	 * and use the default connection set up by the config.
	 *
	 * @param DB_Database_1 $db The database connection to use.
	 * @return void
	 */
	public function setDbConnection(DB_Database_1 $db)
	{
		self::$db = $db;
	}
	
	/**
	 * Returns the LimitCollection factory.
	 *
	 * @return OLPBlackbox_Factory_LimitCollection
	 */
	protected function getLimitCollectionFactory(Blackbox_Models_IReadableTarget $target_model)
	{
		if ($target_model->blackbox_type_id == $this->getBlackboxType('COLLECTION'))
		{
			$factory = $this->getStaticFactoryByClass(
				'OLPBlackbox_Factory_TargetCollectionLimitCollection'
			);
		}
		else
		{
			$factory = $this->getStaticFactoryByClass(
				'OLPBlackbox_Factory_LimitCollection'
			);
		}
		
		return $factory;
	}
	
	/**
	 * Method to get blackbox picker, designed for mocking.
	 * @return OLPBlackbox_Factory_Picker
	 */
	protected function getPickerFactory()
	{
		return $this->getStaticFactoryByClass(
			'OLPBlackbox_Factory_Picker'
		);
	}

	/**
	 * Returns the blackbox type for a type like 'CAMPAIGN' or 'COLLECTION'
	 * @throws InvalidArgumentException if the $type is not a legitimate type.
	 * @param string $type Entry from blackbox_type like 'CAMPAIGN'
	 * @return int The blackbox_type_id for the associated type.
	 */
	protected function getBlackboxType($type)
	{
		return $this->getBlackboxTypeReference()->toId($type);
	}
	
	/**
	 * Reverse of {@see OLPBlackbox_Factory_ModelFactory::getBlackboxType()},
	 * returns the string name of a blackbox_type_id.
	 *
	 * @param int $id The blackbox_type_id to reference. 
	 * @return string Name of the type, like 'CAMPAIGN' or 'TARGET' (always
	 * uppercase)
	 */
	protected function getBlackboxTypeName($id)
	{
		return strtoupper($this->getBlackboxTypeReference()->toName($id));
	}
	
	/**
	 * Returns a static reference model for the blackbox_type table.
	 *
	 * @return Blackbox_Models_ReferenceTable
	 */
	protected function getBlackboxTypeReference()
	{
		if (!self::$bbx_type_ref)
		{
			self::$bbx_type_ref = $this->getModelFactory()->getReferenceTable('BlackboxType', TRUE);
		}
		return self::$bbx_type_ref;
	}

	/**
	 * Method to return an OLPBlackbox_Factory_Campaign, intended for mocking.
	 *
	 * @param string $property_short
	 * @return OLPBlackbox_Factory_Campaign
	 */
	protected function getCampaignFactory($property_short = NULL)
	{
		if (OLPBlackbox_Factory_Campaign_GoodCustomer::isGoodCustomer($property_short))
		{
			return $this->getStaticFactoryByClass(
				'OLPBlackbox_Factory_Campaign_GoodCustomer'
			);
		}
		else
		{
			return $this->getStaticFactoryByClass(
				'OLPBlackbox_Factory_Campaign'
			);
		}
	}
	
	/**
	 * Returns a rule collection for a propery short.
	 * @param string $property_short
	 * @return OLPBlackbox_Factory_RuleCollection
	 */
	protected function getRuleCollectionFactory($property_short)
	{
		$company = CompanyData::getCompany($property_short);
		
		if (strcasecmp($company, CompanyData::COMPANY_ZIPCASH) == 0)
		{
			return new OLPBlackbox_Factory_RuleCollectionZipCash(
				$this->getRuleFactory($property_short)
			);
		}
		else
		{
			return new OLPBlackbox_Factory_RuleCollection(
				$this->getRuleFactory($property_short)
			);
		}
	}
	
	/**
	 * Returns a rule factory based on property short, does factory reuse.
	 * @param string $property_short Property short to use to init factory.
	 * @return OLPBlackbox_Factory_Rule
	 */
	protected function getRuleFactory($property_short)
	{
		$company = EnterpriseData::getCompany($property_short);

		$factory = $this->findEnterpriseRuleFactory($company);
		
		if ($factory == NULL)
		{
			$idx = 'DefaultRuleFactory';
			if (empty(self::$factories[$idx]))
			{
				self::$factories[$idx] = new OLPBlackbox_Factory_Rule();
			}
			
			$factory = self::$factories[$idx];
		}
		
		return $factory;
	}

	/**
	 * Returns a reference model (pre-loaded) for the rule_mode table.
	 *
	 * @return Blackbox_Models_Reference_RuleModeType
	 */
	protected function getRuleModeReferenceModel()
	{
		static $reference_model = NULL;
		if (!$reference_model)
		{
			$reference_model = $this->getModelFactory()->getReferenceTable(
				'RuleModeType', TRUE
			);
		}
		return $reference_model;
	}
	
	/**
	 * Returns an enterprise rule factory for property short, if it exists.
	 * @param string $property_short The property short to search with.
	 * @return NULL|object Factory object or NULL if none is found.
	 */
	protected function findEnterpriseRuleFactory($property_short)
	{
		static $companies = array(
			EnterpriseData::COMPANY_AGEAN => 'OLPBlackbox_Enterprise_Agean_Factory_Rule',
			EnterpriseData::COMPANY_IMPACT => 'OLPBlackbox_Enterprise_Impact_Factory_Rule',
			EnterpriseData::COMPANY_CLK => 'OLPBlackbox_Enterprise_CLK_Factory_Rule',
		);
		
		return $this->getEnterpriseClass($property_short, $companies);
	}

	/**
	 * Silly function to return an enterprise version of a targetcollection factory.
	 * 
	 * I say silly, because there's no need for an enterprise factory, the regular
	 * factory should just handle all kinds of TargetCollections, ideally.
	 *
	 * @todo Deprecate and remove when Enterprise factories are removed.
	 * @param string $property_short The property short used to find the right
	 * kind of factory.
	 * @return NULL|OLPBlackbox_Enterprise_CLK_Factory_TargetCollection
	 */
	protected function findEnterpriseTargetCollectionFactory($property_short)
	{
		// if (!EnterpriseData::isEnterprise(EnterpriseData::resolveAlias($property_short)))
		// TODO: This sucks, but are there any other Enterprise TargetCollections
		// than CLK? I don't think so, at the moment...
		if (strcasecmp(EnterpriseData::resolveAlias($property_short), EnterpriseData::COMPANY_CLK) !== 0)
		{
			// only produce a factory for enterprise companies!
			return NULL;
		}
		
		$class = 'OLPBlackbox_Enterprise_CLK_Factory_TargetCollection';
		
		if (empty(self::$factories[$class]))
		{
			self::$factories[$class] = new $class();
		}
		
		return self::$factories[$class];
	}

	/**
	 * Finds the correct Rule factory class based on an array of companies/class
	 * names and a property short.
	 * 
	 * @param string $property_short The name of the target being assembled.
	 * @param array $class_array Dictionary of 'company' => 'rule factory class name'
	 * @return NULL|OLPBlackbox_Factory_Rule
	 */
	protected function getEnterpriseClass($property_short, array $class_array)
	{
		$company = EnterpriseData::getCompany($property_short);

		foreach ($class_array as $ent_prop => $class)
		{
			if (strcasecmp($ent_prop, $company) == 0)
			{
				if (empty(self::$factories[$class]))
				{
					self::$factories[$class] = new $class();
				}
				
				return self::$factories[$class];
			}
		}
		
		return NULL;
	}
	
	/**
	 * Method to return a OLPBlackbox_Factory_TargetCollection, for mocking.
	 * @param string $property_short The property to use to search for the right
	 * kind of TargetCollection factory.
	 * @return OLPBlackbox_Factory_TargetCollection
	 */
	protected function getTargetCollectionFactory($property_short = NULL)
	{
		$factory = NULL;
		
		if ($property_short)
		{
			$factory = $this->findEnterpriseTargetCollectionFactory($property_short);
		}
				
		return $factory ? $factory : $this->getStaticFactoryByClass(
			'OLPBlackbox_Factory_TargetCollection'
		);
	}

	/**
	 * Method for obtaining a target factory, designed for mocking.
	 * @return OLPBlackbox_Factory_Target
	 */
	protected function getTargetFactory()
	{
		return $this->getStaticFactoryByClass(
			'OLPBlackbox_Factory_Target'
		);
	}
	
	/**
	 * Instantiates simple factory classes AND stores references to them for reuse.
	 * @param string $class The class name to of the simple factory to return.
	 * @return mixed Factory object.
	 */
	protected function getStaticFactoryByClass($class)
	{
		if (!class_exists($class, TRUE))
		{
			throw new Blackbox_Exception(sprintf(
				'attempt to load unknown blackbox model factory %s in %s',
				$class, __METHOD__)
			);
		}
		
		if (!array_key_exists($class, self::$factories)
			|| !self::$factories[$class] instanceof $class)
		{
			self::$factories[$class] = new $class();
		}
		
		return self::$factories[$class];
	}
	
	/**
	 * Returns the OLPBlackbox_Factory_Config used to configure factories.
	 *
	 * Note: Public for unit testing, do not call otherwise.
	 *
	 * @return OLPBlackbox_Factory_Config
	 */
	public function getFactoryConfig()
	{
		if (!self::$factory_config)
		{
			self::$factory_config = new OLPBlackbox_Factory_Config();
		}
		
		return self::$factory_config;
	}

	/**
	 * Determines if the Blackbox_Models_Target passed in is a collection.
	 * @param Blackbox_Models_IReadableTarget $target_model Target model to check for class type.
	 * @return bool
	 */
	protected function targetIsCollection(Blackbox_Models_IReadableTarget $target_model)
	{
		$bbx_type_ref = $this->getModelFactory()->getReferenceTable('BlackboxType', TRUE);
		return $bbx_type_ref->toId('COLLECTION') == $target_model->blackbox_type_id;
	}

	/**
	 * Log an error from the factory
	 *
	 * @param string $description Description of error for app log
	 * @param string $property_short (optional) Property short for event log entry
	 * @return void
	 */
	protected function logError($description, $property_short = NULL)
	{
		$config = OLPBlackbox_Config::getInstance();
		$config->applog->Write($description, LOG_CRIT);
		$this->logEvent(
			$this->getErrorEvent(),
			OLPBlackbox_Config::EVENT_RESULT_ERROR,
			$property_short
		);
	}

	/**
	 * Get the event name for the error event log entry
	 *
	 * @return string
	 */
	protected function getErrorEvent()
	{
		return 'FACTORY';
	}

	/**
	 * Log an event and result for a property short to the event log
	 *
	 * @param string $event
	 * @param string $result
	 * @param string $property_short
	 */
	protected function logEvent($event, $result, $property_short = NULL)
	{
		$this->getConfig()->event_log->Log_Event($event, $result, $property_short);
	}
	
	
	/**
	 * Returns a OLPBlackbox_ListenerHandler. Uses the static factory
	 * cache to make sure every request gets the same one.
	 * @return OLPBlackbox_ListenerHandler
	 */
	public function getListenerHandler()
	{
		if (!self::$factories['OLPBlackbox_ListenerHandler'] instanceof OLPBlackbox_ListenerHandler)
		{
			self::$factories['OLPBlackbox_ListenerHandler'] = new OLPBlackbox_ListenerHandler($this->getConfig()->event_bus);
		}
		return self::$factories['OLPBlackbox_ListenerHandler'];
	}
	
}

?>
