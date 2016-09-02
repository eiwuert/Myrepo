<?php

class ECash_Models_Ach extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'application_id',
			'ach_id', 'ach_batch_id', 'ach_report_id', 'origin_group_id', 
			'ach_date', 'amount', 'ach_type', 'bank_aba', 'bank_account', 
			'bank_account_type', 'ach_status', 'ach_return_code_id', 
			'ach_trace_number'
		);
		return $columns;
	}

    public function getEncryptedColumns()
    {
        static $encrypted_columns = array(
            'bank_account'
        );

        return $encrypted_columns;
    }

	public function getColumnData()
	{
		$modified 	= $this->column_data;
		$modified['date_created'] = date('Y-m-d H:i:s', $modified['date_created']);

		// Encrypt data that needs to be super-secret
		$crypt = new ECash_Models_Encryptor($this->db);
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

		return $modified;
	}

	public function setColumnData($column_data)
	{
		//mysql timestamps
		$column_data['date_modified'] = strtotime( $column_data['date_modified']);
		$column_data['date_created']  = strtotime( $column_data['date_created']);

		if ($column_data['encryption_key_id'] != NULL)
		{
			$crypt  = new ECash_Models_Encryptor($this->db);
			$e_cols = $this->getEncryptedColumns();

			foreach ($e_cols as $col_name)
			{
				$column_data[$col_name] = $crypt->decrypt($column_data[$col_name], $column_data['encryption_key_id']);
			} 
		}

		$this->column_data = $column_data;
	}


	public function getPrimaryKey()
	{
		return array('ach_id');
	}
	public function getAutoIncrement()
	{
		return 'ach_id';
	}
	public function getTableName()
	{
		return 'ach';
	}
}
?>
