<?php

	require_once 'WritableModel.php';
	require_once 'IApplicationFriend.php';

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Demographics extends ECash_Models_WritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'has_income', 'has_minimum_income',
				'has_checking', 'minimum_age', 'opt_in', 'us_citizen',
				'ca_resident_agree', 'email_agent_created', 'tel_app_proc'
			);
			return $columns;
		}

		public function getPrimaryKey()
		{
			return array('application_id');
		}

		public function getAutoIncrement()
		{
			return null;
		}

		public function getTableName()
		{
			return 'demographics';
		}

		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
		}


		public function getColumnData()
		{
			$this->column_data['date_created'] = date('Y-m-d H:i:s', $this->column_data['date_created']);
			return $this->column_data;
		}

	}
?>
