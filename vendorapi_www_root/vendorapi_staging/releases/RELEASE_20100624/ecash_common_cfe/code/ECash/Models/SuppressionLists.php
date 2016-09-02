<?php
/**
 * This is a model for SupressionLists.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class ECash_Models_SuppressionLists extends ECash_Models_WritableModel
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
			'date_modified',		
			'list_id',
			'company_id',
			'name',
			'field_name',
			'name_short',
			'description',
			'loan_action',
			'active'
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
		return array('list_id');
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'list_id';
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'suppression_lists';
	}
}
?>