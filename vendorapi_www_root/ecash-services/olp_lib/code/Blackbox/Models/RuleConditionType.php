<?php

/**
 * Model for the rule_condition_type table in blackbox admin which determines
 * how to build the "trigger" condition for the overall rule condition when being
 * assembled in blackbox.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Models
 */
class Blackbox_Models_RuleConditionType extends Blackbox_Models_WriteableModel
{
	const COND_FLAG_SET = 'when flag is set';
	const COND_FLAG_NOT_SET = 'when flag is not set';
	const COND_ALWAYS = 'always';
	const COND_FLAG_EQ = 'when flag equal to specified value';
	const COND_FLAG_NOT_EQ = 'when flag not equal to specified value';
	const COND_FLAG_IN = 'when flag value in list';
	
	/**
	 * Returns the (string) names and values of constants that are used as "enums"
	 * for the values for this model.
	 * 
	 * There's no constract that enforces that the constants on this class must
	 * be the only things set as "name" values here, but it's implied.
	 * 
	 * @return array Assoc array of (string)name => values for the relevant
	 * constants defined on this class.
	 */
	public static function getValueConstants()
	{
		static $names;
		
		if (!is_array($names))
		{
			$constants = new ClassConstants(__CLASS__);
			$names = $constants->keyStartsWith('COND');
		}
		
		return $names;
	}
	
	/**
	 * Override the parent's setter to make sure only defined constant values are
	 * used as a name for this class.
	 * @param string $name The property to set.
	 * @param mixed $value The value to set this property to.
	 * @return void 
	 */
	public function __set($name, $value)
	{
		if ($name == 'name')
		{
			if (!in_array($value, self::getValueConstants()))
			{
				throw new InvalidArgumentException(
					'name must be a valid constant from ' . __CLASS__
				);
			}
		}
		parent::__set($name, $value);
	}
	
	/**
	 * Columns for this table.
	 * 
	 * @return array Columns on this table. 
	 * @see DB_Models_WritableModel_1::getColumns()
	 */
	public function getColumns()
	{
		return array('rule_condition_type_id', 'name');
	}
	
	/**
	 * Name of the table this represents in the database.
	 * @return string DB name.
	 * @see DB_Models_WritableModel_1::getTableName()
	 */
	public function getTableName()
	{
		return 'rule_condition_type';
	}
}

?>