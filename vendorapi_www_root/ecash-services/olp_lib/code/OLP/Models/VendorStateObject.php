<?php
/**
 * DB table model for the vendor_state_object table.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_Models_VendorStateObject extends OLP_Models_CryptWritableModel
{
	/**
	 * Loads the model by application_id and target_id
	 *
	 * @param int $application_id
	 * @param int $target_id
	 * @return bool
	 */
	public function loadByApplicationTarget($application_id, $target_id)
	{
		return $this->loadBy(array('application_id' => $application_id, 'target_id' => $target_id));
	}
	
	/**
	 * Defined by OLP_Models_WritableModel
	 * 
	 * This adds the state_object column to the process columns.
	 *
	 * @return array
	 */
	public function getProcessedColumns()
	{
		$columns = parent::getProcessedColumns();
		
		$columns['state_object'] = array(self::PROCESS_COMPRESS, self::PROCESS_ENCRYPT);
		
		return $columns;
	}
	
	/**
	 * Defined by DB_Models_WritableModel_1
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'vendor_state_object_id', 'date_modified', 'date_created',
			'application_id', 'target_id', 'state_object'
		);
		return $columns;
	}
	
	/**
	 * Defined by DB_Models_WritableModel_1
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('vendor_state_object_id');
	}
	
	/**
	 * Defined by DB_Models_WritableModel_1
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'vendor_state_object_id';
	}
	
	/**
	 * Defined by DB_Models_WritableModel_1
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'vendor_state_object';
	}
}
