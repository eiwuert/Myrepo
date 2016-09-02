<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Site extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData
	{
		/**
		 * Loads an active site record by license key
		 * @param string $license_key
		 * @return bool
		 */
		public function loadByLicenseKey($license_key)
		{
			return $this->loadBy(array(
				'license_key' => $license_key,
				'active_status' => 'active',
			));
		}

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'site_id',
				'name', 'license_key'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('site_id');
		}
		public function getAutoIncrement()
		{
			return 'site_id';
		}
		public function getTableName()
		{
			return 'site';
		}
	}
?>
