<?php
/**
 * This is a model for SupressionListRevisionValues.
 *
 * @author Raymond Lopez <raymond.loez@Sellingsource.com>
 */
class ECash_Models_SuppressionListRevisionValues extends ECash_Models_WritableModel
{
	/**
	 * The columns in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'list_id',
			'revision_id',		
			'value_id'
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
		return array('revision_id');
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'revision_id';
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'suppression_list_revision_values';
	}
}
?>