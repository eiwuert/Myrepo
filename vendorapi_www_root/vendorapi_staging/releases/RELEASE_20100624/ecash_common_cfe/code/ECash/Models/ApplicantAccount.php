<?php
require_once "crypt.3.php";

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ApplicantAccount extends ECash_Models_ObservableWritableModel
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
			);

			return $encrypted_columns;
		}


		public function getColumns()
		{
			static $columns = array(
				'applicant_account_id', 'login', 'password',
				'modifying_agent_id', 'date_modified', 'date_created'
			);
			return $columns;
		}

		public function getColumnData()
		{
			$modified 	= $this->column_data;
			$modified['date_created'] = date('Y-m-d H:i:s', $modified['date_created']);

			// Encrypt data that needs to be super-secret
			$e_cols = $this->getEncryptedColumns();

			foreach ($e_cols as $col_name)
			{
				if ((isset($modified[$col_name])) && (!empty($modified[$col_name])))
				{
					if ($re_encrypt)
						$this->altered_columns[$col_name] = $col_name;

					$modified[$col_name] = crypt_3::Encrypt($modified[$col_name]);
				}
			}

			return $modified;
		}		

		public function setColumnData($column_data)
		{
			//mysql timestamps
			$column_data['date_modified'] = strtotime( $column_data['date_modified']);
			$column_data['date_created']  = strtotime( $column_data['date_created']);

			$e_cols = $this->getEncryptedColumns();

			foreach ($e_cols as $col_name)
			{
				$column_data[$col_name] = crypt_3::Decrypt($column_data[$col_name]);
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
			return array('applicant_account_id');
		}
		public function getAutoIncrement()
		{
			return 'applicant_account_id';
		}
		public function getTableName()
		{
			return 'applicant_account';
		}
	}
?>
