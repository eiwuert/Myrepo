<?php

/** Database model for customer_history_status
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_References_CustomerHistoryStatus extends OLP_Models_References_ReferenceModel
{
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'customer_history_status_id',
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
		return array('customer_history_status_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'customer_history_status_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'customer_history_status';
	}
	
	/** For the reference model, this is the ID column.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'customer_history_status_id';
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
