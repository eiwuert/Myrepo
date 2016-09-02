<?php
/**
 * Extends the Blackbox_Rule class to include a way to set params
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class Blackbox_StandardRule extends Blackbox_Rule
{
	const PARAM_FIELD = 1;
	const PARAM_VALUE = 2;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * Sets up the name for the rule.
	 *
	 */
	public function __construct()
	{
		/**
		 * Right now we just take everything after the standard Blackbox_Rule_
		 * name of the class as the 'name' of the rule.
		 */
		$this->name = str_replace('Blackbox_Rule_', '', get_class($this));
	}

	/**
	 * Overloaded __get method for retrieving class variables.
	 *
	 * @param string $name The name of the class variable to get
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		$value = NULL;

		if (isset($this->{$name}))
		{
			$value = $this->{$name};
		}

		return $value;
	}

	/**
	 * Sets up standard functionality for a rule.
	 * These are used when the rule is being validated.
	 *
	 * @param array $params List of parameters defining this rule
	 *
	 * @return void
	 */
	public function setupRule($params)
	{
		$this->params = $params;
	}

	/**
	 * Returns whether the rule has sufficient data to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return ($this->getDataValue($data) !== NULL);
	}

	/**
	 * Gets the specific value we need to run this rule from Blackbox_Data.
	 * This value is determined by using the PARAM_FIELD index of
	 * the $this->params array.
	 *
	 * @param BlackBox_Data $data Data to run validation checks on
	 *
	 * @return mixed
	 */
	protected function getDataValue(BlackBox_Data $data)
	{
		$value = NULL;

		if (isset($this->params[self::PARAM_FIELD]))
		{
			if (is_array($this->params[self::PARAM_FIELD]))
			{
				foreach ($this->params[self::PARAM_FIELD] as $field)
				{
					$value[$field] = $data->{$field};
				}
			}
			else
			{
				$value = $data->{$this->params[self::PARAM_FIELD]};
			}
		}

		return $value;
	}

	/**
	 * Gets the current value for this rule.
	 * This value is determined by using the PARAM_VALUE index of
	 * the $this->params array.
	 *
	 * @return mixed
	 */
	protected function getRuleValue()
	{
		$value = NULL;

		if (isset($this->params[self::PARAM_VALUE]))
		{
			$value = $this->params[self::PARAM_VALUE];
		}

		return $value;
	}
}
?>
