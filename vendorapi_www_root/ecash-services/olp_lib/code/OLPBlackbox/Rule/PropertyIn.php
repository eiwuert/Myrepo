<?php

class OLPBlackbox_Rule_PropertyIn extends OLPBlackbox_Rule
{
	protected $property;
	protected $values;
	protected $data_source;
		
	/**
	 * 
	 */
	function __construct($property, array $values, $data_source = OLPBlackbox_Config::DATA_SOURCE_CONFIG)
	{
		if (!OLPBlackbox_Config::isValidDataSource($data_source))
		{
			throw new InvalidArgumentException(
				'source must be valid data source, got ' . var_export($data_source, TRUE)
			);
		}
		$this->property = $property;
		$this->values = $values;
		$this->data_source = $data_source;
	}
	
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->source_type == OLPBlackbox_Config::DATA_SOURCE_CONFIG)
		{
			return $this->getConfig() instanceof OLPBlackbox_Config;
		}
		
		return TRUE;
	}
	
	/**
	 * 
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data 
	 * @return bool 
	 * @see Blackbox_Rule::runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->data_source == OLPBlackbox_Config::DATA_SOURCE_BLACKBOX)
		{
			$source = $data;
		}
		elseif ($this->data_source == OLPBlackbox_Config::DATA_SOURCE_CONFIG)
		{
			$source = $this->getConfig();
		}
		elseif ($this->data_source == OLPBlackbox_Config::DATA_SOURCE_STATE)
		{
			$source = $state_data;
		}
		else 
		{
			throw new RuntimeException('impossible state, data source unknown');
		}
		return $source 
			&& isset($source->{$this->property}) 
			&& in_array($source->{$this->property}, $this->values);
	}
	
	/**
	 * @return array
	 */
	public function getRuleValue()
	{
		return $this->values;
	}
	
	public function __toString()
	{
		return sprintf('Rule PropertyIn%s: [%s in (%s)?]',
			$this->getEventName() ? ' (' . $this->getEventName() . ')' : '',
			var_export($this->property, TRUE),
			implode(', ', $this->values)
		);
	}
}

?>