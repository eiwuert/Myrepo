<?php
/**
 * View class to return the values of a suppression list.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_ListValues extends Blackbox_Models_View_Base
{
	/**
	 * Returns the suppression list values for a given ID.
	 *
	 * @param int $list_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getValues($list_id)
	{
		$query = "
			SELECT
				lv.value_id,
				lv.value
			FROM
				list_revisions lr
				INNER JOIN list_revision_values lrv
					ON lr.list_id = lrv.list_id AND lr.revision_id = lrv.revision_id
				INNER JOIN list_values lv
					ON lrv.value_id = lv.value_id
			WHERE
				lr.status = 'ACTIVE'
				AND lr.list_id = ?";
		
		$st = DB_Util_1::queryPrepared($this->db, $query, array($list_id));
		
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
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
				value_id,
				value
			FROM
				list_values
			WHERE
				value IN ({$in_clause})";
		
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
		return 'lists';
	}
	
	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'value_id',
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
		return array('list_id');
	}
}
