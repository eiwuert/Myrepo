<?php

class ECash_Models_AchBatch extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'ach_batch_id', 'ach_file_outbound','ach_file_outbound_oldkey', 'ach_file_outbound_iv',
			'remote_response', 'batch_status', 'batch_type', 'encryption_key_id'
		);
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('ach_batch_id');
	}
	
	public function getAutoIncrement()
	{
		return 'ach_batch_id';
	}
	
	public function getTableName()
	{
		return 'ach_batch';
	}

	public function getEncryptedColumns()
	{
		static $encrypted_columns = array(
			'ach_file_outbound'
		);

		return $encrypted_columns;
	}

	public function getColumnData()
	{
		$modified = $this->column_data;
		if($modified['ach_file_outbound'] !== NULL)
		{
			$modified['ach_file_outbound_iv'] = md5($modified['ach_file_outbound']);
			$this->altered_columns['ach_file_outbound_iv'] = 'ach_file_outbound_iv';
		}
		
		// Encrypt data that needs to be super-secret

		$crypt = new ECash_Models_Encryptor(NULL,NULL,$modified['ach_file_outbound_iv']);
		$re_encrypt = FALSE; // Flag to denote whether we re-encrypted

		if ($modified['encryption_key_id'] != $crypt->getLatestEncryptionKeyVersion())
		{
			$modified['encryption_key_id'] = $crypt->getLatestEncryptionKeyVersion();
			$this->altered_columns['encryption_key_id'] = 'encryption_key_id';
			$re_encrypt = TRUE;
		}

		$e_cols = $this->getEncryptedColumns();

		foreach ($e_cols as $col_name)
		{
			if ((isset($modified[$col_name])) && (!empty($modified[$col_name])))
			{
				if ($re_encrypt)
					$this->altered_columns[$col_name] = $col_name;

				$modified[$col_name] = $crypt->encrypt($modified[$col_name]);
			}
		}

		$modified['date_created'] = date('Y-m-d H:i:s', $modified['date_created']);

		return $modified;
	}

	public function setColumnData($column_data)
	{
		if ($column_data['encryption_key_id'] != NULL)
      	{
        	$crypt  = new ECash_Models_Encryptor($this->db, NULL, $column_data['ach_file_outbound_iv']);
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
