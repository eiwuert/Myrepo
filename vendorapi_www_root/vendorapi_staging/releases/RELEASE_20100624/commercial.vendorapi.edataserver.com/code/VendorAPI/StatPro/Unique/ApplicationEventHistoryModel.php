<?php
/**
 * Model for StatPro Application Uniquesness that represents individual event
 * records
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_StatPro_Unique_ApplicationEventHistoryModel
	extends DB_Models_ObservableWritableModel_1
{
	/**
	 * @see DB_Models_ObservableWritableModel_1#getColumns
	 */
	public function getColumns()
	{
		return array("application_stat_unique_id",
			"application_id",
			"stat_name_id",
			"date_created",
			"date_modified");
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getTableName
	 */
	public function getTableName()
	{
		return "application_stat_unique";
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getPrimaryKey
	 */
	public function getPrimaryKey()
	{
		return array("application_stat_unique_id");
	}
	
	/**
	 * @see DB_Models_ObservableWritableModel_1#getAutoIncrement
	 */
	public function getAutoIncrement()
	{
		return "application_stat_unique_id";
	}
}