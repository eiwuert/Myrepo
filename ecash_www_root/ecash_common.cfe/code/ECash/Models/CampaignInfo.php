<?php

	require_once 'IApplicationFriend.php';
	require_once 'Site.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_CampaignInfo extends ECash_Models_WritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;
		public $Promo;
		public $Site;
		public $Reservation;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'campaign_info_id', 'promo_id',
				'promo_sub_code', 'site_id', 'reservation_id',
				'campaign_name'
			);
			return $columns;
		}
		
		public function getPrimaryKey()
		{
			return array('campaign_info_id');
		}
		
		public function getAutoIncrement()
		{
			return 'campaign_info_id';
		}
		
		public function getTableName()
		{
			return 'campaign_info';
		}

		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
		}

		public function setSiteData(ECash_Models_Site $site)
		{
			$this->site_id = $site->site_id;
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
                }

	}
?>
