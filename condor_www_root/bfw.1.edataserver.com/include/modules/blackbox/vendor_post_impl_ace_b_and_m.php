<?php

	require_once(BFW_CODE_DIR . 'condor_display.class.php');
	require_once('prpc/client.php');
	
/**
 * This is ACE brick and mortor campaign.
 *
 * @author August Malson <august.malson@sellingsource.com>
 * @see    GForge [#9999]
 */
class Vendor_Post_Impl_ACE_B_AND_M extends Abstract_Vendor_Post_Implementation
{

	/**
	 * @var array
	 */
	protected $rpc_params = array(
		'ALL' => array(
		),
		'ace' => array(
			'ALL' => array(
//			'post_url' => 'http://www.blah.com', 
			'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/POL',
			//********************************************* 
			// if you don't have a post url defined then
			// you don't have it causes things to appear 
			// in the app log - doesn't happen on live
			// so you should always have a post_url
			//********************************************* 
			),
		),
	);

	/** 
	 * @var int
	 */
	const REDIRECT = 4;

	/**
	 * @var boolean
	 */
	protected $static_thankyou = FALSE;

	/**
	 * @var array
	 */
	private $store_array;

	/**
	 * @var array
	 **/
	 private $data_array;


	/**
	 * Generate field values for post request.
	 *
	 * @param array &$lead_data User input data.
	 * @param array &$params Values from $this->rpc_params.
	 * @return array Field values for post request.
	 */
	public function Generate_Fields(&$lead_data, &$params)
	{
		$this->data_array = $lead_data['data'];
		$this->searchForStore($lead_data['data']['home_zip']);	
		return '';
	}

	/**
	 * Generate result based on data received from the HTTP request.
	 * 
	 * @param string &$data_received Data received from the HTTP request.
	 * @param array &$cookies Cookies received from the HTTP request.
	 * @return Vendor_Post_Result Result generated.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		$good_lead = FALSE;
		

		if (is_array($this->store_array) && $this->store_array['address1'] !== '')
		{
			$result->Set_Next_Page( 'bb_vs_thanks' );
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
			if (!$this->Is_SOAP_Type())// added for Mantis #11073 [AuMa]
			{
				$result->Set_Next_Page( 'bb_vs_thanks' );	
			}
		}
		else 
		{
			$result->Set_Message('Rejected');
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason("Zip Code not found in database");
		}
		return $result;
	}

	/**
	 * Generate thank you content.
	 *
	 * @param string &$data_received Input data.
	 * @return string Thank You content.
	 */
	public function Thank_You_Content(&$data_received = NULL)
	{
	
		$this->sendFax();
		if (is_array($this->store_array))
		{
			$location_url = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=" 
				. urlencode($this->cleanUpMapAddress($this->store_array['address1'])) . '+'
	//			. urlencode($this->store_array['address2']) . '+'
				. urlencode($this->store_array['city']) . '+'
				. urlencode($this->store_array['state']) . '+'
				. urlencode($this->store_array['zip_code']) . '+'
				. '&ie=UTF8&iwloc=addr';
		}
		else 
		{
			$location_url = '#';
		}
		$content = <<<END
<style>
.legal
{
	font-size:small;
}

.tagline
{
	text-align: center;
}

.congrats
{
	font-size:125%;
	width: 65%; 
	text-align:center;
}

#thankyou
{
	width: 95%;
	text-align:left; 
	font-size: 10pt;
	margin-left:15px;
/*	margin-right:15px; */
}
</style>
<div id="thankyou" >
<div align='center'>
<div class='congrats'  ><strong>
<span style='font-size:125%'>Congratulations,</span><br />
your application has been pre-approved for a payday loan at your
nearby ACE Cash Express location!
</strong></div>
</div>
<br /><br />
Although we cannot provide you a loan over the Internet at this time, you can 
still get your <strong>CASH</strong> today, by visiting this ACE Cash Express 
location:<br /><br />
<div align="center"><em><a href='{$location_url}' target='_blank'>Click here for the closest ACE Cash Express location</a></em></div><br /><br />
Please bring the following documents to complete a loan transaction.<br /><br />
Required documents
<ul>
<li>Pre-printed check from your current bank account.</li>
<li>Most recent checking account statement</li>
<li>Valid goverment-issued picture I.D.</li>
<li>Recent payroll check stub or government benefits statement</li>
<li>Current phone and utility bill</li>
</ul>
In just minutes, you will be on your way with <strong>CASH!</strong> It's that easy.
<br /><br />
For more information about ACE Cash Express, visit the following website: 
<a href="http://www.acecashexpress.com">www.acecashexpress.com</a><br /><br />
<div class='legal'>Legal:<br />
All loans subject to the approval of, and made by an unaffiliated third-party lender. Certain limitations apply.
Offer is not valid for customers who have an outstanding loan with any unit of ACE Credit Services, LLC. 
ACE reserves the right to terminate this offer at any time. The maximum amount of loan offered varies by state.
<strong>&copy;ACE Credit Services, LLC</strong><br /><br />
<div align='center'><img src="@@SHARED_IMAGE@@/ace/ace_logo_sml.jpg"></div><br /><br />
<div class='tagline'><strong>ACE &mdash; Earning Your Trust Since 1968!</strong></div> <br />
</div>
END;
//' // just to correct the annoying "'" mark

		$_SESSION['bb_vs_thanks'] = $content;
		
		if (!$this->Is_SOAP_Type())
		{
			return $content;
		}
		else
		{
			switch (BFW_MODE)
			{
				case 'LIVE':
					$url = 'https://easycashcrew.com';
					break;
				case 'RC':
					$url = 'http://rc.easycashcrew.com';
					break;
				case 'LOCAL':
					$url = 'http://pcl.3.easycashcrew.com.ds70.tss';
			}
		}		
			return parent::Generic_Thank_You_Page($url . '/?page=bb_vs_thanks');
		
	}

	/**
	 * A PHP magic function.
	 *
	 * @see http://www.php.net/manual/en/language.oop5.magic.php Magic Methods
	 * @return string a string describing this class.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [ACE_B_AND_M]";
	}

	/**
	 * This doesn't do anything just yet, but the intention
	 * is that we clean up the map street address field, because
	 * ACE put some bad data in.   Well sad fact is, that they 
	 * will just have to do it if they want their site to show
	 * on the map.
	 *
	 * @param $data  
	 *
	 * @return string 
	 */
	private function cleanUpMapAddress($data)
	{
		if (preg_match('/^#(.*)/', $data, $m))
		{
			return $m[1];
		}
		return $data;
	}

	/**
	 * This function hits the database and searches for the
	 * closest store in the same zip code.  We only have
	 * 1 store per zip - based on the current restrictions
	 * we actually store the data in $this->store_array
	 *
	 * @param $home_zip 
	 *
	 * @return none
	 */
	private function searchForStore($home_zip)
	{
		try
		{
			$db = Setup_DB::Get_Instance('blackbox', $this->mode);
		
			$query = sprintf(
				"	SELECT
						zip_code,
						store_id,
						dm_name,
						region_name,
						address1,
						address2,
						city,
						state,
						phone1,
						fax
					FROM
						ace_stores a
					WHERE
						a.zip_code = %s
				",
				mysql_real_escape_string($home_zip)
			);
			
			$result = $db->Query($db->db_info['db'], $query);
			
			if (($row = $db->Fetch_Array_Row($result)))
			{
				$this->store_array = $row;
			}
		}
		catch (Exception $e)
		{
			// If we throw an exception, just move on as if the value was 0
		}
	}

	/**
	 * This function insert a new condor document for the application
	 * 
	 * @param number $archive_id 
	 * @return none
	 */
	private function insertCondorDocument($archive_id)
	{
		$return_value = FALSE;
		
		$application_id = $_SESSION['application_id'];
		try
		{
			$db = Setup_DB::Get_Instance('blackbox', $this->mode);
		
			$query = sprintf(
				"	INSERT into application_documents
					(
						application_id,
						document_id
					) 
					VALUES
					(%s,%s)
				",
				mysql_real_escape_string($application_id),
				mysql_real_escape_string($archive_id)
			);
			
			$result = $db->Query($db->db_info['db'], $query);
			
			if (($row = $db->Fetch_Array_Row($result)))
			{
						
				OLP_Applog_Singleton::quickWrite("Fax document tried to resend: Application_id:" . $row['application_id']  );
				$return_value = TRUE; 
			}
		}
		catch (Exception $e)
		{
			OLP_Applog_Singleton::quickWrite("Error occurred in checkIfDocumentExists: \n" . str_repeat("=",70). "\n" .  print_r($e, TRUE) . " \n" . str_repeat("=",70). "\n" );
			// If we throw an exception, just move on as if the value was 0
		}
		
		return $return_value;
	}

	/**
	 * This function will search for an existing document id 
	 * 
	 * no parameters
	 * @return $return_value boolean
	 */
	private function checkIfDocumentExists()
	{
		$return_value = FALSE;
		
		$application_id = $_SESSION['application_id'];
		try
		{
			$db = Setup_DB::Get_Instance('blackbox', $this->mode);
		
			$query = sprintf(
				"	SELECT
						application_id,
						document_id
					FROM
						application_documents a
					WHERE
						a.application_id = %s
				",
				mysql_real_escape_string($application_id)
			);
			
			$result = $db->Query($db->db_info['db'], $query);
			
			if (($row = $db->Fetch_Array_Row($result)))
			{
						
				OLP_Applog_Singleton::quickWrite("Fax document tried to resend: Application_id:" . $row['application_id']  );
				$return_value = TRUE; 
			}
		}
		catch (Exception $e)
		{
			OLP_Applog_Singleton::quickWrite("Error occurred in checkIfDocumentExists: \n" . str_repeat("=",70). "\n" .  print_r($e, TRUE) . " \n" . str_repeat("=",70). "\n" );
			// If we throw an exception, just move on as if the value was 0
		}
		
		return $return_value;
	}


	/**
	 * This function will send the fax to the store
	 * 
	 * no paramters
	 * @return no return value
	 **/
	private function sendFax()
	{
		//require_once(OLP_DIR . 'app_campaign_manager.php');
		if (!$this->checkIfDocumentExists())
		{
			$application_id = $_SESSION['application_id'];
			$property_short = 'ACE';

			$token_data = array();
			
			$lead_data = $this->data_array;

			$token_data = array
			(
			'NAME_FIRST' => $lead_data['name_first'],
			'NAME_LAST' => $lead_data['name_last'],
			'ADDRESS' => $lead_data['home_street'],
			'CITY' => $lead_data['home_city'],
			'STATE' => $lead_data['home_state'],
			'ZIP' => $lead_data['home_zip'],
			'PHONE_HOME' => $lead_data['phone_home'],
			'PHONE_WORK' => $lead_data['phone_work']
			);
		
			$token_data['DATE'] = date('m/d/Y');
			$token_data['TIME'] = date('H:i');


			$message = '';
			try
			{
				if (BFW_MODE != 'LIVE')
				{
					$prpc_server = Server::Get_Server('RC', 'CONDOR', $property_short);
				}
				else
				{
					$prpc_server = Server::Get_Server(BFW_MODE, 'CONDOR', $property_short);
				}
	
				$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");
				
				//Save the doc in Condor
				$condor_data = $condor_api->Create(
					'Internet Lead',
					$token_data,
					TRUE,
					$application_id,
					$_SESSION['statpro']['track_key'],
					$_SESSION['statpro']['space_key']
				);
				
				$archive_id = $condor_data['archive_id'];
				//********************************************* 
				// create instance of the app campaign manager so we
				// can log the archive id/documents in the db
				//********************************************* 
				// also if the record exists for this application then
				// don't send out another fax.
				//********************************************* 
				/*
				$acm = new App_Campaign_Manager($_SESSION['config']->sql, $_SESSION['config']->database, $_SESSION['config']->applog);
				$acm->Document_Event($application_id, $archive_id);
				// the above did not work for some reason - I don't know the correct sql, database, applog info to use it
				// so I created insertCondorDocument instead - it seems to work for now.
				*/
				$this->insertCondorDocument($archive_id);

				unset($condor_data['document']);
				$_SESSION['condor_data'] = $condor_data;
				
				$hq_email = 'ikovach@acecashexpress.com';

				//Try and fax it
				if (BFW_MODE !== 'LIVE' || strcasecmp($lead_data['name_first'], 'TSSTEST') == 0)
				{
					$fax_result = $condor_api->Send($archive_id, array('fax_number' => '702-492-9871'), 'FAX');//$this->store_array['fax']), 'FAX');
					OLP_Applog_Singleton::quickWrite("BFW Mode != LIVE: sending fax to our fax point:we would have sent to {$this->store_array['fax']} {$application_id}\n{$message}");
					$email_result = $condor_api->Send($archive_id, array('email_primary' => 'sean.mclean@partnerweekly.com'), 'EMAIL');//$this->store_array['fax']), 'FAX');
					$email_result = $condor_api->Send($archive_id, array('email_primary' => 'pennied@partnerweekly.com'), 'EMAIL');//$this->store_array['fax']), 'FAX');
					OLP_Applog_Singleton::quickWrite("BFW Mode != LIVE: sending email to our qa person:we would have sent to {$hq_email} {$application_id}\n{$message}");
				}
				else
				{
					$fax_result = $condor_api->Send($archive_id, array('fax_number' => $this->store_array['fax']), 'FAX');
					$email_result = $condor_api->Send($archive_id, array('email_primary' => $hq_email), 'EMAIL');
				}
				
			}
			catch (Exception $e)
			{
				$fax_result = FALSE;
				$email_result = FALSE;
				$message = $e->getMessage();
			}
			
			if ($fax_result === FALSE)
			{
				OLP_Applog_Singleton::quickWrite("Condor failed to send FAX documents for ACE Brick and Mortor Campaign, app_id={$application_id}\n{$message}");
			}
			if ($email_result === FALSE)
			{
				OLP_Applog_Singleton::quickWrite("Condor failed to send EMAIL documents for ACE Brick and Mortor Campaign, app_id={$application_id}\n{$message}");
			}
		}
	}

}
?>
