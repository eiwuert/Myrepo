<?php

/**
 * View class to return the events of a rule
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_RuleEvents extends Blackbox_Models_View_Base
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
				re.rule_event_id,
				rt.name AS 'trigger',
				ret.name AS 'event'
			FROM
				rule_event AS re
				LEFT JOIN rule_trigger AS rt ON rt.rule_trigger_id = re.rule_trigger_id
				LEFT JOIN rule_event_type AS ret ON ret.rule_event_type_id = re.rule_event_type_id
			WHERE
				re.rule_id = ?";

		$params = array($where_args['rule_id']);
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
		return 'rule_event';
	}

	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_event_id',
			'trigger',
			'event',
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
		return array('rule_event_id');
	}
}
