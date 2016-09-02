<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Customer extends ECash_Models_ObservableWritableModel
	{
		public $Company;
		public $ModifyingAgent;
		
		/**
		 * Temporary place to store the application id for the application service customer write
		 *
		 * @var int
		 */
		public $application_id;

		public function getEncryptedColumns()
		{
			static $encrypted_columns = array(
					'ssn'
			);

			return $encrypted_columns;
		}


		public function getColumns()
		{
			static $columns = array(
				'customer_id', 'company_id', 'ssn', 'login', 'password',
				'modifying_agent_id', 'date_modified', 'date_created', 'ssn_oldkey',
				'encryption_key_id'
			);
			return $columns;
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

					$modified[$col_name]             = $crypt->encrypt($modified[$col_name]);
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
			$this->populateFromAppService();
		}

		protected function populateFromAppService()
		{
			if (!empty($this->column_data['application_id']))
			{
				$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
				$customer_info = $app_client->getApplicantAccountInfo($this->column_data['application_id']);

				if (!empty($customer_info))
				{
					foreach ($customer_info as $key => $value)
					{
						$this->column_data[$key] = $value;
					}
				}
			}
		}

		public function getPrimaryKey()
		{
			return array('customer_id');
		}
		public function getAutoIncrement()
		{
			return 'customer_id';
		}
		public function getTableName()
		{
			return 'customer';
		}
	}
?>
