<?php

/**
 * The model for rule_component_parm.
 *
 * @package Model
 * @author Auto Generated
 */
class ECash_Models_RuleComponentParm extends DB_Models_WritableModel_1
	implements
		ECash_Models_IHasPermanentData
{
	/**
	 * Override this method with one that returns an array of valid
	 * column names.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_modified',
			'date_created',
			'active_status',
			'rule_component_parm_id',
			'rule_component_id',
			'parm_name',
			'parm_subscript',
			'sequence_no',
			'display_name',
			'description',
			'parm_type',
			'user_configurable',
			'input_type',
			'presentation_type',
			'value_label',
			'subscript_label',
			'value_min',
			'value_max',
			'value_increment',
			'length_min',
			'length_max',
			'enum_values',
			'preg_pattern'
		);
	}

	/**
	 * Override this method with one that returns a string containing
	 * the name of your table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_component_parm';
	}

	/**
	 * Override this method to return an array containing the primary key
	 * column(s) for your table.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array(
			'rule_component_parm_id'
		);
	}

	/**
	 * Override this method with one that returns the name of the auto_increment
	 * column in your table. Return NULL if your table does not contain an
	 * auto_increment column.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'rule_component_parm_id';
	}
}
?>