<?php
/**
 * Duplicate lead rule uses memcache to verify if the rule has sold.  If it hasn't
 * it will set the keys to identify that it has.  This rule must run just prior to post
 * to have the proper affect as it will return false positives if the post does not occur
 * after the rule is run.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Rule_DuplicateLead extends OLPBlackbox_Rule
{
	const MEMCACHE_KEY_ROOT = "/olp/blackbox/rule/duplicateLead/";
	const MEMCACHE_KEY_EXPIRE = 300;
	
	/**
	 * Override constructor to make rule skippable
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setSkippable(TRUE);
	}
	
	/**
	 * Override to always return true
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !((bool)$this->getConfig()->do_datax_rework);
	}

	/**
	 * @see OLPBlackBox::runRule
	 * @param Blackbox_Data $data The data used to validate the rule.
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{

		$property_short = $this->getPropertyShort($state_data);

		// If it's a duplicate lead, fail the rule
		if ($this->isDuplicateLead($data, $property_short))
		{
			$valid = FALSE;
		}
		else
		{
			$this->setKeys($data, $property_short);
			$valid = TRUE;
		}

		return $valid;
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		/**
		 * If the lead has been sold to this company already
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_COMPANY);
		}
	}

	/**
	 * Determine if the current data comprises a duplicate lead based on the
	 * provided data.
	 *
	 * @param Blackbox_Data $data
	 * @param string $property_short
	 * @return bool
	 */
	protected function isDuplicateLead(Blackbox_Data $data, $property_short)
	{
		$duplicate = FALSE;

		// The items to check are saved in the rule value
		foreach ($this->getDataKeys() as $check)
		{ 
			$value = $data->{$check};
			// If the check element exists in blackbox data, check memcache
			if (!empty($value))
			{
				$key = $this->getMemcacheKey($property_short, $check, $value);
				
				if ($this->getMemcache()->get($key) !== FALSE)
				{
					$duplicate = TRUE;
					break;
				}
			}
		}
		return $duplicate;
	}

	/**
	 * Get the data keys to process from the rule data
	 *
	 * @return array
	 */
	protected function getDataKeys()
	{
		$rule_value = $this->getRuleValue();
		if (isset($rule_value['data_keys']) && is_array($rule_value['data_keys']))
		{
			$keys = $rule_value['data_keys'];
		}
		else
		{
			$keys = array();
		}
		return $keys;
	}

	/**
	 * Set the memcache keys with the data provided
	 *
	 * @param Blackbox_Data $data
	 * @param string $property_short
	 * @return void
	 */
	protected function setKeys(Blackbox_Data $data, $property_short)
	{
		foreach ($this->getDataKeys() as $check) {
			// If the check element exists in blackbox data, check memcache
			$value = $data->$check;
			if (!empty($value))
			{
				$key = $this->getMemcacheKey($property_short, $check, $data->$check);
				$this->getMemcache()->set($key, TRUE, $this->getMemcacheExpire());
			}
		}
	}

	/**
	 * Get the property short from the state data.  We want the property
	 * short of the level the rule is being run at so we need to check 
	 * for the objects that can run rules in order of precedence to not
	 * return incorrect values
	 *
	 * @param Blackbox_IStateData $state_data
	 * @return unknown
	 */
	protected function getPropertyShort(Blackbox_IStateData $state_data)
	{
		if (isset($state_data->campaign_name))
		{
			$property_short = $state_data->campaign_name;
		}
		elseif (isset($state_data->target_name))
		{
			$property_short = $state_data->target_name;
		}
		elseif (isset($state_data->target_collection_name))
		{
			$property_short = $state_data->target_collection_name;
		}
		else
		{
			// If we can't determine a property_short, this is exceptional behavior
			throw new Blackbox_Exception("Unable to determine property_short for rule");
		}
		
		return $property_short;
	}

	/**
	 * Build a memcache key with the values provided
	 *
	 * @param string $property_short Property short the rule is running for 
	 * @param string $data_element Name of the data element of the corresponding value
	 * @param string $value Value of the data element
	 * @return unknown
	 */
	protected function getMemcacheKey($property_short, $data_element, $value)
	{
		return self::MEMCACHE_KEY_ROOT . md5($property_short . $data_element . $value);
	}

	/**
	 * Get a Cache_Memcache object
	 *
	 * @return Cache_Memcache
	 */
	protected function getMemcache()
	{
		return Cache_Memcache::getInstance();
	}

	/**
	 * Get the memcache expire value
	 *
	 * @return int
	 */
	protected function getMemcacheExpire()
	{
		return self::MEMCACHE_KEY_EXPIRE;
	}
	
}

?>