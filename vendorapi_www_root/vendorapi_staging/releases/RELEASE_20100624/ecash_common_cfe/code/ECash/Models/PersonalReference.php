<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_PersonalReference extends ECash_Models_ObservableWritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;
		public $service_personal_reference_id = NULL;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'personal_reference_id', 'name_full',
				'phone_home', 'relationship', 'reference_verified',
				'contact_pref','agent_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('personal_reference_id');
		}
		public function getAutoIncrement()
		{
			return 'personal_reference_id';
		}
		public function getTableName()
		{
			return 'personal_reference';
		}
		
		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
		}

		public function getColumnData()
		{
				$column_data = parent::getColumnData();
				$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
				$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
				return $column_data;
		}

		public function setColumnData($data)
		{
				$this->column_data = $data;
				$this->column_data['date_modified'] = strtotime($data['date_modified']);
				$this->column_data['date_created'] = strtotime($data['date_created']);

				$this->populateFromAppService();
		}

		/**
		 * Make web service calls and override model's data
		 *
		 * @return void
		 */
		protected function populateFromAppService()
		{
			if (!empty($this->application_id))
			{
				$app_client = $this->getAppClient();
				$personal_references = $app_client->getAppPersonalRefs($this->application_id);

				if (empty($personal_references))
				{
					ECash::getLog()->Write("Unable to retrieve personal references from the application service for application_id:" . $this->application_id);
					return;
				}

				foreach ($personal_references as $reference)
				{
					if ((strtolower($reference->name_full) == strtolower($this->column_data['name_full']))
						&& (strtolower($reference->relationship) == strtolower($this->column_data['relationship']))
						&& (strtolower($reference->phone_home) == strtolower($this->column_data['phone_home'])))
					{
						/* save a reference to the app service personal reference id */
						$this->service_personal_reference_id = $reference->personal_reference_id;
						break;
					}
				}

				if (empty($this->service_personal_reference_id))
				{
					ECash::getLog()->Write("Unable to match personal references for application_id:" . $this->application_id);
				}
			}
		}

		protected function getAppClient()
		{
			return ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		}
	}
?>
