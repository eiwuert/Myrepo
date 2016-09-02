<?php

/**
 * Set state data property.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_SetState extends OLPBlackbox_Rule
{
	/**
	 * Property to set in state data.
	 *
	 * @var string
	 */
	protected $property = '';
	
	/**
	 * Value of property to set in state data.
	 *
	 * @var mixed
	 */
	protected $value = TRUE;
	
	/**
	 * Construct a OLPBlackbox_Rule_SetState rule.
	 *
	 * @param string $property Name of the property to set in state data.
	 * @param mixed $value Value of the property to set in state data.
	 * 
	 * @return void
	 */
	function __construct($property, $value = TRUE)
	{
		if (!is_string($property))
		{
			throw new InvalidArgumentException(sprintf(
				'property passed to %s must be a string',
				__CLASS__)
			);
		}
		$this->property = $property;
		$this->value = $value;
	}
	
	/**
	 * Determines whether this rule can run.
	 *
	 * @param Blackbox_Data $data Information about the application.
	 * @param Blackbox_IStateData $state_data Information about calling ITarget.
	 * 
	 * @return bool whether or not the rule can run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// this rule has no hope of running properly if it can't set the property
		// we've been assigned in state_data
		if (method_exists($state_data, 'getMutableKeys')
			&& in_array($this->property, $state_data->getMutableKeys()))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * We don't need to log anything when this rule is valid.
	 *
	 * @param Blackbox_Data $data Info from application being run.
	 * @param Blackbox_IStateData $state_data Info from calling ITarget.
	 * 
	 * @return bool
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->setEventName('');
		$this->setStatName('');
		parent::onValid($data, $state_data);
	}
	
	/**
	 * Sets a property in the state data.
	 *
	 * @param Blackbox_Data $data Information about the application.
	 * @param Blackbox_IStateData $state_data Information about calling ITarget.
	 * 
	 * @return bool whether the rule has run successfully or not
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$state_data->{$this->property} = $this->value;
		
		return TRUE;
	}
	
	/**
	 * Print out a representation of this object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf(
			"Rule: %s [set %s = %s]\n",
			__CLASS__,
			$this->property,
			strval($this->value)
		);
	}
}

?>
