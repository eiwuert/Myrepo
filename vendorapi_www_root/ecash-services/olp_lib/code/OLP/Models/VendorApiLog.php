<?php

/** Database model for vendorapi_log. Data sent/received is compressed and
 * encrypted.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_VendorApiLog extends OLP_Models_CryptWritableModel implements DB_Models_IReferenceable_1
{
	/** Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable(
			$factory->getReferenceTable(Blackbox_ModelFactory::TARGET_COLLECTION_NAME, FALSE),
			'target_id',
			'property_short'
		);
		$reference_model->addReferenceTable(
			$factory->getReferenceTable('VendorApiMethod'),
			NULL,
			'method_name'
		);
		
		return $reference_model;
	}
	
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'vendorapi_log_id',
			'date_modified',
			'date_created',
			'application_id',
			'target_id',
			'method_id',
			'data_sent',
			'data_received',
			'response_time',
			'success',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('vendorapi_log_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'vendorapi_log_id';
	}
	
	/** Return an array of required values.
	 *
	 * @return array
	 */
	public function getRequiredColumns()
	{
		return array(
			'application_id',
			'target_id',
			'method_id',
			'data_sent',
		);
	}
	
	/** Returns an array of columns that need extra processing.
	 *
	 * @return array
	 */
	public function getProcessedColumns()
	{
		$encryption_mode = array(
			self::PROCESS_SERIALIZE,
			self::PROCESS_COMPRESS,
			self::PROCESS_ENCRYPT,
		);
		
		$processed_columns = array(
			'data_sent' => $encryption_mode,
			'data_received' => $encryption_mode,
		);
		
		// Merge in any processed columns from parent
		$parent_processed_columns = parent::getProcessedColumns();
		if (is_array($parent_processed_columns))
		{
			$processed_columns = array_merge_recursive($parent_processed_columns, $processed_columns);
		}
		
		return $processed_columns;
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'vendorapi_log';
	}
}

?>
