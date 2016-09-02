<?php
/**
 * Rule condition relation database model
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class Blackbox_Models_RuleConditionRelation extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns the column names for the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_condition_relation_id',
			'rule_id',
			'rule_condition_id'
		);
		return $columns;
	}

	/**
	 * Returns the name of the table
	 *
	 * @return array
	 */
	public function getTableName()
	{
		return 'rule_condition_relation';
	}
}
?>