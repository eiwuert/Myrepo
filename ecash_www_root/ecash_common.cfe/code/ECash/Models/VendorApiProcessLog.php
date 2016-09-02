<?php
/**
 * The vendor api process log  model
 *
 * @package Models
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class ECash_Models_VendorApiProcessLog extends ECash_Models_WritableModel
{
	/**
	 * The vendor api process log in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_modified',
			'date_stored',
			'vendor_api_process_log_id'
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'vendor_api_process_log';
	}

	/**
	 * The primary key vendor api process log
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('vendor_api_process_log_id');
	}

	/**
	 * The auto increment tags
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return array('vendor_api_process_log_id');
	}
}
