<?php
/**
 * Import applications into parallel that failed to import with the regular
 * script
 *
 * @author Jason Gabriele
 */
ini_set('include_path', '.:/virtualhosts:'.ini_get('include_path'));
define('BFW_OLP_DIR', '/virtualhosts/bfw.1.edataserver.com/include/modules/olp/');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
define('OLP_DIR', BFW_OLP_DIR);
define('PASSWORD_ENCRYPTION', 'ENCRYPT');
//Applog info
define('APPLOG_SIZE_LIMIT', '1000000000');
define('APPLOG_FILE_LIMIT', '20');
define('APPLOG_ROTATE', TRUE);
define('APPLOG_OLE_SUBDIRECTORY','ldb');
define('APPLICATION','ldb');

require_once('mysql.4.php');
require_once('mysqli.1.php');
require_once('null_session.1.php');
require_once('applog.singleton.class.php');
require_once(BFW_CODE_DIR . 'server.php');
require_once(BFW_CODE_DIR . 'setup_db.php');
require_once(BFW_CODE_DIR . 'failover_config.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
require_once(BFW_OLP_DIR . 'app_campaign_manager.php');
require_once(BFW_OLP_DIR . 'authentication.php');
//require_once(BFW_OLP_DIR . 'olp.mysql.class.php');
require_once(BFW_OLP_DIR . 'olp_ldb/olp_ldb.php');
require_once(BFW_OLP_DIR . 'payroll.php');

class Import_LDB
{
	private $mode = null;

	private $olp_db;	// OLP DB Info
	private $ldb_db;	// eCash/LDB DB Info
	private $olp_sql;	// OLP SQL connection
	private $ldb_sql;	// eCash/LDB SQL connection

	private $applog;		// Everyone's favorite applog!
	private $ent_prop_list = null;
	private $session = null;

	private $unsynced_apps = array();

	/**
	 * Lock File Path
	 * @var string Lock file
	 */
	private $lock_file_path = "/tmp/importldb_p";

	/**
	 * Synced Statuses
	 * @var int
	 */
	private $synced_status = null;
	private $un_synced_status = null;

	private $props = array();

	/**
	 * Import_LDB constructor.
	 */
	public function __construct($mode, $props = array())
	{
		$this->mode = strtoupper($mode);
		$this->props = $props;

		$crypt_config = Crypt_Config::Get_Config($this->mode);
		$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'], $crypt_config['IV']);

		$this->ent_prop_list = array (
			"PCL" => array(
				"site_name" => "oneclickcash.com",
				"license" => array (
							'LIVE' => '1f1baa5b8edac74eb4eaa329f14a03619f025e2000e0a7b26429af2395f847ce',
							'RC' => '1f1baa5b8edac74eb4eaa329f14a03610a2177d7a01cd1a59258c95fdb31f87b',
							'LOCAL' => '1f1baa5b8edac74eb4eaa329f14a0361604521a4b54937ed3385eb0b5e274b2a'
							),
				"legal_entity" => "One Click Cash",
				"fax" => "8008039136",
				"phone" => "800-230-3266",
				"db_type" => "mysql",
				'use_verify_queue',
				),
			"UCL" =>array(
				"site_name" => "unitedcashloans.com",
				"license" => array (
							'LIVE' => 'd386ac4380073ed7d193e350851fe34f',
							'RC' => 'd63c6aaf39e22727c6438daf81f3a603',
							'LOCAL' => '060431565db8215c0e44bd345a339cbe',
							),
				"legal_entity" => "United Cash Loans",
				"fax" => "8008038794",
				"phone" => "800-279-8511",
				"db_type" => "mysql",
				'use_verify_queue',
				),
			"CA" => array(
				"site_name" => "ameriloan.com",
				"license" => array (
							'LIVE' => 'b8f225e1a2865c224d55c98cf85d399a',
							'RC' => '2b76c04f9a36630314691f5b7d40825a',
							'LOCAL' => 'b11647308d21180eb2e424ef6d4cae5a',
							),
				"legal_entity" => "Ameriloan",
				"fax" => "8002569166",
				"db_type" => "mysql",
				"phone" => "800-362-9090",
				'use_verify_queue',
				),
			"UFC" => array(
				"site_name" => "usfastcash.com",
				"license" =>  array (
								'LIVE' => '11041e0365baa557ec768915a501faab',
								'RC' => 'f5b522467891c35bdf29db4365e8b253',
								'LOCAL' => '2704c44311fc6383ed880c1c057a3bdf',
								),
				"legal_entity" => "US Fast Cash",
				"fax" => "8008038796",
				"phone" => "800-640-1295",
				"db_type" => "mysql",
				'use_verify_queue',
				),
			"D1"=>array(
				"site_name" => "500fastcash.com",
				"license" => array (
								'LIVE' => '38652e89cffb810a98577dd04c8daf43',
								'RC' => 'adfc593c968599f7f406aa84c0fa8a55',
								'LOCAL' => 'bc599acd75dd875d5a33a597d68af14a',
							),
				"legal_entity" => "500 Fast Cash",
				"fax" => "8003614540",
				"db_type" => "mysql",
				"phone"=>"888-919-6669",
				'use_verify_queue',
				),
			"IC" => array(
				"site_name" => "impactcashusa.com",
				"license" => array (
								'LIVE' => '6acd9423b6a2c32813e85d3705fd5300',
								'RC' => '7d83d14e88f63a492e7375a6de460eb2',
								'LOCAL' => '74cb58689fb09537cb37effafb06ba3b',
							),
				"legal_entity" => "Impact Cash",
				"fax" => "888-430-5140",
				"db_type" => "mysql",
				"phone"=>"800-707-0102",
				'use_verify_queue',
			),
			
			
			/** AGEAN **/
			'PCAL' => array(
				'site_name' => 'payday-cash-advance-loans.com',
				'license' => array(
					'LOCAL' => '7d29fcdadfa5a24e511bde4b27a6cf44',
					'RC'	=> '641ee7d882d4824a2add921403644446',
					'LIVE'	=> 'e8d2e221ee13f2a169ecf4595cf65d6d'
				),
				'legal_entity' => 'Payday Cash Advance Loans',
				'fax' => '800-979-4741',
				'db_type' => 'mysql',
				'phone' => '800-979-4740',
				'use_verify_queue',
				'new_ent' => false,
				'use_soap' => true,
				'property_short' => 'PCAL'
			),
			'MYDY' => array(
				'site_name' => 'maydaypayday.com',
				'license' => array(
					'LOCAL' => 'a80e5156691985808b599ad9b2808b8e',
					'RC'	=> '5648b7d0e8ae55071017e5e6fc240b32',
					'LIVE'	=> 'e90ef0bcc6aa6bae334a90de633a06a8'
				),
				'legal_entity' => 'Mayday Payday',
				'fax' => '800-979-1952',
				'db_type' => 'mysql',
				'phone' => '800-979-1951',
				'use_verify_queue',
				'new_ent' => false,
				'use_soap' => true,
				'property_short' => 'MYDY'
			),
			'CBNK' => array(
				'site_name' => 'cashbanc.com',
				'license' => array(
					'LOCAL' => '1c2ed2ebd72bee75810a88bb7f365eeb',
					'RC'	=> '9b157fdfcf7f4e3f4a2ae414a06ce83f',
					'LIVE'	=> 'c2a64defa50c44daab32a56d470cfb7f'
				),
				'legal_entity' => 'CashBanc',
				'fax' => '800-979-0825',
				'db_type' => 'mysql',
				'phone' => '800-979-0823',
				'use_verify_queue',
				'new_ent' => false,
				'use_soap' => true,
				'property_short' => 'CBNK'
			),
			'JIFFY' => array(
				'site_name' => 'jiffycash.com',
				'license' => array(
					'LOCAL' => '59ccb562c56db2624f90714584b57a70',
					'RC'	=> 'f48601b0b17df7609fa8ba5933616399',
					'LIVE'	=> '624d4123d5cc557377267d5df511bed1'
				),
				'legal_entity' => 'Jiffy Cash',
				'fax' => '800-979-4809',
				'db_type' => 'mysql',
				'phone' => '800-979-4808',
				'use_verify_queue',
				'new_ent' => false,
				'use_soap' => true,
				'property_short' => 'JIFFY'
			),
			'MICR' => array(
				'site_name' => 'online-micro-loans.com',
				'license' => array(
					'LOCAL' => 'ebf6b5db4fb0e3fe06c12fc5d74d11d9',
					'RC'	=> 'd47929949ea4951a214662b501d21811',
					'LIVE'	=> '1ec4c3c9df5cf443dfac9ad90d3804b4'
				),
				'legal_entity' => 'Online Microloans',
				'fax' => '800-979-0832',
				'db_type' => 'mysql',
				'phone' => '800-979-0830',
				'use_verify_queue',
				'new_ent' => false,
				'use_soap' => true,
				'property_short' => 'MICR'
			),
		);
	}

	/**
	 * Runs the import_ldb script.
	 */
	public function Run()
	{
		//Create Applog
		$this->applog = Applog_Singleton::Get_Instance('ldb', 1000000, 20, 'Import Parallel', TRUE);

		//Check for Lock
		if($this->Is_Locked())
		{
		 	$this->Log_Error("The lock file is still in place. Skipping this run.");
			exit(1);
		}
		else
		{
			$this->Lock();
		}
		
		try 
		{
			$this->Setup_OLP_DB();
		}
		catch(Exception $e)
		{
			$this->Send_Error_Out($e->getMessage());
		 	$this->Log_Error("DB Connection Error " . $e->getMessage());
		 	$this->Un_Lock();
			exit(1);
		}
		
		//Get Unsynced Apps
		$this->Get_Unsynced_Apps();
		if(empty($this->unsynced_apps))
		{
			$this->Un_Lock();
			exit(0);
		}

		//Go through them and copy over the status history
		$num_apps = count($this->unsynced_apps);
		for($x = 0; $x < $num_apps; $x++)
		{
			$app = $this->unsynced_apps[$x];
			
			//echo "Syncing app " . $app['application_id'] . "\n";
			
			try {
				//Start new session
				$this->Get_Session_Info($app['session_id']);
			}
			catch(Exception $e) 
			{
				//If session error attempt to close session and continue
				$this->Log_Error("Error with App " . $app['application_id'] . ": " . $e->getMessage());
				unset($this->unsynced_apps[$x]);
				@session_write_close();
				unset($_SESSION);
				continue;
			}
			
			if($_SESSION['config']->use_new_process == TRUE)
			{
				//Setup LDB Connection
				$this->Setup_LDB_DB($app['property_short']);

				try
				{
					//Insert Application into LDB
					$this->Insert_Application($app);
				}
				catch(Exception $e)
				{
					$this->Log_Error("Error with App " . $app['application_id'] . ": " . $e->getMessage());
					unset($this->unsynced_apps[$x]);
				}
			}
			session_write_close();
			unset($_SESSION);
		}

		$this->Un_Lock();
	}

	/**
	 * Inserts an application into LDB.
	 *
	 * @param object $application
	 */
	private function Insert_Application($application)
	{
		$auth = new Authentication($this->olp_sql, $this->olp_db, $this->applog);
		$authentication['authentication'] = $auth->Get_Records($application['application_id']);
		// NEW - BrianF - Will need to add the track hash to the session so we can get to it here.
		// It might already exist in the session?
		$authentication['authentication']['trackhash'] = $_SESSION['datax']['trackhash'];

		$olp_data = $this->Get_App_Data_From_OLP($application['application_id']);

		// Start the transaction data, why this is done 3 or 4 times is beyond me
		$transaction_data = array_merge($_SESSION['data'], $olp_data, $authentication);

		// Get the Campaign Info records
		$app_campaign_manager = new App_Campaign_Manager($this->olp_sql, $this->database, $this->applog);
		$transaction_data['campaign_info'] = $app_campaign_manager->Get_Campaign_Info($application['application_id']);
		$transaction_data['olp_process'] = $app_campaign_manager->Get_Olp_Process($application['application_id']);

		// Add the config
		$transaction_data['config'] = $_SESSION['config'];

		// Enterprise license and site name
		$transaction_data['ent_config']->license = $this->ent_prop_list[strtoupper($application['property_short'])]['license'][strtoupper($_SESSION['config']->mode)];
		$transaction_data['ent_config']->site_name = $this->ent_prop_list[strtoupper($application['property_short'])]['site_name'];

		// Check for a react and add the application_id
		// NEW - BrianF - Will need to add the $_SESSION['is_react'] to the session, look in olp.php for this similar
		// line in Process_First_Tier()
		if($_SESSION['is_react']===TRUE) $transaction_data['react'] = TRUE;
		
		//Check if OC react
		if(isset($_SESSION['calculated_react'])) $transaction_data['calculated_react'] = TRUE;

		//Put in App ID
		$transaction_data['application_id'] = $application['application_id'];

		// Grab the track_key
		$transaction_data['track_key'] = strlen( $_SESSION['statpro']['track_key'] ) ? "{$_SESSION['statpro']['track_key']}" : 'null';

		//Put in status times
		$transaction_data['status_times'] = $this->Get_Status_Dates($application['application_id']);

		//Put property short in
		$transaction_data['property_short'] = $application['property_short'];

		//Put in condor doc id
		$transaction_data['condor_doc_id'] = $this->Get_Condor_Doc_ID($application['application_id']);

		//Clean Phone Numbers
		$transaction_data['phone_home'] = str_replace("-","",$transaction_data['phone_home']);
		$transaction_data['phone_work'] = str_replace("-","",$transaction_data['phone_work']);
		$transaction_data['phone_fax'] = str_replace("-","",$transaction_data['phone_fax']);
		$transaction_data['phone_cell'] = str_replace("-","",$transaction_data['phone_cell']);

		//Get updated qualify info
		$qualify_info = $this->Get_Correct_Fund_Info($application['application_id']);

		if(!empty($qualify_info))
		{
			$transaction_data['qualify_info'] = $qualify_info;
		}

		if($_SESSION['is_DNL'])
		{
			$dnl_tags = $this->Import_DNL_Tags($application['application_id']);
		}

			
		$this->Insert_Transaction($transaction_data, FALSE);
	}


	private function Insert_Transaction($transaction_data, $send_email = TRUE)
	{
		// Create the transaction
		$olp_mysql = OLP_LDB::Get_Object($transaction_data['property_short'], $this->ldb_sql);
		//new OLP_MySQL($this->ldb_sql, FALSE);
		$olp_mysql->Create_Transaction($transaction_data, $send_email);
		
		//Insert React Data
		if($transaction_data['react'])
		{ 
			if(isset($_SESSION['react']['transaction_id']))
			{
				$olp_mysql->Insert_React_Affiliation($transaction_data['application_id'], 
												     $_SESSION['react']['transaction_id'], 
												     $transaction_data['property_short']);
			}
			elseif(isset($_SESSION['data']['react_app_id']))
			{
				$olp_mysql->Insert_React_Affiliation($transaction_data['application_id'], 
												     intval($_SESSION['data']['react_app_id']), 
												     $transaction_data['property_short']);
			}
		}
	}
	
	
	/**
	*	Collects the Do Not Loan tags for an app from the OLP db and
	*	inserts them into ldb.
	*
	*	@param int Application ID
	*	@return array(tag_id, application_id, date_created)
	*/
	private function Import_DNL_Tags($application_id)
	{
		$tags = NULL;
		$query = "SELECT 
					tag_name,
					application_id,
					app.date_created
				FROM
					application_tags as app
				JOIN
					application_tag_details
				USING
					(tag_id)
				WHERE
					application_id = '{$application_id}'
				";
		$result = $this->QueryOLP($query);

		while($row = $this->olp_sql->Fetch_Array_Row($result))
		{
			$tags[] = $row;
		}
		
		if(!is_null($tags))
		{
			foreach($tags as $tag)
			{
				if(strlen($tag['tag_name']) > 0)
				{
					$query = "INSERT INTO
							application_tags
							(tag_id,
							application_id,
							created_date)
						VALUES(
							(SELECT tag_id FROM application_tag_details WHERE tag_name = '{$tag['tag_name']}'),
							'{$tag['application_id']}',
							'{$tag['date_created']}'
							)";
				}
				try
				{
					$result = $this->ldb_sql->Query($query);
				}
				catch(Exception $e){}
			}
		}
		
	}
	
	/**
	*	Grabs the loan information from OLP, since the data
	*	in the session may not be accurate if they change
	*	the amount on the confirmation page.
	*
	*	@param int Application ID
	*	@return array(fund_date, payoff_date, fund_amount, net_pay, finance_charge, apr, total_payments)
	*/
	private function Get_Correct_Fund_Info($application_id)
	{
		$qualify_info = array();

		$query = "SELECT
					DATE_FORMAT(estimated_fund_date, '%Y-%m-%d') AS fund_date,
					DATE_FORMAT(estimated_payoff_date, '%Y-%m-%d') AS payoff_date,
					fund_amount,
					finance_charge,
					total_payments,
					apr,
					pay_frequency,
					monthly_net
				FROM
					loan_note
					INNER JOIN income USING (application_id)
				WHERE
					loan_note.application_id = {$application_id}";

		$result = $this->QueryOLP($query);

		if($this->olp_sql->Row_Count($result) !== 0)
		{
			$qualify_info = $this->olp_sql->Fetch_Array_Row($result);

			switch (strtoupper($qualify_info['pay_frequency']))
			{
				case 'WEEKLY':
					$qualify_info['net_pay'] = round(($qualify_info['monthly_net'] * 12) / 52);
					break;

				case 'BI_WEEKLY':
					$qualify_info['net_pay'] = round(($qualify_info['monthly_net'] * 12) / 26);
					break;
					
				case 'TWICE_MONTHLY':
					$qualify_info['net_pay'] = round($qualify_info['monthly_net'] / 2);
					break;

				case 'MONTHLY':
					$qualify_info['net_pay'] = $qualify_info['monthly_net'];
					break;
			}

			unset($qualify_info['pay_frequency']);
		}

		return $qualify_info;
	}


	/**
	 * Gets Status History Dates
	 *
	 * Get status histories for the application id specified
	 * @param int Application ID
	 * @return array 0=>array("name","date","loan"), 1=>array("name","date","loan")
	 */
	private function Get_Status_Dates($application_id)
	{
		$status_histories = array();

		$application_id = (int)$application_id;

		$query = "SELECT s.name, h.application_status_id, h.date_created
				  FROM status_history as h, application_status as s
				  WHERE h.application_id = {$application_id}
				  	AND h.application_status_id = s.application_status_id
				  ORDER BY status_history_id ASC";

		$result = $this->QueryOLP($query);

		$x = 0;
		while($row = $this->olp_sql->Fetch_Object_Row($result))
		{
			if($row->name == 'ldb_unsynched' || $row->name == 'ldb_synched') continue;

			$status_histories[$x] = array("name" => $row->name,
									      "date" => $row->date_created);

			//If verification or underwriting grab the loan actions
			if($row->name == 'verification' || $row->name == 'underwriting')
			{
				//Grab loan actions
				$query = "SELECT action_name
					  	  FROM application_loan_action
					      WHERE application_id = {$application_id}";
	
				$loan_actions = $this->QueryOLP($query);
				
				if($this->olp_sql->Row_Count($loan_actions) != 0)
				{
					$status_histories[$x]["loan"] = array();
					while($la = $this->olp_sql->Fetch_Object_Row($loan_actions))
					{
						$status_histories[$x]["loan"][] = $la->action_name;
					}
				}
			}

			if(!isset($status_histories[$x]["loan"])) $status_histories[$x]["loan"] = null;

			++$x;
		}

		return $status_histories;
	}

	/**
	 * Get Condor Doc ID
	 * @param int Application ID
	 * @return int Condor Doc ID
	 */
	private function Get_Condor_Doc_ID($application_id)
	{
		$application_id = (int)$application_id;

		$query = "SELECT
				   document_id
				  FROM
				   application_documents
				  WHERE application_id = " . $application_id;
		
		$result = $this->QueryOLP($query);
		
		//If no entry return 0
		if($this->olp_sql->Row_Count($result) == 0) return NULL;

		$d = $this->olp_sql->Fetch_Object_Row($result);

		return $d->document_id;
	}

	/**
	 * Get Property Short
	 * @param int Application ID
	 * @return string Property Short
	 */
	private function Get_Property_Short($application_id)
	{
		$application_id = (int)$application_id;

		$query = "SELECT
				   t.property_short
				  FROM
				   target as t,
				   application as a
				  WHERE a.target_id = t.target_id
					AND a.application_id = " . $application_id;
		
		$result = $this->QueryOLP($query);
		
		$p = $this->olp_sql->Fetch_Object_Row($result);

		return $p->property_short;
	}

	private function Get_Prop_IDs()
	{
		$props = implode("','", $this->props);
		$query = "SELECT target_id FROM target WHERE property_short IN ('{$props}')";
		$result = $this->QueryOLP($query);

		$ids = array();
		while($row = $this->olp_sql->Fetch_Object_Row($result))
		{
			$ids[] = $row->target_id;
		}

		return implode(',', $ids);
	}

	/**
	 * Get Unsynced Apps
	 *
	 * Retrieves all applications that have not been sync'd out to an ldb
	 * database and stores them in $this->unsynced_apps.
	 */
	private function Get_Unsynced_Apps()
	{
		if(!empty($this->props))
		{
			$ids = $this->Get_Prop_IDs();
			$prop_where = "AND a.target_id IN ($ids)";

			if(count($this->props) == 1)
			{
				$this->Setup_LDB_DB($this->props[0]);
			}
		}
		else
		{
			$prop_where = '';
		}
		
		$olp_apps = array();
		$olp_app_ids = array();
		$ldb_apps = array();

		$query = "SELECT
				   h.application_id,
				   h.date_created,
				   a.session_id
				  FROM
				   status_history as h,
				   application as a
				  WHERE a.application_id = h.application_id
					AND h.application_status_id = " . $this->Get_Synced_Status() . "
					AND h.date_created > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
					#AND h.date_created BETWEEN '20071010080000' AND '20071012151000'
					{$prop_where}
				  ORDER BY date_created";

		$result = $this->QueryOLP($query);
		
		while(($row = $this->olp_sql->Fetch_Object_Row($result)))
		{
			$p_short = $this->Get_Property_Short($row->application_id);

			$app = array('application_id' => $row->application_id,
						 'date_created'   => $row->date_created,
						 'session_id'     => $row->session_id,
						 'property_short' => $p_short);

			$olp_apps[$row->application_id] = $app;
			$olp_app_ids[] = $app['application_id'];
		}

		if(empty($olp_app_ids)) return false;
		
		//Check if apps are in ldb
		$query = "SELECT application_id FROM application WHERE application_id IN (" . 
				 implode(",", $olp_app_ids) . ")";
		
		$result = $this->QueryLDB($query);
		
		while(($row = $result->Fetch_Array_Row()))
		{
			$ldb_apps[] = $row['application_id'];
		}
		
		$unsynced = array_diff($olp_app_ids, $ldb_apps);
		
		//Finally, add them to the unsynced apps
		foreach($unsynced as $app)
		{
			$this->unsynced_apps[] = $olp_apps[$app];
		}
	}

	/**
	 * Update Synced Applications
	 *
	 * Updates applications which have put into LDB
	 * @return boolean True on success
	 */
	private function Update_Synced_Apps()
	{
		$synced_id = $this->Get_Synced_Status();
		$unsynced_id = $this->Get_Un_Synced_Status();

		foreach($this->unsynced_apps as $app)
		{
			$query = "UPDATE status_history
					  SET application_status_id = {$synced_id}
					  WHERE application_status_id = {$unsynced_id}
						AND application_id = {$app['application_id']}";
				
			$result = $this->QueryOLP($query);
		}
		return true;
	}

	/**
	 * Get Synced Status
	 *
	 * Get the status id for synced
	 * @return int
	 */
	private function Get_Synced_Status()
	{
		if(!isset($this->synced_status))
		{
			//Get synced status id
			$query = "SELECT application_status_id, name
					  FROM application_status
					  WHERE name = 'ldb_synched'
						 OR name = 'ldb_unsynched'";
				
		    $result = $this->QueryOLP($query);
			
			//If for some reason status ids are not in there return
			if($this->olp_sql->Row_Count($result) == 0)
			{
				$this->Un_Lock();
				throw new Exception("Could not find status IDs");
			}

			while($row = $this->olp_sql->Fetch_Object_Row($result))
			{
				if($row->name == "ldb_unsynched")
				{
					$this->un_synced_id = (int)$row->application_status_id;
				}
				else
				{
					$this->synced_id = (int)$row->application_status_id;
				}
			}
		}

		return $this->synced_id;
	}

	/**
	 * Get Un Synced Status
	 *
	 * Get the status id for un synced
	 * @return int
	 */
	private function Get_Un_Synced_Status()
	{
		if(!isset($this->un_synced_status))
		{
			$this->Get_Synced_Status();
		}

		return $this->un_synced_id;
	}

	/**
	 * Sets up our OLP and LDB database connections.
	 */
	private function Setup_LDB_DB($prop_short = null)
	{
		$this->ldb_sql = Setup_DB::Get_Instance('mysql', 'PARALLEL', $prop_short);
		$this->ldb_db = $this->ldb_sql->db_info['db'];
	}
	
	private function Setup_OLP_DB()
	{
		$this->olp_sql = Setup_DB::Get_Instance("blackbox", $this->mode);
		$this->olp_db = $this->olp_sql->db_info["db"];
	}

	/**
	 * Query OLP DB
	 * @param string Query
	 */
	private function QueryOLP($query)
	{
		try 
		{
			return $this->olp_sql->Query($this->olp_db, $query);
		}
		catch(Exception $e)
		{
			$this->Send_Error_Out($e->getMessage());
		 	$this->Log_Error("DB Connection Error " . $e->getMessage());
		 	$this->Un_Lock();
			exit(1);
		}
	}
	
	/**
	 * Query LDB DB
	 * @param string Query
	 */
	private function QueryLDB($query)
	{
		try 
		{
			return $this->ldb_sql->Query($query);
		}
		catch(Exception $e)
		{
			$this->Send_Error_Out($e->getMessage());
		 	$this->Log_Error("DB Connection Error " . $e->getMessage());
		 	$this->Un_Lock();
			exit(1);
		}
	}
	
	/**
	 * Send Error Out 
	 * @param string Echo message to standard out
	 */
	private function Send_Error_Out($message)
	{
		if($this->mode == "LIVE" || $this->mode == "LOCAL") echo $message;
	}

	/**
	 * Log Error
	 * @param string Message
	 */
	private function Log_Error($message)
	{
		$this->applog->Write($message);
	}

	/**
	 * Lock Process
	 * @return boolean Return true if lock is successful
	 */
	private function Lock()
	{
		if(!$this->Is_Locked())
		{
			if(!touch($this->lock_file_path)) return false;
			return true;
		}
		return true;
	}

	/**
	 * Un Lock Process
	 * @return boolean Return true if unlock is successful
	 */
	private function Un_Lock()
	{
		if($this->Is_Locked())
		{
			if(!unlink($this->lock_file_path)) return false;
			return true;
		}
		return true;
	}

	/**
	 * Is Process Locked
	 * @return boolean Return true if process is successful
	 */
	private function Is_Locked()
	{
		return file_exists($this->lock_file_path);
	}
	
	/**
	 * Get our session information. We don't care about locks, we just want to read
	 * the current information. So we aren't using Session_8.
	 *
	 * @param string $session_id
	 */
	private function Get_Session_Info($session_id)
	{
		$session_table = 'session_'.strtolower(substr($session_id, 0, 1));
		
		$query = "
			SELECT session_info
			FROM $session_table
			WHERE session_id = '$session_id'";
		
		$result = $this->QueryOLP($query);
		
		if(($row = $this->olp_sql->Fetch_Object_Row($result)))
		{
			$session_info = gzuncompress($row->session_info);
		}
		
		$this->session = new Null_Session_1();
		
		session_set_save_handler
		(
			array(&$this->session, 'Open'),
			array(&$this->session, 'Close'),
			array(&$this->session, 'Read'),
			array(&$this->session, 'Write'),
			array(&$this->session, 'Destroy'),
			array(&$this->session, 'Garbage_Collection')
		);
		
		@session_start();
		session_decode($session_info);
	}

	
	/**
		Gets data from the OLP database and formats it so it looks
		like post data from the customer.
	*/
	private function Get_App_Data_From_OLP($application_id)
	{
		$data = array();

		$query = "
			SELECT
				first_name		AS name_first,
				middle_name		AS name_middle,
				last_name		AS name_last,
				email			AS email_primary,
				home_phone		AS phone_home,
				cell_phone		AS phone_cell,
				work_phone		AS phone_work,
				fax_phone		AS phone_fax,
				work_ext		AS ext_work,

				date_of_birth	AS dob,
				social_security_number AS ssn,

				address_1		AS home_street,
				city			AS home_city,
				state			AS home_state,
				zip				AS home_zip,
				apartment		AS home_unit,

				employer		AS employer_name,
				drivers_license_number	AS state_id_number,

				direct_deposit	AS income_direct_deposit,
				income_type,
				pay_frequency	AS income_frequency,
				bank_name,
				account_number	AS bank_account,
				routing_number	AS bank_aba,
				monthly_net		AS income_monthly_net,
				bank_account_type,

				paydate_model_id	AS paydate_model,
				IFNULL(ELT(day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'), 'SUN') AS day_of_week,
				DATE_FORMAT(pay_date_1, '%Y-%m-%d') AS next_paydate,
				day_of_month_1,
				day_of_month_2,
				week_1,
				week_2,

				DATE_FORMAT(pay_date_1, '%Y-%m-%d') AS pay_date_1,
				DATE_FORMAT(pay_date_2, '%Y-%m-%d') AS pay_date_2,
				DATE_FORMAT(pay_date_3, '%Y-%m-%d') AS pay_date_3,
				DATE_FORMAT(pay_date_4, '%Y-%m-%d') AS pay_date_4,

				fund_amount,
				net_pay,
				finance_charge,
				apr,
				total_payments,
				DATE_FORMAT(estimated_fund_date, '%Y-%m-%d') AS fund_date,
				DATE_FORMAT(estimated_payoff_date, '%Y-%m-%d') AS payoff_date,
				
				best_call_time,
				ip_address		AS client_ip_address

				#residence_start_date,
				#banking_start_date,
				#date_of_hire,
				#title AS work_title,

				#engine AS vehicle_engine,
				#keywords AS vehicle_keywords,
				#year AS vehicle_year,
				#make AS vehicle_make,
				#model AS vehicle_model,
				#series AS vehicle_series,
				#style AS vehicle_style,
				#mileage AS vehicle_mileage,
				#vin AS vehicle_vin,
				#value AS vehicle_value,
				#color AS vehicle_color,
				#license_plate AS vehicle_license_plate,
				#title_state AS vehicle_title_state
			FROM
				personal
				INNER JOIN residence USING (application_id)
				INNER JOIN bank_info USING (application_id)
				INNER JOIN employment USING (application_id)
				INNER JOIN loan_note USING (application_id)
				INNER JOIN income USING (application_id)
				INNER JOIN paydate USING (application_id)
				INNER JOIN campaign_info USING (application_id)
				#LEFT JOIN vehicle USING (application_id)
			WHERE
				personal.application_id = {$application_id}
				AND campaign_info.active = 'TRUE'
			LIMIT 1
		";

		$mysql_result = $this->olp_sql->Query($this->olp_db, $query);

		if($mysql_result && ($data = $this->olp_sql->Fetch_Array_Row($mysql_result)))
		{
			$data['paydate'] = array('frequency' => $data['income_frequency']);

			list($data['date_dob_y'], $data['date_dob_m'], $data['date_dob_d']) = explode('-', $data['dob']);
			$data['ssn_part_1'] = substr($data['ssn'], 0, 3);
			$data['ssn_part_2'] = substr($data['ssn'], 3, 2);
			$data['ssn_part_3'] = substr($data['ssn'], 5);

			//Taken from ent_cs and slightly modified
			switch($data['paydate']['frequency'])
			{
				case 'WEEKLY':
					$data['paydate']['weekly_day'] = $data['day_of_week'];
					break;
				case 'BI_WEEKLY':
					$data['paydate']['biweekly_day']    = $data['day_of_week'];
					$data['paydate']['biweekly_date']   = $data['next_paydate'];
					break;
				case 'TWICE_MONTHLY':
					switch($data['paydate_model'])
					{
						case 'DMDM':
							$data['paydate']['twicemonthly_type']   = 'date';
							$data['paydate']['twicemonthly_date1']  = $data['day_of_month_1'];
							$data['paydate']['twicemonthly_date2']  = $data['day_of_month_2'];
							break;
						default:
							$data['paydate']['twicemonthly_type']   = 'week';
							$data['paydate']['twicemonthly_week']   = sprintf( '%s-%s', $data['week_1'], $data['week_2'] );
							$data['paydate']['twicemonthly_day']    = $data['day_of_week'];
							break;
					}
					break;
				case 'MONTHLY':
					switch($data['paydate_model'])
					{

						case 'DM':
						//rsk changed from week
							$data['paydate']['monthly_type']    = 'date';
							$data['paydate']['monthly_date']    = $data['day_of_month_1'];
							break;
						case 'WDW':
							$data['paydate']['monthly_type']    = 'day';
							$data['paydate']['monthly_week']    = $data['week_1'];
							$data['paydate']['monthly_day']     = $data['day_of_week'];
							break;
						default:
							$data['paydate']['monthly_type']        = 'after';
							$data['paydate']['monthly_after_day']   = $data['day_of_week'];
							$data['paydate']['monthly_after_date']  = $data['day_of_month_1'];
							break;
					}
					break;
			}

			$data['social_security_number'] = $data['ssn'];
			
			//Create the paydates
			$data['paydates'] = array();
			for($i = 1; $i <= 4; $i++)
			{
				$data['paydates'][] = $data['pay_date_' . $i];
				unset($data['pay_date_' . $i]);
			}
			
			
			//Create qualify information
			$data['qualify_info'] = array(
				'fund_date'		=> $data['fund_date'],
				'payoff_date'	=> $data['payoff_date'],
				'fund_amount'	=> $data['fund_amount'],
				'net_pay'		=> $data['net_pay'],
				'finance_charge'=> $data['finance_charge'],
				'apr'			=> $data['apr'],
				'total_payments'=> $data['total_payments']
			);
			
			
			//Unset unneeded information
			unset($data['ssn'], $data['dob']);


			$ref_query = "
				SELECT
					full_name	AS name_full,
					phone		AS phone_home,
					relationship
				FROM
					personal_contact
				WHERE
					application_id = {$application_id}";

			$ref_result = $this->olp_sql->Query($this->olp_db, $ref_query);

			$count = 0;
			while($row = $this->olp_sql->Fetch_Array_Row($ref_result))
			{
				$ref_count = sprintf('%02d', ++$count);

				$data['ref_' . $ref_count . '_name_full'] = $row['name_full'];
				$data['ref_' . $ref_count . '_phone_home'] = $row['phone_home'];
				$data['ref_' . $ref_count . '_relationship'] = $row['relationship'];
			}
			
			
			//Create our paydate_model
			$model = new Paydate_Model();
			$model_result = $model->Build_From_Data($data['paydate']);
			
			if($model_result === TRUE)
			{
				$data['paydate_model'] = $model->Model_Data();
			}
		}

		return $data;
	}

}

if(isset($argv[1]))
{
	$mode = $argv[1];
}
else
{
	echo "You must pass the mode\n";
	exit(1);
}
if(isset($argv[2]))
{
	$props = explode(',',$argv[2]);
}
else
{
	$props = array();
}
DEFINE('BFW_MODE',$mode);
//Load Failover Config
Failover_Config::RunConfig();

$import = new Import_LDB(BFW_MODE, $props);
$import->Run();

?>
