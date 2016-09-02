<?php
/**
 * This is a model for SupressionListValues.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class ECash_Models_SuppressionListValues extends ECash_Models_WritableModel
{
	/**
	 * The columns in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'date_created',
			'value_id',		
			'value',
		);
		return $columns;
	}

	/**
	 * The primary key columns
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('value_id');
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'value_id';
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'suppression_list_values';
	}

	public function loadAllValuesForListID($suppression_list_id)
	{
		$db = $this->getDatabaseInstance();

		$query = 'select slv.*
			from suppression_lists as sl
			join suppression_list_revisions as slr ON (slr.list_id = sl.list_id AND slr.status = "active")
			join suppression_list_revision_values as slrv ON (slrv.list_id = sl.list_id AND slrv.revision_id = slr.revision_id)
			JOIN suppression_list_values as slv ON (slv.value_id = slrv.value_id)
			WHERE sl.list_id = ?
			';
		
		$st = DB_Util_1::queryPrepared(
			$db,
			$query,
			array($suppression_list_id)
		);

		return $this->factoryIterativeModel($st, $db);
	}
}
?>