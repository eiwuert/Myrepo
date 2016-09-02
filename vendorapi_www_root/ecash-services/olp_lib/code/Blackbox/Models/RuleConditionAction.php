<?php
/**
 * Reference model for the rule_condition_action table
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class Blackbox_Models_RuleConditionAction extends Blackbox_Models_WriteableModel
{
	const ACTION_SKIP = 'skip';
	const ACTION_REPLACE_WITH_FLAG = 'replace value with flag value';
	const ACTION_REPLACE_WITH_VALUE = 'replace value with specified value';
	const ACTION_MAKE_SKIPPABLE = 'make skippable';
	const ACTION_MAKE_NOT_SKIPPABLE = 'make not skippable';
	const ACTION_RUN_RULE = 'only run when condition met';
	
	/**
	 * Returns a assoc array of constants that are used in an "enum" way to
	 * represent valid values for this model.
	 * 
	 * @return array List of action constant names/values.
	 */
	public static function getValueConstants()
	{
		static $actions;
		
		if (!is_array($actions))
		{
			$constants = new ClassConstants(__CLASS__);
			$actions = $constants->keyStartsWith('ACTION');
		}
		
		return $actions;
	}
	
	/**
	 * Override the parent's setter to make sure only defined constant values are
	 * used as an action for this class.
	 * @param string $name The property to set.
	 * @param mixed $value The value to set this property to.
	 * @return void 
	 */
	public function __set($name, $value)
	{
		if ($name == 'action')
		{
			if (!in_array($value, self::getValueConstants()))
			{
				throw new InvalidArgumentException(
					'action must be a valid constant from ' . __CLASS__
				);
			}
		}
		parent::__set($name, $value);
	}
	
	/**
	 * Get columns for the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_condition_action_id', 'action'
		);
		return $columns;
	}

	/**
	 * Get the name of the table the model represents
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_condition_action';
	}
}
