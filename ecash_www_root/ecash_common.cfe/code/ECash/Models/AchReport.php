<?php

class ECash_Models_AchReport extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'date_request', 'company_id', 'ach_report_id',
			'ach_report_request', 'remote_response', 'remote_response_oldkey', 'remote_response_iv', 'report_status', 'report_type',
			'delivery_method', 'content_hash', 'encryption_key_id', 'ach_provider_id'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('ach_report_id');
	}

	public function getAutoIncrement()
	{
		return 'ach_report_id';
	}

	public function getTableName()
	{
		return 'ach_report';
	}

    public function getEncryptedColumns()
    {
        static $encrypted_columns = array(
            'remote_response'
        );

        return $encrypted_columns;
    }
	/**
	 * Modifies the column data before saving it to the DB
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();

			if($column_data['remote_response'] !== NULL)
			{
				$column_data['remote_response_iv'] = md5($column_data['remote_response']);
				$this->altered_columns['remote_response_iv'] = 'remote_response_iv';
			}
		/**
		 * The content_hash column contains an md5 hash of the remote_response column,
		 * which is used for duplicate file checks when manually uploading a return file.
		 * It should be automatically created the first time the model is saved. [BR]
		 */
		if(empty($column_data['content_hash']) && ! empty($column_data['remote_response']))
		{
			$column_data['content_hash'] = md5($column_data['remote_response']);
		}

		        // Encrypt data that needs to be super-secret
        $crypt = new ECash_Models_Encryptor(NULL,NULL,$column_data['remote_response_iv']);
        $re_encrypt = FALSE; // Flag to denote whether we re-encrypted
																			   
																	   
		if ($column_data['encryption_key_id'] != $crypt->getLatestEncryptionKeyVersion())
        {
	        $column_data['encryption_key_id'] = $crypt->getLatestEncryptionKeyVersion();
	        $this->altered_columns['encryption_key_id'] = 'encryption_key_id';
	        $re_encrypt = TRUE;
        }


		$e_cols = $this->getEncryptedColumns();

		foreach ($e_cols as $col_name)
		{
			if ((isset($column_data[$col_name])) && (!empty($column_data[$col_name])))
			{
				if ($re_encrypt)
					$this->altered_columns[$col_name] = $col_name;

				$column_data[$col_name] = $crypt->encrypt($column_data[$col_name]);
			}
		}
		return $column_data;
		
	}
	
	
	public function setColumnData($column_data)
	{
		if ($column_data['encryption_key_id'] != NULL)
      	{
        	$crypt  = new ECash_Models_Encryptor($this->db, NULL, $column_data['remote_response_iv']);
           	$e_cols = $this->getEncryptedColumns();

			foreach ($e_cols as $col_name)
			{
				$column_data[$col_name] = $crypt->decrypt($column_data[$col_name], $column_data['encryption_key_id']);
			}
		}

		$this->column_data = $column_data;
	}

}
?>
