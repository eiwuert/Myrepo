<?php
/**
 * Database model for the ListValues table.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_ListValues extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns an array of columns for the table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'value_id', 'value', 'date_created'
		);
		return $columns;
	}
	
	/**
	 * Returns an array of the primary keys.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('value_id');
	}
	
	/**
	 * Returns the auto increment column.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'value_id';
	}
	
	/**
	 * Returns the table name.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'list_values';
	}
	
	/**
	 * Returns the column data as an array.
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
	 * Sets the column data from an array.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_created'] = strtotime($data['date_created']);
	}
}
?>