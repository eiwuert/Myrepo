<?php
/**
 * Rule database model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Rule extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns the columns for the rule model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_id', 'rule_collection_class_id', 'blackbox_type_id',
			'rule_definition_id', 'name', 'rule_value', 'date_created',
			'rule_action'
		);
		return $columns;
	}
	
	/**
	 * Returns the primary keys as an array of strings.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('rule_id');
	}
	
	/**
	 * Returns the auto increment column if any.
	 *
	 * @return string|NULL
	 */
	public function getAutoIncrement()
	{
		return 'rule_id';
	}
	
	/**
	 * Returns the name of the table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule';
	}
	
	/**
	 * Returns the data for each column in the table.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		return $column_data;
	}
	
	/**
	 * Sets the data for the columns in the table.
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_created'] = strtotime($data['date_created']);
	}
	
	/**
	 * Returns an interative model of Blackbox_Models_Rule models based on the rule ID and mode ID given.
	 *
	 * @param int $rule_id
	 * @param int $rule_mode_type_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getActiveRules($rule_id, $rule_mode_type_id)
	{
		$query = "
			SELECT
				r.*
			FROM
				rule_revision rev
				INNER JOIN rule_relation rel
					ON rev.rule_id = rel.rule_id AND rev.rule_revision_id = rel.rule_revision_id
				INNER JOIN rule r
					ON rel.child_id = r.rule_id
				INNER JOIN rule_definition rd 
					ON rd.rule_definition_id = r.rule_definition_id
				INNER JOIN rule_mode rm
					ON r.rule_id = rm.rule_id
			WHERE
				rev.rule_id = ?
				AND rev.active = 1
				AND rm.rule_mode_type_id = ?
			ORDER BY rd.name_short = 'lender_post' ASC";
		// TODO: this order by should be replaced with a rule ordering system
		
		$st = DB_Util_1::queryPrepared($this->db, $query, array($rule_id, $rule_mode_type_id));
		
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}

	/**
	 * Returns a single rule type from a rule collection
	 *
	 * @param string $rule_collection_id
	 * @param string $rule_definition_id
	 * @return array
	 */
	public function getActiveRule($rule_collection_id, $rule_definition_id)
	{
		$query = "
			SELECT
				r.*
			FROM
				rule_revision rev
				INNER JOIN rule_relation rel
					ON rev.rule_id = rel.rule_id AND rev.rule_revision_id = rel.rule_revision_id
				INNER JOIN rule r
					ON rel.child_id = r.rule_id
				INNER JOIN rule_definition rd 
					ON rd.rule_definition_id = r.rule_definition_id
			WHERE
				rev.rule_id = ?
				AND rev.active = 1
				AND rd.rule_definition_id = ?
			LIMIT 1";

		return DB_Util_1::querySingleRow($this->db, $query, array($rule_collection_id, $rule_definition_id));
	}
}
?>
