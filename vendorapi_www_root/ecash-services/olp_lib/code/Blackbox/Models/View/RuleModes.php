<?php
/**
 * View class to return the modes of a rule
 *
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_RuleModes extends Blackbox_Models_View_Base
{
	/**
	 * Returns the modes for a given rule
	 *
	 * @param int $rule_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getModes($rule_id)
	{
		$query = "
			SELECT
				b.rule_mode_type_id,
				name
			FROM
				rule_mode a
				JOIN rule_mode_type b ON a.rule_mode_type_id = b.rule_mode_type_id
			WHERE
				a.rule_id = ?";

		$st = DB_Util_1::queryPrepared($this->db, $query, array($rule_id));

		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}

	/**
	 * Returns the modes for a given rule
	 *
	 * @param int $rule_id
	 * @return array
	 */
	public function getModeList($rule_id)
	{
		$query = "
			SELECT
				name
			FROM
				rule_mode a
				JOIN rule_mode_type b ON a.rule_mode_type_id = b.rule_mode_type_id
			WHERE
				a.rule_id = ?";

		$rs = DB_Util_1::querySingleColumn($this->db, $query, array($rule_id));
		return is_array($rs) ? $rs : array();
	}

	/**
	 * Returns an interable model of ListValues models based on the where.
	 *
	 * @param array $values
	 * @return DB_Models_IterativeModel_1
	 */
	public function getAllByValues(array $values)
	{
		$in_clause = trim(str_repeat('?, ', count($values)), ' ,');

		$query = "
			SELECT
				rule_mode_type_id,
				name
			FROM
				rule_mode_type
			WHERE
				name IN ({$in_clause})";

		$st = DB_Util_1::queryPrepared($this->db, $query, $values);

		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}

	/**
	 * Returns the table name, which is meaningless.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_mode';
	}

	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_mode_type_id',
			'name'
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
		return array('rule_mode_type_id');
	}
}
