<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ExtCollectionsBatch extends ECash_Models_WritableModel
	{
		public $Company;
		
		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'ext_collections_batch_id', 'ec_file_outbound',
				'ec_filename', 'batch_status', 'ext_collections_co',
				'item_count', 'is_adjustment', 'sreport_id', 'external_batch_report_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('ext_collections_batch_id');
		}
		public function getAutoIncrement()
		{
			return 'ext_collections_batch_id';
		}
		public function getTableName()
		{
			return 'ext_collections_batch';
		}

		public function loadByApplicationID($application_id, &$response, array $override_dbs = NULL)
		{
			$query = "
			SELECT  
				ecb.*
			FROM	
				 ext_collections ec LEFT JOIN ext_collections_batch ecb ON ecb.ext_collections_batch_id = ec.ext_collections_batch_id
			WHERE
				ec.application_id = {$application_id}
			";
			
			if (($row = $this->getDatabaseInstance()->querySingleRow($query)) !== FALSE)
			{
				$this->fromDbRow($row);
				ECash_Display_LegacyHandler::loadLimited($row, array('ext_collections_co'), $response);
				return TRUE;
			}

			return FALSE;
		}
		
	}
?>
