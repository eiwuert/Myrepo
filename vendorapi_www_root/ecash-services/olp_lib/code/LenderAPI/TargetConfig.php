<?php

/**
 * An object which manages the task of extracting/parsing target_data from the
 * database specifically for targets which have LenderAPI information.
 *
 * @package LenderAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_TargetConfig extends Object_1
{
	/**
	 * Database to pull target config information with. (using models)
	 *
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * The property short of the target we'd like to get a config for.
	 *
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * The type of target we'd like to get the config for.
	 *
	 * @var string Either "CAMPAIGN", "COLLECTION" or "TARGET"
	 */
	protected $blackbox_type;
	
	/**
	 * Holds the campaign constants as a assoc array.
	 *
	 * @var array
	 */
	protected $constants;
	
	/**
	 * Unclassified (miscellaneous) configuration assoc array. The "main" config.
	 *
	 * NULL starting initialization is used to check if the init() function has
	 * been run or not.
	 * 
	 * @see hasBeenInitialized()
	 * @var array
	 */
	protected $config;

	/**
	 * Make a TargetConfig object which is able to pull LenderAPI configuration
	 * information.
	 *
	 * @param DB_Database_1 $db
	 * @param string $property_short The target to manage LenderAPI config for.
	 * @param string $blackbox_type The type of target this is.
	 * @return void
	 */
	public function __construct(DB_Database_1 $db, $property_short, $blackbox_type = 'CAMPAIGN')
	{
		$this->db = $db;
		$this->property_short = $property_short;
		$this->blackbox_type = $blackbox_type;
	}
	
	/**
	 * Get the "campaign constants" for this target's config.
	 * 
	 * "Campaign Constants," as they are referred to, are key-value pairs which
	 * are made available to the outgoing XSL transform as XML.
	 *
	 * @return array
	 */
	public function getConstants()
	{
		$this->init();
		
		return $this->constants;
	}
	
	/**
	 * Get the main key=>value pairs for the target config, coming out of target_data.
	 *
	 * These are the main configuration options for the LenderAPI (as well as
	 * extraneous key/value pairs as well, as of this comment's writing.)
	 * 
	 * Campaign default values come from their target. Target default values
	 * optionally come from an inherited target.
	 * 
	 * @return array
	 */
	public function getConfig()
	{
		$this->init();
		
		return $this->config;
	}
	
	/**
	 * Override the configuration (including constants) with an assoc array.
	 *
	 * @param array $override_config The assoc array to override target_data 
	 * settings with.
	 * @return void
	 */
	public function setRuntimeOverride(array $override_config)
	{
		$this->init();
		
		try
		{
			// remember: array_merge(a, b) overrides a with b
			$this->constants = array_merge(
				$this->constants,
				iterator_to_array(new LenderAPI_ConstantDataSource($override_config))
			);
		}
		catch (InvalidArgumentException $e)
		{
			// pass
		}
		
		$this->config = array_merge($this->config, $override_config);
	}
	
	/**
	 * Check to see if we've pulled the database info for this config already.
	 *
	 * @return bool
	 */
	protected function hasBeenInitialized()
	{
		return is_array($this->config);
	}
	
	/**
	 * Pull database information for {@see $this->property_short}.
	 *
	 * @return void
	 */
	protected function init()
	{
		if ($this->hasBeenInitialized()) return;
		
		$this->constants = array();
		$this->config = array();
		
		$data = array();
				
		// remember: array_merge(a, b) works by overriding a with b
		if ($this->blackbox_type == 'CAMPAIGN')
		{
			$data = $this->mergeInfo(
				$this->getTargetData($this->property_short, 'CAMPAIGN'), $data
			);
		}
				
		// campaigns inherit default values from their targets
		$target = $this->getCampaignTarget($this->property_short);
		if ($target instanceof Blackbox_Models_IReadableTarget)
		{
			$data = $this->mergeInfo(
				$this->getTargetData($target->property_short, 'TARGET'), $data
			);
		}
		
		// TARGETs might have alternate inherited targets listed in their target
		// data to further pull default information from, which allows trans.com stuff.
		if (array_key_exists(LenderAPI_DataKeys::INHERITED_TARGET_KEY, $data))
		{
			$data = $this->mergeInfo(
				$this->getTargetData($data[LenderAPI_DataKeys::INHERITED_TARGET_KEY], 'TARGET'),
				$data
			);
		}
		
		$this->config = $data;
	}
	
	/**
	 * Merge two raw target configs together (does a little magic for campaign
	 * constants). 
	 * 
	 * NOTE: Original campaign constant numerical indexes MAY NOT be respected.
	 * 
	 * @param array $base_array Target config to start with.
	 * @param array $overwrite_array Keys from this array will be overwrite those
	 * in the $base_array
	 * @return array
	 */
	protected function mergeInfo($base_array, $overwrite_array = array())
	{
		$base_array = $this->parseAndStoreConstants($base_array);
		$overwrite_array = $this->parseAndStoreConstants($overwrite_array);
		
		return array_merge($base_array, $overwrite_array);
	}
	
	// note about how we always use our constants primarily, then foreign ones.
	/**
	 * Parses out (removes) 'campaign constant' key/value pairs from a config
	 * array.
	 * 
	 * Note that we always prefer to keep the constants we have now, thus all
	 * subsequent calls to this method after the first are setting DEFAULTS.
	 * 
	 * Campaigns get defaults from targets, targets get optional defaults from 
	 * inherited targets designated by a key in target_data.
	 *
	 * @param array &$config The existing target_data information to remove
	 * constants from
	 * @return array The (possibly modified) target_data passed in.
	 */
	protected function parseAndStoreConstants(array &$config)
	{
		try
		{
			// TRUE in this constructor will unset items on $config if it matches.
			$this->constants = array_merge(
				iterator_to_array(new LenderAPI_ConstantDataSource($config, TRUE)),
				$this->constants
			);
		}
		catch (InvalidArgumentException $e)
		{
			// pass
		}
		
		return $config;
	}
	
	/**
	 * Returns target_data for the specific property/type only.
	 *
	 * @param string $property_short Target/campaign name.
	 * @param string $type 'CAMPAIGN' or 'TARGET', determines which target table
	 * entry to pull information from since targets, campaigns and targetcollections
	 * can all have the same &*@#^%! name.
	 * @return array
	 */
	public function getTargetData($property_short, $type = 'CAMPAIGN')
	{
		$return = array();
		
		$view = new Blackbox_Models_View_TargetData($this->db);
		$views = $view->getDataByPropertyShort($property_short, $type);
		foreach ($views as $target_data)
		{
			$return[$target_data->data_name] = $target_data->data_value;
		}
		return $return;
	}
	
	/**
	 * Obtain the target for a campaign.
	 * @param string $property_short The name of the campaign.
	 * @return Blackbox_Models_IReadableTarget
	 */
	protected function getCampaignTarget($property_short)
	{
		
		$campaign = $this->getCampaignModel($property_short);
		
		$model = new Blackbox_Models_View_TargetCollectionChild($this->db);
		$children = $model->getCollectionTargets($campaign->target_id);

		$child = NULL;

		// this is dumb, but getCollectionTargets() will return an iterator with 1 item [DO]
		foreach ($children as $target)
		{
			$child = $target;
		}

		return $child;
	}
	
	/**
	 * Get the target model for a campaign.
	 * @param string $property_short The name of the campaign.
	 * @return Blackbox_Models_Target
	 */
	protected function getCampaignModel($property_short)
	{
		$type_model = new Blackbox_Models_Reference_BlackboxType($this->db);
		$found_type = $type_model->loadBy(array('name' => 'CAMPAIGN'));
		if (!$found_type)
		{
			throw new Blackbox_Exception("unable to get type id for CAMPAIGN");
		}
		
		$campaign = new Blackbox_Models_Target($this->db);	
		$loaded = $campaign->loadByPropertyShort(
			$property_short, $type_model->blackbox_type_id
		);
		if (!$loaded)
		{
			throw new Blackbox_Exception("unable to load campaign for $property_short");
		}
		return $campaign;
	}
}

?>