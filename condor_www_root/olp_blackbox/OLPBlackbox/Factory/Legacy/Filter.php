<?php

/**
 * Enter description here...
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_Filter
{
	const EMAIL = 'Email';
	const DRIVERS_LICENSE = 'Drivers_License';
	const MICR = 'MICR';
	
	/**
	 * Instance of OLPBlackbox_Factory_Legacy_Filter.
	 *
	 * @var OLPBlackbox_Factory_Legacy_Filter
	 */
	protected static $instance;
	
	/**
	 * Returns an instance of OLPBlackbox_Factory_Legacy_Filter.
	 *
	 * @return OLPBlackbox_Factory_Legacy_Filter
	 */
	public static function getInstance()
	{
		if (!self::$instance instanceof OLPBlackbox_Factory_Legacy_Filter)
		{
			self::$instance = new OLPBlackbox_Factory_Legacy_Filter();
		}
		
		return self::$instance;
	}
	
	/**
	 * Returns a RuleCollection containing the filters passed in to $filters.
	 *
	 * @param array $filters an array of strings containing filter names
	 * @return OLPBlackbox_RuleCollection
	 */
	public function getFilterCollection(array $filters)
	{
		$filter_collection = new OLPBlackbox_RuleCollection();
		$filter_collection->setEventName(OLPBlackbox_Config::EVENT_FILTER_CHECK);
		
		foreach ($filters as $filter_name)
		{
			$filter = $this->getFilter($filter_name);
			if ($filter instanceof OLPBlackbox_Rule_Filter)
			{
				$filter_collection->addRule($filter);
			}
		}
		
		return $filter_collection;
	}
	
	/**
	 * Returns a filter.
	 *
	 * @param string $filter_name a string representing the name of the filter to return
	 * @return OLPBlackbox_Rule_Filter
	 */
	public function getFilter($filter_name)
	{
		switch ($filter_name)
		{
			case self::EMAIL:
				$rule = new OLPBlackbox_Rule_Filter_Email();
				$rule->setEventName(OLPBlackbox_Config::EVENT_FILTER_EMAIL);
				$rule->setStatName(strtolower(OLPBlackbox_Config::EVENT_FILTER_EMAIL));
				break;
			case self::DRIVERS_LICENSE:
				$rule = new OLPBlackbox_Rule_Filter_DriversLicense();
				$rule->setEventName(OLPBlackbox_Config::EVENT_FILTER_DRIVERS_LICENSE);
				$rule->setStatName(strtolower(OLPBlackbox_Config::EVENT_FILTER_DRIVERS_LICENSE));
				break;
			case self::MICR:
				$rule = new OLPBlackbox_Rule_Filter_MICR();
				$rule->setEventName(OLPBlackbox_Config::EVENT_FILTER_MICR);
				$rule->setStatName(strtolower(OLPBlackbox_Config::EVENT_FILTER_MICR));
				break;
		}
		
		return $rule;
	}
}

?>