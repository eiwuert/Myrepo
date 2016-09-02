<?php
/**
 * Model for StatPro Application Uniquesness that represents event names
 * records
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_StatPro_Unique_EventNameModel
	extends DB_Models_ObservableWritableModel_1
{
	/**
	 * @see DB_Models_ObservableWritableModel_1#getColumns
	 */
	public function getColumns()
	{
		return array("stat_name_id",
			"name",
			"active_status",
			"date_created",
			"date_modified");
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getTableName
	 */
	public function getTableName()
	{
		return "stat_name";
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getPrimaryKey
	 */
	public function getPrimaryKey()
	{
		return array("stat_name_id");
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getAutoIncrement
	 */
	public function getAutoIncrement()
	{
		return "stat_name_id";
	}
}