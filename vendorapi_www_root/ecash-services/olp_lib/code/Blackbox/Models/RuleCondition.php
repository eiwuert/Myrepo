<?php
/**
 * Rule condition database model
 *
 * @author Matthew Jump <matthew.jump@sellinsource.com>
 */
class Blackbox_Models_RuleCondition extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns the column names for the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_condition_id',
			'rule_condition_type_id',
			'rule_condition_action_id',
			'rule_condition_source_id',
			'flag',
			'value'
		);
		return $columns;
	}

	/**
	 * Returns the name of the table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_condition';
	}

	/**
	 * Gets a single condition row by id
	 *
	 * @param string $rule_condition_id
	 * @return array
	 */
	public function getCondition($rule_condition_id)
	{
		$query = "
			SELECT
				rc.rule_condition_id,
				rct.name,
				rc.flag,
				rca.action,
				rcs.source,
				rc.value
			FROM
				rule_condition rc
				LEFT JOIN rule_condition_action rca ON rca.rule_condition_action_id = rc.rule_condition_action_id
				LEFT JOIN rule_condition_source rcs ON rcs.rule_condition_source_id = rc.rule_condition_source_id
				LEFT JOIN rule_condition_type rct ON rct.rule_condition_type_id = rc.rule_condition_type_id
			WHERE rc.rule_condition_id = ?";

		return DB_Util_1::querySingleRow($this->db, $query, array($rule_condition_id));
	}
}
?>