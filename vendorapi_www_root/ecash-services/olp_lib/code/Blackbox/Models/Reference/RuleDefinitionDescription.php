<?php
/**
 * Reference model for the rule_definition table for descriptions.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_Models_Reference_RuleDefinitionDescription extends Blackbox_Models_Reference_Model
{
	/**
	 * Returns an array of the column names.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_definition_id', 'description'
		);
		return $columns;
	}
	
	/**
	 * Returns the primary keys.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('rule_definition_id');
	}
	
	/**
	 * Returns the auto increment column.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'rule_definition_id';
	}
	
	/**
	 * Returns the table name.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_definition';
	}
	
	/**
	 * Returns the column used for the ID of the reference table.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'rule_definition_id';
	}

	/**
	 * Returns the column used for the name of the reference table.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'description';
	}
}
