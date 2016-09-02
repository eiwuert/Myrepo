<?php

/**
* Handles loan confirmation and status viewing.
*
* This version of Customer Service is used to interface with a MySQL 4.1 database using MySQLi.
*
* @author Kevin Kragenbrink & Randy Kochis
* @version 1.0.1
*/

require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once(BFW_CODE_DIR.'Cache_Config.php');
require_once(OLP_DIR.'payroll.php');
require_once(BFW_CODE_DIR . 'login_handler.php');
require_once('setstat.3.php');

class Ent_CS_MySQLi
{
	private $template_messages;		// An object used to display messages on the frontend
	
	private $sqli; // LDB MySQL connection
	private $blackbox;
	
	private $crypt_object;
	
	/**
	 * An array of pagenames that require an app to be in OLP. If the Page_Handler
	 * hits one of these and does not, it just uses the status page right now.
	 *
	 * @var array
	 */
	protected static $pages_require_olp_app = array(
			'ent_online_confirm',
			'ent_online_confirm_legal',
	);
	

	public function __construct( &$sqli, &$sql, $normalized_data, $collected_data, &$event, &$applog, &$session, $property_short, $database, $ent_prop_list = NULL, &$blackbox = NULL, $title_loan = false )
	{

		$this->sqli					= &$sqli;
		$this->sql					= &$sql;
		$this->normalized_data		= $normalized_data;
		$this->collected_data		= $collected_data;
		$this->event				= &$event;
		$this->applog				= &$applog;
		$this->session				= &$session;
		$this->property_short		= strtolower( $property_short );
		$this->holiday_array 		= $this->Get_Holiday_Array();
		$this->days					= array( 'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT' );
		$this->database				= $database;
		$this->title_loan			= $title_loan;
		$this->template_messages	= Template_Messages::Get_Instance();
		$this->olp_mysql;

		$this->cs                       = $_SESSION['cs'];
		$this->cs['client_ip_address']  = $_SESSION['data']['client_ip_address'];
		$this->application_id 		= $this->Process_App_ID($this->collected_data['application_id']);
		$this->blackbox				= &$blackbox;
		
		/** AS OF 09/23 THIS ARRAY SHOULD BE
				PASSED IN FROM OLP: KEEPS THINGS
				ALL IN ONE SPOT [AM] **/
		
		if (!is_array($ent_prop_list))
		{

			$this->ent_prop_list = array (
				"oneclickcash.com" => array(
					"legal_entity" => "One Click Cash",
					"phone" => "800-230-3266",
					"property_short" => "PCL"),
				"unitedcashloans.com" =>array(
					"legal_entity" => "United Cash Loans",
					"phone" => "800-279-8511",
					"property_short" => "UCL"),
				"ameriloan.com" => array(
					"legal_entity" => "Ameriloan",
					"phone" => "800-362-9090",
					"property_short" => "CA"),
				"usfastcash.com" => array(
					"legal_entity" => "US Fast Cash",
					"phone" => "800-640-1295",
					"property_short" => "UFC",
					'use_verify_queue'),
				"500fastcash.com"=>array(
					"legal_entity" => "500 Fast Cash",
					"phone"=>"888-919-6669",
					"property_short" => "D1")
					);

		}
		else
		{
			$this->ent_prop_list = $ent_prop_list;
		}

		// used to create md5 authentication hash
		$this->hash_key = "l04ns";

		// instantiate condor
		$this->condor = new Condor_Client(CONDOR_SERVER);

			define('EC3', true);


		// edb database connection object
		$this->olp_mysql = OLP_LDB::Get_Object($property_short, $this->sqli);
		
		return;

	}

	public function __destruct()
	{
		return;
	}

	/**
	* @param $app_id
	* @return string
	* @desc convert base64 encoded app_id and return it
	*/

	public function Process_App_ID ($app_id)
	{
		$app_id = preg_replace("/[^0-9a-zA-Z=]/","",urldecode($app_id));

		// base64 decode application_id if it's not all digits
		if ( !is_numeric($app_id) )
		{
			$app_id = base64_decode($app_id);
		}

		// back to a number so strip any non numeric at this point
		$app_id = preg_replace("/[^0-9]/","",$app_id);

		return $app_id;
	}
	
	/**
	* @param $bb_winner string
	* @param $cs boolean
	* @desc  send confirmation email for agreed and confirmed apps
	**/

	public function Mail_Confirmation( $bb_winner, $cs = FALSE, $react_info = null )
	{
		// add rc to url if mode is rc
		$prefix = ($_SESSION['config']->mode=='RC') ? 'rc.' : "";
		$legal_entity = $this->ent_prop_list[$bb_winner]["legal_entity"];
		
		// Should IC Support receive this email?
		$email_ic = FALSE;
		// Hello friends, let us hack in the data so that Impact's ecashapp reacts workz
		// Added Impact's second company (ifs) to check - GForge #2982 [DW]
		if(isset($_SESSION['data']['ecashapp']) && Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $_SESSION['data']['ecashapp']))
		{
			$cs = TRUE;
			$email_ic = TRUE; // IC Support should receive this email!
			$this->cs = array_merge($this->cs, $_SESSION['data']);
			$this->cs['qualify'] = $this->cs['qualify_info'];
			$this->cs['fund_date'] = $this->cs['qualify']['fund_date'];
			$this->cs['application_id'] = $_SESSION['application_id'];

			unset($_SESSION["data"]["online_confirm_redirect_url"]);
			
			// Temporary hack to get reacts working properly (11-14-06) [jn]
			$login_hash = md5($this->Get_Application_ID() . $this->hash_key);
			// hack for oneclickcash
			$bb_winner = ( $bb_winner == 'preferredcashloans.com' ) ? 'oneclickcash.com' : $bb_winner;
			$site_name = $prefix . $bb_winner;
			
			$_SESSION["data"]["online_confirm_redirect_url"] = "http://" . $site_name . "/?application_id=" . urlencode( base64_encode( $this->cs["application_id"])).
				"&page=ent_cs_login&login=$login_hash&ecvt&force_new_session&ecash_confirm=1";
		}

		$video = 3; // default help video is 3
		switch($this->property_short)
		{
			case "ufc":
				$event_id = 244;
				$template = '';
				$project_id = 10261;
				$video = 4; // if USFastCash we need to use video 4, not video 3.
				break;
			case "ca":
				$event_id = 240;
				$template = 'AL_';
				$project_id = 10511;
				break;
			case "ucl":
				$event_id = 242;
				$template = 'UCL_';
				$project_id = 10512;
				break;
			case "pcl":
				$event_id = 243;
				$template = 'OCC_';
				$project_id = 10513;
			break;
				case "d1":
				$event_id = 241;
				$template = '500FC_';
				$project_id = 10514;
				break;
			default:
				$template = 'CS_';
				$project_id = 10261; // Defaulting to UFC
				$video = 4;
		}
		if ($cs)
		{
			if(is_array($react_info))
			{
				$this->cs = array_merge($this->cs, $react_info);
			}
			$login_hash = md5($this->Get_Application_ID() . $this->hash_key);
			// hack for oneclickcash
			$bb_winner = ( $bb_winner == 'preferredcashloans.com' ) ? 'oneclickcash.com' : $bb_winner;
			
			// get local vars from ent_prop_list array
			$phone = $this->ent_prop_list[$bb_winner]["phone"];
			$site_name = $prefix . $bb_winner;
			// fund date is stored as yyyy-mm-dd convert to mm/dd/yyyy
			$fund_date = date( "m/d/Y", strtotime( $this->cs["fund_date"] ) );
		// ECash App URL Is Different than the normal one [RL]
		// Repaired the application_id in the url [LR]
			$confirm_url = ($_SESSION["data"]["online_confirm_redirect_url"]) 
				?  $_SESSION["data"]["online_confirm_redirect_url"]
				: "http://" . $site_name . "/?application_id=" . urlencode( base64_encode( $this->cs["application_id"])).
				"&page=ent_cs_login&login=$login_hash&ecvt&force_new_session";			
			$data = array(
				"email_primary"			=> $this->cs["email_primary"],
				"email_primary_name"	=> strtoupper( $this->cs["name_first"] . ' ' . $this->cs["name_last"] ),
				"name"					=> strtoupper( $this->cs["name_first"] . ' ' . $this->cs["name_last"] ),
				"applicationid"			=> $this->cs["application_id"],
				"amount"				=> '$' . number_format( $this->cs["qualify"]["fund_amount"], 2 ),
				"date"					=> $fund_date,
				"confirm"				=> $confirm_url,
				"username"				=> $_SESSION['cs']['login'],
				"password"				=> $_SESSION['cs']['cust_password'],
				"csphone"				=> $phone,
				"site"					=> $site_name,
				"site_name"				=> $site_name,
				"name_view"				=> $legal_entity,
			);
			if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $this->property_short))
			{
				$template = 'OLP_PAPERLESS_FUNDER_REVIEW_IMPACT';
			}
			else 
			{
				$template = 'OLP_PAPERLESS_FUNDER_REVIEW';
			}
		}
		else  // finished with application
		{
			$login_hash = md5($this->Get_Application_ID() . $this->hash_key);
			
			// get local vars from ent_prop_list array passed in
			$site_name = $prefix . $bb_winner;
			$fund_date = date( "m/d/Y", strtotime( $_SESSION['data']['qualify_info']['fund_date'] ) );


			// grab date from page_trace so we can feed it into the template
			$date_created = key($_SESSION['page_trace']);

			$date_app = strtotime($date_created);

			$date_app_created = date( "m/d/Y", $date_app  );
			$time_app_created = date( "h:iA" , $date_app );

			$marketing_site_array = parse_url($_SESSION['config']->site_name);
		 	$marketing_site = (array_key_exists('host', $marketing_site_array)) ? $marketing_site_array['host'] :  $marketing_site_array['path'];

			$mark_site = explode(".", $marketing_site);

			// ECash App URL Is Different than the normal one [RL]
			$confirm_url = ($_SESSION["data"]["online_confirm_redirect_url"]) 
				?  $_SESSION["data"]["online_confirm_redirect_url"]
				: "http://" . $site_name . "/?application_id=" . urlencode( base64_encode( $this->Get_Application_ID())).
				"&page=ent_cs_login&login=$login_hash&ecvt&force_new_session";
			$data = array(
				"site_name"					=> $site_name,
				"name_view"					=> $legal_entity,
				"email_primary" 			=> $this->normalized_data["email_primary"],
				"email_primary_name"		=> $this->normalized_data["name_first"] . ' ' . $this->normalized_data["name_last"],
				"name"						=> strtoupper( $this->normalized_data['name_first'] ) . ' ' .
											   strtoupper( $this->normalized_data["name_last"] ),
				"amount"					=> '$' . number_format( $this->normalized_data['qualify_info']["fund_amount"], 2 ),
				"application_id"			=> $_SESSION['application_id'],
				"confirm_link"				=> $confirm_url,
				"video_link"				=> "http://netxstudios.sitestream.com/$project_id/$video.html",
				"estimated_fund_date_1"		=> $fund_date,
				"estimated_fund_date_2" 	=> date( "m/d/Y", strtotime( "+1 day", strtotime( $_SESSION['data']['qualify_info']['fund_date'] ) ) ),
				"username"					=> $_SESSION['username'],
				"password"					=> $_SESSION['password'],
				"client_ip_address"			=> $_SESSION['data']['client_ip_address'],
				"date_app_created"			=> $date_app_created,
				"time_app_created"			=> $time_app_created,
				"marketing_site"			=> $mark_site[0]
			);

			$template .= "OLP_PAPERLESS_I_AGREE";
		}

		$return = TRUE;

		// debug only
		$_SESSION['login_hash'] = $data['confirm_link'];
		// send mail next
		//Send Mail using trendex or Ole based on 
		$ic_email = "support@impactcashusa.com";
		try
		{
			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);
			$res = $tx->sendMessage('live',$template,$data['email_primary'],$_SESSION['statpro']['track_key'],$data);
			if($email_ic && strtoupper($_SESSION['config']->mode) == 'LIVE')
			{
		    	$res = $tx->sendMessage('live', $template, $ic_email,$_SESSION['statpro']['track_key'],$data);
			}
		}
    	catch(Exception $e)
		{
			$return = FALSE;
			$this->applog->Write("Trendex mail $template failed. ".$e->getMessage()." (Session: {$_SESSION['data']['unique_id']})");
		}
	
		if($res === FALSE)
		{
			$return = FALSE;
			$this->applog->Write("Trendex mail $template failed.(session id:{$_SESSION['data']['unique_id']})");
		}
		
		return $return;
	}

	public function Mail_Password()
	{
		$prefix = ($_SESSION['config']->mode=='RC') ? 'rc.' : "";

		$errors = '';

		$login_handler = new Login_Handler($this->sqli, $this->property_short, $this->database, $this->applog);
		$results = $login_handler->Find_User_Info($this->normalized_data['cust_email']);

		if( $results )
		{
			// use ole for this after template is made
			$password = $login_handler->Decrypt_Password($results['password']);

			if($password === FALSE)
			{
				$errors = 'The login or password you entered does not exist.  Please try again.';
				$next_page = 'cs_password';
				return array( 'page' => $next_page, 'errors' => $errors );
			}

			$login = $results['login'];
			$email = $this->normalized_data['cust_email'];
			$name_view = str_replace('.com', '', $_SESSION['config']->name_view);
			
			// gForge #3598 - Our email forgot password email template had page hardcoded, I changed the template in trendex and added this card_site value to the FCP site
			// Future card based sites should also have card_site set to true
			$page = ($_SESSION["config"]->card_site) ? "/?page=ent_cs_card_login" : "/?page=ent_cs_login";

			// gForge #3598 - Added page in as additional value.
			$data = array(
						'firstname' => $results['name_first'],
						'lastname' => $results['name_last'],
						'source' => $_SESSION['data']['client_url_root'],
						'ip_address' => $_SESSION['data']['client_ip_address'],
						'subject' => "Login information",
						'login' => $login,
						'password' => $password,
						'site_link' => 'http://'.$prefix . $_SESSION["config"]->site_name.$page,
						'site_name' => $prefix . $_SESSION["config"]->site_name,
						'name_view' => $name_view,
						'sender_name' => (preg_replace('!<sup>.*</sup>!si', '', $name_view)) ." - Approval Department <no-reply@".$_SESSION["config"]->site_name.">",
						'email_primary_name' => $email,
						'email_primary' => $email,
						'signup_date' => date('Y-m-d H:i:s')
					);
			
			if(USE_TRENDEX){
				//Send via Trendex
				$tx = new tx_Mail_Client(false);

				try 
				{
					$r = $tx->sendMessage('live', "6991", $email, $_SESSION['statpro']['track_key'],$data);
				
				}
				catch(Exception $e)
				{
					$this->applog->Write("Trendex mail password message failed. " . $e->getMessage() . " Called from " . __FILE__ . ":" . __LINE__);
				}
			
				//Log if Trendex failed
				if($r === FALSE)
				{
					$this->applog->Write("Trendex mail password message failed. Called from " . __FILE__ . ":" . __LINE__);
				}
				else
				{
				// Hit cs_mail_passwd
				Stats::Hit_Stats(
					'cs_mail_passwd',
					$this->session,
					$this->event,
					$this->applog,
					$user_data['cs']['application_id'],
					NULL,
					TRUE
				);
				}	
			}	
			else
			{	
				//Send via Ole
				require_once("ole_smtp_lib.php");
				$mail = new Ole_Smtp_Lib();
            
            	$mailing_id = $mail->Ole_Send_Mail("OLP_MAIL_PASSWORD_NEW", NULL, $data);
				if (!is_numeric($mailing_id))		
				{
					$ole_applog = OLP_Applog_Singleton::Get_Instance(APPLOG_OLE_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
                	$ole_applog->Write("OLE Send Mail failed. Last message: \n" . print_r($data,true) . "\nCalled from " . __FILE__ . ":" . __LINE__);
				}
			}

			$_SESSION['data']['message'] = 'Your login/password has been mailed to the email address entered.';

			// gForge #3598 - Added a check for card_site config option to determine the next page after sending the password.
			$next_page = ($_SESSION["config"]->card_site) ? "ent_cs_card_login" : "ent_cs_login";
		}
		else
		{
			$errors = 'The login you entered does not exist. Please try again.';
			$next_page = "cs_password";
		}

		return array( 'page' => $next_page, 'errors' => $errors );
	}

	
	
	/**
	 *  Sets the is_react flag in application in OLP DB
	 */
	public function Set_Is_React($application_id)
	{
		if(empty($application_id))
        {
            if(($application_id = $this->Get_Application_ID()) === FALSE)
            {
                $error = "Cannot Update App - App ID is not set";
                $this->applog->Write($error);
                throw new Exception($error);
            }
        }
        
        $app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
        
        $app_campaign_manager->Update_Is_React($application_id);
	}
	
	/**
	 *  Reset to enterprise config
	 */
	public function Set_Enterprise_Config($cs = NULL)
	{
		
		// backwards compatibility -- even though
		// it never worked anyways!
		if (!is_array($cs))
		{
			$cs = $_SESSION['cs'];
		}
		
		$current_settings = array(
			'enable_rework' => $_SESSION['config']->enable_rework,
			'use_new_process' => $_SESSION['config']->use_new_process,
			'ecash3_prop_list' => $_SESSION['config']->ecash3_prop_list
		);
		
		// for SMS reacts, override the promo_id [RM]
		if ($_SESSION['config']->override_react_promo_id)
		{
			$cs['promo_id'] = $_SESSION['config']->promo_id;
			$cs['promo_sub_code'] = $_SESSION['config']->promo_sub_code;
		}
		
		$originating_config = array();
		// grab the config for the originating site
		if (is_array($cs) && isset($cs['license_key']))
		{
			try
			{
				$originating_config = $this->Setup_Config($cs['license_key'], $cs['promo_id'], $cs['promo_sub_code'], NULL);
			}
			catch(Exception $e)
			{
				$originating_config = array();
			}
		}
		
		// for teleweb: otherwise, we'll override the promo_override
		if (isset($this->collected_data['promo_override']))
		{
			$cs['promo_id'] = $this->collected_data['promo_id'];
			$cs['promo_sub_code'] = $this->collected_data['promo_sub_code'];
		}

		// set the config to the enterprise config
		$config = $this->Setup_Config( $this->normalized_data['enterprise_data']['license'][$_SESSION['config']->mode], $cs['promo_id'], $cs['promo_sub_code'], NULL );
		if (isset($originating_config->online_confirmation)) $config->online_confirmation = $originating_config->online_confirmation;
		if (isset($originating_config->display_captcha)) $config->display_captcha = $originating_config->display_captcha;
		
		$config = OLP::Overwrite_Config_Rerun($config);
		
		// save the old config -- this is a bit of a hack,
		// but we're down to the wire now...
		$_SESSION['old_config'] = $originating_config;
		$_SESSION['config'] = clone $config;
		
		foreach($current_settings as $name => $value)
		{
			$_SESSION['config']->$name = $value;
		}

		if(!empty($cs))
		{
			$_SESSION['cs'] = (!empty($_SESSION['cs'])) ? array_merge($_SESSION['cs'], $cs) : $cs;
		}		

		// force statpro to recalc the space_key
		$_SESSION['statpro']['space_key'] = NULL;
		
		// retain previous track key
		$_SESSION['statpro']['track_key'] = $cs['track_key'];
		
		// run setup stats with the promo_id from ent_cs
		// for old model only
		if (!$_SESSION['config']->statpro_only)
		{
			$_SESSION['stat_info'] = Set_Stat_3::Setup_Stats (NULL, $config->site_id, $config->vendor_id, $config->page_id, $cs['promo_id'], $cs['promo_sub_code'], $config->promo_status);
		}

		// We're no longer using the old process not even for reacts. This is for
		// The sake of unsigned apps.
		$property_short = SiteConfig::getInstance()->property_short;
		if (empty($property_short) || !EnterpriseData::isCFE($property_short))
		{
			$_SESSION['config']->use_new_process = !$this->Check_For_LDB_App($this->Get_Application_ID());
		}
		else
		{
			$_SESSION['config']->use_new_process = TRUE;
		}
	}

	public function Page_Handler( $application_id = '' )
	{
		// Array of pages to check applications for no_esig status's
		// in eCash
		$status_check_pages = array(
			'ent_online_confirm',
			'ent_online_confirm_legal',
			'ecash_sign_docs',
		);
		
		// Array of eCash statuss not to allow on the above pages
		$no_esig_statuses = array(
			'/applicant/underwriting/>',    // All Approved Statuses (include dequeued)
			'/applicant/verification/>',    // All Confirmed Statuses (include dequeued)
			'/applicant/denied',            // Denied
			'/customer/servicing/active',   // Active
			'/customer/servicing/approved', // Pre-Fund
			'/customer/servicing/past_due', // Past Due
			'/customer/paid',               // Inactive (Paid)
			'/external_collections/>',      // External Collections
			'/customer/collections/>',      // All of the Collections statuses
			'/prospect/expired'             // Expired applications
		);
		
		/*****************/
		/* first hit so get user data and set next page */
		/*****************/
		if($application_id != '') $this->application_id = $application_id;
		$application_id = $this->Get_Application_ID();

		// This actually checks ldb, even if it's cfe that way 
		// We can pull all the data from the right place.
		$old_use_new_process = $_SESSION['config']->use_new_process;
		if ($_SESSION['config']->use_new_process)
		{
			$_SESSION['config']->use_new_process = !$this->Check_For_LDB_App($application_id);
		}
		$user_data = $this->Get_User_Data($application_id);
		$_SESSION['config']->use_new_process = $old_use_new_process;
		
		//Preact check
		if(isset($user_data['cs']['olp_process']) && $user_data['cs']['olp_process']=='ecashapp_preact')
	    {
		  	$_SESSION['is_preact'] = "yes";
		}
		
		//Manually populate weird paydate array
		$_SESSION["pay_dates"] = array();
		$_SESSION["pay_dates"]["pay_date1"] = $user_data['cs']['new_pay_dates'][0];
		$_SESSION["pay_dates"]["pay_date2"] = $user_data['cs']['new_pay_dates'][1];
		$_SESSION["pay_dates"]["pay_date3"] = $user_data['cs']['new_pay_dates'][2];
		$_SESSION["pay_dates"]["pay_date4"] = $user_data['cs']['new_pay_dates'][3];
		
		// don't have an application_id so return them to the login page
		if ( !$application_id && !$user_data['cs']['application_id'] )
		{
			$return['page'] = "ent_cs_login";
			return $return;
		}
		
        //Check if config is set to bb and force loading the ent config
        if(isset($_SESSION['config']->property_short) && $_SESSION['config']->property_short=="bb")
        {
            // Force the call to get a new config
            $this->Set_Enterprise_Config($user_data['cs']);
        }
        
		// Log session_id in olp.cs_session
		// User logs in w/ username / password
		$this->Log_CS_Session( $user_data['cs']['application_id'] );
		
		$_SESSION['cs']['confirmed'] = ($user_data['page'] == 'ent_status') ? 1 : null;

		// Implode the array andget the ids 
		$no_esig_statuses = implode(':', $no_esig_statuses);
		$no_esig_statuses = $this->olp_mysql->Status_Glob($no_esig_statuses);
		
		// Mantis #12136 [DY] (follow what BrianF did for Mantis #10993)
		if (in_array($this->collected_data['page'], $status_check_pages))
		{
			$app_status = $this->Get_Application_Status($user_data['cs']['application_id']);
			if(in_array($app_status['id'], $no_esig_statuses))
			{
				$this->collected_data['page'] = $this->next_page  = 'ent_status';
			}
		}
			
		
		//If it's one of our pages that requires an olpapp, and we don't ahve one. Return ent_status.
		if (in_array(strtolower($this->collected_data['page']), self::$pages_require_olp_app) && 
			!$this->appExistsInOlp($application_id))
		{
			$this->collected_data['page'] = $this->next_page  = 'ent_status';
		}
		
		if ( $this->collected_data['submit'] != "I Accept, Send My Cash"
			&& $this->collected_data['page'] != "ent_confirm_legal"
			// NEW ONLINE CONFIRMATION PROCESS
			&& ($this->collected_data['page'] != "ent_online_confirm")
			&& ($this->collected_data['page'] != "ent_online_confirm_legal")
			// *******************************
			&& $this->collected_data['submit'] != "I Decline, I do not want this loan"
			&& $this->collected_data['submit'] != "I DO NOT AGREE - Don't Send Any Cash"
			&& !$this->collected_data['legal_agree'] )
		{
			// checks for list variable to return user to list of loans
			if(isset($_SESSION['data']['list']) && !$_SESSION['cs']['multiple_trans'] && $_SESSION['data']['page'] == 'ent_cs_login')
			{
				$_SESSION['cs']['multiple_trans'] = 1;
				unset($_SESSION['data']['list']);
			}
			
			// check for multiple transactions and no trans id - push them back to ent_cs_account page
			if ($_SESSION['cs']['multiple_trans'] && !$application_id)
			{
				$return['cs'] = $_SESSION['cs'];
				$return['page'] = "ent_cs_account";
				return $return;
			}
			
			// Find next page
			switch($this->normalized_data['page'])
			{
				case 'ent_cs_login':
					if(isset($_SESSION['config']->property_short) && $_SESSION['redirect_logged'] != 2)
					{
						if(($app_id = $this->Get_Application_ID()) !== FALSE || ($app_id = $user_data['cs']['application_id']) !== NULL)
					    {
							$this->event->Log_Event('REDIRECT_PAGE','PASS', $_SESSION['config']->property_short, $app_id);
							$_SESSION['redirect_logged'] = 2;
					    }
					}
										
					$this->next_page = $user_data['page'];
					break;
			
					
					
				case 'ent_payment_opts':
				case 'ent_payment_opts_submitted':

					if($_SESSION['cs']['logged_in'])
					{
						try
						{
							$page_data = $this->Process_Payment_Options();
							$this->cs = array_merge($user_data['cs'], $page_data);
							$this->next_page = 'ent_payment_opts';
						}
						catch(Exception $e)
						{
							$this->cs = $page_data;
							$this->next_page = 'ent_cs_login';
						}
					}
					
					break;
					
				case 'ent_cs_password_change':
				// make sure they are logged in or came in with an authenticated link (md5_hash_match)
				if ( !empty($_SESSION['cs']['login']) || $_SESSION['cs']['md5_hash_match'])
				{
					$this->next_page = $this->Process_Password_Change();
				}
				break;
				
				// ecash requests that customer re-sign legal docs
				// this overrides our normal processing and takes them straight to the
				// esig page - ent_confirm_legal and creates condor docs
				case 'ecash_sign_docs':
					
					// merge $user_data with this->cs
					$this->cs = array_merge($this->cs, $user_data['cs']);
					
					// compare_data needs normalized_data[paydate] set
					$this->normalized_data['paydate'] = $user_data['cs']['paydate'];
					$this->Compare_Data($application_id);
					
					$qualify = $this->Get_Qualify_Info(TRUE);
					
					$this->cs['qualify']  = $qualify;
					$this->cs['paydates'] = $this->new_pay_dates;
					$this->cs['transaction_id'] = $application_id;
					
					$this->cs['new_paydate'] = $this->normalized_data['paydate'];

					// set this to use when we update the ecash document table
					$_SESSION['data']['ecash_document_id'] = $this->normalized_data['document_id'];
					// flag for post processing the esig page
					$_SESSION['data']['ecash_sign_docs'] = 1;
					
					// set unique id
					$_SESSION['data']['unique_id'] = session_id();
					$_SESSION['data'] = array_merge($_SESSION['data'], $user_data['cs']);
			
			
					if(!in_array($this->property_short, $_SESSION['config']->ecash3_prop_list))
					{
						//We need to generate new condor docs here
						$session = $this->Prepare_Condor_Data($application_id);
						$session['config'] = clone $_SESSION['config'];
						$session['application_id'] = $application_id;
		
						// generate the legal documents
						$this->condor->Preview_Docs('paperless_form', $session);
						$this->condor->Condor_Get_Docs('signature_request', '', $session);
						
						$response = $this->condor->response;
						unset($response->data);
						
						// save it
						$_SESSION['condor'] = $response;
					}
					
					
					$app_status = $this->Get_Application_Status($user_data['cs']['application_id']);
					
					if(in_array($app_status['id'], $no_esig_statuses))
					{
						$this->next_page  = 'ent_status';
					}
					else
					{
						$this->next_page  = 'ent_online_confirm_legal';
					}
					break;
					
				default:
					$this->next_page = $user_data['page'];
					break;
				
			}
			
			// set to normalized data if present otherwise use what we passed in
			if( $user_data['cs']['application_id'] )
			{
				$application_id = $user_data['cs']['application_id'];
			}
			
			// If it's a page that requires an olp app, make sure it's in OLP
			if (in_array(strtolower($this->next_page), self::$pages_require_olp_app) && 
				!$this->appExistsInOlp($application_id))
			{
				$this->next_page = 'ent_status';
			}
			
			// If it's a page that requires an ldb stauts check,
			// Check the ldb stauts.
			if (in_array($this->next_page, $status_check_pages))
			{
				$app_status = $this->Get_Application_Status($application_id);
				if(in_array($app_status['id'], $no_esig_statuses))
				{
					$this->next_page  = 'ent_status';
				}
			}

			//Process next page
			switch( $this->next_page )
			{
				
				case 'ent_cs_account':
					// We've got multiple transactions, so we're going to need to show them the
					// account page.  Let's transfer our CS data over into CS
					$this->cs   = array_merge( $this->cs, $user_data['cs'] );
					break;
					
				case 'ent_status':				
					if($this->Check_For_LDB_App($application_id))
					{
						//rsk
						$status_info = $this->Get_User_Status($application_id);

						if($this->ent_prop_list[$_SESSION['config']->site_name]['new_ent'] && $_SESSION['cs']['logged_in'])
						{
							// Mantis #5512 - Running the Process_Pay.. function here so that we know whether they are able to view payment options before they go to that section.
							$page_data = $this->Process_Payment_Options($application_id);
							$status_info = array_merge($page_data, $status_info);
						}

						$this->cs = array_merge($user_data['cs'], $status_info);
					}
					else
					{
						$this->cs = $user_data['cs'];
						$this->cs['app_status'] = 'pending';
					}
					break;
					
				case 'ent_cs_password_change':
					$this->Process_Password_Change();
					break;

				//Need to re-generate docs so that they are accurate.
				case 'ent_online_confirm_legal':

					if(!in_array($this->property_short, $_SESSION['config']->ecash3_prop_list))
					{
						//We need to generate new condor docs here
						$session = $this->Prepare_Condor_Data($application_id);
						$session['config'] = clone $_SESSION['config'];
						$session['application_id'] = $application_id;
		
						// generate the legal documents
						$this->condor->Preview_Docs('paperless_form', $session);
						$this->condor->Condor_Get_Docs('signature_request', '', $session);
						
						$response = $this->condor->response;
						unset($response->data);
						
						// save it
						$_SESSION['condor'] = $response;
					}

					if(is_array($user_data['cs']))
					{
						$this->cs = array_merge($this->cs, $user_data['cs']);
					}
					break;
					
				default: // goto ent_confirm page
					if(is_array($user_data['cs'])) $this->cs = array_merge( $this->cs, $user_data['cs'] );
					break;
					
			}
			
		}
		elseif ($this->collected_data['page'] === 'ent_online_confirm')
		
		{

			// get the result of the page
			if (isset($this->collected_data['submit'])) $confirm = (strtolower($this->collected_data['submit']) !== 'cancel');
			else $confirm = NULL;

			if ($confirm === TRUE)
			{
				// The enterprise config should already be set in cs_login.  Re-setting it
				// here was overwriting $_SESSION['old_config'] with the enterprise license
				// key, at which point anyone who went through an app, then went back to
				// start a new one would cause stat exceptions because it would be trying to
				// hit the wrong stat table. [CB]
				/*if ((!isset($_SESSION['cs']['application_id'])) || ($_SESSION['cs']['application_id'] != $this->application_id))
				{
					$cs = $this->Get_User_Data();
					$cs = $cs['cs'];
				}
				
				// Grab a new configuration pointing to the enterprise set
				$this->Set_Enterprise_Config($cs);*/
				
				// Hit the _confirm stat
				$my_stat = strtolower($_SESSION['config']->property_short . "_confirm");
				Stats::Hit_Stats($my_stat, $this->session, $this->event, $this->applog, $application_id);
				
                if(!isset($user_data['cs']) || !isset($user_data['cs']['application_id']))
                {
                    $error = "Cannot Update Application - App ID is not set";
                    $this->applog->Write($error);
                    throw new Exception($error);
                }


				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
                
				// Need to update the fund_amount
				if(isset($this->normalized_data['fund_amount']) && ($this->normalized_data['fund_amount'] != $this->normalized_data['fund_amount_old']))
				{
					$field['fund_qualified'] = $this->normalized_data['fund_amount'];

					if($_SESSION['config']->use_new_process)
					{
						$app_campaign_manager->Update_Fund_Amount($user_data['cs']['application_id'], $this->normalized_data['fund_amount']);
					}
					else
					{
						$this->olp_mysql->Update_Application($field, $user_data['cs']['application_id']);
					}
				}
				
				// update eCash status
				$this->Update_Status($user_data['cs']['application_id'], 'confirmed');
				
				// update OLP status
				$app_campaign_manager->Update_Application_Status($user_data['cs']['application_id'], 'CONFIRMED');
				
				// hit confirmed stat
				$_SESSION['cs']['confirmed'] = "1";
				Stats::Hit_Stats('confirmed', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
				
				// hit the react_confirmed stat if it's a react
				if((isset($_SESSION['is_react']) && $_SESSION['is_react'] == true) || isset($_SESSION['data']->reckey) || isset($_SESSION['config']->ecash_react))
				{
                	try
                	{
                	Stats::Hit_Stats('react_confirmed', $this->session, $this->event, $this->applog, $user_data['cs']['application_id'], NULL, TRUE);
                	}catch(Exception $e){}
				}
								
				// prepare the data for condor
				$session = $this->Prepare_Condor_Data($user_data['cs']['application_id']);
				$session['config'] = clone $_SESSION['config'];
				$session['application_id'] = $user_data['cs']['application_id'];
				
				// hack this stuff in
				$_SESSION['cs'] = array_merge($_SESSION['cs'], $session['data']);
				
				/*
					We need to update the application in LDB. The situation can arrise where
					the customer applies for the loan on a Thursday, doesn't confirm/agree until
					Friday, where their new estimated fund date is now Saturday, which gets
					pushed to Monday. But then Monday could be within the 10 days to their next
					pay day and the due date changes to their next pay date. In this case, the
					due date in LDB will be wrong, but the loan docs will be correct. [BF]
				*/
				$this->Update_User_Data($session['application_id'], TRUE);

				if(!in_array($this->property_short, $_SESSION['config']->ecash3_prop_list))
				{
					// generate the legal documents
					$this->condor->Preview_Docs("paperless_form", $session);
					$this->condor->Condor_Get_Docs('signature_request', "", $session);
					
					$response = $this->condor->response;
					unset($response->data);
					
					// save it
					$_SESSION['condor'] = $response;
				}
				
				// Since we updated the fund ammount we must have calc'd new qualify info. Lets update LDB with that info [RL]
				// Added some checks just in case we dont have all the info we dont want to update LDB
				if(	!$_SESSION['config']->use_new_process &&
					isset($field['fund_qualified']) &&
					isset($_SESSION['cs']['qualify']['finance_charge']) &&
					isset($_SESSION['cs']['qualify']['apr']) &&
					isset($_SESSION['cs']['qualify']['total_payments']) &&
					isset($_SESSION['cs']['qualify']['payoff_date']) 
				)
				{
					//$field['date_fund_estimated']			= $_SESSION['cs']['qualify']['fund_date'];
					//$field['fund_qualified']				= $_SESSION['cs']['qualify']['fund_amount'];
					
					$field['finance_charge']				= $_SESSION['cs']['qualify']['finance_charge'];
					$field['apr']							= $_SESSION['cs']['qualify']['apr'];
					$field['payment_total']					= $_SESSION['cs']['qualify']['total_payments'];
					$field['date_first_payment']			= $_SESSION['cs']['qualify']['payoff_date'];
					
					
					$this->olp_mysql->Update_Application($field, $user_data['cs']['application_id']);					
				}
					

				// display our online-confirmation legal page
				$this->next_page = 'ent_online_confirm_legal';

			}
// 			// never get here, this is actually done inside OLP -- go figure.
// 			elseif ($confirm === FALSE)
// 			{
// 				
// 				// update eCash status
// 				$this->Update_Status($user_data['cs']['application_id'], 'confirm_declined');
// 				
// 				// update OLP status
// 				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
// 				$app_campaign_manager->Update_Application_Status($user_data['cs']['application_id'], 'CONFIRMED_DISAGREED');
// 				
// 			}
// 			else
// 			{
// 				// hit popconfirm stat
// 				Stats::Hit_Stats('popconfirm', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
// 				$this->next_page = 'ent_online_confirm';
// 			}
			
		}
		elseif ($this->collected_data['page'] === 'ent_online_confirm_legal')
		{

			// get the result of the page
			if (isset($this->collected_data['legal_agree'])) $agree = TRUE;
			else $agree = NULL;
			
			// Prevent multiple simultaneous esigs
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_type = $app_campaign_manager->Get_Application_Type($user_data['cs']['application_id']);
			if($app_type == 'EXPIRED')	// Change this to proper type 'Expired'
			{
				$agree = NULL;
				$this->next_page = 'ent_cs_login';
			}
			else
			{
				$result = true;
				// Added Impact's second company (ifs) to check - GForge #2982 [DW]
				if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $this->property_short))
				{
					$result = $this->Active_Customer_Check();
				}

				if($result)
				{
					// Application types determined to be 'pipeline' apps.
					$pipeline_app_types = array ("PENDING", "CONFIRMED", "CONFIRMED_DISAGREED", "DISAGREED");
					
					// Check for other 'pipeline' apps, then set to 'Expired'
	 				if($other_apps = $this->Get_Other_Apps($user_data['cs']['application_id']))
					{
						foreach($other_apps as $app)
						{
							if(in_array($app['type'], $pipeline_app_types))
							{
								// Change this app's status to Expired
								$app_campaign_manager->Update_Application_Status($app['id'], 'EXPIRED'); 
							}
						}
					}
				}
				else
				{
					$agree = NULL;
					$app_campaign_manager->Update_Application_Status($user_data['cs']['application_id'], 'FAILED');
					$app_campaign_manager->Update_Status_History($user_data['cs']['application_id'], 'denied');
					$this->next_page = 'app_declined';
				}
			}

			if ($agree === TRUE)
			{
				
                if(!isset($user_data['cs']) || !isset($user_data['cs']['application_id']))
                {
                    $error = "Cannot Update Application - App ID is not set";
                    $this->applog->Write($error);
                    throw new Exception($error);
                }
                elseif(!isset($this->application_id))
                {
                	$this->application_id = $user_data['cs']['application_id'];
                }
                
				// update eCash status
				$this->Update_Status($user_data['cs']['application_id'], 'agree');
				
				// update OLP status
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$app_campaign_manager->Update_Application_Status($user_data['cs']['application_id'], 'AGREED');
				//regenerate the loan_note dates and update loan_notes for the application so that dates might be accurate
				$qualify_info = $this->Get_Qualify_Info();

				$_SESSION['qualify'] = $qualify_info;
				$app_campaign_manager->Update_Loan_Note($user_data['cs']['application_id'],
														$qualify_info['fund_date'],
														$qualify_info['fund_amount'],
														$qualify_info['apr'],
														$qualify_info['payoff_date'],
														$qualify_info['finance_charge'],
														$qualify_info['total_payments']);
				
				// hit agree stat
                try
                {
				    Stats::Hit_Stats('agree', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
					Stats::Hit_Stats('new_document_react', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
                } 
                catch(Exception $e) { }
                
                // hit the react_agree stat if it's a react
				if((isset($_SESSION['is_react']) && $_SESSION['is_react'] === true) || isset($_SESSION['data']->reckey) || isset($_SESSION['config']->ecash_react))
				{
                	try
                	{
                		Stats::Hit_Stats('react_agree', $this->session, $this->event, $this->applog, $user_data['cs']['application_id'], NULL, TRUE);
                	}
					catch(Exception $e)
					{
					}
				}
				
				if(!in_array($this->property_short, $_SESSION['config']->ecash3_prop_list))
				{
					// sign our documents
					$this->condor->Condor_Get_Docs('signature_response', 'TRUE', '');
				}
				
				// perform any final processing and queue the app
				$this->Final_Processing();
				
				// Hit TY pop_agree
				Stats::Hit_Stats('popty', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
				
				//Ugly Impact Hack
				/**
				 * Added Impacts second company (ifs) to ugly hack for now.
				 * Also changed stats to use $_SESSION['config']->property_short instead
				 * of just using ic because ifs needs to hit ifs stats - GForge #2982 [DW]
				 */
				if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $_SESSION['config']->property_short)
					&& !in_array(strtolower($_SESSION['old_config']->property_short), array('ic','ips','ifs')))
				{
                    $temp_stat_info = $_SESSION["stat_info"];
					$_SESSION["stat_info"] = Set_Stat_3::Setup_Stats(NULL,
																	 $_SESSION["old_config"]->site_id, 
	                                                                 $_SESSION["old_config"]->vendor_id, 
	                                                                 $_SESSION["old_config"]->page_id, 
	                                                                 $_SESSION["old_config"]->promo_id, 
	                                                                 $_SESSION["old_config"]->promo_sub_code, 
	                                                                 $_SESSION["old_config"]->promo_status);
                                                                 
					Stats::Hit_Stats( 'bb_'.$_SESSION['config']->property_short.'_agree', $this->session, $this->event, $this->applog,  $user_data['cs']['application_id'], NULL, TRUE );
					
					//Hit stat on new loan
					if(!is_array($_SESSION['react'])) Stats::Hit_Stats( 'bb_'.$_SESSION['config']->property_short.'_new_app', $this->session, $this->event, $this->applog,  $user_data['cs']['application_id'], NULL, TRUE );
					
					$_SESSION["stat_info"] = $temp_stat_info;
				}
				else
				{
					Stats::Hit_Stats( 'bb_' . strtolower($_SESSION['config']->property_short) . '_agree', $this->session, $this->event, $this->applog,  $user_data['cs']['application_id'], NULL, TRUE );
					
					//Hit stat on new loan
				/**
				 * Added Impacts second company (ifs) to check. Also changed stats to 
				 * use $_SESSION['config']->property_short instead of just using ic 
				 * because ifs needs to hit ifs stats - GForge #2982 [DW]
				 */
					if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $_SESSION['config']->property_short) && !is_array($_SESSION['react'])) 
					   Stats::Hit_Stats( 'bb_' . strtolower($_SESSION['config']->property_short) . '_new_app', $this->session, $this->event, $this->applog,  $user_data['cs']['application_id'], NULL, TRUE );
				}
				

				//We don't want to increment the stat for ecashapp reacts
				if(!preg_match('/^ecashapp/is', $this->Get_Online_Confirmation_Status($user_data['cs']['application_id'])))
				{
					// increment our counter for this target
					$limits = new Stat_Limits($this->sql, $this->database);
					$result = $limits->Increment( 'bb_' . strtolower($_SESSION['config']->property_short) . '_agree', NULL, NULL, NULL);
					
					//If this is a second loan, increment the limit, but only for CLK
					if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_CLK, $_SESSION['config']->property_short)
						&& $this->event->Check_Event($user_data['cs']['application_id'], EVENT_SECOND_LOAN))
					{
						$limits->Increment('second_loan', NULL, NULL, NULL);
					}
				}
								
				// Trying something new...
				$_SESSION['cs']['agreed'] = 1;
				
				// display our online-confirmation legal page
				$this->next_page = 'ent_thankyou';
				
			}
// 			// never get here, this is actually done inside OLP -- go figure.
// 			elseif ($agree === FALSE)
// 			{
// 				
// 				// update eCash status
// 				$this->Update_Status($user_data['cs']['application_id'], 'disagree');
// 				
// 				// update OLP status
// 				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
// 				$app_campaign_manager->Update_Application_Status($user_data['cs']['application_id'], 'DISAGREED');
// 				
// 			}
// 			else
// 			{
// 				
// 				// return qualify info
// 				$qualify = $this->Get_Qualify_Info(FALSE);
// 				$_SESSION['cs']['qualify']  = $qualify;
// 
// 				// hit popagree stat
// 				Stats::Hit_Stats('popagree', $this->session, $this->event, $this->applog, $user_data['application_id']);
// 				$this->next_page = 'ent_online_confirm_legal';
// 				
// 			}
			
		}
		elseif($this->collected_data['page'] == 'ent_reapply_legal')
		{
			if(!$application_id) $application_id = $this->Get_Application_ID();
			
            if(empty($application_id))
            {
                $error = "Cannot Update Application - App ID is not set";
                $this->applog->Write($error);
                throw new Exception($error);
            }
            
			// prepare the data for condor
			$session = $this->Prepare_Condor_Data($application_id);
				
			// hack this stuff in
			$_SESSION['cs'] = array_merge($_SESSION['cs'], $session['data']);
            
			// update information
			$this->Update_User_Data( $application_id, TRUE); // fix bug #6886 [DY]
			
			$status_info = array();
			if(!$_SESSION['config']->use_new_process)
			{
				$status_info = $this->Get_User_Status( $application_id );
			}
			
			// update application_type in olp.application db
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Update_Application_Status($application_id, 'AGREED');
			
			
			// hit the react_agree stat if it's a react
				if((isset($_SESSION['is_react']) && $_SESSION['is_react'] === true) || isset($_SESSION['data']->reckey) || isset($_SESSION['config']->ecash_react))
				{
                	try
					{
                		Stats::Hit_Stats('react_agree', $this->session, $this->event, $this->applog, $application_id, NULL, TRUE);
					}
					catch(Exception $e)
					{
					}
				}
			
			// set session var so we don't hit the stat again
			$_SESSION['cs']['confirmed'] = "1";
			$this->cs   = array_merge( $_SESSION['cs'], $status_info );
			
			// pass in type of doc, legal status, no data
			$this->condor->Condor_Get_Docs( 'signature_response', 'TRUE', "" );
			
			// run verification rules and queue app
			$this->Final_Processing();
			
			// push them to the status page
			$this->next_page = 'ent_status';
			
			//Set Status for the next page
			if($this->Check_For_LDB_App($application_id))
			{
				//rsk
				$status_info = $this->Get_User_Status($application_id);
				$this->cs = array_merge($user_data['cs'], $status_info);
			}
			else
			{
				$this->cs = $user_data['cs'];
				$this->cs['app_status'] = 'pending';
			}
		}
		/*****************/
		// CONFIRMED ON THE ESIG PAGE - update their data, status, and get new status for
		// ent_status page only if it's not a refresh
		// only if page != "ecash_sign_docs"
		/*****************/
		elseif(
			$this->collected_data['legal_agree']
			&& !$_SESSION['cs']['confirmed']
			&& empty($_SESSION['data']['ecash_sign_docs'])
		)
		{
			if(!$application_id) $application_id = $this->Get_Application_ID();
			
            if(empty($application_id))
            {
                $error = "Cannot Update Application - App ID is not set";
                $this->applog->Write($error);
                throw new Exception($error);
            }

			// prepare the data for condor
			$session = $this->Prepare_Condor_Data($application_id);
				
			// hack this stuff in
			$_SESSION['cs'] = array_merge($_SESSION['cs'], $session['data']);            
            
			// update information
			$this->Update_User_Data( $application_id, TRUE); // fix bug #6886 [DY]
			
			// update status
			$this->Update_Status( $application_id, "confirmed");
			
			
			$status_info = (!$_SESSION['config']->use_new_process) ? $this->Get_User_Status( $application_id ) : array();
			
			// hit confirmed stat
			Stats::Hit_Stats('confirmed', $this->session, $this->event, $this->applog, $application_id);
			
			// hit the react_confirmed stat if it's a react
			if((isset($_SESSION['is_react']) && $_SESSION['is_react'] === true) || isset($_SESSION['data']->reckey) || isset($_SESSION['config']->ecash_react))
				{
                	try
                	{
                	Stats::Hit_Stats('react_confirmed', $this->session, $this->event, $this->applog, $user_data['cs']['application_id'], NULL, TRUE);
                	}catch(Exception $e){}
				}
			
			// update application_type in olp.application db
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$app_campaign_manager->Update_Application_Status($application_id, 'CONFIRMED');
			
			// set session var so we don't hit the stat again
			$_SESSION['cs']['confirmed'] = "1";
			$this->cs   = array_merge( $_SESSION['cs'], $status_info );
			
			// pass in type of doc, legal status, no data
			$this->condor->Condor_Get_Docs( 'signature_response', 'TRUE', "" );
			
			// send confirmation email
			if(!$_SESSION['config']->use_new_process)
			{
				$this->Mail_Confirmation( $_SESSION['config']->site_name, TRUE );
			}
			
			// run verification rules and queue app
			$this->Final_Processing();
			
			// push them to the status page
			$this->next_page    = ($_SESSION['config']->use_new_process) ? 'ent_thankyou' : 'ent_status';
			
		}
		/*****************/
		// ACCEPTED ON  ENT_CONFIRM PAGE - check for changed data only if they haven't
		// confirmed aleady - filter out a refresh
		/*****************/
		elseif ($this->collected_data['submit'] == "I Accept, Send My Cash" && !$_SESSION['cs']['confirmed'])
		{

			if (empty($_SESSION['cs']['application_id']))
			{
				$return['page'] = 'ent_cs_login';
				return $return;
			}

			// FORCE CONFIRMATION if these conditions exist
			// changed paydate info
			// Check dates. If the date_created is not the current date, the qualify info needs to be recalculated
			// (last check in if).

			// see if we have condor docs, if not then force esig page on confirmation
			$condor_docs = $this->condor->view_legal($_SESSION['cs']['application_id']);

			// Changes to make sure OLP and ecash status' will be the same. - [LR]
			$compare_data = $this->Compare_Data( $_SESSION['cs']['application_id']);

			if
			( in_array( $user_data['cs']['originating_source'], array( 'rc.ecashapp.com', 'ecashapp.com' ) ) ||
				!$compare_data ||
				isset( $user_data['cs']['csr_complete'] ) ||
				$user_data['cs']['force_confirm'] === TRUE ||
				date("Y-m-d", strtotime($_SESSION['cs']['date_created'])) != date("Y-m-d", time()) ||
				!$condor_docs
			)
			{

				if(isset($this->normalized_data['fund_amount']))
				{
					$this->cs['fund_qualified']  = $this->normalized_data['fund_amount'];
				}
				$qualify = $this->Get_Qualify_Info( TRUE );
				$this->cs         = array_merge( $this->cs, $_SESSION['cs'], $user_data['cs']);
				$this->cs['qualify']  = $qualify;
				$this->cs['paydates'] = $this->new_pay_dates;
				$this->cs['new_paydate']  = $this->normalized_data['paydate'];
				$this->next_page  = 'ent_confirm_legal';

			}
			else //confirmed the loan, no changes to data
			{

				$this->Update_Status($_SESSION['cs']['application_id'], "confirmed" );

				// update application_type in olp.application db
				$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				$app_campaign_manager->Update_Application_Status($_SESSION['cs']['application_id'], 'CONFIRMED');

				// run verification rules and queue app
				$this->Final_Processing();

				// set session var so we don't hit the stat again
				$_SESSION['cs']['confirmed'] = "1";
				$status_info      = $this->Get_User_Status( $_SESSION['cs']['application_id'] );
				$this->cs         = array_merge($_SESSION['cs'], $user_data, $status_info );
				$this->cs['qualify'] = $this->Get_Qualify_Info();

				// keep this. used to update application status below
				//$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
				
				// hit confirmed stat
				Stats::Hit_Stats('confirmed', $this->session, $this->event, $this->applog, $application_id);
				
				// hit the react_confirmed stat if it's a react
				if((isset($_SESSION['is_react']) && $_SESSION['is_react'] === true) || isset($_SESSION['data']->reckey) || isset($_SESSION['config']->ecash_react))
				{
                	try
                	{
                	Stats::Hit_Stats('react_confirmed', $this->session, $this->event, $this->applog, $user_data['cs']['application_id'], NULL, TRUE);
                	}catch(Exception $e){}
				}
				
				// send confirmation email
				if(!$_SESSION['config']->use_new_process)
				{
					$this->Mail_Confirmation( $_SESSION['config']->site_name, TRUE );
				}
				// push them to the status page
				$this->next_page  = 'ent_status';
				
			}
		}

		/*****************/
		// already confirmed - refreshed page - back to ent_status
		/*****************/
		elseif ($_SESSION['cs']['confirmed'])
		{
			$this->next_page = 'ent_status';
		}
		/*****************/

		// special case for ecash_sign_docs where we just update their status in the ecash documents table
		//
		/****************/
		elseif ($this->collected_data['legal_agree']
			&& !$_SESSION['cs']['confirmed']
			&&  !empty($_SESSION['data']['ecash_sign_docs'])
			)
		{
			// sign condor doc pass in type of doc, legal status, no data
			$this->condor->Condor_Get_Docs( 'signature_response', 'TRUE', "" );
			// set session var so we don't this page again with refresh
			$_SESSION['cs']['confirmed'] = "1";
			// update their status
			$this->Update_Status($this->Get_Application_ID(), "ecash_sign_docs" );

			// push them to the status page
			$this->next_page  = 'ent_status';
		}

		/*****************/
		// done with first IF block in page handler
		/*****************/		

		// Add condor document link if they applied after OL6 go live date (OLP6_GOLIVE_TIMESTAMP)
		// and the hash they came in with matches our foo
		if(!isset($this->cs['date_created'])) $this->cs['date_created']=date("m/d/y");
        if(strtotime($this->cs['date_created']) > OLP6_GOLIVE_TIMESTAMP && $_SESSION['cs']['md5_hash_match'] == 1)
        {
        	$this->cs['legal_docs_link'] = ' | <a id="legal_docs_link" href="javascript:void(0)" onclick="pop_newsite(\'?page=view_docs\');">View your documents</a>';
        }

		if( strlen( $user_data['cs']['track_key'] >= 25 ) && strlen( $user_data['cs']['track_key'] ) <= 40 )
		{
			$_SESSION['statpro']['track_key'] = $user_data['cs']['track_key'];
		}
		
		// If the next page requires an OLP app and we don't have one, goto ent_status.
		if (in_array(strtolower($this->next_page), self::$pages_require_olp_app) && 
			!$this->appExistsInOlp($application_id))
		{
			$this->next_page = 'ent_status';
		}

		switch (strtolower($this->next_page))
		{
			
			case 'ent_online_confirm':
				$unique = !$this->event->Check_Event($user_data['cs']['application_id'], 'STAT_POPCONFIRM');
			
				// hit popconfirm stat and redirect_page (if we pulled up the confirm page,
				// the redirect was successful)
				Stats::Hit_Stats('redirect', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
				Stats::Hit_Stats('popconfirm', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
				
				// If this is a react, we'll want to hit the react_popconfirm stat
				if ((isset($_SESSION['is_react']) && $_SESSION['is_react'] == TRUE)
					|| isset($_SESSION['data']->reckey) || isset(SiteConfig::getInstance()->ecash_react))
				{
					Stats::Hit_Stats(
						'react_popconfirm',
						$this->session,
						$this->event,
						$this->applog,
						$user_data['cs']['application_id']
					);
				}
				
				//Make sure we only update this once.
				if($unique && !preg_match('/^ecashapp/is', $this->Get_Online_Confirmation_Status($user_data['cs']['application_id'])))
				{
					$server = Server::Get_Server($_SESSION['config']->mode, 'BLACKBOX_STATS');

					//New stat limit for Overflow apps which are based on popconfirms.
					$limits = new Stat_Limits($this->sql, $server['db']);
					$result = $limits->Increment('bb_' . $this->property_short . '_popconfirm', NULL, NULL, NULL);
	
					if ($result === FALSE)
					{
						$this->applog->Write("*** QUERY FAILED WHILE UPDATING LIMIT FOR {$this->property_short} ***");
					}
				}
				
				break;
				
			case 'ent_online_confirm_legal':
				// If current page is 'ent_cs_login' and $_SESSION['cs']['qualify'] is not set,
				// we need to rebuild it using Get_Qualify_Info.
				// This may happen if customer jumps straight to ent_online_confirm_legal page
				// from a link or saved url after confirming in a previous session. Mantis #13879 [DW]
				if ($this->collected_data['page'] == 'ent_cs_login'
					&& !isset($_SESSION['cs']['qualify']))
				{
					// store old fund date for later comparison
					$_SESSION['cs']['old_fund_date'] = $_SESSION['cs']['fund_date'];
					// Build new paydate model in case they need to be updated
					$pd_model = new Paydate_Model();
					$test = $pd_model->Build_From_Data($_SESSION['cs']['paydate']);
					$_SESSION['cs']['new_pay_dates'] = $pd_model->Pay_Dates($this->holiday_array);
					$_SESSION['cs']['paydate_model'] = $pd_model->Model_Data();
					$_SESSION['cs']['paydate_model']['next_pay_date'] = $_SESSION['cs']['new_pay_dates'][0];
					// Distribute the paydate list to the required locations
					$this->cs['new_pay_dates'] = $_SESSION['cs']['new_pay_dates'];
					$_SESSION['cs']['paydates'] = $_SESSION['cs']['new_pay_dates'];
					$_SESSION['data']['paydates'] = $_SESSION['cs']['new_pay_dates'];
					// Build new qualify info
					$_SESSION['cs']['qualify'] = $this->Get_Qualify_Info();
					
					// Update the database with the new information if needed
					if (isset($_SESSION['cs']['old_fund_date'])
						&& $_SESSION['cs']['qualify']['fund_date'] != $_SESSION['cs']['old_fund_date']
					)
					{
						if (!isset($app_campaign_manager))
						{
							$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
						}
						$app_campaign_manager->updatePaydateModel($application_id, $_SESSION['cs']);
						$app_campaign_manager->Update_Loan_Note(
							$application_id,
							$_SESSION['cs']['qualify']['fund_date'],
							$_SESSION['cs']['qualify']['fund_amount'],
							$_SESSION['cs']['qualify']['apr'],
							$_SESSION['cs']['qualify']['payoff_date'],
							$_SESSION['cs']['qualify']['finance_charge'],
							$_SESSION['cs']['qualify']['total_payments']
						);
					}
				}
				
				// hit popagree stat
				Stats::Hit_Stats('popagree', $this->session, $this->event, $this->applog, $user_data['cs']['application_id']);
				break;
			
		}
		
		$return = array();
		$return['page'] = $this->next_page;
		$return['cs']   = $this->cs;

		return $return;
	}

	protected function Final_Processing()
	{

		$verify = NULL;
		$app_id = $this->Get_Application_ID();
		
		$app_campaign_manager = new App_Campaign_Manager($this->sql,$this->database,$this->applog);
		$olp_process = $app_campaign_manager->Get_Olp_Process($app_id);
		
        if($app_id === FALSE)
        {
        	throw new Exception("Cannot Update Application Status - no app id set");
        }
		
		$is_react = $this->Is_React($app_id);
		
		// Mantis #4569 -- Make sure these reacts go to underwriting
		if (isset($_SESSION['data']['ecash_sign_docs']))
		{
			$_SESSION['is_react'] = $is_react;
		}
		
		// If we're doing a react we want it to go to underwriting.
		if ($is_react ||
		   $olp_process == 'ecashapp_react' ||
//		   $olp_process == 'cs_react' ||
//		   $olp_process == 'email_react' ||
		   $olp_process == 'ecashapp_preact')
		{
			/* Commented out cs_react/email_react for GForge #9673. This is
			 * a huge hack because of apps failing cs_react but then going
			 * through normal process and sold to a different company.
			 */
			$status = 'underwriting';
		}
		// verification rules are only run for type 2 companies
		else
		{
			// [gForge #4873 12/7/07] - All apps should run verfication regardless
			//     of process_type.
			// run the rules on our DataX Performance packet
			$verify = $this->Verification_Rules($app_id);
			
			if ($this->cs['ecash_process_type'] == 1)
			{
				// process 1 apps always go into the verification queue
				$status = 'verification';
			}
			else
			{
				// put us into the proper queue
				$status = ($verify === FALSE) ? 'underwriting' : 'verification';			
			}
		}
		
		// update LDB status
		$this->Update_Status($app_id, $status, $verify);
		
		// update OLP status
		if($_SESSION['config']->use_new_process)
		{
			$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			//$app_campaign_manager->Update_Status_History($app_id, 'ldb_unsynched');
			//Happens somewhere else now?:/
			$app_campaign_manager->Update_Status_History($app_id, $status);
		}
		//$app_campaign_manager->Update_Application_Status($_SESSION['cs']['application_id'], 'QUEUED');
		
		return $status;
		
	}

	/**
	* Reorganizes the data from the kitchen sink and session into a format prepared for Condor Documents.
	*
	* @param	int		$application_id:		The application ID to be prepared.
	* @return 	array: 	Prepared data ready for condor docs.
	*/
	public function Prepare_Condor_Data( $application_id )
	{
		
		if(empty($application_id))
		{
			$application_id = $this->Get_Application_ID();
		}
		
		$db_to_use = ($_SESSION['config']->use_new_process) ? $this->sql : $this->sqli;
		$old_use_new_proc = NULL;
		if (EnterpriseData::isCFE($this->property_short) && !empty($_SESSION['data']['ecash_sign_docs']))
		{
			$old_use_new_proc = $_SESSION['config']->use_new_process;
			$db_to_use = $this->sqli;
			$_SESSION['config']->use_new_process = FALSE;
		}
		$sink = self::Get_The_Kitchen_Sink($db_to_use, $this->database, $application_id);
		
		$data = array();
		$data['config'] = new stdClass();

		$data['application_id']                 = $application_id;
		$data['config']->property_short         = strtoupper($this->property_short);
		$data['config']->site_name              = $_SESSION['config']->site_name;
		$data['config']->property_name          = strtoupper($sink['property_name']);
		$data['data']['name_first']             = str_replace("\\", "",strtoupper($sink['name_first']));
		$data['data']['name_last']              = str_replace("\\", "", strtoupper($sink['name_last']));
		$data['data']['doc_date']               = date("m/d/Y");
		$data['data']['home_street']            = str_replace("\\", "",strtoupper($sink['home_street']));
		$data['data']['home_unit']              = strtoupper($sink['home_unit']);
		$data['data']['home_city']              = strtoupper($sink['home_city']);
		$data['data']['home_state']             = strtoupper($sink['home_state']);
		$data['data']['home_zip']               = $sink['home_zip'];
		$data['data']['dob']                    = $sink['dob'];
		$data['data']['ssn_part_1']             = $sink['ssn_part_1'];
		$data['data']['ssn_part_2']             = $sink['ssn_part_2'];
		$data['data']['ssn_part_3']             = $sink['ssn_part_3'];
		$data['data']['phone_home']             = $sink['phone_home'];
		
		// residence lengths?
		$data['data']['phone_fax']							= $sink['phone_fax'];
		$data['data']['email_primary']					= strtoupper($sink['email_primary']);
		$data['data']['phone_cell']							= $sink['phone_cell'];
		$data['data']['state_id_number']				= strtoupper($sink['state_id_number']);
		
		// remove backwhacks in data
		$data['data']['employer_name']					= str_replace("\\", "", strtoupper($sink['employer_name']));
		$data['data']['employer_length']				= $sink['employer_length'];
		$data['data']['income_type']						= $sink['income_type'];
		$data['data']['phone_work']							= $sink['phone_work'];
		$data['data']['income_monthly_net']			= $sink['income_monthly_net'];
		$data['data']['title']									= strtoupper($sink['title']);

		if (is_array($_SESSION['cs']['paydates']))
		{
			
			// use our existing paydates
			foreach( $_SESSION['cs']['paydates'] as $paydate)
			{
				$data['data']['paydates'][] = $paydate;
			}
			
		}
		else
		{
			
			// re-generate paydates
			$model = new Paydate_Model();
			$model->Import_From_Record($sink);
			// This was pulling in an empty holiday_array.  Just regenerated it using an existing function [LR]
			$_SESSION['holiday_array'] = $this->Get_Holiday_Array();
			$data['data']['paydates'] = $model->Pay_Dates($_SESSION['holiday_array']);
			
		}
		
		$data['data']['qualify_info']			= $_SESSION['cs']['qualify'];
		$data['employment']['shift']            = $sink['shift'];
		$data['pay_dates']                      = $_SESSION['cs']['new_pay_dates'];
		$data['data']['income_direct_deposit']	= $sink['income_direct_deposit'];

		// set these next three to the potentially new data from the confirm page
		// don't change this because it is used in application_content.php for condor docs
		$data['data']['paydate_model']['income_frequency']  = (isset($_SESSION['cs']['new_paydate']['frequency']) ? $_SESSION['cs']['new_paydate']['frequency'] : $sink['income_frequency']);
		$data['data']['bank_aba']               = (isset($_SESSION['data']['bank_aba']) ? $_SESSION['data']['bank_aba'] : $sink['bank_aba']);
		$data['data']['bank_account']           = (isset($_SESSION['data']['bank_account']) ? $_SESSION['data']['bank_account'] : $sink['bank_account']);
		$data['data']['fund_qualified'] 		= (!empty($_SESSION['data']['ecash_sign_docs']) && !empty($sink['fund_actual'])) ? $sink['fund_actual'] : $sink['fund_qualified'];
		$data['data']['bank_name']				= str_replace("\\", "", strtoupper($sink['bank_name']));
		$data['data']['check_number']			= $sink['check_number'];
		$data['data']['esignature']				= str_replace("\\", "", strtoupper($sink['name_first'] . ' ' . $sink['name_last']));

		$data['data']['ref_01_name_full']		= str_replace("\\", "", strtoupper($sink['ref_01_name_full']));
		$data['data']['ref_01_phone_home']		= $sink['ref_01_phone_home'];
		$data['data']['ref_01_relationship']	= strtoupper($sink['ref_01_relationship']);
		$data['data']['ref_02_name_full']		= str_replace("\\", "", strtoupper($sink['ref_02_name_full']));
		$data['data']['ref_02_phone_home']		= $sink['ref_02_phone_home'];
		$data['data']['ref_02_relationship']	= strtoupper($sink['ref_02_relationship']);
		
		if(empty($_SESSION['cs']['loan_type']))
		{
			//Needed in order to build qualify info for card loans
			$_SESSION['cs']['loan_type'] = $this->Get_Current_Loan_Type($application_id);
		}

		// return qualify info
		$qualify = $this->Get_Qualify_Info(FALSE, $data['data']);
		
		if(!empty($_SESSION['data']['ecash_sign_docs']) && !empty($sink['date_fund_estimated']))
		{
			$qualify['fund_date'] = $sink['date_fund_estimated'];
		}
		
		$_SESSION['cs']['qualify']  = $qualify;
		$data['data']['qualify_info'] = $qualify;

		if (!is_null($old_use_new_proc))
		{
			$_SESSION['config']->use_new_process = $old_use_new_proc;
		}
		return $data;
		
	}

	/**
	* Compares the information provided with the information in the database.
	*
	* If the paydate info, bank account, or bank aba change, we need to let ent_cs know
	* so that we can send them back to the legal documents page.
	*
	* @param	int		$application_id:	The application ID to be checked.
	* @return	bool:	True if no change, otherwise false.
	*/
	private function Compare_Data( $application_id )
	{
		if (empty($application_id))
		{
			return false;
		}

		// First we need to compare our paydate model, if we've got one.
		if( isset( $this->normalized_data['paydate'] ) )
		{
			// Build the model from normalized data.
			$pr = new Paydate_Model();
			$test = $pr->Build_From_Data( $this->normalized_data['paydate'] );
			$pdc_result = $pr->Pay_Dates( $this->holiday_array );

			// Run through the results and set them to the format we like best.
			foreach( $pdc_result AS $key => $value )
			{
			    $new_paydates['pay_date' . ++$key] = $value;
			}

			// Set it into the cs array.
			$this->cs['new_pay_dates'] = $new_paydates;
			$this->new_pay_dates = $new_paydates;

			// We need to save new_model to the session AND the current object in the
			// event that the user hits the back button and edits there paydate model.
			// The array_merge will use the SESSION over $this s
			$_SESSION['cs']['new_model'] = $this->cs['new_model'] = $pr->Model();

			// Run the actual comparison and hope for the best.
			$pdm = $this->Compare_Paydate_Model( $application_id, $new_paydates );
		}
		else
		{
			$pdm = TRUE;
		}

		// Next we need to grab our bank_aba and bank_account from the database.
		if($_SESSION['config']->use_new_process)
		{
			$query = "
				SELECT
					routing_number AS bank_aba,
					account_number AS bank_account,
					fund_amount
				FROM
					bank_info_encrypted
					INNER JOIN loan_note USING (application_id)
				WHERE
					bank_info_encrypted.application_id = {$application_id}
				";
			
			$mysql_results = $this->sql->Query($this->database, $query);
			$results = $this->sql->Fetch_Object_Row($mysql_results);
		}
		else
		{
		 	$query = "
				SELECT
					bank_aba,
					bank_account,
					fund_qualified as fund_amount
				FROM
					application
				WHERE
					application_id = " . $application_id . "
				";
		 	
			$mysqli_results = $this->sqli->Query($query);
			$results = $mysqli_results->Fetch_Object_Row();
		}
		
		$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
		$crypt_object = Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

		$norm_bank_aba = $this->normalized_data['bank_aba'];
		$norm_bank_account = $this->normalized_data['bank_account'];
		$db_bank_aba = $results->bank_aba;
		$db_bank_account = $results->bank_account;
		
		// Compare the bank ABA to normalized data.
		if(isset($norm_bank_aba))
		{
			if(is_numeric($db_bank_aba))
			{
				$aba = ($norm_bank_aba === $db_bank_aba);
			}
			else 
			{
				$aba = ($norm_bank_aba === $crypt_object->decrypt($db_bank_aba));
			}
		}
		else
		{
			$aba = TRUE;
		}

		// Compare the account to normalized data.
		if(isset($norm_bank_account))
		{
			if(is_numeric($db_bank_account))
			{
				$acc = ($norm_bank_account === $db_bank_account);
			}
			else 
			{
				$acc = ($norm_bank_account === $crypt_object->decrypt($db_bank_account));
			}
		}
		else
		{
			$acc = TRUE;
		}

		// Compare the Fund Amount
		if(isset($this->normalized_data['fund_amount']))
		{
			$fnd = ( $this->normalized_data['fund_amount'] == $results->fund_amount ) ? TRUE : FALSE;
		}
		else
		{
			$fnd = TRUE;
		}

		$this->cs['compared'] = ( $pdm && $aba && $acc && $fnd) ? TRUE : FALSE;
		return $this->cs['compared'];
	}

	/**
	* Compares paydate model for sameness.
	*
	* Pulls the user's paydate information from the database, then compares it to the data
	* input from the confirmation page.
	*
	* @param 	int		$application_id:		The application ID to be compared.
	* @param 	array	$new_paydates:			Paydates or paydate model information.
	* @return 	bool:	True if the same, otherwise false.
	*/
	private function Compare_Paydate_Model( $application_id, $new_paydates )
	{
		// Grab their active data from the database.
		if($_SESSION['config']->use_new_process)
		{
			$query = "
				SELECT
					paydate_model_id AS paydate_model,
					IFNULL(ELT(day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'), 'SUN') AS day_of_week,
					next_paydate,
					day_of_month_1,
					day_of_month_2,
					week_1,
					week_2
				FROM
					paydate
				WHERE
					application_id = {$application_id}";
			
			$mysql_result = $this->sql->Query($this->database, $query);
			$result = $this->sql->Fetch_Array_Row($mysql_result);
		}
		else
		{
			$query = "
				SELECT
					UCASE( paydate_model)		AS paydate_model,
					UCASE( day_of_week )		AS day_of_week,
					last_paydate				AS next_paydate,
					day_of_month_1,
					day_of_month_2,
					week_1,
					week_2
				FROM
					application
				WHERE
					application_id = " . $application_id . "
			";

			$mysqli_result = $this->sqli->Query($query);
			$result = $mysqli_result->Fetch_Array_Row(MYSQLI_ASSOC);
		}


		// Set up the model_data array.
		if( strlen( $result['paydate_model'] ) )
		{
			foreach( $result as $field_name => $data )
			{
				if( $data != "" )
				{
					switch( strtolower( $field_name ) )
					{
						case "paydate_model":
							$model_name = $data;
							break;
						case "day_of_week":
							$model_data["day_string_one"] = $data;
							break;
						case "next_paydate":
							$model_data["next_pay_date"] = $data;
							break;
						case "day_of_month_1":
							$model_data["day_int_one"] = $data;
							break;
						case "day_of_month_2":
							$model_data["day_int_two"] = $data;
							break;
						case "week_1":
							$model_data["week_one"] = $data;
							break;
						case "week_2":
							$model_data["week_two"] = $data;
							break;
					}
				}
			}

			// Run the paydate calculator on it.
			$pd = new Pay_date_Calc_1( $this->holiday_array );
			$pdc_result = $pd->Calculate_Payday( $model_name, date('Y-m-d' ), $model_data, 4 );
			reset( $pdc_result );

			foreach( $pdc_result AS $key => $value )
			{
				$paydates['pay_date' . ++$key] = $value;
			}
		}
		else
		{
			// We've got a Soap site.  We'll have to do comparison the ugly way.
			$this->paydate_model_flag       = 'INSERT';
			$query = "
				SELECT
					income_date_soap_1		AS income_date_one,
					income_date_soap_2		AS income_date_two
				FROM
					application
				WHERE
					application_id = " . $application_id . "
			";

			$mysqli_results = $this->sqli->Query( $query );
			$result = $mysqli_results->Fetch_Array_Row(MYSQLI_ASSOC);

			$paydates[]     = $result['income_date_one'];
			$paydates[]     = $result['income_date_two'];

			// Fix our array a bit; the keys aren't going to match as it stands.
			$tmp            = $new_paydates;
			$new_paydates[] = $tmp['pay_date1'];
			$new_paydates[] = $tmp['pay_date2'];
		}
		
		$pay_diff = array_diff($new_paydates, $paydates);
		
		return (empty($pay_diff)) ? TRUE : FALSE;
	}

	/**
	 * Grabs the holidays for the previous and next 90 days (180 days total) from the holiday table
	 * in LDB.
	 *
	 * @return array The array of holidays with dates as keys.
	 */
	private function Get_Holiday_Array()
	{
		try
		{
			$result = $this->sqli->Query("
				SELECT holiday
				FROM holiday
				WHERE holiday BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
					AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)");
		}
		catch(MySQL_Exception $e)
		{
			throw $e;
		}
        
		$ret_val = array();
		
		while(($row = $result->Fetch_Array_Row()))
		{
			$ret_val[$row['holiday']] = $row['holiday'];
		}
		
		return $ret_val;
	}

	/**
	* Organizes qualify info and places it in the object-level Qualify variable.
	*
	* If the user changed their data, uses the updated information.  Otherwise uses the old
	* information.
	*
	* @param 	bool:		$new		If true, use the new information, otherwise the old.
	* @return 	array:		The user's qualify info.
	*/
	private function Get_Qualify_Info( $new = '', $data = NULL )
	{
		
		if (isset($this->cs['new_pay_dates']))
		{
			$pay_dates = $this->cs['new_pay_dates'];
		}
		elseif (isset($this->new_pay_dates))
		{
			$pay_dates = $this->new_pay_dates;
		}
		else
		{
			$pay_dates = $data['paydates'];
		}
		
		// If bad_pay_dates has been flagged and $_SESSION['data']['paydates'] is already set,
		// use $_SESSION['data']['paydates'] for the paydate schedule. Mantis 13513 [DW]
		if (isset($_SESSION['data']['bad_pay_dates']) && isset($_SESSION['data']['paydates']))
		{
			$pay_dates = $_SESSION['data']['paydates'];
		}

		if ($new)
		{
			$frequency              = $this->normalized_data['paydate']['frequency'];
			$monthly_net            = $this->cs['income_monthly'];
			$direct_deposit         = $this->cs['direct_deposit'];
			$date_hire              = $this->cs['date_hire'];
			$fund_amount 			= $this->cs['fund_qualified'];
		}
		elseif (!is_array($data))
		{
			//$this->cs was being overwritten before this call, so use
			//the class variable instead.
			$frequency				= $_SESSION['cs']['paydate']['frequency'];
			$monthly_net			= $_SESSION['cs']['income_monthly'];
			$direct_deposit			= $_SESSION['cs']['direct_deposit'];
			$date_hire				= $_SESSION['cs']['date_hire'] ? TRUE : FALSE;
			$fund_amount 			= (isset($_SESSION['data']['fund_amount'])) ? $_SESSION['data']['fund_amount'] : $_SESSION['cs']['fund_qualified'];
		}
		else
		{
			$frequency = $data['paydate_model']['income_frequency'];
			$monthly_net = $data['income_monthly_net'];
			$direct_deposit = $data['income_direct_deposit'];
			$date_hire = $data['employer_length'];
			$fund_amount = $data['fund_qualified'];
		}
		
		$holiday_array = array_keys( $this->holiday_array );
			
		$q = new OLP_Qualify_2($this->property_short, $holiday_array, $this->sql, $this->sqli, $this->applog, null, $this->title_loan);
		$react_loan = (isset($_SESSION["blackbox"]["react"])) ? TRUE : FALSE;
		
		if($this->Is_Preact())
		{
			$is_preact = TRUE;
			$react_app_id = (!empty($_SESSION['data']['react_app_id']))
								? intval($_SESSION['data']['react_app_id'])
								: $this->Get_React_App_ID($this->Get_Application_ID());
			
			//Don't run the preact checks if we can't get the react_app_id
			if(empty($react_app_id))
			{
				$is_preact = FALSE;
			}
		}
		else
		{
			$is_preact = FALSE;
			$react_app_id = NULL;
		}
		
		$qualify = $q->Qualify_Person( $pay_dates, $frequency, $monthly_net, $direct_deposit, $date_hire, $fund_amount, $react_loan, $react_app_id, $is_preact);
		$this->qualify = $qualify;

		return $qualify;
		
	}

	/**
	* Gathers customer service information from the database.
	*
	* If the user only has one application ID, we'll use that to populate their data, but if they have more than
	* one we'll send them to a page that lets them select from the list.
	*
	* @param 	int		$application_id:	Either null, or the application ID to be used.
	* @return 	array:	Customer service information and the next page to display.
	*/
	public function Get_User_Data( $application_id = NULL )
	{
		// If we have no application ID, use their login to search for one.
		if( $application_id == NULL )
		{
			//Find the application_id from LDB
			$login = new Login_Handler($this->sqli, $this->property_short, $this->database, $this->applog);
			$username = (isset($this->login)) ? $this->login : $_SESSION['cs']['login'];
			$application_id = $login->Find_App_ID($username);
		}

		if($_SESSION['config']->use_new_process)
		{
			$query = "
				SELECT
					application.application_id,
					application.created_date			AS date_created,
					application.track_id,
					UCASE(application.application_type)	AS status,
					application.olp_process,
					application.is_react,
					
					personal_encrypted.first_name					AS name_first,
					personal_encrypted.middle_name				AS name_middle,
					personal_encrypted.last_name					AS name_last,
					personal_encrypted.social_security_number,
					personal_encrypted.email						AS email_primary,
					
					employment.date_of_hire				AS date_hire,
	
					paydate.paydate_model_id			AS paydate_model,
					IFNULL(ELT(paydate.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'), 'SUN') AS day_of_week,
					paydate.next_paydate,
					paydate.day_of_month_1,
					paydate.day_of_month_2,
					paydate.week_1,
					paydate.week_2,
	
					bank_info_encrypted.bank_name,
					bank_info_encrypted.routing_number			AS bank_aba,
					bank_info_encrypted.account_number			AS bank_account,
					bank_info_encrypted.bank_account_type,
					bank_info_encrypted.direct_deposit			AS income_direct_deposit,
	
					income.pay_frequency				AS income_frequency,
					income.monthly_net					AS income_monthly,
					DATE_FORMAT(income.pay_date_1, '%Y-%m-%d')	AS paydate_1,
					DATE_FORMAT(income.pay_date_2, '%Y-%m-%d')	AS paydate_2,
					DATE_FORMAT(income.pay_date_3, '%Y-%m-%d')	AS paydate_3,
					DATE_FORMAT(income.pay_date_4, '%Y-%m-%d')	AS paydate_4,
					
					loan_note.fund_amount				AS fund_qualified,
					DATE_FORMAT(loan_note.estimated_fund_date, '%Y-%m-%d') AS date_fund_estimated
				FROM application
					INNER JOIN personal_encrypted USING (application_id)
					INNER JOIN bank_info_encrypted USING (application_id)
					INNER JOIN employment USING (application_id)
					INNER JOIN paydate USING (application_id)
					INNER JOIN income USING (application_id)
					INNER JOIN loan_note USING (application_id)
				WHERE application.application_id = {$application_id} 
				ORDER BY
					date_created
				LIMIT 1";
			
			$mysql_result = $this->sql->Query($this->database, $query);
		}
		else
		{
		
			$query = "
				SELECT
					application.application_id,
					application.name_first,
					application.name_last,
					application.name_middle,
				  	application.ssn								AS social_security_number,
					application.email							AS email_primary,
					application.date_hire,
					UCASE( application.income_frequency )		AS income_frequency,
					UCASE( application.paydate_model )			AS paydate_model,
					UCASE( application.day_of_week )			AS day_of_week,
					application.last_paydate					AS next_paydate,
					application.day_of_month_1,
					application.day_of_month_2,
					application.week_1,
					application.week_2,
					application.bank_name,
					application.bank_aba,
					application.bank_account,
					application.bank_account_type,
					application.date_created,
					application.date_fund_estimated,
					application.fund_qualified,
					UCASE( application.income_direct_deposit )	AS income_direct_deposit,
					application.income_monthly,
					application.track_id,
					application.application_status_id as status,
					application.loan_type_id as loan_type_id,
					application.is_react,
					company.ecash_process_type,
					(
						SELECT
							name
						FROM
							site
						JOIN
							campaign_info
						ON
							campaign_info.site_id = site.site_id
						WHERE
							campaign_info.application_id = application.application_id
						LIMIT 1
					) AS originating_source,
					(
						SELECT name_short
						FROM loan_type
						WHERE loan_type_id = application.loan_type_id
					) AS loan_type,
					application.olp_process
				FROM
					application
				JOIN
					company ON company.company_id = application.company_id
				WHERE application.application_id = {$application_id} 
				AND
					company.name_short = '" . $this->property_short . "'
				ORDER BY
					application.date_created
				LIMIT 1";
	
			$mysqli_result = $this->sqli->Query( $query );
		}

		$count = 0;

		// Loop through everything and put it all into a more useful format.
		while($result = (($_SESSION['config']->use_new_process) ?  $this->sql->Fetch_Object_Row($mysql_result) : $mysqli_result->Fetch_Object_Row()))
		{
			if($_SESSION['config']->use_new_process)
			{
				$crypt_config 			= Crypt_Config::Get_Config(BFW_MODE);
				$crypt_object			= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
				$result->social_security_number = $crypt_object->decrypt($result->social_security_number);
				$result->bank_aba			= $crypt_object->decrypt($result->bank_aba);
				$result->bank_account 		= $crypt_object->decrypt($result->bank_account);
			}
			$info[$count]['track_key']              = $result->track_id;
			//$info[$count]['promo_id']               = $result->promo_id;
			//$info[$count]['promo_sub_code']         = $result->promo_sub_code;
			$info[$count]['application_id']         = $result->application_id;
			$info[$count]['transaction_id']         = $result->application_id;
			$info[$count]['paydate']['frequency']   = $result->income_frequency;
			$info[$count]['name_first']             = trim( $result->name_first );
			$info[$count]['name_last']              = trim( $result->name_last );
			$info[$count]['name_middle']            = $result->name_middle;
			$info[$count]['bank_aba']               = $result->bank_aba;
			$info[$count]['bank_name']              = $result->bank_name;
			$info[$count]['bank_account']           = $result->bank_account;
			$info[$count]['bank_account_type']      = $result->bank_account_type;
			$info[$count]['income_monthly']         = $result->income_monthly;
			$info[$count]['date_hire']              = $result->date_hire;
			$info[$count]['email_primary']          = $result->email_primary;
			$info[$count]['fund_date']              = $result->date_fund_estimated;
			$info[$count]['fund_qualified']         = $result->fund_qualified;
			$info[$count]['date_created']			= $result->date_created;
			$info[$count]['social_security_number']	= $result->social_security_number;
			$info[$count]['olp_process']			= $result->olp_process;
			$info[$count]['loan_type']				= $result->loan_type;
			
			// Check for is_react	[RV]
			if($result->is_react == 'yes' || $result->is_react == 1) $_SESSION['is_react'] = 1;
			
			if(!$_SESSION['config']->use_new_process)
			{
				$info[$count]['originating_source']		= $result->originating_source;
				$info[$count]['ecash_process_type']		= $result->ecash_process_type;
				$info[$count]['loan_type_id']			= $result->loan_type_id;
				$info[$count]['direct_deposit']         = ( $result->income_direct_deposit == 'YES' ) ? 'TRUE' : 'FALSE';
				$info[$count]['income_direct_deposit']  = ( $result->income_direct_deposit == 'YES' ) ? 'TRUE' : 'FALSE';
			}
			else
			{
				$info[$count]['direct_deposit']         = $result->income_direct_deposit;
				$info[$count]['income_direct_deposit']  = $result->income_direct_deposit;
			}
			
			switch( $info[$count]['paydate']['frequency'] )
			{
				case 'WEEKLY':
					$info[$count]['paydate']['weekly_day'] = $result->day_of_week;
					break;
				case 'BI_WEEKLY':
					$info[$count]['paydate']['biweekly_day']    = $result->day_of_week;
					$info[$count]['paydate']['biweekly_date']   = sprintf( "%s/%s/%s", substr( $result->next_paydate, 5, 2 ), substr( $result->next_paydate,8,2 ), substr( $result->next_paydate, 0, 4 ) );
					break;
				case 'TWICE_MONTHLY':
					switch( $result->paydate_model )
					{
						case 'DMDM':
							$info[$count]['paydate']['twicemonthly_type']   = 'date';
							$info[$count]['paydate']['twicemonthly_date1']  = $result->day_of_month_1;
							$info[$count]['paydate']['twicemonthly_date2']  = $result->day_of_month_2;
							break;
						default:
							$info[$count]['paydate']['twicemonthly_type']   = 'week';
							$info[$count]['paydate']['twicemonthly_week']   = sprintf( "%s-%s", $result->week_1, $result->week_2 );
							$info[$count]['paydate']['twicemonthly_day']    = $result->day_of_week;
							break;
					}
					break;
				case 'MONTHLY':
					switch( $result->paydate_model )
					{

						case 'DM':
						//rsk changed from week
							$info[$count]['paydate']['monthly_type']    = 'date';
							$info[$count]['paydate']['monthly_date']    = $result->day_of_month_1;
							break;
						case 'WDW':
							$info[$count]['paydate']['monthly_type']    = 'day';
							$info[$count]['paydate']['monthly_week']    = $result->week_1;
							$info[$count]['paydate']['monthly_day']     = $result->day_of_week;
							break;
						default:
							$info[$count]['paydate']['monthly_type']        = 'after';
							$info[$count]['paydate']['monthly_after_day']   = $result->day_of_week;
							$info[$count]['paydate']['monthly_after_date']  = $result->day_of_month_1;
							break;
					}
					break;
			}
			
			//Insert paydates if they're there
			if(isset($result->paydate_1))
			{
				$info[$count]['new_pay_dates'] = array(
					$result->paydate_1,
					$result->paydate_2,
					$result->paydate_3,
					$result->paydate_4,
				);
			}
			else
			{
				//Build next paydates from model
				$model = new Paydate_Model();
				$r = $model->Build_From_Data($info[$count]['paydate']);
				if($r)
				{
					$h = array_keys($this->Get_Holiday_Array());
					$info[$count]['new_pay_dates'] = $model->Pay_Dates($h, 4);
				}
			}
						
			// if any application is in one of these two statuses,
			// we need to send them to a confirmation page.
			//$confirm_statuses = array('agree', 'confirm_declined', 'disagree');
			$confirm_statuses = ($_SESSION['config']->use_new_process)
							? array('AGREED', 'CONFIRMED_DISAGREED', 'DISAGREED')
							: array(
									'5', //agree
									'15', //confirm_declined
									'6' //disagree
									);
			

			if ((isset($_SESSION['config']->online_confirmation) || (strtolower($result->olp_process) === 'online_confirmation'))
				&& (strtolower($result->olp_process) != 'email_confirmation'))
			{
				
				// make sure this is set
				$_SESSION['config']->online_confirmation = TRUE;

				switch ($result->status)
				{
					
					case '16': //pending
					case '144': //preact_pending
					case 'PENDING':
					case 'PREACT_PENDING':
						$info[$count]['page'] = 'ent_online_confirm';
						break;

					case '15': //confirm_declined
						if($this->ent_prop_list[$_SESSION['config']->site_name]['new_ent'])
						{
							$info[$count]['page'] = 'ent_status';
						}
						else
						{
							$info[$count]['page'] = 'ent_online_confirm';
						}
						break;
					
					case 'CONFIRMED_DISAGREED':						
						$info[$count]['page'] = 'ent_online_confirm';
						break;
						
					case '7': //confirmed
					case '26': //confirmed
					case '143': //preact_confirmed
					case '6': //disagree
					case 'CONFIRMED':
					case 'PREACT_CONFIRMED':
					case 'DISAGREED':
						$info[$count]['page'] = 'ent_online_confirm_legal';
						break;
						
					default:
						$info[$count]['page'] = 'ent_status';
						break;
					
				}
				
			}
			else
			{
				if (in_array($result->status, $confirm_statuses))
				{
					$info[$count]['page']   = 'ent_confirm';
					$confirm                = $count;
				}
				// Otherwise we send them to a status page.
				else
				{
					$info[$count]['page']   = 'ent_status';
				}
				
			}
			
			$count++;
		}

		// Send them to a confirmation page
		if( isset( $confirm ) )
		{
			$cs										= $info[$confirm];
			$cs['application_id']	= $info[$confirm]['application_id'];
			$cs['date_created']		= $info[$confirm]['date_created'];
			$cs['name_first']			= $info[$confirm]['name_first'];
			$cs['name_last']			= $info[$confirm]['name_last'];
			$page									= $info[$confirm]['page'];
			$cs['multiple_trans']	= FALSE;
			
			$query = "
				SELECT
					count(*) AS count
				FROM
					campaign_info
				WHERE
					application_id = " . $info[$confirm]['application_id'] . "
				";

			if($_SESSION['config']->use_new_process)
			{
				$mysql_result = $this->sql->Query($this->database, $query);
				$result = $this->sql->Fetch_Object_Row($mysql_result);
			}
			else
			{
				$mysqli_result = $this->sqli->Query($query);
				$result = $mysqli_result->Fetch_Object_Row();
			}

			if( $result->count > 1 )
			{
				$cs['force_confirm'] = TRUE;
			}
		}
		// Send them to a status page
		elseif( $count == 1 )
		{
			$cs										= $info[0];
			$cs['application_id']					= $info[0]['application_id'];
            $cs['date_created']						= $info[0]['date_created'];
			$cs['name_first']						= $info[0]['name_first'];
			$cs['name_last']						= $info[0]['name_last'];
			$cs['olp_process']						= $info[0]['olp_process'];
			$page									= $info[0]['page'];
			$cs['multiple_trans']					= FALSE;
		}
		// Send them to a selection page
		elseif( $count > 1 )
		{
			$cs['customer_service']['transactions'] = $info;
			$cs['name_first']						= $info[0]['name_first'];
			$cs['name_last']						= $info[0]['name_last'];
			$page									= 'ent_cs_account';
			$cs['multiple_trans']					= TRUE;

		}
		// We have a problem.  Send them to the try again page, and log it.
		else
		{
			return array( 'page' => 'try_again' );
			$this->applog->Write( 'FATAL: Could not find an application at user login for Application ID ' . $application_id . '.' );
		}

		// Make sure we can hit stats properly.
		if($_SESSION['config']->use_new_process)
		{
			$query = "
				SELECT
					license_key,
					promo_id,
					promo_sub_code,
					url
				FROM
					campaign_info
				WHERE
					application_id = " . $info[0]['application_id'] . "
				ORDER BY
					campaign_info_id DESC
				LIMIT 1
				";
			
			$mysql_result = $this->sql->Query($this->database, $query);
			$result = $this->sql->Fetch_Object_Row($mysql_result);
			
			$cs['originating_source'] = $result->url;
		}
		else
		{
			$query = "
				SELECT
					license_key,
					promo_id,
					promo_sub_code
				FROM
					campaign_info JOIN
					site USING (site_id)
				WHERE
					application_id = " . $info[0]['application_id'] . "
				ORDER BY
					campaign_info_id DESC
				LIMIT 1
				";
			$mysqli_result = $this->sqli->Query( $query );
			$result = $mysqli_result->Fetch_Object_Row();
		}


		$cs['license_key']      = $result->license_key;
		$cs['promo_id']         = $result->promo_id;
		$cs['promo_sub_code']   = $result->promo_sub_code;

		
		if($_SESSION['config']->use_new_process)
		{
			try
			{
				$query = "SELECT ecash_process_type FROM company WHERE name_short = '{$this->property_short}'";
				$mysqli_result = $this->sqli->Query($query);
				$result = $mysqli_result->Fetch_Object_Row();
				
				$cs['ecash_process_type'] = $result->ecash_process_type;
			}
			catch(Exception $e)
			{
			}
			
			if(!is_numeric($cs['ecash_process_type']))
			{
				//They all seem to be this, so if LDB is down, we'll just default to it.
				$cs['ecash_process_type'] = 2;
			}
		}
		
		
		return array( 'page' => $page, 'cs' => $cs );
	}

	/**
	* Returns information to be used on the status page.
	*
	* @param 	int:		$application_id:		The application ID to get the status of.
	* @return	array:		The information to be passed to the Status page.
	*/
	public function Get_User_Status( $application_id )
	{
		if(empty($application_id))
        {
            $error = "Cannot Get User Status - App ID is not set";
            $this->applog->Write($error);
            throw new Exception($error);
        }

        if($this->ent_prop_list[$_SESSION['config']->site_name]['new_ent'] && $_SESSION['cs']['logged_in'])
        {
        	require_once('ent_customer.php');
        	
        	$acm = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
        	$customer = new Ent_Customer($this->Get_Application_ID(), $this->property_short, $this->sqli, $acm);
        	
        	$override = (isset($this->normalized_data['ent_status_override'])) ? $this->normalized_data['ent_status_override'] : NULL;

        	switch($override)
        	{
        		case 'ent_profile':
        			$page_data = $customer->Build_Profile();
        			break;
        			
        		case 'ent_profile_submitted':
        			$page_data = $customer->Compare_Profile($this->normalized_data);
        			break;
        			
        		case 'ent_contact_us':
        		case 'ent_docs':
        			$page_data = $customer->Build_Generic($override, ($override !== 'ent_contact_us'));
        			break;
        			
        		case 'ent_contact_us_submitted':
        			$page_data = $customer->Submit_Contact_Us($this->normalized_data);
        			break;
        			
        		default:
        			$holiday_array = $this->Get_Holiday_Array();
        			$page_data = $customer->Build_Status($override, $holiday_array);
        			break;
        	}

        	$page_data['react_offer'] = $customer->can_react;
        	$page_data['payment_link'] = $customer->view_payment_options;
        	$page_data['page_header'] = $customer->page_header;
        	
        	if($customer->can_react)
        	{
        		$page_data['encoded_app_id'] = urlencode(base64_encode($customer->application_id));
        	}
        	
        	$this->next_page = $customer->next_page;
        	
        	//Terrible way to do this, probably.
        	$page_data = $customer->Format_Dates($page_data);
        	
        	return $page_data;
        }

        $query = "
			SELECT
				application.application_id,
				application.name_first,
				application.name_last,
				application.application_status_id,
				application.ssn,
				DATE_FORMAT(application.date_application_status_set,'%M %d, %Y %l:%i %p') AS date_application_status_set,
				application_status_flat.level0 as status,
				DATE_FORMAT(application.date_fund_actual,'%M %d, %Y %l:%i %p') as funded,
				(
				SELECT
					DATE_FORMAT(date_created,'%M %d, %Y %l:%i %p')
				FROM
					status_history
				WHERE
					application_id = " . $application_id . "
				AND
					application_status_id =
				(
					SELECT
						application_status_id
					FROM
						application_status_flat
					WHERE
						( level2 = '*root' AND level1 = 'prospect' AND level0 = 'agree')
				)
				AND
					company_id =
				(
					SELECT
						company_id
					FROM
						company
					WHERE
						upper(name_short) = '". strtoupper($this->property_short) ."'
				)
				ORDER BY date_created DESC
				LIMIT 1
			) AS accepted,

			(
			SELECT
					DATE_FORMAT(date_created,'%M %d, %Y %l:%i %p')
				FROM
					status_history
				WHERE
					application_id = " . $application_id . "
				AND
					application_status_id =
				(
					SELECT
						application_status_id
					FROM
						application_status_flat
					WHERE
						( level2 = '*root' AND level1 = 'prospect' AND level0 = 'confirmed')
				)
				AND
					company_id =
				(
					SELECT
						company_id
					FROM
						company
					WHERE
						upper(name_short) = '". strtoupper($this->property_short) ."'
				)
				ORDER BY date_created DESC
				LIMIT 1
			) AS confirmed
			FROM
				application
			JOIN
				application_status_flat	ON application_status_flat.application_status_id = application.application_status_id

			WHERE
				application.application_id = " . $application_id . "
			";
		try
		{

			$mysqli_result = $this->sqli->Query( $query );
			$result = $mysqli_result->Fetch_Object_Row();
		
		}
		catch( Exception $e )
		{
			// Something bad happened.  Make sure we log it.
			$this->applog->Write( 'Get_User_Status failed for session ID ' . $_SESSION['data']['unique_id'] );
			throw $e;
		}
        
        
		if (EC3)
		{
			
			try
			{
				// queries for servicing or paid off data if their status is one of the below
				if ($result->status == "servicing")
				{
						$query = "
					SELECT date_effective AS 'current_due_date',
					amount_non_principal as 'current_fee_amount',
					(amount_principal + amount_non_principal) as 'current_due_amount'
					FROM event_schedule
					WHERE application_id = $application_id
					AND event_status = 'scheduled'
					AND (amount_principal + amount_non_principal) < 0
					ORDER BY date_effective LIMIT 1
					";
	
					$servicing_result = $this->sqli->Query( $query );
					$servicing = $servicing_result->Fetch_Object_Row();
				}
				elseif ($result->status == "paid")
				{
						$query = "
					SELECT date_effective AS 'paid_off_date'
					FROM transaction_register
					WHERE application_id = $application_id
					AND transaction_status = 'complete'
					AND transaction_type_id IN
					(SELECT transaction_type_id
				 		FROM transaction_type
						WHERE clearing_type = 'ach')
					ORDER BY date_effective DESC LIMIT 1
					";

					$servicing_result = $this->sqli->Query( $query );
					$servicing = $servicing_result->Fetch_Object_Row();
					
				}
				elseif($result->status == 'recovered')
				{
					$query = "
					SELECT date_effective AS 'paid_off_date'
					FROM transaction_register
					WHERE application_id = $application_id
					AND transaction_status = 'complete'
					AND amount < 0
					ORDER BY date_effective DESC LIMIT 1";

					$servicing_result = $this->sqli->Query( $query );
					$servicing = $servicing_result->Fetch_Object_Row();
				}
				elseif ($result->status == 'active')
				{
					$query = "
						SELECT DISTINCT
							es.date_effective AS current_due_date,
							ABS(SUM(amount_non_principal)) AS current_due_amount,
							ABS(SUM(amount_non_principal) + SUM(amount_principal)) AS current_fee_amount
						FROM
							event_schedule AS es
							JOIN (
								SELECT
									event_type_id,
									transaction_type_id
								FROM
									event_transaction
								GROUP BY event_type_id
							) AS et USING(event_type_id)
							JOIN transaction_type AS tt USING(transaction_type_id)
						WHERE
							application_id = $application_id
							AND es.event_status = 'scheduled'
							AND tt.clearing_type = 'ach'
						GROUP BY current_due_date
						ORDER BY es.date_effective ASC";
					
					$servicing_result = $this->sqli->Query($query);
					$servicing = $servicing_result->Fetch_Object_Row();
				}
			}
			catch(Exception $e)
			{
				// Something bad happened.  Make sure we log it.
				$this->applog->Write( 'ecash3 status check failed in Get_User_Status for session ID ' . $_SESSION['data']['unique_id'] );
				throw $e;
			}
		}
		else
		{

			try
			{
			
				// add switch here based on ecash status 2.5/3
				$cashline_query = "
					SELECT
						date_customer_added,
						current_due_date,
						current_service_charge_amount,
						last_payoff_date,
						status as cashline_status
					FROM
						cashline_customer_list
					WHERE
						social_security_number = '" . $result->ssn . "'
					";
				$cashline_mysqli_result = $this->sql->Query('sync_cashline_' . $this->property_short, $cashline_query);
	
				$cashline_result = $this->sql->Fetch_Object_Row($cashline_mysqli_result);
			}
			catch(Exception $e)
			{
				// Something bad happened.  Make sure we log it.
				$this->applog->Write( 'cashline status check failed in Get_User_Status for session ID ' . $_SESSION['data']['unique_id'] );
				throw $e;
			}
		}

		switch( TRUE )
		{
			// The application is in collections.
			case ( strtolower($cashline_result->cashline_status) == 'collection' ):
			case ( strtolower($cashline_result->cashline_status) == 'hold' ):
			case ( strtolower($cashline_result->cashline_status) == 'scanned' ):
			case ( $result->status == 'new' ): // GForge #9869 - *root/Customer/Collections/New
				$return['app_status']				= 'needs attention';
				break;

			// The application is in progress.  Show them that and prepare their date information.

			//case ( $result->status == 'agree' ):
			case ( $result->status == 'confirmed' ):
			// We need to discuss these [RL]
			//case ( $result->status == 'verify' ):
			//case ( $result->level == 2 && $result->status == 'verify' ):
			//case ( $result->level == 2 && $result->status == 'in_verify' ):
			//case ( $result->level == 2 && $result->status == 'in_underwriting' ):
			case ( $result->status == 'applicant' ):
			case ( $result->status == 'customer' ):
			default:
				
				$return['app_status']				= 'in progress';
				$return['app_status_received']		= $result->accepted . ' PST';
				$return['app_status_confirmed']		= $result->confirmed . ' PST';
				$return['app_confirmed_flag']		= $result->confirmed;
				$return['app_status_approval']		= ( $result->funded != 0 ) ? $result->funded . ' PST' : 'Pending';
				$return['app_status_funded']		= ( $result->funded != 0 ) ? $result->funded . ' PST' : 'Pending';
				
				break;

			// Bad boys need to go to the declined page.
			case ( $result->status == 'denied' ):
				$return['app_status']				= 'declined';
				break;

			// Active status means they're paying off their loan right now.
			// was active previously in ec 2.5
			// if cashline_status is inactive, this loan is not open
			case ( $result->status == 'active' && !(strtolower($cashline_result->cashline_status) == 'inactive')):
				
				$return['app_status'] = 'open';
				if (EC3)
				{
					$return['payoff_date'] = ( isset( $servicing->current_due_date ) ) ? $servicing->current_due_date : 'N/A';
					$return['finance_charge'] = ( $servicing->current_fee_amount > 0 ) ? '$' . $servicing->current_fee_amount : 'N/A';
					$return['amount_due'] = ($servicing->current_due_amount > 0 ) ? '$' . $servicing->current_due_amount : 'N/A';
				}
				else // legacy
				{
					$return['payoff_date'] = ( isset( $cashline_result->current_due_date ) && $cashline_result->current_due_date != '  /  /' ) ? $cashline_result->current_due_date : 'N/A';
					$return['finance_charge'] = ( isset( $cashline_result->current_service_charge_amount ) && $cashline_result->current_service_charge_amount > 0 ) ? '$' . $cashline_result->current_service_charge_amount : 'N/A';
					$return['amount_due'] = ( isset( $cashline_result->current_service_charge_amount ) && $cashline_result->current_service_charge_amount > 0 ) ? '$' . $cashline_result->current_service_charge_amount : 'N/A';
				}
				break;

			// Inactive status is a good thing.  Let's tell them so and try and get them to borrow more money.
			case ( ($result->status == 'paid' || $result->status == 'recovered' || strtolower($cashline_result->cashline_status) == 'inactive') && (isset($cashline_result->cashline_status) || (isset($servicing->paid_off_date) && EC3))):
				
				$return['app_status'] = 'paid in full';
				//We don't want the react button to show for recovered loans
				$return['has_active_loans'] = ($result->status == 'paid') ? $this->Has_Active_Loans() : TRUE;
				// If cashline status is inactive, then display react button
				$return['has_active_loans'] = (strtolower($cashline_result->cashline_status) == 'inactive') ? FALSE : $return['has_active_loans'];
				
				if (EC3)
				{
                	$return['paid_date'] = $servicing->paid_off_date;
				}
				else
				{
					$return['paid_date'] = $cashline_result->last_payoff_date;
				}
				// For use to Reactivate a new loan from the Paid in Full Screen [RL]
				$_SESSION["data"]["encoded_app_id"] = base64_encode($application_id);
                break;

			case ( $result->status == 'withdrawn'):
				$return['app_status']				= 'withdrawn';
				$return['app_status_received']		= $result->accepted . ' PST';
				$return['app_status_confirmed']		= $result->confirmed . ' PST';
				$return['app_date_withdrawn']		= $result->date_application_status_set . ' PST';
				break;
			case ( strtolower($cashline_result->cashline_status) == 'withdrawn' && 
					strtotime($cashline_result->date_customer_added) > strtotime($result->date_application_status_set) ):
				$return['app_status']				= 'withdrawn';
				$return['app_status_received']		= $result->accepted . ' PST';
				$return['app_status_confirmed']		= $result->confirmed . ' PST';
				$return['app_date_withdrawn']		= $result->date_application_status_set . ' PST';
				break;				
				
		}

		$return['application_id']	= $result->application_id;

		// create return to applications link
		if ($_SESSION['cs']['multiple_trans'])
		{
			$return['app_multiple']	= '<a href="' . $_SESSION['data']['client_url_root'] . '?page=ent_cs_login&list">Back to Account Information</a>';
		}

		return $return;
	}
	
	// Mantis #12161 - Added this to check for an active card.	[RV]
	public function Has_Active_Card($ssn, $property_short, $ldb)
	{
		return SSN_Has_Active_Card($ssn, $property_short, $ldb);
	}

	private function Has_Active_Loans()
	{
		$return = FALSE;
		
		$active_statuses = Previous_Customer_Check::$status_map[Previous_Customer_Check::STATUS_ACTIVE]
							. ':' .
							Previous_Customer_Check::$status_map[Previous_Customer_Check::STATUS_PENDING];
		
		$ids = implode(',', $this->olp_mysql->Status_Glob($active_statuses));
		$company_id = $this->olp_mysql->Company_ID($this->property_short);

		
		$app_id = $this->Get_Application_ID();
		
		if(!empty($app_id) && !empty($ids) && !empty($company_id))
		{
			$query = "
				SELECT
					ssn,
					email,
					phone_home,
					bank_account,
					bank_aba,
					legal_id_number
				FROM
					application
				WHERE
					application_id = {$app_id}";
			
			$app_result = $this->sqli->Query($query);
			
			$app_data = $app_result->Fetch_Array_Row(MYSQLI_ASSOC);
			
			if(!empty($app_data))
			{
				$bank_accts = implode(',', Previous_Customer_Check::Permutate_Account($app_data['bank_account']));
				$checks = array(
					'ssn'		=> "ssn = '{$app_data['ssn']}'",
					'email'		=> "email = '{$app_data['email']}'",
					'phone'		=> "phone_home = '{$app_data['phone_home']}'",
					'bank'		=> "bank_aba = '{$app_data['bank_aba']}' AND bank_account IN ({$bank_accts})",
					'dl'		=> "legal_id_number = '{$app_data['legal_id_number']}'"
				);
				
				foreach($checks as $check)
				{
					$query = "
						SELECT
							COUNT(*) as total
						FROM
							application
						WHERE
							{$check}
							AND application_status_id IN ({$ids})
							AND company_id = {$company_id}";
	
					$result = $this->sqli->Query($query);
					$total = $result->Fetch_Object_Row();
	
					if(intval($total->total) > 0)
					{
						$return = TRUE;
					}
				}
			}
		}

		return $return;
	}
	
	
	
	/**
	* Handle login processing & pass off to page handler on success.
	*
	* @return 	array: The page to display, any errors, and the login name.
	*/
	public function Login()
	{
		$login = new Login_Handler($this->sqli, $this->property_short, $this->database, $this->applog);
		
		// unset users session legal agree field in case they came right from the completed app
		unset( $_SESSION['data']['legal_agree'] );
		if(!isset($_SESSION['data']['reckey'])) unset( $_SESSION['condor'] );

		if(isset($_SESSION['data']['reckey']) && isset($_SESSION['application_id']))
		//Reacts
		{
			$this->cs['application_id'] = $this->application_id = $_SESSION['application_id'];
			
			$_SESSION['cs']['md5_hash_match'] = 1;
				
			// Log session_id in olp.cs_session
			// User hits a cs page w/ a decoded application_id
			$this->Log_CS_Session( $this->application_id );
			
			//Load User Data
			$cs = $this->Get_User_Data($this->application_id);
			$cs = $cs['cs'];

			//Update cs array
			$_SESSION['cs'] = $cs;
			
			// Grab a new configuration pointing to the enterprise set
			$this->Set_Enterprise_Config($cs);
			
			//Marked as log in
			$_SESSION['cs']['logged_in'] = TRUE;
		}
		//  login with application id
		elseif ( $this->application_id )
		{
			$this->cs['application_id'] = $this->application_id;
			
			// set md5_hash_match in session if it matches
			if ($_SESSION['data']['login'] == md5($this->Get_Application_ID() . $this->hash_key))
			{
				$_SESSION['cs']['md5_hash_match'] = 1;
			}
			// TEMP HACK FOR TELEWEB - REMOVE ME! 
			elseif($this->normalized_data['promo_override'] && $this->normalized_data['page']=='ent_cs_login')
			{
				$_SESSION['cs']['md5_hash_match'] = 1;
			}
			else
			{
				//Try old process before erroring out
				$this->login = $login->Login_User_App_ID($this->application_id, $this->sql);
				   
				if ($this->normalized_data['login'] == md5($this->login . $this->hash_key) )
				{
					$_SESSION['cs']['md5_hash_match'] = 1;
				}
			}
			
			if($_SESSION['cs']['md5_hash_match'] === 1)
			{
				// Log session_id in olp.cs_session
				// User hits a cs page w/ a decoded application_id
				$this->Log_CS_Session( $this->application_id );
				
				if ((!isset($_SESSION['cs']['application_id'])))
				{
					$cs = $this->Get_User_Data($this->application_id);
					$cs = $cs['cs'];
				}

				
				// Grab a new configuration pointing to the enterprise set
				$this->Set_Enterprise_Config($cs);
				
				// Hit the cs_login_link stat
				Stats::Hit_Stats('cs_login_link', $this->session, $this->event, $this->applog, $this->application_id);
				
				//Marked as log in
				$_SESSION['cs']['logged_in'] = TRUE;
			}
			else
			{
				$errors =  "No login for this application ID, please log in with your username and password";
				$page = "ent_cs_login";
			}
		}
		else // login with username & password
		{
			$login_return = $login->Login_User( $this->normalized_data['cust_username'] , $this->normalized_data['cust_password'] );

			if( is_string( $login_return ) )
			{
				// set login to name they logged in as
				$this->cs['login'] = $_SESSION['cs']['login'] = $this->login = $this->normalized_data['cust_username'];
				
				$this->application_id = $login->Find_App_ID($this->normalized_data['cust_username']);

				//If user had multiple apps with balance > 0 then show them an error
				if($this->application_id === FALSE) 
				{
					$errors = "Unable to display account information at this time. Please call customer service at " . 
							   $this->Get_Phone_By_Property_Short($this->property_short);
					$page = "ent_cs_login";
					return array( 'page' => $page, 'errors' => $errors, 'login' => $this->normalized_data['cust_username'] );
				}
				
				// Log session_id in olp.cs_session
				// User hits a cs page w/ a decoded application_id
				$this->Log_CS_Session( $this->application_id );
				
				if ((!isset($_SESSION['cs']['application_id'])))
				{
					$cs = $this->Get_User_Data($this->application_id);
					$cs = $cs['cs'];
				}
				else
				{
					$cs = $_SESSION['cs'];
				}
				
				// Grab a new configuration pointing to the enterprise set
				$this->Set_Enterprise_Config($cs);
				
				//Put stuff in session
				$_SESSION['cs']['md5_hash_match'] = 1;
				$_SESSION['cs']['cust_username'] = $this->normalized_data['cust_username'];
				$_SESSION['cs']['cust_password'] = $this->normalized_data['cust_password'];
				$_SESSION['cs']['application_id'] = $this->application_id;
				
				// Hit the cs_login_link stat
				Stats::Hit_Stats('cs_login', $this->session, $this->event, $this->applog, $this->application_id);
				
				//Marked as log in
				$_SESSION['cs']['logged_in'] = TRUE;
			}
			else // no match on username/pass
			{
				//set error message and make sure they get the login page again
				$errors = "Your login/password combination was not found. Please try again. Passwords are case sensitive";
				$page = "ent_cs_login";
			}
		}
		
		$_SESSION['is_react'] = ((isset($_SESSION['cs']['is_react']) && $_SESSION['cs']['is_react'] == 1) || $_SESSION['cs']['olp_process'] == 'ecashapp_react') ? TRUE : FALSE;
		

		// done return results
		return array( 'page' => $page, 'errors' => $errors, 'login' => $this->login );

	}

	private function Setup_Config( $license_key, $promo_id, $promo_sub_code, $page = NULL )
	{

		// instantiate config6
        $sql = Setup_DB::Get_Instance("management", $_SESSION["config"]->mode);
        $config_obj = new Cache_Config($sql);

		$config = $config_obj->Get_Site_Config( $license_key, $promo_id, $promo_sub_code);

		$config->site_type = $_SESSION['config']->site_type;
		$config->site_type_obj = $_SESSION['config']->site_type_obj;

		return $config;

	}

	public function Update_Status( $application_id, $status, $loan_action = NULL )
	{

        if(empty($application_id))
        {
            if(($application_id = $this->Get_Application_ID()) === FALSE)
            {
                $error = "Cannot Update Status - App ID is not set";
                $this->applog->Write($error);
                throw new Exception($error);
            }
        }

        if($_SESSION['config']->use_new_process && isset($loan_action) && is_array($loan_action))
        {
	        $app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
	        
	        foreach($loan_action as $action)
	        {
	        	$app_campaign_manager->Insert_Loan_Action($application_id, $action);
	        }
        }
        elseif(!$_SESSION['config']->use_new_process)
        {
	        //Update application_status information
			$data['application_id'] 		= $application_id;
			$data['application_status_id']	= $status;
	
			if($status == 'ecash_sign_docs')
			{
				$history_id  = $this->olp_mysql->Update_Application_Status($status, $application_id, $loan_action, $_SESSION['data']['ecash_document_id']);
			}
			else
			{
				$history_id  = $this->olp_mysql->Update_Application_Status($status, $application_id, $loan_action);
			}
        }

		return;
	}

	private function Update_User_Data( $application_id, $update_loan_note = FALSE )
	{
		if(empty($application_id))
        {
            $error = "Cannot Update User Data - App ID is not set";
            $this->applog->Write($error);
            throw new Exception($error);
        }
        		
		$data['date_fund_estimated']			= $_SESSION['cs']['qualify']['fund_date'];
		$data['fund_qualified']					= $_SESSION['cs']['qualify']['fund_amount'];
		$data['finance_charge']					= $_SESSION['cs']['qualify']['finance_charge'];
		$data['apr']							= $_SESSION['cs']['qualify']['apr'];
		$data['payment_total']					= $_SESSION['cs']['qualify']['total_payments'];
		$data['date_first_payment']				= $_SESSION['cs']['qualify']['payoff_date'];
		
		$bank_info = array();
		$paydates = array();
		// Only update the bank info if we're on an email confirm site and the info has
		// actually been passed to us.
		if(isset($_SESSION['data']['bank_aba']))
		{
			$data['bank_aba'] = $bank_info['bank_aba'] = $_SESSION['data']['bank_aba'];
		}
		
		if(isset($_SESSION['data']['bank_account']))
		{
			$data['bank_account'] = $bank_info['bank_account'] = $_SESSION['data']['bank_account'];
		}
		
		// Only update paydate info if it needs updating.
		if( isset( $this->cs['new_model'] ) )
		{
			
			if($_SESSION['config']->use_new_process)
			{
				$data['paydate_model']		= strtoupper($this->cs['new_model']);
				$data['income_frequency']	= strtoupper($_SESSION['cs']['new_paydate']['frequency']);
			}
			else
			{
				$data['paydate_model']		= strtolower($this->cs['new_model']);
				$data['income_frequency']	= strtolower($_SESSION['cs']['new_paydate']['frequency']);
			}
			
			switch( $_SESSION['cs']['new_paydate']['frequency'] )
			{
				case 'WEEKLY':
					$data['day_of_week']		= strtolower( $_SESSION['cs']['new_paydate']['weekly_day'] );
					//unset others
					$data['last_paydate']		= "";
					$data['day_of_month_1']		= "NULL";
					$data['day_of_month_2']		= "NULL";
					$data['week_1']				= "NULL";
					$data['week_2']				= "NULL";
					
					break;
				case 'BI_WEEKLY':
					$data['day_of_week']		= strtolower( $_SESSION['cs']['new_paydate']['biweekly_day'] );

					$biweekly = $_SESSION['cs']['new_paydate']['biweekly_date'];
					//rsk
					$data['last_paydate']		=  sprintf( "%s-%s-%s", substr($biweekly,6,4), substr($biweekly,0,2), substr($biweekly,3,2) );
					//unset others
					$data['day_of_month_1']		= "NULL";
					$data['day_of_month_2']		= "NULL";
					$data['week_1']				= "NULL";
					$data['week_2'] 			= "NULL";
					break;
				case 'TWICE_MONTHLY':
					if( $_SESSION['cs']['new_paydate']['twicemonthly_type'] == 'date' )
					{
						$data['day_of_month_1']	= $_SESSION['cs']['new_paydate']['twicemonthly_date1'];
						$data['day_of_month_2'] = $_SESSION['cs']['new_paydate']['twicemonthly_date2'];
						//unset others
						$data['day_of_week'] 		= "NULL";
						$data['week_1']				= "NULL";
						$data['week_2'] 			= "NULL";
						$data['last_paydate']		= "";
					}
					else
					{
						$data['week_1'] 		= substr( $_SESSION['cs']['new_paydate']['twicemonthly_week'], 0, 1 );
						$data['week_2']			= substr( $_SESSION['cs']['new_paydate']['twicemonthly_week'], 2, 1 );
						$data['day_of_week'] 	= strtolower( $_SESSION['cs']['new_paydate']['twicemonthly_day'] );
						//unset others
						$data['last_paydate']		= "NULL";
						$data['day_of_month_1']		= "NULL";
						$data['day_of_month_2']		= "NULL";
					}
					break;
				case 'MONTHLY':
					if( $_SESSION['cs']['new_paydate']['monthly_type'] == 'date' )
					{
						$data['day_of_month_1']	= $_SESSION['cs']['new_paydate']['monthly_date'];
						//unset others
						$data['day_of_week'] 		= "NULL";
						$data['last_paydate']		= "";
						$data['day_of_month_2']		= "NULL";
						$data['week_1']				= "NULL";
						$data['week_2'] 			= "NULL";

					}
					elseif( $_SESSION['cs']['new_paydate']['monthly_type'] == 'day' )
					{
						$data['week_1']			= $_SESSION['cs']['new_paydate']['monthly_week'];
						$data['day_of_week']	= strtolower( $_SESSION['cs']['new_paydate']['monthly_day'] );
						//unset others
						$data['last_paydate']		= "";
						$data['day_of_month_1']		= "NULL";
						$data['day_of_month_2']		= "NULL";
						$data['week_2'] 			= "NULL";
					}
					else
					{
						$data['day_of_week']	= strtolower( $_SESSION['cs']['new_paydate']['monthly_after_day'] );
						$data['day_of_month_1']	= $_SESSION['cs']['new_paydate']['monthly_after_date'];
						//unset others
						$data['last_paydate']		= "";
						$data['day_of_month_2']		= "NULL";
						$data['week_1']				= "NULL";
						$data['week_2'] 			= "NULL";
					}
					break;
			}
			
			
	        $paydates = array(
	        	'week_1' => $data['week_1'],
	        	'week_2' => $data['week_2'],
	        	'day_of_month_1' => $data['day_of_month_1'],
	        	'day_of_month_2' => $data['day_of_month_2']
	        );
	        
	        if($data['day_of_week'] !== 'NULL')
	        {
	        	$days = array('sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6);
	        	$paydates['day_of_week'] = $days[$data['day_of_week']];
	        }
        }




        
        $app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
        
		if(!empty($bank_info))
		{
        			$app_campaign_manager->Update_Bank_Info_Encrypted($application_id, $bank_info['bank_aba'], $bank_info['bank_account']);	
		}

		if( isset( $this->cs['new_model'] ) )
		{
			if(!empty($paydates)) $app_campaign_manager->Update_Paydate($application_id, $paydates);
			$app_campaign_manager->Update_Income($application_id, $data['income_frequency']);
		}


        if(!$_SESSION['config']->use_new_process)
        {
        	$this->olp_mysql->Update_Application($data, $application_id);
        }

        /*
        	We ran qualify on them, so if that information changed, we need to
        	update the database information.
        */
		if ($update_loan_note)
		{
			$app_campaign_manager->Update_Loan_Note(
				$application_id,
				$data['date_fund_estimated'],
				$data['fund_qualified'],
				$data['apr'],
				$data['date_first_payment'],
				$data['finance_charge'],
				$data['payment_total']
			);
		}

        unset($data);
        
        /*
        	If we're updating the loan note, we don't need to do this as this information
        	shouldn't have changed. [BF]
        */
        if(!$update_loan_note)
        {
	        // Update the OLP with any new data (bank and paydate on confirmation [RL]
			$data = $_SESSION['data'];
			$data['bank_name'] = strtoupper($_SESSION['cs']['bank_name']);
			$data['income_direct_deposit'] = $_SESSION['cs']['direct_deposit'];
			$data['bank_account_type'] = $_SESSION['cs']['bank_account_type'];
			if(!isset($data['income_monthly_net']))
			{
				$data['income_monthly_net'] = $_SESSION['cs']['income_monthly'];
			}
			if(!isset($data['qualify_info']))
			{
				$data['qualify_info'] = $_SESSION['cs']['qualify_info'];
			}
			
			$app_campaign_manager->Insert_Application_Confirmation($application_id, $data);
        }
		
	}

	public static function Get_The_Kitchen_Sink( &$sql, $database, $application_id )
	{
		if(empty($application_id))
        {
            $error = "Cannot Get The Kitchen Sink - App ID is not set";
            $applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $_SESSION['config']->site_name, APPLOG_ROTATE, APPLOG_UMASK);
            $applog->Write($error);
            throw new Exception($error);
        }
        
        if($_SESSION['config']->use_new_process)
        {
        	if($_SESSION['config']->continuation)
			{
		        $sink_query = "
		        SELECT
						personal_encrypted.first_name					AS name_first,
						personal_encrypted.middle_name				AS name_middle,
						personal_encrypted.last_name					AS name_last,
						personal_encrypted.date_of_birth,
						personal_encrypted.email						AS email_primary,
						personal_encrypted.home_phone					AS phone_home,
						personal_encrypted.cell_phone					AS phone_cell,
						personal_encrypted.fax_phone					AS phone_fax,
						
						residence.address_1					AS home_street,
						residence.apartment					AS home_unit,
						residence.city						AS home_city,
						residence.state						AS home_state,
						residence.zip						AS home_zip,
				
						campaign_info.promo_id,
						campaign_info.promo_sub_code
					FROM
						personal_encrypted
						INNER JOIN residence USING (application_id)
						INNER JOIN campaign_info USING (application_id)
					WHERE
						personal_encrypted.application_id = {$application_id}
		        ";
	
				$mysql_result = $sql->Query($database, $sink_query);
			}
			else 
			{
				$sink_query = "
		        SELECT
						personal_encrypted.first_name					AS name_first,
						personal_encrypted.middle_name				AS name_middle,
						personal_encrypted.last_name					AS name_last,
						personal_encrypted.date_of_birth,
						personal_encrypted.social_security_number,
						personal_encrypted.best_call_time,
						personal_encrypted.email						AS email_primary,
						personal_encrypted.drivers_license_number		AS state_id_number,
						personal_encrypted.drivers_license_state		AS legal_state,
						personal_encrypted.home_phone					AS phone_home,
						personal_encrypted.cell_phone					AS phone_cell,
						personal_encrypted.fax_phone					AS phone_fax,
						
						residence.address_1					AS home_street,
						residence.apartment					AS home_unit,
						residence.city						AS home_city,
						residence.state						AS home_state,
						residence.zip						AS home_zip,
		
						bank_info_encrypted.bank_name,
						bank_info_encrypted.routing_number			AS bank_aba,
						bank_info_encrypted.account_number			AS bank_account,
						bank_info_encrypted.bank_account_type,
						bank_info_encrypted.direct_deposit			AS income_direct_deposit,
						
						employment.employer					AS employer_name,
						employment.date_of_hire				AS employer_length,
						employment.income_type,
						employment.work_phone				AS phone_work,
						employment.work_ext					AS phone_work_ext,
		
						IFNULL(ELT(paydate.day_of_week, 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'), 'SUN') AS day_of_week,
						paydate.next_paydate,
						paydate.day_of_month_1,
						paydate.day_of_month_2,
						paydate.week_1,
						paydate.week_2,
						paydate.paydate_model_id			AS paydate_model,
		
						income.monthly_net					AS income_monthly_net,
						DATE_FORMAT(income.pay_date_1, '%Y-%m-%d')	AS pay_date1,
						DATE_FORMAT(income.pay_date_2, '%Y-%m-%d')	AS pay_date2,
						income.pay_frequency				AS income_frequency,
		
						loan_note.fund_amount				AS fund_qualified,
						loan_note.fund_amount				AS fund_actual,
						DATE_FORMAT(loan_note.estimated_fund_date, '%Y-%m-%d') AS date_fund_estimated,
		
						campaign_info.promo_id,
						campaign_info.promo_sub_code
					FROM
						personal_encrypted
						INNER JOIN residence USING (application_id)
						INNER JOIN bank_info_encrypted USING (application_id)
						INNER JOIN employment USING (application_id)
						INNER JOIN paydate USING (application_id)
						INNER JOIN income USING (application_id)
						INNER JOIN loan_note USING (application_id)
						INNER JOIN campaign_info USING (application_id)
					WHERE
						personal_encrypted.application_id = {$application_id}
		        ";
	
				$mysql_result = $sql->Query($database, $sink_query);
			}
        }
        else
        {
	        $sink_query = "
				SELECT
					application.name_first,
					application.name_middle,
					application.name_last,
					application.dob									AS date_of_birth,
					application.ssn									AS social_security_number,
					application.street								AS home_street,
					application.unit								AS home_unit,
					application.city								AS home_city,
					application.state								AS home_state,
					application.zip_code							AS home_zip,
					application.call_time_pref						AS best_call_time,
					application.email								AS email_primary,
					application.bank_name,
					application.bank_aba,
					application.bank_account,
					application.bank_account_type,
					application.employer_name,
					application.date_hire							AS employer_length,
					application.legal_id_number						AS state_id_number,
					application.legal_id_state						AS legal_state,
					application.day_of_week							AS day_of_week,
					application.last_paydate						AS next_paydate,
					application.day_of_month_1						AS day_of_month_1,
					application.day_of_month_2						AS day_of_month_2,
					application.week_1								AS week_1,
					application.week_2								AS week_2,
					application.paydate_model,
					application.income_monthly						AS income_monthly_net,
					application.income_direct_deposit,
					application.income_date_soap_1					AS pay_date1,
					application.income_date_soap_2					AS pay_date2,
					application.income_source						AS income_type,
					application.income_frequency					AS income_frequency,
					application.loan_type_id						AS loan_type_id,
					application.phone_home,
					application.phone_work,
					application.phone_work_ext,
					application.phone_cell,
					application.phone_fax,
					application.fund_qualified,
					application.fund_actual,
					application.date_fund_estimated,
					campaign_info.promo_id,
					campaign_info.promo_sub_code
				FROM
					application
				JOIN
					campaign_info ON campaign_info.application_id = application.application_id
				WHERE
					application.application_id = " . $application_id . "
			";
	
			$mysqli_result = $sql->Query( $sink_query );
        }

		//if( $row = $mysqli_result->Fetch_Array_Row( MYSQLI_ASSOC ) )
		if($row = (($_SESSION['config']->use_new_process) ? $sql->Fetch_Array_Row($mysql_result) : $mysqli_result->Fetch_Array_Row(MYSQLI_ASSOC)))
		{
			if($_SESSION['config']->use_new_process)
			{
				$crypt_config 		= Crypt_Config::Get_Config(BFW_MODE);
				$crypt_object			= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
				$row['date_of_birth'] 		= $crypt_object->decrypt($row['date_of_birth']);
				$row['social_security_number'] 	= $crypt_object->decrypt($row['social_security_number']);
				$row['bank_aba'] 			= $crypt_object->decrypt($row['bank_aba']);
				$row['bank_account'] 		= $crypt_object->decrypt($row['bank_account']);
			}
			
			// import fields without a no_:
			// these do not require special manipulation
			foreach ($row as $field => $value)
			{

				if ( substr( $field, 0, 3 ) != 'no_' )
				{
					$data[$field] = trim( $value );
				}

			}

			// change a few fields into the required format
			// mm/dd/yyyy format for dob
			// dob is formatted in ldb as yyyy-mm-dd
			$dob = explode ("-", $data["date_of_birth"]);
			$data['dob'] = $dob[1] . "/" . $dob[2] . "/" . $dob[0];

			$ssn = $data['social_security_number'];

			$data['ssn_part_1'] = substr($ssn, 0, 3);
			$data['ssn_part_2'] = substr($ssn, 3, 2);
			$data['ssn_part_3'] = substr($ssn, 5, 4);

			if( strlen( $data['pay_date1'] ) > 0 )
			{
				$paydate = strtotime($data['pay_date1']);
				$data['income_date1_y'] = date('Y', $paydate);
				$data['income_date1_m'] = date('m', $paydate);
				$data['income_date1_d'] = date('d', $paydate);
			}

			if( strlen( $data['pay_date2'] ) > 0 )
			{
				$paydate = strtotime($data['pay_date2']);
				$data['income_date2_y'] = date('Y', $paydate);
				$data['income_date2_m'] = date('m', $paydate);
				$data['income_date2_d'] = date('d', $paydate);
			}

			if($_SESSION['config']->use_new_process)
			{
				$references_query = "
					SELECT
						full_name AS name_full,
						phone AS phone_home,
						relationship
					FROM
						personal_contact
					WHERE
						application_id = {$application_id}
					";
	
				$ref_result = $sql->Query($database, $references_query);
	
				$x = 0;
				while( $ref = $sql->Fetch_Array_Row($ref_result) )
				{
					$x++;
					$data["ref_0" . $x . "_name_full"] = trim( $ref['name_full'] );
					$data["ref_0" . $x . "_phone_home"] = trim( $ref['phone_home'] );
					$data["ref_0" . $x . "_relationship"] = trim( $ref['relationship'] );
				}
			}
			else
			{
				$data['income_direct_deposit'] = ( strtolower($data['income_direct_deposit']) == 'yes' ) ? 'TRUE' : 'FALSE';
				$data['employer_length'] = date('Y-m-d', strtotime($data['employer_length']));
				
				$references_query = "
					SELECT
						name_full,
						phone_home,
						relationship
					FROM
						personal_reference
					WHERE
						application_id = " . $application_id . "
					";
	
				$ref_result = $sql->Query( $references_query );
	
				$x = 0;
				while( $ref = $ref_result->Fetch_Array_Row( MYSQL_ASSOC ) )
				{
					$x++;
					$data["ref_0" . $x . "_name_full"] = trim( $ref['name_full'] );
					$data["ref_0" . $x . "_phone_home"] = trim( $ref['phone_home'] );
					$data["ref_0" . $x . "_relationship"] = trim( $ref['relationship'] );
				}
			}


		}

		return $data;
	}

	
	public function Get_Online_Confirmation_Status( $application_id = NULL )
	{
		if(empty($application_id))
		{
			$error = "Cannot Get online status.  Application id not set";
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $_SESSION['config']->site_name, APPLOG_ROTATE, APPLOG_UMASK);
			$applog->Write($error);
			throw new Exception($error);
		}

		$olp_process = FALSE;
		
		$query = "SELECT olp_process FROM application WHERE application_id = {$application_id}";
		
		try
		{
			if($_SESSION['config']->use_new_process)
			{
				$result = $this->sql->Query($this->database, $query);
				
				if($result = $this->sql->Fetch_Object_Row($result))
				{
					$olp_process = $result->olp_process;
				}
			}
			else
			{
				$mysqli_result = $this->sqli->Query( $query );
				
				
				if( $result = $mysqli_result->Fetch_Object_Row() )
				{
					$olp_process = $result->olp_process;
				}
			}
		}
		catch(MySQL_Exception $e)
		{
			$olp_process = FALSE;
		}

		return $olp_process;
	}
	
	/**
		@privatesection
		@private
		@fn array Get_Application_Status()
		@brief
			Retrieve Application Status ID & Name given an Application ID
		@param $application_id int
		@return 
			array An Array containing 'id' and 'name'.
	*/	
	public function Get_Application_Status( $application_id = NULL )
	{
		if(empty($application_id))
		{
			$error = "Cannot Get Application Status.  Application id not set";
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $_SESSION['config']->site_name, APPLOG_ROTATE, APPLOG_UMASK);
			$applog->Write($error);
			throw new Exception($error);
		}

		$ret_arr = FALSE;

		$query = "
			SELECT 
				app.application_status_id, 
				stat.name
			FROM 
				application app
				INNER JOIN application_status stat
					ON app.application_status_id = stat.application_status_id
			WHERE
				application_id = {$application_id}";

		try
		{
			if($_SESSION['config']->use_new_process && !EnterpriseData::isCFE($this->property_short))
			{	
				$result = $this->sql->Query($this->database, $query);
				
				if($result = $this->sql->Fetch_Object_Row($result))
				{
					$ret_arr['id'] = $result->application_status_id;
					$ret_arr['name'] = strtolower($result->name);
				}
			}
			else
			{
				$mysqli_result = $this->sqli->Query( $query );
				
				if( $result = $mysqli_result->Fetch_Object_Row() )
				{
					$ret_arr['id'] = $result->application_status_id;
					$ret_arr['name'] = strtolower($result->name);
				}
			}
		}
		catch(MySQL_Exception $e)
		{
			$ret_arr = FALSE;
		}

		return $ret_arr;
	}

	private function Active_Customer_Check()
	{
		$result = true;

		if($this->blackbox instanceof Blackbox_Adapter)
		{
			$result = $this->blackbox->runRule($this->property_short, 'cashline');
		}

		return $result;
	}

	/**
	 * Returns other applications that match on SSN and that have been sold to the same company.
	 *
	 * @param int $app_id
	 * @return array The applications that match.
	 */
	public function Get_Other_Apps($app_id)
	{
		/*
			Changed the query to only check for applications in that company. Also reformatted the
		 	query. Ticket #5440 (Teleweb Ticket) [BF]
		 */
		$query = "SELECT
				a2.application_id,
				a2.application_type
			FROM
				personal_encrypted p1
				INNER JOIN application a1
					ON p1.application_id = a1.application_id
				INNER JOIN personal_encrypted p2
					ON p1.social_security_number = p2.social_security_number
				INNER JOIN application a2 USE INDEX (PRIMARY)
					ON p2.application_id = a2.application_id
			WHERE
				p1.application_id = $app_id
				AND p2.application_id != p1.application_id
				AND a1.target_id = a2.target_id";
		
		try
		{
			$result = $this->sql->Query($this->database, $query);
				
			while($row = $this->sql->Fetch_Object_Row($result))
			{
				$ret['id'] = $row->application_id;
				$ret['type'] = $row->application_type;
				$ret_arr[] = $ret;
			}
		}
		catch(MySQL_Exception $e)
		{
			$ret_arr = FALSE;
		}
		
		return $ret_arr;
	}

	
	/**
		@privatesection
		@private
		@fn string Process_Password_Change()
		@brief
			The entry point to updating a password

		@return string
			string Next page to display
	*/
	private function Process_Password_Change()
	{

		// Initialize variables
		$return_page	= FALSE;
		$valid			= TRUE;
		$tries = $_SESSION['pw_change'] + 1;

		// check for max number of tries
		if ($tries > 8)
		{
			// return message, set flag in db to not allow pw change in the future for security?
			$this->template_messages->Add_User_Message('Maximum number of password change tries reached');
			return 'ent_status';
		}

		if(!isset($_SESSION['cs']['cust_username']) || !isset($_SESSION['cs']['cust_password']))
		{
			//Attempt to load from ldb
			$login = new Login_Handler($this->sqli, $this->property_short, $this->database, $this->applog);
			$user_info = $login->Find_User_Info_By_App_ID($_SESSION['cs']['application_id']);
			
			if($user_info)
			{
				$_SESSION['cs']['cust_username'] = $user_info['login'];
				$_SESSION['cs']['cust_password'] = $login->Decrypt_Password($user_info['password']);
			}
			else
			{			
				// Form was submitted, but user has no username/pass
				$this->template_messages->Add_User_Message('Your account does not have a username/password yet. Please try again later.');
				return 'ent_cs_password_change';
			}
		}

		// Was form submitted
		if(isset($this->collected_data['password_current']) == FALSE)
		{
			// Form was not submitted, so just show empty form
			$return_page = 'ent_cs_password_change';
		}
		else if(isset($this->collected_data['cancel']))
		{
			// Form was submitted, but user canceled process so return to status page
			$return_page = 'ent_status';
			$this->template_messages->Add_User_Message('You canceled the password change');
		}
		else
		{
			$_SESSION['pw_change'] = $tries;
			$this->template_messages->Add_User_Message($_SESSION['pw_change']);

			// Validate data
			if($this->Validate_Change_Password_Data() == FALSE)
			{
				$valid = FALSE;
			}

			// If no errors then set new password
			if($valid)
			{
				try
				{
					//We need to connect to the live database for this.
					$sqli = &Setup_DB::Get_Instance('mysql', $_SESSION['config']->mode, $this->property_short);
					$login = new Login_Handler($sqli, $this->property_short, $this->database, $this->applog);
					
					//Finally, update the password					
					$set_password_result = $login->Set_Password($_SESSION['cs']['cust_username'],
																$_SESSION['cs']['cust_password'],
																$this->normalized_data['password_new_1']);
				}
				catch(Exception $e)
				{
					$this->template_messages->Add_Error_Message($e->getMessage());
		
					$set_password_result = FALSE;
		
					$valid = FALSE;
				}
			}

			// Any errors?
			if( $set_password_result != FALSE )
			{
				//Update the session
				$_SESSION['cs']['cust_password'] = $_SESSION['data']['cust_password'] = $this->normalized_data['password_new_1'];
			}

			// Errors ?
			if($valid == FALSE)
			{
				// Send them back to the form
				$page = 'ent_cs_password_change';
			}
			else
			{
				// All went well take them back to the status page
				$return_page = 'ent_status';
				$this->template_messages->Add_User_Message('Your password was updated successfully');
			}
		}

		// Return the page we want to show
		return $return_page;
	}

	/**
		@private
		@fn array Validate_Change_Password_Data()
		@brief
			Checks passed data and returns any errors found

		Makes sure the data in $this->normalized_data is in good condition and that it passes
		 any data validation checks we need. If any checks fails a string message is add to
		 the errors array and then returned

		@return boolean
			TRUE if data is ok, FALSE if there were any errors
	*/
	function Validate_Change_Password_Data()
	{
		// Data validity indicator
		$valid = TRUE;

		$old_password = ($this->collected_data['cust_password']) ? $this->collected_data['cust_password'] : $_SESSION['cs']['cust_password'];

		// Get current password
		if(isset($this->normalized_data['password_current']) == FALSE)
		{
			$this->normalized_data['password_current'] = FALSE;
		}
		// Do we have a password?
		if(empty($this->normalized_data['password_current']))
		{
			$this->template_messages->Add_Error_Message('Please enter you current password');
			$this->template_messages->Add_Custom_Message('*', 'password_current_error');
			$valid = FALSE;
		}
		// Check if password is correct
		elseif($old_password != $this->normalized_data['password_current'])
		{
			$this->template_messages->Add_Error_Message('Password incorrect');
			$this->template_messages->Add_Custom_Message('*', 'password_current_error');
			$valid = FALSE;
		}

		// Check new passwords if we don't have any errors
		if($valid)
		{
			// Get new password 1
			if(isset($this->normalized_data['password_new_1']))
			{
				$this->normalized_data['password_new_1'] = trim($this->normalized_data['password_new_1']);
			}
			else
			{
				$this->normalized_data['password_new_1'] = FALSE;
			}

			// Check size
			if(strlen($this->normalized_data['password_new_1']) < 4)
			{
				$this->template_messages->Add_Error_Message('Password too short, please make it at least 4 letters long');
				$this->template_messages->Add_Custom_Message('*', 'password_new_1_error');
				$valid = FALSE;
			}
			else if(strlen($this->normalized_data['password_new_1']) > 48)
			{
				$this->template_messages->Add_Error_Message('Password too long, please make it 48 letters or less');
				$this->template_messages->Add_Custom_Message('*', 'password_new_1_error');
				$valid = FALSE;
			}
		}

		//Now check password 2 if we don't have any errors
		if($valid)
		{
			// Get new password 2
			if(isset($this->normalized_data['password_new_2']))
			{
				$this->normalized_data['password_new_2'] = trim($this->normalized_data['password_new_2']);
			}
			else
			{
				$this->normalized_data['password_new_2'] = FALSE;
			}

			// Is it the same as passowrd 1?
			if($this->normalized_data['password_new_1'] != $this->normalized_data['password_new_2'])
			{
				$this->template_messages->Add_Error_Message('Passwords didn\'t match, please try again');
				$this->template_messages->Add_Custom_Message('*', 'password_new_2_error');
				$valid = FALSE;
			}
		}

		// Return if data is valid or not
		return $valid;
	}

	/**

		@desc Runs our after-confirm verification rules
		@return A reason for verification, or false

	*/
	public function Verification_Rules($application_id)
	{
        if(empty($application_id))
        {
            $error = "Cannot Run Verification Rules - App ID is not set";
            $this->applog->Write($error);
            throw new Exception($error);
        }
        
		// these are somewhat hacked in: when we get
		// around to rewriting BlackBox (yes, again :-p),
		// this will be completely different
		$verify_reason = '';

		// are we using the automated verification queue yet?
		if (in_array('use_verify_queue', $this->ent_prop_list[$_SESSION['config']->site_name]))
		{
			/** ALL OTHER CHECKS **/

			$fail = array('VERIFY', 'ERROR');
			$pass = array('VERIFIED');

			// get events that we either marked "need to verify" (VERIFY)
			// or, in the DataX's case, didn't run 'cause of an error
			$events = $this->event->Fetch_Events($application_id, NULL, array_merge($fail, $pass), array(NULL, $this->property_short));

			if (count($events))
			{
				// We will check each event against the Loan_Action database and
				// return the event name (we will add a new Loan_Action if we know
				// of the event but it's not in the database or not return the event)
				$verify_reason = $this->Check_Verification_Entry($events, $fail);
			}

		}

		if (!$verify_reason) $verify_reason = FALSE;
		return($verify_reason);

	}

	private function Check_Verification_Entry($events, $fail)
	{
		
		$verify_reason = null;
		$existing = array();

		// array of events and their descriptions
		$descriptions = array(
			'VERIFY_WORK_BIZ' => 'Work phone may be a residential number.',
			'VERIFY_WORK_CELL' => 'Work phone may be a cellular number.',
			'DATAX_PERF' => 'Could not complete verification due to a missing or '.
				'invalid DataX response.',
			'VERIFY_SAME_WH' => 'Work and home phone are the same.',
			'VERIFY_MIN_INCOME' => 'Self-reported monthly income is below $1,300.',
			'VERIFY_PAYDATES' => 'Pay dates are within 5 days of each other.',
			'ABA_CHECK' => 'More than three social security numbers have been used with '.
				'the specified bank account and routing number.',
			'VERIFY_W_TOLL_FREE'=> 'Work phone is a toll free number.',
			'VERIFY_WH_AREA'=> 'Home and work area code mismatch.',
			'VERIFY_W_PHONE'=> 'Unverified work phone.',
			'VERIFY_SAME_CR_W_PHONE'=> 'Cell or residential phone in work phone.'
		);

		try
		{

			// fetch existing loan actions for these events
			$query = "SELECT name_short FROM loan_actions WHERE
				name_short IN ('".implode("', '", array_keys($events))."')";
			$result = $this->sqli->Query($query);

			while ($rec = $result->Fetch_Array_Row())
			{
				$existing[$rec['name_short']] = TRUE;
			}

		}
		catch (Exception $e)
		{
		}

		foreach ($events as $event=>$results)
		{

			// what did we fail?
			$failed = array_intersect($fail, $results);

			if (count($failed))
			{

				// don't attempt to create
				if (!isset($existing[$event]))
				{

					$description = FALSE;

					// is this an event for a suppression list?
					if (preg_match('/^LIST_.*_(\d+)$/i', $event, $m))
					{

						// assume we fail
						$description = 'Unknown suppression list failure.';

						try
						{

							// fetch this list's description
							$query = "SELECT list_id, loan_action FROM lists WHERE list_id = '".$m[1]."'";
							$result = $this->sql->Query($this->database, $query);

							if ($rec = $this->sql->Fetch_Array_Row($result))
							{
								$description = $rec['loan_action'];
							}

						}
						catch (Exception $e)
						{
						}

					}
					else
					{

						if (isset($descriptions[$event]))
						{

							// grab our description
							$description = $descriptions[$event];

							// special case for these: if we failed these with an 'ERROR', then
							// DataX also failed, and we want to give THAT as the reason
							if (($event == 'VERIFY_WORK_BIZ') || ($event == 'VERIFY_WORK_CELL'))
							{
								if (in_array('ERROR', $failed)) $event = FALSE;
							}

						}
						else
						{
							$event = FALSE;
						}

					}

					if ($description !== FALSE)
					{
						if($_SESSION['config']->use_new_process)
						{
							$_SESSION['ldb_data'][$this->application_id]['loan_actions'][] = array(
								'event' => $event,
								'description' => $description
							);
						}
						else
						{
							// create a new loan action
							$this->olp_mysql->Insert_Loan_Action($event, $description);
						}
					}

				}

				// only add this event to our list if we really
				// failed it, and not a related event (see DataX)
				if ($event !== FALSE)
				{
					$verify_reason[] = $event;
				}

			}

		}

		return $verify_reason;

	}


	// Force a DECLINE in both ecash (ldb) and OLP
	private function Force_Decline($application_id, $comment_text = NULL)
	{
        if(empty($application_id))
        {
            $error = "Cannot Force Decline - App ID is not set";
            $this->applog->Write($error);
            throw new Exception($error);
        }
        
		// updating OLP
		$app_campaign_manager = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		$app_campaign_manager->Update_Application_Status($application_id, 'FAILED');

		// updating ldb
		$this->Update_Status($application_id, "declined");

		if ($comment_text)
		{
			if($_SESSION['config']->use_new_process)
			{
				$_SESSION['ldb_data'][$application_id]['comments'][] = array(
					'property_short' => $this->property_short,
					'application_id' => $application_id,
					'type' => 'declined',
					'comment' => $comment_text
				);
			}
			else
			{
				// updating ldb::comment table
				$comment['property_short'] = $this->property_short;
				$comment['application_id'] = $application_id;
				$comment['type'] = "declined";
				$comment['comment'] = $comment_text;
				$this->olp_mysql->Insert_Comment($comment);
			}
		}

		return;

	}

	private function Log_CS_Session( $application_id )
	{
		if ( strlen( $application_id ) && strlen( session_id() ) && !isset($_SESSION['cs_session_id']) )
		{
			 $query = "
				REPLACE INTO cs_session SET
					application_id=".$application_id.",
					session_id='".session_id()."',
					date_created=NOW()
				";
			try
			{
            	$sql_result = $this->sql->Query( $this->database, $query );
				$_SESSION['cs_session_id'] = session_id();
			}
			catch( MySQL_Exception $e )
			{
				// Could not insert into cs_session table
				$this->applog->Write( "Replace into cs_session failed.  application_id='".$application_id."', unique_id='".session_id()."'" );
				//throw $e;
			}
		}
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
		
		if (isset($this->application_id)
			&& (is_numeric($this->application_id) || is_numeric(base64_decode($this->application_id))))
		{
			$app_id = $this->application_id;
		}
		elseif (isset($this->normalized_data) && isset($this->normalized_data['application_id'])
			&& (is_numeric($this->normalized_data['application_id']) || is_numeric(base64_decode($this->normalized_data['application_id']))))
		{
			$app_id = $this->normalized_data['application_id'];
		}
		elseif (isset($_SESSION['application_id'])
			&& (is_numeric($_SESSION['application_id']) || is_numeric(base64_decode($_SESSION['application_id']))))
		{
			$app_id = $_SESSION['application_id'];
		}
		elseif (isset($_SESSION['cs']['application_id'])
			&& (is_numeric($_SESSION['cs']['application_id']) || is_numeric(base64_decode($_SESSION['cs']['application_id']))))
		{
			$app_id = $_SESSION['cs']['application_id'];
		}
		elseif (isset($_SESSION['data']['application_id'])
			&& (is_numeric($_SESSION['data']['application_id']) || is_numeric(base64_decode($_SESSION['data']['application_id']))))
		{
			$app_id = $_SESSION['data']['application_id'];
		}
		
		if (!is_numeric($app_id))
		{
			$app_id = base64_decode($app_id);
		}
		
		return is_numeric($app_id) ? $app_id : FALSE;
	}
	
    public function Check_For_LDB_App($application_id)
    {
    	$result = FALSE;
		
		if(!empty($application_id))
		{
			try
			{
				$query = "SELECT COUNT(*) AS count FROM application WHERE application_id = {$application_id}";
				$mysqli_result = $this->sqli->Query($query);
			
				$row = $mysqli_result->Fetch_Object_Row();

				$result = (intval($row->count) > 0);
			}
			catch(Exception $e)
			{
			}
		}

		return $result;
    }
    
    
    public function Get_Current_Loan_Type_ID($loan_type, $property_short)
    {
 		if(!empty($loan_type) && !empty($property_short))
 		{
 			try
 			{
 				$query = "SELECT loan_type_id
 							FROM loan_type 
 							WHERE name_short = '{$loan_type}'
 							AND company_id = (SELECT company_id from company WHERE name_short = '{$property_short}')";

 				$react_result = $this->sqli->Query($query);
 				$row = $react_result->Fetch_Array_Row(MYSQLI_ASSOC);
 				
 				if(!empty($row))
 				{
 					$result = $row['loan_type_id'];
 				} 
 			}
 			catch(Exception $e)
 			{
 				
 			}
 		}

 		return $result;
    }
    
    protected function Get_Current_Loan_Type($application_id)
    {
     	$result = 'standard';

 		if(!empty($application_id))
 		{
 			try
 			{
 				$query = "SELECT lt.name_short
 						FROM application a
 						INNER JOIN loan_type lt USING (loan_type_id)
 						WHERE a.application_id = {$application_id}";

 				$react_result = $this->sqli->Query($query);
 				$row = $react_result->Fetch_Array_Row(MYSQLI_ASSOC);
 				
 				if(!empty($row))
 				{
 					$result = $row['name_short'];
 				} 
 			}
 			catch(Exception $e)
 			{
 				
 			}
 		}

 		return $result;
    }
	
	/** Tests to see if this application is a react. Checks against
	 * both OLP and LDB.
	 *
	 * @param int $application_id
	 * @return bool
	 */
	public function Is_React($application_id)
	{
		$result = NULL;
		
		if (!empty($application_id))
		{
			// Check OLP first
			try
			{
				$query = "
					SELECT
						is_react
					FROM
						application
					WHERE
						application_id = {$application_id}";
				
				$react_result = $this->sql->Query($this->database, $query);
				if ($row = $this->sql->Fetch_Array_Row($react_result))
				{
					// OLP's is_react is either 0 or 1
					$result = (bool)$row['is_react'];
				}
			}
			catch(Exception $e)
			{
			}
			
			// Try LDB if not found in OLP
			if ($result === NULL)
			{
				try
				{
					$query = "
						SELECT
							is_react
						FROM
							application
						WHERE
							application_id = {$application_id}";
					
					$react_result = $this->sqli->Query($query);
					if ($row = $react_result->Fetch_Array_Row(MYSQLI_ASSOC))
					{
						// LDB's is_react is either no or yes
						$result = ($row['is_react'] == 'yes');
					}
				}
				catch (Exception $e)
				{
				}
			}
		}
		
		return $result !== NULL ? $result : FALSE;
	}
	
 	public function Get_React_App_ID($application_id)
 	{
 		$react_app_id = NULL;
 		
 		try
 		{
			$query = "SELECT application_id
					FROM react_affiliation
					WHERE react_application_id = {$application_id}";
					
			$react_result = $this->sqli->Query($query);
			if($row = $react_result->Fetch_Object_Row())
			{
				$react_app_id = $row->application_id;
			}
 		}
 		catch(Exception $e)
 		{
 			//Comment for good measure
 		}
 		
 		return $react_app_id;
 	}

	/**
     * Get Phone By Property Short
     * 
     * Cycles through the ent property list and gets the phone number out
     * @param string Property Short
     * @return string Phone #
     */
    private function Get_Phone_By_Property_Short($prop)
    {
    	$prop = strtoupper($prop);
    	
    	if($prop == "BB" && isset($_SESSION['config']->bb_force_winner) && $_SESSION['config']->bb_force_winner != "bb")
    	{
			$prop = $_SESSION['config']->bb_force_winner;
    	}

    	foreach($this->ent_prop_list as $ent)
    	{
    		if($ent['property_short'] == $prop)
    		{
    			return $ent['phone'];
    		} 
    	}
    	return false;
    }
   
	private function Process_Payment_Options($application_id = null)
	{
		require_once('ent_customer.php');
		require_once('ent_payment_options.php');
    	
		$acm = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
		$prop_list = $this->ent_prop_list[$_SESSION['config']->site_name];

		if(empty($_SESSION['holiday_array']))
		{
			$_SESSION['holiday_array'] = $this->Get_Holiday_Array();
		}
		
		
		// Mantis 11057
		$application_id = intval($application_id);
		
		if(empty($application_id))
		{
			$application_id = intval($this->Get_Application_ID());
		} 
		
		try 
		{
		$customer = new Ent_Customer($application_id, $this->property_short, $this->sqli, $acm);
		$payment_opts = new Ent_Payment_Options($customer->application, $this->sqli, $prop_list);
		

		$page_data = array(
			'app_status' => 'ent_payment_opts',
			'customer_name' => $customer->Get_Customer_Name(),
			'react_offer' => $customer->can_react
		);
		
		if(empty($this->normalized_data['pay_down_type']) && !isset($this->normalized_data['ent_payment_opts_esig']))
		{
			$page = 'ent_payment_opts';
		}
		elseif(isset($this->collected_data['ent_status_override']))
		{
			$page = $this->normalized_data['ent_status_override'];
		}
		else
		{
			$page = $this->normalized_data['page'];
		}

		$can_view = $payment_opts->Can_View_Page($customer->status);
		//If they already have a paydown/payout scheduled, don't let them do anything.
		if(!empty($can_view))
		{
			$data = $can_view;
		}
		else
		{
			switch($page)
			{
				case 'ent_payment_opts_esig_submitted':
				{
					$data = $payment_opts->ESign_Account_Summary_Doc($this->normalized_data, $this->property_short);
					break;
				}
				
				case 'ent_payment_opts_submitted':
				{
					$this->normalized_data['title_loan'] = $this->title_loan;
					$data = $payment_opts->Generate_Account_Summary_Doc($this->normalized_data, $this->property_short);
					break;
				}
	
				case 'ent_payment_opts_esig_canceled':
				{
					unset($_SESSION['account_summary']);
				}
				
				default:
				{
					$data = $payment_opts->Build_Page();
					break;
				}
			}
		}


		$page_data = array_merge($page_data, $data);
		$page_data = $customer->Format_Dates($page_data);
		}
		catch(Exception $e)
		{
			$page_data = array();
			$this->applog->Write("Error: ".$e->getMessage()." App_id = $application_id");
		}

		return $page_data;
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
	 * Returns if there's application data in OLP for $app_id.
	 * @param int $app_id
	 * @return boolean
	 */
	public function appExistsInOlp($app_id)
	{
		$return = TRUE;
		if (is_numeric($app_id))
		{
			//Inner join a bunch of the data to make sure
			//we have the right data.
			$query = "SELECT
				count(*) as cnt
			FROM
				application
			INNER JOIN personal_encrypted USING (application_id)
			INNER JOIN bank_info_encrypted USING (application_id)
			INNER JOIN residence USING (application_id)
			INNER JOIN loan_note USING (application_id)
			INNER JOIN income USING (application_id)
			WHERE
				application_id = $app_id";
			try 
			{
				$res = $this->sql->Query($this->database, $query);
				if ($row = $this->sql->Fetch_Object_Row($res))
				{
					$return = ($row->cnt > 0);
				}
			}
			catch (Exception $e)
			{
				$this->applog->Write(__CLASS__.'::'.__METHOD__.':Exception: '.$e->getMessage());
				$return = TRUE;
			}
		}
		else
		{
			$this->applog->Write(__CLASS__.'::'.__METHOD__.": Invalid application id ({$app_id})");
		}
		return $return;
	}
}
?>
