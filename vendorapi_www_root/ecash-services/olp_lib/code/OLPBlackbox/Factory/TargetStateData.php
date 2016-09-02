<?php

/**
 * Factory to produce state data objects for targets.
 * 
 * Both Targets and Campaigns need to take entries from the target_data table 
 * in blackbox admin and turn certain ones into state data entries. This factory
 * consolidates this functionality.
 * 
 * The main usage for this class is getTargetStateData() to produce state data
 * but the static method relevantFactoryKeys() is also used by, for example, the
 * {@see OLPBlackbox_Factory_TargetCollection} class to make caching 
 * target_data models where only the needed data is used. 
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_TargetStateData extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Used to make some decisions about how to assemble state data.
	 * @var string
	 */
	const CAMPAIGN_TYPE = 'CAMPAIGN';
	
	/**
	 * Used to make some decisions about how to assemble state data.
	 * @var string
	 */
	const TARGET_TYPE = 'TARGET';
	
	/**
	 * Model for target data (possibly a caching model.)
	 *
	 * @var OLP_IModel
	 */
	protected $target_data_model;
	
	/**
	 * Model for target data types (possibly a caching one.)
	 *
	 * @var OLP_IModel
	 */
	protected $target_data_type_model;
	
	/**
	 * Create a state data factory.
	 *
	 * @param OLP_IModel $target_data_model Model to pull target data with
	 * (possibly a caching one.)
	 * @param OLP_IModel $target_data_type_model Model to pull target data types
	 * with (possibly a caching one!)
	 * @return void
	 */
	public function __construct(OLP_IModel $target_data_model, OLP_IModel $target_data_type_model)
	{
		$this->target_data_model = $target_data_model;
		$this->target_data_type_model = $target_data_type_model;
	}
	
	/**
	 * Produce the keys that targets might be interested in from the target_data
	 * table.
	 *
	 * @return array
	 */
	public static function relevantFactoryKeys()
	{
		return array('eventlog_show_rule_passes');
	}
	
	/**
	 * Create a state data object for a target.
	 *
	 * @param Blackbox_Models_IReadableTarget $target_model The model representing
	 * the target we're producing the state data for.
	 * @return Blackbox_IStateData
	 */
	public function getTargetStateData(Blackbox_Models_IReadableTarget $target_model)
	{
		$type_name = $this->getTargetTypeName($target_model->blackbox_type_id);
		
		if (self::CAMPAIGN_TYPE == $type_name)
		{
			$state_data = new OLPBlackbox_CampaignStateData(array(
				'list_mgmt_nosell' => intval($target_model->list_mgmt_nosell),
				'price_point' => $this->getPricePoint($target_model->property_short),
				'lead_cost' => (float)$target_model->lead_cost
			));
		}
		elseif (self::TARGET_TYPE == $type_name)
		{
			$state_data = new OLPBlackbox_TargetStateData();
		}
		else 
		{
			throw new InvalidArgumentException("cannot assemble state data for type $type_name");
		}
		
		foreach ($this->getTargetData($target_model->target_id) as $key => $value)
		{
			try
			{
				$state_data->$key = $value;
			}
			catch (Exception $e)
			{
				// pass
			}
		}
		
		return $state_data;
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Retrieve target data for a target by ID.
	 *
	 * @param int $target_id
	 * @return array List of key => values extracted from the target data models.
	 */
	protected function getTargetData($target_id)
	{
		$data_types = $this->target_data_type_model->loadAllBy(
			array('name' => self::relevantFactoryKeys())
		);
		
		$data = array();
		
		foreach ($data_types as $type)
		{
			$search_params = array(
				'target_data_type_id' => $type->target_data_type_id,
				'target_id' => $target_id,
			);
			
			if (!$this->target_data_model->loadBy($search_params)) continue;
			
			$data[$type->name] = $this->target_data_model->data_value;
		}
		
		return $data;
	}
	
	/**
	 * Returns the pricepoint/billingrate/leadcost whatever you want to call it for this promo_id and property short
	 *
	 * Stolen from the Campaign factory. [DO]
	 * 
	 * @param string $campaign The name of the campaign to get the price point for.
	 * @return float
	 */
	protected function getPricePoint($campaign)
	{
		$promo_id = $this->getConfig()->promo_id;

		$campaigns = implode('|', CompanyData::getCompanyProperties(CompanyData::COMPANY_CLK));
		// get the lead cost, send 0 if none is found or if its an organic lead
		if (in_array($promo_id, array(10000, 99999))
			|| preg_match("/^({$campaigns})(_ocs)?$/i", $campaign))
		{
			return NULL;
		}

		$olp_db = DB_Connection::getInstance('OLP', $this->getConfig()->mode);
		return BillingRates::getInstance($olp_db)
			->getBillingRate($promo_id, strtolower($campaign));
	}
	
	/**
	 * Shortcut method to get a string name for a blackbox_type_id
	 *
	 * @param int $blackbox_type_id
	 * @return string uppercase translation of the blackbox type like "CAMPAIGN"
	 */
	protected function getTargetTypeName($blackbox_type_id)
	{
		// model factory returned from getModelFactory caches reference tables
		$name = $this->getModelFactory()
			->getReferenceTable('BlackboxType', TRUE)
			->toName($blackbox_type_id);
		
		if (!$name)
		{
			throw new InvalidArgumentException("unknown type id ($blackbox_type_id)");
		}
		return strtoupper($name);
	}
}

?>