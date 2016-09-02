<?php
/**
	@version:
			1.0.0 2005-03-25 - OLP Module for BFW
	@author:
			Don Adriano - version 1.0.0
	@Updates:

	@Todo:
*/
define('DUAL_WRITE', FALSE);

require_once('references.php');
require_once('statpro_client.php');
include_once('collections.php');
require_once('ole_smtp_lib.php');
require_once('mysqli.1.php');
require_once(BFW_CODE_DIR . 'server.php');
require_once(BFW_CODE_DIR . 'setup_db.php');
require_once('prev_customer/prev_customer_check.php');
require_once('olp_ldb/olp_ldb.php');
require_once('setstat.3.php');
require_once('ajax_handler.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once('fraud_scan.php'); // GForge #3077 [DY]
require_once(BFW_CODE_DIR.'accept_ratio_singleton.class.php'); //GF 3833 [TF]


class OLP
{

	// standardized the classifications for the various
	// front-ends that can connect to us: see OLP::Site_Class()
	const FE_CLASS_TSS = 'TSS';
	const FE_CLASS_SAMPLE = 'SAMPLE';
	const FE_CLASS_SOAP = 'SOAP';

	private $current_page;			// current page we are on
	private $next_page;					// what should our next page be
	private $errors;						// array of errors on this page
	private $eds_page;					// html data to be display for the user
	private $override_errors;		// flag to ignore page errors when page = reprint_docs
	private $declined;					// flag to ignore page errors when cust declines on conf page

	private $sql;								// mysql connection object
	private $db;								// ldb connection object (MySQLi)
	private $ldb_pdo;							// ldb connection object (PDO)
	private $database;						// The database name
	private $session;						// session class (used to hit stats)
	private $applog;						// applog error object
	private $timer;							// timer class object
	private $event;							// event class (used to log events)
	private $template_messages;	// Tempate messages object

	public $application_id;			// application_id
	public $transaction_id;			// transaction_id
	public $document_id;
	public $collected_data;			// data passed in from the front end
	public $normalized_data;		// data that has been normalized
	public $client_state;				// fake client session
	public $site_type;					// what site type are we using
	public $qualify_info;				// loan information
	public $pay_dates;					// next four paydates

	// might not need a paydate model variable
	private $paydate_model;			// actual paydate model from the user

	private $config;						// config from config_5
	private $validation_rules;
	private $paydate_calc;			// paydate calc obj
	private $data_validation; 	// data validation object

	private $condor;						// condor client object rsk
	private $blackbox;			// Blackbox result

	private $dm_bypass;		// Direct Mail override

	protected $start_time = NULL;
	protected $title_loan = false;

	private $entgen_properties;  //GFORGE_3891[TF]
	private $agean_properties;
	private $impact_properties;
	private $clk_properties;
	private $compucredit_properties;
	private $is_yellowpage = FALSE;

	private $crypt_object;

	public function __construct(&$session, &$sql, $database, $config)
	{

		$this->session = &$session;
		$this->sql = &$sql;
		$this->config = $config;
		
		//Instantiate Encryption Singleton Class
		$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
		$this->crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

		$_SESSION['references'] = new References($this->sql);
		//to conform to the new SiteConfig,we want to have part of allow_no_refs
		// to be able to change persistently. [TP]
		if(!isset($_SESSION['data']['no_refs']))
		{
			$_SESSION['data']['no_refs'] = SiteConfig::getInstance()->allow_no_refs;
		}
		$this->database = $database;
		$this->client_state = array();
		$this->errors = array();
		$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $this->config->site_name, APPLOG_ROTATE, APPLOG_UMASK);
		$this->timer = new Timer($this->applog);
		$this->template_messages = Template_Messages::Get_Instance();
		$this->dm_bypass = FALSE;

		// instantiate validation class
		$this->data_validation = new Data_Validation(0, 0, 0, 0, 0);

		// instantiate condor
		$this->condor = new Condor_Client(CONDOR_SERVER);

		// We must actively set this to true to allow popups
		$this->allow_popups = FALSE;

		// Just a single isset for call_center
		if (!isset($this->config->call_center)){
			$this->config->call_center=FALSE;

		}

		$this->config->enable_rework = TRUE;

		$this->config->use_new_process = $_SESSION['config']->use_new_process = TRUE;

		//Pages in this array will ALWAYS use the read-only connection.
		$this->process_exceptions = array(
			'ent_cs_login',
			'ent_cs_card_login',
			'ent_cs_login_reload',
			'ent_status',
			'ent_cs_password_change'
		);

		// ECash 3.0 Companies - Once An entry is added to this
		// array then Stats will change to new format
		// UFC should be the first one when we go LIVE [RL]
		// And Loan Tpye ID Is Hardcoded.	(ECashApp May run differently) [RL]
		// 05/05/2006 - Cinco de ecash3 day UFC Turned on [RL]
        if (($this->config->site_name == 'ecashapp.com')
            || $this->config->call_center) // GForge #6153 [DY]
        {
        	// WE DO NOT WANT TO RUN REWORK
        	$this->config->enable_rework = FALSE;
        }
        elseif(strcasecmp($this->config->site_name,'ecashyellowpage.com') == 0)
        {
        	$this->config->enable_rework = FALSE;
        	$this->is_yellowpage = TRUE;
        	$_SESSION['is_yellowpage'] = true;
        }

       	$_SESSION['config']->ecash3_prop_list = array_map('strtolower', Enterprise_Data::getAllProperties());

		$this->ent_prop_short_list = Enterprise_Data::getEntPropShortList();
		$this->ent_prop_list = Enterprise_Data::getEntPropList();

		$this->impact_properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_IMPACT);
		$this->clk_properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_CLK);
		$this->agean_properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_AGEAN);
		//$this->compucredit_properties = array('ccrt1');
		$this->entgen_properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_GENERIC);  //GFORGE_3981 [TF]



		// create a list of site types that follow the FLE model
		$this->fle_site_types = array('blackbox.3part.short');

		if (array_key_exists($this->config->site_name, $this->ent_prop_short_list))
		{

			$this->property_short = $this->ent_prop_short_list[$this->config->site_name];
			$this->legal_entity = $this->ent_prop_list[$this->property_short]['legal_entity'];
			$this->enterprise_data = $this->ent_prop_list[$this->property_short];
			if(!empty($this->ent_prop_list[$this->property_short]['ctc_promo_id']))
			{
				$this->config->ctc_promo_id = $this->ent_prop_list[$this->property_short]['ctc_promo_id'];
			}
			if(!empty($this->ent_prop_list[$this->property_short]['egc_promo_id']))
			{
				$this->config->egc_promo_id = $this->ent_prop_list[$this->property_short]['egc_promo_id'];
			}
		}

		// set bypass_esig flag for soap_sites using new site type - RSK
		if ( $_SESSION['config']->site_type == 'soap_no_esig' )
		{
			$this->config->bypass_esig = 1;
		}

		if(in_array($this->config->site_type, array('soap_oc', 'soap.agean.title', 'soap.agean')))
		{
			$this->config->soap_oc = 1;
			$this->config->online_confirmation = 1;
			$this->config->enable_rework = FALSE;
		}

		if(preg_match('/^soap\.agean\.title/i', $this->config->site_type))
		{
			$this->title_loan = true;
		}
		
		//********************************************* 
		// GForge #9534 [AuMa]
		// We changed the value here so we can modify it
		// for our values for unique_page_stat
		//********************************************* 
		$_SESSION['current_page_stat']   = new stdClass(); 
	}

	public function __destruct()
	{

		unset( $this->db );
		unset( $this->sql );
		unset( $this->timer );
		unset( $this->applog );
		unset( $this->session );
		unset( $this->condor );
		unset( $this->data_validation );

	}

	public function Add_Timer($name)
	{

		if (!$this->start_time)
		{
			$this->start_time = microtime(TRUE);
		}

		// enforce a maximum size
		if (($count = count($_SESSION['timer'])) >= 1000)
		{
			array_splice($_SESSION['timer'], 0, ($count - 1000));
		}

		$_SESSION['timer'][] = $name.' '.sprintf("%0.4f",microtime(TRUE) - $this->start_time);
		return;

	}
	/**
	 * Figure out what 'customer motivation' page we're at so that we can
	 * hit stats properly
	 *
	 * @param Array $collected_data
	 */
	private function Customer_Motivation(&$collected_data)
	{
		//if we have a direction figure out
		//what direction we went, and then
		if(isset($collected_data['cmo_direction']))
		{
			// sites and license key for each of them for each mode so that we
			// can make sure that we are hitting the stats properly.
			$cmo_license_keys = Array(
				'lowratecashloan.com' => Array(
					'vacation' => Array(
						'RC' => 'e44b8884e374ceb02058fc592c8b1edc',
						'LOCAL' => '313df1ded6c999fc6643c252305c28e5',
						'LIVE' => 'b30f40f0d85c8f8af6c0988ffc401860'),
					'medical' => Array(
						'RC' => '8861a2dfa52528688ebd763c5cc213aa',
						'LOCAL' => 'eae7675b437160499e79327049f65b61',
						'LIVE' => '4d189f7314a2900033dafe701fd10e66'),
					'car_home_repair' => Array(
						'RC' => '8fc624737b95badaf7d5f0e27338fee7',
						'LOCAL' => '5d315a96da172f7f4311c02dd8405082',
						'LIVE' => 'c72d1c5ba1ac8feeca37c66b7cde959f'),
					'pay_bills' => Array(
						'RC' => 'f10938977f6b07f69a69b2aef6e9a96e',
						'LOCAL' => '8ef27486c7ad941c05895a5e9e7e6575',
						'LIVE' => '5b93e1345504ef07f77d3da171ebd22a'),
					'other' => Array(
						'RC' => 'f54193dcb76828548eb6db41689e764c',
						'LOCAL' => '51c2d98165f3c3cff639c9688449c283',
						'LIVE' => '8e03f2db1ce850945e2e0f23a7e01959')
					)
				);

				//figure out the proper license key
				$cmo_dir = strtolower($collected_data['cmo_direction']);
				$site = $this->config->site_name;
				if(isset($cmo_license_keys[$site][$cmo_dir][BFW_MODE]))
				{
					//We know what the key is so lets setup a new config
					//so that we're hitting stats correctly
					$key = $cmo_license_keys[$site][$cmo_dir][BFW_MODE];
					$promo = $this->config->promo_id;
					$promo_sub_code = $this->config->promo_sub_code;
					$this->Setup_New_Config($key,$promo,$promo_sub_code);
					Stats::Hit_Stats(
						'__context',
						$this->session,
						$this->event,
						$this->applog,
						$this->Get_Application_ID()
					);
				}
				else
				{
					//No idea what the licensekey was so lets write an applog
					//so that we know it's messed up
					$this->Applog_Write("Customer Motivation: No License key set for $site($cmo_dir).");
					return;
				}
			}
	}
	/**
	* @return bool
	* @param collected_data array consisting of submitted form vars and $page
	* @desc Handle which functions to run for each page
 	*/
	public function Page_Handler($collected_data = array() )
	{
		// This is for debugging a timing issue we're currently seeing.  There will be several throughout page_handler
		$this->Add_Timer('topPageHandler');

		/*
			For Customer Motivation, we'll need to check which option they're using and change
			what page_id they hit stats on based on that option. [BrianF]
		*/

		// Check to see if PW has given us a trackkey to use, if so use it.
		if(strlen($collected_data['track_key']) > 1)
		{
			$_SESSION['statpro']['track_key'] = $this->track_key = $collected_data['track_key'];
		}

		$this->Customer_Motivation($collected_data);

		// stop timer
		// $timer = new Timer($this->applog);
		// $timer->Timer_Start('PAGE_HANDLER');
		// make sure this promo ID is not over their limit
		if ((@$this->config->bb_reject_level) && $this->Is_Caller_Over_Limit($collected_data))
		{
			$return_object =  new stdClass();
			$return_object->page = 'try_again_v2';
			return $return_object;
		}
		/* Time be lazy again....pull some data from live
		 * and store it in our collected data to autofill the app */
		if( isset($collected_data["populate"]) && BFW_MODE != "LIVE")
		{
			//$use_app = (intval($collected_data['populate']) > 0) ? $collected_data['populate'] : 0;

			$data = Populate::Get_Random_Record( $this->sql, $this->applog, $this->config->mode);//, $use_app );
			$this->collected_data = array_merge( $data, $collected_data );

		}
		else
		{
			//populate based on sitelifter input but only if we don't already have this
			//info from somewhere else task #11152 [TP]
			if(isset($collected_data['fn']) && !isset($collected_data['name_first']))
			{
				$collected_data['name_first'] = $collected_data['fn'];
			}
			if(isset($collected_data['ln']) && !isset($collected_data['name_last']))
			{
				$collected_data['name_last'] = $collected_data['ln'];
			}
			if(isset($collected_data['a1']) && !isset($collected_data['home_street']))
			{
				$collected_data['home_street'] = $collected_data['a1'];
			}
			if(isset($collected_data['a2']) && !isset($collected_data['home_unit']))
			{
				$collected_data['home_unit'] = $collected_data['a2'];
			}
			if(isset($collected_data['ct']) && !isset($collected_data['home_city']))
			{
				$collected_data['home_city'] = $collected_data['ct'];
			}
			if(isset($collected_data['st']) && !isset($collected_data['home_state']))
			{
				$collected_data['home_state'] = $collected_data['st'];
			}
			if(isset($collected_data['zp']) && !isset($collected_data['home_zip']))
			{
				$collected_data['home_zip'] = $collected_data['zp'];
			}
			if(isset($collected_data['em']) && !isset($collected_data['email_primary']))
			{
				$collected_data['email_primary'] = $collected_data['em'];
			}
			if(isset($collected_data['ph1']) &&
			   isset($collected_data['ph2']) &&
			   isset($collected_data['ph3']) &&
			   !isset($collected_data['phone_home']))
			{
				$collected_data['phone_home'] = "{$collected_data['ph1']}-{$collected_data['ph2']}-{$collected_data['ph3']}";

			}

			// continue like normal
			$this->collected_data = $collected_data;
		}

		// If this sticks around, the message may show up on other pages
		// and potentially confuse our valued customers.
		unset($_SESSION['data']['message']);

		if(isset($_SESSION['ecashnewapp'])
			|| ($this->config->site_name == 'newecashapp.com'
				&& isset($this->collected_data['ecashnewapp'])
				&& isset($this->ent_prop_list[strtoupper($this->collected_data['ecashnewapp'])]))
		)
		{
			if(!isset($_SESSION['ecashnewapp']))
			{
				$_SESSION['ecashnewapp'] = strtolower($this->collected_data['ecashnewapp']);
			}

			//Pretend we're an enterprise site.
			$this->collected_data['enterprise'] = TRUE;
			$this->config->property_short = $this->property_short = $_SESSION['ecashnewapp'];
			$this->config->use_new_process = $_SESSION['config']->use_new_process = FALSE;
			$this->config->enable_rework = FALSE;
		}

		if($this->config->call_center
			&& (isset($this->collected_data['csr_complete'])
				|| isset($_SESSION['data']['csr_complete']))
		)
		{
			$this->config->use_new_process = $_SESSION['config']->use_new_process = FALSE;
		}


		if(isset($this->collected_data['ecashapp']) || isset($_SESSION['data']['ecashapp']))
		{
			$ecashapp = (isset($this->collected_data['ecashapp'])) ? $this->collected_data['ecashapp'] : $_SESSION['data']['ecashapp'];

			if(isset($this->ent_prop_list[strtoupper($ecashapp)]))
			{
				$this->config->use_new_process = $_SESSION['config']->use_new_process = FALSE;
			}
			$this->property_short = $this->config->property_short = strtolower($ecashapp);
			unset($this->config->bb_reject_level);
		}


		//Set for Preacts
		if((isset($this->collected_data['react_type']) && $this->collected_data['react_type']=='preact') ||
		   (isset($_SESSION['cs']['olp_process']) && $_SESSION['cs']['olp_process']=='ecashapp_preact'))
	    {
		  	$_SESSION['is_preact'] = "yes";
		}

		//Add the EZMs to excluded targets if its coming from SOAP
		if($_SESSION["config"]->site_type == "soap_oc" || $_SESSION["config"]->site_type == "soap")
		{
			if(isset($_SESSION["config"]->excluded_targets))
			{
				$_SESSION["config"]->excluded_targets .= ",ezmcr,ezmcr40,ezmpan,ezmpan40";
				$this->config->excluded_targets = $_SESSION["config"]->excluded_targets;
			}
			else
			{
				$this->config->excluded_targets = $_SESSION["config"]->excluded_targets = "ezmcr,ezmcr40,ezmpan,ezmpan40";
			}
		}

		// Check to see if there are any page
		// specific cases we need to execute
		$this->Page_Override();

		// unset errors
		// need to make sure these are logged into Keith's database
		$ecyp_override = ($this->config->site_type == 'ecash_yellowbook' || $this->config->site_type == 'blackbox.one.page.yellowpage') ? TRUE : FALSE;
		if ($this->errors && !$ecyp_override) $this->errors = array();

		// set page vars
		$this->Set_Page();

		// This check was yanked out of Check_And_Collect
		$current_page = $this->Get_Current_Page();

		$config = $this->Get_Config();

		$pages = $config->site_type_obj->page_order;
		$pages[] = 'default';
		$pages[] = 'ent_cs_confirm_start';

		// did we come from customer service, but now want a loan?
		if (in_array($current_page, $pages) && isset($_SESSION['cs']))
		{
			unset( $_SESSION['cs'] );
			unset( $_SESSION['transaction_id'] );
			unset($_SESSION['ent_status_override'], $_SESSION['data']['ent_status_override']);
		}

		// are we using a enterprise license key?
		$enterprise_license_key = (isset($this->ent_prop_list[$this->property_short]) && in_array($this->config->license, $this->ent_prop_list[$this->property_short]['license']));

		//Make sure config is correct
		if (in_array($current_page, $pages) && $enterprise_license_key && isset($_SESSION['old_config']))
		{
			unset($_SESSION['old_config']);
			$this->Setup_New_Config(BFW_ORIGINAL_KEY, BFW_ORIGINAL_PROMO, BFW_ORIGINAL_SUB_CODE);
		}

		// Fix for Mantis #9662 - the enterprise config was sticking around if customer went from csreact to home page and started a normal app. [RV]
		// We need to bypass this check for Agean since they will ONLY use enterprise keys.
		$non_ent_pages = $config->site_type_obj->page_order;
		if($enterprise_license_key && in_array($this->current_page, $non_ent_pages) && $this->Is_CLK())
		{
			unset($_SESSION['old_config']);
			$this->Setup_New_Config(BFW_ORIGINAL_KEY, BFW_ORIGINAL_PROMO, BFW_ORIGINAL_SUB_CODE);
		}

		// check to see that the application has not been completed yet only if there are no errors
		// Uh... will $this->application_id _ever_ be set here? I don't think so..?
		$application_id = $this->Get_Application_ID();

        //Check if they should be at react but logged into cs
		$react_safe_pages = array("ent_cs_confirm_start","ent_cs_confirm_react","ent_reapply",
                    "info_contactus_base","info_overview","info_faq","info_testimonials",
                    "cs_removeme","info_contactus_base","info_privacy","info_terms",
                    "info_adv","info_spam","info_webmasters");

        if(isset($_SESSION["react"]) && (!isset($application_id) || $application_id == null) && !in_array($this->current_page,$react_safe_pages))
        {
            $this->next_page = "ent_cs_confirm_start";
            return $this->Gen_Return_Object();
        }
		 
		// we don't have an app_id when they first hit the ent_cs_login page from email link so check for it in collected_data
		// and decode
		// Added in the check for the continuation url option so that ECYP apps coming back to finish can bypass this case [RV]
		if(!$application_id && $this->collected_data['application_id'] && $this->collected_data['continuation'] != 1)
		{
			$ent_cs = $this->Get_Ent_Cs($this->property_short);

			$application_id = $ent_cs->Process_App_ID($this->collected_data['application_id']);
			$this->collected_data['application_id'] = $this->application_id = $application_id;
		}
		// For ecash yellowpages, we grab the app_id from the url and also set it in the session for continuation.	[RV]
		elseif($this->collected_data['continuation'] == 1 && $this->collected_data['application_id'] && $_SESSION['config']->continuation == 1)
		{
			$_SESSION['application_id'] = $this->Get_Application_ID();

			$ent = $this->Get_Ent_Cs($this->config->property_short);
			$cs = $ent->Get_The_Kitchen_Sink($this->sql, $this->database, $_SESSION['application_id']);

			$temp = array_merge($cs, $_SESSION['data']);
			$_SESSION['data'] = $temp;
		}
		// Basically if webadmin settings for the site have a continuation value set to 1 like americascashadvancecenter
		// then we have to have an app id and the continuation flag on the url.  If those are missing we forward them on to another site.
		elseif($_SESSION['config']->continuation == 1 && !$_SESSION['data']['continuation'] && !$_SESSION['data']['application_id'])
		{
			// This is the redirect for yellowpage when someone tries to go to ACAC without proper authoritah!
			$_SESSION['data']['redirect'] = "http://click.linkstattrack.com/zoneId/163415?sub=ecyp";
		}
	
		// Removed for Unsigned Apps crap
		if(!EnterpriseData::isCFE(SiteConfig::getInstance()->property_short) && $this->Check_For_LDB_App($this->Get_Application_ID()))
		{
			$this->config->use_new_process = $_SESSION['config']->use_new_process = FALSE;
		}
		// keep us on the straight and narrow (I added the check for the continuation configuration setting so that ACAC apps will be caught if they answer no to the qual questions.	[RV]
		if ($application_id 
			&& $this->App_Completed_Check($application_id) 
			&& $this->current_page != 'second_loan' 
			&& $this->current_page != 'bb_ezm_legal' // added because ezm failed when they really won [AuMa] 
			&& $_SESSION['config']->continuation != 1)
		{
			return $this->Gen_Return_Object();
		}

		// prepare collected_data
		$this->Prepare_Collected_Data();

		// validate submitted data
		// look below at the comments

		$this->Set_Soap_OC();

		//Override sample soap sites to put everything in redirects
		if($this->config->site_type == "blackbox")
		{
			$this->config->soap_oc = 1;
			$this->config->online_confirmation = 1;
			$this->config->enable_rework = 0;
		}

		//Need to force these sites to online_confirmation if a CSR is doing the app
		if((isset($this->collected_data['csr_complete']) || isset($_SESSION['data']['csr_complete']))
			&& (in_array($this->config->site_name, array('1hourfastcash.com', 'woodscashloans.com')) || $this->config->call_center)
		)
		{
			$this->config->online_confirmation = TRUE;
			unset($this->config->display_captcha);
		}

		$this->Check_And_Collect();


		// as soon as a SSN is seen and passes validation, store it for
		// the vetting process described in GForge 9922 [DO]
		if (isset($this->normalized_data['social_security_number_encrypted'])
			&& $this->Get_Application_ID())
		{
			$info = DBInfo_OLP::getDBInfo($this->config->mode);
			$vetting = new Vetting_SSN(
				new DB_MySQL4Adapter_1(
					$this->sql->getConnection(),
					$info['db'])
			);
			try 
			{
				$vetting->ssnSeen(
					$this->normalized_data['social_security_number_encrypted'],
					$this->Get_Application_ID()
				);
			} 
			catch (Exception $e) 
			{
				$this->Applog_Write(sprintf(
					'could not update vetting table: (%s) %s',
					get_class($e),
					$e->getMessage())
				);
			}
		}
		
		// Impact Utah State Decline Hack [MP]
		// [#10545] Utah applicants through our websites need to be denied
		// Utah applications on impact sites should create an application [for logging]
		// but fail the app.  Create an event log just so someone looking at the app
		// understands why we failed it.  Once thats all done, take the customer to
		// an error message.
		if (
			(
				EnterpriseData::siteIsCompany(EnterpriseData::COMPANY_IMPACT)
				|| SiteConfig::getInstance()->site_name == 'cashproviderusa.com'
			)
			&& strcasecmp($this->collected_data['home_state'], 'UT') == 0)
		{
			$this->Create_Application();
      
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Insert_Application($this->application_id, $this->collected_data);
			$app_campaign_manager->Update_Application_Status($this->application_id, 'FAILED');
			
			$this->Event_Log();
			$this->event->Log_Event('IMPACT_UTAH_CHECK', 'FAIL');
			
			$content = "<p>We're sorry, but neither Impact Cash, LLC, nor any of its partner companies and/or subsidiaries is licensed to make loans in the state of Utah. Additionally, we do not process, sell, redirect or offer leads or other information which could result in the opportunity to provide payday or cash advance loans to residents of the state of Utah. IT IS ILLEGAL FOR ANY LENDER TO PROVIDE LOANS IN THE STATE OF UTAH WITHOUT PROPER LICENSING, and Impact Cash, LLC is not licensed to provide loans in Utah.</p>";
			$this->eds_page = array('force_content' => TRUE, 'content' => $content, 'type' => 'html' , 'action' => 'standard');
			$this->page = $_SESSION['data']['page']  = 'bb_extra';
			$this->next_page = 'bb_extra';
			
			return $this->Gen_Return_Object();
		}
		
		// If we're collections, we end here and don't do any more processing beyond what we've defined here.
		if('collections' == $this->config->site_type)
		{
			$collections = new Collections($this->config);

			list(, $errors) = $collections->Validate_Collection_Data($this->collected_data);

			if(!empty($errors))
			{
				$this->Add_Errors($errors);
			}

			if('collections_confirm' == $this->current_page)
			{
				$this->next_page = $collections->Process_Collections();
			}
			elseif('info_contactus_base' == $this->current_page)
			{
				if( isset($_SESSION['data']['name_first']) &&
					isset($_SESSION['data']['name_last']) &&
					isset($_SESSION['data']['home_street']) &&
					isset($_SESSION['data']['home_city']) &&
					isset($_SESSION['data']['home_state']) &&
					isset($_SESSION['data']['home_zip']) &&
					isset($_SESSION['data']['phone_home']) &&
					isset($_SESSION['data']['phone_best_contact']) &&
					isset($_SESSION['data']['social_security_number']) &&
					isset($_SESSION['data']['terms_conditions']))
				{
					$collections->Send_Contact_Us_Email();
					$this->next_page = 'thanks_contactus';
				}
				else
				{
					$this->next_page = 'info_contactus_base';
				}
			}

			return $this->Gen_Return_Object();
		}

		// set the client state
		if( isset($this->collected_data["client_state"]) )
		{
			$this->client_state = array_merge($this->client_state, $this->collected_data["client_state"]);
		}

		// Coreg Process
		if($this->config->coreg_site && !isset($_SESSION['coreg']) && $this->current_page == "app_coreg")
		{
			$this->Process_Coreg_Application();

			return $this->Gen_Return_Object();
		}

		// execute fcna process.  We need to do this here before pretty much anything
		// else happens.

		if (strtolower($collected_data['fcna_email'])=='send')
		{

			Stats::Hit_Stats( 'visitor', $this->session, $this->event, $this->applog,  $this->Get_Application_ID() );

			// Find the first page and stick it in the trace
			$site_pages = get_object_vars($this->config->site_type_obj->pages);
			$site_page_keys = array_keys($site_pages);
			$this->current_page = $site_page_keys[0];
			$this->Set_Page();	// ensures we get into page trace
			$this->Check_And_Collect();

			$fcna = new FCNA_Handler($this, $this->collected_data, $this->applog);
			$fcna->Execute();

			$this->current_page = 'try_again';
			$this->next_page = 'try_again';
			return $this->Gen_Return_Object();
		}

		// soap_drop - An application that represents an incompletly processed application, that had enough information for
		//   CS to contact them by phone.
		// If this is a soap_drop app, use the supplied info and track ID to generate the statPro info
		// Alternately, if any Direct Mail app comes from cashnowbyphone (now signaled by $config->call_center), we'll let them override the track_id
		if (($this->collected_data['app_type'] == 'soap_drop'
				|| ($this->config->call_center
					&& !empty($this->collected_data['reservation_id'])))
			 && $this->collected_data['track_id'])
		{
			$_SESSION['statpro']['track_key'] = $this->collected_data['track_id'];
		}
		// hit visitor stat for soap sites if there is no track_key, this will set the track key
		// we need this set prior to creating the app below
		// Also check to make sure stat_info exists.  Fix for reprint_docs [CB] 2006-02-08
		if (!$_SESSION['statpro']['track_key'] && isset($_SESSION['stat_info']))
		{
			Stats::Hit_Stats('visitor', $this->session, $this->event, $this->applog, null);

			if (strtoupper($this->normalized_data['app_type']) == 'SOAP_DROP')
			{
				Stats::Hit_Stats( 'soap_drops', $this->session, $this->event, $this->applog,  $this->Get_Application_ID() );
			}
		}

		// lets only give them an application if the don't have any errors on page 1
		// dont have a application id in the session
		// and is not on the first default page load
		// change to work with the fle 3 page form
		if( $this->current_page != 'default' &&
			(isset($this->normalized_data['email_primary'])
				|| $this->config->site_type == 'blackbox.1fieldcell.online.confirmation')
			&& (!isset($_SESSION['cs'])) )
		{
			$this->Create_Application();
			$this->Update_Campaign_Info($this->application_id);
		}

		// set this->application_id to normalized_data['application_id'] for cust service
		//$this->application_id = ($this->application_id) ? $this->application_id : $this->normalized_data['application_id'];
		//$this->application_id = ($this->normalized_data['application_id']) ? $this->normalized_data['application_id'] : $this->application_id;

		// Instantiate the event log. Be mindful of what event log table we're using.
		// restricted only if there is a application id set
		// added "or" statement for cust service - this is the app_id pulled from olp.application
		if ($this->Get_Application_ID())
		{
			$this->Event_Log();
		}

		//Agean Legacy Bypass - bypass checks for socials on the no checks list.
		//Added for GForge #5283 [MJ] - Modified for GForge #11375 [MJ]
		include_once('NoChecksSSNs.php');
		if(!isset($_SESSION['no_checks_ssn']) 	 
			&& EnterpriseData::siteIsCompany(EnterpriseData::COMPANY_AGEAN)
			&& !$this->isReact() //Fix for GForge #6697
			&& isset($this->normalized_data['ssn_part_1']) 	 
			&& isset($this->normalized_data['ssn_part_2']) 	 
			&& isset($this->normalized_data['ssn_part_3']) 	 
			&& NoChecksSSNs::getNoChecksSSNResult($this->normalized_data['ssn_part_1'].$this->normalized_data['ssn_part_2'].$this->normalized_data['ssn_part_3'],$this->sql))
		{
			$this->event->Log_Event(EVENT_NO_CHECK_SOCIAL,EVENT_PASS,null,$this->Get_Application_ID());
			$_SESSION['is_fraud'] = FALSE;
			$this->normalized_data['no_checks'] = '';
		}

		//================================
		// Start: Duplicate Leads check using memcache
		try
		{
			if(file_exists(BFW_CODE_DIR . 'Cache_Duplicate_Leads.php') && //Make sure memcache duplicate lead modules exhists
			is_numeric($this->Get_Application_ID()) &&  //Make sure we have been assigned an application_id
			!isset($_SESSION['duplicate_lead']) && // Make sure we havn't already hit duplicate_leads
			$_SESSION['redirect_logged'] != 1) //Make sure we havn't already won and are in the redircting phase.
			{
				$this->Event_Log(TRUE);
				require_once(BFW_CODE_DIR . 'Cache_Duplicate_Leads.php');
				$cache_duplicate_leads = new CacheDuplicateLeads($this->sql);
				$cache_duplicate_leads->run_for_email($this->Get_Application_ID(),$this->event,$this->next_page,$this->config,$_SESSION['data']);
			}
		}
		catch(Exception $e)
		{
			$this->Applog_Write("Duplicate Leads Error: $e");
			throw $e;
		}
		//End:  Duplicate Leads check using memcache
		//=================================

		// set the client state
		if( isset($this->collected_data["client_state"]) )
		{
			$this->client_state = array_merge($this->client_state,$this->collected_data["client_state"]);
		}

		// make sure that the pages prior to the current page has been completed
		// to ensure the user has not skipped any steps
		// rsk - apps coming in from app_allinone sites break on csr completes
		// $_SESSION['return_visitor'] is getting set in page_override

		if (!$_SESSION['return_visitor'])
		{
			$this->Check_Page_Order();
		}

		// return error to front page if there is an invalid session
		if (is_array($this->errors) && in_array('invalid_session', $this->errors))
			return $this->Gen_Return_Object();



		// GForge #3077 - Fraud Scan [DY] --start--
		switch (TRUE) {
			case (!empty($_SESSION['data']['income_frequency'])):
				$income_frequency = $_SESSION['data']['income_frequency'];
				break;
			case (!empty($_SESSION['data']['paydate_model']['income_frequency'])):
				$income_frequency = $_SESSION['data']['paydate_model']['income_frequency'];
				break;
			case (!empty($_SESSION['data']['paydate']['frequency'])):
				$income_frequency = $_SESSION['data']['paydate']['frequency'];
				break;
			default:
				$income_frequency = NULL;
				break;
		}
		
		$dob = '';
		switch (TRUE) 
		{
			case (isset($_SESSION['data']['dob']) 
				&& !empty($_SESSION['data']['dob'])):
				$dob = $_SESSION['data']['dob'];
				break;
			case (isset($_SESSION['data']['date_dob_m'])
				&& isset($_SESSION['data']['date_dob_d'])
				&& isset($_SESSION['data']['date_dob_y'])):
				$dob = "{$_SESSION['data']['date_dob_m']}/{$_SESSION['data']['date_dob_d']}/{$_SESSION['data']['date_dob_y']}";
				break;
		}

		if (!isset($_SESSION['is_fraud']) 
			&& $_SESSION['application_id'] 
			&& $_SESSION['data']['email_primary'] 
			&& $_SESSION['data']['dep_account'] 
			&& $income_frequency 
			&& $_SESSION['data']['income_monthly_net']
			&& $dob) 
		{	
			$fraud_app = new FApplication(
				$_SESSION['application_id'],
				$_SESSION['data']['email_primary'],
				$_SESSION['data']['dep_account'],
				$income_frequency,
				$_SESSION['data']['income_monthly_net'],
				date('Ymd', strtotime($dob))
			);

			$_SESSION['is_fraud'] = FALSE;
			
			if ($this->isReact()) 
			{
				$is_react_app = TRUE;
			}
			else
			{
				$is_react_app = FALSE;
			}

			$_SESSION['is_fraud'] = FALSE;
			if ($is_react_app) 
			{
				// G3284: Don't run Fraud Scan for ecash reacts [DY] (for react apps, $_SESSION['is_fraud'] is FALSE)
			} 
			elseif (isset($_SESSION['data']['fraud_scan']) && !$_SESSION['data']['fraud_scan'] && ($this->config->mode != 'LIVE')) 
			{
				$this->event->Log_Event('FRAUD_SCAN', EVENT_SKIP, NULL, $_SESSION['application_id']);
			} 
			else if (Fraud_Scan::Is_Fraud_App($fraud_app, $this->sql, $this->database)) 
			{
				$_SESSION['is_fraud'] = TRUE;
				$this->event->Log_Event('FRAUD_SCAN', 'FAIL', NULL, $_SESSION['application_id']);
			} 
			else 
			{
				$this->event->Log_Event('FRAUD_SCAN', 'PASS', NULL, $_SESSION['application_id']);
			}

			unset($app_campaign_manager);
			unset($fraud_app);
		}

		unset($income_frequency);
		// GForge #3077 - Fraud Scan [DY] --end--

		// [AM, 8/2] moved into BlackBox_Prequal
		// and renamed to Pre_Prequal_Collect
		// Collect email and site for new FLE form
		// $this->FLE_Collect();

		// rsk - go to cust_decline in cust service without error checking page
		if ($this->declined)
		{
			$this->current_page = $this->declined;
			unset($this->errors);
		}

		//rsk - ignore page errors when when customer declines on esig, conf page, or ent_confirm_legal (esig), or reprint_docs function
		// Also ignore page errors on the first trip in from a coreg site - [BF]
		if ($this->override_errors)
		{
			$this->current_page = $this->override_errors;
			unset($this->errors);
		}

		// calculate paydates
		$this->pay_dates = $this->Calculate_Paydates();
		if( is_array($this->pay_dates->errors) && count($this->pay_dates->errors) )
		{
			// need to return to the front end
			$this->errors = array_merge($this->errors,$this->pay_dates->errors);
		}

		$this->Add_Timer('afterCalculate_Paydates');

			
		if(!empty($_SESSION['data']['es_offer']))
		{
			$this->postExitStrategyCoregOffer($_SESSION['data']['es_offer']);
		}
		
		// run pre-qualify
		$this->pre_qualify = $this->Pre_Qualify();
		if (is_array($this->pre_qualify->errors) && count($this->pre_qualify->errors))
		{
			$this->errors = array_merge($this->errors, $this->pre_qualify->errors);
		}
		// page specific cases

		$this->Run_Current_Page_Cases();

		$this->Add_Timer('AfterCurrentPageCases');

		$this->Set_Soap_OC();
		// run preliminary cases for next_page
		$this->Run_Next_Page_Cases();

		$this->Add_Timer('AfterNextPageCases');


		// hit the visitor stat if we're on any page in
		// the page order, or if we're on the default page
		if (in_array($this->current_page, $this->config->site_type_obj->page_order) || ($this->current_page == 'default'))
		{
			$app_id = is_integer($this->Get_Application_ID()) ? $this->Get_Application_ID() : NULL;

			Stats::Hit_Stats('visitor', $this->session, $this->event, $this->applog, $app_id);
			if (strtoupper($this->normalized_data['app_type']) == 'SOAP_DROP')
			{
				Stats::Hit_Stats( 'soap_drops', $this->session, $this->event, $this->applog,  $app_id );
			}
		}
		$this->Add_Timer('AfterHitStatsVisitor');

		/*
			Lets hit our stats that are in our site type
			or lets hit the visitor stat if we our on default
			never hit the agree (accepted) stat here
			Hit the unique stats if we're submitting the application
		*/
		if (strlen($_SESSION["config"]->site_type_obj->pages->{$this->current_page}->stat) || $this->current_page == 'second_loan')
		{

			if ($this->current_page == 'second_loan')
			{
				$stat = 'income';
			}
			else
			{
				$stat = $_SESSION["config"]->site_type_obj->pages->{$this->current_page}->stat;
			}
			
			$app_id = ($this->Get_Application_ID()) ? $this->Get_Application_ID() : NULL;

			if (!count($this->errors))
			{
				$submit_stats = Stats::Translate('submit');
				foreach ($submit_stats as $submit)
				{
					if (strpos($stat, $submit) !== FALSE)
					{
						// Check if this app is unique and hit stats accordingly
						$app_campaign_manager = new App_Campaign_Manager(
							$this->sql,
							$this->database,
							$this->applog
						);

						if ($app_campaign_manager->Check_Unique_Lead($_SESSION['data']['social_security_number']))
						{
							Stats::Hit_Stats(
								'unique',
								$this->session,
								$this->event,
								$this->applog,
								$app_id
							);
						}

						// Always hit the direct deposit stats on submit, even if the lead isn't unique
						$dir_deposit = ($_SESSION['data']['income_direct_deposit'] == 'TRUE') ? 'dir_deposit' : 'nondir_deposit';
						Stats::Hit_Stats(
							$dir_deposit,
							$this->session,
							$this->event,
							$this->applog,
							$app_id
						);
					}
				}

				if ($stat != 'agree')
				{
					Stats::Hit_Stats($stat, $this->session, $this->event, $this->applog, $app_id);
					// Moneyhelper Opt-In
					if (isset(SiteConfig::getInstance()->optin_cid)
						&& !$_SESSION['moneyhelper_posted']
						&& $_SESSION['data']['email_primary'])
					{
						$_SESSION['moneyhelper_posted'] = $this->Moneyhelper_Optin();
					}

					// 19 Communications Opt-In
					if (isset(SiteConfig::getInstance()->nineteencom_optin_id)
						&& !$_SESSION['nineteencom_posted']
						&& $_SESSION['data']['email_primary'])
					{
						$_SESSION['nineteencom_posted'] = $this->Nineteencom_Optin();
					}
					// Cash Credit News Opt In -- Mantis 7988 [AuMa]
					if (isset(SiteConfig::getInstance()->cash_credit_news)
						&& !$_SESSION['cash_credit_news_posted']
						&& $_SESSION['data']['email_primary'])
					{
						$_SESSION['cash_credit_news_posted'] = $this->Cash_Credit_News_Optin();
					}
					// YourFinancePro Newsletter Optin - GForge 5764 [AuMa]
					/*
					in english:
					we check if 'your_finance_pro_news' is defined in the webadmin1 config 
					and if 'your_finance_pro_posted' has not been set
					and if we have an email address
					- if it meets these requirements then we call the YourFinancePro_OptIn()
					*/
					if (isset(SiteConfig::getInstance()->your_finance_pro_news)
						&& !$_SESSION['your_finance_pro_posted']
						&& $_SESSION['data']['email_primary'])
					{
						$_SESSION['your_finance_pro_posted'] = $this->YourFinancePro_Optin();
					}
				}
			}
		}

		// Hack for FastCashDay.com.
		if( $_SESSION['config']->site_name == 'fastcashday.com' && $_SESSION['config']->mode == 'LIVE' && !count( $this->errors ) )
		{
			switch ( $current_page )
			{
				case "app_2part_page01":
					$this->_Send_Fastcashday_Post( "I" );
					break;
				case "app_done_paperless":
					$this->_Send_Fastcashday_Post( "A" );
					break;
			}

		}
		//Replace track key with provided PW track key.
		if(strlen($_SESSION['data']['track_key']) > 1)
		{
			$_SESSION['statpro']['track_key'] = $this->track_key = $_SESSION['data']['track_key'];
		}
		if
		(
			$this->config->passback_winner == TRUE
			|| in_array(strtolower($_SESSION['blackbox']['winner']), array('ct4u', 'bmg178')) // added for Mantis #11073 [AuMa]
		)
		{
			$_SESSION['data']['bb_winner'] = $_SESSION['blackbox']['winner'];
		}

		// run epm collect

		//soap site types
		$soap_site_types = array ('blackbox.one.page','soap_oc','soap','soap_no_esig');
		//known soap fails
		$application_types = array ('VISITOR','FAILED');

		// application_id not being set at this point can cause problems with some sites [TP]
		if($this->Get_Application_ID())
		{
			$application_type = $this->Get_Application_Type();
		}
		else
		{
			$application_type = 'VISITOR';
		}
		
		if($this->config->site_name != 'ecashapp.com' &&   // Check for Ecash
		  (!in_array(strtolower($this->config->site_type),$soap_site_types) //check for a soap app
		   || !in_array(strtoupper($application_type),$application_types))
		   && ($_SESSION['cs']['olp_process'] != 'email_react' || $_SESSION['cs']['olp_process'] != 'cs_react'))	//Check if this app was failed
		{
			// add app to list
			$this->EPM_Collect();
		}

		$return = $this->Gen_Return_Object();

		// stop timer
		// $timer->Timer_Stop('PAGE_HANDLER');
		$this->Add_Timer('endPageHandler');

		return($return);

	}

	/**
	 * Sets  soap_oc in various stages throughout the app process; we need it to be true or false depending
	 * on where we are at with the app [RSK]
	 *
	 * @param
	 * @return none
	 */
	private function Set_Soap_OC()

	{

		// set to trigger captcha validation when on ent_online_confirm page
		if ($_SESSION['data']['soap_oc']==1 && $this->current_page == 'ent_online_confirm')
		{
			$this->soap_oc = TRUE;
		}

		// check for captcha on the ent_online_confirm page
		if ($this->normalized_data['soap_oc']==1 && $this->next_page == 'ent_online_confirm')
		{
			$this->soap_oc = TRUE;
		}

		// unset it if we've made it to the legal page
		if ($this->next_page =='ent_online_confirm_legal') $this->soap_oc = FALSE;

		return;
	}

	/**
	 * Determines if the page passed to this function is a cost action page.
	 *
	 * @param string $page The page to check
	 * @return boolean true if the page is a cost action page, false otherwise
	 */
	public function Is_Cost_Action($page)
	{

		$is_cost_action = FALSE;

		// if the page we're returning is going to trigger
		// our cost-action, then display a CAPTCHA
		if (isset($this->config->site_type_obj->pages->{$page}))
		{

			// get the stats for this page
			$stats = $this->config->site_type_obj->pages->{$page}->stat;
			$stats = array_map('trim', array_map('strtolower', explode(',', $stats)));

			// unlike most other stats, popagree is hit when the esig page is displayed -- not
			// when it is submitted... note that this will break if there are intermediate processing
			// pages before the esig page
			if (strtolower($this->config->site_type_obj->pages->{$page}->next_page) === 'esig')
			{
				$stats[] = 'popagree';
			}

			// Similar to popagree, popconfirm is hit when the page is displayed, therefor, simlar situation
//			if (strtolower($this->config->site_type_obj->pages->{$page}->next_page) === 'ent_online_confirm')
//			{
//				$stats[] = 'popconfirm';
//			}

			// get an array containing old and new names
			$stats = Stats::Translate($stats);

			$prequal_stats = Stats::Translate(array('prequal', 'pre_prequal'));
			$cost_action_stat_map = array(
				'pre_prequal' => 'pre_prequal_pass',
				'prequal' => 'nms_prequal ',
			);

			$map_cost_action_stats = array();
			foreach ($stats as $stat)
			{
				if (isset($cost_action_stat_map[$stat]))
				{
					$map_cost_action_stats[] = $cost_action_stat_map[$stat];
				}
			}

			// Lets force captcha verification here, if we have a cost action
			//   in any of the subsequent pages 	[LR]
			$cost_action = strtolower($this->config->cost_action);
			if(!in_array($cost_action, $prequal_stats) && !in_array($cost_action, $map_cost_action_stats))
			{
				$is_cost_action = in_array('submit', $stats); // TRUE;
			}
			else
			{
				if ($cost_action=='pre_prequal_pass')
				{
//					if ($this->config->site_name=='acescashloan.com')
//					{
//						// see if this page triggers our cost action
//						$is_cost_action = (in_array($cost_action, $stats) || in_array($cost_action, $map_cost_action_stats));
//					}
				}
				else
				{
					// see if this page triggers our cost action
					$is_cost_action = (in_array($cost_action, $stats) || in_array($cost_action, $map_cost_action_stats));
				}
			}

		}

		return $is_cost_action;

	}

	/**

		@desc Return a string identifying a site "classification": its
			front-end type (TSS, SOAP, or SAMPLE)
		@return string Site classification

	**/
	public function Site_Class()
	{

		// assume we're a TSS front-end
		$class = self::FE_CLASS_TSS;

		// True SOAP sites
		if (substr($this->config->site_type, 0, 5) === 'soap') $class = self::FE_CLASS_SOAP;

		// "SOAP" Sample sites
		if (defined('SOAP_SAMPLE')) $class = self::FE_CLASS_SAMPLE;

		return $class;

	}


	/**
	* @return object
	* @desc Generate standard return vars to front end and prepare tracking pixels
	*
	**/
	public function Gen_Return_Object()
	{
		// Rules:
		// Failed lead from enterprise sites and from Disallowed States: Redirect to Last Chance Cash Advance page.
		// Failed lead from enterprise sites but not from Disallowed States: Redirect to Last Chance Cash Advance page.
		// Failed lead from marketing  sites and from Disallowed States: Show Thank-You page and redirect to Disallowed State Cash Advance page.
		// Failed lead from marketing  sites but not from Disallowed States: Redirect to Last Chance Cash Advance page. 
		//
		// Comments for this if statement: 
		// Why we have to handle it here? Because function self::Page_Handler() would 
		// return at function call to self::App_Completed_Check() when application status 
		// is FAILED because of WV_VG_WV leads. In this case, we won't continue running
		// self::Page_Handler() and won't execute self::Run_Next_Page_Cases(). 
		// 
		// Case 1
		// Case 2.x are cases specified in self::Run_Next_Page_Cases() when $this->next_page = 'app_declined';
		// Case 3.x are cases specified the same as tickets G#6972 and G#8246;
		// Case 4 is the case specified by the ticket (G#10066).
		// #10066 [DY]		
		if (($this->next_page == 'app_declined') // Case 1
			&& ((string)$this->config->site_language != 'spa') // Case 2.1
			&& ($_SESSION['data']['no_refs'] != TRUE) // Case 2.2
			&& ($this->config->enable_rework) // Case 2.3 
			&& (!$this->config->call_center) // Case 3.1 
			&& (!$this->isReact()) // Case 3.2
			&& !Enterprise_Data::isEnterprise($this->property_short) // variation of Case 3.3: marketing sites only
			&& $this->isDisallowedState()) // Case 4
		{
			unset($_SESSION['data']['redirect']); // this may be set in self::Run_Next_Page_Cases() when $this->next_page == 'app_declined'
			
			$this->next_page = 'bb_thanks';
			$redirect_url = $this->getRedirectURLforPageAppDeclined();
			$redirect_time = Abstract_Vendor_Post_Implementation::REDIRECT;
			$_SESSION['data']['thanks_content'] = <<<PAGE
<br/>
<p>Sorry we are unable to match you with a loan, but we have prequalified you to
receive cash for your unwanted jewelry and gold.  You will be redirected or
please click the link <b><a href="$redirect_url">here</a></b>.</p>

<script type="text/javascript">
var script_expression = "document.location.href = '$redirect_url'";
var msecs = $redirect_time * 1000;
setTimeout(script_expression, msecs);
</script>
PAGE;
		}	
	
		// Mantis #12161 - Had to set up a redirect expire to kill the redirect url value
		if(!empty($_SESSION['data']['redirect_expire']) && $_SESSION['data']['redirect_expire'] < time())
		{
			unset($_SESSION['data']['redirect']);
			unset($_SESSION['data']['redirect_expire']);
		}

		/*
			!NEEDS TO BE REWRITTEN!
			mikeg -- started to clean up
		*/
		$return_obj = new stdClass();

		// Set errors, user messages, page to send back, and all validated collected data.

		// Add Error Messages From Template Message Object Errors
		$this->Add_Errors($this->template_messages->Get_Error_Message_Array());

		// If we have any errors add them to return object
		$return_obj->errors = ($this->errors) ? $this->errors : NULL;

		// log errors in session error_trace
		$this->Log_Errors();

		// And log the errors to SQL as well
		require_once( BFW_CODE_DIR . 'err_log.class.php');
		$db = Server::Get_Server($this->config->mode, 'BLACKBOX');
		$app_id = $this->Get_Application_ID();
		$this->err_log = new Err_Log($app_id, $this->config->promo_id, $this->config->site_type, $this->current_page, $this->config->mode, $this->sql, $db['db']);
		$this->err_log->Set_Error($this->errors);
		$this->err_log->Write_Errors();

		// Set user messages if any
		if(count($this->template_messages->Get_User_Message_Array()) > 0)
		{
			$return_obj->user_messages = $this->template_messages->Get_User_Message_Array();
		}
		else
		{
			$return_obj->user_messages = NULL;
		}

		//GF 5103 New DoubleZones Code [TF] make the front end site-aware
		if(!empty($_SESSION['blackbox']['winner']))
		{
			$return_obj->data['bb_winner'] = $_SESSION['blackbox']['winner'];
		}

		$this->CSR_Thank_You($return_obj);


		//  set page
		if (!$this->current_page || $this->current_page == "default")
		{
			$return_obj->page = $this->config->site_type_obj->page_order[0];
		}
		elseif ($return_obj->errors || !$this->next_page)
		{
			$return_obj->page = $this->current_page;
		}
		elseif (!$return_obj->errors && $this->next_page)
		{
			$return_obj->page = $this->next_page;
		}

		if($return_obj->page == 'try_again' || $return_obj->page == 'try_again_v2')
		{
			//hit a stat for #11911 [TP]
			Stats::Hit_Stats('try_again',$this->session,$this->event,$this->applog,$this->Get_Application_ID());
		}

		if($this->is_yellowpage)
		{
			if(defined('DATAX_DOWN') && DATAX_DOWN === true)
			{
				$return_obj->page = 'datax_down';
			}
		}

		$_SESSION['return_page'] = $return_obj->page;

		//Updates for UFC's visual sciences.  They want to track which pages
		//are being hit in customer service, but we override the pages and
		//only return ent_status, so it wouldn't report correctly.
		if(!empty($this->normalized_data['ent_status_override']))
		{
			$return_obj->original_page = $this->normalized_data['ent_status_override'];
		}
		else
		{
			$return_obj->original_page = $return_obj->page;
		}

		// set data
		$return_obj->data = $_SESSION['data'];

		//GROOPZ_SWITCH (this switch is set in failover_data table, see failover_config)
		$this->client_state['groopz_switch'] = USE_GROOPZ;

		//check if accountnow field is set in webadmin1 to turn on accountnow offer
		if (isset($this->config->accountnow))
		{
			$this->client_state['accountnow_link'] = $this->config->accountnow;
		}

		// if we're displaying the page that will trigger our
		// cost_action, we need to display a CAPTCHA
		// always display if soap_oc is passed in for soap sites [RSK]
		if ( isset($this->config->display_captcha) && $this->Is_Cost_Action($return_obj->page) || $this->soap_oc)
		{
			// No longer need to pass the whole link, just whether to display it or not. [BF]
			$return_obj->data['captcha_link'] = 1;
		}

		// set custom messages, we won't allow it to overwrite any current data
		foreach($this->template_messages->Get_Custom_Message_Array() as $label=>$message)
		{

			if(isset($return_obj->data[$label]) == FALSE)
			{
				$return_obj->data[$label] = $message;
			}

		}

		// If theres an eds_page ( legacy style, page we generate) then send it back.
		if( $this->eds_page != NULL && is_array($this->eds_page) )
		{
			// eds_page array structure
			//$this->eds_page = array('content' => "test data", 'type' => 'html' , 'action' => 'standard');
			$return_obj->eds_page = $this->eds_page;
		}


        //Put thanks content in eds page content for old sample "soap" sites
        if($this->next_page == "bb_thanks" && $this->config->site_type == "blackbox" &&
           (isset($return_obj->data["thanks_content"]) || isset($return_obj->data["redirect_time"])))
        {
        	$return_obj->eds_page = array();
        	if(isset($return_obj->data["thanks_content"]))
        	{
            	$return_obj->eds_page['content'] = $return_obj->data["thanks_content"];
        	}
        	else
        	{
        		$redirect = "<p>Congratulations, you have been approved with one of our lending partners.<br>
							You will be redirected to their site in a moment. <br>
							If you are not redirected <a href='" . $return_obj->data['online_confirm_redirect_url'] .
							"'>click here</a>." . $return_obj->data['redirect_time'];
        		$return_obj->eds_page['content'] = $redirect;
        	}
        }

		// only pass back session if we are not in live
		if( $_SESSION["config"]->mode != "LIVE" )
		{
			$return_obj->session = $_SESSION;
		}

		//********************************************* 
		// GForge #9534 [AuMa]
		// We are moving this down here so we don't lose
		// the stat information if the user has an
		// error and has to repeat the page (in which
		// case the exit strategy offers disappear from
		// the user.
		//********************************************* 
		if (!is_array($_SESSION['unique_page_stat']))
		{
			$_SESSION['unique_page_stat'] = array();
		}

		if(!isset($_SESSION['unique_page_stat'][$return_obj->page]) || $return_obj->page == 'pwarb_exit')
		{
			$_SESSION['unique_page_stat'][$return_obj->page] = $_SESSION['current_page_stat'];
		} 
		else 
		{
			foreach($_SESSION['current_page_stat'] as $key => $val)
			{
				$_SESSION['unique_page_stat'][$return_obj->page]->$key = $val; 
			}
		}

		//********************************************* 

		if(!empty($this->config->exit_strategy))
		{
			//********************************************* 
			// Changed the unique page stat to pass the one
			// for the actual page that you're on
			//********************************************* 
			$return_obj->exit_strategy = array(
				'unique_page_stat' => $_SESSION['unique_page_stat'][$return_obj->page],
				'unique_stat' => $_SESSION['unique_stat'],
				'data' => $this->config->exit_strategy
			);

			//For crappy bid4prizes
			if($this->normalized_data['es_offer'] && is_numeric($this->normalized_data['phone_cell']))
			{
				$return_obj->data['cell1'] = substr($this->normalized_data['phone_cell'],0,3);
				$return_obj->data['cell2'] = substr($this->normalized_data['phone_cell'],3,3);
				$return_obj->data['cell3'] = substr($this->normalized_data['phone_cell'],6,4);
			}
		}

		// if we're a "SOAP" sample site,
		// we need to send the esig page back
		if (($this->Site_Class() === self::FE_CLASS_SAMPLE) && $this->esig_doc)
		{

			// get the esig page for the soap sample people
			include_once('documents/esig_soap_sample.php');
			$return_obj->eds_page = array('content'=>NULL, 'type'=>'html', 'action'=>'standard');

		}

		if ($this->esig_doc)
		{
			$return_obj->data['esig_doc'] = $this->esig_doc;
		}

		if(!empty($this->errors) && $this->current_page == 'ent_profile')
		{
			//We need to make sure the values stored in cs don't overwrite the values
			//they submitted on the form if there were errors.
			$return_obj->data = array_merge( $_SESSION['cs'], $return_obj->data );
		}
		else
		{
			if( isset( $_SESSION['cs'] ) && is_array( $_SESSION['cs'] ) )
			{
				$return_obj->data = array_merge( $return_obj->data, $_SESSION['cs'] );
			}
			else if( $this->cs && is_array( $this->cs ) )
			{
			    $return_obj->data = array_merge( $return_obj->data, $this->cs );
			}
		}

		$return_obj->data['ip_address'] = $_SERVER['REMOTE_ADDR'];

		// Rename keys per client side request.
		$map = array();
		$map['promo_id'] = "eds_promo_id";
		$map['promo_sub_code'] = "eds_promo_sub_code";
		$map['site_name'] = "eds_site_name";
		$map['end_pop'] = "eds_pop_end";
		$map['pop_arg'] = "eds_pop_arg";
		$map['support_phone'] = "eds_phone_support";
		$map['support_fax'] = "eds_phone_fax";
		$map['company_phone'] = "company_phone";
		$map['property_name'] = "eds_name_property";
		$map['webmasters_url'] = "eds_webmaster_url";
		$map['disable_advertising'] = "eds_disable_advertising";
		$map['unique_id'] = "eds_unique_id";
		$map['name_view'] = "eds_name_view";
		$map['legal_entity'] = "eds_legal_entity";
		$map['property_short'] = "eds_property_short";
		$map['collections_phone'] = "collections_phone";
		$map['egc_promo_id'] = 'egc_promo_id';
		$map['ctc_promo_id'] = 'ctc_promo_id';

		// If its in our map add it to the return object.
		foreach($this->config as $key => $value)
		{
			if( isset($map[$key]) )
			{
				$return_obj->data[$map[$key]] = $value;
			}
		}

		// If we're on the enterprise confirm page and we're a CFE company,
		// get the minimum fund amount and increment from the CFE rules
		// Added for new abilities within eCash to make this dynamic [AE]
		if ($return_obj->page == 'ent_online_confirm' && Enterprise_Data::isCFE($this->property_short))
		{
			$loan_type = ($this->title_loan) ? OLPECash_LoanType::TYPE_TITLE : OLPECash_LoanType::TYPE_PAYDAY;
			$cfe_rules = new OLPECash_CFE_Rules($this->property_short, strtoupper(BFW_MODE), $loan_type);
			$return_obj->data['min_fund_amount'] = $cfe_rules->getMinFundAmount($_SESSION['is_react']);
			$return_obj->data['inc_fund_amount'] = $cfe_rules->getFundAmountIncrement();
		}

		if($this->ent_prop_list[strtoupper($this->property_short)]['new_ent'])
		{
			$prop_data = $this->ent_prop_list[strtoupper($this->property_short)];

			$return_obj->data['eds_support_email'] = 'customerservice@' . $this->config->site_name;
			$return_obj->data['new_ent'] = TRUE;

			if(preg_match('/^ent\_/is', $this->current_page))
			{
				$return_obj->data['eds_phone_support'] = $prop_data['cs_phone'];
				$return_obj->data['eds_phone_fax'] = $prop_data['cs_fax'];
			}
		}

		// pass back out global key
		$return_obj->data["global_key"] = $_SESSION["statpro"]["global_key"];


		// run our blackbox prequal
		$winner = $this->BlackBox_Prequal();

		//If we failed the Prequal, do stuff
		if($winner === FALSE && isset($this->datax_decision))
		{
			if($this->datax_decision['DATAX_IDV'] == 'N')
			{
				//We're ecashnewapp so fail the app
				if(isset($_SESSION['ecashnewapp']))
				{
					$return_obj->page = 'app_declined';
					$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
					$app_campaign_manager->Insert_Application($app_id, $this->normalized_data, FALSE);
					$app_campaign_manager->Update_Application_Status($app_id, 'FAILED');
				}
			}
		}


		//Set Selected Tier
		if(isset($_SESSION['blackbox']) && isset($_SESSION['blackbox']['tier']) &&
		   isset($_SESSION['config']->show_tier_in_response))
		{
			if($_SESSION['blackbox']['original_tier'] > 1)
			{
				$return_obj->data['tier'] = 2;
			}
			else
			{
				$return_obj->data['tier'] = 1;
			}
		}

		// This will be false if nothing is set.
		$pixels = $this->session->Fetch_Pixels();

		if( $pixels )
		{
			// Tracking pixel exceptions - ie return pixel only if certain conditions are met
			$return_obj->data["eds_tracking_pixel"] = $pixels;
		}

		// pass back the client_state
		$return_obj->client_state = $this->client_state;

		if ($this->Get_Application_ID())
		{
			$return_obj->data["application_id"] = $this->Get_Application_ID();
		}

		// <HACK by="Andrew Minerd">
		// Generate events for "SOAP" sample sites

		$event = NULL;

		switch(strtolower($return_obj->page))
		{

			case 'app_2part_page02':
				$event = 'app_2part_page01_completed';
				break;

			case 'esig':
				$event = 'app_2part_page02_completed';
				break;

			case 'bb_thanks':
				$event = 'app_2part_page02_completed';
				break;

			case 'app_done_paperless':
				$event = 'app_completed';
				break;

			case 'app_declined':
				$event = 'app_declined';
				break;

		}

		if ((!array_key_exists('events', $_SESSION)) || (!is_array($_SESSION['events'])))
		{
			$_SESSION['events'] = array();
		}

		if ($event && (!in_array($event, $_SESSION['events'])))
		{
			$return_obj->event = array($event);
			$_SESSION['events'][] = $event;
		}

		// </HACK>

		//  return disallowed states to the front end client state
		if (is_array($this->config->disallowed_states) && !$_SESSION['client_state']['disallowed_states'])
		{
			$return_obj->client_state['disallowed_states'] = $this->config->disallowed_states;
		}

		if( $_SESSION['blackbox']['winner'] )
		{
			$bb_winner = Enterprise_Data::resolveAlias($_SESSION['blackbox']['winner']);

			$return_obj->data['customer_service_email'] = "customerservice@" . $this->ent_prop_list[$bb_winner]['site_name'];
			$return_obj->data['customer_service_phone'] = $this->ent_prop_list[$bb_winner]['phone'];
			$return_obj->data['customer_service_link'] = $this->ent_prop_list[$bb_winner]['site_name'];
		}

		if($_SESSION["return_visitor"] && $_SESSION["process_rework"])
		{

			$this->application_id = $_SESSION["application_id"];
			$this->Online_Rework_Session_Reset();
			$this->Event_Log();
			Stats::Hit_Stats("rework_return" , $this->session, $this->event, $this->applog, $this->application_id);
		}

		// Determine footer and "How It Works" amount content
		if(isset($this->config->footer_amount) && strlen($this->config->footer_amount) > 0)
		{
			$return_obj->client_state['footer_amount'] = $this->config->footer_amount;
		}

		$return_obj->client_state['site_company'] = $this->config->site_company;
		$return_obj->client_state['datran_pop'] = $this->config->sitelifter;
		$return_obj->client_state['thankyoupath_pop'] = $this->config->thankyouexit; // added task #11734


		$return_obj->client_state['allow_popups'] = $this->allow_popups;

		if(isset($_SESSION['bb_vs_thanks']))
		{
			$return_obj->client_state['bb_vs_thanks'] = $_SESSION['bb_vs_thanks'];
		}

		
		if (($this->next_page == 'ent_thankyou'
			|| ($this->current_page == 'ent_thankyou' && empty($this->next_page)))
			&& (
				(Enterprise_Data::siteIsCompany(Enterprise_Data::COMPANY_CLK)
					&& (SECOND_LOAN_CAP_CLK > 0 && mt_rand(1, 100) <= SECOND_LOAN_CAP_CLK))
				|| (Enterprise_Data::siteIsCompany(Enterprise_Data::COMPANY_IMPACT)
					&& (SECOND_LOAN_CAP_IMP > 0 && mt_rand(1, 100) <= SECOND_LOAN_CAP_IMP))
			)
			&&  !$this->event->Check_Event($this->Get_Application_ID(), EVENT_SECOND_LOAN)
		)
		{
			$link = 'CashAngelsOnline.com';
	
			//Sprinkle on some lame hacks to get it to work nicely on dev/RC...
			switch (strtoupper(BFW_MODE))
			{
				case 'LOCAL':
					preg_match('/(\.ds\d{2}\.tss)/is', $_SERVER['SERVER_NAME'], $match);
					$link = 'pcl.3.' . $link . $match[1];
				break;
	
				case 'RC':
					$link = 'rc.' . $link;
				break;
			}
	
			$second_loan = array(
				'link' => $link,
				'options' => array(
					'page' => 'second_loan',
					'application_id' => base64_encode($this->Get_Application_ID()),
					'promo_id' => (!Enterprise_Data::siteIsCompany(Enterprise_Data::COMPANY_IMPACT) && $this->Second_Loan_Check()) ? 30596 : 31895, // If it passes the check, we can still sell to CLK.
					'promo_sub_code' => 'second_loan',
					'force_new_session' => 1
				)
			);
	
			if (isset($_SESSION['data']['no_checks']) && strtoupper(BFW_MODE) != 'LIVE')
			{
				$second_loan['options']['no_checks'] = '1';
			}
	
			$return_obj->data['second_loan'] = $second_loan;
		}

		//Add server to session
		$_SESSION["data"]["process_server"] = $_SERVER["SERVER_ADDR"];

		//Return the fail promo ID so that it can be used in the link on the landing page
		if($this->config->direct_mail_reservation
			&& ($this->current_page == 'default' || $this->current_page == 'app_3part_page01')
			&& !isset($this->config->bypass_res_page))
		{
			require_once(BFW_MODULE_DIR . 'ocs/ocs.php');

			try
			{
				$ocs = new OCS('OLP', $this->config->mode);
				$return_obj->data['dm_fail_promo'] = $ocs->Get_Fail_Promo();
			}
			catch(Exception $e)
			{
				//If it fails, just use the last one we know it was
				$return_obj->data['dm_fail_promo'] = 29687;
			}
		}

		//GFORGE [#4290] LeadRev - page 1 popunder [TF]
		//Only show on the first page [CB]
		if(isset(SiteConfig::getInstance()->pageone_strategy) && $this->current_page == 'default')
		{
			//********************************************* 
			// Changed the unique page stat to pass the one
			// for the actual page that you're on
			//********************************************* 
			$return_obj->pageone_strategy = array(
				'unique_page_stat' => $_SESSION['unique_page_stat'][$return_obj->page],
				'unique_stat' => $_SESSION['unique_stat'],
				'data' => SiteConfig::getInstance()->pageone_strategy
			);

			$return_obj->client_state['allow_global_pop'] = array('pageone_strategy');
		}

		$return_obj->client_state['config'] = $this->Scrub_Config();

		// added for Mantis 9607/9608 - UpSellIt - even though it shows up in the config... [AuMa]
		// You never know it might get zapped later, so I'm storing it in client state
		// as well.
		$return_obj->client_state['upsellit_siteid'] = $this->config->upsellit_siteid;
		$return_obj->client_state['upsellit_qs'] = $this->config->upsellit_qs;

		return $return_obj;

	}


	private function Second_Loan_Check()
	{
		$result = FALSE;

		//Make sure event obj is created
		if(!isset($this->event))
		{
			$this->Event_Log();
		}

		//We want to make sure we're on the thank you page and that it's not a second loan
		if(!empty($_SESSION['CASHLINE_RESULTS']))
		{
			//Then we want to make sure they would actually qualify for a second loan
			//In other words, they pretty much have to be a new customer.
			$cashline_result = array_intersect(array('underactive', 'overactive', 'denied', 'bad', 'do_not_loan'), array_map('strtolower', $_SESSION['CASHLINE_RESULTS']));

			//Only online confirmation apps
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$olp_process = $app_campaign_manager->Get_Olp_Process($this->Get_Application_ID());

			if(empty($cashline_result) && $olp_process == 'online_confirmation')
			{
				//Then we need to grab all these dates for the limit checking.
				//We can probably remove these once (if?) they decide to fully implement this.
				$sunday = date('Y-m-d', strtotime('-' . date('w') . ' days', time()));
				$saturday = date('Y-m-d', strtotime('+7 days', strtotime($sunday)));

				try
				{
					//We need to check weekly totals for now.  This is all hard-coded,
					//and once again, can probably be removed when all is said and done.
					$query = "SELECT SUM(`count`) AS total FROM stat_limits WHERE stat_name = 'second_loan' AND stat_date BETWEEN '{$sunday}' AND '{$saturday}' GROUP BY stat_name";
					$weekly_result = $this->sql->Query($this->database, $query);
					$weekly_total = $this->sql->Fetch_Column($weekly_result, 'total');

					/**
					 * Removed the monthly check and lowered the weekly check to 60 [CB/#8814]
					 */
					if($weekly_total < 60)
					{
						$result = TRUE;
					}
				}
				catch(MySQL_Exception $e)
				{
					$result = FALSE;
				}
			}
		}

		return $result;
	}

	public function Cap_Submitlevel1()
	{
		$limits = new Stat_Limits($this->sql, $this->database);
		$limit_stat = "submitlevel1";
		return $limits->Over_Limit($limit_stat, $this->config);
	}


	/**

		@desc Runs a prequal app through BlackBox to
			determine, to the best of our ability, if
			we can go to CLK/NMS.
		@return string The winner

	*/
	function BlackBox_Prequal()
	{
		//Check to see if record already exhists inside memcache, this is set inside the Cache_Duplicate_Leads.php module
		if($_SESSION['duplicate_lead'] == TRUE)
			return;

		$fire_on = array('coreg','prequal', 'base', 'pre_prequal');
		$refresh = array('post', 'non_nms_prequal', 'pre_prequal_pass', 'pre_prequal_fail');

		// get the stats for this page;
		$stats = $this->config->site_type_obj->pages->{$this->current_page}->stat;
		$stats = explode(',', $stats);

		// figure out which stat we're firing on
		$hit_it = reset(array_intersect($fire_on, $stats));

		// check to see if we've hit either of these
		// stats already, and don't run it again if we did
		$refresh = (count(array_intersect($refresh, array_keys(get_object_vars($_SESSION['unique_stat'])))) > 0);

		//Still need to hit nms_prequal on lifepayday
		if($hit_it == 'base'
			&& ($this->config->site_type == 'blackbox.1fieldcell.online.confirmation'
				|| $this->config->direct_mail_reservation)
		)
		{
			$refresh = false;
		}
		
		// GForge #5793 - Check to see if this specific page should not run Prequal. [RM]
		if (isset(SiteConfig::getInstance()->prequal_bypass_pages))
		{
			$bypass_pages = explode(',', SiteConfig::getInstance()->prequal_bypass_pages);
			$bypass_pages = array_map('trim', $bypass_pages);
			
			// If in our list of pages to bypass prequal, don't "hit" prequal.
			if (in_array($this->current_page, $bypass_pages))
			{
				$hit_it = FALSE;
			}
		}

		if(isset($this->application_id) && $hit_it && (!$refresh) && (empty($this->errors)))
		{

			// ALL CAPS REMOVED

			/*
			// limits object
			$db = Server::Get_Server($this->config->mode, 'BLACKBOX');
			$limits = new Stat_Limits($this->sql, $db['db']);

			// which stat are we capped on?
			$limit_stat = ($hit_it == 'pre_prequal') ? 'pre_prequal_pass' : 'nms_prequal';

			// make sure we're not over our nms_prequal
			// (or pre_prequal_pass) limit
			if (!$limits->Over_Limit($limit_stat, $this->config))
			{
			*/

				$tiers = ($this->Is_Impact() || $this->Is_Agean() || isset($_SESSION['ecashnewapp'])) ? array(0) : array(1);

				// have we run blackbox already?
				$winner = $this->blackbox;

				if (!$winner)
				{

					if ($hit_it == 'pre_prequal')
					{
						// for now, the $_SESSION var set in FLE_Collect
						// is the flag to run the pre_prequal dupe check
						$this->Pre_Prequal_Collect();
					}

					// initialize blackbox and don't
					// run rules we don't have data for
					$blackbox = $this->Configure_Blackbox(NULL, NULL, MODE_PREQUAL);

					//For lifepayday, we run a duplicate cell check offer.
					if($this->config->site_type == 'blackbox.1fieldcell.online.confirmation' && $hit_it == 'pre_prequal')
					{
						$winner = $blackbox->runRule(NULL, 'dupe_cell');

						if($winner !== FALSE)
						{
							$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
							$app_campaign_manager->Insert_Cell_Phone($this->Get_Application_ID(), $this->normalized_data['phone_cell']);
						}
					}
					else
					{
						// do we qualify, eh?
						$winner = $blackbox->pickWinner();
						$this->datax_decision = $blackbox->getDataXDecision();
					}
				}
				else
				{

					// don't hit the stat if we didn't go to tier 1
					if (!in_array($winner['tier'], $tiers)) $winner = FALSE;

				}

			/*
			}
			else
			{
				// didn't prequal
				$winner = FALSE;
			}
			*/

			// what stat should we hit, prequal or pre_prequal notifiers
			if ($hit_it == 'pre_prequal')
			{
				$stat = ($winner === FALSE) ? 'pre_prequal_fail' : 'pre_prequal_pass';
			}
			elseif($hit_it != 'coreg')
			{
				// hit our prequal stat
				$stat = ($winner === FALSE) ? 'non_nms_prequal' : 'nms_prequal';

				// If we're on ecashapp.com and we aren't a react, we don't want to hit $stat
				if($_SESSION['config']->site_name == 'ecashapp.com' &&
					!isset($this->blackbox["react"]))
				{
					$stat = NULL;
				}
			}

            if(isset($stat)) Stats::Hit_Stats($stat,
                                              $this->session,
                                              $this->event,
                                              $this->applog,
                                              $this->application_id);

		}

    	return $winner;

	}

	/**
		@publicsection
		@public
		@fn void BlackBox_Postqual()
		@brief
			Blackbox Qualify for Confirmation

		If the customer changes their data on the confirmation page we need to rerun qualify. [RayL]

		@param boolean $bypass_used_info If true, blackbox will bypass the used info check

		@return void
	 */
	private function BlackBox_Postqual($bypass_used_info = FALSE)
	{

		$return = false;
		//Make sure application id is set before running
		if (is_object($this->event) && $this->Get_Application_ID())
		{

			// Data that we want to Qualify Against
			// this may be expanded upon as time goes on.
			//If it's a react, don't run blackbox
			// email confirmation
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database,$this->applog);
			$olp_process = $app_campaign_manager->Get_OLP_Process($this->Get_Application_Id());
			$is_react = ((isset($_SESSION['is_react']) && $_SESSION['is_react'] == true) ||
					preg_match('/_react$/',$olp_process));
			if ($this->current_page == 'ent_confirm' || $this->current_page == 'ent_confirm_legal')
			{
				$new_data["bank_aba"] = $this->normalized_data['bank_aba'];
				$new_data["bank_account"] = $this->normalized_data['bank_account'];
				$new_data["social_security_number"] = $_SESSION['cs']['social_security_number'];
				$mode = ($is_react === true) ? MODE_ECASH_REACT : MODE_CONFIRMATION;
				$blackbox = $this->Configure_Blackbox(NULL, $new_data, $mode);
				$comment = "Changed bank info on confirmation. Overactive aba/account combination";
			}
			else // online confirmation
			{
				$new_data["bank_aba"] = $_SESSION['cs']['bank_aba'];
				$new_data["bank_account"] = $_SESSION['cs']['bank_account'];
				$new_data["social_security_number"] = $_SESSION['cs']['social_security_number'];
				$new_data["email_primary"] = $_SESSION['cs']['email_primary'];
				$new_data["phone_home"] = $_SESSION['cs']['phone_home'];
				$new_data["state_id_number"] = $_SESSION['cs']['state_id_number'];
				$olp_process = $app_campaign_manager->Get_OLP_Process($this->Get_Application_Id());
				$mode = MODE_ONLINE_CONFIRMATION;
				$blackbox = $this->Configure_Blackbox(NULL, $new_data, $mode);
				$comment = "Overactive";
			}

			//Force Event Log to be created
			$this->Event_Log(TRUE);

//			$blackbox->restrict(array('FIND' => $this->property_short));

			$winner = $blackbox->pickWinner(FALSE, $bypass_used_info);

			// force decline if they changed info on email confirmation page
			if (!$winner )		
			{
				$app_id = $this->Get_Application_ID();
				// Update OLP
				$app_campaign_manager->Update_Application_Status($app_id, 'FAILED');

				if($is_react === true)
				{
					$this->Force_Fail($app_id, $this->property_short, 'Failed confirmation check.');
				}
				else
				{
					// Update LDB
					$this->Force_Decline($app_id, $this->property_short, $comment);
				}

				$this->next_page = 'app_declined';
				$return = false;

				// We may want to hit a stat if we fail or if we are even running this check
				Stats::Hit_Stats('confirm_bb_fail', $this->session, $this->event, $this->applog, $app_id, NULL, TRUE);

			}
			else
			{
				$return = true;
			}
		}

		return $return;

	}

	/**
		@publicsection
		@public
		@fn void Reset_For_Another_Run()
		@brief
			Resets object so we can run page_handlerpage_handler again

		If you want to run the page_handler for OLP again after the initial
		run we need to reset some variables. Right now I'm just doing one
		but I guess that will change so feel free to update

		@return void
	*/
	/*
	public function Reset_For_Another_Run()
	{
		$this->next_page = NULL; // If next page is eqaul to true then Set_Page will not update $this->next_page
	}
	*/


	public function Load_Holidays()
	{
		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		$property_short = (isset($this->ent_prop_short_list[$_SESSION['config']->site_name]))
			? $this->ent_prop_short_list[$_SESSION['config']->site_name]
			: $this->config->property_short;
		// We grab the holiday list from the holiday table in ldb.
		$this->Setup_DB($property_short);
		$_SESSION['holiday_array'] = $app_campaign_manager->Get_Holidays($this->db);

	}

	/**
		@publicsection
		@public
		@fn return stdClass Calculate_Paydates()
		@brief
			Generate future paydates.

		Builds the paydate model from the raw form data,
		then generates four future paydates and returns them,
		along with any validation or processing errors.

		@desc prequalification
		@return Returns a stdClass object, with two
			members: errors, an array of errors that
			occured, and paydates, an array of future
			paydates.
	**/
	private function Calculate_Paydates()
	{
		if (!isset($this->collected_data['paydate']) && (!isset($this->collected_data['pay_date1']) && !isset($this->collected_data['pay_date2'])))
			return FALSE;


		$errors = array();
		$result = FALSE;

		if (!count($_SESSION['holiday_array']))
		{
			$this->Load_Holidays();
		}

		// build the holiday array so that it's
		// acceptable to both models
		if (isset($_SESION['holiday_array']) && is_array($_SESSION['holiday_array']))
		{
			foreach($_SESSION['holiday_array'] as $holiday)
			{
				$holidays[$holiday] = $holiday;
			}
		}

		// instantiate paydate_calc
		$paydate_calc = new Pay_Date_Calc_1($holidays);

		if (is_array($this->normalized_data['paydate']))
		{
			// new paydate model
			$model = new Paydate_Model();
			$result = $model->Build_From_Data($this->normalized_data['paydate']);
		}
		elseif ($this->normalized_data['pay_date1'] || $this->normalized_data['pay_date2'])
		{

			$pay_date_1 = $this->normalized_data['pay_date1'];
			$pay_date_2 = $this->normalized_data['pay_date2'];
			$frequency = $this->normalized_data['income_frequency'];

			// We're validating accept_level inside pay_date_validation, so we need the limits object [LR]
			$temp_holidays = array();
			$validate = new Pay_Date_Validation($this->normalized_data, $temp_holidays);
         $validate->setStatsInformation($this->session, $this->event, $this->applog, $this->Get_Application_Id());
			$valid = $validate->Validate_Paydates();

			if ((!isset($valid['errors'])) || (!count($valid['errors'])))
			{

				// old paydate model
				$model_data = $paydate_calc->Generate_Paydate_Model($pay_date_1, $pay_date_2, $frequency);

				// import this model
				$model = new Paydate_Model();
				$model->Import($model_data);

				$result = TRUE;

			}
			else
			{

				$result = $valid['errors'];
			}

		}

		if ($result === TRUE)
		{

			$model_name = $model->Model();
			$model_data = $model->Model_Data();

			// calculate paydates
			//$pay_dates = $this->paydate_calc->Generate_Paid_On_Dates($model_name, $model_data);
            //Force DD on because its also calculated in qualify_2
			$pay_dates = $paydate_calc->Calculate_Payday($model_name, date("Y-m-d"), $model_data, 4, TRUE, TRUE);
			//Override First Paydate if on holiday
			$model_data['next_pay_date'] = $pay_dates[0];

			// get last paydate
			$last_pay_date = $paydate_calc->Calculate_Previous_Payday($model_name, date('Y-m-d'), $model_data);
			$model_data['last_pay_date'] = $last_pay_date;

			if (!count($pay_dates['errors']))
			{
				// save info to session
				$_SESSION['data']['paydate_model'] = $model_data;
				$_SESSION['data']['paydates'] = $pay_dates;

				// set in normalized data for db record inserts
				$this->normalized_data['paydate_model'] = $model_data;
				$this->normalized_data['paydates'] = $pay_dates;
			}
			else
			{
				$errors = $pay_dates['errors'];
			}

		}
		else
		{
			$errors = $result;
		}


		// build our return object
		$return = new stdClass();
		if ($pay_dates) {
			$return->pay_dates = $pay_dates;
		}
		$return->errors = $errors;

		return($return);

	}

	/**
	* @return bool
	* @desc Log errors in session
	*
	**/
	public function Log_Errors()
	{
		if (count($this->errors))
			$_SESSION['error_trace'][$this->current_page .' - '. date("Y-m-d h:i:s")] = $this->errors;

		return TRUE;
	}


	/**
	* @return bool
	* @param current page string
	* @desc sets page vars
	*
	**/
	private function Set_Page()
	{
		// set locate variabel
		$page = $this->collected_data["page"];

		switch ( TRUE )
		{
			case (!$page || $page =='default') :
				$this->current_page = 'default';
			break;

			case ($page && $page != 'default'):
				// we're going to set popups because this isn't a landing page.

				$popup_safe_pages = array("ent_cs_confirm_start","ent_cs_confirm_react","ent_reapply",
                            "info_contactus_base","info_overview","info_faq","info_testimonials",
                            "cs_removeme","info_contactus_base","info_privacy","info_terms",
                            "info_adv","info_spam","info_webmasters");
				if (!in_array($page,$popup_safe_pages) && empty($_SESSION['ecashnewapp']))
				{
					$this->allow_popups = TRUE;
				}
				$this->current_page = $page;
				if (!$this->next_page)
					$this->next_page = ($this->config->site_type_obj->pages->{$page}->next_page) ? $this->config->site_type_obj->pages->{$page}->next_page : NULL;
			break;
		}

		// hit page trace
		$this->Page_Trace($this->current_page);

		return TRUE;
	}

	/**
	* @param $group string
	* @param $data array
	* @desc map $data to session[$group]
	**/
	public function Set_Session_Data( $group, $data )
	{
		if( is_array($data) )
		{
			foreach($data AS $field => $value)
			{
				if (!empty($value))
				{
					$_SESSION[$group][strtolower($field)] = $value;
				}
			}
		}
		return TRUE;
	}

	/**
	* @desc adds page to page_trace in session
	**/
	public function Page_Trace($page = NULL)
	{

		// default to the current page
		if (is_null($page)) $page = $this->current_page;

		// enforce a maximum size
		if (($count = count($_SESSION['page_trace'])) >= 1000)
		{
			array_splice($_SESSION['page_trace'], 0, ($count - 1000));
		}

		// add to the page trace
		$_SESSION['page_trace'][date("Y-m-d H:i:s")] = $page;

		return(TRUE);

	}



	/**
	* @return bool
	* @desc runs specific current page cases
	**/
	private function Run_Current_Page_Cases()
	{
		if ( (!$this->errors && !$this->override_errors) && $this->current_page)
		{
			switch( $this->current_page )
			{
				case 'accept_my_cash_reference':
					switch($this->config->mode)
					{
						case 'LOCAL':
							$site_url = 'http://bb.1.acceptmycash.com.'.BFW_LOCAL_NAME.'.tss';
							break;
						case 'RC':
							$site_url = 'http://rc.acceptmycash.com';
							break;
						case 'LIVE':
							$site_url = 'http://www.acceptmycash.com';
							break;
					}
					$_SESSION['data']['redirect'] = $site_url."/?promo_id=".$_SESSION['data']['promo_id'];
					break;
				case 'ent_online_confirm':
					// We always want to run Postqual on confirmation for the new process and
					// we need to bypass the used_info check

					// We do not perform post qualif for ECash App [RL]
					if(!$this->Is_Agean() && !$_SESSION['data']['react_confirm'] && !$_SESSION['data']['ecash_confirm'])
					{
						$valid = $this->BlackBox_Postqual(TRUE);
						if (!$valid) break;
					}

					if(isset($_SESSION['data']['ref_count'])
						&& intval($_SESSION['data']['ref_count'] < 2)
						&& $this->Is_CLK())
					{
						$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						$app_campaign_manager->Insert_Personal_Contacts($this->Get_Application_ID(), $this->normalized_data);
					}

	               // Insert the paydate model if they left it blank - if they are present
	               // and equal then execute the database query to update it. Mantis 12475 - [AuMa]
					if(isset($_SESSION['data']['bad_pay_dates']))
					{
						// in case app_campaign_manager is already defined, let's not do it again
						if(!isset($app_campaign_manager))
						{
							$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						}
						// Update qualify information and paydate information
						$app_campaign_manager->updatePaydateModel($this->Get_Application_ID(), $this->normalized_data);
						$_SESSION['data']['paydates'] = $this->normalized_data['paydates'];
						$qualify_info = $this->Build_Qualify_Info($this->normalized_data['fund_amount']);
						$_SESSION['cs']['qualify'] = $qualify_info;
						$app_campaign_manager->updatePayoffDate($this->Get_Application_ID(), $qualify_info['payoff_date']);
					}

					// process the page
					$return = $this->Customer_Service();
					// ECash wants a page to close for thier confirms [RL]
					if($_SESSION['data']['react_confirm'])
					{
						$application_id = $this->Get_Application_ID();
						$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						$olp_process = $app_campaign_manager->Get_Olp_Process($application_id);

						if($olp_process == 'ecashapp_new')
						{
							//Let the agent choose if they included both.
							if(!empty($_SESSION['cs']['phone_fax']) && !empty($_SESSION['cs']['email_primary']))
							{
								$this->next_page = 'ecash_fax_or_email';
							}
							//Otherwise, do it automatically.
							else
							{
								$type = (!empty($_SESSION['cs']['phone_fax'])) ? 'fax' : 'email';
								$this->ECashNewApp_Send_Docs($type);
							}
						}
						//Pretty sure we don't use this anymore, but better safe than sorry, eh?
						else
						{
							$content .= "<script language=javascript> window.close()</script>";
							$content .= "<br>Loan amount changed.";
							$content .= "<br><a href='' onclick='window.close()'>Click here to close this window.</a>";
							$this->eds_page = array('content' => $content, 'type' => 'html' , 'action' => 'standard');
							$this->next_page = "bb_extra";
						}
					}

					if($return != "continue") return $return;

					break;
				case 'app_online_confirm_rework':
					$this->Online_Rework(TRUE);
					$_SESSION['timer'][] = "AfterSelectWinner3 " . sprintf("%0.4f",microtime(true) - $this->start_time);
					if( ($this->config->coreg_egc == TRUE) && ($_SESSION['data']['offers'] == 'TRUE'))
					{
						$_SESSION['coreg_egc'] = TRUE;
						$this->Process_EGC_Coreg_Application();
					}

					break;
				case 'ent_confirm_legal':

					// If for what ever reason the customer changes their information
					// on the confirm page we need to run a BlackBox PostQualify
					if (isset($_SESSION['cs']['compared']) && ($_SESSION['cs']['compared'] === FALSE) && (!$_SESSION['cs']['confirmed']))
					{
						$valid = $this->BlackBox_Postqual();
						if (!$valid) break;
					}

					// Missing Break here is intentional, allow to run for below cases.
				case "ent_confirm":
				case "ent_cs_login":
				case "ent_cs_account":
				case "ent_status":
				case 'ent_profile':
				case 'ent_contact_us':
				case 'ent_docs':
				case 'ent_password_mailed':
				case 'ent_cs_password_change':
				case 'password_mailed':
				case 'ecash_sign_docs':
				case 'ent_online_confirm_legal':
				case 'ent_payment_opts':
				case 'ent_payment_submitted':
					// process the page
					$return = $this->Customer_Service();

					if($return != "continue")
						return $return;

					break;
				// Mantis #12245 - Added in for the fcpcard site to allow logins to Cubis CS site
				case "ent_cs_login_reload":
				case 'ent_cs_card_login':
					require_once('CS_Card_API.php');

					// Gather the needed data
					$cs_data = $this->Customer_Service();

					if($cs_data)
					{
						$cs_data['cs']['client_ip_address'] = $_SESSION['data']['client_ip_address'];
						$cs_data['cs']['page'] = $this->collected_data['module'];

						$this->Setup_DB($this->property_short);

						// Instantiate our CS_Card_API class
						$card_api = new csCardAPI($cs_data['cs'], $this->property_short, $this->db, $this->config->mode);

						$return = $card_api->redirectToCardCS();

						if(is_array($return))
						{
							$this->errors[] = $return['errors'];
							$this->current_page = $return['page'];
							return $return;
						}
					}
					else
					{
						return $cs_data;
					}
					break;

				// REACT PAGES
				case 'ent_cs_confirm_start':
				case 'ent_cs_confirm_react':
				case 'ent_reapply':
				case 'ent_reapply_legal':
					$this->errors = $this->React_Page($this->current_page);
					break;

				// pull up legal docs from condor
				case "view_docs":
					$this->View_Condor_Docs();
					break;

				case "preview_docs":
					$this->Preview_Condor_Docs();
					break;
				case 'bb_confirm_lead':
					$this->BlackBox_Confirm_Lead();
					break;
				case 'bb_spa_intersticial':
					$this->BlackBox_Intersticial();
					break;
				case 'app_online_refs_rework':
					$this->BlackBox_Rerun_Refs();
					break;

				case 'cust_decline':
					$type = $this->Get_Application_Type($this->Get_Application_ID());

					$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
					$olp_process = $app_campaign_manager->Get_Olp_Process($this->Get_Application_ID());
					// If they've already agreed, send them over to customer service
					//		bug #2846

					if (($olp_process == 'email_confirmation' && $type == 'CONFIRMED')
						|| ($olp_process != 'email_confirmation' && $type=='AGREED'))
					{
						$this->next_page = 'ent_cs_login';
					}
					else
					{
						$this->Customer_Decline();
					}
					break;

				case 'info_pub_submit':
				case 'info_adv_submit':
				case 'info_contactus_base':
				case 'info_contactus_app':
				case 'info_contactus_noapp':
					// emails data to customer service
					$this->Contact_Switch();
					break;
				case 'react_optout':
					$this->Customer_React_Optout();
					break;
				case 'remove':
					$this->Customer_Removal();
					break;

				case 'bb_ezm_legal':
					$this->Process_EZM();
					break;

				// New flow for a split tier1/tier2+ validation [LR]
				case 'app_1part_noesig_page01':

					// call for black_box select winner tier 1
					$this->config->bb_reject_level = 2;

					// Bypass writing a fail on tier1.
					$_SESSION['bypass_tier1_failed'] = TRUE;

					$this->Select_Winner();

					// Make sure we can write it for next time..
					unset($_SESSION['bypass_tier1_failed']);

					// If we have a winner after Tier1, we're finished!
					//   Display the thank you, and move on.
					 if ($_SESSION['blackbox']['winner'])
					 {
					 	$this->next_page = 'app_done_paperless';
					 }
					 // Otherwise, we need to clear blackbox, so we can run Tier2+
					 else
					 {
						unset($this->blackbox);
						unset($_SESSION['blackbox']);
					 }

				break;

				// We didn't get sold to Tier1 so we'll try it for Tier2+ [LR]
				case "app_1part_noesig_page02":

					Stats::Hit_Stats("bb_submit", $this->session, $this->event, $this->applog, $this->Get_Application_ID() );

					$this->config->bb_reject_level = NULL;
					$this->config->limits->accept_level = 2;

					$this->Select_Winner();

				break;


    			//Rework Phase 3 exit pop
    			case 'info_exitpop':

    				$stat = 'pop_exit';
	    			if(isset($this->normalized_data['exit_pop']))
	    			{
	    				if($this->normalized_data['exit_pop'] == 'agree')
	    				{
		    				$stat = 'exit_agree';
		    				$this->client_state['rework_exit_agree'] = TRUE;
		    				$_SESSION['rework_exit_agree'] = TRUE;
		    				$this->next_page = 'app_online_confirm_rework';
	    				}
	    				elseif($this->normalized_data['exit_pop'] == 'disagree')
	    				{
		    				$stat = 'exit_disagree';
		    				$this->client_state['rework_exit_agree'] = FALSE;
	    				}
	    			}

	    			Stats::Hit_Stats($stat, $this->session, $this->event, $this->applog, $this->Get_Application_ID());

    			break;

				case 'app_3part_page01':
					if($this->config->direct_mail_reservation)
					{
						//This will be true if they clicked on the link on the first page.
						//It'll just bypass the first page, so we need to fake like they just failed twice.
						if($this->dm_bypass)
						{
							//Add the current apge to the trace to bypass any errors
							$this->Page_Trace();
							$this->next_page = 'app_3part_page02';

							//Make sure we have an app
							$this->Event_Log();
							$this->Create_Application();

							//Get a config with the new promo
							if(!isset($this->config->bypass_res_page))
							{
								$this->Setup_New_Config($this->config->license, $this->collected_data['promo_id']);

								//And insert it into Campaign_Info
								$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
								$app_campaign_manager->Update_Campaign($this->Get_Application_ID(), $_SESSION['config']);
							}

							if($this->config->bypass_res_page)
							{
								Stats::Hit_Stats('pre_prequal_pass', $this->session, $this->event, $this->applog,  $this->Get_Application_ID());
							}

							//Hit this fancy stat.
							Stats::Hit_Stats('dm_no_res', $this->session, $this->event, $this->applog,  $this->Get_Application_ID());

							//For fun!
							unset($_SESSION['reservation_fails']);
						}
						else
						{
							$this->Check_OCS();
						}
					}

				break;

				case 'view_sign_doc':
					require_once('esign_doc.php');
					$eSignature = new eSignature(
						$this->collected_data['document_id'],
						$this->config->mode,
						$this->property_short);
					$doc = $eSignature->View_Doc();
					if(!empty($doc->data))
					{
						$content = $doc->data;
					}
					else
					{
						$content = 'That document does not exist.';
					}
					$this->eds_page = array(
						 'content' => $content,
						 'type' => 'text' ,
						 'action' => 'standard');
					break;

				case 'sign_document_process':
					require_once('esign_doc.php');
					$eSignature = new eSignature(
						$this->collected_data['document_id'],
						$this->config->mode,
						$this->property_short);
					$app = $eSignature->Get_App_By_Doc_Id();
					$agree_btn = $this->collected_data['b_legal_agree'];
					$doc_agree = $this->collected_data['agree_docs_1'];

					if($agree_btn == 'I AGREE')
					{
						if($doc_agree != "on")
						{
							$this->errors[] = "You must first agree to the document before you can sign it.";
						}
						else
						{
							$doc_name = trim(strtolower($app['first_name'] . " ".$app['last_name']));
							$esig = strtolower(trim($this->collected_data['esignature']));
							if($doc_name == $esig)
							{
								list($pass,$msg,$code) = $eSignature->Sign_Doc();
								if($pass)
								{
									$msg = "Thank you!";
								}
								else
								{
									$this->errors[] = $msg;
								}
							}
							else
							{
								$this->errors[] = "Your name did not match that of the person who owns this document.";
							}
						}
					}
					else
					{
						$this->errors[] = "An error occured signing your document.";
					}

					$this->eds_page = array(
						 'content' => $msg,
						 'type' => 'text' ,
						 'action' => 'standard');

					if(!$this->errors)
						break;

					else
					{
						$this->collected_data['page'] = 'sign_document';
						$this->current_page = 'sign_document';
					}

				case 'sign_document':
					require_once('esign_doc.php');
					$eSignature = new eSignature(
						$this->collected_data['document_id'],
						$this->config->mode,
						$this->property_short);
					$app = $eSignature->Get_App_By_Doc_Id();
					if($app === false)
					{
						$this->collected_data['page'] = 'sign_document_no_doc';
						$this->current_page = 'sign_document_no_doc';
						$this->eds_page = array(
							 'content' => 'Document does not exist.',
						 	'type' => 'text' ,
						 	'action' => 'standard');
					}
					$_SESSION['data']['name_first'] = trim($app['first_name']);
					$_SESSION['data']['name_last'] = trim($app['last_name']);
				break;

				case 'return_app':
					$this->Return_To_Application();
				break;


				case 'ecash_fax_or_email':

					if(isset($this->collected_data['fax_or_email']))
					{
						$this->ECashNewApp_Send_Docs($this->collected_data['fax_or_email']);
					}
				break;

				case 'esig':
					if (SiteConfig::getInstance()->ivr_scripted_thanks)
					{
						$_SESSION['data']['ivr_scripted_thanks'] = TRUE;
						if (isset($this->ent_prop_list[$_SESSION['blackbox']['winner']]))
						{
							$_SESSION['data']['winning_company'] = $this->ent_prop_list[$_SESSION['blackbox']['winner']]['legal_entity'];
						}

						if (!$_SESSION['data']['winning_company'])
						{
							$_SESSION['data']['winning_company'] = "<i>[unknown company short {$_SESSION['blackbox']['winner']}]</i>";
						}
					}
				break;
				
				/**
				 * Case for a PW offer.  Currently runs for the SYIN offer on the thank you page.
				 * The pw_offer_id should be the same as the coreg_id for whatever exit strategy
				 * coreg offer you're using.
				 */
				case 'pw_offer':

					if (!empty($this->normalized_data['pw_offer_id']))
					{
						$this->postExitStrategyCoregOffer(
							array($this->normalized_data['pw_offer_id'] => $this->normalized_data['pw_offer_result']),
							TRUE
						);
					}
					
					$this->next_page = 'bb_extra';
					$this->eds_page = array(
						'content' => 'Thank you for applying.', 
						'type' => 'html', 
						'action' => 'standard'
					);
				break;
				
				
				default:
					break;

			}

		}
		else
		{
			// bring back esig loan disclosure docs from condor
			switch( $this->current_page )
			{
				case 'esig':
				case 'ent_reapply_legal':
                    if(!isset($this->application_id) || $this->application_id == null ||
                        $this->application_id == "")
                    {
                        if($this->Get_Application_ID())
                        {
                            $this->application_id = $this->Get_Application_ID();
                        }
                        else
                        {
                            throw new Exception("Cannot Update Application Status - no app id set");
                        }
                    }

					// set session config vars for legal form
					$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
					if ($app_campaign_manager->Get_Application_Type($this->application_id)!='CONFIRMED')
					{
						$app_campaign_manager->Update_Application_Status($this->application_id, 'PENDING');
					}

					// create data to pass into condor - cloning object because session[config] is a reference
					$my_session['config'] = clone $_SESSION['config'];
					$my_session['data'] = $_SESSION['data'];

					if ($_SESSION['blackbox']['winner'])
					{
						$my_session['config']->legal_entity = $this->ent_prop_list[ strtoupper($_SESSION['blackbox']['winner']) ]["legal_entity"];
						$my_session['config']->site_name = $this->ent_prop_list[ strtoupper($_SESSION['blackbox']['winner']) ]["site_name"];
					}
					else // react
					{
						$my_session['config']->legal_entity = $this->ent_prop_list[ strtoupper($this->property_short) ]["legal_entity"];
						$my_session['config']->site_name = $this->ent_prop_list[ strtoupper($this->property_short) ]["site_name"];
					}
					// get the esig doc or write app log
					$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $my_session);
					if (!$this->esig_doc)
					{
						$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
						$this->event->Log_Event('CONDOR_PREVIEW', 'FAIL');
					}
					break;

				case "ent_confirm_legal":
					// need to get the data from cs to pass in
					$data = array();
					$data['config']->legal_entity = $this->ent_prop_list[ strtoupper($this->property_short) ]["legal_entity"];
					$data['config']->site_name = $_SESSION['config']->site_name;
					// get the esig doc or write app log
					$data['data']['qualify_info'] = $_SESSION['cs']['qualify'];

					if (!$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $data))
					{
						$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
						$this->event->Log_Event('CONDOR_PREVIEW', 'FAIL');
					}
					break;

				case "reprint_docs":
					// winner should always be set (or bb_force winner from ent site
					$property_short = strtoupper(($_SESSION['blackbox']['winner']) ? $_SESSION['blackbox']['winner'] : $_SESSION['config']->bb_force_winner);
					// bring up paperless application_xhtml
					$data = array();
					$data['config'] = clone $_SESSION['config'];
					$data['data'] = $_SESSION['data'];

					$data['application_id'] = $_SESSION['transaction_id'];
					$legal_entity = $this->ent_prop_list[strtoupper($property_short)]['legal_entity'];
					$support_fax = $this->ent_prop_list[strtoupper($property_short)]['fax'];
					$data['config']->legal_entity = $legal_entity;
					$data['config']->property_name = $legal_entity;
					$data['config']->support_fax = $support_fax;
					$data['config']->property_short = $property_short;
					// flag to insert first and last name into document for archival
					$data['esignature'] = TRUE;

					$legal_doc = $this->condor->Preview_Docs("paperless_application", $data);

					// test for legal doc and display
					if ($legal_doc)
					{
						$this->eds_page = array('content' => $legal_doc, 'type' => 'html' , 'action' => 'standard');
					}
					else
					{
						$this->eds_page = array('content' => "Sorry, the legal documents are not available at this time.", 'type' => 'text' , 'action' => 'standard');
					}
					break;

				case 'ent_profile':
				case 'ent_contact_us':

					//Some lame hacks to control the ent_profile form when it has errors.
					//It has two tables, one which just shows data and one which shows text
					//fields for the user to change values (which are toggled on and off
					//with the edit button).  If there are errors, however, we want to the
					//form table to show up so that they don't need to click edit again to
					//see where the errors are.  Really dumb, but whatever.
					unset(
						$_SESSION['data']['edit_table_display'],
						$_SESSION['data']['view_table_display'],
						$_SESSION['data']['edit_button_toggle']
					);

					if(empty($this->errors))
					{
						if($this->current_page == 'ent_profile')
						{
							$_SESSION['data']['edit_button_toggle'] = '';
							$_SESSION['data']['edit_table_display'] = 'none';
							$_SESSION['data']['view_table_display'] = '';
						}

						// process the page
						$return = $this->Customer_Service();

						if($return != "continue")
							return $return;
					}
					elseif($this->current_page == 'ent_profile')
					{
						$_SESSION['data']['edit_button_toggle'] = 'disabled="disabled"';
						$_SESSION['data']['edit_table_display'] = '';
						$_SESSION['data']['view_table_display'] = 'none';
					}

					break;

				default:
					break;

			}

		}

		return TRUE;
	}

	/**
	* @return bool
	* @desc runs preliminary cases for next_page
	**/
	private function Run_Next_Page_Cases()
	{

		if ( (!$this->errors && !$this->override_errors) && $this->next_page )
		{

			// used to update application status below
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);

			switch( $this->next_page )
			{

				// preliminary logic before the esig page
				// if the next page is esig this always means the
				// customer should have went through all the
				// forms collecting the complete set of data
				case "app_online_confirm_rework":
				case "esig":
				case "bb_thanks":
					//If it's a yellowpage site and is NOT a resell app
					//Run blackbox only on ecash 3 companies,
					$this->Select_Winner();
					$_SESSION['timer'][] = "AfterSelectWinner1 " . sprintf("%0.4f",microtime(true) - $this->start_time);

					if( ($this->config->coreg_egc == TRUE)
						&& ($_SESSION['data']['offers'] == 'TRUE')
						&& ($this->next_page == 'esig' || $this->next_page == 'bb_thanks') )
					{
						$_SESSION['coreg_egc'] = TRUE;
						$this->Process_EGC_Coreg_Application();
					}
					else
					{
						if(SiteConfig::getInstance()->coreg_egc == TRUE)
						{
							//********************************************* 
							// GForge 7447 [AuMa]
							// This was added so QA can see the events fire
							// on Live and RC
							//********************************************* 
							// We need to differentiate the leads that 
							// acutally fail versus the ones that the users
							// just don't accept.
							//********************************************* 
							$this->event->Log_Event('USER_DECLINE_EGC_OPTIN_' . SiteConfig::getInstance()->coreg_egc_promoid, 
                               'PASS', NULL, $this->application_id);
						}
					}


				case "app_declined":
					if(
						((string)$this->config->site_language == 'spa' &&
						is_null($_SESSION['intersticial_processed'])) &&
						(($this->blackbox['denied'] === TRUE && $this->next_page == 'bb_thanks') ||
						   ($this->next_page == 'app_declined'))
					)
					{
						// Not used.
						//$_SESSION['blackbox_asleep'] = $this->blackbox_obj->sleep();
						$this->next_page = 'bb_spa_intersticial';
					}
					elseif(!$_SESSION['is_fraud'] && $_SESSION['data']['no_refs'] == true && $this->blackbox['denied'] === TRUE)
					{
						// set up the reference removal page [TP]
						$this->next_page = "app_online_refs_rework";
						$this->event->Log_Event("ASK_FOR_REFS","PASS");
						$_SESSION['data']['no_refs']= false;
					}
					elseif(($this->blackbox['denied'] === TRUE && $this->next_page == 'bb_thanks') ||
					   ($this->next_page == 'app_declined' && $this->config->enable_rework))
					{
						/**
						 * Comments for GForge #8246:
						 * If Enterprise_Data::getLicenseKey($this->property_short) and SiteConfig::getInstance()->license
						 * are not the same, it means the application process has switched to an Enterprise site (which means
						 * the application has been sold to an enterprise company and the applicant is visiting a CS page
						 * like 'ent_online_confirm' or 'ent_online_confirm_legal'. If the application fails after submitting
						 * a CS page, don't redirect the applicant to the Last Chance URL. [DY] 
						 */
						if (!$this->config->call_center
							&& !$this->isReact()
							&& (!Enterprise_Data::isEnterprise($this->property_short) || Enterprise_Data::siteIsEnterprise(NULL))) #GForge #8246, #10066: for non-CS sites (which includes marketing sites and enterprise sites) [DY]
						{
							$_SESSION['data']['redirect'] = $this->getRedirectURLforPageAppDeclined(); // GForge #6972 [DY]
						}
						else
						{				
							$this->Check_Imagine_Card();	
						}
					}

					break;

				// submit condor_response prior to app complete page
				// will need to get response from esig page (agree/disagree)
				case "app_done_paperless":

					// If we have bypass_esig flag set, we must force Select_Winner() - LR
					// Promo ID for Ecash Reacts: 27713

					if ($this->config->bypass_esig || $this->config->soap_oc || $_SESSION['config']->ecash_react )
					{
						$this->Select_Winner();
						$_SESSION['timer'][] = "AfterSelectWinner2 " . sprintf("%0.4f", microtime(true) - $this->start_time);

						// Removing as per GForge #10602.  submitlevel1 should be hit for every app. [CB]
						/*if ($this->config->soap_oc && $_SESSION['blackbox']['original_tier'] == 1)
						{
							Stats::Hit_Stats('submitlevel1', $this->session, $this->event, $this->applog,  $this->Get_Application_ID() );
							$limits = new Stat_Limits($this->sql, $this->database);
							$limits->Increment('submitlevel1',0,$this->config->promo_id,0);
						}*/

						if ($this->config->coreg_egc == TRUE && $_SESSION['data']['offers'] == 'TRUE' && !$_SESSION['config']->ecash_react)
						{
							$_SESSION['coreg_egc'] = TRUE;
							$this->Process_EGC_Coreg_Application();
						}
					}
					elseif (isset($this->normalized_data['legal_agree']))
					{
						// set legal status based on esig response
						$this->condor->Condor_Get_Docs('signature_response', "TRUE", "");
						$_SESSION['timer'][] = "AfterCondor_Get_Docs " . sprintf("%0.4f",microtime(true) - $this->start_time);
					}

					if ($_SESSION['transaction_id'] && !$_SESSION['app_completed'] && !$this->config->soap_oc)
					{
						// Final Validation for the Application
						$this->E_Sig_App_Validation($app_campaign_manager);
						$_SESSION['timer'][] = "AfterE_Sig_App_Validation " . sprintf("%0.4f",microtime(true) - $this->start_time);
					}

				break;

				case "ent_confirm_legal":
					$this->Next_Page_Case_Ent_Confirm_Legal();
				break;

				//Removing because Impact is billed on their bb_* stats now, so it
				//doesn't make a lot of sense to hit this when they agree. (GForge #4506)
				case "ent_thankyou":
					$app_id = $this->Get_Application_ID();
					$winner = $app_campaign_manager->Get_Winner($app_id);

					//If CLK wins a loan from acceptmycash.com, we display a different offer on
					//the thank you page that redirects them to a site where they can get a
					//second loan with a non-CLK company.
					if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $winner) && !empty($_SESSION['cs']))
					{
						$campaigns = $app_campaign_manager->Get_Campaign_Info($app_id);
						foreach($campaigns as $campaign)
						{
							//We only want acceptmycash.com
							if(strcasecmp($campaign['url'], 'acceptmycash.com') == 0)
							{
								//This data will be pre-populated on the second-loan site's form.
								$data_needed = array(
									'name_first',
									'name_last',
									'home_street',
									'home_city',
									'home_state',
									'home_zip',
									'email_primary',
									'phone_home',
									'phone_work',
									'phone_cell'
								);

								$data = array_intersect_key($_SESSION['cs'], array_flip($data_needed));

								//text.ent.thankyou.html will look for this variable and change
								//the offer to use it, if it exists.
								$_SESSION['data']['non_clk_second_loan'] = http_build_query($data);

								break;
							}
						}
					}
				break;

				case 'ent_online_confirm':
					$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
					$contacts = $app_campaign_manager->Get_Personal_Contacts($this->Get_Application_ID());

               //If the pay dates that we have are 0000-00-00
               //then we set the session option so we can run
               //the twice monthly update
               //Soap Pay Date Widget - Mantis 12475 [AuMa]
               if($_SESSION['cs']['new_pay_dates'][0] == '0000-00-00')
               {
                  $_SESSION['data']['bad_pay_dates'] = TRUE;
               }

					//If they don't have enough references, we need to display the
					//references on the confirmation page
					if(count($contacts) < 2 && !$this->Is_CLK())
					{
						//If for some reason they do have contact info, populate it
						for($i = 1; $i <= count($contacts); $i++)
						{
							foreach($contacts[$i] as $key => $value)
							{
								$_SESSION['data']["ref_0{$i}_{$key}"] = $value;
							}
						}
					}

					//Don't want to do this for Impact.
					$_SESSION['data']['ref_count'] = (Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, Enterprise_Data::getProperty(SiteConfig::getInstance()->site_name))) ? count($contacts) : 2;
				break;

				/*
				case "app_online_confirm_rework":
					$this->nextpage = "app_online_confirm_rework";
				break;
				*/

			}

		}
		$_SESSION['timer'][] = "BeforeCondorDocs " . sprintf("%0.4f",microtime(true) - $this->start_time);

		// we want this to run ALL the time -- even with errors
		if (($this->next_page === 'ent_online_confirm_legal') || (count($this->errors) && ($this->current_page === 'ent_online_confirm_legal')))
		{
			if(isset($this->property_short))
            {
            	$p_short = $this->property_short;
            }
            elseif(isset($_SESSION['config']->property_short) && $_SESSION['config']->property_short != "bb")
            {
            	$p_short = $_SESSION['config']->property_short;
            }
            elseif(isset($_SESSION['condor']->property_short))
            {
            	$p_short = $_SESSION['condor']->property_short;
            }
            elseif(isset($_SESSION['config']->bb_force_winner))
            {
            	$p_short = $_SESSION['config']->bb_force_winner;
            }
            elseif(isset($_SESSION['old_config']->bb_force_winner) && $_SESSION['config']->property_short != "bb")
            {
            	$p_short = $_SESSION['old_config']->bb_force_winner;
            }
            else
            {
            	throw new Exception("Property short is not set. User might have hit back to marketing site?");
            }

            if(!$this->Is_Ecash3($p_short))
            {
	            $this->Setup_DB($p_short);
				$ent_cs = $this->Get_Ent_Cs($p_short);

				// save this stuff
				$data = $ent_cs->Prepare_Condor_Data($this->Get_Application_ID());
				$_SESSION['cs'] = array_merge($_SESSION['cs'], $data['data']);
				$_SESSION['timer'][] = "AfterPrepare_Condor_Data " . sprintf("%0.4f",microtime(true) - $this->start_time);

				// this'll take care of the condor stuff
				$this->Refresh_Legal_Docs($_SESSION['cs']);
				$_SESSION['timer'][] = "AfterRefresh_Legal_Docs " . sprintf("%0.4f",microtime(true) - $this->start_time);
            }
		}
		$_SESSION['timer'][] = "afterCondorDocs " . sprintf("%0.4f",microtime(true) - $this->start_time);

		return;

	}

	private function Online_Rework_Session_Reset()
	{
		if(!isset($_SESSION["data"]["orig_name_first"]))
		{
			$_SESSION["data"]["orig_name_first"] 		= $_SESSION["data"]["name_first"];
			$_SESSION["data"]["orig_name_last"] 		= $_SESSION["data"]["name_last"];
			$_SESSION["data"]["orig_home_street"]		= $_SESSION["data"]["home_street"];
			$_SESSION["data"]["orig_home_city"]			= $_SESSION["data"]["home_city"];
			$_SESSION["data"]["orig_home_state"]		= $_SESSION["data"]["home_state"];
			$_SESSION["data"]["orig_home_zip"]			= $_SESSION["data"]["home_zip"];
			$_SESSION["data"]["orig_ssn_part_1"]		= $_SESSION["data"]["ssn_part_1"];
			$_SESSION["data"]["orig_ssn_part_2"]		= $_SESSION["data"]["ssn_part_2"];
			$_SESSION["data"]["orig_ssn_part_3"]		= $_SESSION["data"]["ssn_part_3"];
			$_SESSION["data"]["orig_date_dob_y"]		= $_SESSION["data"]["date_dob_y"];
			$_SESSION["data"]["orig_date_dob_m"]		= $_SESSION["data"]["date_dob_m"];
			$_SESSION["data"]["orig_date_dob_d"] 		= $_SESSION["data"]["date_dob_d"];
			$_SESSION["data"]["orig_state_id_number"]	= $_SESSION["data"]["state_id_number"];
			$_SESSION["data"]["orig_state_issued_id"]	= $_SESSION["data"]["state_issued_id"];

			// We need to format the string
			if(!strstr("-",$_SESSION["data"]["orig_phone_home"]))
			{
				$_SESSION["data"]["orig_phone_home"] = 	substr($_SESSION["data"]["phone_home"],0,3)."-".
														substr($_SESSION["data"]["phone_home"],3,3)."-".
														substr($_SESSION["data"]["phone_home"],6);
			}
			else
			{
				$_SESSION["data"]["orig_phone_home"] = $_SESSION["data"]["phone_home"];
			}


			unset($_SESSION["data"]["name_first"]);
			unset($_SESSION["data"]["name_last"]);
			unset($_SESSION["data"]["home_street"]);
			unset($_SESSION["data"]["home_city"]);
			unset($_SESSION["data"]["home_state"]);
			unset($_SESSION["data"]["home_zip"]);
			unset($_SESSION["data"]["ssn_part_1"]);
			unset($_SESSION["data"]["ssn_part_2"]);
			unset($_SESSION["data"]["ssn_part_3"]);
			unset($_SESSION["data"]["date_dob_y"]);
			unset($_SESSION["data"]["date_dob_m"]);
			unset($_SESSION["data"]["date_dob_d"]);
			unset($_SESSION["data"]["state_id_number"]);
			unset($_SESSION["data"]["state_issued_id"]);
			unset($_SESSION["data"]["phone_home"]);
		}
	}

	private function Online_Rework($validate = FALSE)
	{

		$return  =  FALSE;

		if ((isset($this->normalized_data['no_checks'])
				&& $this->normalized_data['datax_idv'] != 1)
			&& strtoupper($_SESSION['config']->mode) != "LIVE")
		{
			$no_checks = TRUE;
		}


		if ($validate && $_SESSION["process_rework"])
		{

			// They hit the magic rework copy button :P [RL]
			if ($this->normalized_data["rework_button_copy"] == "TRUE")
			{
				Stats::Hit_Stats("rework_button_resubmit", $this->session, $this->event, $this->applog, $this->application_id);
			}
			else
			{
				Stats::Hit_Stats("rework_button_submit", $this->session, $this->event, $this->applog, $this->application_id);
			}

			//STAT:RESUBMIT
			$data_stat = ($_SESSION["return_visitor"]) ? "resubmit_return" : "resubmit";
			Stats::Hit_Stats($data_stat, $this->session, $this->event, $this->applog, $this->application_id);
			$this->config->process_rework = "ORIGINAL";

			foreach ($this->normalized_data as $key => $value)
			{
				// We only want to compare data that pertains to changes [RL]
				if (isset($_SESSION['orig_data'][$key]))
				{
					if ((strtolower($_SESSION['orig_data'][$key]) != strtolower($value)) && $key != "page" )
					{
						unset($_SESSION['blackbox']);
						$this->config->process_rework = "CHANGED";
						break;
					}
				}
			}

			unset($_SESSION["process_rework"]);
			unset($_SESSION['orig_data']);
			// replace the "process_rework" which is set in new Blackbox
			// with a "do_datax_rework" session variable for the constructor of
			// new Blackbox so it changes the datax call type when needed
			$_SESSION['do_datax_rework'] = TRUE;

			if ($this->config->process_rework == "ORIGINAL")
			{
				//STAT:SAME DATA FAIL

				$data_stat = ($_SESSION["return_visitor"]) ? "same_data_fail_return" : "same_data_fail";
				Stats::Hit_Stats($data_stat, $this->session, $this->event, $this->applog, $this->application_id);


				/*$_SESSION['blackbox_asleep']['exclude'][] = $_SESSION['blackbox_asleep']['winner'];
				unset($_SESSION['blackbox_asleep']['winner']);*/
				
				$this->blackbox_obj = $this->Configure_Blackbox($_SESSION['blackbox_asleep']);

				//Since we know they'll fail again, exclude the current tier.
				//$this->blackbox_obj->restrict(array($_SESSION['idv_failed_tier'] => ''), FALSE);

				$_SESSION['IDV_REWORK'] = TRUE;

				//Find a new winner
				$this->blackbox_obj->pickWinner(TRUE);
				$this->blackbox = $this->blackbox_obj->winner();

				unset($_SESSION['IDV_REWORK']);

				//$tier = (isset($this->blackbox['tier'])) ? $this->blackbox['tier'] : NULL;
				//$enterprise = ($this->normalized_data['enterprise'] ) ? TRUE : FALSE;

				if (!isset($this->blackbox['winner']))
				{
					$this->blackbox['denied'] = 1;
				}
				else
				{
					$_SESSION['data']['sell'] = 'YES';
				}
				
				$this->Process_Winner();
				/*if( $tier != 1 && !array_key_exists( 'denied', $this->blackbox ) )
				{
					// Mantis #10788 Added in the custome next_page for tier2 winners
					if ( ($enterprise) && ($tier != 1))
					{
						$_SESSION['blackbox_asleep'] = $this->blackbox_obj->Sleep();

						$this->next_page = 'bb_confirm_lead';
					}
					else
					{
						//We didn't find a winner and they didn't change their data so they must be denied [RL]
						if(!isset($this->blackbox['winner']))
						{
							$this->blackbox['denied'] = 1;
						}
						else
						{
							$_SESSION['data']['sell'] = 'YES';
						}

						$this->Process_Winner();
					}
				}
				else
				{

					$this->Process_Winner();
				}*/
			}
			else
			{
				$_SESSION["IDV_REWORK"] = true;

				$this->Select_Winner();

			}

		}
		else
		{

			if ($this->config->online_confirmation && $this->config->enable_rework)
			{
				if (isset($this->config->process_rework))
				{
					// We are selecting the winner again so
					// we need a fresh start
					if ($this->config->process_rework == "CHANGED")
					{
						$bb_datax = new BlackBox_DataX($this->config);
						$bb_datax->Reset();
					}
					elseif ($this->config->process_rework == "ORIGINAL")
					{
						// Data for the app didnt change so we continue on as if nothing happened.

					}
				}
				elseif (((($this->blackbox['datax_decision']['DATAX_IDV'] == "N" )
							|| $this->blackbox['datax_decision']['DATAX_IDVE_IMPACT'] == 'N')
					 		|| $_SESSION["process_rework"])
					 	&& !$no_checks)
				{
					// The App failed datax_idv so we are going to
					// try rework the application
					// STAT:REWORK
					Stats::Hit_Stats("rework", $this->session, $this->event, $this->applog, $this->application_id);
					$_SESSION["process_rework"] = true;
					$_SESSION['orig_data'] = $this->normalized_data;
					// We want to blank out the form
					$this->Online_Rework_Session_Reset();

					// Save the state but dont overwrite it
					if (empty($_SESSION['blackbox_asleep']))
					{
						$_SESSION['blackbox_asleep'] = $this->blackbox_obj->sleep(TRUE);
						
						if ($this->blackbox['datax_decision']['DATAX_IDV'] == 'N')
						{
							$_SESSION['blackbox_asleep']['exclude'][] = 'clk';
							$_SESSION['blackbox_asleep']['tier'] = 1;
						}
						else
						{
							$_SESSION['blackbox_asleep']['exclude'][] = 'ic';
							$_SESSION['blackbox_asleep']['tier'] = 2;
						}
					}

					$this->next_page = 'app_online_confirm_rework';

					$return = TRUE;
				}
			}
		}

		return $return;
	}
	/**
	*  Moved this into it's own function so we can call it as needed.
	*    Changed to suport bypass_esig configuration flag - LR
	**/
	private function Select_Winner()
	{

		// run Blackbox if it has not been run yet
		if (!$_SESSION['blackbox'] && !$_SESSION["process_rework"])
		{

			// don't run the FLE dupe this time
			unset($_SESSION['fle_dupe_id']);

			//If we're doing an overflow, we want to make sure we restrict to tier 2
			//since tier 1 has already failed and we only want to send to our overflow
			//targets.
			$restrict = NULL;
			if(isset($_SESSION['process_overflow']))
			{
				$restrict = array(array('2' => TRUE), FALSE);
			}

			$_SESSION['bb_restricts'] = $restrict;
			// run blackbox
			$this->blackbox = $this->Blackbox($restrict);
			$_SESSION['timer'][] = "Select_Winner::AfterBlackbox " . sprintf("%0.4f",microtime(true) - $this->start_time);

			$enterprise = ($this->normalized_data['enterprise']) ? TRUE : FALSE;
			$tier = (isset($this->blackbox['tier'])) ? $this->blackbox['tier'] : NULL;

			// ecashapp react - insert denied apps
			if(isset($_SESSION['data']['ecashapp']) && isset($this->blackbox['denied']))
			{
				// Insert the application into LDB anyway
				$this->Process_First_Tier($_SESSION['data']['ecashapp']);
			}

			// Online Rework Process, if the lead fails IDV we will redirect them to a rework
			// page where they will be able to change any info to help them pass
			if(!$this->Online_Rework(FALSE))
			{
				if($tier != 1 && !isset($this->blackbox['denied']))
				{
					$_SESSION['data']['sell'] = 'YES';
				}

				$this->Process_Winner();
				$_SESSION['timer'][] = "Select_Winner::AfterProcess_winner " . sprintf("%0.4f",microtime(true) - $this->start_time);
			}

		}
		elseif ($_SESSION["process_rework"])
		{
			$this->Online_Rework(FALSE);
		}


	}

	/**
		@publicsection
		@public
		@fn return stdClass Pre_Qualify()
		@brief
			Perform rudimentary "prequalification".

		This function performs rudimentary error and
		prequalification checks	on the income data.

		@desc prequalification
		@return Returns a stdClass object, with a
			single member, errors, an array of errors
			that occured.
	**/
	public function Pre_Qualify()
	{

		$errors = array();

		// use paydate/frequency for normal apps. paydate_model/income_frequency for soap apps
		$income_frequency = $this->normalized_data['paydate']['frequency'] ? $this->normalized_data['paydate']['frequency'] : $this->normalized_data['income_frequency'];
	 	$income_net = $this->normalized_data['income_monthly_net'];
	 	$income_type = strtoupper($this->normalized_data['income_type']);

		//********************************************* 
		// GForge 6672
		// Employer_length = 9 means the applicant is 
		// unemployed - we don't want to pass the 
		// date into the pre_qualify check
		// [AuMa]
		//********************************************* 
		if ($this->normalized_data['employer_length'] === 'FALSE')
		{
			$date_hire = 'FALSE';
		} 
		else 
		{
			$date_hire = $this->normalized_data['date_of_hire'];
		}

		if($income_frequency && $income_net && $date_hire &&
		   !($this->config->site_type == "blackbox.address" &&
		    ($this->current_page != "app_2part_page01" || $this->current_page != "default")))
		{

			$pre_qualify = new Pre_Qualify();
			$result = $pre_qualify->Check(strtoupper($income_frequency), $income_net, $income_type, $date_hire);

			if ($result!==TRUE) $errors = $result;

		}
//		elseif ($income_frequency || $income_net || $date_hire)
//		{
//			if (!$income_frequency) $errors[] = 'income_frequency';
//			if (!$income_net) $errors[] = 'income_monthly_net';
//			if (!$date_hire) $errors[] = 'employer_length';
//		}

		// build our return object
		$return = new stdClass();
		$return->errors = $errors;

		return($return);

	}

	public function Configure_Blackbox($restore = NULL, $data = NULL, $mode = NULL, $preferred_targets = NULL)
	{

        //Do we need this???  (Yes we do [for confirmation])
		$app_id = $this->Get_Application_ID();

		// "configuration" for BlackBox
		$config = new stdClass;
		$config->sql = &$this->sql;

		// We need the ldb connection for Qualify_2 inside black box.
		//Actually, we don't because if we set it here, it won't work for Impact!
		//It'll be created in the constructor for qualify 2
		/*if(!isset($this->db))
		{
			$this->Setup_DB($_SESSION['config']->property_short);
		}

		$config->db = &$this->db;*/
		$config->session = &$this->session;
		$this->Event_Log(TRUE); //Recreate event log before passing
		$config->log = &$this->event;
		$config->applog = &$this->applog;
		$config->application_id = $app_id;
		$config->data = (is_array($data)) ? $data : $_SESSION['data'];
		$config->database = $this->database;
		$config->mode = $this->config->mode;
		$config->ent_prop_list = $this->ent_prop_list;
		$config->is_enterprise = EnterpriseData::siteIsEnterprise(SiteConfig::getInstance()->site_name);
		$config->config = $_SESSION["config"];
		$config->fle_dupe_id = $_SESSION["fle_dupe_id"] ? $_SESSION["fle_dupe_id"] : NULL;
		$config->impact_properties = $this->impact_properties;
		$config->clk_properties = $this->clk_properties;
		//$config->compucredit_properties = $this->compucredit_properties;
		$config->agean_properties = $this->agean_properties;
		$config->title_loan = $this->title_loan;
		$config->entgen_properties = $this->entgen_properties;
		$config->ent_prop_short_list = $this->ent_prop_short_list;
		
		// Check if enable rework is set and put it in the config.
		if (isset($this->config->enable_rework))
		{
			$config->config->enable_rework = $this->config->enable_rework;
		}
		
		//if(is_null($restore))
		{
			if(isset($_SESSION['ecashnewapp']))
			{
				$preferred_targets = $_SESSION['ecashnewapp'];
				$this->config->bb_force_winner = $_SESSION['ecashnewapp'];
			}

			if (($_SESSION['data']['no_refs'] == 1) && (!$this->Is_Soap(SiteConfig::getInstance()->site_type)))
			{
				// no Mantis # changing no refs over to new site type setup
				// tried useing the SiteConfig version of bb_force_winner
				// but this didn't seem to carry over after this bit, when someone moves the
				// whole thing over then this should be changed to match.
				// brian had said that thease changes are not persistent
				// which is the desired function. we do not want bb_force_winner
				// set to bb_no_refs after the reference popup page.
				$this->config->bb_force_winner = SiteConfig::getInstance()->bb_no_refs;
				//$this->config->bb_reject_level = 1;
			}

			// do we have preferred targets?
			if(isset($this->config->preferred_targets) || isset($preferred_targets))
			{
				$preferred_targets = (is_null($preferred_targets)) ? $this->config->preferred_targets : $preferred_targets;
				$preferred = array_map('trim', explode(',', $preferred_targets));
			}
			else
			{
				$preferred = NULL;
			}

			// GForge #10559 - react verification check for Agean reacts	[RV]
			if ($mode === MODE_ECASH_REACT && ($this->Is_Agean_Site() || $this->Is_Agean($config->data['ecashapp'])))
			{
				include_once(BFW_MODULE_DIR.'olp/VerifyReact.php');
				
				$verify = new Verify_React();
				$config->verified_react = $verify->verifyReact($config->data);
			}
			
			// create a new blackbox object
			// Your going to pass preferred to blackbox sql tiers
			//$blackbox = new BlackBox($config, TRUE, $preferred, $mode);
			$config->preferred_targets = $preferred;
			$blackbox = Blackbox_Adapter::getInstance($mode, $config);

			// get some stuff
			$force_winner = $this->config->bb_force_winner;
			$excluded_targets = $this->config->excluded_targets;
			$reject_level = isset($this->config->bb_reject_level) ? $this->config->bb_reject_level : FALSE;
			$accept_level = isset($this->config->limits->accept_level) ? $this->config->limits->accept_level : 1;
			
			// PWARB fix [AuMa]
			//********************************************* 
			// GForge 9355
			// basically the idea is to add pwarb to the 
			// exclude list unless it is in the config
			// variable 'use_pwarb' is set
			//********************************************* 
			if (SiteConfig::getInstance()->use_pwarb != TRUE)
			{
				if (!empty($excluded_targets))
				{
					$excluded_targets .= ',pwarb';
				} 
				else
				{
					$excluded_targets = 'pwarb';
				}
			}
			
			
			/**
			 * ic_nd is only allowable on certain sites (OCS sites), so we need to always
			 * exclude it unless we have a specific config option.
			 */
			$exclude_list = array('ic_nd');
			if (isset(SiteConfig::getInstance()->bypass_excluded_targets))
			{
				$bypass_list = array_map('trim', explode(',', SiteConfig::getInstance()->bypass_excluded_targets));
				$exclude_list = array_diff($exclude_list, $bypass_list);
			}
			
			if (!empty($exclude_list))
			{
				if (!empty($excluded_targets))
				{
					$excluded_targets .= ',' . implode(',', $exclude_list);
				}
				else
				{
					$excluded_targets = implode(',', $exclude_list);
				}
			}
			
			//********************************************* 
			// End GForge #9355 [AuMa]
			//********************************************* 

			if (!is_null($restore))
			{
				// just restore our previous object
				/*$blackbox = BlackBox_OldSchool::Restore($restore, $config);
				if(!is_null($mode))
				{
					$blackbox->mode($mode);
				}*/
				
				if (!empty($restore['exclude']))
				{
					if (empty($excluded_targets))
					{
						$excluded_targets = implode(',', $restore['exclude']);
					}
					else
					{
						$excluded_targets .= ',' . implode(',', $restore['exclude']);
					}
				}
				
				if (!empty($restore['winner']))
				{
					$force_winner = $restore['winner'];
				}
				
				if (!empty($restore['tier']))
				{
					$accept_level = $restore['tier'];
				}
			}
			
			
			// excluded targets by site/promo/etc.
			if(!empty($excluded_targets))
			{
				// exclude targets in the config->exclude_targets setting
				$exclude = array_map('trim', explode(',', $excluded_targets));
				$blackbox->restrict(array('FIND' => $exclude), FALSE);
			} 
						
			// get all of our tiers: this will return the tiers
			// as both keys AND values (i.e., 1=>1, 2=>2, 3=>3...)
			$restrict = $blackbox->getTiers();

			$restrict = array_combine($restrict, $restrict);

			/*      RESTRICTIONS     */

			// Reject/Accept Explanation --
			//	Tiers:	1,2,3,4,5,6,7
			//				1)	Reject:		5
			//					Restrict:	1,2,3,4
			//				2)	Accept:		4
			//					Restrict:	4,5,6,7
			//				3)	Reject:		6
			//					Accept:		3
			//					Restrict:	3,4,5

			// This checks against the submitlevel1 row located in stat_limits.
			// One this threshold is reached we should no longer sell the leads to tier1.
			if($this->Cap_Submitlevel1())
			{
				$accept_level = 2;

				// If overflow targets are defined, use them,
				// If not, continue onto the next tier
				if(strlen($this->config->overflow_targets) > 1)
				{
					$_SESSION['cap_submitlevel1_overflow'] = TRUE;
				}

				$this->Event_Log(TRUE);
				$this->event->Log_Event('CAP_SUBMITLEVEL1', 'FAIL');
			}

			if($this->In_Blocked_Sub_Codes() && $accept_level < 2)
			{
				$accept_level = 2;
			}

			if($accept_level !== FALSE)
			{
				foreach($restrict as $tier)
				{
					// remove any tiers higher than our accept level
					if($tier < $accept_level)
					{
						unset($restrict[$tier]);
					}
				}
			}

			// If we're doing overflow, we need to make sure we can actually sell to tier 2!
			// I really dislike having to do this, to be perfectly honest. [CB]
			if(isset($_SESSION['enable_overflow']) && $reject_level == 2)
			{
				$reject_level = 3;
			}

			if($reject_level)
			{
				foreach($restrict as $tier)
				{
					// remove any tiers at our reject level our lower
					if($tier >= $reject_level)
					{
						unset($restrict[$tier]);
					}
				}
			}

			if($force_winner)
			{
				// try to find this specific target
				$force_winner = array_map('trim', explode(',', $force_winner));

				//Can't sell title loans to jiffy.
				if($this->title_loan && $this->Is_Agean_Site())
				{
					$key = array_search('jiffy', $force_winner);
					if($key !== false)
					{
						unset($force_winner[$key]);
					}
				}

				$restrict['FIND'] = $force_winner;
			}


			// at this point, we only want to check our limit if
			// there's a possibility that we'll go to tier 1
			/*
			if (isset($restrict['1']))
			{

				// limits object
				$db = Server::Get_Server($this->config->mode, 'BLACKBOX');
				$limits = new Stat_Limits($this->sql, $db['db']);

				if ($limits->Over_Limit('agree', $this->config))
				{
					// can't go to tier 1
					unset($restrict['1']);
				}

				unset($limits);

			}
			*/

			//$restrict_for_debugging = $restrict; // for debug purpose
			if ($_SESSION['is_fraud']) // GForge #3077 [DY]
			{
				$restrict = array(); // -> won't select any vendor from any tier.
			}

			$blackbox->restrict($restrict);
			
			if ($mode == MODE_PREQUAL)
			{
				// Is this an Impact site or an Agean site or are we an ecashnewapp?
				$tiers = ($this->Is_Impact() || $this->Is_Agean() || isset($_SESSION['ecashnewapp']))
					? array(0 => 0)
					: array(1 => 1);
			
				// We'll only use tier 0 or tier 1 for prequal
				$dont_use = array_diff($blackbox->getTiers(), $tiers);
				$blackbox->restrictTiers($dont_use, FALSE);
			
				// If we're restricting to tier 1, then also restrict only to the CLK properties
				if($tiers[1] == 1)
				{
					$blackbox->restrict(array('FIND' => $this->clk_properties));
				}
			}
			elseif ($mode == MODE_ONLINE_CONFIRMATION || $mode == MODE_ECASH_REACT)
			{
				$blackbox->restrict(array('FIND' => array($this->property_short)));
			}

			/*      DEBUG OPTIONS     */

			// restricting sub procedures in blackbox
			$options = array(
				'cashline' => DEBUG_RUN_CASHLINE,
				'used_info' => DEBUG_RUN_USEDINFO,
				'datax_idv' => DEBUG_RUN_DATAX_IDV,
				'datax_perf' => DEBUG_RUN_DATAX_PERF,
				'rules' => DEBUG_RUN_RULES,
				'stats'	=> DEBUG_RUN_STATS,
				'filters' => DEBUG_RUN_FILTERS,
				'aba'	=> DEBUG_RUN_ABA,
				'cfe'   => DEBUG_RUN_CFE);

			$data = array_change_key_case($this->normalized_data, CASE_LOWER);
			$debug_opt = array();
			$debug_exclude = array();
			$debug_restrict = array();

			// disable all restrictions on no_checks
			if(isset($data['no_checks']))
			{
				$debug_opt = array_combine($options, array_fill(0, count($options), FALSE));
				$debug_opt[DEBUG_RUN_CFE] = 1;
			}

			// Only run certain options for ecashapp.com [RL]
			if($_SESSION['config']->site_name == 'ecashapp.com' || $mode === MODE_ECASH_REACT)
			{
				$debug_opt = array_combine($options, array_fill(0, count($options), FALSE));
				unset($data['cashline']);  //This is being passed in from cm_soap, so we need to unset it to ensure cashline runs
				unset($debug_opt[DEBUG_RUN_CASHLINE]);
				unset($debug_opt[DEBUG_RUN_CFE]);

				//Will be ecash_react if we're doing a cs or email react, and we won't run the rules
				if($mode !== MODE_ECASH_REACT)
				{
					unset($debug_opt[DEBUG_RUN_RULES]);
				}

				//Set preacts
				if($data['react_type'] == 'preact')
				{
					$debug_opt[DEBUG_RUN_PREACT_CHECK] = TRUE;
				}
			}

			// run the blackbox debug option for the
			// restrictions passed through the url
			foreach($options as $key => $option)
			{
				if(isset($data[$key]))
				{
					if($data[$key])
					{
						unset($debug_opt[$option]);
					}
					elseif(!$data[$key])
					{
						$debug_opt[$option] = FALSE;
					}
				}
			}

			// check for ssforce or ecashapp to ssforce to a property
			if($data['ssforce'] || $data['ecashapp'])
			{
				$ssforce = ($data['ssforce']) ? $data['ssforce'] : $data['ecashapp'];
				$ssforce = array_map('trim', explode(',', $ssforce));

				$debug_restrict = array('FIND' => $ssforce);
			}
			elseif(isset($this->normalized_data['use_tier']))
			{
				$use_tier = array_map('trim', explode(',', $this->normalized_data['use_tier']));

				foreach($use_tier as $u_tier)
				{
					if(is_numeric($u_tier))
					{
						$debug_restrict[$u_tier] = TRUE;
					}
				}
			}

			if(isset($this->normalized_data['exclude_tier']))
			{
				$exclude_tier = array_map('trim', explode(',', $this->normalized_data['exclude_tier']));

				foreach($exclude_tier as $e_tier)
				{
					if(is_numeric($e_tier))
					{
						$debug_exclude[$e_tier] = TRUE;
					}
				}
			}



			if(isset($data['ssexclude']))
			{
				$ssexclude = array_map('trim', explode(',', $data['ssexclude']));

				$debug_exclude['FIND'] = $ssexclude;
			}

			/*if (isset($data['fraud_scan'])
				&& $data['fraud_scan']
				&& $_SESSION['is_fraud'])
			{
				$restrict_for_debugging = array();
			}
			elseif($_SESSION['is_fraud'])
			{
				$restrict_for_debugging = array();
			}*/
			
			unset($data);

			if(count($debug_opt) || count($debug_restrict) || count($debug_exclude))
			{

				$name_first = (!empty($_SESSION['cs']['name_first'])) ? $_SESSION['cs']['name_first'] : $this->normalized_data['name_first'];

				// don't allow the use of debugging options in
				// LIVE mode without "test" somewhere in the first
				// name (unless we're flagged as an ecash app)
				if($this->config->mode != 'LIVE'
					|| strpos(strtolower($name_first), 'test') !== FALSE
					|| isset($this->normalized_data['ecashapp'])
					|| $mode === MODE_ECASH_REACT
					|| SiteConfig::getInstance()->live_test_enabled === TRUE
				)
				{
					if(!empty($debug_opt))
					{
						// set debug options in blackbox
						$blackbox->setDebugOptions($debug_opt);
					}

					//$blackbox->restrict($restrict_for_debugging); // currently it's used for fraud scan only.

					if(!empty($debug_restrict))
					{
						// SET DEBUG RESTRICTIONS
						$blackbox->restrict($debug_restrict);
					}

					if(!empty($debug_exclude))
					{
						$blackbox->restrict($debug_exclude, FALSE);
					}
				}
				elseif($config->mode == 'LIVE')
				{
					$message = array();

					$message[] = wordwrap('Someone attempted to use BlackBox debugging options without a proper first name.', 72);
					$message[] = '';
					$message[] = print_r($debug_opt, TRUE);
					$message[] = print_r($debug_restrict, TRUE);
					$message[] = '';
					$message[] = 'IP ADDRESS: '.$_SESSION['data']['client_ip_address'];
					$message[] = 'REMOTE SITE: '.$_SESSION['data']['client_url_root'];
					$message[] = '';
					$message[] = 'REQUEST:';
					$message[] = str_repeat('-', 72);
					$message[] = print_r($_SERVER, TRUE);
					$message[] = '';
					$message[] = 'SESSION:';
					$message[] = str_repeat('-', 72);
					$message[] = print_r($_SESSION, TRUE);
					$message[] = '';

					$message = implode("\r\n", $message);

					mail('andrew.minerd@thesellingsource.com', 'BLACKBOX WARNING', $message);
				}
			}

			// We want to make sure that we only run in Ecash React Mode for ECash App
			if ((isset($_SESSION['react_target']) && $_SESSION['react_target'] !== FALSE)
				|| $_SESSION['config']->site_name == 'ecashapp.com')
			{
				$blackbox->mode(MODE_ECASH_REACT);
			}
			
			$blackbox->postConfigure();
		}

		return $blackbox;
	}

	/**
	* @param $restrict_param_array array of length 2, which are arguments to Blackbox->Restrict()
	* @desc adds page to page_trace in session
	**/
	public function Blackbox($restrict_param_array = NULL)
	{

		// instantiate blackbox
		if (!$this->blackbox_obj)
		{
			// get a new BlackBox object
			$this->blackbox_obj = $this->Configure_Blackbox();
			$_SESSION['timer'][] = "Blackbox::Configure_Blackbox " . sprintf("%0.4f",microtime(true) - $this->start_time);
		}

		// tier restrictions
		if (is_array($restrict_param_array))
		{

			$this->blackbox_obj->restrict($restrict_param_array[0], !$restrict_param_array[1]);
			$_SESSION['timer'][] = "Blackbox::Restrict " . sprintf("%0.4f",microtime(true) - $this->start_time);

		}

		// pick winner
		$winner = $this->blackbox_obj->pickWinner();
		$_SESSION['timer'][] = "Blackbox::Pick_Winner " . sprintf("%0.4f",microtime(true) - $this->start_time);

		// save a snapshot so we know what happened
		$this->Save_BlackBox_Snapshot();
		$_SESSION['timer'][] = "Blackbox::Save_Blackbox_Snapshot " . sprintf("%0.4f",microtime(true) - $this->start_time);

		// retrieve winner data
		if ($winner)
		{
			$return = $this->blackbox_obj->winner();
		}
		else
		{
			$return = array('denied' => TRUE);
		}

		$return['datax_decision'] = $this->blackbox_obj->getDataXDecision();

		// set return in session['blackbox']
		$_SESSION['blackbox'] = $return;


		return($return);

	}

	protected function Online_Confirmation($page)
	{



	}

	/** BEGIN REACT FUNCTIONS **/

	/**
	 @author Justin Foell
	 These are fantastic partial implementations of React_Page() for WAP Reacts

	*/
	public function Get_React_User_Data($app_id, $property_short)
	{
		// import user data
		$ent_cs = $this->Get_Ent_Cs($property_short);

		if(empty($app_id))
		{
			$app_id = $this->Get_Application_ID();
		}

		//We have to get this info from LDB, so let's fake it if we're using the new process.
		$old_process = $this->config->use_new_process;
		$_SESSION['config']->use_new_process = FALSE;
		$user_data = $ent_cs->Get_The_Kitchen_Sink($this->db, $this->database, $app_id, $property_short);

		// Mantis #12161 - Added in check for an active card	[RV]
		if($this->Is_CLK($property_short))
		{
			require_once('CS_Card_API.php');

			// Instantiate our CS_Card_API class
			$card_api = new csCardAPI($user_data, $property_short, $this->db, $this->config->mode);

			$cubis_status = $card_api->getCardStatus();
			$ecash_status = $ent_cs->Has_Active_Card($user_data['social_security_number'], $property_short, $this->db);
			$user_data['has_active_card'] = ($cubis_status) ? $cubis_status : $ecash_status;
		}

		$_SESSION['config']->use_new_process = $old_process;
		return $user_data;
	}

	/**

	@author Andrew Minerd
	@desc Main page handler for the Reactivation Process.

	This function is the main page handler for the
	reactivation process - in other words, it does
	just about everything.

	*/
	public function React_Page($page, $is_wap = FALSE)
	{

		// make sure we have this
		include_once('react.php');

		$errors = array();

		// LOAD OUR SPECIAL SITE_TYPE_OBJ
		if (!isset($this->config->site_type_obj->pages->ent_cs_confirm_react))
		{

			$database_prefix = (BFW_MODE == 'RC') ? 'rc_' : '';
			// get this special site type thing
			$manager = new Cache_Site_Type_Manager($this->sql, $database_prefix . 'olp_site_types');
			$site_type = $manager->Get_Site_Type('olp_reapply');

			// merge it into our current site type object
			foreach ($site_type->pages as $name=>$data)
			{
				$this->config->site_type_obj->pages->$name = $data;
			}

		}

		// UGLY!
		if (isset($this->collected_data['reckey']))
		{
			$key = $_SESSION['react']['key'] = $_SESSION['data']['reckey'];
		}
		else
		{
			$key = ($_SESSION['react']['key']) ? $_SESSION['react']['key'] : $_SESSION['data']['reckey'];
		}


		$this->Event_Log(TRUE);
		$this->Create_Application();


		$property_short = $this->ent_prop_short_list[$_SESSION['config']->site_name];
		$this->Setup_DB($property_short, $db_type = null);

		$this->React_Setup_Ent_Stats();

		// get our database information
		$server = Server::Get_Server($this->config->mode, 'REACT', $property_short);
		$database = $server['db'];

		// get our react information
		$react = new React($this->db, $this->sql, $this->config->mode, $database, $this->applog, $this->event);
		$react_info = $react->Get_React_Info($key);

		if ($react_info && (strtolower($react_info['property_short']) == strtolower($this->property_short)))
		{
			// retrieve the number of times we have
			// attempted our react
			$logins = $react_info['logins'];

			if (($logins >= 0) && ($logins < 6))
			{
				if ($page=='ent_cs_confirm_start')
				{

					// import react info into the session
					$_SESSION['data']['name_first'] = $react_info['name_first'];
					$_SESSION['data']['name_last'] = $react_info['name_last'];

					$this->next_page = 'ent_cs_confirm_react';

					if($is_wap) //this is a hack, please leave it here [JustinF]
						return $react_info;
				}
				elseif ($page=='ent_cs_confirm_react')
				{
					// get entered information
					$ssn = $this->collected_data['social_security_number'];
					$dob = $this->collected_data['dob'];

					if ($ssn == $react_info['ssn'])
					{
						//Check for existing react
						if($react->Existing_React($this->db, $ssn, $property_short))
						{
							$errors[] = 'already_applied';
							$this->next_page = 'ent_cs_confirm_start';

							// let's hit a stat for failed reacts too
							$this->React_Hit_Stat('react_already_applied');
							return $errors;
						}

						// we need a DB connection here
						$this->Setup_DB($react_info['property_short']);

						//if we want the latest app in the future dont supply an appid but for now
						// use the app id as reference [RL]
						$reacapp_id = (isset($react_info['app_id'])) ? $react_info['app_id'] : null;
						// import our DOB and transaction ID
						$cust_info = $react->Get_Cust_Info($this->db, $ssn, $reacapp_id);

						if ($cust_info['transaction_id'])
						{

							// verify the collected information
							$react_info = array_merge($react_info, $cust_info);

							$dob = date('Y-m-d', strtotime($dob));

							// For whatever reason we have a bad DOB we want to pass it along [RL]
							// And Check to make sure for is set [RL]
							if( (strstr($react_info['date_birth'],"1800")) || ($react_info['date_birth'] == "1970-1-1") || (trim($react_info['date_birth']) == "")	)
							{
								$react_dob = $dob;
							}
							else
							{
								$react_dob = date('Y-m-d', strtotime($react_info['date_birth']));
							}

							if (($dob == $react_dob) &&
									(
										(is_numeric($this->collected_data["date_dob_d"])) &&
										(is_numeric($this->collected_data["date_dob_m"])) &&
										(is_numeric($this->collected_data["date_dob_y"]))
									)
								)
							{


								// get our old transaction ID
								$app_id = $react_info['transaction_id'];
								$_SESSION['react']['transaction_id'] = $app_id;
								if (empty($app_id))
								{
									$_SESSION['react_cust_info'] = $cust_info;
									$_SESSION['react_react_info1'] = $react_info;
									$this->Applog_Write('No React Transaction ID Found: '.$_SESSION['react']['key'] . ' with session ID: ' . session_id() );
								}

								$user_data = $this->Get_React_User_Data($app_id, $react_info['property_short']);

								$_SESSION['data'] = array_merge($_SESSION['data'], $user_data);
								$_SESSION['data']['income_monthly_net'] = sprintf('%u', $user_data['income_monthly_net']);

								$blackbox = $this->Configure_Blackbox(NULL, NULL, MODE_ECASH_REACT);
								$cashline = $blackbox->runRule(strtoupper($react_info['property_short']), 'cashline');

								if($cashline)
								{
									// successful react, let's log this
									$this->React_Hit_Stat('react_success');

									// let them reapply
									$this->next_page = 'ent_reapply';
								}
								else
								{
									$errors[] = 'react_active_loan';

									$this->next_page = 'ent_cs_confirm_start';
									$this->event->Log_Event('REACT_UNDERACTIVE', EVENT_FAIL, $react_info['property_short'], $this->Get_Application_ID());
								}

							}
							else
							{
								// in the past, we had different errors depending on whether they're SSN or DOB
								// failed the check - in the interest keeping personal information as secure as
								// possible, I will no longer make that distinction
								$errors[] = 'dob_match';
							}

						}
						else
						{
							$errors[] = 'Your info could not be found';
							$this->Applog_Write('Exception: React has been verified, no record in db ('.$_SESSION['data']['reckey'].')');
						}

					}
					else
					{
						$errors[] = 'rec_nomatch';
					}

				}
				elseif ($page == 'ent_reapply')
				{
					// in the event that $_SESSION['config']->bb_force_winner does
					// not exist, property_short will now key off of site_name - since both
					// the Black Box and Enterprise configs have the same site_name
					if (isset($this->ent_prop_short_list[$_SESSION['config']->site_name]))
					{
						if (strtoupper($_SESSION['config']->mode) != "LIVE" && isset($this->normalized_data['no_checks']))
							$nochecks = TRUE;

						$target = $this->ent_prop_short_list[$_SESSION['config']->site_name];
						$failed = FALSE;
						// Check for disallowed state
						// We're not going to actually run blackbox.  We are only going to
						// extract the excluded stats rule for ent sites.

						// Check if NO_ACCOUNT was chosen for direct deposit. - GForge #10438 [DW]
						if ($_SESSION['data']['income_direct_deposit'] == 'NO_ACCOUNT' ||
							$this->collected_data['income_direct_deposit'] == 'NO_ACCOUNT')
						{
							$this->setNoAccount();
						}
						
						$this->Event_Log(TRUE);
						$blackbox = $this->Configure_Blackbox(null, null, MODE_ECASH_REACT);

						// Run account_type and direct_deposit for clk reacts only - GForge #10438 [DW]
						if (Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $target) 
							&& !$blackbox->runRule($target, 'bank_account_type', $_SESSION['data']))
						{
							$failed = TRUE;
							$comment = 'React changed account type to disallowed account';
						}
						elseif (Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $target)
							&& !$blackbox->runRule($target, 'income_direct_deposit', $_SESSION['data']))
						{
							$failed = TRUE;
							$comment = 'React was denied on direct deposit check';
						}
						elseif(!$blackbox->runRule($target, 'excluded_states', $_SESSION['data']))
						{
							$failed = TRUE;
							$comment = 'React changed state to disallowed state';
						}
						elseif(!$blackbox->runRule($target, 'military', $_SESSION['data']))
						{
							$failed = TRUE;
							$comment = 'React was denied by military check.';
						}
						elseif(!$blackbox->runRule($target, 'suppression_lists', $_SESSION['data']))
						{
							// Moved failed set inside if statement in case check was run, but skipped all of
							// the lists due to being a react which should not fail the app. - GForge #7259 [DW]
							if(!empty($_SESSION['SUPPRESSION_LIST_FAILURE']))
							{
								$failed = TRUE;
								$comment = 'React was denied by suppression list.';
								
								foreach($_SESSION['SUPPRESSION_LIST_FAILURE'] as $supp_key => $supp_val)
								{
									$comment = "React was denied failed $supp_key $supp_val list";
								}
							}
						}
						elseif (EnterpriseData::isCFE($target))
						{
							$cfe_config = new stdClass();
							$cfe_config->bb_mode = MODE_ECASH_REACT;
							$cfe_config->site_name = SiteConfig::getInstance()->site_name;
							$failed = !$blackbox->Run_CFE($target, $_SESSION['data'], $cfe_config);
							if ($failed)
							{
								$comment = 'React was denied by CFE.';
							}
						}
						if($failed)
						{
							$this->Force_Decline($this->application_id, $target, $comment);
							$this->next_page = 'app_declined';

							$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
							$app_campaign_manager->Update_Application_Status($this->application_id, 'FAILED');
						}


						// Run a cashline check if we haven't already declined
						// the customer
						//if ($this->next_page != "app_declined")
						//{
						//	$ssn = $_SESSION["data"]['social_security_number'];
						//	$db = Server::Get_Server($this->config->mode, 'BLACKBOX');
						//	$cashline = new Cashline($this->sql,$db['db']);
						//	$cl_check = $cashline->Check($ssn,array($target));

						//	if ( !count($cl_check) && !$nochecks)
						//	{
						//		$comment = "React failed cashline check";
						//		$this->Force_Decline($this->application_id, $target, $comment);
								//Stats::Hit_Stats( 'react_confirmed_overactive', $this->session, $this->event, $this->applog, $app_id);
						//		$this->event->Log_Event("REACT_CONFIRMED_OVERACTIVE", "FAIL", $target, $this->application_id);
						//		$this->next_page = "app_declined";
						//	}
						//}
					}

					if (!count($this->errors) && $this->next_page!='app_declined')
					{

						// hit prequal stat

						$this->React_Hit_Stat('prequal');

						// create our transaction
						$app_id = $this->React_Create_Transaction($react_info['property_short'], $is_wap);

						if ($app_id)
						{
							// get our legal documents
							$this->React_Get_Legal($react_info['property_short']);
							//$this->next_page = 'ent_reapply_legal';
						}
						else
						{
							// problem creating the transaction?
							$this->next_page = 'app_declined';
						}

					}

				}
				elseif ($page == 'ent_reapply_legal')
				{
					// At this point we need to switch over and hit
					// stats for the enterprise sites
					//$this->React_Setup_Ent_Stats();

					// agree to our legal docs
					if($this->Is_Ecash3($react_info['property_short']))
					{
						$this->Sign_Condor_Docs();
					}
					else
					{
						$this->condor->Condor_Get_Docs('signature_response', 'TRUE', '');
					}


					if (!count($this->errors))
					{

						// mark the key as used
						$react->Key_Used($key);

						// create their transaction
						$this->React_Hit_Stat('accepted');
						$this->React_Hit_Stat('confirmed');

						$this->React_Hit_Stat('react_confirmed', NULL, TRUE);

                        $this->React_Hit_Stat('bb_' . strtolower($react_info['property_short']) . '_agree', NULL, TRUE);

                        if(!isset($this->application_id) || $this->application_id == null ||
                            $this->application_id == "")
                        {
                            if($this->Get_Application_ID())
                            {
                                $this->application_id = $this->Get_Application_ID();
                            }
                            else
                            {
                                throw new Exception("Cannot Update Application Status - no app id set");
                            }
                        }

						// update olp application record
						$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						$app_campaign_manager->Update_Application_Status($this->application_id, 'CONFIRMED');

						if(!$this->config->use_new_process)
						{
							// LDB Needs to know this app is a react [RL]
							$olp_db = $this->Setup_OLP_DB($react_info['property_short']);
							$up_field["is_react"] = "yes";
							$olp_db->Update_Application($up_field, $this->application_id);
						}

						$_SESSION['is_react'] = 1;

						// updates sub status to confirmed and pushes them to ent_status
						if($is_wap) //I love this next line (to avoid problems performing a CS_Login) [JustinF]
							@$this->React_Get_Status($react_info['property_short'], $_SESSION['transaction_id']);
						else
							$this->React_Get_Status($react_info['property_short'], $_SESSION['transaction_id']);
					}

				}

			}
			elseif ($logins < 0)
			{
				$errors[] = 'already_applied';
				$this->next_page = 'ent_cs_confirm_start';

				// let's hit a stat for failed reacts too
				$this->React_Hit_Stat('react_already_applied');

			}
			elseif ($logins > 6)
			{
				$errors[] = 'too_many_attempts';
				$this->next_page = 'ent_cs_confirm_start';

				// let's hit a stat for failed reacts too
				$this->React_Hit_Stat('react_attempts');
			}

		}
		else
		{
			$errors[] = 'incorrect_link';
			$this->next_page = 'ent_cs_confirm_start';
			// incorrect link?  Let's log it!
			$this->React_Hit_Stat('react_badlink');
		}

		// something else?
		return($errors);

	}

	private function React_Hit_Stat($stat, $app_id = NULL, $use_new_stat = FALSE)
	{
		if (!$app_id) $app_id = $this->Get_Application_ID();
		if (!$app_id) $app_id = $this->transaction_id;
		if (!$app_id) $app_id = $_SESSION['transaction_id'];

		Stats::Hit_Stats($stat, $this->session, $this->event, $this->applog, $app_id, NULL, $use_new_stat);

	}

	// Hit react stats (accepted, confirmed for the enterprise site instead of the blackbox site.
	private function React_Setup_Ent_Stats()
	{
		$property_short = $this->ent_prop_short_list[$_SESSION['config']->site_name];
		$enterprise_license_key = $this->ent_prop_list[strtoupper($property_short)]['license'][$_SESSION['config']->mode];

		//Only set up a new config if we don't have the enterprise license key already
		if($enterprise_license_key != $_SESSION['config']->license)
		{
			$this->Setup_New_Config($enterprise_license_key, $_SESSION['config']->promo_id, $_SESSION['config']->promo_sub_code);
		}
	}

	private function React_Get_Legal($target)
	{

		// returns array with condor session data and legal doc
		// pass in document, legal status, data to merge onto app, prop type
		// check for refresh

		if($this->Is_Ecash3($target))
		{
			$this->Generate_Condor_Docs();
		}
		elseif(!$_SESSION['condor']->archive_id)
		{

			// pull up esig page
			// need to reset config legal_entity and site name to bb winner for legal docs
			$my_session['config'] = clone $_SESSION['config'];
			$my_session['data'] = $_SESSION['data'];

			$my_session['config']->legal_entity = $this->ent_prop_list[ strtoupper($target) ]["legal_entity"];
			$my_session['config']->site_name = $this->ent_prop_list[ strtoupper($target) ]["site_name"];
			$my_session['config']->support_fax = $this->ent_prop_list[ strtoupper($target) ]["fax"];
			$my_session['config']->property_short = strtoupper($target);
			$my_session['application_id'] = $this->Get_Application_ID();

			// unset stuff we don't need to pass
			unset($my_session['config']->site_type_obj);
			unset($my_session['data']['client_state']);


			// pull up esig page
			$this->esig_doc = $this->condor->Preview_Docs('paperless_form', $my_session);

			if (!$this->esig_doc)
			{
				$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
			}

			$this->condor->Condor_Get_Docs('signature_request', "", $my_session);
			$_SESSION['condor'] = $this->condor->response;

			// unset legal_content and legal_page so we don't overload the session
			unset($_SESSION['condor']->data);

		}

	}

	private function React_Get_Status($prop, $app_id = NULL)
	{
		//if (!$app_id) $app_id = $this->transaction_id;
		//if (!$app_id) $app_id = $_SESSION['transaction_id'];

		// in case of a refresh
		if(!$_SESSION['cs']['confirmed'])
		{

			$olp_db = $this->Setup_OLP_DB($prop);
			$ent_cs = $this->Get_Ent_Cs($prop);
			// update status

            if(empty($this->application_id))
            {
                $this->application_id = $this->Get_Application_ID();
            }

            if($this->config->use_new_process)
            {
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$app_campaign_manager->Update_Status_History($this->application_id, 'agree');
				$app_campaign_manager->Update_Status_History($this->application_id, 'ldb_unsynched');
				$app_campaign_manager->Update_Status_History($app_id, 'underwriting');
            }
            else
            {
				//set sub status to agree and then confirmed
				$olp_db->App_Completed_Updates($this->application_id);
				$olp_db->App_Confirmed_Updates($this->application_id);

				// We want reacts to go to the underwriting queue
				$olp_db->Update_Application_Status('underwriting', $this->application_id);
            }


			// add enterprise_data to normalized data because it's needed in ent_cs
			$this->normalized_data['enterprise_data'] = $this->enterprise_data;



			$user_data = $ent_cs->Get_User_Data($app_id);

			if(!$this->config->use_new_process)
			{
				$user_status = $ent_cs->Get_User_Status($app_id);

				// build the array for Ent_Cs
				$user_data = array_merge($user_data['cs'], $user_status);
			}

			$this->collected_data['transaction_id'] = $app_id;



			$_SESSION['cs'] = $user_data['cs'];
			$_SESSION['cs']['qualify'] = $_SESSION['data']['qualify_info'];

			// set confirmed flag true
			$_SESSION['cs']['confirmed'] = TRUE;

			$_SESSION['react_completion'] = TRUE;

			$user_data['cs']['qualify'] = array('fund_amount' => $user_data['cs']['fund_qualified']);
			$_SESSION['cs']['cust_password'] = $_SESSION['password'];

			if(!$this->config->use_new_process)
            {
				$ent_cs->Mail_Confirmation($this->ent_prop_list[strtoupper($prop)]['site_name'], TRUE, $user_data['cs']);
			}

		}

		$this->application_id = $_SESSION['transaction_id'];

		if($_SESSION['data']['loan_type'] == 'card')
		{
			$content = "<p>Thank you for accepting your loan. Your loan documents will be available in approx 15 minutes.
						Please return to the 500fastcash.com website and log in as a returning user with your user name and password to view and print your loan documents.</p>";

			$this->eds_page = array('content' => $content, 'type' => 'html' , 'action' => 'standard');
			$this->current_page = 'bb_extra';
			$this->next_page = 'bb_extra';
			$this->Run_Current_Page_Cases();
		}
		else
		{
			$this->current_page = 'ent_status';
			$this->Run_Current_Page_Cases();
		}
	}

	private function React_Create_Transaction($prop, $is_wap = FALSE)
	{

        if(empty($this->application_id))
        {
            if($this->Get_Application_ID())
            {
                $this->application_id = $this->Get_Application_ID();
            }
        }

		if (!$_SESSION['react_transaction_id'])
		{

			// set up db
			$this->Setup_DB($prop);

			$previous_appid = $_SESSION['react']['transaction_id'];

			// get a fund amount
			$qualify = new OLP_Qualify_2($prop, array(), $this->sql, $this->db, $this->applog, null, $this->title_loan);

			// Mantis #11748 - Set the weapp flag
			$qualify->Set_Is_Weapp($is_wap);

			$fund_amount = $qualify->Calculate_React_Loan_Amount(
				$this->normalized_data['income_monthly_net'],
				$this->normalized_data['income_direct_deposit'],
				$previous_appid,
				strtolower($this->normalized_data['income_frequency'])
			);

			if ($fund_amount)
			{

				// build our qualification information
				$this->Build_Qualify_Info($fund_amount);

				// instantiate olp db class
				$olp_db = $this->Setup_OLP_DB($prop);

				// Get the authentication data.
				$auth = new Authentication( $this->sql, $this->database, $this->applog );
				$authentication['authentication']				= $auth->Get_Records( $this->application_id );
				//$authentication['authentication']['track_hash']	= $this->blackbox_obj->DataX_Track_Hash();

				// merge transaction data to pass into
				$transaction_data = array_merge( $_SESSION['data'], $this->normalized_data, $authentication );

				// Putting in a check for card loan_type within the submitted data since that's what we should base our loan_type_id
				// on not what we pulled from the app that we are reacting from.  Ya that made sense in my head.  [RV]
				if($_SESSION['data']['loan_type'] == 'card')
				{
					$transaction_data['loan_type_id'] = $_SESSION['data']['loan_type_id'] = $this->Get_Loan_Type_ID($_SESSION['data']['loan_type']);
				}

				$transaction_data['paydate_model'] = $_SESSION['data']['paydate_model'];
				$transaction_data['property_short'] = $prop;

				// for old transaction where we did not collect bank_account_type
				// or best_call_period, default to CHECKING and MORNING
				if (!strlen($transaction_data['bank_account_type']))
				{
					$transaction_data['bank_account_type'] = $_SESSION['data']['bank_account_type'] = 'CHECKING';
				}
				if (!strlen($transaction_data['best_call_time']))
				{
					$transaction_data['best_call_time'] = $_SESSION['data']['best_call_time'] = 'MORNING';
				}

				// get campaign info records
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$transaction_data['campaign_info'] = $app_campaign_manager->Get_Campaign_Info($this->application_id);

				// set config data
				$transaction_data['config'] = clone $_SESSION['config'];

				// set ent config data
				$transaction_data['ent_config']->license = $this->ent_prop_list[strtoupper($prop)]['license'][strtoupper($_SESSION['config']->mode)];

				if($_SESSION["config"]->mode == "RC")
				{
					$transaction_data['ent_config']->site_name = 'rc'.$this->ent_prop_list[strtoupper($prop)]['site_name'];
					$transaction_data['config']->site_name = 'rc'.$transaction_data['config']->site_name;
				}
				else
				{
					$transaction_data['ent_config']->site_name = $this->ent_prop_list[strtoupper($prop)]['site_name'];
				}

				// set application_id in transaction_data
				$transaction_data['application_id'] = $this->application_id;

				// Add track_id to transaction data
				$transaction_data['track_key'] = strlen( $_SESSION['statpro']['track_key'] ) ? "{$_SESSION['statpro']['track_key']}" : 'null';

				// Set RecKey to prove that we are a react
				$transaction_data['reckey'] = $_SESSION['react']['key'];

				if (isset($this->blackbox['react']) || isset($_SESSION['config']->ecash_react))
				{
					$transaction_data['react'] = $_SESSION['is_react'] = true;
				}

				if($this->config->use_new_process)
				{
					$transaction_id = $this->application_id;
				}
				else
				{
					try
					{
						// create transaction
						$transaction_id = $olp_db->Create_Transaction($transaction_data, FALSE);
						$olp_db->Insert_React_Affiliation($transaction_data['application_id'],
														  intval($_SESSION['data']['react_app_id']),
														  $transaction_data['property_short']);
					}
					catch( Exception $e )
					{
						mail('jason.gabriele@sellingsource.com', 'Create Trans Failed', 'Exception creating React Transaction for reckey: ' . $_SESSION['react']['key'] . ' with session ID: ' . session_id());
						$this->Applog_Write( 'Rn creating React Transaction for reckey: ' . $_SESSION['react']['key'] . ' with session ID: ' . session_id() );
					}
				}

				// store this!
				$this->transaction_id = $transaction_id;

				// For various reasons, $_SESSION['transaction_id'] is being unset.  A separate session variable needs to be created to
				// store transaction ID specific to the react process
				$_SESSION['react_transaction_id'] = $_SESSION['transaction_id'] = $transaction_id;

				// update transaction_sub_status to confirmed
				// these rely on $_SESSION['transaction_id'] :-X
				$app_campaign_manager->Update_Application($this->application_id, $this->transaction_id, $prop);
				$app_campaign_manager->Update_Application_Status($this->application_id, 'PENDING');

				//Insert Application Info
				$app_campaign_manager->Insert_Application($this->application_id, $_SESSION['data'], $this->title_loan);
			}

		}
		else
		{
			$transaction_id = $_SESSION['react_transaction_id'];
		}

		if (!$transaction_id) $transaction_id = FALSE;
		return($transaction_id);

	}

	/** END REACT **/

	/**
	 *
	 */
	private function Create_Application()
	{
		if ($_SESSION['application_id'])
		{
			$this->application_id = $_SESSION['application_id'];
		}
		else
		{
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);

			$olp_process_info = array(
				'is_ecashapp' => isset($_SESSION['data']['ecashapp']),
				'has_reckey' => isset($_SESSION['data']['reckey']),
				'is_online_confirmation' => isset($this->config->online_confirmation),
				'is_preact' => isset($_SESSION['is_preact'])
			);

			//if we have no track_key, hit the visitor stat because we haven't already and we need to
			//before we insert.
			if(!isset($_SESSION['statpro']['track_key']))
			{
				Stats::Hit_Stats('visitor', $this->session, $this->event, $this->applog, null);
			}

			//	This change is for the Telephone Application Process. <--- (I have no idea what 'this change' refers to.)
			$this->application_id = $app_campaign_manager->Create_Application(
					$_SESSION['statpro']['track_key'],
					$this->config,
					$this->normalized_data['offers'],
					$this->config->license,
					$this->collected_data['client_ip_address'],
					$this->normalized_data['tel_app_proc'],
					$this->normalized_data['reservation_id'],
					$olp_process_info);

			$app_campaign_manager->Update_Application_Status($this->application_id, 'VISITOR');
			$_SESSION['application_id'] = $this->application_id;

			//Need to hit the no_market stat for cashnowbyphone.com
			if(!empty($this->normalized_data['reservation_id']) && $this->config->call_center)
			{
				$this->Event_Log();
				Stats::Hit_Stats('dm_no_market', $this->session, $this->event, $this->applog,  $this->application_id);
			}

		}
		// campaign info update is now handled in Page_Handler
	}

	/**

		Update campaign info tables in OLP and LDB when we change promos, etc.

	*/
	protected function Update_Campaign_Info($application_id)
	{
		if ($_SESSION['promo_override'])
		{
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Update_Campaign($application_id, $this->config);

			if (!$_SESSION['promo_update'])
			{
				$target = (isset($_SESSION['blackbox']['winner']) ? $_SESSION['blackbox']['winner'] : $this->property_short);

				if ($target && !$this->config->use_new_process)
				{
					try
					{
						$olp_db = $this->Setup_OLP_DB($target);
						$olp_db->Insert_Campaign_Info($application_id, $this->config);
					}
					catch(Exception $e)
					{
						$this->Applog_Write('Could not update campaign_info record in eCash.');
					}
				}

				$_SESSION['promo_update'] = TRUE;
			}

			$_SESSION['promo_override'] = FALSE;

		}

		return;
	}

	/**
	* @param bool
	* @desc prepares database vars for the winner
	*		creates db connection
	**/
	private function Setup_DB($property_short, $db_type = null)
	{

		if (!$property_short)
		{
			$this->applog->Write('Setup_DB:  no prop short passed in. session_id:'.session_id());
			throw new Exception('No property short passed into setup_db. session: '.session_id());
		}

		// get db type from the ent_prop_list using the target as the key
		$db_type = 'mysql';
		$mode = ($this->config->use_new_process || in_array($this->current_page, $this->process_exceptions))
			? $this->config->mode . '_READONLY' : $this->config->mode;

		// run setup db
		include_once(BFW_CODE_DIR . 'setup_db.php');
		$this->db = Setup_DB::Get_Instance($db_type, $mode, $property_short);
		$this->ldb_pdo = Setup_DB::Get_PDO_Instance($db_type, $mode, $property_short);
	}

	/**
	* @param bool
	* @desc Processes the Blackbox winner
	*
	**/
	private function Process_Winner()
	{

		$result = FALSE;
		$restrict = FALSE;

		// stop timer
		// $timer = new Timer($this->applog);
		// $timer->Timer_Start('Process Winner');

        if(($app_id = $this->Get_Application_ID()) === FALSE)
        {
        	throw new Exception("Cannot Process Winner - No App ID");
        }

		if($this->config->online_confirmation)
		{
			// currently only hit for the new process
			Stats::Hit_Stats("bb_processing", $this->session, $this->event, $this->applog, $app_id );
		}

		// We need to insert into the personal table before we run blackbox.  If we insert after
		// running blackbox, it's possible for soap customers to submit several apps within the time
		// it takes to attempt a post to all possible vendors.  This results in multiple apps to each
		// vendor for the same social/email (minimum_recur always passes since there'e no personal
		// record yet).
		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		if (!$personal_info = $app_campaign_manager->Get_Personal_Info($app_id))
		{
			$app_campaign_manager->Insert_Personal_Encrypted($app_id, $this->normalized_data);
		}

		$qualify = new Qualify_2(NULL);

		$clk_look = TRUE;
		if(!Blackbox_Adapter::isNewBlackbox() && !isset($this->blackbox['denied']))
		{
			//Get the possible winners for tier1 for this app and merge in the current winner
			$possible_winners = array_merge(
				$this->blackbox_obj->getPossibleWinners(1),
				array(strtoupper($this->blackbox['winner']) => strtoupper($this->blackbox['winner']))
			);

			//If we have possible CLK winners here, we'll need to check to see if CLK gets a look or not
			$clk_look = (count(array_intersect(array_map('strtoupper', $this->clk_properties), $possible_winners)) === 0);
		}

		// ECash Reacts Checkers
		// Moved before while loop so applications that are denied here do not send out confirmation emails first. - GF#8017 [MJ]
		if($_SESSION['config']->ecash_react)
		{
			$ecash_checks = array("DISALLOWED_STATES", "CASHLINE_CHECK", "SUPPRESSION_LIST","MINIMUM_INCOME");

			foreach($ecash_checks as $e_key => $e_check)
			{
				if(!$this->Ecash_React_Check($e_check))
				{
					$this->blackbox["denied"] = TRUE;
				}
			}
		}
		
		while($result !== TRUE && !isset($this->blackbox['denied']))
		{			
			
			$target = $this->blackbox['winner'];
			$tier = $this->blackbox['tier'];
			
			//[#3833] BB Frequency Scoring [TF] ~~~
			if(!Blackbox_Adapter::isNewBlackbox() && $this->blackbox_obj->mode() === MODE_DEFAULT)
			{
				// look for business flag to override freq score processing
				if (isset($_SESSION['data']['freq_score'])
					&& !$_SESSION['data']['freq_score']
					&& $this->config->mode != 'LIVE')
				{
					$this->event->Log_Event('FREQ_DECLINE', 'DEBUG_SKIP', $target, $this->application_id);
				}
				
				else
				{
					$freq_object = Accept_Ratio_Singleton::getInstance($this->sql);
					$test_obj = $this->blackbox_obj->getTarget($target);
					
					// call the singleton to check if current freq score(s) are within limits
					if(!$freq_object->testLimits($test_obj->frequency_limits, $_SESSION['data']['email_primary']))
					{
						// denied by freq limit, testLimits returned FALSE
						$this->event->Log_Event('FREQ_DECLINE', 'FAIL', $target, $this->application_id);
							
						// move to next winner
						if ($restrict)
						{
							$restrict = array(array($restrict=>TRUE), TRUE);
						}

						// get another winner
						$this->blackbox = $this->Blackbox($restrict);
						$restrict = FALSE;
						continue; 
					}
					
					else
					{
						// passed freq_decline check
						$this->event->Log_Event('FREQ_DECLINE', 'PASS', $target, $this->application_id);
					}
					//gonna post, add it to the memcache
					$freq_object->addPost($_SESSION['data']['email_primary']);  //$this->collected_data['email_primary']

				}
					
			}
			
			switch($tier)
			{

				// tier 1 case
				case 1:

					if(!$this->Is_Enterprise($target))
					{
						$result = $this->Post_To_Winner($target);
					}
					else
					{
						$result = TRUE;

						/*
							Check if were on ecashapp.com and if we're a react. If we're not
							a react then we deny the app. Otherwise, add it to LDB as usual.
						*/
						if(($_SESSION['config']->site_name == 'ecashapp.com' &&
							!isset($this->blackbox['react']) && !isset($this->blackbox['denied'])))
						{
							$this->blackbox['denied'] = 1;
							$is_not_react = TRUE;
						}
						else
						{
							// process a CLK loan
							if ($result) $result = ($this->Build_Qualify_Info()!==FALSE);
							if ($result) $result = $this->Process_First_Tier($target);

							if (!$result)
							{
								// don't use this tier again
								$restrict = $tier;
							}
						}

						if($this->Is_CLK($target))
						{
							$clk_look = TRUE;
						}
					}

					break;


				// tier 4 case
				case 4:
					switch (strtolower($target))
					{
						// ezm case sure to 2 step app process
						case 'ezm4':
						case 'ezmpan':
						case 'ezmcr':
						case 'ezmcr40':
						case 'ezmpan40':

							if ($this->current_page == 'bb_ezm_legal')
							{
								$this->normalized_data['qualify_info']['net_pay'] =  $qualify->Calculate_Monthly_Net($this->normalized_data['paydate_model']['income_frequency'],$this->normalized_data['income_monthly_net']);
								$result = $this->Post_To_Winner($target);
							}
							else
							{
								require_once BLACKBOX_DIR . 'vendor_post_impl_ezm.php';
								Vendor_Post_Impl_EZM::Set_Session_Data($target);

								// set page to bb_ezm_legal
								$this->next_page = 'bb_ezm_legal';

								// goodnight blackbox
								$_SESSION['blackbox_asleep'] = $this->blackbox_obj->sleep(TRUE);

								// return true
								return TRUE;
							}
						break;

						default:
							// send the loan???
							$this->normalized_data['qualify_info']['net_pay'] =  $qualify->Calculate_Monthly_Net($this->normalized_data['paydate_model']['income_frequency'],$this->normalized_data['income_monthly_net']);
							$result = $this->Post_To_Winner($target);
						break;
					}


				break;
				DEFAULT:
					// tier 0/2/3/etc case
					// send the loan???
					$this->normalized_data['qualify_info']['net_pay'] =  $qualify->Calculate_Monthly_Net($this->normalized_data['paydate_model']['income_frequency'],$this->normalized_data['income_monthly_net']);
					if(isset($target))
					{
						$result = $this->Post_To_Winner($target);
					}
					break;

				break;

			}

			if (!$result)
			{
				if ($restrict)
				{
					$restrict = array(array($restrict => TRUE), TRUE);
				}

				// get another winner
				$this->blackbox = $this->Blackbox($restrict);
				$restrict = FALSE;
			}
		}

		// don't need this anymore!
		if (key_exists('blackbox_asleep', $_SESSION))
		{
			unset($_SESSION['blackbox_asleep']);
		}

		if(!isset($this->blackbox['denied']))		
		{
			// [#9526]  If we have inserted a management list buffer record make sure we update it with the correct tier
			if($_SESSION["epm_collect"])
			{
				$group_id = (isset($this->config->group_id)) ? $this->config->group_id : 1;
				$ole_list_id = $_SESSION["config"]->ole_list_id ? $_SESSION["config"]->ole_list_id : 1;
				$ole_site_id = $_SESSION["config"]->ole_site_id ? $_SESSION["config"]->ole_site_id : 1;

				// Grab the app type
				$application_type = $this->Get_Application_Type();
				$application_types = array ('VISITOR','FAILED');

				include_once(BFW_MODULE_DIR.'olp/list_mgmt_collect.php');
				$no_sell_obj = new List_Mgmt_Collect($this->sql,$this->database);
					
				// If they are not supposed to be sold to then set a flag saying we did anyway and they can suck eggs
				$bb_vendor_bypass = ($no_sell_obj->Check_List_Mgmt_Nosell($this->normalized_data["email_primary"])) ? 1 : 0;

				$date_of_birth = "{$this->normalized_data["date_dob_y"]}-{$this->normalized_data["date_dob_m"]}-{$this->normalized_data["date_dob_d"]}";

				$no_sell_obj->Replace_Into_List_Mgmt_Buffer(
					$this->application_id,
					$this->normalized_data["email_primary"],
					$this->normalized_data["name_first"],
					$this->normalized_data["name_last"],
					$ole_site_id,
					$ole_list_id,
					$group_id,
					BFW_MODE,
					$this->config->license,
					$this->normalized_data["home_street"],
					$this->normalized_data["home_unit"],
					$this->normalized_data["home_city"],
					$this->normalized_data["home_state"],
					$this->normalized_data["home_zip"],
					$this->config->site_name,
					$this->normalized_data["phone_home"],
					$this->normalized_data["phone_cell"],
					$date_of_birth,
					$this->config->promo_id,
					$bb_vendor_bypass,
					$tier);
			}
			
			$this->Increment_Winning_Stat($this->blackbox);

			//record the total accept_ratio score in the DB
			if ($this->blackbox_obj->mode() === MODE_DEFAULT)
			{
				$freq_object = Accept_Ratio_Singleton::getInstance($this->sql);
				$freq_object->addAccept($_SESSION['data']['email_primary'], $this->blackbox['winner'], $this->application_id);
			}

			//If we hit nms_prequal and CLK didn't even get a look (a tier 1 BB vendor wins), then we hit this stat.
			if($this->blackbox['original_tier'] == 1
				&& !$clk_look
				&& isset($_SESSION['unique_stat']->post)
				&& $this->blackbox_obj->mode() === MODE_DEFAULT)
			{
				Stats::Hit_Stats('clk_no_look', $this->session, $this->event, $this->applog, $app_id);
			}

			// hit custom stat for gforge 9922
			if (!empty($this->blackbox['state_data']) 
				&& $this->blackbox['state_data'] instanceof Blackbox_IStateData
				&& $this->blackbox['state_data']->vetting_react_sold)
			{
				$vetting_stat_name = sprintf('vetting_react_sold_%s', $this->blackbox['winner']);
				$this->session->Hit_Stat($vetting_stat_name, TRUE, TRUE, 'NEW');
			}
			
			/**
			* Sends supertier winner data to PW via the Messaging system
			*/
			if (SiteConfig::getInstance()->use_firstlook_revshare)
			{
				$messaging_data = array(
					'react' => !empty($this->blackbox_obj->winner()->react),
					'vetted' => !empty(SiteConfig::getInstance()->use_vetting_tier),
					'promo_id' => SiteConfig::getInstance()->promo_id,
					'promo_sub_code' => SiteConfig::getInstance()->promo_sub_code,
					'vendor_looks' => $this->blackbox_obj->getWinners(),
					'pwadvid' => $_SESSION['data']['pwadvid'],
					'timestamp' => time()
				);
				
				switch (BFW_MODE)
				{
					default:
						$url = 'http://test.prpc.linkstattrack.com/firstlook.php';
						break;

					case 'LIVE':
						$url = 'http://prpc.linkstattrack.com/firstlook.php';
						break;
				}
				               
				Message_1::enqueue(new Message_Container_1('olp', $url, $messaging_data));
			}
		}
		else
		{
			// Mantis #10788 - Moved here for the If/Else statement below
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);

			// If we're working on the split tier1/tier2 validation, we currently have to bypass
			//   the failure and logging for tier1.  We'll pick it up normally for tier2          [LR]
			if (isset($_SESSION['bypass_tier1_failed']))
			{
				// hit the loser for this applicant
//				Stats::Hit_Stats("fail", $this->session, $this->event, $this->applog, $this->application_id ); Save this for later....

				// Insert Application even though it's Failed
				$app_campaign_manager->Insert_Application($app_id, $this->normalized_data, $this->title_loan);
				$app_campaign_manager->Update_Application_Status($app_id, 'VISITOR');
			}
			else
			{
				// LOHOOUSERHERR!
				if($is_not_react)
				{
					$this->next_page = "agent_not_react";
					$_SESSION["data"]["new_loan_url"] = "http://www.".
						$this->ent_prop_list[strtoupper($_SESSION["data"]["ecashapp"])]["site_name"];
				}
				else
				{
					// Mantis #10788 - Added in Check for Yellowbook/pages for the app_declined to display specific script/error
					$olp_process = $app_campaign_manager->Get_Olp_Process($app_id);

					if($olp_process == 'ecash_yellowpage')
					{
						$this->eds_page = array('content' => "<p>Thank you for your application.  You will receive an email soon with instructions on how to complete the loan application process.",
												'type' => 'html', 'action' => 'standard');
						$this->next_page = 'bb_extra';
						return(TRUE);
					}
					else
					{
						$this->next_page = "app_declined";
					}
				}

				// hit the loser for this applicant
				Stats::Hit_Stats("fail", $this->session, $this->event, $this->applog, $app_id );


				// update their application_type to failed.
				// Insert Application even though it's Failed
				$app_campaign_manager->Insert_Application($app_id, $this->normalized_data, $this->title_loan);
				$app_campaign_manager->Update_Application_Status($app_id, 'FAILED');
			}

			// Send Agent React Denial Letters [RL]
			// Promo ID for ECash Reacts: 27713
			if($_SESSION['config']->ecash_react)
			{
				$this->Email_React_Denial();
			}
		}

		// stop timer
		// $timer->Timer_Stop('Process Winner');
		// $time = $timer->Get_Elapsed('Process Winner');

		return(TRUE);

	}

	/*
	 * @desc Call the posting functions and try to post the loan to the winner
	 * @desc log all information to the database
	 * @return object Vendor_Post_Result object
	 */
	private function Post_To_Winner( $target )
	{
		// Since double posting is a big problem with soap apps, we need to re-run something
		// similar to the minimum recur check in blackbox.  Since soap apps generally come
		// within the minute, we will only do a one day check.
		if (!isset($this->normalized_data['no_checks']) || (isset($this->normalized_data['rules']) && $this->normalized_data['rules']=='1') || isset($this->normalized_data['secondary_recur']))
		{
			if (!$result = Log_Vendor_Post::Secondary_Recur_Check( $this->sql, $this->database, $target, $this->application_id, $this->normalized_data ))
			{
				$this->event->Log_Event('SECONDARY_RECUR', 'FAIL', $target, $this->application_id);
				return FALSE;
			}
		}

		// create new vendor post object if we need to
		if (! ($this->vendor_post instanceof Vendor_Post))
		{
			$this->vendor_post = new Vendor_Post($this->sql, $target, $_SESSION, $this->config->mode, $this->applog);
		}
		else
		{
			// otherwise just set the new winner
			$this->vendor_post->Set_Property_Short($target);
		}


		// Insert a dummy record so that we know we've started the process.
		Log_Vendor_Post::Insert_Dummy_Record( $this->sql, $this->database, $target, $this->application_id );

		// Hit lead_sent stat for OLP BB Graphs
		Stats::Hit_Stats(
			'lead_sent_'.$target,
			$this->session,
			$this->event,
			$this->applog,
			$this->application_id,
			NULL,
			TRUE
		);

		// lets try to send this bad boy
		$vp_result = $this->vendor_post->Post();

		if (!$vp_result)
		{
			// No vendor post implementation exists
			$this->event->Log_Event( "POST", "NO_IMPL", $target, $this->application_id);
			return FALSE;
		}

		if ($this->vendor_post->Post_Timeout_Exceeded())
		{
			Stats::Hit_Stats("vendor_post_timeout", $this->session, $this->event, $this->applog, $this->application_id, $target );
		}
		else
		{
			/*
				Hit response_received stat for OLP BB Graphs
				Hitting it here because if we timed out, we really didn't get a response
			*/
			Stats::Hit_Stats(
				'response_received_'.$target,
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE
			);

			// GF #3395 BBx - Check Giant - Dup Check [TF]
			// "failed_cg_dupes" is set by vendor_post_impl_cg_new
			if((is_object($vp_result)) && strcasecmp(($vp_result->Get_Data_Received()),"failed_cg_dupes")==0){
				Stats::Hit_Stats(
					'cg_dupe_'.$target,
					$this->session,
					$this->event,
					$this->applog,
					$this->application_id,
					NULL,
					TRUE
				);
		}

		}

		// We have am array of vendor post results.. This means that the vendor choose which target purchased it of
		// their valid targets.  So we need to do some work on the logs to make sure everything is correctly recorded. [LR]
		if ( is_array($vp_result) )
		{
			// Hit Reject Stat for OLP BB Graphs
			Stats::Hit_Stats(
				'reject_'.$_SESSION['blackbox']['winner'],
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE // Treat this as StatPro only
			);

			$this->event->Log_Event('POST', EVENT_FAIL, $_SESSION['blackbox']['winner']);

			Log_Vendor_Post::Log_Vendor_Post($this->sql, $this->database, $target, $this->application_id, $vp_result[0]);

			$target = $_SESSION['blackbox']['new_winner'];
			$_SESSION['blackbox']['winner'] = $target;
			$this->event->Log_Event('PICK_WINNER', EVENT_PASS, $target);

			/*
				Hit lead_sent stat for OLP BB Graphs
				The lead really isn't sent for the other vendor. Same goes for the
				response_received below. The real time it took this vendor to give
				us a response is the time from the first stat hits.
			*/
			Stats::Hit_Stats(
				'lead_sent_'.$target,
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE
			);

			Log_Vendor_Post::Insert_Dummy_Record( $this->sql, $this->database, $target, $this->application_id );
			$vp_result = $vp_result[1];

			// Hit response_received stat for OLP BB Graphs
			Stats::Hit_Stats(
				'response_received_'.$target,
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE
			);
		}

		// lets put this stuff in the db
		Log_Vendor_Post::Log_Vendor_Post($this->sql, $this->database, $target, $this->application_id, $vp_result);

		// Grab target rules - needed in order to hit
		// rule-dependent events (verify_post/verify_post2)
		// VERIFY RULES IS DUMB [CB]
		/*if(isset($target))
		{
			$target_obj = $this->blackbox_obj->getTarget($target);	
		}*/

		if (!$vp_result->Get_Data_Received()) // Mantis #8353 [DY]
		{
			Stats::Hit_Stats(
				'blank_response_'.$target,
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE
			);
		}

		// successfully posted and accepted
		if ($vp_result->Is_Success())
		{
			//Disable popups upon success
			unset($this->config->sitelifter);

			//********************************************* 
			// GForge 8672
			// Disable the exit strategy killer
			// at least until we figure out how we can get
			// around it
			//********************************************* 
			if(!in_array( strtolower($target), array('test1', 'pwarb')))
			{
				unset($this->config->exit_strategy);
			}
			//********************************************* 
			// End Disable Exit Strategy Killer
			//********************************************* 

			// log post pass
			$this->event->Log_Event('POST', 'PASS', $target, $this->application_id);

			// Verify post is stupid and no one uses it
			/*if (isset($target_obj) && $target_obj instanceof BlackBox_Target_OldSchool && $target_obj->VerifyPost())
			{
				$this->event->Log_Event('VERIFY_POST_2', 'PASS', $target, $this->application_id);
			}*/

			// check to see if there is a next_page set
			if ($vp_result->Is_Next_Page())
			{
				$this->next_page = $vp_result->Is_Next_Page();
			}
			else
			{

				// build thank-you page
				//$this->eds_page = array('content' => $vp_result->Get_Thank_You_Content(), 'type' => 'html', 'action' => 'standard');
				$_SESSION['data']['thanks_content'] = $vp_result->Get_Thank_You_Content();
				$this->next_page = 'bb_thanks';

			}

			$result = TRUE;

			//Since this can be in any tier, we need to ensure qualify_info is populated for customer service
			// to work. This normally only gets populated correctly for tier 1. Gforge 3878 [VT]
			if($_SESSION['qualify_info'])
				$this->normalized_data['qualify_info'] = $_SESSION['qualify_info'];

			// update current application
			$this->Update_Current_Application($target);

			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Update_Application_Status($this->application_id, 'COMPLETED');

			// Check to see if this vendor wants its applicants on the nosell list
			// Vendor doesn't want this lead to be sent to epm_collect so remove it from the
			// outgoing epm_collect buffer. Also add this email to the main nosell list.
			// Moved the object function call to the if statement. [BF]
			if ($this->blackbox_obj->sellToListManagement())
			{
				include_once(BFW_MODULE_DIR.'olp/list_mgmt_collect.php');
				$no_sell_obj = new List_Mgmt_Collect($this->sql,$this->database);
				$no_sell_obj->Replace_Into_List_Mgmt_Nosell($_SESSION['data']['email_primary'],$target);
			}

		}
		else
		{

			// Hit Reject Stat for OLP BB Graphs
			Stats::Hit_Stats(
				'reject_'.$target,
				$this->session,
				$this->event,
				$this->applog,
				$this->application_id,
				NULL,
				TRUE // Treat this as StatPro only
			);

			// log post fail
			$this->event->Log_Event('POST', 'FAIL', $target, $this->application_id);

			if (!isset($_SESSION['bypass_withheld_targets']) || $_SESSION['bypass_withheld_targets'] == FALSE)
			{
				$this->blackbox_obj->withholdTargets();
			}

			unset($_SESSION['bypass_withheld_targets']);
				
			// Verify post is stupid. [CB]
			/*if($target_obj->VerifyPost())
			{
				$this->event->Log_Event('VERIFY_POST_2', 'FAIL', $target, $this->application_id);
			}*/

			// add target to the post_failures
			$this->post_failures[$target] = TRUE;

			$result = FALSE;

		}

		return($result);

	}

	private function Refresh_Thank_You($application_id, $prev_winner)
	{
		// With the new intermediate redirect page, the redirect may
		// still be set and we won't see the thank you page again.
		if(!empty($_SESSION['data']['redirect']))
		{
			unset($_SESSION['data']['redirect']);
		}

		// hack for now -- hate this!
		if (in_array($prev_winner, array('EZMCR', 'EZMCR40', 'EZMPAN', 'EZMPAN40')))
		{
			$_SESSION['data']['imagine_card'] = true;
			$_SESSION['data']['bb_winner'] = $prev_winner;
			$this->next_page = 'imagine_card';
			return true;
		}

		// get the correct post implementation
		$post = Vendor_Post::Find_Post_Implementation($prev_winner, $this->config->mode, $_SESSION);

		if ($post)
		{
			$data_received = null;
			if (!$post->Static_Thankyou_Content())
			{
				$data_received = Log_Vendor_Post::Get_Data_Received($this->sql, $this->database, $application_id, $prev_winner);
			}

			// rebuild the page
			$content = $post->Thank_You_Content($data_received);
			$_SESSION['data']['thanks_content'] = $content;
		}

		$this->next_page = 'bb_thanks';

		return true;
	}

	private function Process_First_Tier($target)
	{


		$result = FALSE;

        if(empty($this->application_id))
        {
            $this->application_id = $this->Get_Application_ID();
        }

		try
		{

			$alias = $target;
			$target = Enterprise_Data::resolveAlias($target);

			// Only run this if there is no transaction id in the session
			if (!$_SESSION['transaction_id'])
			{

				// instantiate olp db class
				$olp_db = $this->Setup_OLP_DB($target);

				// Get the authentication data.
				$auth = new Authentication( $this->sql, $this->database, $this->applog );
				$authentication['authentication']	= $auth->Get_Records( $this->application_id );
				$authentication['authentication']['track_hash'] = $this->blackbox_obj->getDataXTrackHash();

				// merge transaction data to pass into
				$transaction_data = array_merge( $_SESSION['data'], $this->normalized_data, $authentication );
				$transaction_data['property_short'] = $target;

				// get campaign info records
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$transaction_data['campaign_info'] = $app_campaign_manager->Get_Campaign_Info($this->application_id);

				// set config data
				$transaction_data['config'] = $_SESSION['config'];

				// set ent config data
				$transaction_data['ent_config']->license = $this->ent_prop_list[strtoupper($target)]['license'][strtoupper($_SESSION['config']->mode)];
				$transaction_data['ent_config']->site_name = $this->ent_prop_list[strtoupper($target)]['site_name'];

				// set application_id in transaction_data
				if (isset($this->blackbox['react']) || isset($_SESSION['config']->ecash_react))
				{
					$transaction_data['is_react'] = $_SESSION['is_react'] = TRUE;
				}

				$transaction_data['application_id'] = $this->application_id;

				// Set track id (used to be set somewhere else but after conversion from DB2 to MySqli wasn't happening)
				//$transaction_data['track_id'] = $_SESSION['statpro']['track_key'];

				// Moved out of olp.mysql
				$transaction_data['track_key'] = strlen( $_SESSION['statpro']['track_key'] ) ? "{$_SESSION['statpro']['track_key']}" : 'null';

				if($this->config->use_new_process)
				{
					$_SESSION['transaction_id'] = $this->Update_Current_Application($alias);
					$_SESSION['datax_track_hash'] = $this->blackbox_obj->getDataXTrackHash();

					if (isset($this->blackbox['react']))
					{
						$this->transaction_id = $_SESSION['transaction_id'];
					}
				}
				else
				{
					if (Enterprise_Data::isCFE($alias))
					{
						$transaction_data['asynch_result'] = $app_campaign_manager->Get_Asynch_Result($this->application_id);
					}

					$this->Update_Current_Application($alias);

					//Don't want to send an email for these processes since it will be sent later.
					$send_email = !(isset($_SESSION['ecashnewapp']) || $this->config->call_center);

					// Mantis #11482 - Get olp_process and set it in $transaction_data to make sure that the Insert_Application function has the info needed.
					$transaction_data['olp_process'] = $app_campaign_manager->Get_Olp_Process($this->application_id);

					// Create transaction
					$this->transaction_id = $olp_db->Create_Transaction($transaction_data, $send_email);
					$_SESSION['transaction_id'] = $this->transaction_id;
				}



				// stats
				// hit the stat for this target
				if(!($_SESSION['config']->ecash_react && isset($this->blackbox['denied'])))
				{
					Stats::Hit_Stats(
						"bb_{$alias}",
						$this->session,
						$this->event,
						$this->applog,
						$this->application_id
					);
				}

				if(!$this->config->use_new_process && isset($_SESSION['data']['ecashapp'])
					&& isset($_SESSION['data']['react_app_id']))
				{
					$olp_db->Insert_React_Affiliation($_SESSION['transaction_id'],
													  intval($_SESSION['data']['react_app_id']),
												  	  $target,
												  	  $_SESSION['data']['agent_id']);
				}

				if (!isset($this->config->online_confirmation))
				{
					// Ignore esig if bypass_esig flag is set in config - LR
					// Promo Id For ECash Reacts: 27713
					if (! $this->config->bypass_esig || $_SESSION['config']->ecash_react)
					{
						// hit the popagree stat
						Stats::Hit_Stats("popagree", $this->session, $this->event, $this->applog, $this->application_id );

						// pull up esig page
						// need to reset config legal_entity and site name to bb winner for legal docs
						// copy session into my_session so we don't pollute the session
						$my_session['config'] = clone $_SESSION['config'];
						$my_session['data'] = $_SESSION['data'];
						$my_session['config']->property_short = $_SESSION['blackbox']['winner'];

						$my_session['config']->legal_entity = $this->ent_prop_list[ strtoupper($target) ]["legal_entity"];
						$my_session['config']->site_name = $this->ent_prop_list[ strtoupper($target) ]["site_name"];
						$my_session['config']->support_fax = $this->ent_prop_list[ strtoupper($target) ]["fax"];
						$my_session['application_id'] = $this->Get_Application_ID();

						// unset stuff we don't need to pass
						unset($my_session['config']->site_type_obj);
						unset($my_session['data']['client_state']);

						$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $my_session);
						if (!$this->esig_doc)
						{
							$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
						}

						// returns array with condor session data and legal doc
						// pass in document, legal status, data to merge onto app, prop type
						$this->condor->Condor_Get_Docs('signature_request', "", $my_session);

						// unset legal_content and legal_page so we don't overload the session
						$response = $this->condor->response;
						unset($response->data);

						$_SESSION['condor'] = $response;

						$app_campaign_manager->Update_Application_Status($this->application_id, 'PENDING');
						$this->next_page = 'esig';


					}
					else
					{
						// update application stat to agree - LR
						$app_campaign_manager->Update_Application_Status($this->application_id, 'AGREE');
						$olp_db->Update_Application_Status('agree', $this->application_id);
					}

				}
				else
				{
					//No Popups Upon winner
					unset($this->config->sitelifter);
					unset($this->config->exit_strategy);

					// new online confirmation process
					$app_campaign_manager->Update_Application_Status($this->application_id, 'PENDING');

					if(!empty($_SESSION['data']['reservation_id']) && !empty($_SESSION['data']['csr_complete']))
					{
						require_once(BFW_MODULE_DIR . 'ocs/ocs.php');
						$ocs = new OCS('OLP', $this->config->mode);
						$ocs->Insert_Application(
							$this->application_id,
							$_SESSION['data']['reservation_id'],
							$_SESSION['data']['social_security_number']
						);
					}

					//For IVR applications, they use the SOAP API to confirm and agree.
					//Because of this, they never hit a redirect page, and subsequently,
					//they never hit popconfirm.  Unfortunately, CLK's weighting is based on
					//popconfirm, so we need to hit it here for those apps.
					if(SiteConfig::getInstance()->is_ivr_app && $this->Is_CLK($target))
					{
						$limits = new Stat_Limits($this->sql, $this->sql->db_info['db']);
						$result = $limits->Increment("bb_{$alias}_popconfirm", NULL, NULL, NULL);
						Stats::Hit_Stats('popconfirm', $this->session, $this->event, $this->applog, $this->application_id);
					}

					// show Blackbox thank you page
					$this->Get_Online_Confirm_Thanks($alias);
					$this->next_page = 'bb_thanks';

					//Hit Redirect Page Stat
					if(!isset($_SESSION['redirect_logged']))
					{
						$this->event->Log_Event('REDIRECT_PAGE', 'TRUE', $alias, $this->application_id);
						$_SESSION['redirect_logged'] = 1;
					}

					// If we are a ECash React we need to display the ECash Agent React Complete Page [RL]
					if ($_SESSION['config']->ecash_react && !isset($this->blackbox['denied']))
					{

						$_SESSION["data"]["fund_amount"] = $this->blackbox["fund_amount"];
						$_SESSION["data"]["due_date"]    = date("m/d/Y",strtotime($_SESSION["data"]["qualify_info"]["payoff_date"]));

						//$original_redirect_url = $_SESSION["data"]["online_confirm_redirect_url"];
						//$online_confirm_redirect_url = $_SESSION["data"]["online_confirm_redirect_url"]."&force_new_session";
						$online_confirm_redirect_url = $_SESSION["data"]["online_confirm_redirect_url"];

						// May Get Removed once ECash Stops using parrelle testing for ECash 3.0
						if($_SESSION["data"]["ecashdn"])
						{
							$online_confirm_redirect_url = $online_confirm_redirect_url."&ecashdn=".$_SESSION["data"]["ecashdn"];
						}

						// We want to perform a different type of confirmation [RL]
						if ($_SESSION['config']->site_name == 'ecashapp.com')
        				{
        					$online_confirm_redirect_url = $online_confirm_redirect_url."&react_confirm=1";
        				}

        				$_SESSION["data"]["online_confirm_redirect_url"] =
        					$_SESSION["data"]["online_confirm_redirect_url"]."&ecash_confirm=1";

						$ent_cs = $this->Get_Ent_Cs($_SESSION['blackbox']['winner']);

						// We actually want the agent to have the $online_confirm_redirect_url, so set the session
						// to that.
						$_SESSION["data"]["online_confirm_redirect_url"] = $online_confirm_redirect_url;
						$_SESSION["data"]["application_id"] = $this->Get_Application_ID();

						$comment_type = (isset($_SESSION['data']['reactforce'])) ? 'Agent-Initiated' : 'ECashApp';

						if($this->config->use_new_process)
						{
							$_SESSION['ldb_data'][$this->application_id]['comments'][] = array(
								'property_short' => $target,
								'application_id' => $this->application_id,
								'type' => 'standard',
								'comment' => $comment_type . ' React (confirmation email sent)',
								'agent_id' => $_SESSION['data']['agent_id']
							);
						}
						else
						{
							// updating ldb::comment table
							$comment['property_short'] = $target;
							$comment['application_id'] = $this->application_id;
							$comment['type'] = "standard";
							$comment['comment'] = $comment_type . ' React (confirmation email sent)';

							$olp_db->Insert_Comment($comment, $_SESSION['data']['agent_id']);
						}

						if(!isset($_SESSION['data']['reactforce']))
						{
							$this->next_page = 'agent_react_confirm';
						}
					}
					elseif($_SESSION['config']->ecash_react && isset($this->blackbox['denied']) && !$this->config->use_new_process)
					{
				        //Do we need this???  (Yes we do [for confirmation])
						$app_id = $this->Get_Application_ID();

						$olp_db->Update_Application_Status('denied', $app_id);

						//Send Adverse Action
						include_once(OLP_DIR . 'adverse_action.php');

						// "configuration" for BlackBox
						$config = new stdClass();
						$config->sql = &$this->sql;
						$config->session = &$this->session;
						$config->log = &$this->event;
						$config->application_id = $app_id;
						$config->database = $this->database;
						$config->clk_properties = $this->clk_properties;
						$config->impact_properties = $this->impact_properties;
						//$config->compucredit_properties = $this->compucredit_properties;
						$config->agean_properties = $this->agean_properties;
						$config->entgen_properties = $this->entgen_properties;

						// Add check for Impact Compnay 2 (ifs) - GForge #2892 [DW]
						if($this->Is_Impact($_SESSION['data']['ecashapp']))
						{
							$aa = new Adverse_Action_Impact($config, 'aa_denial_impact');
						}
						else
						{
							$aa = new Adverse_Action($config, 'aa_denial_clk');
						}

						$aa->Update_Denial_Winner();

						$comment['property_short'] = $target;
						$comment['application_id'] = $app_id;
						$comment['type'] = 'standard';
						$comment['comment'] = 'Adverse Action email sent.';

						$olp_db->Insert_Comment($comment, $_SESSION['data']['agent_id']);
					}
				}

				$app_campaign_manager->Update_Application($this->application_id, $this->transaction_id, $alias);

			}

			$result = TRUE;

		}
		catch( Exception $e )
		{

			// transaction insert failed!!!
			// we must pick another winner on a second tier
			$this->applog->Write("app_id: ".$this->application_id." - Unknown exception: {$e->getMessage()}");
			$this->event->Log_Event('TRANSACTION_INSERT', 'ERROR');

			$result = FALSE;

		}

		return($result);

	}

	private function Build_Qualify_Info($fund_amount = NULL)
	{

		$qualify_info = NULL;
		$react_loan = (isset($_SESSION["blackbox"]["react"])) ? TRUE : FALSE;

		// default to local value
		if ((!is_numeric($fund_amount)) && array_key_exists('fund_amount', $this->blackbox))
		{
			$fund_amount = $this->blackbox['fund_amount'];
		}

		if ($fund_amount)
		{
			// Prepare call to Qualify_2->Qualify
			$pay_dates = $_SESSION['data']['paydates'];
			$frequency = $_SESSION['data']['paydate_model']['income_frequency'];
			$monthly_net = $this->normalized_data['income_monthly_net'];
			$direct_deposit = $this->normalized_data['income_direct_deposit'];
			$date_hire = $this->normalized_data['employer_length'];

			// Call Qualify_1->Qualify_Person to obtain qualify_info

			$holidays = (is_array($_SESSION['holiday_array'])) ? $holidays = $_SESSION['holiday_array'] : array();

			if($this->Is_Preact())
			{
				$is_preact = TRUE;
				$react_app_id = intval($_SESSION['data']['react_app_id']);
			}
			else
			{
				$is_preact = FALSE;
				$react_app_id = NULL;
			}

			if(isset($_SESSION['data']['ecashapp']))
			{
				$this->Setup_DB($_SESSION['data']['ecashapp']);
				$db = $this->db;
				$prop = $_SESSION['data']['ecashapp'];
			}
			else
			{
				$prop = NULL;
				$db = NULL;
			}

			$qualify = new OLP_Qualify_2($prop, $holidays, $this->sql, $db, $this->applog, null, $this->title_loan);
			$result = $qualify->Qualify_Person($pay_dates, $frequency, $monthly_net, $direct_deposit, $date_hire, $fund_amount, $react_loan, $react_app_id, $is_preact);

			if (!array_key_exists('errors', $result))
			{

				$_SESSION['data']['qualify_info'] = $result;
				$this->normalized_data['qualify_info'] = $result;
				$qualify_info = $result;

			}
			else
			{

				$this->Applog_Write("Unexpected qualify failure, errors follow. Trying again and restricting 1st tier.");

				foreach($result['errors'] as $errstr)
				{
					$this->Applog_Write("\tError: $errstr");
				}

				$qualify_info = NULL;

			}

		}

		if (!$qualify_info)
		{
			$this->Applog_Write('QUALIFY_INFO', 'FAIL', $target);
		}


		return($qualify_info);

	}

	private function Increment_Winning_Stat($winner)
	{

		$server = Server::Get_Server($this->config->mode, 'BLACKBOX_STATS');

		if($winner['winner'])
		{
			// some info about our winner
			// Grab the lender specified winning target, if there is one [LR]
			$target = ($_SESSION['blackbox']['new_winner']) ? $_SESSION['blackbox']['new_winner'] : $winner['winner'];

			$tier = $winner['original_tier'];
			$react = (isset($winner['react']));

			// get our stat name
			$field = "bb_{$target}";

			//This should never be used anymore since the bb_*_agree stats are hit in ent_cs.
			/*if($tier == 1	&& $this->Is_Enterprise($target))
			{
				$field .= '_agree';
			}*/

			$limits = new Stat_Limits($this->sql, $server['db']);
			
			//Enterprise bb_* stats are hit in Process_First_Tier() for some reason.
			if(!$this->Is_Enterprise($target))
			{
				// hit target stat
				Stats::Hit_Stats($field, $this->session, $this->event, $this->applog, $this->application_id);
			}

			//We hit submitlevel1 some other place (Run_Next_Page_Cases and ONLY for SOAP apps)
			if($tier != 1)
			{
				// hit submit level stat
				Stats::Hit_Stats('submitlevel' . $tier, $this->session, $this->event, $this->applog, $this->application_id);
			}
			else
			{
				// We only hit submitlevel1 for CLK
				if ($this->Is_CLK($target))
				{
					Stats::Hit_Stats('submitlevel1', $this->session, $this->event, $this->applog,  $this->application_id);
				}
				
				// Everyone in tier 1 hits SLT1, though.  See GForge #10602.
				Stats::Hit_Stats('slt1', $this->session, $this->event, $this->applog,  $this->application_id);
				$limits->Increment('submitlevel1', NULL, SiteConfig::getInstance()->promo_id, NULL);
			}

			//If an enterprise company is sending leads from their own sites, don't increment the standard field
			if($this->Bypass_Limits($target))
			{
				$result = $limits->Increment($field, NULL, SiteConfig::getInstance()->promo_id, NULL);
			}
			// only update the stat limit table if we're not a react
			elseif(!$react)
			{
				// increment our counter for this target
				$result = $limits->Increment($field, NULL, NULL, NULL);

				if($result === FALSE)
				{
					$this->Applog_Write("*** QUERY FAILED WHILE UPDATING LIMIT FOR {$target} ***");
				}
			}
		}

		return TRUE;
	}

	/**
	 * Saves a Blackbox snapshot into the database.  The snapshot
	 * contains data that can be vital in determining how it made
	 * its decision on who to pick as a winner.
	 *
	 * @return bool TRUE if the save was successful.
	 */
	private function Save_BlackBox_Snapshot()
	{
		$result = FALSE;

		if ($this->blackbox_obj)
		{
			// get the snapshot from BlackBox and serialize it
			$snapshot = $this->blackbox_obj->getSnapshot();
			
			if (!empty($snapshot))
			{
				$snapshot = mysql_escape_string(serialize($snapshot));
	
				try
				{
					$query = "REPLACE INTO
						blackbox_snapshot (application_id, date_created, snapshot)
						VALUES ({$this->application_id}, NOW(), '{$snapshot}')";
					$this->sql->Query($this->database, $query);
	
					// make sure it worked
					$result = ($this->sql->Affected_Row_Count() > 0);
				}
				catch (MySQL_Exception $e)
				{
					// MySQL4 will already spit out its own applog entries when it encounters an exception
				}
			}
		}

		return $result;
	}

	/**
	* @param bool
	* @desc Instantiates Event_Log obj
	**/
	private function Event_Log($override = FALSE)
	{

		if (!$this->event || $override)
		{

			require_once( BFW_CODE_DIR . 'event_log.singleton.class.php' );

			$table = isset( $_SESSION['event_log_table'] ) ? $_SESSION['event_log_table'] : NULL;
			$application_id = $this->Get_Application_ID();

			$this->event = Event_Log_Singleton::Get_Instance($this->config->mode, $application_id);

			if (!$this->event)
			{
				$this->Applog_Write("ERROR setting up event_log. app_id: " . $application_id . " this->config->mode:" . $this->config->mode . $_SESSION['transaction_id']. ", property_short = $property_short, session_id = " .  session_id() );
			}
			
			if($application_id > 0)
			{
				$_SESSION['event_log_table'] = $this->event->tableName();
			}
		}

	}

	/**
	* @param string
	* $desc  Send a message to our applog
	**/
	public function Applog_Write($message)
	{
		$this->applog->Write($message);
	}


	/**
	* @param bool
	* @desc Checks to make sure that if the winner persists
	**/
	private function App_Completed_Check($application_id = NULL)
	{

		if (is_null($application_id))
		{
			$application_id = $this->Get_Application_ID();
		}

		// considering the pages in the page_order array
		// are the pages with the important forms on it
		// only restrict the user from submitting any of those pages
		// this is a way to filter our pages like testimonials,
		// questions, how it works, etc...
		if ( (!in_array($this->current_page, $this->config->site_type_obj->page_order)
			/*|| $this->current_page == 'esig'*/
			|| $this->current_page == 'app_online_confirm_rework'
			|| $this->current_page == 'app_done_paperless'
			|| $this->current_page == 'return_visitor'
			|| ( $this->current_page == 'ent_confirm_legal' && $this->config->bypass_esig ))
			&& ($this->current_page != 'bb_ezm_legal')
			// don't return false on the ent_confirm and ent_cs_login pages
			&& ($this->current_page != 'ent_confirm')
			&& ($this->current_page != 'ent_cs_login')
			&& ($this->current_page != 'ent_online_confirm')
			&& ($this->current_page != 'ent_online_confirm_legal')
			&& ($this->current_page != 'ent_reapply_legal')
			)
		{
			return FALSE;
		}

		$redirect = FALSE;

		// Since application_type has become so verbose, we can do a lot of this
		// checking based on that value.  We're still going to need a few things
		// below, but hopefully we can handle most of it here, now.
		$type = $this->Get_Application_Type($application_id);

		switch( $type )
		{

			case 'PENDING':
				$this->Teleweb_Override();
				// OLD PROCESS: PENDING -> AGREE -> CONFIRM
				if (!isset($this->config->online_confirmation))
				{
					if (($this->current_page != 'ent_cs_login') && ($this->current_page != 'esig') && $this->current_page != 'ent_reapply_legal')
					{

						if(!$this->Is_Ecash3($this->property_short))
						{
							$this->Refresh_Legal_Docs();
						}

						$this->next_page = 'esig';
						$redirect = TRUE;

					}

				}
				// NEW PROCESS: PENDING -> CONFIRM -> AGREE
				elseif (($this->current_page != 'ent_cs_login') && ($this->current_page != 'ent_online_confirm') && $this->current_page != 'ent_reapply_legal')
				{
					$this->application_id = $application_id;
					$this->Get_Online_Confirm_Thanks($_SESSION['blackbox']['winner']);
					$this->next_page = 'bb_thanks';

					$redirect = TRUE;

				}
				break;

			case 'COMPLETED':

				$this->Refresh_Thank_You($application_id, $_SESSION['blackbox']['winner']);
				$redirect = TRUE;
				break;

			case 'AGREED':

				if($this->current_page == 'ent_reapply_legal')
				{
					$this->next_page = 'ent_status';
					$redirect = TRUE;
				}
				elseif (!isset($this->config->online_confirmation))
				{

					if($this->current_page != 'ent_confirm' && $this->current_page != 'ent_cs_login')
					{
						$this->next_page = 'app_done_paperless';
						$redirect = TRUE;
					}

				}
				else
				{
					$this->Teleweb_Override();
					if(!empty($_SESSION['data']['ecash_sign_docs']))
					{
						//Fake this so the docs actually generate correctly
						$_SESSION['app_completed'] = TRUE;
						$redirect = FALSE;
					}
					//Check to make sure they are not hitting refresh or logging in
					elseif (($_SESSION['cs']['confirmed'] || $_SESSION['cs']['agreed'] || $this->current_page == 'ent_cs_login') &&
						!($_SESSION['cs']['agreed'] && ($this->current_page == 'ent_online_confirm' || $this->current_page == 'ent_online_confirm_legal')))
					{
						if ($_SESSION['cs']['application_id'] || is_numeric($this->collected_data['application_id']))
						{
							$this->next_page = 'ent_status';
							$redirect = FALSE;
						}
					}
					else
					{
						$this->next_page = 'ent_thankyou';
						$redirect = TRUE;
					}

				}
				break;

			case 'DISAGREED':

				// If the CSR returns to try to correct the application and the customer has already declined the loan
				// the system will return them to the esig page and allow them to change the data or sign the forms.
				if ($this->collected_data['return_visitor'])
				{
					$this->next_page = 'esig';
					$redirect = false;
				}
				elseif($this->Teleweb_Override())
				{
					$this->next_page = 'ent_online_confirm_legal';
					$redirect = FALSE;
				}
				elseif($this->current_page == 'ent_reapply_legal')
				{
					$this->next_page = 'ent_reapply_legal';
					$redirect = FALSE;
				}
				elseif (($this->current_page != 'esig') && ($this->current_page != 'ent_online_confirm_legal'))
				{
					$this->next_page = 'cust_decline';
					$redirect = TRUE;
				}

				break;

			case 'CONFIRMED':
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$olp_confirmation = $app_campaign_manager->Get_Olp_Process($application_id);
				if (!isset($this->config->online_confirmation) || (strtolower($olp_confirmation) == 'email_confirmation'))
				{
					$this->next_page = 'ent_status';
					$redirect = FALSE;
				}
				elseif ($this->current_page != 'ent_online_confirm_legal')
				{

					// redirect to the confirm_legal page
					$this->next_page = 'ent_online_confirm_legal';
					$redirect = TRUE;

					$this->Teleweb_Override();
					//check if the cs array is in the session
					if(!isset($_SESSION["cs"]["application_id"]) && $this->Get_Application_ID())
					{
						$p_short = (isset($_SESSION["config"]->bb_force_winner)) ? $_SESSION["config"]->bb_force_winner : $_SESSION["config"]->property_short;
						$this->Setup_DB($p_short);
						$ent = $this->Get_Ent_Cs($p_short);
						$cs = $ent->Get_User_Data($this->Get_Application_ID());
						$_SESSION['cs'] = $cs['cs'];

						$redirect = FALSE;
						// We need to make sure we handle this based on the marketing site confirmation type,
						//   instead of the enterprise site confirmation type [LR]
					}
					elseif ( $application_id && $this->current_page == 'ent_cs_login')
					{
						//if($this->config->use_new_process)
						//{
						$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						$olp_confirmation = $app_campaign_manager->Get_Olp_Process($application_id);
						//}
						/*else
						{
						$p_short = (isset($_SESSION["config"]->bb_force_winner)) ? $_SESSION["config"]->bb_force_winner : $_SESSION["config"]->property_short;
						$this->Setup_DB($p_short);
						$ent = $this->Get_Ent_Cs($p_short);
						$olp_confirmation = $ent->Get_Online_Confirmation_Status($application_id);
						}*/

						if (strtolower($olp_confirmation) == 'email_confirmation')
						{
						$this->next_page = 'ent_status';
						$redirect = FALSE;
						break;
						}

					}

                   			 // do preparation for rendering this page
                   			$this->Run_Next_Page_Cases();
				}
				break;
			case 'FAILED':

				$this->next_page = 'app_declined';
				$redirect = TRUE;
				break;

			case 'CONFIRMED_DISAGREED':

				$this->Teleweb_Override();

				if ($this->current_page != 'ent_confirm' && $this->current_page != 'ent_online_confirm' && $this->current_page != 'ent_cs_login')
				{
					$this->next_page = 'cust_decline';
					$redirect = TRUE;
				}
				break;

			case 'VISITOR':

				$enterprise = (isset($_SESSION['data']['enterprise']) ? TRUE : FALSE);
				$tier = (isset($_SESSION['blackbox']['tier']) ? $_SESSION['blackbox']['tier'] : NULL);
				$confirmed = isset($_SESSION['data']['sell']);

				// if we have a winner, but we're a visitor,
				// we're probably doing something wrong
				if (!is_null($tier))
				{

					if ($enterprise && ($tier != 1) && (!$confirmed))
					{
						$this->next_page = 'bb_confirm_lead';
						$redirect = TRUE;
					}
					elseif (($tier == 4) && ($this->current_page != 'bb_ezm_legal'))
					{
						$this->next_page = 'bb_ezm_legal';
						$redirect = TRUE;
					}

				}

				// Somehow we ended up here and we need to go back to the rework page
				// Most likely the customer hit refresh.. newbies
				if($_SESSION["process_rework"])
				{
					$this->next_page = "app_online_confirm_rework";
				}

				break;

		}

		return($redirect);

	}

	protected function Refresh_Legal_Docs($use = NULL)
	{

		// bring back condor docs
		$my_session['config'] = clone $_SESSION['config'];

		if (!is_array($use))
		{
			$my_session['data'] = $_SESSION['data'];
		}
		else
		{
			$my_session['data'] = $use;
		}

		$my_session['config']->legal_entity = $this->ent_prop_list[ strtoupper($_SESSION['blackbox']['winner']) ]["legal_entity"];
		$my_session['config']->site_name = $this->ent_prop_list[ strtoupper($_SESSION['blackbox']['winner']) ]["site_name"];

		// get the esig doc or write app log
		$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $my_session);

		if (!$this->esig_doc)
		{
            $this->applog->Write("app_id: ".$this->Get_Application_ID()." - Condor Preview Docs failed" );
			$this->event->Log_Event('CONDOR_PREVIEW', 'FAIL');
		}

		return;

	}

	private function Get_Application_Type( $application_id = NULL )
	{

		if( $application_id === NULL )
		{
			$application_id = $this->Get_Application_ID();
		}

		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		$results = $app_campaign_manager->Get_Application_Type($application_id);

		return $results;
	}

	public function Get_Next_Page()
    {
    	return $this->next_page;
    }

	/**
	 * @return string
	 * @desc Allows another object to grab the current page
	 *
	 */
	public function Get_Current_Page()
	{
		return $this->current_page;
	}

	/**
	 * @return Object
	 * @desc Allows another object to grab the config object
	 *
	 */
	public function Get_Config()
	{
		return $this->config;
	}

	/**
	 * @return string
	 * @desc Allows another object to grab the data validation object
	 *
	 */
	public function Get_Data_Validation()
	{
		return $this->data_validation;
	}

	/**
	 * @param $errors Array An array of errors
	 * @desc Allows another object to manipulate OLP's internal list of errors
	 */
	public function Set_Errors($errors)
	{
		if ($errors && is_array($errors))
			$this->errors = $errors;
	}

	/**
	 * @param $errors Array An array of errors
	 * @desc Allows another object to manipulate OLP's internal list of errors
	 */
	public function Add_Errors($errors)
	{
		if ($errors && is_array($errors))
		{
			foreach ($errors as $error)
			{
				$this->errors[] = $error;
			}
		}

	}

	/**
	 * @param $error String An error message
	 * @desc Allows another object to manipulate OLP's internal list of errors
	 */
	public function Add_Error($error)
	{
		if ($error)
			$this->errors[] = $error;
	}

	/**
	 * Removes bank data from the collected data and session.
	 *
	 * Of note, this should probably be done in validation, but that
	 * is handled by a shared lib which is not just OLP, so I'm advised
	 * it's OK to do it here in olp.php [GF 6988]
	 * @return NULL
	 */
	private function setNoAccount()
	{
		// Moved to here to not duplicate code since this event and stat will always be hit if this is used. - GForge #10438 [DW]
		$this->Event_Log();  // Making sure event log is hit before I hit the stat.
		// HIT THE STAT
		//ReportPro Column "No Checking or Savings Account" (no_checking_or_savings_account) Added
		Stats::Hit_Stats(
			'no_checking_or_savings_account', 
			$this->session, 
			$this->event, 
			$this->applog,  
			$this->Get_Application_ID());

		// Clearing out bank_account info
		$_SESSION['data']['dep_account'] = 'NO_ACCOUNT';
		unset($this->collected_data['income_direct_deposit']);
		unset($this->collected_data['bank_account_type']);
		unset($this->collected_data['bank_account']);
		unset($this->collected_data['bank_account_encrypted']);
		unset($this->collected_data['bank_aba']);
		unset($this->collected_data['bank_aba_encrypted']);
		unset($this->collected_data['bank_name']);
		unset($_SESSION['data']['bank_account_type']);
		unset($_SESSION['data']['bank_account']);
		unset($_SESSION['data']['bank_account_encrypted']);
		unset($_SESSION['data']['bank_aba']);
		unset($_SESSION['data']['bank_aba_encrypted']);
		unset($_SESSION['data']['bank_name']);
	}

	/**
	 * @param $page string
	 * @desc prepares data prior to processing
	 */
	private function Prepare_Collected_Data()
	{
		
		/**
		 * Temporary fix for cashwest.  They start passing in yes/no instead of TRUE/FALSE
		 * like the retards that they are. [CB] 2008-03-17
		 */
		if (!empty($this->collected_data['income_direct_deposit']))
		{
			if (strcasecmp($this->collected_data['income_direct_deposit'], 'yes') == 0)
			{
				$this->collected_data['income_direct_deposit'] = 'TRUE';
			}
			elseif (strcasecmp($this->collected_data['income_direct_deposit'], 'no') == 0)
			{
				$this->collected_data['income_direct_deposit'] = 'FALSE';
			}
		}

		//This is originally called in the constructor and should probably not have to be reinstantiated.
		//$this->crypt_object = Crypt_Singleton::Get_Instance();

		if(isset($this->collected_data['dep_account']) || isset($this->collected_data['dep_account']))
		{
			if(($this->collected_data['dep_account'] == 'NO_ACCOUNT')
			|| ($this->collected_data['income_direct_deposit'] == 'NO_ACCOUNT'))
			{
				// remove session/collected info relating to bank [GF 6988]
				$this->setNoAccount();
			}
		}
		//********************************************* 
		// GForge 6672 - [AuMa]
		//
		// This is the area where we interpret the data 
		// that we've collected
		// - First up Employment length/date_of_hire
		//********************************************* 
		
		//********************************************* 
		// The current problem is that the select box
		// does not remember what was selected
		// because we replace what was selected with 
		// values that we need in other places.
		// So we'll change the name of the drop down
		// box on the form and use it if it's available
		//********************************************* 
		if(isset($this->collected_data['employer_length_select']) &&
			$this->collected_data['employer_length_select'] != '')
		{
			$this->collected_data['employer_length'] = $this->collected_data['employer_length_select'];
		}
		//********************************************* 
		// We changed how employment length is sent over
		// so now we have to change how that calculates 
		// this->collected_data['date_of_hire']
		//********************************************* 
		if(isset($this->collected_data['employer_length']))
		{
			switch (trim($this->collected_data['employer_length']))
			{
				case '':
				//********************************************* 
				// if the employer_length field is left blank
				// we should still make the user put something in
				//********************************************* 
				break;
				case 'F': 	
					//********************************************* 
					// This is a Form False - in case we don't have 
					// all of the forms converted over, it will still 
					// select a date for them but it will assume they 
					// are employed
					// This could be a flaw that we might have to fix
					//********************************************* 
				case 'FALSE': 
					//********************************************* 
					// SOAP Vendors can pass over 'FALSE' for unemployed 
					// (9 doesn't make sense without context)
					//********************************************* 
				case '9':
					// not employed
					$this->collected_data['employer_length'] = 'FALSE';
					$employer_length = 'FALSE';
					//********************************************* 
					// we're setting the above variable in case the
					// soap vendor send over false, everything else
					// should work properly from there
					//********************************************* 
				break;
				case 'TRUE':
				case 'T':
				case '4':
					$this->collected_data['employer_length'] = '4';
					//********************************************* 
					// This is an intentional fall through - no
					// break on purpose
					//********************************************* 
				default:
					$employer_length = $this->collected_data['employer_length'];
					//*********************************************
					// We are setting the variable back to TRUE
					// for legacy reasons (blackbox vendors)
					//********************************************* 
					$this->collected_data['employer_length'] = 'TRUE';
				break;
					//********************************************* 
					// we're setting the above variables because
					// the employer length might contain the old
					// information from the previous forms types
					// so this we will stick this information in
					// there for now so they can work properly
					//********************************************* 
			}
			$this->collected_data['date_of_hire'] = $this->evaluateTimeToken(
																$employer_length);
			//********************************************* 
			// End date of hire
			// GForge 6672 - [AuMa]
			// Next up: residence_start_date
			//********************************************* 
			if (isset($this->collected_data['residence_length']))
			{
				// our marketing sites collect this data
				$this->collected_data['residence_start_date'] = $this->evaluateTimeToken( 
																$this->collected_data['residence_length'] );
			} 
			else 
			{
				// soap leads collect this data
				$this->collected_data['residence_start_date'] = $this->evaluateTimeToken( 
																$this->collected_data['residence_start_date'] );
			}
			//********************************************* 
			// End Residence_start_date
			//********************************************* 

			//********************************************* 
			// GForge #10340 [AuMa]
			// Add Months_at_employer, months_at_residence
			// to data collection
			// - so we don't have to calc it everytime
			//********************************************* 
			if($this->collected_data['residence_start_date'] != '' )
			{
				$this->collected_data['months_at_residence'] = $this->getMonths($this->collected_data['residence_start_date']);

			}
			else
			{
				$this->collected_data['months_at_residence'] = 0;
			}
		
			$this->collected_data['months_at_employer'] = $this->getMonths($this->collected_data['date_of_hire']);

		//********************************************* 
		// End GForge #10340
		//********************************************* 
		} // Added this during GForge #10340

		// fields that need to be assembled
		$field = array();
		$field['dob'] = array("date_dob_m", "date_dob_d","date_dob_y");
		$field['pay_date1'] = array("income_date1_y", "income_date1_m", "income_date1_d");
		$field["pay_date2"] = array("income_date2_y", "income_date2_m", "income_date2_d");

		// paperless confirmation paydate 3/4 request assembly
		$field["ent_pay_date3"] = array("income_date3_y", "income_date3_m", "income_date3_d");
		$field["ent_pay_date4"] = array("income_date4_y", "income_date4_m", "income_date4_d");

		$field['social_security_number'] = array("ssn_part_1", "ssn_part_2", "ssn_part_3");
		$glue = array("dob" => "/", "pay_date1" => "-", "pay_date2" => "-","ent_pay_date3" => "-","ent_pay_date4" => "-");

		//Make sure dob format is correct
		if(isset($this->collected_data["date_dob_d"]) && isset($this->collected_data["date_dob_d"]))
		{
	        $this->collected_data["date_dob_d"] = str_pad($this->collected_data["date_dob_d"], 2, "0", STR_PAD_LEFT);
	        $this->collected_data["date_dob_m"] = str_pad($this->collected_data["date_dob_m"], 2, "0", STR_PAD_LEFT);
		}

        //Put in outdated fields if they aren't there
        if(!isset($this->collected_data["income_stream"]) && isset($this->collected_data["employer_length"]))
        {
            $this->collected_data["income_stream"] = $this->collected_data["employer_length"];
        }
        if(!isset($this->collected_data["monthly_1200"]) && is_numeric($this->collected_data["income_monthly_net"]) &&
           $this->collected_data["income_monthly_net"] >= 1000)
        {
            $this->collected_data["monthly_1200"] = "TRUE";
        }

		// Check for and assemble any data that may be spread across multiple fields.
		$assembled = Data_Preparation::Assemble_Data($this->collected_data, $field, $glue);


		if (isset($this->collected_data['dep_account']))
		{
			switch($this->collected_data['dep_account'])
			{
				case 'DD_SAVINGS':
					$this->collected_data['income_direct_deposit'] = 'TRUE';
					$this->collected_data['bank_account_type'] = 'SAVINGS';
				break;

				case 'DD_CHECKING':
					$this->collected_data['income_direct_deposit'] = 'TRUE';
					$this->collected_data['bank_account_type'] = 'CHECKING';
				break;

				case 'OTHER':
				case 'FALSE':
					$this->collected_data['income_direct_deposit'] = 'FALSE';
					$this->collected_data['bank_account_type'] = 'CHECKING';
				break;
			}
		}

		if (!isset($this->collected_data['dep_account'])
			&& !empty($this->collected_data['income_direct_deposit']) 
			&& !empty($this->collected_data['bank_account_type']))
		{
			if($this->collected_data['income_direct_deposit'] == 'TRUE')
			{
				$this->collected_data['dep_account'] = ($this->collected_data['bank_account_type'] == 'SAVINGS') ? 'DD_SAVINGS' : 'DD_CHECKING';
			}
			else
			{
				$this->collected_data['dep_account'] = ($this->collected_data['bank_account_type'] == 'CHECKING') ? 'FALSE' : 'OTHER';
			}
		}

		//CLK adds a third option to income type for military, but we'll just set military to true instead.
		if(!empty($this->collected_data['income_type']) && $this->collected_data['income_type'] == 'MILITARY')
		{
			$this->collected_data['military'] = 'TRUE';
			$this->collected_data['income_type'] = 'EMPLOYMENT';
		}

		//********************************************* 
		// We have to rename a few fields to get past the rules for uk and blackbox [AuMa]
		// GForge 6011 
		//********************************************* 
		$uk_site_type_array = array
		( 
			'blackbox.uk.oc.two.page',
			'blackbox.uk.online.confirmation'
		);

		if(in_array(SiteConfig::getInstance()->site_type, $uk_site_type_array))
		{
			if(isset($this->collected_data['nin']))
			{
				//********************************************* 
				// ssn is not collected for uk sites
				// so we can overwrite the data that 
				// exists there, because we don't 
				// care about it. [AuMa]
				//********************************************* 
				$ssn = $this->collected_data['nin'];
				$this->collected_data['social_security_number_encrypted'] = $this->crypt_object->encrypt($ssn);
			}
			if(isset($this->collected_data['home_type']))
			{
				//********************************************* 
				// just in case the form is goofy
				//********************************************* 
				$this->collected_data['residence_type'] = $this->collected_data['home_type'];
			}
			if(isset($this->collected_data['sort_code']))
			{
				//********************************************* 
				// just in case the form is goofy
				//********************************************* 
				$this->collected_data['bank_aba'] = $this->collected_data['sort_code'];
				$this->collected_data['bank_aba_encrypted'] = $this->crypt_object->encrypt($this->collected_data['bank_aba']);
			}
		
		}

		//********************************************* 
		// End changes for GForge 6011 [AuMa]
		//*********************************************

		// If any data was assembled add it to the collected data array.
		if( count($assembled) )
		{
			$this->collected_data = array_merge($this->collected_data, $assembled);
		}

	}

	/**
	 * @return errors array
	 * @desc Validate/normalize current submitted data
	 *
	 */
	private function Check_And_Collect()
	{
		$current_page = $this->Get_Current_Page();
		$config = $this->Get_Config();

		$pages = $config->site_type_obj->page_order;
		$pages[] = 'ent_cs_confirm_start';

		$data_validation = $this->Get_Data_Validation();

		list($normalized_data, $errors) = Data_Preparation::Validate_Data($config, $this->collected_data, $data_validation, $config->site_type_obj->pages, $current_page,$this->applog);

		$this->Remap_Errors($errors);

		if(empty($errors) && $this->title_loan)
		{
			$this->Setup_DB($this->config->property_short);

			require_once('ecash_common/nada/NADA.php');
			$nada = new NADA_API($this->ldb_pdo);

			if(!empty($normalized_data['vehicle_vin']) && strlen($normalized_data['vehicle_vin']) > 8)
			{
				$vehicle = $nada->getVehicleByVin($normalized_data['vehicle_vin']);

				if(!empty($vehicle))
				{
					$value = $vehicle->value;
					$normalized_data['vehicle_make'] = $vehicle->make;
					$normalized_data['vehicle_model'] = $vehicle->model;
					$normalized_data['vehicle_series'] = $vehicle->series;
					$normalized_data['vehicle_style'] = $vehicle->body;
					$normalized_data['vehicle_year'] = $vehicle->vic_year;
				}
			}
			else
			{
				$value = $nada->getValueFromDescription(
					$normalized_data['vehicle_make'],
					$normalized_data['vehicle_model'],
					$normalized_data['vehicle_series'],
					$normalized_data['vehicle_style'],
					$normalized_data['vehicle_year']
				);
			}

			if(!empty($value))
			{
				$normalized_data['vehicle_value'] = $value;
			}
			else
			{
				$normalized_data['vehicle_value'] = '0.00';
				$errors['vehicle_value'] = 'vehicle_value';
			}
		}

		// check if our site config uses the CAPTCHA and this page is our cost action
		if (isset($this->config->display_captcha) && $this->Is_Cost_Action($current_page) || $this->soap_oc)
		{

			// shameless hack: we don't want to create an application
			// before we've run validation, but we need to Create_Application
			// will give us $this->application_id
			if ($this->Get_Application_ID())
			{
				$this->application_id = $this->Get_Application_ID();
				$this->Event_Log(TRUE);
				//got to have an application id before we can do this
				if(isset($_SESSION['aba_call_result']))
				{
					if(isset($_SESSION['aba_call_result']['dataxerror']))
					{
						$this->event->Log_Event('DATAX_ABA','ERROR');
						//$this->event->Log_Event('LIST_VERIFY_BANK_ABA_1','verify');
					}
					elseif(strtoupper($_SESSION['aba_call_result']['valid']) == 'VERIFY')
					{
						$this->event->Log_Event('DATAX_ABA','VERIFY');
					}
					/*else
					{
						$res = $_SESSION['aba_call_result']['valid'] == true ? 'PASS' : 'FAIL';
						$this->event->Log_Event('DATAX_ABA',$res);
					}
					*/
				}
			}
			// validate CAPTCHA
			if ((!isset($_SESSION['captcha'])) || strcasecmp($this->collected_data['captcha_response'], trim($_SESSION['captcha'])))
			{
				if ($this->Get_Application_ID()) $this->event->Log_Event('CAPTCHA', 'FAIL');
				$errors[] = 'captcha_response';
			}
			else
			{
				if ($this->Get_Application_ID()) $this->event->Log_Event('CAPTCHA', 'PASS');
			}

		}

		// Encrypt data after it is normalized
		$encrypt_fields = array(
			'social_security_number' => 'social_security_number_encrypted',
			'dob' => 'dob_encrypted',
			'bank_aba' => 'bank_aba_encrypted',
			'bank_account' => 'bank_account_encrypted',
		);

		foreach ($encrypt_fields AS $field_normal => $field_encrypted)
		{
			if (isset($normalized_data[$field_normal]) && strlen($normalized_data[$field_normal]))
			{
				$normalized_data[$field_encrypted] = $this->crypt_object->encrypt($normalized_data[$field_normal]);
			}
		}

		// merge normalized data into session data and vice versa
		if ($_SESSION['data'] && $normalized_data)
		{
			$normalized_data = $_SESSION['data'] = array_merge($_SESSION['data'], $normalized_data);
		}
		else
		{
			$_SESSION['data'] = $normalized_data;
		}

		if ($errors && sizeof($errors))
		{
			$this->Add_Errors($errors);
		}
		if ($normalized_data && sizeof($normalized_data))
		{
			$this->normalized_data = $normalized_data;
		}

		// needs to be changed, only save normalized data
		$this->Set_Session_Data('data', $this->normalized_data);

	}

	/**
	 * @return bool
	 * @desc Generates arguments and calls Check_Page_Order::Check_Page_Order()
	 *
	 */
	private function Check_Page_Order()
	{

		$current_page = $this->Get_Current_Page();
		$config = $this->Get_Config();

		$errors = Check_Page_Order::Check_Page_Order($config, $current_page, $_SESSION['page_trace']);

		if ($errors && is_array($errors) && sizeof($errors))
		{

			if (in_array('invalid_session', $errors))
			{

				// set current page to the first page in the
				// page_order and return the invalid session error
				$this->current_page = $config->site_type_obj->page_order[0];
				$this->Page_Trace('invalid_session');
				$this->Set_Errors(NULL);

				$errors = array('invalid_session');

			}

			$this->Add_Errors($errors);

		}

	}

	private function Page_Override()
	{
        if(isset($this->collected_data["coreg_site_url"]))
        {
            $this->collected_data['page'] = $this->current_page = "app_coreg";
        }

        if(isset($this->collected_data['page']))
        {
    		switch ($this->collected_data['page'])
    		{
    			case 'app_2part_page01':
    			case 'app_allinone':
    				if(!$this->Get_Application_ID() && $this->config->site_type == 'ecash_yellowbook')
    				{
    					$data_validation = $this->Get_Data_Validation();
						list($normalized_data, $errors) = Data_Preparation::Validate_Data($this->config, $this->collected_data, $data_validation, $this->config->site_type_obj->pages, $this->collected_data['page'], $this->applog);

    					$this->Create_Application();
    				}

					// Mantis #10788 - Added in this check for any No selected on Prequal Radio buttons  [RV]
					if($this->config->site_type == 'ecash_yellowbook' && $this->collected_data['qualify'] == '0')
					{
						$this->Event_Log();

						if(strcasecmp($this->collected_data['has_checking_account'], 'no') == 0)
							Stats::Hit_Stats('has_checking_account_no', $this->session, $this->event, $this->applog, $this->application_id);
						if(strcasecmp($this->collected_data['has_email_address'], 'no') == 0)
							Stats::Hit_Stats('has_email_address_no', $this->session, $this->event, $this->applog, $this->application_id);

						Stats::Hit_Stats('clk_no_first_page', $this->session, $this->event, $this->applog, $this->application_id);

						$this->eds_page = array('content' => '<p><br>We are sorry but you do not meet our minimum requirements. Please call back when your situation changes.  Thank you for calling!</p>',
												'type' => 'html' ,
												'action' => 'standard');

						$this->next_page = "bb_extra";
					}
					elseif($this->config->site_type == 'ecash_yellowbook' && $this->collected_data['qualify'] == '1')
					{
						if(empty($this->collected_data['email_primary']))
						{
							$this->Add_Error('email_primary');
							$this->next_page = "app_2part_page01";
						}
						elseif($this->application_id = $this->Get_Application_ID())
						{
							$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
							$app_campaign_manager->Insert_Application($this->application_id, $this->collected_data);
							$this->ECYP_Send_Email($this->application_id, $this->collected_data);

							$content = "<p>Thank you for your application.  You will receive an email soon with instructions on how to complete the loan application process.";
							$this->eds_page = array('content' => $content, 'type' => 'html' , 'action' => 'standard');
							$this->page = $_SESSION['data']['page']  = 'bb_extra';
							$this->next_page = 'bb_extra';
						}
						else 
						{
							$this->next_page = "app_2part_page01";
						}
					}
					elseif($this->config->site_type == 'blackbox.one.page.yellowpage' && $this->collected_data['qualify'] == '0')
					{
						$this->Event_Log();

						// Hit a stat for each No they selected	[RV]
						if(strcasecmp($this->collected_data['18_or_over'], 'no') == 0)
							Stats::Hit_Stats('18_or_over_no', $this->session, $this->event, $this->applog, $this->application_id);
						if(strcasecmp($this->collected_data['800_monthly_income'], 'no') == 0)
							Stats::Hit_Stats('800_monthly_income_no', $this->session, $this->event, $this->applog, $this->application_id);

						Stats::Hit_Stats('clk_no_first_page_acac', $this->session, $this->event, $this->applog, $this->application_id);

						$this->eds_page = array('content' => '<p><br>We are sorry but you do not meet our minimum requirements. Please call back when your situation changes.  Thank you for calling!</p>',
												'type' => 'html' ,
												'action' => 'standard');

						$this->next_page = "bb_extra";
					}

    				break;

    			case 'int_redirect':
					//Marketing Site
					if(isset($this->collected_data['redirect_link']))
					{
						$this->collected_data['redirect'] = $this->collected_data['redirect_link'];
					}
					elseif(isset($this->collected_data['unique_id']))
					{
						if(strtolower(BFW_MODE)!="live" || ($_SESSION["data"]["redirect_start_time"] + 60) > time()) // 60 second time limit
						{
							$this->collected_data['redirect'] = $_SESSION["data"]["redirected_to"];
						}
						else //Redirect expired, send them to easycashcrew.com
						{
							$this->collected_data['redirect'] = 'https://easycashcrew.com/?force_new_session';
						}
					}

    				$application_id = $this->Get_Application_ID();

    				if(is_numeric($application_id))
    				{
    					$target = $_SESSION['blackbox']['winner'];


    					// Hit event that we redirected
    					$this->Event_Log();
    					$this->event->Log_Event(
    						'REDIRECT_PAGE',
    						'PASS',
    						$target,
    						$application_id
    					);

    					/*
    						This should probably be looked at. Since we're hitting a stat
    						now, we could probably get rid of the event. We'll just need to
    						update any reports that use the event to use the stat instead.
    					*/
    					Stats::Hit_Stats(
    						'redirect_'.$target,
    						$this->session,
    						$this->event,
    						$this->applog,
    						$application_id,
    						NULL,
    						TRUE
    					);
    				}
    				else
    				{
    					$this->Applog_Write('redirect log attempt with no session data. page: - ' .$this->collected_data['page'] . 'session: ' . session_id());
    				}
    				break;

    			case 'coreg_return':
                    //Make sure to hit prequal stats
                    $this->BlackBox_Prequal();

    				$this->collected_data = array_merge($_SESSION['data'], $this->collected_data);

    				// Find the page we should have
    				$site_pages = get_object_vars($this->config->site_type_obj->pages);
    				$site_page_keys = array_keys($site_pages);
    				$found_page = $site_page_keys[0];

    				// Set it
    				$this->collected_data['page'] = $found_page;
    				$this->current_page = $found_page;

    				$this->override_errors = 'default';

    				// We don't want to run the initial coreg again.
    				$_SESSION['coreg'] = '1';

    				// We also don't want it to ouput xml anymore.
    				unset($_SESSION['data']['coreg_xml']);
    				unset($this->collected_data['coreg_xml']);

				// This was removed from Process_Coreg_Application()
				if (!$this->event)
				{
  					$this->Event_Log();
				}
				Stats::Hit_Stats('visitor', $this->session, $this->event, $this->applog, $this->Get_Application_ID());

    				// Reset the config, in case it came from a different site
    				unset($_SESSION['config']);

    				break;

    			// Trick FCNA return into going to the first page if there are validation failures
    			case 'fcna_return':
    				// Merge data
    				$this->collected_data = array_merge($_SESSION['data'], $this->collected_data);

    				// Find the page we should have
    				$site_pages = get_object_vars($this->config->site_type_obj->pages);
    				$site_page_keys = array_keys($site_pages);
    				$found_page = $site_page_keys[0];

    				// Set it
    				$this->collected_data['page'] = $found_page;
    				$this->current_page = $found_page;

    				switch( $found_page )
    				{
    					case 'app_2part_page02':
    						Stats::Hit_Stats( 'prequal', $this->session, $this->event, $this->applog,  $this->Get_Application_ID() );
    						break;
    				}

    				// Make sure fcna_email flag doesn't try to fire again
    				$this->collected_data['fcna_email'] = 'fcna_return';
    			break;

    			// Trick return_visitor variables
    			case 'return_visitor':

    				// Merge data
    				if( $_SESSION['data'] )
    				{
    					$this->collected_data = array_merge($_SESSION['data'], $this->collected_data);
    				}
    				
					// Hit a stat saying that a visitor returned - per gForge 6288
					$this->Event_Log(); // For whatever reason, this wasn't instantiated already
					
					Stats::Hit_Stats(
						'return_visitor',
						$this->session,
						$this->event,
						$this->applog,
						$this->Get_Application_ID()
					);

    				$return_handler = new Return_Handler($this->config, $_SESSION['page_trace'], $this->applog);
    				$found_page = $return_handler->Get_Return_Page($_SESSION['app_completed'], $this->collected_data['unique_id'] );
    				// if the user dropped from a react, verify that the user actually confirmed b-day/ssn
    				if ($found_page=='ent_cs_confirm_react' && $_SESSION['react']['transaction_id']>0)
    				{
    					$found_page='ent_reapply';
    				}

    				$this->collected_data["page"] = $found_page;
    				$this->collected_data["return_visitor"] = 1;
    				$_SESSION["return_visitor"] = 1;



    			break;

    			// need this for cust service - bypass errors if they decline
    			case 'ent_confirm':
    				if ( $this->collected_data['legal_deny'] || preg_match("/decline/i", $this->collected_data['submit']) )
    				{
    					$this->declined = "cust_decline";
    				}
    				// back on the confirmation page from a link to page=ent_confirm, override errors
    				elseif (empty($this->collected_data['submit']))
    				{
    					$this->override_errors = "ent_confirm";
    				}
    			break;

    			case 'ent_online_confirm':

    				// They are declining...
    				if($this->collected_data['submit'] == 'Cancel')
    				{
    					$this->declined = 'cust_decline';
    				}
    				elseif (empty($this->collected_data['submit']))
    				{
    					$this->override_errors = "ent_online_confirm";
    				}


    				if(isset($_SESSION['data']['ref_count'])
    					&& intval($_SESSION['data']['ref_count']) < 2
    					&& $this->Is_CLK()
    				)
    				{
						//Mantis #11118 fix. New enterprise sites
						//do this on a different page than old ones.
    					if($this->ent_prop_list[$this->property_short]['new_ent'] === true)
    					{
    						$page = 'app_ent_page04';
    					}
    					else
    					{
    						$page ='app_2part_page02';
    					}
    					for($num = 1; $num <= 2; $num++)
    					{
    						$this->config->site_type_obj->pages->ent_online_confirm->{"ref_0{$num}_name_full"} =
    								$this->config->site_type_obj->pages->$page->{"ref_0{$num}_name_full"};
							$this->config->site_type_obj->pages->ent_online_confirm->{"ref_0{$num}_phone_home"} =
    								$this->config->site_type_obj->pages->$page->{"ref_0{$num}_phone_home"};
							$this->config->site_type_obj->pages->ent_online_confirm->{"ref_0{$num}_relationship"} =
    								$this->config->site_type_obj->pages->$page->{"ref_0{$num}_relationship"};
    					}
    				}

    				break;

    			// cust service, don't show errors if they declined and now want back in
    			case 'ent_online_confirm_legal':
    			case 'ent_confirm_legal':
    				if ( $_SESSION['data']['legal_deny'] && empty($this->collected_data['legal_agree']) )
    				{
    					if(!$this->config->online_confirmation)
    					{
    						$this->override_errors = "ent_confirm_legal";
    						$this->next_page = "ent_confirm_legal";
    					}
    					else
    					{
    						$this->override_errors = "ent_online_confirm_legal";
    						$this->next_page = "ent_online_confirm_legal";
    					}
    				}
    				break;
    			// sig page, don't show errors if they declined and now want back in
    			case 'esig':
    				if ( $_SESSION['data']['legal_deny'] && empty($this->collected_data['legal_agree']) )
    				{
    					$this->override_errors = "esig";
    					$this->next_page = "esig";
    				}
    				// allow the customer to decline on soap sites
    				elseif ( preg_match('/soap/i', $this->config->site_type) && strtolower($this->collected_data['legal_agree']) != 'true' && !empty($this->collected_data['legal_agree']))
    				{
    					$this->declined = 'cust_decline';
    			 		$this->next_page = 'cust_decline';

    				}

    			break;

    			// reprint_docs for ecash; need to override errors on page
    			case 'reprint_docs':
    				$this->override_errors = 'reprint_docs';
    				$this->next_page = "preview_docs";
    			break;
    			//bypass the esig page if bypass flag is set - LR
    			case 'app_2part_page02':
    			case 'app_1part_noesig_page02':
    			if ($this->config->bypass_esig == TRUE)
    			{
    					$this->bypass_esig = "app_done_paperless";
    			}
    			break;


    			case 'info_exitpop':

	    			if(isset($this->collected_data['exit_pop']))
	    			{
	    				if($this->collected_data['exit_pop'] == 'agree')
	    				{

		    				$this->Event_Log();
		    				Stats::Hit_Stats('exit_agree', $this->session, $this->event, $this->applog, $this->Get_Application_ID());

		    				$this->client_state['rework_exit_agree'] = TRUE;

		    				//Set us back to app_online_confirm_rework and post the original data
		    				//This way, we can pretend they just hit the resubmit same data button
		    				//and process the app from there.
	    					$this->collected_data['page'] = 'app_online_confirm_rework';

							$this->collected_data["name_first"] 	= $_SESSION["data"]["orig_name_first"];
							$this->collected_data["name_last"] 		= $_SESSION["data"]["orig_name_last"];
							$this->collected_data["home_street"]	= $_SESSION["data"]["orig_home_street"];
							$this->collected_data["home_city"]		= $_SESSION["data"]["orig_home_city"];
							$this->collected_data["home_state"]		= $_SESSION["data"]["orig_home_state"];
							$this->collected_data["home_zip"]		= $_SESSION["data"]["orig_home_zip"];
							$this->collected_data['phone_home']		= $_SESSION['data']['orig_phone_home'];
							$this->collected_data["ssn_part_1"]		= $_SESSION["data"]["orig_ssn_part_1"];
							$this->collected_data["ssn_part_2"]		= $_SESSION["data"]["orig_ssn_part_2"];
							$this->collected_data["ssn_part_3"]		= $_SESSION["data"]["orig_ssn_part_3"];
							$this->collected_data["date_dob_y"]		= $_SESSION["data"]["orig_date_dob_y"];
							$this->collected_data["date_dob_m"]		= $_SESSION["data"]["orig_date_dob_m"];
							$this->collected_data["date_dob_d"] 	= $_SESSION["data"]["orig_date_dob_d"];
							$this->collected_data["state_id_number"]= $_SESSION["data"]["orig_state_id_number"];
							$this->collected_data["state_issued_id"]= $_SESSION["data"]["orig_state_issued_id"];
							$this->collected_data['social_security_number'] = $_SESSION['data']['orig_ssn_part_1'] . $_SESSION['data']['orig_ssn_part_2'] . $_SESSION['data']['orig_ssn_part_3'];
							$this->collected_data['dob'] = $_SESSION['data']['orig_date_dob_m'] . '/' . $_SESSION['data']['orig_date_dob_d'] . '/' . $_SESSION['data']['orig_date_dob_y'];
							$this->collected_data['rework_button_copy'] = 'TRUE';
	    				}
	    				elseif($this->collected_data['exit_pop'] == 'disagree')
	    				{
	    					$this->client_state['rework_exit_agree'] = FALSE;
	    				}
    				}
    				elseif(!isset($_SESSION['process_rework']))
    				{
    					//If they didn't come in through rework somehow, let's shove 'em back to the first page.
    					$this->next_page = $this->config->site_type_obj->page_order[0];
    				}

    			break;


    			case 'ent_reapply_legal':
    				if($_SESSION['data']['legal_deny'] && empty($this->collected_data['legal_agree']))
    				{
    					$this->override_errors = 'ent_reapply_legal';
    					$this->next_page = 'ent_reapply_legal';
    				}
    			break;

				case 'ent_payment_opts':
				case 'ent_payment_opts_submitted':
    			case 'ent_contact_us':
    			case 'ent_docs':
				case 'ent_profile':

			    	//Hack to the change the processing after the form pages are submitted!
    				if(isset($this->collected_data['submit']))
    				{
    					$this->collected_data['ent_status_override'] = $this->collected_data['page'] . '_submitted';
    				}
    				elseif(isset($this->collected_data['submit_esig']))
    				{
    					$this->collected_data['ent_status_override'] = $this->collected_data['page'] . '_esig_submitted';
    				}
    				elseif(isset($this->collected_data['cancel_esig']))
    				{
    					$this->collected_data['ent_status_override'] = $this->collected_data['page'] . '_esig_canceled';
    				}
    				else
    				{
    					if(in_array($this->collected_data['page'], array('ent_contact_us', 'ent_profile')))
    					{
    						$this->override_errors = $this->collected_data['page'];
    					}

    					$this->collected_data['ent_status_override'] = $this->collected_data['page'];
    				}

    				if($this->collected_data['page'] == 'ent_docs')
    				{
    					unset($_SESSION['condor_data']);
    				}

	    		break;

    			case 'ent_balance':
    			case 'ent_payment_history':
    			case 'ent_next_payment':

    				//Now we count the page passed in as an 'override' and set
    				//the real page to ent_status.
					$this->collected_data['ent_status_override'] = $this->collected_data['page'];
					$this->collected_data['page'] = $this->current_page = 'ent_status';

    			break;

    			case 'ent_status':

    				unset($_SESSION['data']['ent_status_override']);

    			break;

				case 'second_loan':

					$application_id = base64_decode($this->collected_data['application_id']);

					//Make sure we have a valid app_id...
					if(is_numeric($application_id))
					{
						$data = $this->Get_App_Data_From_OLP($application_id);

						//Then make sure they actually have valid data...
						if(!empty($data))
						{
							//And even though we set force_new_session, let's just make sure to get rid of all
							//this extra crap so it doesn't mess with anything.
							unset($_SESSION['data'], $_SESSION['cs'],
								$this->application_id, $_SESSION['application_id'],
								$this->collected_data['application_id'], $_SESSION['transaction_id']);

							$this->collected_data = array_merge($this->collected_data, $data);

							//Then we'll find who they originally sold to and exclude them from
							//being sold to this time around.
							$query = "SELECT property_short FROM application INNER JOIN target USING (target_id) WHERE application_id = {$application_id}";
							$result = $this->sql->Query($this->database, $query);

							$winner = $this->sql->Fetch_Column($result, 0);

							if(empty($this->config->excluded_targets))
							{
								$this->config->excluded_targets = $winner;
							}
							else
							{
								$this->config->excluded_targets .= ",$winner";
							}

							//Make sure we have a new app
							$this->Create_Application();
							$this->Update_Campaign_Info($this->application_id);

							$this->Event_Log();
							$this->event->Log_Event('SECOND_LOAN', EVENT_PASS);
							
							// Fix for redirect links
							$_SESSION['data']['unique_id'] = session_id();

							//Set next_page to bb_thanks so it'll process all the blackbox stuff
							$this->current_page = 'second_loan';
							$this->next_page = 'bb_thanks';
						}
						else
						{
							$this->current_page = 'try_again_v2';
							$this->next_page = 'try_again_v2';
						}
					}
					else
					{
						$this->current_page = 'try_again_v2';
						$this->next_page = 'try_again_v2';
					}

    			break;
            case 'callcenter_rerun':
					// Todo: At some point we should make sure that the user only clicks one time
					// Todo: let them know that this application has already been completed
					$application_id = base64_decode($this->collected_data['call_center_id']);

					//Make sure we have a valid app_id...
					if(is_numeric($application_id))
					{
						$data = $this->Get_App_Data_From_OLP($application_id);
						//Then make sure they actually have valid data...
						if(!empty($data))
						{
							//And even though we set force_new_session, let's just make sure to get rid of all
							//this extra crap so it doesn't mess with anything.

							unset($_SESSION['data'], $_SESSION['cs'],
								$this->application_id, $_SESSION['application_id'],
								$this->collected_data['application_id'], $_SESSION['transaction_id']);

							$this->collected_data = array_merge($this->collected_data, $data);

							//Make sure we have a new app
							$this->Create_Application();
							$this->Update_Campaign_Info($this->application_id);

							$this->Event_Log();
							$this->event->Log_Event('CALLCENTER_RERUN', EVENT_PASS);

							// Fix for redirect links
							$_SESSION['data']['unique_id'] = session_id();
							
							//Set next_page to bb_thanks so it'll process all the blackbox stuff
							$this->current_page = 'callcenter_rerun';
							$this->next_page = 'bb_thanks';
						}
						else
						{
							$this->current_page = 'try_again_v2';
							$this->next_page = 'try_again_v2';
						}
					}
					else
					{
						$this->current_page = 'try_again_v2';
						$this->next_page = 'try_again_v2';
					}

            break;


				case 'bb_option_email':

					$application_id = base64_decode($this->collected_data['bb_option']);

					//Make sure we have a valid app_id...
					if(is_numeric($application_id))
					{
						$data = $this->Get_App_Data_From_OLP($application_id);

						//Then make sure they actually have valid data...
						if(!empty($data))
						{
							//And even though we set force_new_session, let's just make sure to get rid of all
							//this extra crap so it doesn't mess with anything.
							unset($_SESSION['data'], $_SESSION['cs'],
								$this->application_id, $_SESSION['application_id'],
								$this->collected_data['application_id'], $_SESSION['transaction_id']);

							$this->collected_data = array_merge($this->collected_data, $data);

							$this->config->limits->accept_level = 2; // Go Directly to Blackbox vendors (skip CLK vendors)

							//Make sure we have a new app
							$this->Create_Application();
							$this->Update_Campaign_Info($this->application_id);

							$this->Event_Log();
							$this->event->Log_Event('BB_OPTION_EMAIL', EVENT_PASS);

							// Fix for redirect links
							$_SESSION['data']['unique_id'] = session_id();
							
							//Set next_page to bb_thanks so it'll process all the blackbox stuff
							$this->current_page = 'bb_option_email';
							$this->next_page = 'bb_thanks';
						}
						else
						{
							$this->current_page = 'try_again_v2';
							$this->next_page = 'try_again_v2';
						}
					}
					else
					{
						$this->current_page = 'try_again_v2';
						$this->next_page = 'try_again_v2';
					}

    			break;
				case 'return_app':
					// We can only forward the user to the login page from Page_Override, but any
					// errors set here get immediately erased. Thus, we need to call it twice:
					// once from Page_Override, and once from Run_Current_Page_Cases
					$this->Return_To_Application();
				break;
    		}
        }
	}

	/**
	 * Display and/or process the "return" page.
	 * If 'application_id' & 'ssn_part_3' are both provided then it will attempt to log the user in and
	 * redirect them to the page they were last at.
	 */
	private function Return_To_Application()
	{
		static $stored_errors = array();

		if ($stored_errors)
		{
			$this->Add_Errors($stored_errors);
			return;
		}
		if (!empty($this->collected_data['promo_id']) &&
			is_numeric($this->collected_data['promo_id']))
		{
			$promo_id = $this->collected_data['promo_id'];
		}
		else
		{
			$promo_id = 10000;
		}

		// submitted form
		// If both ssn_part_3 && application_id are supplied OR form_submission, then consider the form
		// submitted. By doing this we can allow regular links to forward directly into the
		// application, by-passing manual input.
		if (isset($this->collected_data['form_submission']) ||
			(!empty($this->collected_data['ssn_part_3']) &&
				!empty($this->collected_data['application_id'])))
		{
			// If either part of the main form are empty then add an error to the stack
			if (empty($this->collected_data['ssn_part_3']))
			{
				$this->errors[] = 'ssn_part_3';
			}
			if (empty($this->collected_data['application_id']) ||
				!is_numeric($this->collected_data['application_id']))
			{
				$this->errors[] = 'application_id';
			}

			if (empty($this->errors))
			{
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$personal_information = $app_campaign_manager->Get_Personal_Info($this->collected_data['application_id']);
				if (!$personal_information ||
					substr($personal_information['social_security_number'], 5, 4) !== $this->collected_data['ssn_part_3'])
				{
					$this->errors[] = 'application_id_compare';
				}
				else
				{
					$this->config->promo_id = $promo_id;
					$this->normalized_data['promo_override'] = 1;
					$this->collected_data['page'] = $this->normalized_data['page'] = 'ent_cs_login';

					return;
				}
			}
		}

		// If we're running from Page_Override then these will get overridden. We need to store
		// them until we get called again from Run_Current_Page_Cases.
		if ($this->errors)
		{
			$stored_errors = $this->errors;
		}

		$_SESSION['data']['promo_id'] = $promo_id;
	}

	private function Update_Current_Application($target)
	{
		if(empty($this->application_id))
        {
        	$this->application_id = $this->Get_Application_ID();
        }

        // Update application table as well.
		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		$app_campaign_manager->Insert_Application($this->application_id, $this->normalized_data, $this->title_loan);
		$app_campaign_manager->Update_Application($this->application_id, $this->transaction_id, $target);

		return $this->application_id;
	}

	private function _send_fastcashday_post( $status )
	{
		include_once( BLACKBOX_DIR . 'http_client.php' );

		$hc = new Http_Client();

		$url =  "http://www.fastcashtoday.com/transmit/transmit.php";

		$fields = array(
			"created" => date("Y-m-d h-m-s").".0000",
			"sourceid" => $_SESSION["data"]["sourceid"],
			"campaignid" => $_SESSION["data"]["campaignid"],
			"creditoffersoptin" => ($_SESSION["data"]["offers"] == "TRUE") ? "Y" : "N",
			"ipaddress" => $_SESSION["data"]["client_ip_address"],
			"email" => $_SESSION["data"]["email_primary"],
			"firstname" => $_SESSION["data"]["name_first"],
			"middlename" => $_SESSION["data"]["name_middle"],
			"lastname" => $_SESSION["data"]["name_last"],
			"address1" => $_SESSION["data"]["home_street"],
			"address2" => "",
			"city" => $_SESSION["data"]["home_city"],
			"state" => $_SESSION["data"]["home_state"],
			"zip" => $_SESSION["data"]["home_zip"],
			"homephone" => $_SESSION["data"]["phone_home"],
			"status" => $status, // (I | A | E) I = IMCOMPLETE, A = APPLICATION, E = INELIGIBLE
			"hasdebt" => ($_SESSION["data"]["unsecured_debt"] == "TRUE") ? "Y" : "N",
		);

		$response = $hc->Http_Post( $url, $fields );

		return TRUE;
	}


	/**
	 * @desc Prepares and sends the contact us page form emails
	 *		 Logic grandfathered from OLP5
	 */
	private function Contact_Switch()
	{
		switch($this->current_page)
		{
			case "info_pub_submit":

					$recipient = array();
					$header = array();
					$message = '';
					$header['subject'] = $this->config->property_name." Publishing Contact";


					// RC and LOCAL email redirection
					if ($this->config->mode != "LIVE")
					{
						$recipient['email_primary'] = "adam.englander@sellingsource.com";
					}
						else
					{
						$recipient['email_primary'] = "bizopp@partnerweekly.com";
					}


					$header["site_name"] = $this->config->site_name;
					$header["sender_name"] = "Publisher Contact Post <cs@".$this->config->site_name.">";
					$header["site"] = $this->config->site_name;
					$header["property"] = $this->config->property_name;
					$header["name"] = $this->collected_data['name'];
					$header["company_name"] = $this->collected_data['company_name'];
					$header['email'] = $this->collected_data['email'];
					$header['phone'] =  $this->collected_data['phone']  ;
					$header['comments'] =  $this->collected_data['comments'];
					$data = array_merge($recipient, $header);

					//require_once('tx/Mail/Client.php');
					require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
					//$tx = new tx_Mail_Client(false);
					$tx = new OlpTxMailClient(false);
					try 
					{
						$result = $tx->sendMessage('live','OLP_PUBLISHER_CONTACT',$data['email_primary'],'',$data);
					}
					catch (Exception $e)
					{
						$this->applog->Write(
							"Trendex mail OLP_PUBLISHER_CONTACT failed. ".
								$e->getMessage()." (App ID: ". 
								$this->Get_Application_ID() . ")");

					}

					$this->next_page = 'thanks_publishers';
				break;
			case "info_adv_submit":

					$recipient = array();
					$header = array();
					$message = '';
					$header['subject'] = $this->config->property_name." Advertising Contact";


					// RC and LOCAL email redirection
					if ($this->config->mode != "LIVE")
					{
						$recipient['email_primary'] = "adam.englander@sellingsource.com";
					}
						else
					{
						$recipient['email_primary'] = "bizopp@partnerweekly.com";
					}


					$header["site_name"] = $this->config->site_name;
					$header["sender_name"] = "Advertiser Contact Us Post <cs@".$this->config->site_name.">";
					$header["site"] = $this->config->site_name;
					$header["property"] = $this->config->property_name;
					$header["name"] = $this->collected_data['adv_companyname'];
					$header['email'] = $this->collected_data['adv_email'];
					$header['phone'] =  $this->collected_data['adv_phone']  ;
					$header['description'] =  $this->collected_data['adv_description'];
					$header['test_to_start'] =  $this->collected_data['adv_testtostart'];
					$data = array_merge($recipient, $header);

					require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
					$tx = new OlpTxMailClient(false);
					try 
					{
						$tx->sendMessage('live','OLP_ADVERTISER_SIGNUP',$data['email_primary'],'',$data);	
					}
					catch (Exception $e)
					{
						$this->applog->Write(
							"Trendex mail OLP_ADVERTISER_SIGNUP failed. ".
								$e->getMessage()." (App ID: ". 
								$this->Get_Application_ID() . ")");
					}

					$this->next_page = 'thanks_advertisers';
			break;
			case "info_contactus_base":
				if( isset($this->collected_data["email_primary"]) && isset($this->collected_data["radiobutton"]) )
				{
					$_SESSION['contact_us']['name_first'] = $this->collected_data['name_first'];
					$_SESSION['contact_us']['name_last'] = $this->collected_data['name_last'];
					$_SESSION['contact_us']['email_primary'] = $this->collected_data['email_primary'];
					$_SESSION['contact_us']['contact_type'] = $this->collected_data['radiobutton'];

					// Decide which page to show next
					switch($_SESSION['contact_us']['contact_type'])
					{
						case "existing":
						case "pending":
							$this->next_page = "info_contactus_app";
						break;

						case "general":
							$this->next_page = "info_contactus_noapp";
						break;
					}
				}
				else
				{
					$this->next_page = "info_contactus_base";
				}
			break;

			case "info_contactus_app":
			case "info_contactus_noapp":

				// apparently, it is possible for us to hit this code
				// without setting contact type, so let's check it here
				if (!$_SESSION['contact_us']['contact_type'])
				{
					$this->next_page = "info_contactus_base";
				}
				else
				{
					$recipient = array();
					$header = array();
					$message = '';
					$ssn_text = ' ';
					switch($_SESSION['contact_us']['contact_type'])
					{
						// General Questions
						case "general":
							if( $contact_us_general_loan_email = SiteConfig::getInstance()->contact_us_general_loan_email )
							{
								// If an override was set in config, use that.
								$recipient = array(
									"email_primary_name" => $contact_us_general_loan_email,
									"email_primary" => $contact_us_general_loan_email,
								);
							}
							else
							{
								// Otherwise use the default address.
								$recipient = array(
									"email_primary_name" => "Client Services",
									"email_primary" => "clientservices@".$this->config->site_name,
								);
							}
							$header['subject'] = $this->config->property_name." General question about getting a loan";
						break;

						// Pending loans
						case "pending":
							if( $contact_us_pending_loan_email = SiteConfig::getInstance()->contact_us_pending_loan_email )
							{
								// If an override was set in config, use that.
								$recipient = array(
									"email_primary_name" => $contact_us_pending_loan_email,
									"email_primary" => $contact_us_pending_loan_email,
								);
							}
							else
							{
								// Defaults go to teleweb
								$recipient = array(
									"email_primary_name" => "Teleweb",
									"email_primary" => "customerservice@fastcashsupport.com",
								);
							}
							$header['subject'] = $this->config->property_name." Question about a pending loan";
							$ssn_text = "SSN: ".$this->collected_data["ssn_complete"]."\r\n";
						break;

						// Existing Loans
						case "existing":
							if( $contact_us_existing_loan_email = SiteConfig::getInstance()->contact_us_existing_loan_email )
							{
								// If an override was set in config, use that.
								$recipient = array(
									"email_primary_name" => $contact_us_existing_loan_email,
									"email_primary" => $contact_us_existing_loan_email,
								);
							}
							else
							{
								// Otherwise use the default address.
								$recipient = array(
									"email_primary_name" => "Customer Service",
									"email_primary" => "customerservice@".$this->config->site_name,
								);
							}
							$header['subject'] = $this->config->property_name." Question about an existing loan";
							$ssn_text = "SSN: ".$this->collected_data["ssn_complete"]."\r\n";
						break;
					}

					//If coming from impactsolutiononline.com, change recepient to use customerservice@impactsolutiononline.com no matter what.
					if(in_array(strtolower($this->config->site_name), array("impactsolutiononline.com","impactcashcap.com","cashfirstonline.com")));
					{
						$recipient = array(
							"email_primary_name" => "Customer Service",
							"email_primary" => "customerservice@{$this->config->site_name}",
						);
					}

					// RC and LOCAL email redirection
					if ($this->config->mode != "LIVE")
					{
						$recipient['email_primary'] = "adam.englander@sellingsource.com";
					}

					$header["site_name"] = $this->config->site_name;
					$header["sender_name"] = "Contact Us Post <cs@".$this->config->site_name.">";
					$header["site"] = $this->config->site_name;
					$header["property"] = $this->config->property_name;
					$header["ssn_text"] = $ssn_text;
					$header["name"] = $_SESSION['contact_us']['name_first']." ".$_SESSION['contact_us']['name_last'];
					$header['contactus_email'] = $_SESSION['contact_us']['email_primary'];
					$header['contactus_text'] = $this->collected_data['contactus_text'];
					$header['application_id'] = $this->Get_Application_ID();
					$data = array_merge($recipient, $header);

					require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
					$tx = new OlpTxMailClient(false);
					try 
					{
						$tx->sendMessage('live','OLP_CONTACT_US_2',$data['email_primary'],'',$data);	
					}
					catch (Exception $e)
					{
						$this->applog->Write(
							"Trendex mail OLP_CONTACT_US_2 failed. ".
								$e->getMessage()." (App ID: ". 
								$this->Get_Application_ID() . ")");
					}

					$this->next_page = 'thanks_contactus';
				}
			break;

		}
	}


	private function Remove_Email ($email)
	{
		$email = trim($email);
		if (!empty($email))
		{
			$params = array('type' => 'email');
			$result = $this->data_validation->Validate_Engine($email, $params);
			if ($result['status'] === TRUE)
			{
				// URL of the Prpc server
				switch($this->config->mode)
				{
					case 'LIVE':
						$pw_prpc_url = 'prpc://cpanel.partnerweekly.com/service/unsub.php';
						break;
					default:
						$pw_prpc_url = 'prpc://rc.cpanel.partnerweekly.com/service/unsub.php';
						break;
				}

				//Required for prpc client
				require_once('prpc/client.php');

				// Set up Prpc for Request
				$obj_prpc_request = new Prpc_Client($pw_prpc_url);

				// Insert the lid if we have it. The lid removes them just for the offer they're on. - Brian F
				if(isset($this->config->lid))
				{
					try
					{
						//The actual function call
						$obj_prpc_request->addUnsubEmail($email, $this->config->lid);
					}
					catch (Exception $e)
					{
						$message .= 'Exception: '.$e.' ';
						//Do nothing
					}
				}
				else
				{
					try
					{
						//The actual function call
						$obj_prpc_request->addUnsubEmail($email, '');
					}
					catch (Exception $e)
					{
						$message .= 'Exception: '.$e.' ';
						// Do nothing
					}
				}

				$message .= "Email ($email) has been removed from our list.";
			}
			else
			{
				$message .= "Email ($email) does not appear to be a valid email.";
			}


		}
		else
		{
			// changed this to pop up when email is blank - GForge 6887 [AuMa]
			$message = "Email ($email) does not appear to be a valid email.";
		}

		return $message;
	}

	/**
	 * @desc Inserts a SMS cell phone number into the remove table so that number
	 * will not receieve SMS messages.
	 *
	 * @param $cell_number string The phone number to be removed
	 * @return string Response message, either failed or succeeded
	 */
	private function Remove_SMS($cell_number)
	{
		$message = "You did not provide a valid phone number.";

		// Insert SMS number into SMS remove table
		if(isset($cell_number))
		{
			if(preg_match('/[0-9]{10}/', $cell_number))
			{
				require_once ("prpc/client.php");

				switch($this->config->mode)
				{
					case 'LIVE':
						$prpc_url = 'prpc://sms.edataserver.com/sms_prpc.php';
						break;
					default:
						$prpc_url = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
						break;
				}

				$sms_obj = new Prpc_Client($prpc_url);
				$sms_obj->Add_To_Blacklist($cell_number);

				Stats::Hit_Stats('sms_removed_number', $this->session, $this->event, $this->applog);

				$message = "(".substr($cell_number, 0, 3).") ".substr($cell_number, 3, 3)."-".substr($cell_number, 6, 4)." has been removed from our list.";
			}
			else
			{
				$message = "$cell_number does not appear to be a valid phone number.";
			}
		}

		return $message;
	}

	/**
	 * @desc Function to process EZM legal submissions
	 */
	private function Process_EZM()
	{

		$errors = array();

		// validate data
		if (!$this->normalized_data['ezm_nsf_count'])
		{
			$errors['ezm_nsf_count'] = 'ezm_nsf_count';
		}
		if (!$this->normalized_data['ezm_terms'])
		{
			$errors['ezm_terms'] = 'ezm_terms';
		}
		if (!$this->normalized_data['ezm_signature'])
		{
			$errors['ezm_signature'] = 'ezm_signature';
		}

		$this->errors = $errors;

		if (!$this->errors)
		{

			// wake BlackBox up and retrieve our current winner
			$this->blackbox_obj = $this->Configure_Blackbox($_SESSION['blackbox_asleep']);
			$this->blackbox = $this->blackbox_obj->winner();
			$this->Process_Winner();

		}

	}

	/**
	* @desc Function to instantiate ent_cs object
	*/
	private function Get_Ent_Cs($property_short, &$blackbox = NULL)
	{

		$this->Setup_DB($property_short);

		// our ent_prop_list is by property short: ent_cs needs it
		// by site name, so let's transform it
		foreach ($this->ent_prop_short_list as $site=>$prop_short)
		{
			$ent_prop_list[$site] = $this->ent_prop_list[$prop_short];
		}

		if(!$this->event)
		{
			$this->Event_Log();
		}

		include_once('ent_cs.mysqli.php');
		$ent_cs = new Ent_CS_MySQLi(
			$this->db,
			$this->sql,
			$this->normalized_data,
			$this->collected_data,
			$this->event,
			$this->applog,
			$this->session,
			$property_short,
			$this->database,
			$ent_prop_list,
			$blackbox,
			$this->title_loan
		);


		return $ent_cs;

	}

	/**
	* @desc inserts email into ole
	*/
	private function EPM_Collect()
	{

		if(!defined('USE_EPM_COLLECT') || USE_EPM_COLLECT === true)
		{

			// insert email/name info into ole database if ole list/site id is in the config, there are no errors from check_and_collect, epm_collect is not set
			// hard code these variables if they don't exist
			$ole_list_id = $_SESSION["config"]->ole_list_id ? $_SESSION["config"]->ole_list_id : 1;
			$ole_site_id = $_SESSION["config"]->ole_site_id ? $_SESSION["config"]->ole_site_id : 1;

			/*
				We need to sell to EPM collect on the prequal stat for sites that have the
				sell_epm_prequal variable set. This was originally setup so that host-n-post sites
				don't sell to EPM collect when the first page of the application hasn't even been
				submitted yet. [BrianF]
			*/
			$submit_epm = TRUE;

			if(isset($this->config->sell_epm_prequal))
			{
				// If sell_epm_prequal is set, don't sell to EPM collect unless we're also hitting prequal
				$submit_epm = FALSE;
				$page_stats = $this->config->site_type_obj->pages->{$this->current_page}->stat;
				$page_stats = explode(',', $page_stats);

				$prequal_stats = Stats::Translate('prequal');

				foreach($prequal_stats as $prequal_stat)
				{
					if(in_array($prequal_stat, $page_stats))
					{
						$submit_epm = TRUE;
					}
				}
			}

			if($this->collected_data["email_primary"] &&
				!count($this->errors) && !isset($_SESSION["epm_collect"])
				&& $submit_epm && ($_SESSION["config"]->mode == 'LIVE' || $_SESSION['config']->mode == 'RC'))
			{

				$ip_addr = $this->normalized_data['client_ip_address'];
				$group_id = (isset($this->config->group_id)) ? $this->config->group_id : 1;

				//This class is in charge of maintaining the data inside the list_mgmt tables
				// The list_mgmt tables are used in conjuction with an external cronjob to decide who is or is not
				// sent to epm_collect.
				include_once(BFW_MODULE_DIR.'olp/list_mgmt_collect.php');
				$no_sell_obj = new List_Mgmt_Collect($this->sql,$this->database);
				
				// Grab the app type
				$application_type = $this->Get_Application_Type();
				$application_types = array ('VISITOR','FAILED');
				
				// If the app is VISITOR or FAILED we set the tier to 0 otherwise use the sold to tier
				$tier = ((!in_array(strtoupper($application_type),$application_types)) && isset($_SESSION['blackbox']['tier']) && $_SESSION['blackbox']['tier'] != '')  ? $_SESSION['blackbox']['tier'] : 0;
				
				// If they are not supposed to be sold to then set a flag saying we did anyway and they can suck eggs
				$bb_vendor_bypass = ($no_sell_obj->Check_List_Mgmt_Nosell($this->normalized_data["email_primary"])) ? 1 : 0;

				if(isset($this->application_id))
				{
					try
					{
						$date_of_birth = "{$this->normalized_data["date_dob_y"]}-{$this->normalized_data["date_dob_m"]}-{$this->normalized_data["date_dob_d"]}";

						$no_sell_obj->Insert_Into_List_Mgmt_Buffer(
							$this->application_id,
							$this->normalized_data["email_primary"],
							$this->normalized_data["name_first"],
							$this->normalized_data["name_last"],
							$ole_site_id,
							$ole_list_id,
							$group_id,
							BFW_MODE,
							$this->config->license,
							$this->normalized_data["home_street"],
							$this->normalized_data["home_unit"],
							$this->normalized_data["home_city"],
							$this->normalized_data["home_state"],
							$this->normalized_data["home_zip"],
							$this->config->site_name,
							$this->normalized_data["phone_home"],
							$this->normalized_data["phone_cell"],
							$date_of_birth,
							$this->config->promo_id,
							$bb_vendor_bypass,
							$tier);
					}
					catch (Exception $e)
					{
						// Do nothing for now
					}
					$_SESSION["epm_collect"] = TRUE;
				}

			}
		}
	}

	private function Pre_Prequal_Collect()
	{

		// since this is now called by the Blackbox_Prequal function (when
		// our current page has the 'pre_prequal' stat), this is outdated [AM]
		//if( !isset($_SESSION["fle_dupe_id"]) && in_array($this->config->site_type, $this->fle_site_types) && strlen($this->normalized_data["email_primary"]) > 5 )

		// only run this if we haven't run it already, and
		// if our email address is considered valid
		if ((!isset($_SESSION['fle_dupe_id'])) && (!in_array('email_primary', $this->errors)))
		{
			// insert into the dupes table: we might want to make
			// the index unique and use a replace into?
			$query = "INSERT INTO fle_dupes ( email, site ) VALUES ( '{$this->normalized_data['email_primary']}', '{$this->config->site_name}' )";
			$result = $this->sql->Query($this->database, $query);

			// save our ID
			$_SESSION['fle_dupe_id'] = $this->sql->Insert_ID();

		}

		return;

	}

	/**
	* @desc Instantiates olp db class
	*/

	private function Setup_OLP_DB($property_short)
	{
		$this->Setup_DB($property_short);
		$olp_db = OLP_LDB::Get_Object($property_short, $this->db);
		return $olp_db;
	}

	/**
		@privatesection
		@private
		@fn boolean Is_Caller_Over_Limit( &$collected_data )
		@brief
			Will tell you if the calling vendor is over daily limit

		@param &$collected_data array
			The data sent by the PRPC client
		@return boolean
			Will return TRUE if vendor is over daily request limit, else FALSE
	*/
	private function  Is_Caller_Over_Limit( &$collected_data )
	{
		// Get our limits class
		include_once('request_limit.php');

		// Find promo id
		$promo_id = $_SESSION['config']->promo_id;

		$request_limit = new Request_Limit($this->sql, $this->database, $promo_id);

		// Are they over ?
		if( $request_limit->Is_Over_Limit )
		{
			$this->applog->Write("Over limit on site ". $_SESSION["config"]->site_name ." for promo_id ". $_SESSION["config"]->promo_id . " using session ". session_id(). "\n");
		}

		return $request_limit->Is_Over_Limit;
	}

	/**
		@privatesection
		@private
		@fn boolean E_Sig_App_Validation($app_campaign_manager)
		@brief
			Duplicate App Check

		@desc Check for Duplicate and process Agreed Apps
	*/
	private function E_Sig_App_Validation($app_campaign_manager)
	{

		$this->Setup_DB($_SESSION['blackbox']['winner']);
		if (strtoupper($_SESSION['config']->mode) != "LIVE" && isset($this->normalized_data['no_checks']))
			$nochecks = TRUE;

		$ssn = $_SESSION["data"]['social_security_number'];
		$db = Server::Get_Server($this->config->mode, 'BLACKBOX');

		$cashline = Previous_Customer_Check::Get_Object(
			$this->sql,
			$db['db'],
			$_SESSION['blackbox']['winner'],
			$this->config->mode
		);

		//$cashline = new Cashline($this->sql, $db['db'], $_SESSION['blackbox']['winner'], $this->applog, $this->config->mode);
		$cl_check = $cashline->Check(
			$ssn,
			array($_SESSION['blackbox']['winner']),
			Previous_Customer_Check::TYPE_SSN,
			NULL,
			$_SESSION['application_id']
		);

		if (!count($cl_check) && !$nochecks)
		{
			$comment = "Changed bank info on confirmation. Overactive aba/account combination";
			$this->Force_Decline($this->Get_Application_ID(),$_SESSION['blackbox']['winner'],$comment);
			$this->blackbox = array('denied'=>TRUE);
			$this->Process_Winner();
			Stats::Hit_Stats( 'confirmed_overactive', $this->session, $this->event, $this->applog, $this->Get_Application_ID() );

			$this->next_page = "app_declined";
		}
		else
		{
			// Set sub status to AGREE
			$olp_db = $this->Setup_OLP_DB($_SESSION['blackbox']['winner']);

            if(empty($this->application_id))
            {
                $this->application_id = $this->Get_Application_ID();
            }

			if(!$this->config->use_new_process)
			{
				$olp_db->App_Completed_Updates($this->application_id);
			}


			// instantiate ent_cs class to send mail - rsk
			$ent_cs = $this->Get_Ent_Cs($_SESSION['blackbox']['winner']);

			if ($this->config->force_confirm)
			{
				// Update Agree Stat and set application status to Agree
				$this->Stat_App_Agree($app_campaign_manager);

                if(empty($this->application_id))
                {
                    throw new Exception("Could not update application status - no app id");
                }

                if(!$this->config->use_new_process)
                {
					$olp_db->App_Confirmed_Updates($this->application_id);
                }

				$app_campaign_manager->Update_Application_Status($this->application_id, 'CONFIRMED');

				$ent_cs->Final_Processing();

			}
			else
			{
				// Update Agree Stat and set application status to Agree
				$this->Stat_App_Agree($app_campaign_manager);

				// If we have a cell number, let's send an SMS
				if (preg_match("/^\d{10}$/", $_SESSION['data']['phone_cell']))
				{
					$this->Send_SMS_Agreed();
				}

				$ent_cs->Mail_Confirmation($this->ent_prop_list[$_SESSION['blackbox']['winner']]['site_name']);

			}

		}
		$_SESSION['app_completed'] = TRUE;

	}


	/**
		@privatesection
		@private
		@fn void Stat_App_Agree()
		@brief
			Stat_App_Agree

		@desc Update Agree Stat and set application status to Agree
	*/
	private function Stat_App_Agree($app_campaign_manager)
	{
		// update the stat limit table
		$this->Increment_Winning_Stat($_SESSION['blackbox']);

		Stats::Hit_Stats( 'agree', $this->session, $this->event, $this->applog,  $this->application_id );
		Stats::Hit_Stats( 'bb_' . $_SESSION['blackbox']['winner'] . '_agree', $this->session, $this->event, $this->applog,  $this->application_id );

		// Added check for Impact Company 2 (ifs) to make sure it hits submit level 2 - GForge #2892 [DW]
		$submitlevel = 'submitlevel' . $_SESSION['blackbox']['original_tier'];
		Stats::Hit_Stats($submitlevel, $this->session, $this->event, $this->applog,  $this->application_id );
		$limits = new Stat_Limits($this->sql, $this->database);
		$limits->Increment($submitlevel,0,$this->config->promo_id,0);

		// check and update their application_type to agreed
		$app_campaign_manager->Update_Application_Status($this->application_id, 'AGREED');
	}


	function Send_SMS_Agreed()
	{

		try
		{
			$license = $this->ent_prop_list[strtoupper($_SESSION['blackbox']['winner'])]['license'][strtoupper($_SESSION['config']->mode)];
			if (isset($_SESSION['data']['sms_mode']) && strtolower($_SESSION['data']['sms_mode'])=='live')
			{
				$sms_mode = 'live';
			}
			else
			{
				$sms_mode = $_SESSION['config']->mode == 'LIVE' ? 'live' : 'test';
			}

			require_once('olp.sms.php');
			$olp_sms = new OLP_SMS($this->sql, $license, $sms_mode);
			$olp_sms->Run_SMS_Script();
		}
		catch(Exception $e)
		{
			$applog_msg = "Send_SMS_Agreed failed:\n
				license: {$license}\n
				sms_mode: {$sms_mode}\n
				promo_id: {$_SESSION['config']->promo_id}\n
				promo_sub_code: {$_SESSION['config']->promo_sub_code}\n
				winner: {$_SESSION['blackbox']['winner']}\n
				cell_phone: {$_SESSION['data']['phone_cell']}\n
				session_id: ".session_id();
			$this->Applog_Write($applog_msg);
		}

	}

	/**
	 * Set an application to failed and add a comment?
	 *
	 * @param int $application_id
	 * @param string $property_short
	 * @param string $comment_text
	 */
	private function Force_Fail($application_id, $property_short, $comment_text)
	{
		if($this->config->use_new_process)
		{
			$ent_cs = $this->Get_Ent_cs($property_short);
			$ent_cs->Update_Status($application_id, 'denied');

			$_SESSION['ldb_data'][$application_id]['comments'][] = array(
				'property_short' => $property_short,
				'application_id' => $application_id,
				'type' => 'declined',
				'comment' => $comment_text
			);
		}
		else
		{
			$olp_db = $this->Setup_OLP_DB($property_short);

			$olp_db->Update_Application_Status('denied',$application_id);

			// updating ldb::comment table
			$comment['property_short'] = $property_short;
			$comment['application_id'] = $application_id;
			$comment['type'] = "deny";
			$comment['comment'] = $comment_text;
			$olp_db->Insert_Comment($comment);
		}

		return;
	}

	/**
		@privatesection
		@private
		@fn void Force_Decline()
		@brief
			Force_Decline

		@desc Force_Decline application in LDB with comment
	*/
	private function Force_Decline($application_id, $property_short, $comment_text)
	{
		if($this->config->use_new_process)
		{
			$ent_cs = $this->Get_Ent_cs($property_short);
			$ent_cs->Update_Status($application_id, 'declined');

			$_SESSION['ldb_data'][$application_id]['comments'][] = array(
				'property_short' => $property_short,
				'application_id' => $application_id,
				'type' => 'declined',
				'comment' => $comment_text
			);
		}
		else
		{
			$olp_db = $this->Setup_OLP_DB($property_short);

			$olp_db->Update_Application_Status('declined',$application_id);

			// updating ldb::comment table
			$comment['property_short'] = $property_short;
			$comment['application_id'] = $application_id;
			$comment['type'] = "declined";
			$comment['comment'] = $comment_text;
			$olp_db->Insert_Comment($comment);
		}

		return;
	}
	/**
		@privatesection
		@private
		@fn void View_Condor_Docs()
		@brief
			View_Condor_Docs

		@desc Run Current Page Cases for: View_Condor_Docs
	*/

	private function View_Condor_Docs()
	{

		// at this point, we are in an enterprise site, so bb_force_winner should be ca, d1, pcl, ucl, or ufc
		$property_short = ($_SESSION['blackbox']['winner']) ? $_SESSION['blackbox']['winner'] : strtolower($this->ent_prop_short_list[$_SESSION['config']->site_name]);
		if (!$property_short) $property_short = $this->property_short;

		// Use transaction_id from CS if set and base transaction_id not set (happens when
		// viewing docs from ent_cs status page)

		if(empty($_SESSION['transaction_id']) && isset($_SESSION['cs']['transaction_id']))
		{
			$_SESSION['transaction_id'] = $_SESSION['cs']['transaction_id'];
		}

		// only try to get the doc if we have these, added md5_hash_match and app_completed for security
		if (($_SESSION['transaction_id'] && $property_short) && ( $_SESSION['cs']['md5_hash_match'] || $_SESSION['app_completed'] ))
		{

			try
			{
				if($this->ent_prop_list[strtoupper($this->property_short)]['new_ent'] && isset($_SESSION['condor_data']))
				{
					require_once('prpc/client.php');
					$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $this->property_short);
					$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");

					$condor_data = $condor_api->Find_By_Archive_Id($_SESSION['condor_data']['archive_id']);
					$legal_doc = $condor_data->data;
				}
				elseif($this->Is_Ecash3($property_short))
				{
					// We are in view docs from the ent_status page
					if(empty($_SESSION['condor_data']['document']) || empty($_SESSION['condor_data']['document']->data))
					{
						$this->Find_Loan_Document();
					}

					$legal_doc = $_SESSION['condor_data']['document']->data;
				}
				else
				{
					$legal_doc = $this->condor->View_Legal($_SESSION['transaction_id']);
				}
			}
			catch (MYSQL_Exception $e)
			{
				$this->Applog_Write("app_id: ".$this->application_id . " - Condor view_legal failed to get document. transaction_id = " . $_SESSION['transaction_id']. ", property_short = $property_short, session_id = " .  session_id() );
			}

			//Prevent documents with missing data from getting written GForge #8061 [MJ]
			if (isset($_SESSION['condor_data']['archive_id'])
				&& $_SESSION['condor_data']['archive_id']
				&& is_numeric($_SESSION['condor_data']['archive_id']))
			{
				// document event
				$this->Document_Event($property_short);
			}
		}

		// test for legal docs
		if ($legal_doc)
		{
			$this->eds_page = array('content' => $legal_doc, 'type' => 'html' , 'action' => 'standard');
		}
		else
		{
			//$this->event->Log_Event('CONDOR_VIEW', 'FAIL');
			$this->eds_page = array('content' => "Sorry, the legal documents are not available at this time.", 'type' => 'text', 'action' => 'standard');
		}

	}
	/**
		@privatesection
		@private
		@fn void Preview_Condor_Docs()
		@brief
			Preview_Condor_Docs

		@desc Run Current Page Cases for: Preview_Condor_Docs
	*/
	private function Preview_Condor_Docs()
	{
		$legal_doc = NULL;
		
		// at this point, we should have a tier 1 winner.  It should not be the case that we are using bb_force_winner
		if ($this->property_short && (strtolower($this->property_short) !== 'bb'))
		{
			$property_short = $this->property_short;
		}
		else
		{
			$property_short = strtoupper(($_SESSION['blackbox']['winner']) ? $_SESSION['blackbox']['winner'] : $_SESSION['config']->bb_force_winner);
		}

		if($this->ent_prop_list[strtoupper($this->property_short)]['new_ent'] && isset($_SESSION['account_summary']['condor_data']))
		{
			require_once ('prpc/client.php');

			$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $this->property_short);
			$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");
			$condor_data = $condor_api->Create('Account Summary - FAX', $_SESSION['account_summary']['condor_data']);
			$legal_doc = $condor_data->data;
		}
		elseif($this->Is_Ecash3($property_short))
		{
			require_once(BFW_CODE_DIR.'condor_display.class.php');
			require_once ("prpc/client.php");

			$condor_display = new Condor_Display('preview');
			if(empty($_SESSION['data']['ecash_sign_docs']))
			{
				$token_data = $condor_display->Generate_Condor_Tokens();
			}
			else
			{
				$ent_cs = $this->Get_Ent_Cs($property_short);
				$token_data = $condor_display->Rename_Tokens($ent_cs->Prepare_Condor_Data($this->Get_Application_ID()));
			}

			// GForge 6741 - Don't call Condor if not logged in. [RM]
			if (isset($token_data['LoginId']))
			{
				$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $property_short);
				$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");
				// Mantis #12161 -  Added in this if statement to decide card loan or standard loan doc	[RV]
				$condor_template = ($_SESSION['data']['loan_type'] == 'card' ||  $_SESSION['cs']['loan_type'] == 'card') ? "Card Loan Document" : "Loan Document";
				$condor_data = $condor_api->Create($condor_template, $token_data);
				$legal_doc = $condor_data->data;
			}
		}
		else
		{
			// bring up paperless application_xhtml
			$data = array();

			// in customer service
			if ( isset( $_SESSION['cs']['transaction_id'] ) )
			{

				$transaction_id = $_SESSION['cs']['transaction_id'];
				$this->Setup_DB($property_short);

				// instantiate ent_cs class
				$ent_cs = $this->Get_Ent_Cs($property_short);
				$data = $ent_cs->Prepare_Condor_Data($transaction_id);

				$data['application_id'] = $transaction_id;
				$data['config']->legal_entity = $this->ent_prop_list[ $property_short ]["legal_entity"];
				$data['config']->support_fax = $this->ent_prop_list[ $property_short ]["fax"];
				$data['config']->promo_id = $_SESSION['config']->promo_id;

			}
			else // new app or react
			{

				$data['config'] = clone $_SESSION['config'];
				$data['data'] = $_SESSION['data'];

				$data['application_id'] = $this->Get_Application_ID();
				$legal_entity = $this->ent_prop_list[strtoupper($property_short)]['legal_entity'];
				$support_fax = $this->ent_prop_list[strtoupper($property_short)]['fax'];

				$data['config']->property_short = strtoupper($property_short);
				$data['config']->site_name = $this->ent_prop_list[strtoupper($property_short)]['site_name'];

				$data['config']->legal_entity = $legal_entity;
				$data['config']->property_name = $legal_entity;
				$data['config']->support_fax = $support_fax;

				// unset stuff we don't need to pass
				unset($data['config']->site_type_obj);
				unset($data['data']['client_state']);

			}

			// GForge 6741 - Don't call Condor if no application id. [RM]
			if ($data['application_id'])
			{
				$legal_doc = $this->condor->Preview_Docs("review_paperless_app", $data);
			}
			else
			{
				OLP_Applog_Singleton::quickWrite('Attempted to call Condor without a valid application id.');
			}
		}

		// test for legal doc and log if not present
		if ($legal_doc)
		{
			$this->eds_page = array('content' => $legal_doc, 'type' => 'html' , 'action' => 'standard');
		}
		else
		{
			$this->event->Log_Event('CONDOR_VIEW', 'FAIL');
			$this->eds_page = array('content' => "Sorry, the legal documents are not available at this time.", 'type' => 'text' , 'action' => 'standard');
		}
	}




	private function SOAP_CS($property_short)
	{
		require_once('customer_service.php');
		$this->Event_Log();
		$result = false;
		// instantiate the class if these conditions exist otherwise return to login page
		// deny access to cust service if they passed in the unique_id
		// otherwise when unique_id is passed in it retrieves the session info and breaks customer service
		$passed_in_unique_id = preg_match("/unique_id/i", $_SESSION['data']['client_state']['_SERVER']['QUERY_STRING'] );

		// cust_email is populated when they request to have their login info emailed to them
		if(empty($passed_in_unique_id)
			&& (isset($this->normalized_data['cust_username'])
				|| isset($this->collected_data['application_id'])
				|| $this->Get_Application_ID()
				|| !empty($this->normalized_data['cust_email'])))
		{

			$this->Setup_DB($property_short);

			// add enterprise_data to normalized data
			$this->normalized_data['enterprise_data'] = $this->enterprise_data;

			$cs = new Customer_Service(
				$this->config,
				$this->session,
				$this->ent_prop_list[$property_short],
				$this->normalized_data,
				$this->Get_Application_ID()
			);

			// returns ent_cs object
			//$ent_cs = $this->Get_Ent_Cs($property_short);
			// email password: from ent_cs_login page
			if($this->current_page == 'password_mailed')
			{
				$result = $cs->Mail_Password();
			}
			elseif(isset($_SESSION['cs']['logged_in']))
			{
				switch($this->current_page)
				{
					case 'ent_online_confirm': $result = $cs->Confirm(); break;
					case 'ent_online_confirm_legal': $result = $cs->Agree(); break;
					default:
					case 'ent_status':
					{
						$ent_cs = $this->Get_Ent_Cs($property_short);
						$result = $ent_cs->Page_Handler();

						if(isset($result['errors']))
						{
							$this->errors[] = $result['errors'];
						}

						$this->next_page = $result['page'];
						$this->cs = $result['cs'];

						break;
					}
				}

				/*if(isset($_SESSION['react_completion']))
				{
					$this->cs['app_status'] = 'pending';
					$this->cs['transaction_id'] = $this->application_id;
				}*/
			}
			else // not logged in yet
			{
				$result = $cs->Login();

				if($result)
				{
					$_SESSION['cs']['logged_in'] = true;
					$this->Update_Campaign_Info($cs->Get_ID());
				}

				$_SESSION['application_id'] = $cs->Get_ID();
			}

		// no application id or username so show login page
		}
		else
		{
			$this->next_page = "ent_cs_login";
		}


		if(!is_array($result))
		{
			if(!$result)
			{
				$this->errors = array_merge($this->errors, $cs->Get_Errors());
				$this->next_page = $this->current_page;
			}
			else
			{
				$this->next_page = $cs->Page();
			}

			if($cs->Page() == 'ent_online_confirm')
			{
				$_SESSION['data']['fund_qualified'] = $cs->GetFundAmount();
			}
		}

		return $result;
	}




	/**
		@privatesection
		@private
		@fn void Customer_Service()
		@brief
			Customer_Service

		@desc Run Current Page Cases for: Customer_Service
	*/
	private function Customer_Service()
	{
		$property_short = $this->ent_prop_short_list[$_SESSION['config']->site_name];

		/*if($this->Get_Application_ID() !== false)
		{
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$winner = $app_campaign_manager->Get_Winner($this->Get_Application_ID());

			if(!empty($winner))
			{
				$property_short = strtoupper($winner);
			}
		}


		if($this->ent_prop_list[$property_short]['use_soap'])
		{
			return $this->SOAP_CS($property_short);
		}*/

		// we need to re-check hourly limits here.
		// See webadmin task #7001
		if (isset($this->normalized_data['no_checks']))
		{
			$nochecks = TRUE;
		}


		$continue = "continue";

		if( $_SESSION["config"]->site_name == "paydaycentral.com" )
		{
			$prefix = ($config->mode == "RC") ? "rc." : "";
			$_SESSION["data"]["redirect"] = "http://{$prefix}{$this->ent_prop_list['CA']['site_name']}/?page=ent_cs_login&application_id={$this->collected_data["application_id"]}&property_short=ca";
			return TRUE;
		}

		// just in case they somehow came in with a unique_id and site_name is set to the non ent site and prop short isn't set
		$this->property_short = (!$this->property_short) ? $_SESSION['config']->property_short : $this->property_short;

		// short circuit non enterprise sites from trying to get into cust service from a non enterprise domain
		if (!$this->property_short || strtoupper($this->property_short) == "BB")
		{
			$this->next_page = "try_again";
			return FALSE;
		}

		// instantiate the class if these conditions exist otherwise return to login page
		// deny access to cust service if they passed in the unique_id
		// otherwise when unique_id is passed in it retrieves the session info and breaks customer service
		$passed_in_unique_id = preg_match("/unique_id/i", $_SESSION['data']['client_state']['_SERVER']['QUERY_STRING'] );

		// cust_email is populated when they request to have their login info emailed to them
		if ( !$passed_in_unique_id && ($this->normalized_data['cust_username'] || $this->collected_data['application_id'] || isset($_SESSION['cs']['application_id']) || !empty($this->normalized_data['cust_email']) ))
		{

			// strip non alphanumeric chars from application_id
			$transaction_id = preg_replace("/[^0-9a-zA-Z=]/", "", urldecode($this->collected_data['application_id']));

			// base64 decode application_id if it's not all digits
			if (!is_numeric($transaction_id))
			{
				$transaction_id = base64_decode($transaction_id);
			}

			// back to a number so strip any non numeric at this point
			$transaction_id = preg_replace("/[^0-9]/","",$transaction_id);

			// if transaction_id is passed in then set to that, otherwise set to session value
			$transaction_id = isset($transaction_id) ? $transaction_id : $_SESSION['cs']['transaction_id'];

			// setup db stuff
			if(isset( $_SESSION['react']['key']))
			{
				if(isset($_SESSION['config']->bb_force_winner))
                {
                	$p_short = $_SESSION['config']->bb_force_winner;
                }
                elseif(isset($_SESSION['config']->property_short))
                {
                	$p_short = $_SESSION['config']->property_short;
                }
                elseif(isset($this->property_short))
                {
                	$p_short = $this->property_short;
                }
                elseif(isset($_SESSION['old_config']->bb_force_winner))
                {
                	$p_short = $_SESSION['old_config']->bb_force_winner;
                }
                elseif(isset($_SESSION['condor']->property_short))
                {
                	$p_short = $_SESSION['condor']->property_short;
                }
                else {
                	throw new Exception("Cannot setup db - no valid property short");
                }
                // Reacts should always be processed by and enterprise site.  At this poinst we should only have one property short [nr 2005-10-21]
				$this->Setup_DB($p_short);
			}
			else
			{
				// just in case they somehow came in with a unique_id and site_name is set to the non ent site and prop short isn't set
				//$this->property_short = (!$this->property_short) ? $_SESSION['config']->property_short : $this->property_short;
				$this->Setup_DB($this->property_short);
			}

			// add enterprise_data to normalized data
			$this->normalized_data['enterprise_data'] = $this->enterprise_data;

			//Since we run a cashline check on the legal page,
			//we need to pass in a blackbox connection.
			$blackbox = NULL;
			if($this->current_page == 'ent_online_confirm_legal' && !empty($_SESSION['cs']))
			{
				$new_data = array(
					'bank_aba'				=> $_SESSION['cs']['bank_aba'],
					'bank_account'			=> $_SESSION['cs']['bank_account'],
					'social_security_number'=> $_SESSION['cs']['social_security_number'],
					'email_primary'			=> $_SESSION['cs']['email_primary'],
					'phone_home'			=> $_SESSION['cs']['phone_home'],
					'state_id_number'		=> $_SESSION['cs']['state_id_number']
				);

				$blackbox = $this->Configure_BlackBox(NULL, $new_data, MODE_AGREE);
			}

			// returns ent_cs object
			$ent_cs = $this->Get_Ent_Cs($this->property_short, $blackbox);

			// email password: from ent_cs_login page
			if ( $this->current_page == "password_mailed" )
			{
				$return = $ent_cs->Mail_Password();

				// handle results - go back to cs_password page which is front end
				$this->current_page = $return['page'];

				if ($return['errors'])
				{
					$this->errors[] = $return['errors'];
				}

				return $continue;

			}

			/////////////////////////////////////////////
			// ALREADY LOGGED ON PASS TO PAGE HANDLER //
			///////////////////////////////////////////
			if (isset($_SESSION['cs']['logged_in']))
			{
				// need to use collected_data because normalized data is sticky
				// we check to see if  transaction_id was passed in for cs in ent_cs.php
				$transaction_id = $this->collected_data['transaction_id'];
				$return = $ent_cs->Page_Handler( $transaction_id );

				if (isset($return['errors']))
				{
					$this->errors[] = $return['errors'];
				}

				$this->next_page = $return['page'];
				$this->cs = $return['cs'];

				if(isset($_SESSION['react_completion']))
				{
					$this->cs['app_status'] = 'pending';
					$this->cs['transaction_id'] = $this->application_id;
				}
			}
			else // not logged in yet
			{

				// login returns next page and errors
				$login = $ent_cs->Login();

				// check for errors
				if (isset($login['errors']))
				{
					if(in_array($this->current_page, array('ent_cs_card_login', 'ent_cs_login_reload')) && $login['page'] == 'ent_cs_login')
					{
						$this->next_page = $this->current_page;
					}
					else
					{
						$this->next_page = $login['page'];
					}

					$this->errors[] = $login['errors'];
				}
				else  // no errors hand off to page handler
				{
					$this->Event_Log();
					$return = $ent_cs->Page_Handler();

					$this->next_page = $return['page'];
					$this->cs = $return['cs'];

					// process any campaign changes
					$this->config = $_SESSION['config'];
					$this->Update_Campaign_Info($this->cs['application_id']);

				}

			}

			if (isset($_SESSION['cs']) && is_array($this->cs))
			{
				$_SESSION['cs'] = array_merge( $_SESSION['cs'], $this->cs);
			}
			else
			{
				$_SESSION['cs'] = $this->cs;
			}

		// no application id or username so show login page
		}
		else
		{
			if(in_array($this->current_page, array('ent_cs_card_login', 'ent_cs_login_reload')))
			{
				$this->next_page = $this->current_page;
			}
			else
			{
				$this->next_page = "ent_cs_login";
			}
		}
		
		if ($this->Is_Ecash3($property_short))
		{

			$application_id = $this->Get_Application_ID();
			$olp_process = '';

			if(!empty($application_id))
			{
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$olp_process = $app_campaign_manager->Get_Olp_Process($application_id);
			}

			if(empty($_SESSION['transaction_id']) && isset($_SESSION['cs']['transaction_id']))
			{
				$_SESSION['transaction_id'] = $_SESSION['cs']['transaction_id'];
			}

			// if going to agree page from a link or saved url (already confirmed in a previous session), 
			// check if $_SESSION['cs']['old_fund_date'] is set and compare it with the $_SESSION['cs']['qualify']['fund_date']
			// to see if any information has been updated and condor docs need to be regenerated. Mantis #13879 [DW]
			if ($this->current_page == "ent_cs_login" && $this->next_page == "ent_online_confirm_legal")
			{
				if (isset($_SESSION['cs']['old_fund_date'])
					&& $_SESSION['cs']['qualify']['fund_date'] != $_SESSION['cs']['old_fund_date']
				)
				{
					$this->Generate_Condor_Docs();
				}
			}
			
			//Don't generate the docs for new ecashapps
			if(($this->current_page == 'ent_online_confirm' || $this->current_page == 'ecash_sign_docs')
				&& $olp_process != 'ecashapp_new')
			{
				$this->Generate_Condor_Docs();
			}

			// send a sign request to condor
			if($this->current_page == 'ent_online_confirm_legal')
			{
				$this->Sign_Condor_Docs();
			}
		}

		return $return;

	}


	// This function checks for webadmin1 configuration options that can be used to rewrite the original config.
	// This sometimes needs to be done when an application is going to runthrough blackbox a 2nd time however
	// with different configuration rules.
	// As of the date this was originally written, this function is called here in blackbox_intersticial() as well as ent_cs.mysqli.php
	static public function Overwrite_Config_Rerun($config)
	{
		if(isset($config->bb_force_winner_rerun))
		{
			$config->bb_force_winner = $config->bb_force_winner_rerun;

			if($config->bb_force_winner_rerun == 0)
			{
				unset($config->bb_force_winner);
			}
		}


		if(isset($config->accept_level_rerun))
		{
			$config->limits->accept_level = $config->accept_level_rerun;
		}



		if(isset($config->excluded_targets_rerun))
		{
			$config->excluded_targets = $config->excluded_targets_rerun;

			if($config->excluded_targets_rerun == 0)
			{
				unset($config->bb_force_winner);
			}

		}


		if(isset($config->bb_reject_level_rerun))
		{
			$config->bb_reject_level = $config->bb_reject_level_rerun;
		}


		if(isset($config->force_priority_rerun))
		{
			$config->force_priority = $config->force_priority_rerun;
		}


		return $config;
	}

	/**
		@privatesection
		@private
		@fn void BlackBox_Intersticial()
		@brief
			BlackBox_Intersticial

		@desc Run Current Page Cases for: BlackBox_Intersticial
			Will reconfigure new blackbox options and attempt to rerun the application if YES is selected
			on the intersticial page.
			*/
	private function BlackBox_Intersticial()
	{
		if($_SESSION['data']['sell'] == 'TRUE')
		{
			$this->config->bb_force_winner_rerun = 0;
			$this->config->bb_reject_level = '';
			$this->config = $this->Overwrite_Config_Rerun($this->config);

			$this->blackbox_obj = $this->Configure_Blackbox();

			if($this->blackbox_obj)
			{
				$this->blackbox_obj->pickWinner();
				$this->blackbox = $this->blackbox_obj->winner();
				$this->event->Log_Event('CONFIRM_INTERSTICIAL', 'PASS', $this->blackbox['winner'], $this->application_id);
			}
			else
			{
				// we had an error, just act like it denied them?
				$this->blackbox = array('denied' => TRUE);
			}

			$this->Process_Winner();
		}
		else
		{
			$this->next_page = 'app_declined';
		}

		$_SESSION['intersticial_processed'] = TRUE;
	}


	private function BlackBox_Rerun_Refs()
	{
			$this->event->Log_Event('RERUN_REFS', 'PASS', $this->blackbox['winner'], $this->application_id);
			//force to tier 2 since tier 1 already denied these [TP]
			// tried switching this to the new SiteConfig and it didn't carry over
			// to the new blackbox [TP]
			$this->config->limits->accept_level = 2;
			$this->blackbox_obj = $this->Configure_Blackbox();

			if ($this->blackbox_obj)
			{
				$this->blackbox_obj->pickWinner();
				$this->blackbox = $this->blackbox_obj->winner();
				$_SESSION['blackbox'] = $this->blackbox;
			}
			else
			{
				// we had an error, just act like it denied them?
				$this->blackbox = array('denied'=>TRUE);
			}

			$this->Process_Winner();

	}



	/**
		@privatesection
		@private
		@fn void BlackBox_Confirm_Lead()
		@brief
			BlackBox_Confirm_Lead

		@desc Run Current Page Cases for: BlackBox_Confirm_Lead
	*/
	private function BlackBox_Confirm_Lead()
	{

		// hack to see if they've refreshed the page
		$refreshed = (!array_key_exists('blackbox_asleep', $_SESSION));

		if ($this->collected_data['sell']=='YES')
		{

			if (!$refreshed)
			{

				$this->Applog_Write('Waking up BlackBox...');

				// start timer
				// $timer = new Timer($this->applog);
				// $timer->Timer_Start('BLACKBOX_WAKEUP');

				// wake BlackBox up and retrieve our current winner
				$this->blackbox_obj = $this->Configure_Blackbox($_SESSION['blackbox_asleep']);

				if ($this->blackbox_obj)
				{

					$this->blackbox = $this->blackbox_obj->winner();

					// log confirm pass
					$this->event->Log_Event('CONFIRM_LEAD', 'PASS', $this->blackbox['winner'], $this->application_id);

				}
				else
				{
					// we had an error, just act like it denied them?
					$this->blackbox = array('denied'=>TRUE);
				}

				// stop timer
				// $timer->Timer_Stop('BLACKBOX_WAKEUP');
				// $time = $timer->Get_Elapsed('BLACKBOX_WAKEUP');

				// sell the guy
				$this->Process_Winner();

			}
			else
			{

				if (array_key_exists('winner', $_SESSION['blackbox']))
				{

					// they refreshed!
					$this->Refresh_Thank_You($this->Get_Application_ID(), $_SESSION['blackbox']['winner']);
					$this->next_page = 'bb_thanks';

				}
				else
				{
					// they were declined
					$this->next_page = 'app_declined';
				}

			}

		}
		else
		{
			if (!$refreshed)
			{
				// log confirm fail
				$this->event->Log_Event('CONFIRM_LEAD', 'FAIL', NULL, $this->application_id);
			}

			// they declined, so we deny them!
			$this->blackbox = array('denied'=>TRUE);
			$_SESSION['blackbox'] = $this->blackbox;

			// this will send them to the denied page
			$this->Process_Winner();

		}

	}

	/**
		@privatesection
		@private
		@fn void Customer_React_Optout()
		@brief
			Customer_React_Optout - Removal of customer from Reactivation process.

		@desc Run Current Page Cases for: Customer_React_optout
	*/

	private function Customer_React_Optout()
	{
					// Check for cell number or email
					if(!empty($this->collected_data['react_email']))
					{
						$email = $this->collected_data['react_email'];
						if (preg_match('/^[a-zA-Z0-9]+([.-]+[a-zA-Z0-9]+)*@([a-zA-Z0-9]+(-+[a-zA-Z0-9]+)*\.)+[a-zA-Z]{2,6}$/', $email))
						{
						    $message = "Email ($email) has been removed from our list.";
							$_SESSION['data']['message'] = $message;
							// Do Email-Based React Optout Here!
							if($this->React_Optout('email', $email))
							{
								$this->next_page = 'react_optout_thankyou';
							}
							else
							{
							    $this->next_page = 'react_optout';
							}
						}
						else
						{
							$message = "Email ($email) does not appear to be a valid email.";
						    $this->errors['message'] = $message;
							$this->current_page = 'react_optout';
						}
					}
					elseif($this->collected_data['react_number'] != '')
					{
					    $react_number = str_replace("-","",$this->collected_data['react_number']);
						if(preg_match('/[0-9]{10}/', $react_number))
						{
							$message = "(".substr($react_number, 0, 3).") ".substr($react_number, 3, 3)."-".substr($react_number, 6, 4)." has been removed from our list.";
							$_SESSION['data']['message'] = $message;
							// Do Number-Based React Optout Here!
							if($this->React_Optout('phone', $react_number))
							{
								$this->next_page = 'react_optout_thankyou';
							}
							else
							{
								$this->next_page = 'react_optout';
							}
						}
						else
						{
							$message = "$react_number does not appear to be a valid phone number.";
							$this->errors['message'] = $message;
							$this->current_page = 'react_optout';
						}
					}else{
					    $this->current_page = 'react_optout';
					}
	}
/**
		@privatesection
		@private
		@fn void React_Optout()
		@brief
			React_Optout - Removal of customer from Reactivation process.

		@desc Function to remove a customer from React process based on data provided
	*/
	private function React_Optout($type, $data)
	{
	    switch($type)
		{
	     case 'email':
	     $query = "select distinct a.application_id,
			c.name_short as property_short,
			a.track_id,
			ci.promo_id,
			a.application_status_id
  			from application a, company c
  				JOIN campaign_info ci ON ci.campaign_info_id = (
				SELECT MAX(ci.campaign_info_id) FROM campaign_info ci
	  			WHERE ci.application_id = a.application_id)
			WHERE a.company_id = c.company_id
			AND a.application_status_id = '109'
			AND a.email = '{$data}'";
	     break;

	     case 'phone':
	     $query = "select distinct a.application_id,
			c.name_short as property_short,
			a.track_id,
			ci.promo_id,
			a.application_status_id
  			from application a, company c
  				JOIN campaign_info ci ON ci.campaign_info_id = (
				SELECT MAX(ci.campaign_info_id) FROM campaign_info ci
	  			WHERE ci.application_id = a.application_id)
			WHERE a.company_id = c.company_id
			AND a.application_status_id = '109'
			AND a.phone_cell = '{$data}'";
	     break;
	    }

		try
		{
		    if(!$this->db)
			{
		        $this->Setup_DB($_SESSION["config"]->property_short);
		    }
		$result = $this->db->Query($query);
		$react = $result->Fetch_Array_Row();
		if(!$react['application_id'])
		{
			return FALSE;
		}
		$this->application_id = $react['application_id'];
		$this->Event_Log();
		Stats::Hit_Stats('react_optout', $this->session, $this->event, $this->applog, $this->application_id, NULL, TRUE);
		}
		catch(Exception $e)
		{
		    return false;
		}
	return true;
	}

	/**
		@privatesection
		@private
		@fn void Customer_Removal()
		@brief
			Customer_Removal

		@desc Run Current Page Cases for: Customer_Removal
	*/

	private function Customer_Removal()
	{
					// Check for sms_number or rmemail
					if($this->collected_data['remove_choice'] == "email")
					{
						$pds_response = $this->Remove_Email($this->collected_data['rmemail']);
						if (preg_match ('/does not appear to be a valid email/', $pds_response))
						{
							$this->errors['message'] = $pds_response;
							$this->current_page = 'cs_removeme';
						}
						else
						{
							$_SESSION['data']['message'] = $pds_response;
							$this->next_page = 'thanks_remove';
						}
					}
					elseif($this->collected_data['remove_choice'] == "cell_phone")
					{
						$pds_response = $this->Remove_SMS($this->collected_data['sms_number']);
						if(preg_match('/does not appear to be a valid phone number/', $pds_response))
						{
							$this->errors['message'] = $pds_response;
							$this->current_page = 'cs_removeme';
						}
						else
						{
							$_SESSION['data']['message'] = $pds_response;
							$this->next_page = 'thanks_remove';
						}
					}
	}

	/**
		@privatesection
		@private
		@fn void Customer_Decline()
		@brief
			Customer_Decline

		@desc Run Current Page Cases for: Customer_Decline
	*/
	private function Customer_Decline()
	{

		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);

		// I reorganize this because it was getting too confusing for regular apps/cs/and reacts rsk
		// Added the confirmed check because we should NOT be changing
		// an app to any status once it is already confirmed [NR]
		if (($_SESSION['blackbox']['winner'] || $_SESSION['bb_force_winner'] || $this->property_short) && !$_SESSION['cs']['confirmed'])
		{
			// set the property

			// regular app
			if ($_SESSION['blackbox']['winner'])
			{
				$property = $_SESSION['blackbox']['winner'];
			} // customer service or react
			// we should always have a winner at this point (esig/cs)
			elseif ($_SESSION['bb_force_winner'])
			{
				$property = $_SESSION['bb_force_winner'];
			}
			else // all other cases. may not need this
			{
				$property = $this->property_short;
			}
			if ($property)
			{
				// if in cust service set session transaction_id = cs transaction_id
				$_SESSION['transaction_id'] = isset($_SESSION['transaction_id']) ? $_SESSION['transaction_id'] : $_SESSION['cs']['transaction_id'];

				// connect to database
				$olp_db = $this->Setup_OLP_DB($property);

				// check if they're on the ent_confirm page in cust service and declined not ent_confirm_legal
				$ent_confirm = preg_match("/decline/i", $this->collected_data['submit']);

                //check if the cs array is in the session
                if(!isset($this->application_id) &&
                   !isset($_SESSION["cs"]["application_id"]) &&
                    isset($this->normalized_data["application_id"]) &&
                    $this->normalized_data["application_id"] != null)
                {
                    $app_id = $this->Get_Application_ID();

                    $p_short = (isset($_SESSION["config"]->bb_force_winner)) ? $_SESSION["config"]->bb_force_winner : $_SESSION["config"]->property_short;
                    $this->Setup_DB($p_short);
                    $ent = $this->Get_Ent_Cs($p_short);
                    $cs = $ent->Get_User_Data($app_id);
                    $_SESSION['cs'] = $cs['cs'];
                }

                //Check if event logger is set
                if($this->event == null)
                {
                	$this->Event_Log();
                }

                if ($ent_confirm)
				{
					$app_campaign_manager->Update_Application_Status($this->Get_Application_ID(), 'CONFIRMED_DISAGREED');
					if(!$this->config->use_new_process) $olp_db->Update_Application_Status('confirm_declined', $this->Get_Application_ID());
					$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_confirm">Back to the Loan Confirmation page</a>';
					return;
				}

				// update condor with disagree status
				$this->condor->Condor_Get_Docs('signature_response', 'FALSE', "");

				// check to see if they're in cust service
				if (isset($_SESSION['cs']))
				{
					if(isset($this->collected_data['legal_deny']))
					{
						$app_campaign_manager->Update_Application_Status($this->Get_Application_ID(), 'DISAGREED');
						if(!$this->config->use_new_process) $olp_db->Update_Application_Status('disagree', $this->Get_Application_ID());

						Stats::Hit_Stats( 'self_declined', $this->session, $this->event, $this->applog, $this->Get_Application_ID() );
					}
					// Old process
					else
					{
						$app_campaign_manager->Update_Application_Status($this->Get_Application_ID(), 'CONFIRMED_DISAGREED');
						if(!$this->config->use_new_process) $olp_db->Update_Application_Status('confirm_declined', $this->Get_Application_ID());
					}

					// reset other return value if they declined on main confirmation page first
					unset($_SESSION['cs']['return']);

					// New Online Confirmation process
					if($this->config->online_confirmation)
					{
						if($_SESSION['data']['submit'] == 'Cancel')
						{
							// They cancelled on the new confirm page
							Stats::Hit_Stats('cancel', $this->session, $this->event, $this->applog, $this->Get_Application_ID());
							$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_online_confirm">Back to the Confirmation page</a>';
						}
						else
						{
							$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_online_confirm_legal">Back to the Loan Acceptance & eSignature page</a>';
						}

						// Since it's checking for the submit = 'Cancel', unsetting it for now
						unset($_SESSION['data']['submit']);
					}
					// Old process
					else
					{
						$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_confirm_legal">Back to the Loan Acceptance & eSignature page</a>';
					}
				}
				elseif ($_SESSION['react'])
				{
					$app_campaign_manager->Update_Application_Status($this->application_id, 'DISAGREED');
					if(!$this->config->use_new_process) $olp_db->Update_Application_Status('confirm_declined', $this->Get_Application_ID());

					// hit the declined stats
					Stats::Hit_Stats( 'self_declined', $this->session, $this->event, $this->applog, $this->Get_Application_ID());
					$this->event->Log_Event("CONFIRM", "FAIL");

					$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_reapply_legal">Back to the Loan Application</a>';
				}
				else // regular application
				{
					if(!isset($this->application_id) || $this->application_id == null)
                    {
                        if(isset($_SESSION["application_id"]))
                        {
                        	$this->application_id = $_SESSION["application_id"];
                        }
                    }

					$app_campaign_manager->Update_Application_Status($this->Get_Application_ID(), 'DISAGREED');
					if(!$this->config->use_new_process) $olp_db->Update_Application_Status('disagree', $this->Get_Application_ID());

					// hit the declined stats
					Stats::Hit_Stats( 'self_declined', $this->session, $this->event, $this->applog, $this->Get_Application_ID() );
					$this->event->Log_Event("CONFIRM", "FAIL");

					$_SESSION['data']['return'] = '<a href="' . $_SESSION['data']['client_url_root'] . '?page=esig">Back to the Loan Application</a>';

				}
			}
		}

	}

	private function Ecash_React_Check($check_option)
	{
		$pass = TRUE;
		//if (strtoupper($_SESSION['config']->mode) != "LIVE" && isset($this->normalized_data['no_checks']))
		//	return $pass;
		switch ($check_option)
		{
			CASE "DISALLOWED_STATES":
				// Mantis #12117 - added in this to fix disallowed states comments.
				if (!Blackbox_Adapter::isNewBlackbox())
				{
					$blackbox = $this->Configure_BlackBox(null, null, MODE_ECASH_REACT);
				}

                if (in_array(strtoupper($_SESSION['data']['home_state']), $this->blackbox_obj->getDisallowedStates($_SESSION['data']['ecashapp'])))
				{
					$_SESSION['ECASH_REACT_ERROR'][] = "React changed state to disallowed state.";
					$pass = FALSE;
				}

				break;

			CASE "CASHLINE_CHECK":
				foreach ($_SESSION['CASHLINE_RESULTS'] as $cash_key => $cash_val)
				{
					// We are going going to denie them if they are bad
					if(in_array(strtolower($cash_val), array('denied', 'bad', 'overactive', 'underactive', 'do_not_loan')))
					{
						switch ($cash_val)
						{
							case 'bad':
								$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, $cash_key was bad.";
								break;
							case 'denied':
								$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, $cash_key had denied apps within the last 30 days.";
								break;
							case 'underactive':
								$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, active loan found with $cash_key.";
								break;
							case 'do_not_loan':
								$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, {$cash_key} was marked as do not loan.";
								break;
							default:
								$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, $cash_key was $cash_val.";
								break;
						}
						$pass = FALSE;
					}
				}
			CASE "SUPPRESSION_LIST":
				// If we denied by supression list report the reasons to ECash [RL]
				if(!empty($_SESSION['SUPPRESSION_LIST_FAILURE']))
				{
					foreach ($_SESSION['SUPPRESSION_LIST_FAILURE'] as $supp_key => $supp_val)
					{
						$_SESSION['ECASH_REACT_ERROR'][] = "React was denied failed $supp_key $supp_val list";
					}
				}
				break;
			CASE 'MINIMUM_INCOME':
				if($_SESSION['MINIMUM_INCOME_FAIL'])
				{
					$_SESSION['ECASH_REACT_ERROR'][] = "React was denied, Minimum Income not met";
					$pass=FALSE;
				}
		}

		return $pass;

	}
	/**
		@privatesection
		@private
		@fn void Email_React_Denial()
		@brief
			Email_React_Denial

		@desc Denail Emails for ECash React: Email_React_Denial
		@todo Need to make sure the denial emails are being sent to the correct
			destinations.
	*/
	private function Email_React_Denial($failed_datax = FALSE)
	{

		// We need to make sure we dont send multiple copies
		// (Failed DataX will send an email so we dont want to send an additional one
		if(!$_SESSION["Email_React_Denial"])
		{
					$template = ($failed_datax) ? array("OLP_ECASH_REACT_DENY_TELETRACK",17176)
									: array("OLP_ECASH_REACT_DENY",17176);


					$header["site_name"] = $this->config->site_name;
					$header["sender_name"] = "Contact Us Post <cs@".$this->config->site_name.">";
					$header["Today"] = date("m/d/Y");
					$header["CustomerName"] = $_SESSION['data']['name_first']." ".$_SESSION['data']['name_last'];
					$header["CustomerAddress"] = $_SESSION['data']['home_street'];
					$header["CustomerCity"] = $_SESSION['data']['home_city'];
					$header["CustomerState"] = $_SESSION['data']['home_state'];
					$header["CustomerZip"] = $_SESSION['data']['home_zip'];
					$header['application_id'] = $this->Get_Application_ID();

					$email = $_SESSION['data']['email_primary'];

					$recipient = array(
						"email_primary_name" => $email,
						"email_primary" => $email,
					);
					$data = array_merge($recipient, $header);

					require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
					$tx = new OlpTxMailClient(false);
					try 
					{
						$result = $tx->sendMessage('live',$template[0],
							$data['email_primary'],'',$data);
							
					}
					catch (Exception $e)
					{
						$this->applog->Write(
							"Trendex mail {$template[0]} failed. ".
								$e->getMessage()." (App ID: ". 
								$this->Get_Application_ID() . ")");

					}

					// If this is ecashapp, then we need to insert comments on why it failed
					if($_SESSION['data']['ecashapp'] || $_SESSION['data']['reactforce'])
					{
						$property = ($_SESSION['data']['ecashapp']) ? $_SESSION['data']['ecashapp'] : $_SESSION['data']['reactforce'];


						if($this->config->use_new_process)
						{
							$_SESSION['ldb_data'][$this->application_id]['comments'][] = array(
								'property_short' => $property,
								'application_id' => $this->application_id,
								'type' => 'declined',
								'comment' => 'Agent React Application Denied.',
								'agent_id' => $_SESSION['data']['agent_id']
							);
						}
						else
						{
							$olp_db = $this->Setup_OLP_DB($property);

							// Updating the ldb comment table of the denied application
							$comment['property_short'] = $property;
							$comment['application_id'] = $this->application_id;
							$comment['type'] = "declined";
							$comment['comment'] = "Agent React Application Denied.";
							$olp_db->Insert_Comment($comment, $_SESSION['data']['agent_id']);
						}

						// Add more reasons
						foreach($_SESSION['ECASH_REACT_ERROR'] as $comment['comment'])
						{
							if($this->config->use_new_process)
							{
								$_SESSION['ldb_data'][$this->application_id]['comments'][] = array(
									'property_short' => $property,
									'application_id' => $this->application_id,
									'type' => 'declined',
									'comment' => $comment['comment'],
									'agent_id' => $_SESSION['data']['agent_id']
								);
							}
							else
							{
								$olp_db->Insert_Comment($comment, $_SESSION['data']['agent_id']);
							}
						}
					}

					$_SESSION["Email_React_Denial"] = TRUE;
		}

	}
	/**
		@privatesection
		@private
		@fn void Next_Page_Case_Ent_Confirm_Legal()
		@brief
			Next_Page_Case_Ent_Confirm_Legal

		@desc Next_Page_Case for: Next_Page_Case_Ent_Confirm_Legal
	*/
	private function Next_Page_Case_Ent_Confirm_Legal()
	{
				// refresh on the ent_confirm_legal page so just pull condor paperless_form and break
				if ( !$this->normalized_data['legal_agree'] &&  !$this->normalized_data['legal_deny'] && $_SESSION['condor']->signature_id)
				{
					// need to get the data from cs to pass in
					$data = array();
					$data['config']->legal_entity = $this->ent_prop_list[ strtoupper($this->property_short) ]["legal_entity"];
					$data['config']->site_name = $_SESSION['config']->site_name;
					// get the esig doc or write app log
					$data['data']['qualify_info'] = $_SESSION['cs']['qualify'];
					if (!$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $data))
					{
						$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
						$this->event->Log_Event('CONDOR_PREVIEW', 'FAIL');
					}

				}
				else
				{
					// not a refresh so get condor docs etc
					// instantiate ent_cs class
					$ent_cs = $this->Get_Ent_Cs($this->property_short);
					// pulling condor data from session so we don't need to prepare it
					$data = $ent_cs->Prepare_Condor_Data($_SESSION['cs']['transaction_id']);
					if (empty($data['data']['name_first']))
					{
						$this->Applog_Write("Prepare_Condor_Data call to Get_The_Kitchen_Sink failed (2) transaction_id: ".$_SESSION['cs']['transaction_id'].", session_id = ".session_id());
					}
					$data['config'] = clone $_SESSION['config'];
					// set legal_entity & fax number
					$data['config']->legal_entity = $this->ent_prop_list[ $this->property_short ]["legal_entity"];
					$data['config']->support_fax = $this->ent_prop_list[ $this->property_short ]["fax"];

					// application_content needs transaction id set
					$data['application_id'] = $this->Get_Application_ID();
					$data['config']->property_short = strtoupper($this->property_short);

					// unset stuff we don't need to pass in
					unset($data['config']->site_type_obj);
					unset($data['data']['client_state']);

					// pull up esig page
					// uses generic paperless app - pass in data to parse

					// paperless_form is the loan disclosure on the esig page
					$this->esig_doc = $this->condor->Preview_Docs("paperless_form", $data);
					if (!$this->esig_doc)
					{
						$this->applog->Write("app_id: ".$this->application_id." - Condor Preview Docs failed" );
					}

					// returns array with condor session data and legal doc
					// pass in document, legal status, data to merge onto app, prop type
					try
					{
						$this->condor->Condor_Get_Docs('signature_request', "", $data);
					}
					catch (Exception $e)
					{
						throw $e;
					}
					// need this for the next step when they agree to the docs
					$_SESSION['condor'] = $this->condor->response;
					// unset legal_content and legal_page so we don't overload the session
					unset($_SESSION['condor']->data);
				}
	}

	/**
	 * Performs the steps for a Coreg Application. Inserts the information as
	 * an application and hits the coreg_pass stat. Nirvana should then
	 * pick up the hit stat and email out the initial auto response email.
	 */
	private function Process_Coreg_Application()
	{
		$ignore_errors = true;

		$coreg_fields = array(
			'name_first',
			'name_last',
			'ssn_part_3',
			'dob',
			'home_street',
			'home_city',
			'home_state',
			'home_zip',
			'email_primary',
			'phone_home',
			'phone_work',
			'dep_account',
			'monthly_1200',
			'employer_length'
		);

		// Check for errors
		if(is_array($this->errors))
		{
			foreach($coreg_fields as $field)
			{
				if(in_array($field, $this->errors))
				{
					$invalid_fields[] = $field;
					$ignore_errors = false;
				}
			}

			if(!$ignore_errors)
			{
				// This is a rejected app due to invalid data
				$_SESSION['data']['coreg_xml'] = $this->Generate_Coreg_Xml('Rejected', 'Invalid data passed', $invalid_fields, null);
				Stats::Hit_Stats('coreg_fail', $this->session, $this->event, $this->applog);

				/*
					We don't save the failed information here because we haven't even created an application
				 	at this time.
				*/
			}

			// If we didn't find anything, then we don't care about any other errors
			$this->errors = null;
		}

		if($ignore_errors)
		{
			// None of the errors are fields we care about

			// Need this to set the track_id
			// NOTE: The process has been changed.  This stat will now be hit
			//  when the user lands on the homepage. - NR
			// Stats::Hit_Stats('visitor', $this->session, $this->event, $this->applog);

			// Override the client IP address, we'll get the IP from the Coreg site.
			$_SESSION['data']['client_ip_address'] = $this->collected_data['user_ip_addr'];

			// This originally overwrote the config site name, instead, we're going to save this in the
			// session. Overwriting it in the config is b-a-d.
			$_SERVER['data']['coreg_site_url'] = $this->collected_data['coreg_site_url'];

            //Create the statpro track_key because it has not been created at this point
            if(!isset($_SESSION['statpro']) || !isset($_SESSION['statpro']['track_key']))
            {
                if(!isset($_SESSION['statpro'])) $_SESSION['statpro'] = array();

                $statpro_key = 'clk';
                $statpro_pass = 'dfbb7d578d6ca1c136304c845';

                $bin = '/opt/statpro/bin/spc_' . $statpro_key . '_' .
                	(strtoupper($this->config->mode) !== 'LIVE') ? 'test' : 'live';
                $statpro = new StatPro_Client($bin, NULL, $statpro_key, $statpro_pass);
                $_SESSION['statpro']['track_key'] = $statpro->Track_Key();
            }

			$this->Create_Application();

			// Insert additional information into the application table
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Insert_Application($this->application_id, $this->normalized_data, $this->title_loan);

			// Setup event handler
			$this->Event_Log();

			$result = false;

			// Post to the target that was passed in
			if(isset($this->config->coreg_target))
			{
				$target = $this->config->coreg_target;
				$result = $this->Post_To_Winner(strtolower($target));
			}
			else // Just run the blackbox prequal
			{
				$result = $this->BlackBox_Prequal();
			}

			if($result)
			{
				if(isset($target))
				{
					// If we went to a specific target, we hit this stat so that we don't
					// send out the auto responder email
					Stats::Hit_Stats(
						'coreg_'.$target.'_pass',
						$this->session,
						$this->event,
						$this->applog,
						$this->application_id);
				}
				else
				{
					// Send the auto responder email
					Stats::Hit_Stats(
						'coreg_pass',
						$this->session,
						$this->event,
						$this->applog,
						$this->application_id);
				}
				$_SESSION['data']['coreg_xml'] = $this->Generate_Coreg_Xml(
					'Accepted',
					'Application accepted',
					null,
					$this->application_id);

				// Save the information
				$app_campaign_manager->Insert_Coreg_Response(
					$this->application_id,
					'coreg',
					$this->normalized_data,
					$_SESSION['data']['coreg_xml'],
					App_Campaign_Manager::COREG_SUCCESS_TRUE);
			}
			else
			{
				Stats::Hit_Stats(
					'coreg_fail',
					$this->session,
					$this->event,
					$this->applog,
					$this->application_id);
				$_SESSION['data']['coreg_xml'] = $this->Generate_Coreg_Xml(
					'Rejected',
					'Reject decision',
					null,
					$this->application_id);

				// Save the failed information
				$app_campaign_manager->Insert_Coreg_Response(
					$this->application_id,
					'coreg',
					$this->normalized_data,
					$_SESSION['data']['coreg_xml'],
					App_Campaign_Manager::COREG_SUCCESS_FALSE);
			}
		}

		// This won't really matter, but set them anyway
		$this->current_page = 'try_again';
		$this->next_page = 'try_again';
	}

	private function Process_EGC_Coreg_Application()
	{
		/*
		 * call an http post process to submit the coreg info in real time.
		 * If accepted, log it into the dataabase and continue
		 * If not accepted, pass it through the normal co-reg process for nightly batch
		 */

		$coreg_succeeded = $this->Send_EGC_Coreg_Post();
	}

	/**
	 * Generates the XML message for Coreg sites. Returns the XML string on success,
	 * false otherwise.
	 *
	 * @param string $status
	 * @param string $message
	 * @param array[optional] $invalid_fields
	 * @param string[optional] $app_id
	 * @return string
	 */
	private function Generate_Coreg_Xml($status, $message, $invalid_fields = null, $app_id = null)
	{
		$coreg_xml = false;

		if(is_string($status) && is_string($message))
		{
			$dom = new DOMDocument();
			$root_element = $dom->createElement('application');
			$dom->appendChild($root_element);

			// TEMP: For debugging...
			if($app_id != null)
			{
				$element = $dom->createElement('id', $app_id);
				$root_element->appendChild($element);
			}
			// END TEMP

			$element = $dom->createElement('status', $status);
			$root_element->appendChild($element);

			$detail_element = $dom->createElement('detail');
			$root_element->appendChild($detail_element);

			$message_element = $dom->createElement('message', $message);
			$detail_element->appendChild($message_element);

			if($invalid_fields != null)
			{
				foreach($invalid_fields as $field)
				{
					$element = $dom->createElement('invalid_field', $field);
					$detail_element->appendChild($element);
				}
			}

			$coreg_xml = $dom->saveXML();
		}

		return $coreg_xml;
	}


	private function Send_EGC_Coreg_Post()
	{
		include_once( BLACKBOX_DIR . 'http_client.php' );

		$hc = new Http_Client();

		if ($this->config->mode == "LIVE")
		{
			$url =  "https://expressgoldcard.com/index.php";
		}
		else
		{
			$url =  "http://rc.expressgoldcard.com/index.php";
		}

		$account_type = ($this->normalized_data['bank_account_type'] == 'CHECKING') ? 'C' : 'S';
		$active_checking = ($this->normalized_data['checking_account'] == 'TRUE' || $account_type == 'C') ? 'Y' : 'N';

		$dow = array(0 => 'SUN', 1 => 'MON', 2 => 'TUE', 3 => 'WED', 4 => 'THU', 5 => 'FRI', 6 => 'SAT', 7 => 'SUN');
		// Build the array
		$coreg_array = array(
			'referrer_id'				=> $this->application_id,
			'promo_id' 					=> $this->config->coreg_egc_promoid,
			'promo_sub_code' 			=> $_SESSION['config']->site_name,
			'referrer_promo_id'			=> $this->config->promo_id,
			'referrer_promo_sub_code'	=> $this->config->promo_sub_code,
			'name_first' 				=> ($this->normalized_data['name_first']) ? $this->normalized_data['name_first'] : $_SESSION['data']['name_first'],
			'name_last' 				=> ($this->normalized_data['name_last']) ? $this->normalized_data['name_last'] : $_SESSION['data']['name_last'],
			'address_1' 				=> ($this->normalized_data['home_street']) ? $this->normalized_data['home_street'] : $_SESSION['data']['home_street'],
			'address_2' 				=> '',
			'city' 						=> ($this->normalized_data['home_city']) ? $this->normalized_data['home_city'] : $_SESSION['data']['home_city'],
			'state' 					=> ($this->normalized_data['home_state']) ? $this->normalized_data['home_state'] : $_SESSION['data']['home_state'],
			'zip' 						=> ($this->normalized_data['home_zip']) ? $this->normalized_data['home_zip'] : $_SESSION['data']['home_zip'],
			'phone_home' 				=> ($this->normalized_data['phone_home']) ? $this->normalized_data['phone_home'] : $_SESSION['data']['phone_home'],
			'phone_work' 				=> ($this->normalized_data['phone_work']) ? $this->normalized_data['phone_work'] : $_SESSION['data']['phone_work'],
			'email' 					=> ($this->normalized_data['email_primary']) ? $this->normalized_data['email_primary'] : $_SESSION['data']['email_primary'],
			'checking_account_yes_no' 	=> $active_checking,
			'bank_name' 				=> ($this->normalized_data['bank_name']) ? $this->normalized_data['bank_name'] : $_SESSION['data']['bank_name'],
			'routing_number' 			=> ($this->normalized_data['bank_aba']) ? $this->normalized_data['bank_aba'] : $_SESSION['data']['bank_aba'],
			'account_number' 			=> ($this->normalized_data['bank_account']) ? $this->normalized_data['bank_account'] : $_SESSION['data']['bank_account'],
			'account_type' 				=> $account_type,
			'social_security_number' 	=> ($this->normalized_data['social_security_number']) ? $this->normalized_data['social_security_number'] : $_SESSION['data']['social_security_number'],
			'dob_month' 				=> ($this->normalized_data['date_dob_m']) ? $this->normalized_data['date_dob_m'] : $_SESSION['data']['date_dob_m'],
			'dob_day' 					=> ($this->normalized_data['date_dob_d']) ? $this->normalized_data['date_dob_d'] : $_SESSION['data']['date_dob_d'],
			'dob_year' 					=> ($this->normalized_data['date_dob_y']) ? $this->normalized_data['date_dob_y'] : $_SESSION['data']['date_dob_y'],
			'process_page' 				=> 'cash_site_coreg',
			'referrer_url'				=> $_SESSION['config']->site_name,
			'idv_pass' 					=> ($_SESSION['blackbox']['datax_decision']['DATAX_IDV_REWORK'])
											? $_SESSION['blackbox']['datax_decision']['DATAX_IDV_REWORK']
											: $_SESSION['blackbox']['datax_decision']['DATAX_IDV'],
			'paydate_model' 			=> $this->normalized_data["paydate_model"]["model_name"],
			'day_of_week' 				=> ($this->normalized_data["paydate_model"]["day_of_week"])
											? $dow[$this->normalized_data["paydate_model"]["day_of_week"]]
											: $this->normalized_data["paydate_model"]["day_string_one"],
			'next_paydate'				=> $this->normalized_data["paydate_model"]["next_pay_date"],
			'day_of_month_1' 			=> $this->normalized_data["paydate_model"]["day_int_one"],
			'day_of_month_2' 			=> $this->normalized_data["paydate_model"]["day_int_two"],
			'week_1' 					=> $this->normalized_data["paydate_model"]["week_one"],
			'week_2' 					=> $this->normalized_data["paydate_model"]["week_two"],
			'income_direct_deposit' 	=> ($this->normalized_data['income_direct_deposit'] == 'TRUE') ? 'yes' : 'no',
			'income_frequency' 			=> ($this->normalized_data['qualify_info']['net_pay'] == 0)
											? $this->normalized_data['qualify_info']['net_pay']
											: $this->normalized_data["paydate_model"]["income_frequency"],
			'phone_mobile'              => ($this->normalized_data['phone_cell']) ? $this->normalized_data['phone_cell'] : $_SESSION['data']['phone_cell']
		);
		
		if (isset(SiteConfig::getInstance()->coreg_egc_programid))
		{
			$coreg_array['program_id'] = SiteConfig::getInstance()->coreg_egc_programid;
		}

		$response = $hc->Http_Post($url, $coreg_array);

		$response_ser = serialize($response);
		$coreg_array_ser = serialize($coreg_array);
		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		if ($response)
		{
			//********************************************* 
			// GForge 7447 [AuMa]
			// This was added so QA can see the events fire
			// on Live and RC
			//********************************************* 
            $this->event->Log_Event('EGC_OPTIN_' . SiteConfig::getInstance()->coreg_egc_promoid, 
                               'PASS', NULL, $this->application_id);

			$app_campaign_manager->Insert_Coreg_Response(
				$this->application_id,
				'egc',
				$response_ser,
				$coreg_array_ser,
				App_Campaign_Manager::COREG_SUCCESS_TRUE);
		}
		else 
		{
			//********************************************* 
			// GForge 7447 [AuMa]
			// This was added so QA can see the events fire
			// on Live and RC
			//********************************************* 
            $this->event->Log_Event('EGC_OPTIN_' . SiteConfig::getInstance()->coreg_egc_promoid, 
                               'FAIL', NULL, $this->application_id);
		}

		return $response;
	}


	/**
	 * Generates the information for the Online Confirmation thank you winner page.
	 *
	 * @param string $target CLK property short
	 */
	private function Get_Online_Confirm_Thanks($target)
	{
		$alias = NULL;
		$alias_promo = NULL;

		if(Enterprise_Data::isAlias($target))
		{
			$alias = $target;
			$alias_promo = Enterprise_Data::getAliasPromo($target);
		}

		$target = strtolower(Enterprise_Data::resolveAlias($target));

		$site_name = $this->ent_prop_list[strtoupper($target)]['site_name'];

		$login_hash = md5($this->Get_Application_ID() . 'l04ns');

		$prefix = ($this->config->mode == "RC") ? ( preg_match("/^demo\./i", $_SERVER['SERVER_NAME']) ? "demo." : "rc." ) : "";

		if ($this->config->mode == "LOCAL")
		{
			// changed to use ic.1. instead of $target.1. for ic campaigns as both ic and ic_t1 use ic.1. GForge #3034 [DW]
			if($this->Is_Impact($target))
			{
				$prefix = "ic.1.";
			}
			elseif($this->ent_prop_list[strtoupper($target)]['new_ent'])
			{
				$prefix = "ent.1.";
			}
			else
			{
			 	$prefix = "$target.3.";
			}
			$suffix = "." . BFW_LOCAL_NAME . ".tss";

		}

		$encoded_app_id = urlencode(base64_encode($this->application_id));

		if($this->Is_Agean($target))
		{
			$site_id = $this->ent_prop_list[strtoupper($target)]['site_id'];
			$login_hash = md5($this->application_id . $site_id . 'L08N54M3');
			$url = "http://{$site_name}/LoanPage.aspx?applicationid={$encoded_app_id}&login={$login_hash}";
		}
		elseif(!$this->Is_Impact($target))
		{
			$http = (BFW_MODE == 'RC') ? 'http' : 'https';
			$url = "{$http}://{$prefix}{$site_name}{$suffix}/?application_id={$encoded_app_id}&page=ent_cs_login&login=$login_hash&ecvt&force_new_session";
		}
		else
		{
			$url = "http://{$prefix}{$site_name}{$suffix}/?application_id={$encoded_app_id}&page=ent_cs_login&login=$login_hash&ecvt&force_new_session";
		}

		if(!empty($alias_promo))
		{
			$url .= "&promo_override&promo_id={$alias_promo}";
		}

		if (isset($_SESSION['data']['pwadvid'])) $url .= '&pwadvid='.$_SESSION['data']['pwadvid'];

		// If Partner Weekly's pubtransid exists, tack it on to the redirect URL. [BF]
		if(isset($_SESSION['data']['pubtransid'])) $url .= '&pubtransid='.$_SESSION['data']['pubtransid'];

		if(isset($_SESSION['data']['no_checks']) && (strtolower(BFW_MODE) == "rc" || strtolower(BFW_MODE) == "local"))
		{
			$url.= "&no_checks";
		}

		if(isset($_SESSION['ecashnewapp']))
		{
			$url .= '&react_confirm=1';
		}

		// modify redirect for soap_oc sites
		if ($_SESSION['config']->site_type == 'soap_oc' )
		{
			$url .= '&soap_oc=1';

			$_SESSION["data"]["redirected_to"] = $url;
			$_SESSION["data"]["redirect_start_time"] = time();

			if(strtolower(BFW_MODE) == "local")
			{
				$url = 'http://pcl.3.easycashcrew.com.' . BFW_LOCAL_NAME . '.tss/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
			}
			elseif(strtolower(BFW_MODE) == "rc")
			{
				$url = 'http://rc.easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
			}
			else
			{
				$url = 'https://easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
			}
		}

		// Didn't want to put it in the code. Reason being we still pass the other redirect code
		// with the !tier1 vendors. Will need to come back and fix that so they both use the same
		// redirect code.
		$_tmp['redirect_time'] = Abstract_Vendor_Post_Implementation::REDIRECT; // used by next statement only.
		$redirect = <<<REDIRECT_JAVASCRIPT
<script type="text/javascript">
var script_expression = "document.location.href = '$url'";
var msecs = {$_tmp['redirect_time']} * 1000;
setTimeout(script_expression, msecs);
</script>
REDIRECT_JAVASCRIPT;

		$_SESSION['data']['online_confirm_winner'] = $target;
		$_SESSION['data']['online_confirm_redirect_url'] = $url;
		$_SESSION['data']['redirect_time'] = $redirect;

		if(!empty($alias))
		{
			$_SESSION['data']['online_confirm_alias'] = $alias;
		}
	}

	private function Is_Impact($property = NULL)
	{
		return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $property);
	}

	private function Is_CLK($property = NULL)
	{
		return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $property);
	}

	/*private function Is_CompuCredit($property = NULL) //Replaced by Is_Enterprise
	{
		if(is_null($property))
		{
			$property = (isset($this->config->bb_force_winner)) ? $this->config->bb_force_winner : '';
		}

		return in_array(strtolower($property), $this->compucredit_properties);
	}*/

	private function Is_Enterprise($target)//Mantis 12109 [MJ]
	{
		return Enterprise_Data::isEnterprise($target);
	}

	private function Is_CFE($target)
	{
		return Enterprise_Data::isCFE($target);
	}

	private function Is_Agean($property = NULL)
	{
		return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $property);
	}

	private function Is_Agean_Site()
	{
		return (strcasecmp($this->config->property_name, 'Agean') === 0 || isset(SiteConfig::getInstance()->is_agean_site));
	}

	private function Is_Ecash3($property)
	{
		return $this->Is_Enterprise($property);
	}

	/**
	 * Check for generic enterprise site (eCash3) GFORGE_3981 [TF]
	 *
	 */
	private function Is_Entgen($property=NULL)
	{
		return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_GENERIC, $property);
	}

	private function Bypass_Limits($property)
	{
		$result = false;

		if(SiteConfig::getInstance()->bypass_limits && !empty(SiteConfig::getInstance()->bb_force_winner))
		{
			$bb_force_winner = array_map('trim', explode(',', SiteConfig::getInstance()->bb_force_winner));
			$result = in_array(strtolower($property), $bb_force_winner);
		}

		return $result;
	}


	private function Document_Event($property_short)
	{
		if (!$_SESSION['document_event'])
		{
			try
			{

				if($this->config->use_new_process)
				{
					if ($this->Is_Ecash3($this->ent_prop_short_list[$_SESSION['config']->site_name]))
					{
						$app_campaign_manager = new App_Campaign_Manager(
													$this->sql,
													$this->database,
													$this->applog);

						$app_campaign_manager->Document_Event($this->Get_Application_ID(),
															  $_SESSION['condor_data']['archive_id']);
					}
				}
				else
				{
					$olp_db = $this->Setup_OLP_DB($property_short);

					//Did someone say 'hack'?  If connected, sqlstate should return '00000', if the connection fails,
					//it will return null.  I just don't want a bunch of applog exceptions when LDB goes kaput.
					if(!is_null($this->db->Get_Link()->sqlstate))
					{
						$olp_db->Document_Event($this->Get_Application_ID(), $property_short);
					}

					// unset db
					unset($this->db);
				}

				// set document_event flag
				$_SESSION['document_event'] = TRUE;

			}
			catch (MYSQL_Exception $e)
			{
				$this->Applog_Write("app_id: ".$this->application_id . " - Document_Event failed  session: " .  session_id() );
			}

		}
	}

	private function Set_Datran_Property($site_name)
	{
		switch (strtolower($site_name))
		{
			case "123onlinecash.com":
			case "1500payday.com":
			case "americash-fastcash.com":
			case "americash-online.com":
			case "bestcashsource.com":
			case "cashloanweb.com":
			case "directdepositpayday.com":
			case "fast-funds-online.com":
			case "fastcash1.com":
			case "fastcashnow.com":
			case "greenpayday.com":
			case "moneyinaclick.com":
			case "mycashcentral.com":
//			case "nationalfastcash.com": Disabled at request of Mel Leonard
//			case "paydayangels.com":	disabled at business owners request
			case "paydaytrust.com":
			case "quickpayrollapp.com":
			case "rapidcashproviderapp.com":
			case "rapidmoneyloan.com":
			case "seasonalcashadvance.com":
			case "starpayday.com":
			case "yourfastcash.com":

			// Task #8713 additions [CB]
			case 'nofaxcashnow.com':
			case 'northcash.com':
			case 'hourpayday.com':
			case 'universalpayday.com':
			case 'americashbank.com':
			case 'americash-fastcash.com':
			case 'americash-usa.com':
			case 'fastcashusa.com':

			return TRUE;
		}

		return FALSE;
	}
	
	/** Convert any integer or base64 integer into an integer.
	 *
	 * @param mixed $number An integer or base64 encoded integer.
	 * @return int The numeric value, or FALSE if not an integer.
	 */
	protected function getIntorBase64($number)
	{
		if (!is_numeric($number))
		{
			$number = base64_decode($number);
			if (!is_numeric($number))
			{
				$number = FALSE;
			}
		}
		
		return $number;
	}

	/**
	 * Get Application ID
	 *
	 * This function will look for an application id and 
	 * return it. It is meant to replace manually looking
	 * for the app id in the session
	 *
	 * GForge #6906 - App ID may be in session as base64. [RM]
	 *
	 * @return int Application ID/false on failure
	 */
	private function Get_Application_ID()
	{
		$app_id = FALSE;
		
		(isset($this->application_id) && $app_id = $this->getIntorBase64($this->application_id))
		|| (isset($this->normalized_data['application_id']) && $app_id = $this->getIntorBase64($this->normalized_data['application_id']))
		|| (isset($_SESSION['application_id']) && $app_id = $this->getIntorBase64($_SESSION['application_id']))
		|| (isset($_SESSION['cs']['application_id']) && $app_id = $this->getIntorBase64($_SESSION['cs']['application_id']))
		|| (isset($_SESSION['transaction_id']) && $app_id = $this->getIntorBase64($_SESSION['transaction_id']))
		|| (isset($this->collected_data['application_id']) && $app_id = $this->getIntorBase64($this->collected_data['application_id']))
		|| (isset($_SESSION['data']['application_id']) && $app_id = $this->getIntorBase64($_SESSION['data']['application_id']))
		;
		
		return $app_id;
	}
	
	/** Returns the application id to use for Condor. This may not be the
	 * correct application id for Condor, but at least it allows it to be
	 * consistent.
	 *
	 * @return int AppID for Condor. Else, FALSE.
	 */
	protected function getCondorApplicationID()
	{
		$app_id = FALSE;
		
		(isset($_SESSION['cs']['transaction_id']) && $app_id = $this->getIntorBase64($_SESSION['cs']['transaction_id']))
		|| (isset($_SESSION['transaction_id']) && $app_id = $this->getIntorBase64($_SESSION['transaction_id']))
		|| ($app_id = $this->Get_Application_ID())
		;
		
		return $app_id;
	}

	// Genereate Condor docs and store data in $_SESSION['condor_data']
	private function Generate_Condor_Docs()
	{
		$application_id = $this->getCondorApplicationID();
		$property_short = $this->ent_prop_short_list[$_SESSION['config']->site_name];
		if ($this->Is_Ecash3($property_short))
		{
			require_once(BFW_CODE_DIR.'condor_display.class.php');
			require_once ("prpc/client.php");

			//  Mantis #12161 - added in the check for card loan or standard loan for docs.	[RV]
			$condor_template = ($_SESSION['data']['loan_type'] == 'card' || $_SESSION['cs']['loan_type'] == 'card') ? "Card Loan Document" : "Loan Document";
			$condor_display = new Condor_Display();

			if(empty($_SESSION['data']['ecash_sign_docs']))
			{
				$token_data = $condor_display->Generate_Condor_Tokens();
			}
			else
			{
				$ent_cs = $this->Get_Ent_Cs($property_short);
				$token_data = $condor_display->Rename_Tokens($ent_cs->Prepare_Condor_Data($application_id));
			}

			$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $property_short);
			$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");

			/*
				We need to pass in the track and space key so that Condor can hit stats
				associated with this document/application.
			*/
			$_SESSION['condor_data'] = $condor_api->Create(
				$condor_template,					// Template
				$token_data,						// Data
				TRUE,								// Archive
				$application_id,		// Application ID
				$_SESSION['statpro']['track_key'],	// Track key
				$_SESSION['statpro']['space_key']	// Space key
			);

			if(!isset($_SESSION['data']['ecash_sign_docs']))
			{
				$this->Document_Event($property_short);
			}
		}
	}

	// Genereate Condor docs and store data in $_SESSION['condor_data']
	private function Sign_Condor_Docs()
	{
		$property_short = $this->ent_prop_short_list[$_SESSION['config']->site_name];

		if($this->Is_Ecash3($property_short))
		{
			require_once ("prpc/client.php");
			$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $property_short);
			$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");

			$archive_id = (!empty($_SESSION['condor_data']['archive_id'])) ? $_SESSION['condor_data']['archive_id'] : $this->Find_Loan_Document();

			if($archive_id !== FALSE)
			{
				$_SESSION['condor_data']['document']->signed = $condor_api->Sign($archive_id, $_SESSION['condor_data']['document']->data);
				$_SESSION['condor_doc_signed'] = TRUE;

				if(!$this->config->use_new_process)
				{
					// We want to run the documents again in this case (it will only update the Loan Docs)
					unset($_SESSION['document_event']);
					$this->Document_Event($property_short);
				}
			}
		}
	}

	/**
	 * Posts an exit strategy coreg offer.  You will need to define
	 * individual offers here, based on the ID that's created inside
	 * of webadmin1 for them.
	 *
	 * @param array $data Exit strategy post data
	 * @param bool $force_run Force the exit strategy to run even if it's not set in the config.
	 */
	private function postExitStrategyCoregOffer($data, $force_run = FALSE)
	{
		if (!defined('USE_MONEYHELPER') || USE_MONEYHELPER === TRUE)
		{
			foreach ($data as $id => $result)
			{		
			
				if ((isset(SiteConfig::getInstance()->exit_strategy[$id]) || $force_run))
				{
					switch($id) 
					{
						case 34: // Auto Loan - PW Arb
							Stats::Hit_Stats(
								'pw_arb_auto_loan', 	
								$this->session, 
								$this->event, 
								$this->applog, 
								$this->Get_Application_ID(), 
								NULL, 
								TRUE);
							break;
						case 35: // EdebitPay - PW Arb
							Stats::Hit_Stats(
								'pw_arb_edebit_pay', 
								$this->session, 
								$this->event, 
								$this->applog, 
								$this->Get_Application_ID(), 
								NULL, 
								TRUE);
							break;
						case 32: // 1st Credit Now - PW Arb
							Stats::Hit_Stats(
								'pw_arb_1st_credit_now', 
								$this->session, 
								$this->event, 
								$this->applog, 
								$this->Get_Application_ID(), 
								NULL, 
								TRUE);
							break;
						case 33: // Prepop Page
							Stats::Hit_Stats(
								'pw_arb_pre_pop', 
								$this->session, 
								$this->event, 
								$this->applog, 
								$this->Get_Application_ID(), 
								NULL, 
								TRUE);
							
							$this->next_page = 'bb_thanks';
							
							break;


					}
				}

				

				if ((isset(SiteConfig::getInstance()->exit_strategy[$id]) || $force_run)
					&& $result == '1'
					&& empty($_SESSION["es_offer_{$id}_posted"]))
				{
					try
					{
						switch ($id)
						{
							case 30: //Save Your Identity Now
							case 31: //1st Credit Now
							case 32: // credit repair source.net - PW Arb
							case 35: // EDebit Pay - PW Arb
							case 37: // gforge #11306 -- broken gold internal opt-in [BA]
								$result = $this->postPWOptIn($id);
								break;
							case 34: // Auto Loan - PW Arb
								$result = $this->postAutoLoanOfferOptin($id);
								break;
							case 33: // Prepop -- nothing here
								break;	
							case 38: // GForge 11203 [TF]
								$_SESSION['data']['mh_offer'] = 'TRUE';
								$result = $this->Moneyhelper_Optin();
							break;
						}
						
						if (!is_null($result))
						{
							$stat = ($result) ? "es_offer_{$id}_pass" : "es_offer_{$id}_fail";
							Stats::Hit_Stats($stat, $this->session, $this->event, $this->applog, $this->Get_Application_ID(), NULL, TRUE);
								
							$_SESSION["es_offer_{$id}_posted"] = TRUE;
						}
					}
					catch (Exception $e)
					{
						$this->Applog_Write('Failed to post Exit Strategy opt-in for ES ID ' . $id);
					}
				}
			}
		}
	}
	

	/**
	 *
	 *
	 */
	private function postAutoLoanOfferOptin($id) 
	{
		$url = (BFW_MODE == 'LIVE') ? 'https://pwlss.com/' : 'http://rc.pwlss.com/';
		$data = $_SESSION['data'];
		

		$result = NULL;
		if (strlen($data['social_security_number']) == 9
			&& !empty($data['address_length_years'])
			&& !empty($data['address_length_months'])
			&& !empty($data['phone_mobile'])
			&& !empty($data['rent_or_own'])
			&& !empty($data['monthly_rent'])
			&& !empty($data['employer_length_years'])
			&& !empty($data['employer_length_months'])
			&& !empty($data['job_title'])
			&& !empty($data['bankruptcy'])
			&& !empty($data['cosigner_available'])
			&& !empty($data['authorized_to_check_credit_report'])
			)
		{
			$params = array(
				'process_page'	=> 'auto_loan',
				'zoneid'		=> '', // checking with Greg
				'source_url'	=> SiteConfig::getInstance()->site_name,
				'client_ip'		=> $data['client_ip_address'],
				'user_data'		=> $this->Get_Application_ID(),
				'name_first'	=> $data['name_first'],
				'name_last'		=> $data['name_last'],
				'email'			=> $data['email_primary'],
				'address_1'		=> $data['home_street'],
				'city'			=> $data['home_city'],
				'state'			=> $data['home_state'],
				'zip'			=> $data['home_zip'],
				'phone_home'	 => $data['phone_home'],
				'phone_mobile'	 => $data['phone_mobile'],
				'address_length_years' => $data['address_length_years'],
				'address_length_months' => $data['address_length_months'],
				'rent_or_own' 	=> $data['rent_or_own'],
				'monthly_rent' 	=> $data['monthly_rent'],
				'dob_month'		=> $data['date_dob_m'],
				'dob_day'		=> $data['date_dob_d'],
				'dob_year'		=> $data['date_dob_y'],
				'social_security_number' => $data['social_security_number'],
				'employer' 	=> $data['employer_name'],
				'job_title' => $data['job_title'],
				'employer_length_years' => $data['employer_length_years'],
				'employer_length_months' => $data['employer_length_months'],
				'income_monthly' => $data['income_monthly_net'],
				'phone_work' => $data['phone_work'],
				'phone_work_ext' => $data['phone_work_ext'],
				'bankruptcy' => $data['bankruptcy'] ? 'YES' : 'NO',
				'cosigner_available' => $data['cosigner_available'] ? 'YES' : 'NO',
			);
			
	
		
			$params['lss_target_id'] = 177;
			$params['target_name'] = 'AUTOOPTIN';
			
			$post_url = $url . '?' . http_build_query($params);

			require_once 'http_client.1.php';
			$http_client = new Http_Client_1();
			$http_client->Set_Timeout(5);
			$response = $http_client->Http_Get($post_url);
			
			$result = (preg_match('/Approved/i', $response));
		}
			
		return $result;

	}
	/**
	 * Post a Partner Weekly opt-in
	 *
	 * @return bool
	 */
	private function postPWOptIn($id)
	{
		$url = (BFW_MODE == 'LIVE') ? 'https://pwlss.com/' : 'http://rc.pwlss.com/';
		$data = $_SESSION['data'];
		
		/**
		 * Since we're adding this to the enterprise thank you page, we need to check if we're on the enterprise
		 * site, and if we are, get all the user data from OLP.  In theory, $_SESSION['cs'] would have most of this
		 * data, but a bunch of it isn't indexed in the way we need it (dob, ssn, etc).
		 */
		if(Enterprise_Data::getLicenseKey(SiteConfig::getInstance()->property_short) == SiteConfig::getInstance()->license)
		{
			$olp_data = $this->Get_App_Data_From_OLP($this->Get_Application_ID());
			if(is_array($olp_data))
			{
				$data = array_merge($data, $olp_data);
			}
		}

		/**
		 * The thank you pages will pass this value in.  We need to
		 * blank out the pwad at this point so that publishers who
		 * haven't signed up for this offer don't get credited for it.
		 */
		if (!empty($this->collected_data['no_pwad']))
		{
			unset($data['pwadvid']);
		}
		
		$result = NULL;
		if (strlen($data['social_security_number']) == 9
			&& !empty($data['bank_account'])
			&& !empty($data['phone_home']))
		{
			$params = array(
				'process_page'	=> 'opt_in',
				'zoneid'		=> '',
				'source_url'	=> SiteConfig::getInstance()->site_name,
				'client_ip'		=> $data['client_ip_address'],
				'user_data1'	=> $this->Get_Application_ID(),
				'name_first'	=> $data['name_first'],
				'name_last'		=> $data['name_last'],
				'email'			=> $data['email_primary'],
				'address_1'		=> $data['home_street'],
				'city'			=> $data['home_city'],
				'state'			=> $data['home_state'],
				'zip'			=> $data['home_zip'],
				'country'		=> (empty($data['country']) || strlen($data['country']) != 2) ? 'US' : $data['country'],
				'phone_home_area'	 => substr($data['phone_home'], 0, 3),
				'phone_home_prefix'	 => substr($data['phone_home'], 3, 3),
				'phone_home_suffix'	 => substr($data['phone_home'], 6),
				'dob_month'		=> $data['date_dob_m'],
				'dob_day'		=> $data['date_dob_d'],
				'dob_year'		=> $data['date_dob_y'],
				'social_security_number' => $data['social_security_number'],
				'ach_account_number'	=> $data['bank_account'],
				'ach_account_type'		=> $data['bank_account_type'],
				'ach_bank_name'			=> $data['bank_name'],
				'ach_routing_number'	=> $data['bank_aba'],
				'ach_name_on_account'	=> $data['name_first'] . ' ' . $data['name_last'],
				'ach_check_number'		=> 101,
				'date_sale'		=> date('Ymd'),
				'opt_in_info_1' => 'CASH_ADV_LEAD',
				'opt_in_info_2' => (empty($data['pwadvid'])) ? '' : $data['pwadvid'],
				'payment_method' => 'ACH',
			);

			switch($id)
			{
			//Save Your Identity Now
				case 30:
					$params['lss_target_id'] = 171;
					$params['target_name'] = 'SYIN';
				break;
			//1st Credit Now
				case 31:
					$params['lss_target_id'] = 173;
					$params['target_name'] = 'FCIM';
			
				break;
				case 32:
					$params['opt_in_info_3'] = 'cash_thanks_internal';
					$params['lss_target_id'] = 173;
					$params['target_name'] = 'FCN';
				break;
				// EdebitPay
				case 35:
					$params['opt_in_info_3'] = 'cash_thanks_internal'; // GForge #9962 [AuMa]
					$params['lss_target_id'] = 176;
					$params['target_name'] = 'EDP';
				break;
				// Broken Gold
				case 37: // gForge #11306 [BA]
					$params['lss_target_id'] = 190;
					$params['target_name'] = 'BG1';
					$params['opt_in_info_4'] = 'partnerweeklyoptin';
					break;
			}
	
			$post_url = $url . '?' . http_build_query($params);


			require_once 'http_client.1.php';
			$http_client = new Http_Client_1();
			$http_client->Set_Timeout(5);
			$response = $http_client->Http_Get($post_url);
			
			$result = (preg_match('/Approved/i', $response));
		}
			
		return $result;
	}
	
	private function Moneyhelper_Optin()
	{
		if(!defined('USE_MONEYHELPER') || USE_MONEYHELPER === true)
		{
			$cid_list = array_map('trim', explode(",", $_SESSION['config']->optin_cid));
			// Since there is different posting criteria based upon cid, we need to
			// hard-code the differences here. yuck!
			foreach($cid_list as $cid)
			{
				if($cid == 165 && (strtoupper($_SESSION['data']['mh_offer']) === 'TRUE'
					|| strtoupper($_SESSION['data']['cf_offer']) === 'TRUE'))
				{
					$_SESSION['moneyhelper_optin_'.$cid] = $this->Post_Moneyhelper_Optin($cid);
				}
				elseif($cid == 175 || $cid == 155)
				{
					$_SESSION['moneyhelper_optin_'.$cid] = $this->Post_Moneyhelper_Optin($cid);
				}
			}
			// we need to hit 2 stats for moneyhelper
			// 1)	Hit a stat if we show the moneyhelper form for
			//		cid 165 (regardless of user input)
			// 2)	Hit a stat if the user opts in with cid 165

			$app_id = $this->Get_Application_ID();

			if (in_array(165, $cid_list))
			{
				Stats::Hit_Stats('money_helper_visitor', $this->session, $this->event, $this->applog, $app_id, NULL, TRUE);
				if (strtoupper($_SESSION['data']['mh_offer'])=='TRUE')
				{
					Stats::Hit_Stats('money_helper_optin', $this->session, $this->event, $this->applog, $app_id, NULL, TRUE);
				}
			}
			return TRUE;
		}
	}


	// This function will insert the data into the columns
	private function Cash_Credit_News_Optin()
	{
		$app_id = $_SESSION['application_id'];

		if($_SESSION['data']['mh_offer'] == 'TRUE')
		{
			$query = "REPLACE INTO ccn_daily ( application_id, date_created ) VALUES ( '{$app_id}', now() )";
			$result = $this->sql->Query($this->database, $query);

			//********************************************* 
			// GForge 7447 [AuMa]
			// QA wants to be able to view when the CCN event
			// fires on RC and live
			//********************************************* 
			$this->event->Log_Event('CCN_OPTIN' , 
            	'PASS', NULL, $this->application_id);

		}
		else
		{
			//*********************************************  
			// GForge 7447 [AuMa]
			// QA wants to be able to view when the CCN event
			// fires on RC and live
			//********************************************* 
			$this->event->Log_Event('CCN_OPTIN' , 
            	'FAIL', NULL, $this->application_id);
		}
		return TRUE;
	}

	// Begin Mods for GForge #5764 [AuMa]
	/**
	 * This function runs a check to find out if we're supposed to be using moneyhelper and
	 * if the money helper values are set.  If the user answered "Yes/TRUE" to the question 
	 * about wanting newsletters - then run Post_YourFinancePro_Optin
	 * - no parameters
	 * - no return ( we do however save the data in $_SESSION['yourfinancepro_optin'])
	*/
	private function YourFinancePro_Optin()
	{
		if((!defined('USE_MONEYHELPER') || USE_MONEYHELPER === true) && $_SESSION['data']['mh_offer'] == 'TRUE')
		{
			$_SESSION['yourfinancepro_optin'] = $this->Post_YourFinancePro_Optin();
		}
	}
	
	/**
	 * This function performs the actual post to the server - this function returns true - always saying we posted
	 * or at least attempted to post to the vendor's server.
	 * - no parameters (we take things from $_SESSION['data'])
	 * @return bool TRUE
	*/
	private function Post_YourFinancePro_Optin()
	{
		$source = urlencode(preg_replace("/^http(s)*:\/\//", "", $_SESSION['data']['client_url_root']));
		$list_code = "b12uaxv0x1kvxf3pn70";
		$email = urlencode($_SESSION['data']['email_primary']);
		$ts = urlencode(date('m/d/Y'));
		$ip = urlencode($_SESSION['data']['client_ip_address']);
		$fname = urlencode($_SESSION['data']['name_first']);
		$lname = urlencode($_SESSION['data']['name_last']);
		$zip = urlencode($_SESSION['data']['home_zip']);
		$state = urlencode($_SESSION['data']['state']);
		$dob = urlencode($_SESSION['data']['dob']);
		$address = urlencode($_SESSION['data']['home_street']);
		$city = urlencode($_SESSION['data']['home_city']);
		$state = urlencode($_SESSION['data']['home_state']);
		$phone = urlencode($_SESSION['data']['phone_home']);

		require_once('http_client.1.php');
		$http_client = new Http_Client_1();
		$http_client->Set_Timeout(5);
		/*
		// This is the test post url
		$post_url = "http://ds82.tss/tools/post_output.php?Command=ManageList&ListCode={$list_code}&EmailAddress={$email}&Type=Subscribe&ReturnType=Code&SignupIp={$ip}&Source={$source}&Info[FirstName]={$fname}&Info[LastName]={$lname}&Info[Address]={$address}&Info[City]={$city}&Info[State]={$state}&Info[Zip]={$zip}&Info[DateOfBirth]={$dob}&Info[Phone]={$phone}";
		*/
		// Switch this to live once we are ready to post to them (that is when we have the list code
		
		$post_url = "http://api.resultsgeneration.com/api.php?Command=ManageList&ListCode={$list_code}&EmailAddress={$email}&Type=Subscribe&ReturnType=Code&SignupIp={$ip}&Source={$source}&Info[FirstName]={$fname}&Info[LastName]={$lname}&Info[Address]={$address}&Info[City]={$city}&Info[State]={$state}&Info[Zip]={$zip}&Info[DateOfBirth]={$dob}&Info[Phone]={$phone}";
		
		// switch the following line back on before it gets committed to live.
		if(strcasecmp(BFW_MODE,'LIVE')!==0) return TRUE;
		// comment out the above line for QA to test on RC.
		
		try
		{
			$response = $http_client->Http_Get($post_url);
			if (preg_match("/SUCCESS/i", $response))
			{
				$this->event->Log_Event('YOURFINANCEPRO_OPTIN', 'PASS', NULL, $this->application_id);
			}
			else
			{
				$this->event->Log_Event('YOURFINANCEPRO_OPTIN', 'FAIL', NULL, $this->application_id);
			}

		}
		catch (Exception $e)
		{
			$this->Applog_Write('Post to Money Helper Opt-In failed. App ID: '.$this->application_id.' - Post URL: '.$post_url);
		}
		return TRUE;
	}
	// End Mods for Gforge #5764 [AuMa]
	
	private function Post_Moneyhelper_Optin($cid)
	{
		$source = urlencode(preg_replace("/^http(s)*:\/\//", "", $_SESSION['data']['client_url_root']));
		$email = urlencode($_SESSION['data']['email_primary']);
		$ts = urlencode(date('m/d/Y'));
		$ip = urlencode($_SESSION['data']['client_ip_address']);
		$fname = urlencode($_SESSION['data']['name_first']);
		$lname = urlencode($_SESSION['data']['name_last']);
		$zip = urlencode($_SESSION['data']['home_zip']);

		require_once('http_client.1.php');
		$http_client = new Http_Client_1();
		$http_client->Set_Timeout(5);
		$post_url = "http://post.ccidatafeed.com/Post.aspx?email={$email}&source={$source}&ts={$ts}&cid={$cid}&ip={$ip}&fname={$fname}&lname={$lname}&zip={$zip}";
		//$post_url = "http://olp_tools.ds70.tss:8080/Post.aspx?email={$email}&source={$source}&ts={$ts}&cid={$cid}&ip={$ip}&fname={$fname}&lname={$lname}";

		if(strcasecmp(BFW_MODE,'LIVE')!==0)
		{
			//********************************************* 
			// GForge #9937 [AuMa]
			// Change the Event log verbiage to reflect
			// the WebYes Exclusive Data Feed
			// (don't ask) 
			//********************************************* 
			$event_name = 'MONEYHELPER_OPTIN_';

			if($cid == 175)
			{
				$event_name = 'WEBYES_EXCLUSIVE_DATA_FEED_';
			}
			
			$this->event->Log_Event($event_name . $cid, 'PASS', NULL, $this->application_id);
			
			return TRUE;
		}

		try
		{
			$response = $http_client->Http_Get($post_url);

			//********************************************* 
			// GForge #9937 [AuMa]
			// Change the Event log verbiage to reflect
			// the WebYes Exclusive Data Feed
			// (don't ask) 
			//********************************************* 
			$event_name = 'MONEYHELPER_OPTIN_';

			if($cid == 175)
			{
				$event_name = 'WEBYES_EXCLUSIVE_DATA_FEED_';
			}

			if (preg_match("/successfully added to our system/i", $response))
			{
				$this->event->Log_Event($event_name . $cid, 'PASS', NULL, $this->application_id);
			}
			else
			{
				$this->event->Log_Event($event_name . $cid, 'FAIL', NULL, $this->application_id);
			}

		}
		catch (Exception $e)
		{
			$this->Applog_Write('Post to Money Helper Opt-In failed. App ID: '.$this->application_id.' - Post URL: '.$post_url);
		}
		return TRUE;
	}

	private function Nineteencom_Optin()
	{
		if(!defined('USE_MONEYHELPER') || USE_MONEYHELPER === true)
		{
			if(strtoupper($_SESSION['data']['nineteencom_offer']) === 'TRUE')
			{
				$_SESSION['nineteencom_optin_offer'] = $this->Post_Nineteencom_Optin($_SESSION['config']->nineteencom_optin_id);
			}

			$app_id = $this->Get_Application_ID();

			// Please add the STAT COLUMNS
			Stats::Hit_Stats('nineteencom_visitor', $this->session, $this->event, $this->applog, $app_id, NULL, TRUE);
			if (strtoupper($_SESSION['data']['nineteencom_offer'])=='TRUE')
			{
				Stats::Hit_Stats('nineteencom_optin', $this->session, $this->event, $this->applog, $app_id, NULL, TRUE);
			}
			return TRUE;
		}
	}

	private function Post_Nineteencom_Optin($id)
	{

		$source = $_SESSION['data']['client_url_root'];
		$email = urlencode($_SESSION['data']['email_primary']);
		$ts = urlencode(date('m/d/Y'));
		$ip = urlencode($_SESSION['data']['client_ip_address']);
		$fname = urlencode($_SESSION['data']['name_first']);
		$lname = urlencode($_SESSION['data']['name_last']);
		$zip = urlencode($_SESSION['data']['home_zip']);
		require_once('http_client.1.php');
		$http_client = new Http_Client_1();
		$http_client->Set_Timeout(5);
		$post_url = "http://moderator.bighip.com/post.jsp?listid={$id}&email={$email}&referrer={$source}&remoteaddr={$ip}&firstname={$fname}&lastname={$lname}&postal={$zip}";
		//$post_url = "http://ds82.tss/tools/post_output.php?listid={$cid}&email={$email}&referrer={$source}&remoteaddr={$ip}&firstname={$fname}&lastname={$lname}";

		if(strcasecmp(BFW_MODE,'LIVE')!==0) 
		{
			//********************************************* 
			// GForge 8890 [AuMa]
			// Always pass on RC - because we don't want to fill
			// up the database with junk 
			//********************************************* 
			$this->event->Log_Event('NINETEENCOM_OPTIN_'.$id, 'PASS', NULL, $this->application_id);
			return TRUE;
		}

		try
		{
			$response = trim($http_client->Http_Get($post_url));

			// Have not had a successful post yet - so I cannot set this up properly [AuMa]
			if ($response == "OK")
			{
				$this->event->Log_Event('NINETEENCOM_OPTIN_'.$id, 'PASS', NULL, $this->application_id);
			}
			else
			{
				$this->event->Log_Event('NINETEENCOM_OPTIN_'.$id, 'FAIL', NULL, $this->application_id);
			}

		}
		catch (Exception $e)
		{
			$this->Applog_Write('Post to 19 Communications Opt-In failed. App ID: '.$this->application_id.' - Post URL: '.$post_url);
		}


		return TRUE;
	}


	public function CSR_Thank_You(&$return_obj)
	{
		//For teleweb csr completions, we need to stop the app before it redirects!
		if($this->next_page == 'bb_thanks' && !empty($_SESSION['blackbox']['winner']) && isset($_SESSION['data']['csr_complete']))
		{
			$winner = Enterprise_Data::resolveAlias($_SESSION['blackbox']['winner']);

			if($this->config->call_center && isset($this->ent_prop_list[$winner]))
			{
				if(SiteConfig::getInstance()->ivr_scripted_thanks)
				{
					$winning_company = $this->ent_prop_list[$winner]['legal_entity'];

					if(!$winning_company)
					{
						$winning_company = "<i>[unknown company short {$winner}]</i>";
					}

					$content = "Congratulations! You have been pre-approved for a cash advance loan with <b>{$winning_company}</b>. In order to receive your loan, you need to carefully follow the following instructions in our automated system. Please keep your offer letter handy while I transfer you to complete the acceptance process. Have a great day.";

					$this->eds_page = array('content' => $content, 'type' => 'html' , 'action' => 'standard');
					$this->next_page = 'bb_extra';
				}
				//Let the agent choose if they included both.
				elseif(!empty($_SESSION['data']['phone_fax']) && !empty($_SESSION['data']['email_primary']))
				{
					$this->next_page = 'ecash_fax_or_email';
				}
				else
				{
					$this->config->property_short = strtolower($winner);

					$type = (!empty($_SESSION['data']['phone_fax'])) ? 'fax' : 'email';
					$this->ECashNewApp_Send_Docs($type, 'twb');
				}
			}
			elseif($this->config->call_center || in_array($this->config->site_name, array('1hourfastcash.com', 'woodscashloans.com')))
			{
				unset(
					//$return_obj->data['online_confirm_winner'],   //Show the thank you page for enterprise winners
					$return_obj->data['online_confirm_redirect_url'], //But don't do the redirects
					$return_obj->data['redirect_time']
				);

				//If an enterprise company won, send out the confirmation email
				if(isset($this->ent_prop_list[$winner]))
				{
					$ent_cs = $this->Get_Ent_Cs($winner);
					$ent_cs->Mail_Confirmation($this->ent_prop_list[$winner]['site_name']);
				}
				//Otherwise, just display a thank you message
				else
				{
					$name = $winner;

					//We'll try to find the company name here.
					try
					{
						$result = $this->sql->Query($this->database, "SELECT client.name FROM client INNER JOIN target USING (client_id) WHERE target.property_short = '{$winner}'");

						if($result)
						{
							$client_name = $this->sql->Fetch_Column($result, 'name');

							if(!empty($client_name))
							{
								 $name = "{$client_name} ({$winner})";
							}
						}
					}
					catch(MySQL_Exception $e)
					{
						//Ignore
					}

					$return_obj->data['thanks_content'] = "<br /><br /><span>Thank you for your application.  You have been
						pre-approved with one of our lending partners, <b>{$name}</b></span>.";

				}
			}
		}
		elseif($_SESSION['blackbox']['denied']
			&& $this->config->call_center
			&& (isset($_SESSION['data']['csr_complete']) ))
		{
			// This change was requested by Partner Weekly to
			// capitalize on potential lost revenue that was just
			// dropped from the system because people did not
			// complete the applications
			// GForge 3031 - Open TW Call Center Leads to BBx - [AuMa]
			$app_id = base64_encode( $this->application_id);
			// we need different promo ids just for tracking
			$myPromoId = 31192; // default one
			if(isset($this->config->twbb_resell_promo))
			{
				$myPromoId = $this->config->twbb_resell_promo;
			}
			// other promo ids: 31233, 31234
			$urlToSend = "http://CashAngelsOnline.com/?promo_id={$myPromoId}&page=callcenter_rerun&force_new_session&call_center_id={$app_id}";
			$data = array();
			$data['confirm_link'] = $urlToSend;
			$data['first_name'] = $this->collected_data['name_first'];
			$data['last_name'] = $this->collected_data['name_last'];
			$data['name'] = $this->collected_data['name_first'] . ' ' . $this->collected_data['name_last'];
			$data['email'] = $this->collected_data['email_primary'];
			$data['app_id'] = $this->application_id;
			$data['date_app_created'] = date('Y-m-d');
			$data['time_app_created'] = date('H:m:s');
			$data['signup_date'] = date('Y-m-d H:m:s');	// needed for Trendex
			$data['source'] = 'Phone'; 		// needed for Trendex
			$data['ip_address'] = '127.0.0.1';  	// needed for Trendex
			try
			{
				require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
				$tx = new OlpTxMailClient(false);
				// don't use the number on live - use the template name
				$template ='CALLCENTER_BBX_RESELL';
				// if you're hitting stat pro - then you want the track key
				// $data is an associative array that is passed in so you can access %%%link%%%
				// see example /virtualhosts/lib/tx/Mail/Client.example.php
				$res = $tx->sendMessage('live', $template, $data['email'], $_SESSION['statpro']['track_key'], $data);
			}
			catch(Exception $e)
			{
				$this->Applog_Write("Trendex mail $template failed. ".$e->getMessage()." (App ID: ". $this->application_id . ") " . "\n" 
					. str_repeat('=', 70) . "\n" . "Data: " . " \n " . str_repeat('-', 70) . "\n" . print_r($data, true) . "\n" 
					. str_repeat('=', 70) . "\n" . "Result: " . "\n" . str_repeat('-', 70) . "\n" . print_r($res, true));
			}
			
				
			if($res === FALSE)
			{ 	
				$this->Applog_Write("Trendex mail $template failed. (App ID: ". $this->application_id . ")" . "\n" 
					. str_repeat('=', 70) . "\n" . "Data: " . " \n " . str_repeat('-', 70) . "\n" . print_r($data, true));
			}
			
			// This allows the user to customize the text that is displayed back to the user (with a little bit of formatting)
			$myTxt = "<div style='padding-left:50px; padding-right:50px; padding-top:25px; text-align:left'>";
			$myTxt .= "<div align='center'><strong>Thank you!</strong></div>" . "\n";
			$myTxt .= "" . "<br />\n";
			$myTxt .= "Your application has been pre-approved. For security reasons, you must check" . "\n";
			$myTxt .= "your email and click on the link in the email so we can process and fund your" . "\n";
			$myTxt .= "application.  " . "<br /> <br />\n";
			$myTxt .= "Please check your email as soon as you can." . "\n";
			$myTxt .= "</div>";

			$this->eds_page = array('content' => $myTxt, 'type' => 'html' , 'action' => 'standard');
			$this->next_page = 'bb_extra';
		}
	}


	public function Get_Loan_Type_ID($loan_type)
	{
		if(!empty($this->property_short))
		{
			$ent_cs = $this->Get_Ent_Cs($this->property_short);
			$result = $ent_cs->Get_Current_Loan_Type_ID($loan_type, $this->property_short);
		}

		return $result;
	}


	public function Check_For_LDB_App($application_id)
	{
		$result = FALSE;

		if(!empty($this->property_short))
		{
			$ent_cs = $this->Get_Ent_Cs($this->property_short);
			$result = $ent_cs->Check_For_LDB_App($application_id);
		}

		return $result;
	}

	/**
	* Function to ensure teleweb promo_overrides work correctly
	**/
	private function Teleweb_Override()
	{
		$return = FALSE;

		if($this->current_page == 'ent_cs_login' && $_SESSION['promo_override'] && $this->Get_Application_ID() !== FALSE)
		{
			//Since they use ent_cs_login, we need to make sure it doesn't use the read_only connection
			//An exception to an exception?  Ouch.
			unset($this->process_exceptions[array_search('ent_cs_login', $this->process_exceptions)]);

			//Since they don't pass in a login hash, we also need to fake this so that the docs show up.
			$_SESSION['cs']['md5_hash_match'] = 1;

			$return = TRUE;
		}

		return $return;
	}

	/**
		Gets data from the OLP database and formats it so it looks
		like post data from the customer.
	*/
	private function Get_App_Data_From_OLP($application_id)
	{
		$data = array();

		if (empty($application_id))
		{
			return $data;
		}
		
		$query = "
			SELECT
				first_name		AS name_first,
				middle_name		AS name_middle,
				last_name		AS name_last,
				email			AS email_primary,
				home_phone		AS phone_home,
				cell_phone		AS phone_cell,
				fax_phone		AS phone_fax,
				work_phone		AS phone_work,
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
				drivers_license_state	AS state_issued_id,
				ca_resident_agree,

				direct_deposit	AS income_direct_deposit,
				income_type,
				pay_frequency	AS income_frequency,
				bank_name,
				account_number	AS bank_account,
				routing_number	AS bank_aba,
				monthly_net		AS income_monthly_net,
				bank_account_type,
				military,

				paydate_model_id	AS paydate_model,
				IFNULL(ELT(day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'), 'SUN') AS day_of_week,
				pay_date_1 			AS next_paydate,
				day_of_month_1,
				day_of_month_2,
				week_1,
				week_2,

				best_call_time,

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
				title_state AS vehicle_title_state
			FROM
				personal_encrypted
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
			LIMIT 1
		";

		$mysql_result = $this->sql->Query($this->database, $query);

		if ($mysql_result && ($data = $this->sql->Fetch_Array_Row($mysql_result)))
		{
			$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'], $crypt_config['IV']);
			$data['ssn'] 			= $crypt_object->decrypt($data['ssn']);
			$data['bank_account'] 	= $crypt_object->decrypt($data['bank_account']);
			$data['bank_aba'] 		= $crypt_object->decrypt($data['bank_aba']);
			$data['dob'] 			= $crypt_object->decrypt($data['dob']);

			$data['paydate'] = array('frequency' => $data['income_frequency']);

			list($data['date_dob_y'], $data['date_dob_m'], $data['date_dob_d']) = explode('-', $data['dob']);
			$data['ssn_part_1'] = substr($data['ssn'], 0, 3);
			$data['ssn_part_2'] = substr($data['ssn'], 3, 2);
			$data['ssn_part_3'] = substr($data['ssn'], 5);
			$data['social_security_number'] = $data['ssn'];
			
			if (strcasecmp($data['home_state'], 'CA') == 0)
			{
				$data['cali_agree'] = ($data['ca_resident_agree'] == 1) ? 'agree' : 'disagree'; 
			}
			
			// modify the military information
			$data['military'] = ($data['military'] == 'yes') ? 'TRUE' : 'FALSE';

			//Taken from ent_cs and slightly modified
			switch ($data['paydate']['frequency'])
			{
				case 'WEEKLY':
					$data['paydate']['weekly_day'] = $data['day_of_week'];
					break;
				case 'BI_WEEKLY':
					$data['paydate']['biweekly_day'] = $data['day_of_week'];
					$data['paydate']['biweekly_date'] = sprintf('%s/%s/%s', substr($data['next_paydate'], 5, 2), substr($data['next_paydate'], 8, 2), substr($data['next_paydate'], 0, 4));
					break;
				case 'TWICE_MONTHLY':
					switch ($data['paydate_model'])
					{
						case 'DMDM':
							$data['paydate']['twicemonthly_type'] = 'date';
							$data['paydate']['twicemonthly_date1'] = $data['day_of_month_1'];
							$data['paydate']['twicemonthly_date2'] = $data['day_of_month_2'];
							break;
						default:
							$data['paydate']['twicemonthly_type'] = 'week';
							$data['paydate']['twicemonthly_week'] = sprintf('%s-%s', $data['week_1'], $data['week_2']);
							$data['paydate']['twicemonthly_day'] = $data['day_of_week'];
							break;
					}
					break;
				case 'MONTHLY':
					switch ($data['paydate_model'])
					{
						case 'DM':
						//rsk changed from week
							$data['paydate']['monthly_type'] = 'date';
							$data['paydate']['monthly_date'] = $data['day_of_month_1'];
							break;
						case 'WDW':
							$data['paydate']['monthly_type'] = 'day';
							$data['paydate']['monthly_week'] = $data['week_1'];
							$data['paydate']['monthly_day'] = $data['day_of_week'];
							break;
						default:
							$data['paydate']['monthly_type'] = 'after';
							$data['paydate']['monthly_after_day'] = $data['day_of_week'];
							$data['paydate']['monthly_after_date'] = $data['day_of_month_1'];
							break;
					}
					break;
			}

			unset($data['ssn'], $data['dob'], $data['ca_resident_agree']);


			$ref_query = "
				SELECT
					full_name	AS name_full,
					phone		AS phone_home,
					relationship
				FROM
					personal_contact
				WHERE
					application_id = {$application_id}";

			$ref_result = $this->sql->Query($this->database, $ref_query);

			$count = 0;
			while ($row = $this->sql->Fetch_Array_Row($ref_result))
			{
				$ref_count = sprintf('%02d', ++$count);

				$data['ref_' . $ref_count . '_name_full'] = $row['name_full'];
				$data['ref_' . $ref_count . '_phone_home'] = $row['phone_home'];
				$data['ref_' . $ref_count . '_relationship'] = $row['relationship'];
			}
		}

		return $data;
	}


	private function Setup_New_Config($key, $promo = NULL, $sub_code = NULL, $test_config = false)
	{
		//Load correct Config
		require_once(BFW_CODE_DIR . 'server.php');
		require_once(BFW_CODE_DIR . 'setup_db.php');
		require_once(BFW_CODE_DIR . 'Cache_Config.php');

		//These values are set in the OLP constructor and we should make sure they're still there, I guess.
		$current_settings = array(
			'enable_rework' => $_SESSION['config']->enable_rework,
			'use_new_process' => $_SESSION['config']->use_new_process,
			'ecash3_prop_list' => $_SESSION['config']->ecash3_prop_list
		);

		$sql = Setup_DB::Get_Instance('management', $_SESSION['config']->mode);
		$config_obj = new Cache_Config($sql);
		$config = $config_obj->Get_Site_Config($key, $promo, $sub_code);

		$config->site_type = $_SESSION['config']->site_type;
		$config->site_type_obj = $_SESSION['config']->site_type_obj;

		// If creating a test config, return the config object. Otherwise modify actual config.
		if($test_config)
		{
			return $config;
		}
		else
		{
			$_SESSION['config'] = $config;

			//Ensure the configs are the same
			$this->config = &$_SESSION['config'];

			//Need to reset the space key!!
			$_SESSION['statpro']['space_key'] = NULL;

			foreach($current_settings as $name => $value)
			{
				$_SESSION['config']->$name = $value;
			}


			if(!$this->Is_Impact() && !$this->Is_Agean())
			{
				$_SESSION['stat_info'] = Set_Stat_3::Setup_Stats (NULL,
																  $config->site_id,
																  $config->vendor_id,
																  $config->page_id,
																  $promo,
																  $sub_code,
																  $config->promo_status);
			}
		}
	}



	/**
	 * Checks to see if we should redirect to the Imagine Card offer.
	 *
	 * @return bool
	 */
	function Check_Imagine_Card()
	{
		$return = FALSE;

		$excluded_sites = array(
			'500fastcash.com',
			'ameriloan.com',
			'unitedcashloans.com',
			'oneclickcash.com',
			'usfastcash.com',
			'impactcashusa.com',
			'impactsolutiononline.com',
			'impactcashcap.com',
			'cashfirstonline.com',
			'lendingcashsource.com',
		);

		// Removed the check for direct deposit. Partner Weekly wanted to redirect any failed
		// Blackbox application.

		if((!(in_array($this->config->site_name, $excluded_sites, TRUE))) || $this->config->call_center)
		{
			$return = TRUE;
			$_SESSION['data']['imagine_card'] = TRUE;
			$this->next_page = 'imagine_card';
		}

		return $return;
	}

    /**
     * Check if given promo sub code is in block list or not. For Mantis #9610 [DY]
     * This function is exactly same as the one defined in bfw.1.php for Mantis #8361.
     *
     * @param string $promo_sub_code Promo sub code.
     * @param string|array $block_sub_codes A list of blocked sub codes.
     * @return boolean true if $promo_sub_code is in $block_sub_codes; otherwise, false.
     */
    function In_Blocked_Sub_Codes($promo_sub_code = NULL, $block_sub_codes = NULL) {
        if (!$promo_sub_code)
            $promo_sub_code  = trim($this->config->promo_sub_code);

        if (!$block_sub_codes)
            $block_sub_codes = $this->config->block_sub_codes;

        if ($promo_sub_code && $block_sub_codes) {
            if (is_array($block_sub_codes)) { // $block_sub_codes is in ARRAY format
                array_walk($block_sub_codes, create_function('&$val','$val=strtolower(trim($val));'));
                if (in_array(strtolower($promo_sub_code), $block_sub_codes)) {
                    return TRUE;
                }
            } else { // $block_sub_codes is in STRING format
                if (strpos($block_sub_codes, ',') !== FALSE) {
                    $block_sub_codes = explode(',', $block_sub_codes);
                    return $this->In_Blocked_Sub_Codes($promo_sub_code, $block_sub_codes);
                } else if (strcasecmp($promo_sub_code, trim($block_sub_codes)) == 0) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }


	/**
		For newecashapp.com

		Depending on what the agent chooses or what information the user
		has provided, this will send an esign doc email or fax loan documents
		(or both) to the user.

		@param $type string		fax/email/both
		@param $caller string	ecash/twb
	*/
	private function ECashNewApp_Send_Docs($type)
	{
		require_once('ecash_new_app.php');

		$result = array();
		$app_id = $this->Get_Application_ID();

		//if($this->config->site_name == 'cashnowbyphone.com')
		if($this->config->call_center)
		{
			$prop = Enterprise_Data::resolveAlias($_SESSION['blackbox']['winner']);
			$prop_data = $this->ent_prop_list[$prop];
			$this->Setup_DB($prop);
			$ent_cs = $this->Get_Ent_Cs($prop);

			$new_app = new Teleweb_New_App(
				$this->Get_Application_ID(),
				$prop_data,
				$this->db,
				$this->sql,
				$this->database,
				$this->applog,
				$ent_cs
			);
		}
		else
		{
			$prop_data = $this->ent_prop_list[strtoupper($this->config->property_short)];
			$this->Setup_DB($this->config->property_short);

			$new_app = new eCash_New_App(
				$this->Get_Application_ID(),
				$prop_data,
				$this->db,
				$this->sql,
				$this->database,
				$this->applog
			);
		}

		$new_app->Send_Docs($type);
		$content = $new_app->Get_Content();

		$this->eds_page = array('content' => $content, 'type' => 'html' , 'action' => 'standard');
		$this->next_page = 'bb_extra';
	}


	/**
	 * Is Preact
	 *
	 * Checks whether the current app is a preact
	 * @return boolean True if preact
	 */
	public function Is_Preact()
	{
		if(isset($_SESSION['is_preact']) ||
		  (isset($_SESSION['cs']['olp_process']) && $_SESSION['cs']['olp_process']=='ecashapp_preact'))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * There are certain cases where standard error-handling doesn't work.
	 * For instance, on acceptmycash.com, we require a reservation number, but
	 * that number is actually comprised of three different fields.  We don't want
	 * to raise an error for each field, but for the number as a whole.
	 */
	private function Remap_Errors(&$errors)
	{
		//We're going to override the individual parts of the reservation number here
		if($this->config->direct_mail_reservation)
		{
			//Used to bypass reservation number page on certain promo_ids GForge #3800 [MJ]
			if($this->config->bypass_res_page && strcasecmp($this->current_page, 'default') == 0)
			{
				$errors = array();
				$this->current_page = 'app_3part_page01';
				$this->dm_bypass = TRUE;
				return;
			}
			elseif ($this->config->bypass_res_page)
			{
				return;
			}

			require_once(BFW_MODULE_DIR . 'ocs/ocs.php');

			$ocs = new OCS('OLP', $this->config->mode);

			//If they passed in the fail promo_id, we don't want to check for errors
			//and just bypass the first page.
			if(isset($this->collected_data['promo_id'])
				&& $this->collected_data['promo_id'] == $ocs->Get_Fail_Promo()
				&& $this->current_page == 'default')
			{
				$errors = array();
				$this->current_page = 'app_3part_page01';
				$this->dm_bypass = TRUE;
			}

			if(isset($errors['res_part_1']) ||
				isset($errors['res_part_2']) ||
				isset($errors['res_part_3'])
			)
			{
				$errors['reservation_id'] = 'reservation_id';
				unset($errors['res_part_1'], $errors['res_part_2'], $errors['res_part_3']);
			}
		}

		//Really ugly temp reference hack
		// still ugly, but now using new SiteConfig [TP]
		if(!empty(SiteConfig::getInstance()->allow_no_refs) && ($this->Is_Soap(SiteConfig::getInstance()->site_type)))
		{
			//Remove reference errors.
			unset($errors['ref_01_name_full'], $errors['ref_01_phone_home'], $errors['ref_01_relationship'],
				  $errors['ref_02_name_full'], $errors['ref_02_phone_home'], $errors['ref_02_relationship']);
		}

		//Ecashnewapp check
		if(isset($_SESSION['ecashnewapp']) || $this->config->call_center)
		{
			//We need either a fax or email address for them to do this.
			if(isset($errors['phone_fax']) && isset($errors['email_primary']))
			{
				$errors['fax_or_email'] = 'fax_or_email';
			}

			unset($errors['phone_fax'], $errors['email_primary']);
		}
	}


	/**
	 * Here we make a check against the OCS database for a specific reservation number
	 * and zip code.  The Direct Mail campaign will send paper mail to people with a
	 * reservation number and they'll come and do this process with it.
	 */
	private function Check_OCS()
	{
		require_once(BFW_MODULE_DIR . 'ocs/ocs.php');

		$reservation_id = $this->normalized_data['res_part_1'] .
						  $this->normalized_data['res_part_2'] .
						  $this->normalized_data['res_part_3'];

		$zip = $this->normalized_data['home_zip'];
		$ocs = new OCS('OLP', $this->config->mode);
		$data = $ocs->Get_Reservation($reservation_id, $zip);

		if(!$data['result'])
		{
			$_SESSION['reservation_fails'] = (empty($_SESSION['reservation_fails'])) ? 1 : $_SESSION['reservation_fails'] + 1;

			//We give them two tries to get it right, otherwise we redirect them to UFC.
			if($_SESSION['reservation_fails'] >= 2)
			{
				$this->Event_Log();
				$this->Create_Application();

				$promo = $data['promo_id'];

				$this->Setup_New_Config($this->config->license, $data['promo_id']);

				//And also a new campaign_info record with the reservation_id and promo
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$app_campaign_manager->Update_Campaign($this->Get_Application_ID(), $_SESSION['config']);

				Stats::Hit_Stats('pre_prequal_fail', $this->session, $this->event, $this->applog,  $this->Get_Application_ID());

				unset($_SESSION['reservation_fails']);
			}
			else
			{
				$this->errors['reservation_fail'] = 'reservation_fail';
				$this->next_page = $this->current_page;
			}
		}
		else
		{
			//If we found the reservation number and zip, then this is a good application, hooray
			$this->Event_Log();

			//Changed to use config data instead of hard coding the promo ids and reservation ids. - GForge [#5688] [DW]
			$reservation_prefix = substr($reservation_id,0,5);
			$temp_config = $this->Setup_New_Config($this->config->license, $data['promo_id'], null, true);

			//Reservation numbers that start with 12380 are forwarded directly to acceptmycashfast.com
			// Added 12171 and 12172 from acceptymycash.com to redirect to acceptmycashfast.com using promo_ids 30218 and 30219 and bypass the first page - GForge 5148 [DW]
			if(strcasecmp(SiteConfig::getInstance()->site_name,"acceptmycash.com") === 0
				&& $temp_config->bypass_res_page)
			{
				// get url based on mode (LOCAL, RC, LIVE)
				switch($this->config->mode)
				{
					case 'LOCAL':
						$site_url = 'http://bb.1.acceptmycashfast.com.'.BFW_LOCAL_NAME.'.tss/?force_new_session&';
						break;
					case 'RC':
						$site_url = 'http://rc.acceptmycashfast.com/?force_new_session&';
						break;
					case 'LIVE':
						$site_url = 'http://www.acceptmycashfast.com/?';
						break;
				}

				//Get bypass promo id
				$bypass_promo = $data['promo_id'];

				$_SESSION['data']['redirect'] = $site_url.'promo_id='.$bypass_promo.'&res_part_1='.$this->normalized_data['res_part_1'].'&res_part_2='.$this->normalized_data['res_part_2'].'&res_part_3='.$this->normalized_data['res_part_3'].'&home_zip='.$this->normalized_data['home_zip'].'&page=app_3part_page01&track_key='.$_SESSION['statpro']['track_key'];
				return;
			}

			$this->Create_Application();

			$_SESSION['data']['name_first']	= $data['name_first'];
			$_SESSION['data']['name_last']	= $data['name_last'];
			$_SESSION['data']['home_street']= $data['address'];
			$_SESSION['data']['home_city']	= $data['city'];
			$_SESSION['data']['home_state']	= $data['state'];
			$_SESSION['data']['home_zip']	= $data['zip'];

			//Need to set up a new config with the new promo_id specific to the direct mail campaign
			$this->Setup_New_Config($this->config->license, $data['promo_id']);

			//And also a new campaign_info record with the reservation_id and promo
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Update_Campaign($this->Get_Application_ID(), $_SESSION['config'], $reservation_id);

			//And hit this stat which tells Nirvana not to market to these people
			Stats::Hit_Stats('dm_no_market', $this->session, $this->event, $this->applog,  $this->Get_Application_ID());
			Stats::Hit_Stats('pre_prequal_pass', $this->session, $this->event, $this->applog,  $this->Get_Application_ID());

			unset($_SESSION['reservation_fails']);
		}
	}

	/**
	 * transforms $this->config into an array and then
	 * scrubs unneeded data out first
	 *
	 * clean the config
	 * @return array array of cleaned up config
	 */
	private function Scrub_Config()
	{
		$return_array = (array)$this->config;

		//config fields to be stripped
		$unmap =  array(
						 'site_type_obj' ,
						 'stat_info' ,
						 'promo_id' ,
						 'promo_sub_code' ,
						 'vendor_id' ,
						 'promo_status',
						 'promo_sub_code' ,
						 'vendor_id' ,
						 'promo_status' ,
						 'cost_action' ,
						 'exit_strategy' ,
						 'validation_fields',
						 'license' ,
						 'page_name' ,
						 'site_name' ,
						 'property_name' ,
						 'stat_server' ,
						 'stat_base' ,
						 'site_server' ,
						 'site_base' ,
						 'site_category' ,
						 'bb_flag' ,
						 'bb_stamp' ,
						 'created_date' ,
						 'mode' ,
						 'page_id' ,
						 'site_id' ,
						 'property_id' ,
						 'qualify' ,
						 'legal_entity' ,
						 'property_short' ,
						 'disable_advertising' ,
						 'webmasters_url',
						 'support_phone',
						 'support_fax',
						 'new_form',
						 'ole_site_id',
						 'ole_list_id',
						 'collections_phone',
						 'disallowed_states',
						 'bb_reject_level',
						 'Db Type',
						 'force_promo_id',
						 'module',
						 'display_captcha',
						 'online_confirmation',
						 'optin_cid',
						 'lid',
						 'sitelifter',
						 'event_pixel',
						 'name_view',
						 'site_type',
						 'enable_rework',
						 'use_new_process',
						 'ecash3_prop_list',
						 'force_confirm',
						 'promo_limit',
						 'soap_oc',
						 'excluded_targets'
						 );


		//scub unneed info from config
		foreach($unmap as $value)
		{
			unset($return_array[$value]);
		}
		return $return_array;
	}


	/**
		Finds a loan document inside condor based on the current application ID.
		If there are multiple loan docs, it will use the most recent one.

		DISCLAIMER: THIS IS MOSTLY NORBINN'S CODE
	*/
	private function Find_Loan_Document()
	{
		require_once('prpc/client.php');

		$prpc_server = Server::Get_Server($this->config->mode, 'CONDOR', $this->config->property_short);
		$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");

		$loan_doc = NULL;
		$docs = $condor_api->Find_By_Application_Id($this->Get_Application_ID());

		if(!empty($docs))
		{
			foreach($docs as $doc)
			{
				// get the latest Loan Document archived
				// Mantis #12161 - Added in check for card loan docs as well	[RV]
				if(($doc['template_name'] == 'Loan Document' || $doc['template_name'] == 'Card Loan Document')
					&& (is_null($loan_doc) || strtotime($doc['date_created']) > strtotime($loan_doc['date_created'])))
				{
					$loan_doc = $doc;
				}
			}

			if(!is_null($loan_doc))
			{
				$_SESSION['condor_data'] = array(
					'archive_id' => $loan_doc['document_id'],
					'document' => $condor_api->Find_By_Archive_Id($loan_doc['document_id']),
				);
			}
		}

		return (!is_null($loan_doc)) ? $loan_doc['document_id'] : FALSE;
	}

	/**
	 *
	 * check if this is a soap site type
	 *
	 * @param string $sitetype the pages site type
	 * @return boolean true if it is a soap site, false otherwise
	 */
	private function Is_Soap($sitetype) {
		$soap_site_types = array ('blackbox.one.page','soap_oc','soap','soap_no_esig');
		return((in_array(strtolower($this->config->site_type),$soap_site_types)));
	}


	public function Ajax_Handler($collected_data)
	{
		$handler = new Ajax_Handler($this->applog, $this->sql, $this->config);
		return $handler->Handle_Request($collected_data);
	}

	/**
	 * Function for sending out the email to the ECYP customer to finishe their app.	[RV]
	 *
	 * @param $type array		user data
	 *
	 */
	private function ECYP_Send_Email($application_id, $data)
	{
		$this->Event_Log();

		switch(TRUE)
		{
			case preg_match ("/\.(jubilee|ds\d{2}|dev\d{2}|alpha|test)\.tss$/i", $_SERVER["SERVER_NAME"], $matched):
				$prefix = 'pcl.3.';
				$suffix = '.'.$matched[1].'.tss';
			break;
			case preg_match ("/^rc\./i", $_SERVER["SERVER_NAME"]):
				$prefix = 'rc.';
				$suffix = '';
			break;
			default:
				$prefix = 'www.';
				$suffix = '';
			break;
		}

		$promo_id = "30985";
		$encoded_app_id = urlencode(base64_encode($application_id));
		$name_first = ucfirst(strtolower($data['name_first']));
		$name_last = ucfirst(strtolower($data['name_last']));
		$mail_data['EMAIL'] = $data['email_primary'];
		$mail_data['NAME_FIRST'] = $name_first;
		$mail_data['NAME_LAST'] = $name_last;
		$mail_data['firstname'] = $name_first;
		$mail_data['lastname'] = $name_last;

		// Need to generate the correct url for the customer to come back in on and also put in the promo_id in the url.
		$mail_data['REFERRING_URL'] = "http://{$prefix}americascashadvancecenters.com{$suffix}/?promo_id={$promo_id}&promo_override&force_new_session&application_id={$encoded_app_id}&continuation=1&unique_id=".session_id();
		$mail_data['COMPANY_NAME'] = "America's Cash Advance Center";
		$mail_data['SITE_NAME'] = "americascashadvancecenters.com";
		$mail_data['SENDER'] = $mail_data['SITE_NAME'];
		$mail_data['application_id'] = $this->Get_Application_ID();

		require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
		$tx = new OlpTxMailClient(false);

		try
		{
			$result = $tx->sendMessage('live','ECYP_First_Page', $data['email_primary'],'', $mail_data);
			Stats::Hit_Stats('ecyp_first_page_email_sent', $this->session, $this->event, $this->applog,  $this->application_id);
		}
		catch (Exception $e)
		{
			$this->applog->Write("Trendex mail ECYP_First_Page failed. ".$e->getMessage()." (App ID: ".$this->Get_Application_ID().")");
		}
	}
	
	/**
	 * Get redirect URL used on the fails page.
	 *
	 * @return Redirect URL. 
	 * @author Demin Yin <Demin.Yin@SellingSource.com>
	 * @see    GForge #6972 - Last Chance Cash Advance redirect
	 * @since  Wed 13 Feb 2008 01:55:12 PM PST  
	 */
	private function getRedirectURLforPageAppDeclined() 
	{
		if (!empty($_SESSION['data']['home_unit']))	
		{
			$address1 = $_SESSION['data']['home_street'] . ' ' . $_SESSION['data']['home_unit'];
		}
		else
		{
			$address1 = $_SESSION['data']['home_street'];
		}

		$job_title = isset($_SESSION['data']['work_title']) ? $_SESSION['data']['work_title'] : '';

		$job_time = (strcasecmp($_SESSION['data']['employer_length'], 'TRUE') === 0) ? '3' : '';

		if (isset($_SESSION['data']['residence_type']))
		{
			switch (strtolower($_SESSION['data']['residence_type']))
			{
				case 'OWN':
					$residence_type = '2';
					break;
				case 'RENT':
					$residence_type = '1';
					break;
				default:
					$residence_type = '';
					break;
			}
		}
		else
		{
			$residence_type = '';
		}

		$ssn = array();  // Define variable before it's used. Just to avoid warning message in Zend Eclipse.
		$homephone = array();
		$cellphone = array();
		$dob = array();

		preg_match('/^([\d]{3})([\d]{2})([\d]{4})$/', $_SESSION['data']['social_security_number'], $ssn);
		preg_match('/^([\d]{3})([\d]{3})([\d]{4})$/', $_SESSION['data']['phone_home'],             $homephone);
		preg_match('/^([\d]{3})([\d]{3})([\d]{4})$/', $_SESSION['data']['phone_cell'],             $cellphone);
		preg_match('/^([\d]+)\/([\d]+)\/([\d]+)$/',   $_SESSION['data']['dob'],                    $dob);

		$military = (strcasecmp($_SESSION['data']['military'], 'TRUE') === 0) ? '1' : '0';
		$activechecking = (strcasecmp($_SESSION['data']['bank_account_type'], 'CHECKING') === 0) ? 'Y' : 'N';


		$url = '';

		// Only leads from disallowed states through non-enterprise sites should 
		// go to TURE case.
		if (!Enterprise_Data::isEnterprise($this->property_short)
			&& $this->isDisallowedState())
		{
			$query_fields = array(
				'firstName' => $_SESSION['data']['name_first'],
				'lastName' => $_SESSION['data']['name_last'],
				'email' => strtolower($_SESSION['data']['email_primary']),
			);
		
			switch (strtoupper($_SESSION['data']['home_state']))
			{
				case ('WV'):
					$url = 'http://click.linkstattrack.com/zoneId/185597';
					break;
				case ('VA'):
					$url = 'http://click.linkstattrack.com/zoneId/185595';
					break;
				case ('GA'):
					$url = 'http://click.linkstattrack.com/zoneId/185593';
					break;
				default:
					break;
		    }
		}
		else
		{
			$query_fields = array(
				'email' => strtolower($_SESSION['data']['email_primary']),
				'firstname' => $_SESSION['data']['name_first'],
				'lastname' => $_SESSION['data']['name_last'],
				'address1' => $address1,
				'city' => $_SESSION['data']['home_city'],
				'zip' => $_SESSION['data']['home_zip'],
				'job_employer' => $_SESSION['data']['employer_name'],
				'job_title' => $job_title,
				'job_time' => $job_time,
				'residence_type' => $residence_type,
				'ssn1' => $ssn[1],
				'ssn2' => $ssn[2],
				'ssn3' => $ssn[3],
				'homephone1' => $homephone[1],
				'homephone2' => $homephone[2],
				'homephone3' => $homephone[3],
				'cellphone1' => $cellphone[1],
				'cellphone2' => $cellphone[2],
				'cellphone3' => $cellphone[3],
				'monthob' => sprintf('%02d', intval($dob[1])),
				'dayob' => sprintf('%02d', intval($dob[2])),
				'yearob' => $dob[3],
				'bank_aba' => $_SESSION['data']['bank_aba'],
				'bank_account_num' => $_SESSION['data']['bank_account'],
				'military' => $military,
				'activechecking' => $activechecking,
			);		
		
			switch (strtolower($this->config->site_name))
			{
				case 'acceptmycash.com':
					$url = 'http://click.linkstattrack.com/zoneId/179250';
					break;
				case 'cashloannetwork.com':
					$url = 'http://click.linkstattrack.com/zoneId/179251';
					break;
				default:
					$url = 'http://click.linkstattrack.com/zoneId/177910';
					break;
			}
        }
        
        $url .= '?' . http_build_query($query_fields);		

		return $url;
	}
	
	/**
	 * Check if an application is a react application or not.
	 * 
	 * I (DY) doesn't believe in $_SESSION['is_react'] when working on GForge #3284, so here I have
	 * to enumurate various possbile cases where an application could be a react. A new variable 
	 * $_SESSION['is_react2'] is introduced, which should be more reliable than $_SESSION['is_react'].
	 * 
	 * Please note that when checking if an application is a react or not, you should never try to 
	 * access $_SESSION['is_react2'] directly. Instead, you should call method $this->isReact().
	 *  
	 * @see GForge #3284 - Don't run Fraud Scan for ecash reacts. (For which the code was first added)
	 * @see GForge #6972 - Last Chance Cash Advance redirect (For which the code was separated into a new method and $_SESSION['is_react2'] was introduced)
	 * @return boolean True if is a react application; otherwise false.
	 */
	public function isReact()
	{
		if (!isset($_SESSION['is_react2']))
		{
			$enterprise_license_key = (
				isset($this->ent_prop_list[$this->property_short])
				&& in_array($this->config->license, $this->ent_prop_list[$this->property_short]['license'])
			);
			
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$olp_process = $app_campaign_manager->Get_Olp_Process($this->Get_Application_ID());
							
			if (preg_match('/react$/i', $olp_process) 
				|| (isset($_SESSION['is_react']) && $_SESSION['is_react'] == TRUE)  
				|| (isset($_SESSION['data']->reckey) && $enterprise_license_key)  
				|| isset($_SESSION['config']->ecash_react))
			{
				$_SESSION['is_react2'] = TRUE;	
			} 
			else 
			{
				$_SESSION['is_react2'] = FALSE;
			}
		}
		
		return $_SESSION['is_react2'];
	}
	
	/**
	 * GForge #10340 [AuMa]
	 * Date Diff
	 * http://www.ilovejackdaniels.com/php/php-datediff-function/
	 * TODO:Cleaning up the code
	 *
	 */
	private function datediff($interval, $datefrom, $dateto, $using_timestamps = false) 
	{
		/*
		$interval can be:
		yyyy - Number of full years
		q - Number of full quarters
		m - Number of full months
		y - Difference between day numbers
		(eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
		d - Number of full days
		w - Number of full weekdays
		ww - Number of full weeks
		h - Number of full hours
		n - Number of full minutes
		s - Number of full seconds (default)
		*/

		if (!$using_timestamps)
		{
			$datefrom = strtotime($datefrom, 0);
			$dateto = strtotime($dateto, 0);
		}
		$difference = $dateto - $datefrom; // Difference in seconds

		switch($interval) 
		{

		case 'yyyy': // Number of full years

			$years_difference = floor($difference / 31536000);
			if (mktime(
				date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom), 
				date("j", $datefrom), 
				date("Y", $datefrom)+$years_difference) > $dateto) 
			{
				$years_difference--;
			}
			if (mktime(
				date("H", $dateto), 
				date("i", $dateto), 
				date("s", $dateto), 
				date("n", $dateto), 
				date("j", $dateto), 
				date("Y", $dateto)-($years_difference+1)) > $datefrom) 
			{
				$years_difference++;
			}
			$datediff = $years_difference;
		break;

		case "q": // Number of full quarters

			$quarters_difference = floor($difference / 8035200);
			while (mktime(date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom)+($quarters_difference*3), 
				date("j", $dateto), date("Y", $datefrom)) < $dateto) {
			$months_difference++;
			}
			$quarters_difference--;
			$datediff = $quarters_difference;
		break;

		case "m": // Number of full months

			$months_difference = floor($difference / 2678400);
			while (mktime(
				date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom)+($months_difference), 
				date("j", $dateto), date("Y", $datefrom)) < $dateto) 
			{
			$months_difference++;
			}
			$months_difference--;
			$datediff = $months_difference;
		break;

		case 'y': // Difference between day numbers

			$datediff = date("z", $dateto) - date("z", $datefrom);
		break;

		case "d": // Number of full days

			$datediff = floor($difference / 86400);
		break;

		case "w": // Number of full weekdays

			$days_difference = floor($difference / 86400);
			$weeks_difference = floor($days_difference / 7); // Complete weeks
			$first_day = date("w", $datefrom);
			$days_remainder = floor($days_difference % 7);
			$odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
			if ($odd_days > 7) 
			{ // Sunday
				$days_remainder--;
			}
			if ($odd_days > 6) 
			{ // Saturday
				$days_remainder--;
			}
			$datediff = ($weeks_difference * 5) + $days_remainder;
		break;

		case "ww": // Number of full weeks

			$datediff = floor($difference / 604800);
		break;

		case "h": // Number of full hours

			$datediff = floor($difference / 3600);
		break;

		case "n": // Number of full minutes

			$datediff = floor($difference / 60);
		break;

		default: // Number of full seconds (default)

			$datediff = $difference;
		break;
		}

		return $datediff;

	}	

	/**
	 * GForge 10340 [AuMa] 
	 * This function will compute the date for the
	 * field and convert it into # of months 
	 * 
	 * @param date1 (ex: residence_start_date, date_of_hire
	 * 
	 * @return int month
	 */
	private function getMonths($date1)
	{
		$ret_val = 0;
		if(trim($date1) == '')
		{
			return $ret_val;
		}

		//********************************************* 
		// Implementation notes:
		// We're going to call the above datediff function
		// for months and then we'll use that number and
		// do a "/" to find out how many years are in that
		// months and a "%" to find out many months
		// are left over
		//********************************************* 
		$months = $this->datediff("m", $date1,  date('m/d/y')  ) + 1;
		//********************************************* 
		// for some reason the above is off by 1 month
		//********************************************* 
		
		return $months;
	}


	/**
	 * Check if the lead is from state WV, VA, GA or any other disallowed state.
	 * 
	 * @return boolean True if the lead is from any disallowed state; otherwise, return false.
	 */	
	public function isDisallowedState()
	{
		$home_state = '';
		
		if (!empty($this->normalized_data['home_state']))
		{
			$home_state = $this->normalized_data['home_state'];
		}
		else if (!empty($_SESSION['data']['home_state']))
		{
			$home_state = $_SESSION['data']['home_state'];
		}
		
		return in_array(strtoupper($home_state), array('VA','WV','GA'));
	}
	
	/**
	 * This function will evaluate the time token values
	 * specifically residence_start_date and 
	 * date_of hire
	 * 
	 * @param value - the value that the user passed in (int/date)
	 *
	 * @return date calculated or FALSE if we can't match the value
	 **/
	private function evaluateTimeToken($value)
	{
		$return_val = FALSE;

		switch ($value) //$this->collected_data['residence_length'])
		{
			case '1':
				// Date < 1 month
				$return_val = date("Y-m-d", strtotime("-15 days"));
			break;
			case '2':
				// Date 1 month
				$return_val = date("Y-m-d", strtotime("-1 months"));
			break;
			case '3':
				// Date 2 months
				$return_val = date("Y-m-d", strtotime("-2 months"));
			break;
			case '4':
				// Date 3 months 
				$return_val = date("Y-m-d", strtotime("-3 months"));
			break;
			case '5':
				// Date 4 - 6 months - store 6 months
				//$return_val = date("Y-m-d", strtotime("-4 months"));
				$return_val = date("Y-m-d", strtotime("-6 months"));
			break;
			case '6':
				// Date 7 months - 12 months - store 12 months
				//$return_val = date("Y-m-d", strtotime("-7 months"));
				$return_val = date("Y-m-d", strtotime("-1 year"));
			break;
			case '7':
				// Date 12 months - 24 months - store 2 years
				//$return_val = date("Y-m-d", strtotime("-1 year"));
				$return_val = date("Y-m-d", strtotime("-2 years"));
			break;
			case '8':
				// Date > 24 months - store 2 years 1 month
				$return_val = date("Y-m-d", strtotime("-25 months"));
			break;
			default:
				$myval = strtotime($value);
				if ($myval !== FALSE)
				{
					// in case we are sent a date instead of an integer
					$return_val = date("Y-m-d", strtotime($value));
				}
		} // End Switch

		return $return_val;

	} // End Function
}
