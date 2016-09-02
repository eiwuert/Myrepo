<?php
ini_set("include_path",ini_get("include_path").":/virtualhosts/ecash_common/");

require_once('ECash/CFE/AsynchEngine.php');
require_once('ECash/CFE/AsynchResult.php');

class Blackbox_CFE
{
	const CFE_EVENT = 'CFE_RULES';
	
	protected $property_short;
	protected $lead_data;
	protected $config;
	protected $enterprise_data;
	protected $mode;
	protected $applog;
	
	public function __construct($property_short, $lead_data, $config, $mode = NULL)
	{
		$this->property_short = $property_short;
		$this->lead_data = $lead_data;
		$this->config = $config;
		$this->enterprise_data = Enterprise_Data::getEnterpriseData(Enterprise_Data::resolveAlias($this->property_short));
		$this->applog = Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $this->config->site_name, APPLOG_ROTATE, APPLOG_UMASK);
		if(is_null($mode) && defined('BFW_MODE'))
		{
			$mode = BFW_MODE;
		}
		$this->mode = $mode;
	}
	
	/**
	 * Map all the data in lead_data to an array suitable for
	 * use with the CFE AsyncEngine.
	 *
	 * @return array
	 */
	protected function getAsyncData()
 	{
 		$return = array(
 			'ip_address'         => $this->lead_data['client_ip_address'],
 			'name_first'         => $this->lead_data['name_first'],
 			'name_last'          => $this->lead_data['name_last'],
 			'email'              => $this->lead_data['email_primary'],
 			'phone_home'         => $this->lead_data['phone_home'],
 			'phone_work'         => $this->lead_data['phone_work'],
 			'phone_cell'         => $this->lead_data['phone_cell'],
 			'phone_work_ext'     => empty($this->lead_data['ext_work']) ? NULL : $this->lead_data['ext_work'],
 			'call_time_pref'     => $this->lead_data['best_call_time'],
 			'street'             => $this->lead_data['home_street'],
 			'unit'               => $this->lead_data['home_unit'],
 			'city'               => $this->lead_data['home_city'],
 			'state'              => $this->lead_data['home_state'],
 			'zip_code'           => $this->lead_data['home_zip'],
 			'employer_name'      => $this->lead_data['employer_name'],
 			'date_hire'          => $this->lead_data['date_hire'],
 			'legal_id_number'    => $this->lead_data['state_id_number'],
 			'legal_id_state'     => empty($this->lead_data['state_issued_id']) ? $this->lead_data['home_state'] : $this->lead_data['state_issued_id'],
 			'legal_id_type'      => 'dl',
 			'income_direct_deposit' => ((strtoupper($this->lead_data['income_direct_deposit']) == 'TRUE') ? 'yes' : 'no'),
 			'income_source'      => $this->lead_data['income_type'],
 			'income_frequency'   => $this->lead_data['paydate_model']['income_frequency'],
 			'bank_name'          => $this->lead_data['bank_name'],
 			'bank_account_type'  => $this->lead_data['bank_account_type'],
 			'income_monthly'     => $this->lead_data['income_monthly_net'],
 			'ssn'                => $this->lead_data['social_security_number'],
 			'dob'                => $this->lead_data['dob'],
 			'bank_aba'           => $this->lead_data['bank_aba'],
 			'bank_account'       => $this->lead_data['bank_account'],
 			'paydate_model'      => $this->lead_data['paydate_model']['model_name'],
 			'olp_process'        => $this->getOLPProcess(),
 			'application_id'     => $this->GetApplicationId(),
 			'track_id'           => (isset($_SESSION['statpro']['track_key'])) ? $_SESSION['statpro']['track_key'] : NULL,
 			'phone_fax'          => empty($this->lead_data['phone_fax']) ? NULL : $this->lead_data['phone_fax'],
 			'application_type'   => 'paperless',
 			'income_monthly'     => $this->lead_data['income_monthly_net'],
 			'is_react'           => ((isset($_SESSION['is_react']) && $_SESSION['is_react'] === true) || isset($this->lead_data['react']) || isset($this->lead_data['lead_data']['reckey'])) ? 'yes' : 'no',
 			'pwadvid'            => (isset($this->lead_data['pwadvid'])) ? $this->lead_data['pwadvid'] : NULL,
 			'enterprise_site_id' => $this->getEcashSiteId($this->enterprise_data['license'][$this->mode])
 		);
 		return $return;
 	}
 	
 	/**
 	 * Takes a licesne key and returns the site_id from
 	 * LDB.
 	 *
 	 * @param string $license_key
 	 * @return int
 	 */
 	protected function getEcashSiteId($license_key)
	{
		$pdo = Setup_DB::Get_PDO_Instance('mysql', $this->mode, $this->enterprise_data['property_short']);

		$query = 'SELECT site_id FROM site WHERE license_key = ?';
		$stmt = $pdo->queryPrepared($query, array($license_key));
		$row = $stmt->fetch(PDO::FETCH_OBJ);
		return $row->site_id;
	}
	
	protected function getEcashCompanyId($property_short)
	{
		$pdo = Setup_DB::Get_PDO_Instance('mysql', $this->mode, $this->enterprise_data['property_short']);
		$query = 'SELECT company_id FROM company WHERE name_short = ?';
		$stmt = $pdo->queryPrepared($query, array($property_short));
		$row = $stmt->fetch(PDO::FETCH_OBJ);
		return $row->company_id;
	}
	public function Run()
	{
		$valid = NULL;
		$cfe_db = Setup_DB::Get_PDO_Instance(
			'mysql',
			$this->mode,
			$this->enterprise_data['property_short']
		);
		$async_data = $this->getAsyncData();
		$cfe = new ECash_CFE_AsynchEngine($cfe_db, $this->getEcashCompanyId($this->enterprise_data['property_short']));
		$cfe_result = $cfe->beginExecution($async_data, false);

		
		if ($cfe_result->getIsValid())
		{
			// access attributes that were changed during execution
			$attr = $cfe_result->getAttributes();
			$_SESSION['cfe_attributes'] = $attr;
			$valid = TRUE;
		}
		else
		{
			$valid = FALSE;
		}
		// this will be passed to endExecution in import_pending
		$this->SaveAsynchResult($cfe_result);
		return $valid;
		
	}
	public function SaveAsynchResult($result)
 	{
 		$asynch_result = mysql_escape_string(gzcompress(serialize($result)));
 		$application_id = $this->getApplicationId();
 		
 		if(!empty($application_id))
 		{
			$db = Setup_DB::Get_Instance('BLACKBOX', $this->mode, $this->property_short);
			$target_id = $this->getTargetId();
			$query = "INSERT INTO
				asynch_result
				(
					application_id,
					date_created,
					asynch_result_object,
					mode,
					target_id
				)
				VALUES
				(
					'$application_id',
					NOW(),
					'$asynch_result',
					'{$this->config->bb_mode}',
					'$target_id'
				)
				ON DUPLICATE KEY UPDATE
					asynch_result_object = VALUES(asynch_result_object)
			";
 					
 			$db->Query($this->olp_db->db_info['db'],$query);
 		}
 	}
 	
 	/**
 	 * Get the target id for the property short of this property_short thing
 	 *
 	 * @return unknown
 	 */
 	protected function getTargetId()
 	{
 		$db = Setup_DB::Get_Instance('BLACKBOX', $this->mode, $this->property_short);
 		$query = 'SELECT
 			target_id 
 		FROM
 			target
 		WHERE
 			property_short = \''.$this->property_short.'\'
 		AND
 			status = \'ACTIVE\'
 		AND
 			deleted = \'FALSE\'';
 		try 
 		{
 			$target_id = FALSE;
 			$res = $db->Query($db->db_info['db'], $query);
 		
 			if (($row = $db->Fetch_Object_Row($res)))
 			{
 				$target_id = $row->target_id;
 			}
 		}
 		catch (Exception $e)
 		{
 			$this->applog->Write('EXCEPTION '.$e->getMessage());
 		}
 		return $target_id;
 		
 	}

	protected function GetApplicationId()
	{
		$app_id = FALSE;;
		if(isset($this->lead_data['application_id']) && is_numeric($this->lead_data['application_id']))
		{
			$app_id = $this->lead_data['application_id'];
		}
		 elseif(isset($_SESSION["application_id"]) && is_numeric($_SESSION["application_id"]))
        {
            $app_id = $_SESSION["application_id"];
        }
        elseif(isset($_SESSION["cs"]["application_id"]) && is_numeric($_SESSION["cs"]["application_id"]))
        {
           $app_id = $_SESSION["cs"]["application_id"];
        }
        elseif(isset($_SESSION['transaction_id']) && is_numeric($_SESSION['transaction_id']))
        {
        	$app_id = $_SESSION['transaction_id'];
        }
		return $app_id;
	}
	
	protected function getOLPProcess()
	{
		$db = Setup_DB::Get_Instance('BLACKBOX', $this->mode, $this->enterprise_data['property_short']);
		$acm = new App_Campaign_Manager($db, $db->db_info['db'], $this->applog);
		return $acm->Get_Olp_Process($this->GetApplicationId());
	}
}
