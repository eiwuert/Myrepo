<?php

/**
 * This is a model for SupressionListRevisions.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class ECash_Models_SuppressionListRevisions extends ECash_Models_WritableModel
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
			'revision_id',
			'status'
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
		return 'suppression_list_revisions';
	}
}
?>