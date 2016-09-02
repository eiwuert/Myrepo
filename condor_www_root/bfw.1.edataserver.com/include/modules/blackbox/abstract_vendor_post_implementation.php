<?php

require_once BFW_CODE_DIR . 'OLP_Applog_Singleton.php';
require_once BFW_CODE_DIR . 'pay_date_validation.php';
require_once BFW_CODE_DIR . 'dynamic_post_singleton.class.php';
include_once BFW_CODE_DIR . "server.php";

abstract class Abstract_Vendor_Post_Implementation
{
	/**
	 * Redirect time defined for thank you page.
	 * This is a universal value which shouldn't be overwritten in any implementation (since task #11637).
	 */
	const REDIRECT				= 2;
	private $http_client        = NULL;
	protected $mode             = '';
	protected $property_short   = '';
	protected $lead_data        = NULL;
	protected $params           = Array();
	protected $rpc_params       = Array
		(
			'ALL'     => Array(
				'post_url' => 'http://dump.ds32.tss',
				),
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				),


		);
	protected $static_thankyou = TRUE;
	public $timeout_exceeded = FALSE;
	private $SOAP_types = array('soap_oc','soap','blackbox.one.page');

	public function __construct(&$lead_data, $mode, $property_short)
	{

		if (is_array($lead_data))
		{
			$this->lead_data = &$lead_data;
		}
		else
		{
			$this->lead_data = array();
		}

		$this->mode = $mode;
		$this->property_short = $property_short;
	}

	public function Get_Lead_Data()
	{
		$lead_data = $this->lead_data;

		foreach ($lead_data['data'] as $key => $val)
		{		
			if (!is_array($val) && !is_object($val))
			{
				$lead_data['data'][$key] = htmlentities($val, ENT_COMPAT);
			}
		}
		
		$this->lead_data['references']->addLeadData($lead_data,$property_short);
		$tmp[1] = $this->lead_data['references']->getReference(1);
		$tmp[2] = $this->lead_data['references']->getReference(2);
		$lead_data['data'][ref_01_name_full]=$tmp[1]['full_name'];
		$lead_data['data'][ref_01_phone_home]=$tmp[1]['phone_number'];
		$lead_data['data'][ref_01_relationship]=$tmp[1]['relationship'];
		$lead_data['data'][ref_02_name_full]=$tmp[2]['full_name'];
		$lead_data['data'][ref_02_phone_home]=$tmp[2]['phone_number'];
		$lead_data['data'][ref_02_relationship]=$tmp[2]['relationship'];
		return($lead_data);
	}

	public function __toString()
	{
		return "Vendor Post Implementation [ABSTRACT]";
	}

	private function Set_Post_Time($post_time)
	{
		$this->post_time = $post_time;
	}

	protected function Merge_Params()
	{
		$prop_short = strtolower($this->property_short);

		if (is_array($this->rpc_params['ALL']) && sizeof($this->rpc_params['ALL']))
		{
			foreach ($this->rpc_params['ALL'] as $k => $v)
			{
				$this->params[$k] = $v;
			}
		}

		if (is_array($this->rpc_params[$this->mode]) && sizeof($this->rpc_params[$this->mode]))
		{
			foreach ($this->rpc_params[$this->mode] as $k => $v)
			{
				$this->params[$k] = $v;
			}
		}

		if (is_array($this->rpc_params[$prop_short]['ALL']) && sizeof($this->rpc_params[$prop_short]['ALL']))
		{
			foreach ($this->rpc_params[$prop_short]['ALL'] as $k => $v)
			{
				$this->params[$k] = $v;
			}
		}

		if (is_array($this->rpc_params[$prop_short][$this->mode]) && sizeof($this->rpc_params[$prop_short][$this->mode]))
		{
			foreach ($this->rpc_params[$prop_short][$this->mode] as $k => $v)
			{
				$this->params[$k] = $v;
			}
		}

	}

	public function Get_Http_Client()
	{
		if (!$this->http_client)
			$this->http_client = new Http_Client();
		else
			$this->http_client->Reset_State();

		return $this->http_client;
	}


	/**
	 * @desc HTTP Post Processing
	 *	Post to Impl post_url with Fields
	 */
	public function HTTP_Post_Process($fields, $qualify = FALSE)
	{
		
		
		
		$http_client = $this->Get_Http_Client();

		//Set the headers if we have any
		if(isset($this->params['headers']))
		{
			$http_client->Set_Headers($this->params['headers']);
		}

		// vendors may want to use a different url to
		// handle the verifify xml post
		$post_url = $qualify && isset($this->params['qualify_post_url'])
					?
				$this->params['qualify_post_url']
					:
				$this->params['post_url'];
				
				
		//Mantis #11881 dynamically change the posting URL of Blackbox vendors [TF]
		$tmp_dyn=Dynamic_Post_Singleton::Get_Instance($this->sql);
		$tmp_post=$tmp_dyn->getDynamicPostUrl($this->property_short);
		if(strlen($tmp_post) > 4){
			$post_url=$tmp_post;
		}
		

		if ($post_url)
		{
			$post_or_get = $this->Get_Post_Type();
			if ($post_or_get == Http_Client::HTTP_GET)
			{
				$data_received = $http_client->Http_Get($post_url, $fields);
			}
			else // Must be Http_Client::HTTP_POST
			{
				$data_received = $http_client->Http_Post($post_url, $fields);
			}
		}

		$cookies = $http_client->Get_Cookies();
		$result = ($qualify) ? $this->Verify_Result($data_received, $cookies)
							 : $this->Generate_Result($data_received, $cookies);

		// If we have both an original winner, and a lender redirected target, we need to do a little extra work [LR]
		// We'll post the data sent, and data received for both targets
		if (is_array($result))
		{
			foreach ($result as $r)
			{
				$r->Set_Data_Sent(serialize($fields));
				$r->Set_Data_Received($data_received);
			}
		}
		else
		{
			$result->Set_Data_Sent(serialize($fields));
			$result->Set_Data_Received($data_received);
		}
		if (!$this->params['post_url'])
		{
			$result->Set_Message("No post_url found in params");
            if ($this->mode == 'LOCAL' || $this->mode == 'RC')
            {
                $result->Set_Message("Accepted");
                $result->Set_Data_Received("No post_url for Local/RC.  Forcing Accept");
                $result->Set_Thank_You_Content(self::Generic_Thank_You_Page(""));
                $result->Set_Success(TRUE);
            }
		}
		if ($http_client->timeout_exceeded)
		{
			$this->timeout_exceeded = TRUE;
		}

		return $result;

	}

	/**
	 * @desc Posts leads to Vendors.
	 *	Relies on PHP's runtime polymorphism to dispatch control to
	 *	Generate_Fields() and Generate_Result() in the subclasses.
	 */
	public function Post()
	{
		$this->Merge_Params();
		$lead_data = $this->Get_Lead_Data();
		$this->movePayDate($lead_data); // Mantis #8769 [DY]
		$fields = $this->Generate_Fields($lead_data, $this->params);
		$result = $this->HTTP_Post_Process($fields,FALSE);
		return $result;
	}



	/**
	 * @desc Verify Vendor Posting
	 *	Prequalify Posting to Vedor without sending complete data
	 *  Vendor Must accept XML Postings
	 */
	public function Verify($verify_post_type='XML')
	{
		$this->Merge_Params();
		$lead_data = $this->Get_Lead_Data();
		$fields = $this->Generate_Qualify_Fields($lead_data);

		switch ($verify_post_type)
		{

		case 'HTTP':
			$verify_data = array(
				'id' => $this->params['id'],
				'ssn' => $fields['SSN'],
				'email' => $fields['EMAIL'],
			);
				break;

		case 'XML':
		default:
			$verify_data =
				"<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
				<data>
				<id>{$this->params['id']}</id>
				<ssn>{$fields['SSN']}</ssn>
				<email>{$fields['EMAIL']}</email>
				</data>";
				break;

		}
		$result = $this->HTTP_Post_Process($verify_data,TRUE);
		return $result;
	}

	/**
	 * @desc Checks to see if Vendor will accept Post.
	 *	Will post to vendor ID, SSN, and Email.
	 *	Vendor will reply with XML <accept>TRUE/FALSE</accept>
	 */
	public function Verify_Result($data_received, $cookies)
	{
		$result = new Vendor_Post_Result();

		if (!strlen($data_received))
		{
			$result->Empty_Response();
		}
		elseif (preg_match("/<accept>true<\/accept>/", strtolower(str_replace(" ", "", $data_received))))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
		}

		return $result;
	}


	abstract public function Generate_Fields(&$lead_data, &$params);
	abstract public function Generate_Result(&$data_received, &$cookies);

	public static function Get_Post_Type()
	{
		return Http_Client::HTTP_POST;
	}

	/**
	 * Generate content of a "Thank You" page.
	 *
	 * According to task #11637, all venders should use the universal redirect time defined in this class.
	 * So the second parameter ($redirect) is no longer used.
	 *
	 * @param string $url redirect URL.
	 * @param int $redirect redirect time (in seconds). THIS PARAMETER IS NO LONGER USED.
	 * @return string content of the result page.
	 */
	public static function Generic_Thank_You_Page($url, $redirect=self::REDIRECT)
	{
		$redirect=self::REDIRECT; // use the universal redirect time value
		$url = (isset($_SESSION['data']['redirect_link'])) ? $_SESSION['data']['redirect_link'] : $url;

		// If the URL has substance, redirect them to the page
		if(strlen($url) > 0)
		{
			$application_id = $_SESSION['application_id'];

			if (!$_SESSION['redirect_logged'] == TRUE) {
                if(file_exists(BFW_CODE_DIR . 'event_log.singleton.class.php'))
                {
                    require_once( BFW_CODE_DIR . 'event_log.singleton.class.php' );
                }
				if (! class_exists('Event_Log_Singleton',false))
				{
					trigger_error("Unable to load class: Event_Log_Singleton.  Session is {$_SESSION['data']['unique_id']}", E_USER_WARNING);
					die("class_exists failed");
			    }
				// Grab the lender specified winning target, if there is one [LR]
		    	$event = Event_Log_Singleton::Get_Instance(BFW_MODE, $application_id);
				$event->Log_Event('REDIRECT_PAGE', 'TRUE',($_SESSION['blackbox']['new_winner']) ? $_SESSION['blackbox']['new_winner'] : $_SESSION['blackbox']['winner']);
				$_SESSION['redirect_logged'] = TRUE;
			}

			// Redirect to usfastcash.com always, include the unique_id (session_id)
			if(strtolower(BFW_MODE) == "local")
			{
				if($_SESSION['config']->site_type == 'soap_oc')
				{
					$temp_redirect_page = 'http://pcl.3.easycashcrew.com.' . BFW_LOCAL_NAME . '.tss/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
				}
				else
				{
					$temp_redirect_page = 'http://pcl.3.easycashcrew.com.' . BFW_LOCAL_NAME . '.tss/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"] . '&redirect_link='.urlencode($url);
				}
			}
			elseif(strtolower(BFW_MODE) == "rc")
			{
				if($_SESSION['config']->site_type == 'soap_oc')
				{
					$temp_redirect_page = 'http://rc.easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
				}
				else
				{
					$temp_redirect_page = 'http://rc.easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"] . '&redirect_link='.urlencode($url);
				}
			}
			else
			{
				//Switch on site type
				if($_SESSION['config']->site_type == 'soap_oc')
				{
					$temp_redirect_page = 'https://easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"];
				}
				else
				{
					$temp_redirect_page = 'https://easycashcrew.com/?page=int_redirect&unique_id=' . $_SESSION["data"]["unique_id"] . '&redirect_link='.urlencode($url);
				}
			}

			// set the other variables
			$_SESSION["data"]["redirected_to"] = $url;
			$_SESSION["data"]["redirect_start_time"] = time();
			
			
			if($_SESSION['config']->site_language == 'spa')
			{
				$page = <<<PAGE
<br/>
<p>Gracias por su aplicaci&oacute;n. Usted ha sido pre-aprobado con uno de nuestros  compa&ntilda;eros prestamistas. Por favor haga click <b><a href="$temp_redirect_page">aqu&iacute;</a></b> para completar su aplicaci&oacute;n.</p>

<p>Usted va a ser automáticamente enviado a la página en unos segundos.</p>

<script type="text/javascript">
var script_expression = "document.location.href = '$temp_redirect_page'";
var msecs = $redirect * 1000;
setTimeout(script_expression, msecs);
</script>
PAGE;
			}
			else
			{
			$page = <<<PAGE
<br/>
<p>Thank you for your application. You have been pre-approved with one of
our lending partners. Please click <b><a href="$temp_redirect_page">here</a></b>
to complete your application.</p>

<p>You will be automatically sent to the page in a few seconds.</p>

<script type="text/javascript">
var script_expression = "document.location.href = '$temp_redirect_page'";
var msecs = $redirect * 1000;
setTimeout(script_expression, msecs);
</script>

PAGE;
			}
		}
		else
		{
			// The URL was not defined, what went wrong?
			$page = <<<PAGE
<br/>
<p>Thank you for your application. You have been pre-approved with one of
our lending partners. Please click <b>here</b>
to complete your application.</p>

<p>You will be automatically sent to the page in a few seconds.</p>

PAGE;

			// Define data for email alerts
			$data = array(
				'site_name' => 'sellingsource.com',
				'sender_name' => 'Redirect URL <no-reply@sellingsource.com>',
				'subject' => "Redirect URL Undefined",
				'property' => 'Undefined Property',
				'session'  => 'Undefined Session'
			);

			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);

			if(isset($_SESSION['blackbox']['winner']))
			{
				// Say what target is affected, rather than generic message.
				$data['property'] = $_SESSION['blackbox']['winner'];
				$data['session'] =  session_id();

				// Write applog entry with the target
				$applog->Write("Redirect URL was empty for '{$_SESSION['blackbox']['winner']}' session:" . session_id());
			}
			else
			{
				// Write generic applog error message
				$applog->Write("Redirect URL was empty for an undefined property.");
			}


			if(strtolower(BFW_MODE) != "live")
			{
				$recipients = array(
						array(	'email_primary_name' => 'Josef Norgan ' . BFW_MODE,
							'email_primary' => 'josef.norgan@sellingsource.com')
				);
			}
			else
			{
				$recipients = array(
					array(	'email_primary_name' => 'Brian Feaver',
							'email_primary' => 'brian.feaver@sellingsource.com'),
					array(	'email_primary_name' => 'Mike Genatempo',
							'email_primary' => 'mike.genatempo@sellingsource.com')
				);
			}

			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);

			$mail_failed = false;
            $last_data = null;
            $data["application_id"] = self::getApplicationId();
			foreach($recipients as $recipient)
			{
				$send_data = array_merge($recipient, $data);
				try
				{
					//$result = $tx->sendMessage('live','REDIRECT_URL_2',
					//	$send_data['email_primary'],'',$send_data);

				}
				catch (Exception $e)
				{
					$mail_failed = true;
					$last_data = $send_data;
				}
            }
		}
		return $page;
	}

	public function Static_Thankyou_Content()
	{

		return($this->static_thankyou);

	}

	/*
		Fields to be sent to Pre Qualify Vendor Post
	*/
	public static function Generate_Qualify_Fields($lead_data)
	{
		$fields = array (
			'ID' => $params['id'],
			'EMAIL' => $lead_data['data']['email_primary'],
			'SSN' => $lead_data['data']['social_security_number'],
		);

		return $fields;
	}

	/**
	 * Move Date
	 *
	 * Moves a date if it's
	 * @param string Date 2006-01-01
	 * @param boolean Move date if on weekend
	 * @return string 2006-01-01
	 */
	protected function Move_Date($date, $weekends = TRUE)
	{
		//Convert date to timestamp
		$d = strtotime($date);

		$holiday_check = TRUE;

		if(!is_array($_SESSION['holiday_array']))
		{
			if($weekends == FALSE) return $date;
			$holiday_check = FALSE;
			$_SESSION['holiday_array'] = array();
		}

		//Create pay date validation so we check for holidays
		$v = new Pay_Date_Validation(array(),$_SESSION['holiday_array']);

		//Cycle through em
		while(($holiday_check && $v->_Is_Holiday($d)) || ($weekends && $v->_Is_Weekend($d)))
		{
			$d += 86400;
		}

		return date("Y-m-d", $d);
	}

	/**
	 * If the next paydate is within N days of the current date, then we append the data for
	 * the purposes of submitting to this BB target only; and submit the lead so the '2nd
	 * paydate' becomes the 'next payday' and we calculate a new '2nd paydate' based on the
	 * information we have about the paydate cycle. [DY]
	 * 
	 * Moved the query for paydate_minimum into this check. [BF]
	 *
	 * @param array &$lead_data the data we're sending to the the target
	 * @link http://bugs.edataserver.com/view.php?id=8769 48-Modify Loan Due Date, Next Paydate within 4 days (On/Off)
	 * @return void
	 */
	protected function movePayDate(&$lead_data)
	{
		
		$paydates = & $lead_data['data']['paydates'];
		
		try
		{
			$db = Setup_DB::Get_Instance('blackbox', $this->mode);
		
			$query = sprintf(
				"	SELECT
						paydate_minimum
					FROM
						target t
						INNER JOIN rules r
							ON t.target_id = r.target_id
					WHERE
						t.property_short = '%s'
						AND r.`status` = 'active'
				",
				mysql_real_escape_string($this->property_short)
			);
			
			$result = $db->Query($db->db_info['db'], $query);
			
			if (($row = $db->Fetch_Object_Row($result)))
			{
				$paydate_minimum = (int)$row->paydate_minimum;
			}
		}
		catch (Exception $e)
		{
			// If we throw an exception, just move on as if the value was 0
			$paydate_minimum = 0;
		}

		if (($paydate_minimum > 0) && $paydates && is_array($paydates))
		{
			$d = strtotime(reset($paydates)); 	// get first paydate.

			if ((time() + ($paydate_minimum*86400)) >= $d)
			{
				array_shift($paydates); 		// kick out first paydate.
				$lead_data['data']['paydate_model']['next_pay_date'] = reset($paydates);
			}
		}
	}

	/**
	 * Is SOAP Type
	 *
	 * Checks if submitting site is a SOAP site by checking its site_type
	 * @return boolean True If SOAP Site
	 */
	protected function Is_SOAP_Type()
	{
		if(in_array($_SESSION['config']->site_type,$this->SOAP_types,TRUE))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	protected function getApplicationId()
	{
        $app_id = null;

        if(!empty($_SESSION['application_id']))
        {
            $app_id = $_SESSION['application_id'];
        }
        elseif(!empty($_SESSION['cs']['application_id']))
        {
            $app_id = $_SESSION['cs']['application_id'];
        }
        elseif(!empty($_SESSION['data']['application_id']))
        {
            $app_id = $_SESSION['data']['application_id'];
        }
        elseif(!empty($_SESSION['transaction_id']))
        {
            $app_id = $_SESSION['transaction_id'];
        }
        elseif(!empty($this->lead_data['application_id']))
        {
        	$app_id = $this->lead_data['application_id'];
        }

        // GForge #4760 - Sometimes app_id is base64 encoded. If so, correct it. [RM]
        if(!is_numeric($app_id))
        {
            $app_id = base64_decode($app_id);

            if (!is_numeric($app_id))
            {
                $app_id = NULL;
            }
        }

        return $app_id;
    }

}


