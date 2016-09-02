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
		
		public static function getConfigInfoRow($application_id)
		{
			$query = "
			SELECT
				camp.campaign_info_id, 
				camp.promo_id, 
				camp.promo_sub_code, 
				s.name as url,
				s.license_key
			FROM
				application a
			inner join site s on (a.enterprise_site_id = s.site_id)
			inner join campaign_info camp on (camp.application_id = a.application_id) 		
			WHERE
				a.application_id = ?
			AND camp.campaign_info_id = 
				(
					SELECT
						MAX(campaign_info_id) 
					FROM
						campaign_info cref
					WHERE
						cref.application_id = camp.application_id
				)";

			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			if (($row = $base->getDatabaseInstance()->querySingleRow($query, array($application_id))) !== FALSE)
			{
				return $row;
			}
			return NULL;			
		}
		
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
	}
?>
