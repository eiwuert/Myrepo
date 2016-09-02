<?php
/**
 * Blackbox Company Model
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Company extends Blackbox_Models_WriteableModel
{
	/**
	 * Loads the company by the name short
	 *
	 * @param string $name_short
	 * @return boolean
	 */
	public function loadByNameShort($name_short)
	{
		return $this->loadBy(array(
			'name_short' => $name_short
		));
	}
	
	/**
	 * Returns the columns for this table
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'company_id', 'name', 'name_short', 'contact_name', 'email_address',
			'phone_number', 'date_modified', 'date_created'
		);
		return $columns;
	}
	
	/**
	 * Returns the primary keys for the table
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('company_id');
	}
	
	/**
	 * Returns the auto increment column for the table
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'company_id';
	}
	
	/**
	 * Returns the table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'company';
	}
	
	/**
	 * Returns the column data formatted for database insert or update
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
		$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		return $column_data;
	}
	
	/**
	 * Sets the column data formatted for use in scripts
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_modified'] = strtotime($data['date_modified']);
		$this->column_data['date_created'] = strtotime($data['date_created']);
	}
}
?>