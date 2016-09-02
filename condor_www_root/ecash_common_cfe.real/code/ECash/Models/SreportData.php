<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportData extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created','sreport_data_id','sreport_id','sreport_type_id','sreport_data','filename','filename_extension'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('sreport_data_id');
		}
		public function getAutoIncrement()
		{
			return 'sreport_data_id';
		}
		public function getTableName()
		{
			return 'sreport_data';
		}
		
				/**
		 * Overrides DB_Models_WritableModel_1's method so we can
		 * gzcompress the the sent_package and received_package columns
		 * before they are stored in a blob
		 * 
		 * @return array
		 */
		public function getColumnData()
		{
			// This method is called twice by canInsert() and insert()
			// so the compression is done both times.  It's not ideal,
			// but oh well.
			
			$data = $this->column_data;
			
			$data['sreport_data'] = pack('L', strlen($this->column_data['sreport_data'])) . gzcompress($this->column_data['sreport_data']);

			return $data;
		}

		/**
		 * Overrides DB_Models_WritableModel_1's method so we can
		 * gzuncompress the the sent_package and received_package columns
		 * 
		 * @param array $data
		 */
		public function setColumnData($data)
		{
			if(isset($data['sreport_data']) && ! empty($data['sreport_data']))
			{
				$data['sreport_data'] = gzuncompress(substr($sreport_data = $data['sreport_data'], 4));
			}
						
			$this->column_data = $data;
		}
		
		
	}
?>
