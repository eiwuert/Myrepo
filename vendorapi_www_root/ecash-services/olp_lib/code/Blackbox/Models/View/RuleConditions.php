<?php
/**
 * View class to return the conditions of a rule
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_RuleConditions extends Blackbox_Models_View_Base
{
	/**
	 * Overridden version of loadAllBy() for the special view functionality this
	 * class wants to provide.
	 * 
	 * @param array $where_args List of arguments which must include a 'rule_id'
	 * key and can optionally use an 'action_id' key.
	 * @param array $order_by Unused. TODO: use this.
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function loadAllBy(array $where_args = NULL, array $order_by = NULL)
	{
		if (!array_key_exists('rule_id', $where_args))
		{
			throw new InvalidArgumentException(__METHOD__ . ' requires "rule_id" in where clause.');
		}
		$query = "
			SELECT
				rc.rule_condition_id,
				rct.name AS `type`,
				rc.flag,
				rca.action,
				rcs.source,
				rc.value
			FROM
				rule_condition rc
				LEFT JOIN rule_condition_type rct ON rct.rule_condition_type_id=rc.rule_condition_type_id
				LEFT JOIN rule_condition_action rca ON rca.rule_condition_action_id = rc.rule_condition_action_id
				LEFT JOIN rule_condition_source rcs ON rcs.rule_condition_source_id = rc.rule_condition_source_id
				LEFT JOIN rule_condition_relation rcr ON rcr.rule_condition_id = rc.rule_condition_id
			WHERE rcr.rule_id = ?";

		$params = array($where_args['rule_id']);
		if (!empty($where_args['action_id']))
		{
			$query .= 'AND rca.rule_condition_action_id = ?';
			$params[] = $where_args['action_id'];
		}
		$st = DB_Util_1::queryPrepared($this->db, $query, $params);
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}
	
	/**
	 * Returns the table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_condition';
	}

	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_condition_id',
			'type',
			'flag',
			'action',
			'source',
			'value'
		);
		return $columns;
	}

	/**
	 * Returns the primary key for the table.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('rule_condition_id');
	}
}
