<?php
class Blackbox_Models_View_TargetCampaign extends Blackbox_Models_View_Base
{
	/**
	 * Get all targets
	 *
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getAll()
	{
		$query = "
			SELECT
				c.*,
				t.target_id as target_target_id,
				t.property_short as target_property_short,
				t.name as target_name
			FROM
				target_relation tr
				INNER JOIN target c
					ON tr.target_id = c.target_id
				INNER JOIN target t
					ON tr.child_id = t.target_id
				INNER JOIN blackbox_type bbt
					ON (c.blackbox_type_id = bbt.blackbox_type_id) 
			WHERE
				bbt.name = 'CAMPAIGN'";
		
		$st = DB_Util_1::queryPrepared($this->db, $query, array());
		
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}
	
	/**
	 * Gets all the campaigns for a given target ID.
	 *
	 * @param int $target_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getCampaignsByTargetID($target_id)
	{
		$query = "
			SELECT
				c.*,
				t.target_id as target_target_id,
				t.property_short as target_property_short,
				t.name as target_name
			FROM
				target_relation tr
				INNER JOIN target c
					ON tr.target_id = c.target_id
				INNER JOIN target t
					ON tr.child_id = t.target_id
				INNER JOIN blackbox_type bbt
					ON (c.blackbox_type_id = bbt.blackbox_type_id) 
			WHERE
				tr.child_id = ?";
		
		$st = DB_Util_1::queryPrepared($this->db, $query, array($target_id));
		
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}
	
	/**
	 * Returns the table name, which is meaningless.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target';
	}
	
	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'target_id',
			'property_short',
			'name',
			'company_id',
			'lender_id',
			'active',
			'deleted',
			'rule_id',
			'reference_data',
			'paydate_minimum',
			'list_mgmt_nosell',
			'target_target_id',
			'target_property_short',
			'lead_cost',
			'target_name'
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
		return array('target_id');
	}
}
