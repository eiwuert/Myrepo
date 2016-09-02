<?php
/**
 * Created by IntelliJ IDEA.
 * User: mikel
 * Date: May 25, 2010
 * Time: 1:58:55 PM
 * To change this template use File | Settings | File Templates.
 */

class VendorAPI_StateObjectMysqlModel extends DB_Models_WritableModel_1
{
	public function getColumns()
	{
		return array('vendor_state_object_id', 'date_modified', 'date_created', 'application_id', 'state_object');
	}

	public function getAutoIncrement()
	{
		return 'vendor_state_object_id';
	}

	public function getTableName()
	{
		return 'vendor_state_object';
	}

	public function getPrimaryKey()
	{
		return array('vendor_state_object_id');
	}

	public function stateObjectExists($application_id)
	{
		$pk = reset($this->getPrimaryKey());
		$query = "SELECT {$pk} FROM {$this->getTableName()} WHERE application_id = ? LIMIT 1";

		$id = DB_Util_1::querySingleValue($this->getDatabaseInstance(), $query, array($application_id));

		return $id;
	}


	public function loadByApplicationId($application_id)
	{
		$query = "SELECT * FROM {$this->getTableName()} WHERE application_id = ? LIMIT 1";
		return $this->loadPrepared($this->getDatabaseInstance(), $query, array($application_id));
	}

	public function setVendorStateObjectId($id)
	{
		$this->column_data['vendor_state_object_id'] = $id;
	}

	/**
	 * Returns the column data.
	 *
	 * Overridden to automatically set the date_queued and date_available
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		if (!empty($column_data['date_created']))
		{
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		}
		if (!empty($column_data['date_modified']))
		{
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
		}
		if (!empty($column_data['state_object']))
		{
			$column_data['state_object'] = base64_encode(serialize($column_data['state_object']));
		}
		return $column_data;
	}

	/**
	 * Sets the column data.
	 *
	 * Overridden to automatically set the date_queued and date_available
	 *
	 * @param Array $column_data
	 * @return null
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;

		if (!empty($data['date_created']))
		{
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
		if (!empty($data['date_modified']))
		{
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
		}
		if (!empty($data['state_object']))
		{
			$this->column_data['state_object'] = unserialize(base64_decode($data['state_object']));
		}
	}
}
