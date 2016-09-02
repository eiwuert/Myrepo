<?php
/**
 * Model of a target that belongs to a collection.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_CollectionTarget extends Blackbox_Models_Target
{
	/**
	 * Gets all the targets/campaigns for a specified collection.
	 *
	 * @param int $target_id
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getCollectionTargets($target_id)
	{
		$query = "
			SELECT
				t.target_id,
				t.property_short,
				t.name,
				t.lender_id,
				t.active,
				t.company_id,
				t.blackbox_type_id,
				tr.weight,
				t.lead_cost
			FROM
				target_relation tr
				INNER JOIN target t
					ON tr.child_id = t.target_id
			WHERE
				tr.target_id = ?
			ORDER BY property_short";

		$db = $this->getDatabaseInstance();
		$st = DB_Util_1::queryPrepared(
			$db,
			$query,
			array($target_id)
		);

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
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
			'lender_id',
			'active',
			'company_id',
			'blackbox_type_id',
			'weight',
			'lead_cost'
		);
		return $columns;
	}

	/**
	 * Returns an array of primary keys
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_id');
	}

	/**
	 * Returns the auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_id';
	}

	/**
	 * Returns the table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target';
	}

	/**
	 * Returns an array of table data
	 *
	 * This is used for inserting and updating the table.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		return $column_data;
	}

	/**
	 * Returns an array of table data
	 *
	 * This is used for retrieving the data from the database and passing it to the application.
	 *
	 * @param unknown_type $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;
	}
}
?>