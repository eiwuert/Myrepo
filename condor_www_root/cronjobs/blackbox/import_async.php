<?php
/**
 * Import applications from OLP/BlackBox to LDB/eCash/CLK.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 Selling Source, Inc.
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
require_once(BFW_CODE_DIR . 'crypt_config.php');
require_once(BFW_CODE_DIR . 'crypt.singleton.class.php');
require_once(BFW_CODE_DIR . 'Cache_Config.php');
require_once(BFW_OLP_DIR . 'app_campaign_manager.php');
require_once(BFW_OLP_DIR . 'authentication.php');
//require_once(BFW_OLP_DIR . 'olp.mysql.class.php');
require_once(BFW_OLP_DIR . 'olp_ldb/olp_ldb.php');
require_once(BFW_OLP_DIR . 'payroll.php');
require_once(BFW_CODE_DIR . 'Enterprise_Data.php');
require_once(BFW_CODE_DIR . '../../www/memcache_servers.php');

class Import_Async
{
	protected $mode = null;

	private $olp_db;	// OLP DB Info
	private $ldb_db;	// eCash/LDB DB Info
	protected $olp_sql;	// OLP SQL connection
	private $ldb_sql;	// eCash/LDB SQL connection

	private $applog;		// Everyone's favorite applog!
	private $ent_prop_list = null;
	private $session = null;

	protected $crypt_object;
	private $crypt_config;

	private $unsynced_apps = array();

	/**
	 * Lock File Path
	 * @var string Lock file
	 */
	protected $lock_file_path = "/tmp/importldb";

	/**
	 * Synced Statuses
	 * @var int
	 */
	private $synced_status = null;
	private $un_synced_status = null;

	private $props = array();
	
	/**
	 * The wait timeout in seconds for this connection.
	 *
	 * This is used to override the OLP databases default setting of 120 seconds. Currently
	 * set for 15 minutes.
	 */
	const WAIT_TIMEOUT = 900;

	/**
	 * Import_LDB constructor.
	 */
	public function __construct($mode, $props = array())
	{
		$this->mode = strtoupper($mode);
		$this->props = $props;

		$this->crypt_config = Crypt_Config::Get_Config($this->mode);
		$this->crypt_object = Crypt_Singleton::Get_Instance($this->crypt_config['KEY'], $this->crypt_config['IV']);

		$this->ent_prop_list = Enterprise_Data::getEntPropList();
		
		if (is_array($this->props))
		{
			$lf = implode('', $this->props);
		}
		else
		{
			$lf = $this->props;
		}
		$this->lock_file_path = "/tmp/importldb_".$lf;
	}

	/**
	 * Runs the import_ldb script.
	 */
	public function Run()
	{
		$this->getApplog();

		//Check for Lock
		if($this->Is_Locked())
		{
			$props = implode('', $this->props);
			$this->Log_Error("The lock file is still in place for {$props}. Skipping this run.");
			exit(1);
		}
		else
		{
			$this->Lock();
		}

		try
		{
			$this->Setup_OLP_DB();
			
			/*
				We need to set the wait_timeout setting to something longer than our db's default 120 seconds. We were
				having issues where an LDB query was taking too long to run (locked) and we'd lose our connection to
				OLP. This will allow us to maintain our connection for a longer period of time. Per gForge #6318.
				[BrianF]
			*/
			$query = sprintf("SET LOCAL wait_timeout = %u", self::WAIT_TIMEOUT);
			$this->QueryOLP($query);
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

			// If we don't have the config in the session (probably because we don't have the session)
			// we don't want to just skip the insert.
			if(!isset($_SESSION['config']) || $_SESSION['config']->use_new_process == TRUE ||
				EnterpriseData::isCFE(EnterpriseData::resolveAlias($app['property_short']))
							
			)
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

		//Marked the apps synced
		try
		{
			$this->Update_Synced_Apps();
		}
		catch(Exception $e)
		{
			$m = "Could not update unsynced apps";
			$this->Send_Error_Out($m);
		 	$this->Log_Error("DB Connection Error " . $m);
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

		// We only want to merge session data in if it actually exists
		if (isset($_SESSION['data']))
		{
			// Start the transaction data, why this is done 3 or 4 times is beyond me
			$transaction_data = array_merge($_SESSION['data'], $olp_data, $authentication);
		}
		else
		{
			$transaction_data = array_merge($olp_data, $authentication);
		}

		// Get the Campaign Info records
		$app_campaign_manager = new App_Campaign_Manager($this->olp_sql, $this->database, $this->applog);
		$transaction_data['campaign_info'] = $app_campaign_manager->Get_Campaign_Info($application['application_id']);
		$transaction_data['olp_process'] = $app_campaign_manager->Get_Olp_Process($application['application_id']);

		// Add the config
		// We can't use the $_SESSION config, since it's entirely possible that the session doesn't exist
		if (isset($_SESSION['config']))
		{
			$transaction_data['config'] = $_SESSION['config'];
		}
		elseif (isset($transaction_data['campaign_info']) && !empty($transaction_data['campaign_info']))
		{
			// We need to get the config from the database based on the last campaign info record
			$config_campaign = end($transaction_data['campaign_info']);
			
			$cache_config_obj = new Cache_Config($this->olp_sql);
			$transaction_data['config'] = $cache_config_obj->Get_Site_Config(
				$config_campaign['license_key'],
				$config_campaign['promo_id'],
				$config_campaign['promo_sub_code']
			);
		}

		// Enterprise license and site name
		$transaction_data['ent_config']->license = $this->ent_prop_list[strtoupper($application['property_short'])]['license'][strtoupper($_SESSION['config']->mode)];
		$transaction_data['ent_config']->site_name = $this->ent_prop_list[strtoupper($application['property_short'])]['site_name'];

		// Check for a react and add the application_id
		// NEW - BrianF - Will need to add the $_SESSION['is_react'] to the session, look in olp.php for this similar
		// line in Process_First_Tier()
		//if($_SESSION['is_react']===TRUE) $transaction_data['react'] = TRUE;
		/// NOTE: Removed for GForge #5632. Using is_react from database now. [RM]

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

		// Put in dob - GForge #8063 [DW]
		$transaction_data['dob'] = "{$transaction_data['date_dob_y']}-{$transaction_data['date_dob_m']}-{$transaction_data['date_dob_d']}";

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

		$this->Insert_Transaction($transaction_data);

		//Dual Write (for CLK only)
		if(USE_DUAL_WRITE && strcasecmp($application['property_short'], 'ic') !== 0)
		{
			try
			{
				$this->Setup_Dual_Write_DB($application['property_short']);
				$this->Insert_Transaction($transaction_data, FALSE);
			}
			catch(Exception $e)
			{
				$this->Log_Error('Error dual-writing app id ' . $application['application_id'] . "\n" . $e->getMessage());
			}
		}
	}


	protected function Insert_Transaction($transaction_data, $send_email = TRUE)
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
			if($row->name == 'ldb_unsynched') continue;
			// Reset the array of status times so it only
			// includes things after the latest synched time
			if ($row->name == 'ldb_synched')
			{
				$x = 0;
				continue;
			}

			$status_histories[$x] = array("name" => $row->name,
									      "date" => $row->date_created);

			//If verification or underwriting grab the loan actions
			if(in_array($row->name, array('verification', 'underwriting', 'soft_fax', 'addl')))
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
	protected function Get_Property_Short($application_id)
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

		$returnPropertyShort = Enterprise_Data::resolveAlias($p->property_short);
		return $returnPropertyShort;
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
		}
		else
		{
			$prop_where = '';
		}

		$query = "SELECT
				   h.application_id,
				   h.date_created,
				   a.session_id
				  FROM
				   status_history as h,
				   application as a
				  WHERE a.application_id = h.application_id
					AND h.application_status_id = " . $this->Get_Un_Synced_Status() . "
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

			$this->unsynced_apps[] = $app;
		}
	}

	/**
	 * Update Synced Applications
	 *
	 * Updates applications which have put into LDB
	 * @return boolean True on success
	 */
	protected function Update_Synced_Apps()
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
		$this->ldb_sql = Setup_DB::Get_Instance("mysql", $this->mode, $prop_short);
		$this->ldb_db = $this->ldb_sql->db_info["db"];
	}

	private function Setup_Dual_Write_DB($prop_short = null)
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
	protected function QueryOLP($query)
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
	 * Send Error Out
	 * @param string Echo message to standard out
	 */
	private function Send_Error_Out($message)
	{
		if($this->mode == "LIVE" || $this->mode == "LOCAL" || TRUE) echo $message;
	}

	/**
	 * Log Error
	 * @param string Message
	 */
	private function Log_Error($message)
	{
		$this->getapplog()->Write($message);
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

				is_react,

				date_of_birth 	AS dob,
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
				ip_address		AS client_ip_address,

				residence_start_date,
				banking_start_date,
				date_of_hire,
				title AS work_title,

				#engine AS vehicle_engine,
				#keywords AS vehicle_keywords,
				year AS vehicle_year,
				make AS vehicle_make,
				model AS vehicle_model,
				series AS vehicle_series,
				style AS vehicle_style,
				mileage AS vehicle_mileage,
				vin AS vehicle_vin,
				value AS vehicle_value,
				color AS vehicle_color,
				license_plate AS vehicle_license_plate,
				title_state AS vehicle_title_state,
				application.application_type AS olp_application_type,
				application.target_id AS winning_target_id
			FROM
				personal_encrypted
				INNER JOIN application USING(application_id)
				INNER JOIN residence USING (application_id)
				INNER JOIN bank_info_encrypted USING (application_id)
				INNER JOIN employment USING (application_id)
				INNER JOIN loan_note USING (application_id)
				INNER JOIN income USING (application_id)
				INNER JOIN paydate USING (application_id)
				INNER JOIN campaign_info USING (application_id)
				LEFT JOIN vehicle USING (application_id)
			WHERE
				personal_encrypted.application_id = {$application_id}
				AND campaign_info.active = 'TRUE'
			LIMIT 1
		";

		$mysql_result = $this->olp_sql->Query($this->olp_db, $query);
	
		if($mysql_result && ($data = $this->olp_sql->Fetch_Array_Row($mysql_result)))
		{
			$data['paydate'] = array('frequency' => $data['income_frequency']);

			// Following are encrypted, decrypt to use them.
			$data['dob'] = $this->crypt_object->decrypt($data['dob']);
			$data['ssn'] = $this->crypt_object->decrypt($data['ssn']);
			$data['bank_account'] = $this->crypt_object->decrypt($data['bank_account']);
			$data['bank_aba'] = $this->crypt_object->decrypt($data['bank_aba']);

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

	/**
	 * Gets an applog instance
	 *
	 * @return Applog
	 */
	protected function getApplog()
	{
		if (!$this->applog)
		{
			//Create Applog
			return $this->applog = Applog_Singleton::Get_Instance('ldb', 1000000, 20, 'Import LDB', TRUE);
		}
		return $this->applog;
	}

}

?>
