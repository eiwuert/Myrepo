<?php

class ECash_Models_BankAccount extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'bank_account_id','bank_account',  'bank_account_oldkey', 'bank_account_iv', 'encryption_key_id'
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

		// Encrypt data that needs to be super-secret
		$crypt = new ECash_Models_Encryptor($this->db, NULL, $modified['bank_account_iv']);
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
			$crypt  = new ECash_Models_Encryptor($this->db,NULL, $column_data['bank_account_iv']);
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
		return array('bank_account_id');
	}
	public function getAutoIncrement()
	{
		return 'bank_account_id';
	}
	public function getTableName()
	{
		return 'bank_account';
	}
	
	public function getBankAccountIdByBankAccount($bank_account_number)
	{
		$encryptor = new ECash_Models_Encryptor(NULL,NULL,substr($bank_account_number,0,5));
		$bank_account_numbers = $encryptor->encryptWithAllActiveKeys($bank_account_number);
		
		
		$query = "
			SELECT  bank_account_id
			FROM bank_account ba
			WHERE bank_account IN " . $encryptor->generatePreparedInBlock($bank_account_number) . "
			";

			$prep_args   = $encryptor->encryptWithAllActiveKeys($bank_account_number);

			$st = DB_Util_1::querySingleValue($this->db, $query, $prep_args);

		if(empty($st))
		{
			$key = $encryptor->getLatestEncryptionKeyVersion();
			$bank_account = new self($this->getDatabaseInstance());
			$bank_account->bank_account = $encryptor->encrypt($bank_account_number,$key);
			$bank_account->bank_account_oldkey = $encryptor->encrypt($bank_account_number,$key);
			$bank_account->bank_account_iv = substr($bank_account_number, 0, 4);
			$bank_account->encryption_key_id = $encryptor->getLatestEncryptionKeyVersion();
			
			$bank_account->insert();
			
			$bank_account_id = $bank_account->bank_account_id;
		}
		else
		{
			$bank_account_id = $st;
			
		}
		return $bank_account_id;
	}
	
}
?>
