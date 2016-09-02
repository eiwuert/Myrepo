<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_PersonalReference extends ECash_Models_WritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;
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
	}
?>
