<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

//require_once('config.php');
//require_once("qualify.1.php");
require_once("prpc/client.php");
require_once("config.4.php");
require_once(SERVER_CODE_DIR . "loan_data.class.php");
require_once(SQL_LIB_DIR . "/fetch_campaign_info.func.php");
require_once(SQL_LIB_DIR . "/application.func.php");
require_once(SQL_LIB_DIR . "/react.func.php");
require_once(ECASH_COMMON_DIR."/ecash_api/qualify.2.ecash.php");
require_once(SERVER_CODE_DIR . "vehicle_data.class.php");
require_once(COMMON_LIB_DIR.'pay_date_calc.3.php');

class eCash_Document_DeliveryAPI_Condor_Receive_Exception extends Exception {
}

class eCash_Document_DeliveryAPI_Condor {

	static private $prpc;

	static public $deny_view_tokens = array("CustomerSSNPart1","CustomerSSNPart2");

	static private $template_names = array();

	static public function Prpc()
	{
		try
		{
			if (!(self::$prpc instanceof Prpc_Client))
			{
				$condor_server = eCash_Config::getInstance()->CONDOR_SERVER;
				self::$prpc = new Prpc_Client($condor_server);
			}

			return self::$prpc;

		}
		catch (Exception $e)
		{
			if (preg_match("//",$e->getMessage()))
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: " . $condor_server . " is not a valid PRPC resource.");
			}

			throw $e;

		}

	}

	static public function Receive(Server $server, $document_list, $request)
	{
		try
		{
			$orequest = $request;

			if(!is_numeric($request->archive_id))
			{
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Archive ID must be numeric");
			}

			if(!isset($request->document_list))
			{
				$_SESSION['current_app']->archive_id = '';
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("No document is selected");
			}

//			if (isset($request->document_list) && count($request->document_list) > 1) {
//				$_SESSION['current_app']->archive_id = '';
//				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Select 1 document at a time");
//			}

			$_SESSION['current_app']->archive_id = $request->archive_id;

			if(!self::Validate_Tiff($request->archive_id))
			{
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Document not found");
			}

//			if(self::Check_ArchiveIDs($server, $request->archive_id)) {
//				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Archive ID already used");
//			}

			eCash_Document::$message = "<font color=\"green\"><b>Document found and updated</b></font>";

			foreach($document_list as $document)
			{

				$otherdoc = "docname_" . strtolower($document->name);
				if (preg_match("/^other/", strtolower($document->name)) && strlen($request->$otherdoc) < 1)
				{
					throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Enter another name");
				}
				elseif (preg_match("/^other/", strtolower($document->name)) )
				{
					$request->destination['name'] = $request->$otherdoc;
				}

				$request->signature_status = ($request->signature_status) ? $request->signature_status : 'unsigned';

				$request->method = ( isset($request->method) && !empty($request->method) ? $request->method : "fax");
				$request->document_event_type = "received";
				$result = array();
				foreach($request as $key => $value)
				{
					$result[$key] = $value;
				}
				eCash_Document::Log_Document($server, $document,  $result);

				self::Set_Application_ID($request->archive_id, $request->application_id);


			} // end foreach

			$_SESSION['current_app']->archive_id = '';
		}

		catch (eCash_Document_DeliveryAPI_Condor_Receive_Exception $e)
		{
				eCash_Document::Log()->write($e->getMessage(), LOG_ERROR);

				eCash_Document::$message = "<font color=\"red\"><b>" . $e->getMessage() . "</b></font>";
				return false;
		}

		return true;
	}

	static public function Preview(Server $server, $data, $document_list)
	{
		$send_arr = self::Map_Data($server, $data);
		$result = array();

		foreach($document_list as $doc)
		{

			try
			{
				if(!count(self::$template_names))
				{
					self::$template_names = self::Prpc()->Get_Template_Names();
				}

				if(in_array($doc->description,self::$template_names))
				{

					$tokens = self::Prpc()->Get_Template_Tokens($doc->description);
					if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens))
					{
						throw new Exception();
					}
					$acl = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);

					if(	count(@array_intersect(array('ssn_last_four_digits'),$acl)) &&
						count(array_intersect($tokens,self::$deny_view_tokens))) {
							echo "Document Blocked";
							die;
					}

				}

			}
			catch (Exception $e)
			{
				if ($e->getMessage())
				{
					throw $e;
				}
			}

//	 		$doc_id = self::Prpc()->Create(($doc->part_name) ? $doc->part_name : $doc->body_name, $send_arr, false, $data->application_id, $data->track_id, null);
			$doc_id = self::Prpc()->Create($doc->description, $send_arr, false, $data->application_id, $data->track_id, null);

			self::Set_Display_Headers($doc_id);

			echo $doc_id->data;

			die;
		}

		return $result;


	}

	static public function Send(Server $server, $data, $document_list, $send_type = "email", $destination_override = NULL)
	{
		//load the transport data if the transport exists.
		if (is_object(ECash::getTransport()))
		{
			$trans_obj = ECash::getTransport()->get_Data();
		}

		if ( isset($trans_obj->SenderName) )
		{
			$data->SenderName = $trans_obj->SenderName;
		}

		$send_arr = self::Map_Data($server, $data);
		$result = array();

		switch(true)
		{
			/**
			 * TODO: This case routes all faxes from non LIVE environments to
			 * the DOCUMENT_TEST_FAX number. This is here because Parallel was
			 * sending faxes to customers. Agents are entering fax numbers that
			 * are not associated with the apps, so there's no way to identify
			 * a 'test' number. This should be replaced by something
			 * that allows for easier RC and Local testing.
			 */
			case (strtolower($send_type) == "fax" && EXECUTION_MODE != 'LIVE') :
				$destination_override = eCash_Config::getInstance()->DOCUMENT_TEST_FAX;
				$recp['fax_number'] = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			case (strtolower($send_type) == "fax" && $destination_override != NULL) :
				$recp['fax_number'] = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			case strtolower($send_type) == "fax" :
				$destination['destination'] = $send_arr['CustomerFax'];
				break;

			// RC environment check.. ugly hack
			case EXECUTION_MODE != 'LIVE' && $destination_override != NULL && $send_arr['CustomerEmail'] == $destination_override && stripos($send_arr['CustomerEmail'],"sellingsource") === FALSE :
				$destination_override = eCash_Config::getInstance()->DOCUMENT_TEST_EMAIL; //'ecash3drive@gmail.com';

			case $destination_override != NULL :
				$recp['email_primary'] = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			// RC environment check.. ugly hack
			case EXECUTION_MODE != 'LIVE' &&  stripos($send_arr['CustomerEmail'],"sellingsource") === FALSE:
				$recp['email_primary'] = eCash_Config::getInstance()->DOCUMENT_TEST_EMAIL; //'ecash3drive@gmail.com';
				$destination['destination'] = eCash_Config::getInstance()->DOCUMENT_TEST_EMAIL; //'ecash3drive@gmail.com';
				break;

			default:
				$recp['email_primary'] = $send_arr['CustomerEmail'];
				$destination['destination'] = $send_arr['CustomerEmail'];
		}
		$recp['email_primary_name'] = $send_arr['CustomerNameFull'];

		// Log document as GenericSubject if set -- currently used for email responses
		if (isset($trans_obj->GenericSubject) )
		{
			$destination['name'] = $trans_obj->GenericSubject;
		}
		else
		{
			$destination['name'] = $send_arr['CustomerNameFull'];
		}

		$single_send = false; // If all fax documents use the same cover sheet, bunch them all up into one fax
		if (strtolower($send_type) == 'fax' && count($document_list) > 1)
		{
			$single_send = true;
			$send_result = "";
			$res_check = array();
			$doc_part = array();
			for ($i = 1 ; $i < count($document_list); $i++)
			{
				if ($document_list[$i]->fax_body_name != $document_list[($i-1)]->fax_body_name)
				{
					$single_send = false;
					break;
				}
			}
		}

		$j = 0;
		foreach($document_list as $doc)
		{
			//hack
			$final_iteration = false;
			if($single_send == false)
			{
				$send_result = "";
				$res_check = array();
				$doc_part = array();
			}
			elseif ($single_send == true && ++$j == count($document_list))
			{
				$final_iteration = true;
			}

			if(!is_array($doc->bodyparts))
			{
				$doc->bodyparts = array();
			}

			if(count($doc->bodyparts) == 0 && in_array(strtolower($send_type), array("fax","esig")))
			{
				$doc->bodyparts[] = $doc->description;
			}

			if(count($doc->bodyparts) > 0)
			{
				if(!is_object(current($doc->bodyparts)))
				{
					$doc->bodyparts = eCash_Document::singleton($server,$_REQUEST)->Get_Documents($doc->bodyparts);
				}
			}

			$i = 0;
			foreach ($doc->bodyparts as $subpart)
			{
				if (strtolower($send_type) == 'fax' && ++$i == count($doc->bodyparts) && ($single_send == false || $final_iteration == true))
				{
					$body_name = $subpart->description;
					break;

				}
				else
				{
					$doc_res = self::Prpc()->Create($subpart->description, $send_arr, TRUE, $data->application_id, $data->track_id, null);
				}

				if(!isset($doc_res['archive_id']))
				{
					throw new OutOfBoundsException(__METHOD__ . " Error: condor.4::Create did not return a valid result. missing archive id.");
				}

				$doc_part[] = $doc_res['archive_id'];

				$res_check[] = $subpart->document_list_id;
				$result[$subpart->document_list_id] = array("status" => &$send_result,
															"application_id" => $data->application_id,
															"archive_id" => $doc_res['archive_id'],
															"destination" => $destination,
															"method" => (strtolower($send_type) == 'fax') ? 'fax': 'email',
															"document" => (array) $subpart
															);
			}

			switch (strtolower($send_type))
			{
				case "fax":
					$send_arr['template_name'] = $doc->fax_body_name;
//					$body_name = $doc->fax_body_name;
					break;

				case "esig":
					$body_name = $doc->esig_body_name;
					break;

				case "email":
				default:
					$body_name = $doc->email_body_name;
			}

			if ($single_send == false || $final_iteration == true)
			{

				if((is_array($doc_part) && count($doc_part) && strtolower($send_type) != 'esig'))
				{
					$bid = self::Prpc()->Create_As_Attachment($body_name, $doc_part, "application/pdf", $send_arr, TRUE, $data->application_id, $data->track_id, null);
				}
				else
				{
					$bid = self::Prpc()->Create($body_name, $send_arr, TRUE, $data->application_id, $data->track_id, null);
				}

				if(!isset($bid['archive_id']))
				{
					throw new OutOfBoundsException(__METHOD__ . " Error: condor.4::Create did not return a valid result. missing archive id.");
				}

				$msg = self::Prpc()->Send($bid['archive_id'], $recp, (strtolower($send_type) == 'fax') ? 'FAX': 'EMAIL', (strtolower($send_type) == 'fax' ? $send_arr : NULL), isset($data->SenderName) ? $data->SenderName : null);

				if(!in_array($doc->document_list_id,$res_check))
				{
					$res_check[] = $doc->document_list_id;
					$result[$doc->document_list_id] = array("status" => &$send_result,
															"application_id" => $data->application_id,
															"archive_id" => $bid['archive_id'],
															"destination" => $destination,
															"method" => (strtolower($send_type) == 'fax') ? 'fax': 'email',
															"document" => (array) $doc
															);
				}

				$send_result =  ($msg) ? "sent" : "failed";

				foreach($res_check as $did)
				{
					$result[$did]['document_id'] = eCash_Document::Log_Document($server,(object) $result[$did]['document'],$result[$did]);
				}
			}
		}
		//eCash_Document::Log()->Write(__METHOD__ . " Result: " . var_export($result, true));

		return $result;

	}

	static public function Map_Data(Server $server, $data)
	{
		$ci = Fetch_Campaign_Info($data->application_id, $server->company_id);
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);

		if(!empty($ci))
		{
			try
			{
				require_once 'config.6.php';
				require_once 'mysql.4.php';

				$stat_host = eCash_Config::getInstance()->STAT_MYSQL_HOST;
				$stat_user = eCash_Config::getInstance()->STAT_MYSQL_USER;
				$stat_pass = eCash_Config::getInstance()->STAT_MYSQL_PASS;

				$scdb = new MySQL_4($stat_host, $stat_user, $stat_pass);
				$scdb->Connect();

				// The following is a quirk in how Config_6 is using MySQL_4
				$scdb->db_info['db'] = 'management';

				$scdb->Select('management');
				$config_6 = new Config_6($scdb);
				$site_config = $config_6->Get_Site_Config($ci->license_key, $ci->promo_id, $ci->promo_sub_code);
			}
			catch (Exception $e)
			{
				eCash_Document::Log()->Write("Error with Config_6:: " . $e->getMessage());
				eCash_Document::Log()->Write("Error with Config_6:: " . $e->getTraceAsString());
				eCash_Document::Log()->Write("Error with Config_6:: Trying Config_4 instead..");

				$site_config = Config_4::Get_Site_Config($ci->license_key, $ci->promo_id, $ci->promo_sub_code);
			}
		}

		preg_match('/(\d{3})(\d{2})(\d{4})/', $data->ssn, $ssn_matches);

    	$references = Fetch_References($data->application_id);

		$esig_site = split("\?",$server->new_app_url);

		// retrieved customer data
		$object->CustomerCity 			= ucwords($data->city); // Customer City
		$object->CustomerCounty 		= ucwords($data->county); // Customer City
		$object->CustomerDOB 			= $data->dob;
		$object->CustomerEmail 			= $data->customer_email;
		$object->CustomerESig 			= ""; //strtoupper(trim($data->name_first) . ' ' . trim($data->name_last)); //"*** FIX ME ***";
		$object->CustomerFax 			= empty($data->phone_fax) ? 'N/A' : self::Format_Phone($data->phone_fax);
		$object->CustomerNameFirst		= ucwords(trim($data->name_first));
		$object->CustomerNameFull 		= ucwords(trim($data->name_first))." ".ucwords(trim($data->name_last)); // Customer's Name
		$object->CustomerNameLast 		= ucwords(trim($data->name_last));
		$object->CustomerPhoneCell 		= self::Format_Phone($data->phone_cell);
		$object->CustomerPhoneHome 		= self::Format_Phone($data->phone_home);
		$object->CustomerResidenceLength = ""; // Length of Time the customer has been at their address (set to blank
		$object->CustomerResidenceType 	= ucwords($data->tenancy_type);
		$object->CustomerSSNPart1 		= $ssn_matches[1];
		$object->CustomerSSNPart2	 	= $ssn_matches[2];
		$object->CustomerSSNPart3 		= $ssn_matches[3];
		$object->CustomerState 			= strtoupper($data->state); // Customer's State
		$object->CustomerStateID 		= $data->legal_id_number;
		$object->CustomerStreet 		= ucwords($data->street);
		$object->CustomerUnit 			= ucwords($data->unit);
		$object->CustomerZip 			= $data->zip; // Customer's Zip
		$object->EmployerLength 		= "3 months"; // Checked and validated by olp...was told we don't care.
		$object->EmployerName			= ucwords($data->employer_name); // Customer's Employer
		$object->EmployerPhone 			= self::Format_Phone($data->phone_work); // Customer Employer Phone
		$object->EmployerShift 			= ucwords($data->shift); // The customer's work shift or hours as used in the load documents
		$object->EmployerTitle 			= ucwords($data->job_title);
		$object->IncomeDD 				= ($data->income_direct_deposit == "yes") ? "TRUE" : "FALSE";
		$object->IncomeFrequency 		= ucwords(str_replace("_", " ", $data->income_frequency));
		$object->IncomeMonthlyNet 		= self::Format_Money($data->income_monthly);
		$object->IncomeNetPay 			= self::Format_Money($data->net_paycheck);
		$object->BankABA				= $data->bank_aba;
		$object->BankAccount			= $data->bank_account;
		$object->BankAccountType		= $data->bank_account_type;
		$object->BankName 				= ucwords($data->bank_name);
		$object->IncomeType 			= $data->income_source;
		$object->LoanApplicationID 		= $data->application_id;
		$object->Password 				= $data->decrypt_pass;
		$object->Username 				= $data->login_id;

		// derived customer data
		$object->ConfirmLink			= &$object->eSigLink; //"*** FIX ME ***";
		$object->GenericEsigLink 		= &$object->eSigLink;
		$object->eSigLink 	 			= $data->esig_url;
		$object->ReactLink				= $data->react_url;
		$object->CSLoginLink			= $data->cs_login_link;
		$object->IncomePaydate1 		= $data->paydate_0;
		$object->IncomePaydate2 		= $data->paydate_1;
		$object->IncomePaydate3 		= $data->paydate_2;
		$object->IncomePaydate4 		= $data->paydate_3;

		$ref_num = 1;
		foreach($references as $ref)
		{
			$name_2 = "Ref0{$ref_num}NameFull";
			$phone_2 = "Ref0{$ref_num}PhoneHome";
			$relationship_2 = "Ref0{$ref_num}Relationship";

			$object->$name_2 			= ucwords($ref->full_name);
			$object->$phone_2 			= self::Format_Phone($ref->phone);
			$object->$relationship_2 	= ucwords($ref->relationship);

			$ref_num++;
		}

		//Company Data
		$object->CompanyCity			= !empty($data->company_addr_city) ? $data->company_addr_city: NULL; //"*** FIX ME ***"; // Company's City
		$object->CompanyCounty			= !empty($data->company_addr_county) ? $data->company_addr_county: NULL; //"*** FIX ME ***"; // Company's County
		$object->CompanyDept			= ($data->company_dept_name) ? $data->company_dept_name : NULL; //"*** FIX ME ***"; // Company Department handling loans
		$object->CompanyEmail 			= isset($data->company_email) ? $data->company_email : $data->customer_service_email;
		$object->CompanyFax 			= isset($data->company_fax) ? $data->company_fax : $site_config->company_support_fax; // Main fax number
		$object->CompanyInit 			= $site_config->property_short; // Company Initials (property short)
		$object->CompanyLogoLarge		= isset($data->company_logo_large) ? '<img src="'.$data->company_logo_large.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyLogoSmall		= isset($data->company_logo_small) ? '<img src="'.$data->company_logo_small.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyName			= isset($data->company_name) ? $data->company_name : NULL;
		$object->CompanyNameFormal		= isset($data->company_name_formal) ? $data->company_name_formal : NULL;
		$object->CompanyNameLegal		= isset($data->company_name_legal) ? $data->company_name_legal : NULL; //"*** FIX ME ***";
		$object->CompanyNameShort		= isset($data->company_name_short) ? $data->company_name_short : NULL; //"*** FIX ME ***";
		$object->CompanyPhone 			= isset($data->company_support_phone) ? self::Format_Phone($data->company_support_phone) : self::Format_Phone($site_config->support_phone); // Customer Service phone number
		$object->CompanyPromoID			= $ci->promo_id; //The promo ID of the company
		$object->CompanyState			= isset($data->company_addr_state) ? $data->company_addr_state : NULL; //"*** FIX ME ***"; // Company State
		$object->CompanyStreet			= isset($data->company_addr_street) ? $data->company_addr_street : NULL; //"*** FIX ME ***"; // Company Street
		$object->CompanySupportFax 		= isset($data->company_support_fax) ? $data->company_support_fax : NULL; //"*** FIX ME ***"; // Company Support Fax
		$object->CompanyUnit 			= isset($data->company_addr_unit) ? $data->company_addr_unit : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyWebSite 		= isset($data->company_site) ? $data->company_site : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyZip				= isset($data->company_addr_zip) ? $data->company_addr_zip : NULL; //"*** FIX ME ***"; // Company's Zip Code
		$object->LoginID 				= $object->LoginId = $data->agent_login; //"*** FIX ME ***";
		$object->SourcePromoID			= $ci->promo_id; //The promo ID of the company
		$object->SourceSiteName 		= $ci->url; // URL of the enterprise site as used in the loan documents

		$object->CompanyDeptPhoneCollections = isset($data->company_collections_phone) ? self::Format_Phone($data->company_collections_phone) : self::Format_Phone($object->CompanyPhone) ;
		$object->CompanyDeptPhoneCustServ = isset($data->company_support_phone) ? self::Format_Phone($data->company_support_phone) : self::Format_Phone($object->CompanyPhone);

		$object->CardProvBankName 		= '*** FIX ME ***'; // Company's Stored Value card provider's full name
		$object->CardProvBankShort 		= '*** FIX ME ***'; // Company's Stored Value card provider's short name
		$object->CardProvServName 		= '*** FIX ME ***'; // Company's Stored Value card provider's provider's service
		$object->CardProvServPhone 		= '*** FIX ME ***'; // Company's Stored Value card provider's provider's service provider'ss phoen number


		// Process Loan Data
		$object->LoanCollectionCode 	= isset($data->company_collections_code) ? $data->company_collections_code : null;
		$object->LoanDocDate 			= date("m/d/Y"); // The date of the document as used in the loan documents.
		$object->LoanStatus 			= $data->application_status; //"*** FIX ME ***";

		$object->LoanFundAvail 			= date('m-d-Y', strtotime(isset($data->date_fund_actual) ? $data->date_fund_actual : null));

		$object->LoanPayoffDate 		= isset($data->current_due_date)? $data->current_due_date : $data->date_first_payment;

		$object->LoanCurrAPR			= number_format($data->current_apr, 2, '.', '') . '%'; // calculated from current balance & current fin charge
		$object->LoanCurrPrincipal		= self::Format_Money($data->current_principal_payoff_amount); //the current principal payoff amount

		$object->LoanCurrFinCharge		= self::Format_Money(isset($data->current_service_charge) ? $data->current_service_charge : null); // finance charge of this upcoming debit event
		$object->LoanCurrPrinPmnt		= self::Format_Money(isset($data->current_principal) ? $data->current_principal : null); // principal payment amount of this upcoming debit event
		$object->LoanCurrDueDate		= isset($data->current_due_date) ? $data->current_due_date : null ; // due date of upcoming debit event
		$object->LoanCurrFees			= self::Format_Money(0); // any currently owed fees
		$funding_loan_balance = $data->fund_amount + (isset($data->current_service_charge) ? $data->current_service_charge : 0);
		$object->LoanCurrBalance		= self::Format_Money(isset($data->current_payoff_amount) ? $data->current_payoff_amount : null, $funding_loan_balance); // current principal payment + current fin ch

		$object->LoanNextAPR			= number_format(isset($data->next_apr) ? $data->next_apr : null, 2, '.', '') . '%';  // calculated from next balance & next fin charge
		$object->LoanNextPrincipal		= self::Format_Money(isset($data->next_principal_payoff_amount) ? $data->next_principal_payoff_amount : 0); //the current principal payoff amount
		$object->LoanNextBalance		= self::Format_Money(isset($data->next_total_due) ? ($data->next_total_due) : null); // next principal + next fin ch
		$object->LoanNextFinCharge		= self::Format_Money(isset($data->next_service_charge) ? ($data->next_service_charge) : null); // finance charge of the debit event following the current
		$object->LoanNextPrinPmnt		= self::Format_Money(isset($data->next_principal) ? ($data->next_principal) : null); // principal amount of the debit event following the current
		$object->LoanNextDueDate		= isset($data->next_due_date) ? $data->next_due_date : null; // due date of debit event following the current
		$object->LoanNextFees			= self::Format_Money(0); // any fees as of the next event
		$object->LoanAPR				= ($data->current_apr) ? $object->LoanCurrAPR : number_format($data->apr, 2, '.', '') . '%' ; // Curr if exists, else from DB
		$object->LoanInterestAccrued	= self::Format_Money($data->interest_accrued);
		$data->current_service_charge = isset($data->current_service_charge) ? $data->current_service_charge : null;


		$service_charge = !empty($data->estimated_service_charge) ? $data->estimated_service_charge : $data->current_service_charge;
		$object->LoanFinCharge			= self::Format_Money($service_charge, 0); // Curr if exists, else from DB
		$object->LoanFinanceCharge		= self::Format_Money($service_charge, 0); // Curr if exists, else from DB
		$object->LoanFundDate			= date('m/d/Y', strtotime($data->fund_action_date));
		$object->LoanFundDate2			= date('m/d/Y', strtotime($data->fund_due_date));
		$object->LoanDueDate			= !empty($data->current_due_date) ? $data->current_due_date : (!empty($data->due_date_inactive) ? $data->due_date_inactive : $data->date_first_payment); // Curr if exists, then due_date_inactive, else from DB; mantis:5924
		$object->LoanFees				= self::Format_Money($data->fee_balance);
// 		Curr if exists, else from DB
//		echo $data->current_principal_payoff_amount .'<br>';
//		echo '<pre>'.print_r($data,true).'</pre>';


		//$object->LoanDueDate			= !empty($data->current_due_date) ? $data->current_due_date : $data->date_first_payment ; // Curr if exists, else from DB


//		$object->LoanFundDate			= ($data->current_fund_date) ? $data->current_fund_date : $data->date_fund_estimated_month . '-' . $data->date_fund_estimated_day . '-' . $data->date_fund_estimated_year;
//		$object->LoanFundDate2			= date('m/d/Y', strtotime($data->date_fund_2));


//		$result->fund_action_date = $result->date_fund_actual_ymd;
//		$result->fund_due_date = $pdc->Get_Business_Days_Forward($result->fund_action_date, 1);

//		$object->LoanOrigFundAmount		= self::Format_Money(); // Original balance, from schedule or db
//		$object->LoanOrigFundDate		= ""; // original date_event of funding schedule.. or db
//		$object->LoanOrigFundAvail		= ""; // Original date_Effective of schedule, or est_fund_date from db

//		$object->TotalOfPayments		= self::Format_Money(); // Sum of payments of the entire schedule, or est from Qualify.2
//		$object->TotalPaymentsToDate	= self::Format_Money(); // Sum of payments to date, from schedule
//		$object->TotalPaymentsFromDate	= self::Format_Money(); // Sum of payments after date, or est from Qualify.2

		$object->PaymentArrAmount		= self::Format_Money(isset($data->next_arrangement_payment) ? $data->next_arrangement_payment : null);
		$object->PaymentArrDate			= isset($data->next_arrangement_due_date) ? ($data->next_arrangement_due_date) : null;
		$object->PaymentArrType			= isset($data->next_arrangement_type) ? ($data->next_arrangement_type) : null;

		$object->MissedArrAmount		= self::Format_Money(isset($data->past_arrangement_payment) ? $data->past_arrangement_payment : 0);
		$object->MissedArrDate			= isset($data->past_arrangement_due_date) ? $data->past_arrangement_due_date : null;
		$object->MissedArrType			= isset($data->past_arrangement_type) ? $data->past_arrangement_type : null;

		$object->PDAmount				= self::Format_Money(isset($data->current_principal) ? $data->current_principal : 0);
		$object->PDFinCharge			= self::Format_Money(isset($data->current_service_charge) ?  $data->current_service_charge : 0);
		$object->PDTotal				= self::Format_Money(isset($data->current_total_due) ? $data->current_total_due : 0);
		$object->PDDueDate				= !empty($data->current_due_date) ? $data->current_due_date : $data->date_first_payment;

		$object->PDNextAmount			= self::Format_Money(isset($data->next_principal) ? $data->next_principal : 0);
		$object->PDNextFinCharge		= self::Format_Money(isset($data->next_service_charge) ? $data->next_service_charge : 0);
		$object->PDNextTotal			= self::Format_Money(isset($data->next_total_due) ? $data->next_total_due : 0);
		$object->PDNextDueDate			= !empty($data->next_due_date) ? $data->next_due_date : "Not Scheduled";
		$object->PDPercent				= isset($data->paydown_percent) ? $data->paydown_percent : '0%';
		$object->RefinanceAmount		= self::Format_Money(0);
		$object->ReturnFee 				= self::Format_Money($data->business_rules['return_transaction_fee']);

		// Someone can't spell
		$object->LoanCancellationDelay  = isset($data->business_rules['cancelation_delay']) ? $data->business_rules['cancelation_delay'] : NULL;

		$payment_amount = Loan_Data::Get_Payment_Amount($data->business_rules, $data->fund_amount);
		$object->PrincipalPaymentAmount	= self::Format_Money($payment_amount);

		$object->ReturnReason 			= empty($data->reason_for_ach_return) ? 'for review' : $data->reason_for_ach_return; //'*** FIX ME ***';

		//Misc
		$object->Today 					= date("m/d/Y"); // Today's Date
		$object->Day 					= date("d"); // Today's Day
		$object->Time					= date("h:ia");

		$object->VIN					= $data->vehicle_vin;
		$object->Year					= $data->vehicle_year;
		$object->Model					= ($data->vehicle_model) ? $data->vehicle_model : $data->vehicle_series;
		$object->Make					= $data->vehicle_make;
		$object->VehicleMileage			= $data->vehicle_mileage;

		$object->AccountRep				= $data->agent_name;
		$object->CustomerResidenceLength = $data->CustomerResidenceLength;
		//These are fees which affect principal (primarily fees that Agean adds)
		$principal_fees = 0;
		$WireTransferFee = 0;
		$DeliveryFee = 0;
		$TitleLienFee = 0;

		//GF 5431 Will only show fees that have been added
		if (Application_Has_Events_By_Event_Names($data->application_id, array('assess_fee_transfer', 'payment_fee_transfer', 'writeoff_fee_transfer')) == TRUE)
		{
			$WireTransferFee = Fetch_Balance_Total_By_Event_Names($data->application_id,array('assess_fee_transfer','payment_fee_transfer', 'writeoff_fee_transfer'));
		}
		$principal_fees = bcadd($WireTransferFee, $principal_fees, 2);
		$object->WireTransferFee = self::Format_Money($WireTransferFee);

		// GF 8293 Check if Title loan has event types relating to delivery fees, if so, total them up and return that, else use the old method.
       	if (Application_Has_Events_By_Event_Names($data->application_id, array('assess_fee_delivery', 'payment_fee_delivery', 'writeoff_fee_delivery')) == TRUE)
       	{
           	$DeliveryFee = Fetch_Balance_Total_By_Event_Names($data->application_id,array('assess_fee_delivery','payment_fee_delivery', 'writeoff_fee_delivery'));
       	}
       	$principal_fees = bcadd($DeliveryFee, $principal_fees, 2);
		$object->DeliveryFee = self::Format_Money($DeliveryFee);

		//GF 5429 Only show Lien fee for Title Loans
		//GF 6334 Check if Title loan has event types relating to lien fees, if so, total them up and return that, else use the old
		//        method.
		if (Application_Has_Events_By_Event_Names($data->application_id, array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien')) == true)
		{
			$TitleLienFee = Fetch_Balance_Total_By_Event_Names($data->application_id,array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien'));
		}
		$principal_fees = bcadd($TitleLienFee, $principal_fees, 2);
		$object->TitleLienFee = self::Format_Money($TitleLienFee);

		$fees = $data->fee_balance;
		$object->NetLoanProceeds		= self::Format_Money($data->fund_amount); //@TODO: Account for fees

		//NetProceedsAmount is used in the Delaware Payday Loan documents, but wasn't being populated with anything
		$object->NetProceedsAmount = $object->NetLoanProceeds;

		//This is the predicted fund amount plus the the currently registered fees that count towards principal.
		$object->LoanFundAmount	= self::Format_Money($data->fund_amount + $principal_fees);

		//Because documents are being sent that contain balances in them before the loan has even been funded
		//we need to verify whether or not the loan disbursement has taken place yet. AGEAN LIVE #14863
		$funded_amount = Fetch_Balance_Total_By_Event_Names($data->application_id,array('loan_disbursement'));

		//If there's a current principal amount due and the disbursement is pending or complete, we use the current principal amount due
		if ((isset($data->current_principal_payoff_amount) && (($object->LoanStatus == "Inactive (Paid)") || ($data->current_principal_payoff_amount > 0))) && ($funded_amount))
		{
			$principal = $data->current_principal_payoff_amount;
		}
		//If not, we predict the principal amount by taking the application's fund amount and adding all the pending/complete principal fees to it.
		else
		{
			$principal = $data->fund_amount + $principal_fees;
		}

		$object->LoanPrincipal			= self::Format_Money($principal);

		// GF #15409
		// This is an admitted hack, but I can't account for why the principal and service charge need all these
		// special conditions which change which value they display.
		// However, I can check if this condition is happening by service_charge being empty at this point (not 0, empty)
		// so I'll just go ahead and do that here, even though none of this crap should be happening here in the first
		// place.
		if (empty($service_charge))
		{
			$balance = Fetch_Balance_Information($data->application_id);

			$service_charge = $balance->service_charge_pending + $fees;
		}

		$object->LoanBalance			= self::Format_Money($principal + $service_charge);
		// The total amount paid as used in the loan documents.  We are now calculating this amount if current_payoff_amount is empty. [#21853]
		$object->TotalOfPayments        = self::Format_Money(($data->current_payoff_amount) ? $data->current_payoff_amount : ($principal + $service_charge + $fees));
		
		$object->CustomerCounty			= $data->customer_county;
		$object->CompanyCounty			= $stat_pass = eCash_Config::getInstance()->COMPANY_COUNTY;

		$balance = $data->current_payoff_amount ? $data->current_payoff_amount : $data->payment_total;
		$object->SettlementOffer		= self::Format_Money($balance * $data->business_rules['settlement_offer']['settlement_offer']/100);

		$object->LoanRefAmount			= &$object->LoanFinCharge;
		$object->LoanCurrPrinAmount		= &$object->LoanPrincipal;

		$object->MoneyGramReference		= str_replace("Check # ", '', isset($data->check_number) ? $data->check_number : null);

		$object->PaymentDate			= date('m/d/Y', strtotime($data->last_payment_date));
		$object->PaymentPostedAmount    = self::Format_Money($data->last_payment_amount);

		if (is_object(ECash::getTransport()) && is_a(ECash::getTransport(), 'ECash_Transport'))
		{
			$trans_data = ECash::getTransport()->Get_Data();
		}

		$object->GenericSubject			= (isset($trans_data->GenericSubject) ? $trans_data->GenericSubject : "");
		$object->GenericMessage			= (isset($trans_data->GenericMessage) ? $trans_data->GenericMessage : "");
		$object->SenderName				= (isset($trans_data->SenderName) ? $trans_data->SenderName : "");

		require_once LIB_DIR ."/business_time.class.php";
		$object->NextBusinessDay		= date("m/d/Y", strtotime(Company_Time::Singleton()->Get_Days_Forward(1)));

		//NEW TOKENS [#12440]
		// These are good
		$object->CompanyClientEmail = $data->pre_support_email;
		$object->CompanyClientFax = $data->pre_support_fax;
		$object->CompanyClientPhone = $data->pre_support_phone;

		// So are these (I Think)
		$object->CompanyCustEmail = $data->company_support_email;
		$object->CompanyCustFax = $data->company_support_fax;
		$object->CompanyCustPhone = $data->company_support_phone;

		// These 3 are all good
		$object->CompanyCollEmail = $data->collections_email;
		$object->CompanyCollFax = $data->collections_fax;
		$object->CompanyCollPhone = $data->collections_phone;

		// Suggested Paydown Increment - Used in docs, defined in eCash Config
		$object->PDIncrement = $data->suggested_payment_increment;

		//Yeah, I'm really adding a token for this.  This is required for GRV's Loan Renewals. This is, easily, the most brilliant idea ever!
		$object->RenewalDocument = 'document_1.pdf';
		$object->LoanNoticeDays = $data->loan_notice_days;
		$object->LoanNoticeTime = self::format_time($data->loan_notice_time) . ' ' . Company_Rules::Get_Config("time_zone");		

		$object->TimeCSMFOpen = self::format_time(Company_Rules::Get_Config("company_start_time"));
		$object->TimeCSMFClose = self::format_time(Company_Rules::Get_Config("company_close_time"));
		$object->TimeCSSatOpen = self::format_time(Company_Rules::Get_Config("sat_company_start_time"));
		$object->TimeCSSatClose = self::format_time(Company_Rules::Get_Config("sat_company_close_time"));
		$object->TimeZoneCS =   Company_Rules::Get_Config("time_zone");

		//CSO Tokens [#17240]
		$service_charge 				= !empty($data->estimated_service_charge) ? $data->estimated_service_charge : $data->current_service_charge;
		$object->CSOApplicationFee 		= self::Format_Money($data->cso_assess_fee_app); //Value of eCash Business Rule w/ same name Ex �$30�
		$object->CSOBrokerFee 			= self::Format_Money($data->cso_assess_fee_broker); //Value of eCash Business Rule w/ same name Ex. �$90�
		$object->CSOLenderACHReturnFee 	= self::Format_Money($data->lend_assess_fee_ach); //Value of eCash Business Rule w/ same name Ex. �$20�
		// It is stupid we're attaching arbitrary formatting specifiers to token values rather than putting them in the document itself [benb]
		$object->CSOLenderInterest 		= $data->svc_charge_percentage . '%'; //Value of eCash Business Rule w/ same name Ex. �10%�
		$object->CSOLenderLateFee 		= $data->cso_assess_fee_late; //Value of eCash Business Rule w/ same name.  Ex. �$7.50 or 5% of the payment amount, whichever is greater�
		$object->CSOTotalFinanceCost 	= self::Format_Money($service_charge + $data->cso_assess_fee_broker); //$ sum of the values of CSO Broker Fee and Lender Interest business rules.  Ex. �$91.15� ($90 + $1.15)
		$object->CSOAmountFinanced 		= self::Format_Money($data->fund_amount); //$ sum of Loan Principal and CSO Broker Fee.  Ex. �$390.00� ($300 + $90)
		$object->LoanCancellationDate 	= date('m/d/Y', strtotime($data->loan_cancellation_date)); //Calendar date that a cancellation notice must be received by.  Derived from the values of estimated funding date and the eCash business rule "Cancellation delay" Ex. 8/18/2008
		
		//CSO Tokens [#18142]
		$object->CSOBrokerFeePercent	= $data->business_rules['cso_assess_fee_broker']['percent_amount'] . '%';
		$object->CSOTotalOfPayments		= self::Format_Money($data->fund_amount + $data->cso_assess_fee_broker + $service_charge); //Total of Principal, CSO Fee, and Interest
		
		//Lender Legal Name [#18923]
		$object->CSOLenderNameLegal             = $data->cso_lender_name_legal;
		
		// Tokens added for HMS [#19277]
		$object->CompanyPaymentStreet           = $data->payment_street;
		$object->CompanyPaymentCity             = $data->payment_city;
		$object->CompanyPaymentState            = $data->payment_state;
		$object->CompanyPaymentZip              = $data->payment_zip;
		$object->CompanyPaymentBank             = $data->payment_bank;
		$object->CompanyPaymentABA              = $data->payment_aba;
		$object->CompanyPaymentAccount          = $data->payment_account;
		
		// We are going to try to run Map data recursively if we have Child Apps
		// this is a dirty hack to obtain child application information for tokens.
		// Only the most recent child data will be used. Using the switch map_data_level_2 will
		// keep mapdata from going into a endless loop. [rlopez]
		// Loop Checker
		if(empty($data->map_data_level2))
		{

			$tmpobj = clone($object);
			foreach ($tmpobj as $key => $value)
			{
				$objName = "Child{$key}";
				$object->$objName = "";
			}

			// Fetch Child Application Information
			$arrChildApps = Get_Reacts_From_App($data->application_id, $server->company_id);
			if(count($arrChildApps))
			{
				$application_id = end($arrChildApps)->application_id;
				$objChildMapData = eCash_Document_ApplicationData::Get_Data($server, $application_id, null);
				$objChildMapData->map_data_level2 = true; // Don't fall into a loop
				$objChildMapData = self::Map_Data($server, $objChildMapData);
				foreach ($objChildMapData as $key => $value)
				{
					$objName = "Child{$key}";
					$object->$objName = $value;
				}
			}
		}

		return (array) $object;

	}
	static function format_time($dec)
	{
		if(empty($dec))
		{
			return 'Closed';
		}
		if($dec > 1200)
		{
			return intval((substr(($dec),0,2) - 12)) . ':' . substr(($dec),2)  . 'pm';
		}
		elseif($dec < 1200)
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'am';
		}
		else
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'pm';
		}
	}

	static public function Fetch_Doc_List( $server = NULL )
	{

		try
		{
			if(!count(self::$template_names))
			{
				self::$template_names = self::Prpc()->Get_Template_Names();
			}

			foreach(self::$template_names as $key => $value)
			{

				$obj = (object) array();
				$obj->name = $value;
				$obj->description = $obj->name;
				$obj->file = $value;
				$obj->required = 0;
				$doc_return[$obj->name] = $obj;
			}

		}
		catch(Exception $e)
		{
			if($server instanceof Server) eCash_Document::Log()->Write($e->getMessage());
			else throw $e;
		}

		return $doc_return;

	}


	static public function Validate_Tiff ($archive_id)
	{

		return (bool) self::Prpc()->Find_By_Archive_Id($archive_id);

	}



	static public function Get_PDF (Server $server, $archive_id)
	{
		$document = self::Prpc()->Find_By_Archive_Id($archive_id);

		if($document === FALSE)
		{
			echo "There was an error retrieving this document.  Please contact support and reference Archive ID: {$archive_id}";
			die();
		}

		if(isset($document->template_name))
		{
			try
			{
				if(!count(self::$template_names))
				{
					self::$template_names = self::Prpc()->Get_Template_Names();
				}

				if(in_array($document->template_name,self::$template_names))
				{

					$tokens = self::Prpc()->Get_Template_Tokens($document->template_name);
					if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens))
					{
						throw new Exception();
					}


					$acl = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
					if(	is_array($acl) && count(array_intersect(array('ssn_last_four_digits'),$acl)) &&
						count(array_intersect($tokens,self::$deny_view_tokens))) {
						echo "Document Blocked";
						die;
					}

				}

			}
			catch (Exception $e)
			{
				if ($e->getMessage())
				{
					throw $e;
				}
			}

		}

		//header("Content-type: " . $document->content_type);

		self::Set_Display_Headers($document);

		echo (isset($document->data)) ? $document->data : "No Document Data" ;

		die;

//		return ($document->data) ? $document : false;

	}

	/**
	 * Same as Get_PDF(), but for document attachments.
	 *
	 * @param object $server
	 * @param object $archive_id
	 * @param object $attachment_key The key from the document's attached_data array
	 */
	static public function Get_Attachment_PDF (Server $server, $archive_id, $attachment_key)
	{
		$document = self::Prpc()->Find_By_Archive_Id($archive_id);

		if($document === FALSE)
		{
			echo "There was an error retrieving this document.  Please contact support and reference Archive ID: {$archive_id}";
			die();
		}

		// TODO: Modify this in some way to protect attachments with no templates
		if(isset($document->template_name))
		{
			try
			{
				if(!count(self::$template_names))
				{
					self::$template_names = self::Prpc()->Get_Template_Names();
				}

				if(in_array($document->template_name,self::$template_names))
				{

					$tokens = self::Prpc()->Get_Template_Tokens($document->template_name);
					if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens))
					{
						throw new Exception();
					}

					$acl = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
					if(	count(array_intersect(array('ssn_last_four_digits'),$acl)) &&
						count(array_intersect($tokens,self::$deny_view_tokens))) {
						echo "Document Blocked";
						die;
					}

				}

			}
			catch (Exception $e)
			{
				if ($e->getMessage())
				{
					throw $e;
				}
			}
		}

		if ( !is_array($document->attached_data) || !isset($document->attached_data[$attachment_key]) )
			die;

		$attachment = $document->attached_data[$attachment_key];

		self::Set_Display_Headers($attachment);

		echo (isset($attachment->data)) ? $attachment->data : NULL ;
		die;
	}



	static public function Set_Application_Id ($archive_id, $application_id)
	{

		return (bool) self::Prpc()->Set_Application_Id($archive_id, $application_id);

	}

	static public function Check_ArchiveIDs(Server $server, $archive_id)
	{
		$app_docs = 0;
		$query = "
			SELECT COUNT(archive_id) as Counter FROM document WHERE document_event_type = 'received' AND archive_id = $archive_id
			";

		$db = ECash_Config::getMasterDbConnection();
		$q_obj = $db->query($query);

		while( $row = $q_obj->fetch(PDO::FETCH_OBJ))
		{
			$app_docs = $row->Counter;
		}
		return $app_docs;
	}

	static public function Format_Money($value, $default = NULL)
	{
		return eCash_Document_ApplicationData::Format_Money($value, $default);
	}

	static public function Format_Phone($value)
	{
		preg_match("/1?(\d{3})(\d{3})(\d{4})/", preg_replace("/\D/","",$value), $matches);
		array_shift($matches);

		if ( strlen(implode("",$matches)) != 10 )
		{
			return $value;
		}

		return ( ($incl_iac) ? "1 " : "" ) . "({$matches[0]}) {$matches[1]}-{$matches[2]}";
	}

	/**
	 * Returns a file name based on the document's Condor data object
	 *
	 * @param object $document The Condor data object
	 * return string The file extension
	 */
	static protected function Get_File_Name($document)
	{
		if ( !empty($document->uri) && $document->uri != 'NULL' )
		{
			return $document->uri;
		}

		$extensions = array(
		                   'text/html'       => '.html',
		                   'text/plain'      => '.txt',
		                   'text/rtf'        => '.rtf',
		                   'text/rtx'        => '.rtf',
		                   'application/pdf' => '.pdf',
		                   'image/tif'       => '.tif'
		                   );

		$filename = ($document->template_name != 'NULL' ? $document->template_name : 'Document');

		if ( isset($extensions[$document->content_type]) )
		{
			return $filename . $extensions[$document->content_type];
		}

		return $filename;
	}

	/**
	 * Sets header data based on the document's Condor data object
	 *
	 * @param object $document The Condor data object
	 */
	static protected function Set_Display_Headers($document)
	{
		header("Content-type: " . $document->content_type);

		// content types that will display in the browser
		$display_types = array(
		                   'text/html',
		                   'text/plain'
		                   );

		// if this document's content type will not display in the browser
		if ( !in_array($document->content_type, $display_types) )
		{
			header('Content-Disposition: attachment; filename="' . self::Get_File_Name($document) . '"');
		}
	}

}

?>
