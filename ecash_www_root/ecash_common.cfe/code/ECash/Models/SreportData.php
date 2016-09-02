<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportData extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created','sreport_data_id','sreport_id','sreport_data_type_id','sreport_data',
				'sreport_data_oldkey', 'sreport_data_iv', 'filename','filename_extension', 'encryption_key_id'
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
        public function getEncryptedColumns()
        {
                static $encrypted_columns = array(
                        'sreport_data'
                );

                return $encrypted_columns;
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
			
			if($data['sreport_data'] !== NULL)
			{
				$data['sreport_data_iv'] = md5($data['sreport_data']);
				$this->altered_columns['sreport_data_iv'] = 'sreport_data_iv';
			}
			// Encrypt data that needs to be super-secret
            $crypt = new ECash_Models_Encryptor($this->db,NULL,md5(pack('L', strlen($this->column_data['sreport_data'])) . gzcompress($this->column_data['sreport_data'])));
            $re_encrypt = FALSE; // Flag to denote whether we re-encrypted
																			   
			if ($data['encryption_key_id'] != $crypt->getLatestEncryptionKeyVersion())
            {
                $data['encryption_key_id'] = $crypt->getLatestEncryptionKeyVersion();
                $this->altered_columns['encryption_key_id'] = 'encryption_key_id';
                $re_encrypt = TRUE;
            }
	
	
			$e_cols = $this->getEncryptedColumns();
	
			foreach ($e_cols as $col_name)
			{
				if ((isset($data[$col_name])) && (!empty($data[$col_name])))
				{
					if ($re_encrypt)
						$this->altered_columns[$col_name] = $col_name;
	
					$data[$col_name] = $crypt->encrypt($data[$col_name]);
				}
			}
			
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
		if ($data['encryption_key_id'] != NULL)
      	{
        	$crypt  = new ECash_Models_Encryptor($this->db, NULL, $data['sreport_data_iv']);
           	$e_cols = $this->getEncryptedColumns();

			foreach ($e_cols as $col_name)
			{
				$data[$col_name] = $crypt->decrypt($data[$col_name], $data['encryption_key_id']);
			}
		}
			
			if(isset($data['sreport_data']) && ! empty($data['sreport_data']))
			{
				$data['sreport_data'] = gzuncompress(substr($data['sreport_data'], 4));
			}
						
			$this->column_data = $data;
		}
		
		
	}
?>
