<?php

/**
 * A rule to check if a property is set on a data source, such as Blackbox_Config.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_PropertySet extends OLPBlackbox_Rule
{
	/**
	 * The name of the property to look for.
	 * @var string
	 */
	protected $flag;
	
	/**
	 * The class name of the data source to check, something available during
	 * {@see isValid()}.
	 * 
	 * Should be a constant from this class.
	 * @var string
	 */
	protected $source_type;
	
	/**
	 * Create new PropertySet rule which will check for a property set on a data
	 * source available during {@see isValid()}.
	 * 
	 * @param string $property The property to check for "setness"
	 * @param string $source_type Indicates where to check for this property,
	 * {@see self::BLACKBOX_DATA} and {@see self::BLACKBOX_CONFIG}
	 * @return void
	 */
	public function __construct($property, $source_type = OLPBlackbox_Config::DATA_SOURCE_BLACKBOX)
	{
		$this->validateSourceType($source_type);
				
		$this->flag = $property;
		$this->source_type = $source_type;
		
		parent::__construct();
	}
	
	/**
	 * Get the name of the property this rule is configured to look for.
	 * @see OLPBlackbox_Rule_IHasValueAccess::getRuleValue()
	 * @return string
	 */
	public function getRuleValue()
	{
		return $this->flag;
	}
	
	/**
	 * Set the property this rule is configured to look for.
	 * @see OLPBlackbox_Rule_IHasValueAccess::setRuleValue()
	 * @param string $value The property name to look for in the data source.
	 * @return void
	 */
	public function setRuleValue($value)
	{
		$this->flag = $value;
	}
	
	/**
	 * Blackbox_Data will be passed in, so the only other factor is whether or 
	 * not we have an OLPBlackbox_Config if that's what we're using.
	 * @param Blackbox_Data $data Application data.
	 * @param Blackbox_IStateData $state_data Blackbox state.
	 * @return bool
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->source_type == OLPBlackbox_Config::DATA_SOURCE_CONFIG)
		{
			return $this->getConfig() instanceof OLPBlackbox_Config;
		}
		
		return TRUE;
	}
	
	/**
	 * Valid if the property exists on the data source this rule is configured 
	 * to look at.
	 * 
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data 
	 * @return bool 
	 * @see Blackbox_Rule::runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->source_type == OLPBlackbox_Config::DATA_SOURCE_CONFIG)
		{
			$source = $this->getConfig();
		}
		elseif ($this->source_type == OLPBlackbox_Config::DATA_SOURCE_BLACKBOX)
		{
			$source = $data;
		}
		elseif ($this->source_type == OLPBlackbox_Config::DATA_SOURCE_STATE)
		{
			$source = $state_data;
		}
		else
		{
			throw new RuntimeException('source type was configured incorrectly');
		}
		
		return isset($source) && isset($source->{$this->flag});
	}
	
	/**
	 * Validates the data source to look at is a class name this rule will have
	 * available during {@see isValid()}.
	 * 
	 * It may be odd to use isValidDataSource, but that's the only 
	 * context in which this rule is used, currently, and it dovetails nicely
	 * with the sources we'll need to look through.
	 * 
	 * @param string $source_type Class name of the data source, should be a
	 * constant from the OLPBlackbox_Config class, for now.
	 * @return bool TRUE if this is a valid data source.
	 */
	protected function validateSourceType($source_type)
	{
		if (!OLPBlackbox_Config::isValidDataSource($source_type))
		{
			throw new InvalidArgumentException(
				strval($source_type) . ' is not a valid source type'
			);
		}
	}
}

?>