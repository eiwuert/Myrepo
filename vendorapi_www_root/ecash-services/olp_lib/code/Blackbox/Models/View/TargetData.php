<?php
class Blackbox_Models_View_TargetData extends Blackbox_Models_View_Base
{
	/**
	 * Returns target data when given the property short of a target.
	 * 
	 * @param string $property_short The name of the target to get.
	 * @param string $type one of 'CAMPAIGN', 'COLLECTION' or 'TARGET'
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getDataByPropertyShort($property_short, $type = 'CAMPAIGN')
	{
		$query = "
			SELECT d.*, t.name AS data_name
			FROM target
			JOIN blackbox_type bbtype ON bbtype.blackbox_type_id=target.blackbox_type_id
			JOIN target_data d ON d.target_id=target.target_id
			JOIN target_data_type t ON t.target_data_type_id=d.target_data_type_id
			WHERE target.property_short = ? and bbtype.name = ?
		";
		
		$st = DB_Util_1::queryPrepared($this->db, $query, array($property_short, $type));
		
		return new DB_Models_DefaultIterativeModel_1($this->db, $st, clone $this);
	}
	
	/**
	 * @return array 
	 * @see DB_Models_WritableModel_1::getColumns()
	 */
	public function getColumns ()
	{
		static $columns = array(
			'target_id',
			'target_data_type_id',
			'data_value',
			'data_name',
		);
		
		return $columns;
	}
	/**
	 * Not really used, but required.
	 * @return string 
	 * @see DB_Models_WritableModel_1::getTableName()
	 */
	public function getTableName ()
	{
		return 'target_data';
	}
}
?>
