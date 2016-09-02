<?php

/** Database model for application_value_field
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLP_Models_References_ApplicationValueField extends OLP_Models_References_ReferenceModel
{
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'application_value_field_id',
			'date_created',
			'name',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('application_value_field_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'application_value_field_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'application_value_field';
	}
	
	/** For the reference model, this is the ID column.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'application_value_field_id';
	}
	
	/** For the reference model, this is the VALUE column.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'name';
	}
}

?>
