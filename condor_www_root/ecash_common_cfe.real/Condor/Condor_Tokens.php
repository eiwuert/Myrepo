<?php
/**
 * @package condor_tokens
 * 
 * <b>Revision History</b>
 * <ul>
 *   <li><b>2007-12-06 - rayl</b><br>
 *     Condor Token API created for use with OLP and eCash.
 *   </li>
 *   <li><b>2007-12-10 - rayl</b><br>
 *     Code cleanup for Condor Tokens and addition of Child Tokens.
 *   </li>
 *   <li><b>2007-12-12 - mlively</b><br>
 *     Added the CustomerInitialPayDown and CustomerInitialInFull Tokens.
 *   </li>
 * </ul>
 */

require_once("qualify.2.php");
require_once("prpc/client.php");
require_once('business_rules.class.php');
require_once("config.6.php");
require_once("mysql.4.php");
require_once("qualify.2.php");
require_once("pay_date_calc.3.php");
require_once("../ECash/Crypt.php");

/**
 * An API for accessing and creating unified Condor Tokens.
 *
 * This class will connect to the eCash database to gather any
 * needed information for Condor based on application id.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
abstract class Condor_Tokens
{
	protected $db;
	protected $app;
	protected $company_id;
	protected $config_6;
	protected $space_key;
	protected $campaign_info;
	protected $biz_rules;
	protected $qualify;
	protected $site_config;
	protected $generic_email;
	protected $login_id;
	


	
	/**
	 * Set OLP Database. 
	 * @param object $db
	 */
	public function Set_OLP($db) { $this->db["OLP"] = $db; }
	
	/**
	 * Set LDB Database. 
	 * @param object $db
	 */
	public function Set_LDB($db) { $this->db["LDB"] = $db; }	
	
	/**
	 * Get OLP Database. 
	 */
	public function Get_OLP() { return $this->db["OLP"]; }
	
	/**
	 * Get LDB Database. 
	 */
	public function Get_LDB() { return $this->db["LDB"]; }		
	
	/**
	 *  Set Space Key
	 * @param object $db
	 */
	public function Set_Space_Key($space_key) { $this->space_key = $space_key; }
	
	/**
	 *  Set Space Key
	 * @param object $db
	 */
	public function Get_Space_Key() { return $this->space_key; }
	
	/**
	 *  Set Config 6
	 * @param object $db
	 */
	public function Set_Config_6($config_6) { $this->config_6 = $config_6; }
	
	/**
	 * Set Company ID
	 * @param int $company_id
	 */	
	public function Set_Company_ID($company_id) { $this->company_id = $company_id; }
	
	/**
	 * Get Company ID
	 */	
	public function Get_Company_ID() { return $this->company_id; }	
	
	
	/**
	 * Set Application ID
	 * @param int $application_id
	 */	
	public function Set_Application($app) { $this->app = $app; }

	/**
	 * Get Application ID
	 * @param int $application_id
	 */	
	public function Get_Application() { return $this->app; }	
	
	/**
	 * Set Campaign_Info
	 * 
	 * Campaign info can either be created by the Condor Tokens object or can be passed in.
	 *
	 * @param object $ci
	 */
	public function Set_Campaign_Info($ci) { $this->campaign_info = $ci; }	
	public function Get_Campaign_Info() { return $this->campaign_info; }	
		
	/**
	 * Set Site Config
	 * 
	 * The Config 6 Site Config object can be passed in beforehand or created within the Condor_Token object
	 * with the supplied Campaign Info object.
	 */
	public function Set_Site_Config() 
	{
		$this->site_config = $this->config_6->Get_Site_Config($this->campaign_info->license_key, $this->campaign_info->promo_id, $this->campaign_info->promo_sub_code);
	}		
	
	/**
	 * Get Site Config
	 */
	public function Get_Site_Config() { return $this->site_config; }
		
	/**
	 * Set Login ID
	 * 
	 * Pass the login ID of the agent to be used with Condor documents. If none is passed the Company name 
	 * short will be used.
	 *
	 * @param string $login_id
	 */
	public function Set_Login_ID($login_id) { $this->login_id = $login_id; }
	public function Get_Login_ID() { return $this->login_id; }

	/**
	 * Set Business Rules
	 */
	public function Set_Business_Rules() { $this->biz_rules = new ECash_Business_Rules($this->db["LDB"]); }
	
	/**
	 * Get Business Rules
	 */
	public function Get_Business_Rules() { return $this->biz_rules;}	
	
	/**
	 * Set Holiday List
	 */
	public function Set_Holiday_List() { $this->holidays = $this->Fetch_Holiday_List(); }
	
	/**
	 * Set Holiday List
	 */
	public function Get_Holiday_List() { return $this->holidays; }	

	/**
	 *  Set Pay Date Calc
	 */
	public function Set_Pay_Date_Calc() {  
		$this->pdc = new Pay_Date_Calc_3($this->holidays); 
		$this->next_bus_day 	= date('m/d/Y', strtotime($this->pdc->Get_Business_Days_Forward(date('Y-m-d'), 1)));
	}
	
	/**
	 *  Get Pay Date Calc
	 */
	public function Get_Pay_Date_Calc() {  return $this->pdc; }
	
	/**
	 *  Get Get_Next_Bus_Day
	 */
	public function Get_Next_Bus_Day() {  return $this->next_bus_day; }	
		
	/**
	 *  Set Qualify_2
	 */
	public function Set_Qualify_2() {  $this->qualify = new Qualify_2(null,null); }	
	
	/**
	 *  Set Pay Date Calc
	 */
	public function Get_Qualify_2() {  return $this->qualify; }		
	
	/**
	 * Set Generic Email
	 * 
	 * Sets inforation to be used with eCasg Eail Queues. Could be used for otehr documents in the future.
	 *
	 * @param unknown_type $sender
	 * @param unknown_type $subject
	 * @param unknown_type $message
	 */
	public function Set_Generic_Email($sender, $subject, $message)
	{
		$this->generic_email	= array("sender" => $sender, "subject" => $subject, "message" => $message);
	}
	
	abstract function Get_Tokens();
	
	abstract function Process_Application_ID($application_id);
	
	abstract function Fetch_References($application_id);
	
	abstract function Get_Condor_Application_Child($application_id);
	
	/**
	 * Get Condor Child Tokens
	 *
	 * In certain cases documents will use data from an application child
	 * but are send via the exisiting application. This funcation will gather child
	 * capplication information and store that within the Condor Token list
	 * as "Child" Tokens.
	 *
	 * @param object $object
	 */
	public function Get_Condor_Child_Tokens(&$object)
	{
		$arrChildApps = $this->Get_Condor_Application_Child($this->Get_Application()->getId());
		if(count($arrChildApps))
		{
			$application_id = end($arrChildApps)->application_id;
			$data = $this->Process_Application_ID($application_id);
			$childObject = $this->Map_Condor_Data($data);
			foreach ($childObject as $key => $value)
			{
				$objName = "Child{$key}";
				$object->$objName = "";				
			}
		}
		else
		{
			$tmpobj = clone($object);
			foreach ($tmpobj as $key => $value)
			{
				$objName = "Child{$key}";
				$object->$objName = "";
			}			
		}		
	}
	
	/**
	 * May Condor Data
	 * 
	 * Compiles condor token object with supplied application data.
	 *
	 * @param object $data
	 * @return object $tokens
	 */
	public function Map_Condor_Data($data)
	{				
		preg_match('/(\d{3})(\d{2})(\d{4})/', $data->ssn, $ssn_matches);
		$references = $this->Fetch_References($data->application_id);

		$esig_site = split("\?",$server->new_app_url);
				
		$object = new stdclass;		
		// retrieved customer data
		$object->space_key				= $this->Get_Space_Key();
		$object->CustomerCity 			= ucwords($data->city); // Customer City
		$object->CustomerCounty 		= ucwords($data->county); // Customer City
		$object->CustomerDOB 			= $data->dob;
		$object->CustomerEmail 			= $data->customer_email;
		$object->CustomerESig 			= ""; //strtoupper(trim($data->name_first) . ' ' . trim($data->name_last)); //"*** FIX ME ***";
		$object->CustomerFax 			= empty($data->phone_fax) ? 'N/A' : $data->phone_fax;
		$object->CustomerNameFirst		= ucwords(trim($data->name_first));
		$object->CustomerNameFull 		= ucwords(trim($data->name_first))." ".ucwords(trim($data->name_last)); // Customer's Name
		$object->CustomerNameLast 		= ucwords(trim($data->name_last));
		$object->CustomerPhoneCell 		= $this->Format_Phone( $data->phone_cell );
		$object->CustomerPhoneHome 		= $this->Format_Phone( $data->phone_home );
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
		$object->EmployerPhone 			= $this->Format_Phone($data->phone_work ); // Customer Employer Phone
		$object->EmployerShift 			= ucwords($data->shift); // The customer's work shift or hours as used in the load documents
		$object->EmployerTitle 			= ucwords($data->job_title);
		$object->IncomeDD 				= ($data->income_direct_deposit == "yes") ? "TRUE" : "FALSE";
		$object->IncomeFrequency 		= $data->income_frequency;
		$object->IncomeMonthlyNet 		= $this->Format_Money($data->income_monthly); // number_format($data->income_monthly, 0, '.', '');
		$object->IncomeNetPay 			= $this->Format_Money($this->Calculate_Monthly_Net($data->income_frequency, $data->income_monthly)); // number_format($data->income_monthly, 0, '.', '');
		$object->BankABA				= $data->bank_aba;
		$object->BankAccount			= $data->bank_account;
		$object->BankName 				= ucwords($data->bank_name);
		$object->IncomeType 			= $data->income_source;
		$object->LoanApplicationID 		= $data->application_id;
		$object->LoanDateCreated		= $data->date_app_created;
		$object->LoanTimeCreated		= $data->time_app_created;
		$object->CustomerIPAddress		= $data->client_ip_address;
		$object->CustomerInitialPayDown = '';
		$object->CustomerInitialInFull = '';
		
		if (isset($data->crypt_password) && strlen($data->crypt_password))
		{
			$object->Password = crypt_3::Decrypt($data->crypt_password);
		}
		else 
		{
			$object->Password= 'UNKNOWN';
		}		
		$object->Username 				= $data->login_id;

		// derived customer data
		$object->ConfirmLink			= &$object->eSigLink; //"*** FIX ME ***";
		$object->GenericEsigLink 		= &$object->eSigLink;
		$object->eSigLink 	 			= $data->esig_url;
		$object->ReactLink				= $data->react_url;
		$object->CSLoginLink			= $data->cs_login_link;

		$object->GenericEsigLink 		= &$object->eSigLink;

		$object->IncomePaydate1 		= $data->paydate_0;
		$object->IncomePaydate2 		= $data->paydate_1;
		$object->IncomePaydate3 		= $data->paydate_2;
		$object->IncomePaydate4 		= $data->paydate_3;

		$ref_num = 1;
		foreach($references as $ref) {
			$name_2 = "Ref0{$ref_num}NameFull";
			$phone_2 = "Ref0{$ref_num}PhoneHome";
			$relationship_2 = "Ref0{$ref_num}Relationship";

			$object->$name_2 			= ucwords($ref->full_name);
			$object->$phone_2 			= $this->Format_Phone( $ref->phone );
			$object->$relationship_2 	= ucwords($ref->relationship);

			$ref_num++;
		}

		//Company Data
		$object->CompanyCity			= ($data->company_addr_city) ? $data->company_addr_city: NULL; //"*** FIX ME ***"; // Company's City
		$object->CompanyDept			= ($data->company_dept_name) ? $data->company_dept_name : NULL; //"*** FIX ME ***"; // Company Department handling loans
		$object->CompanyCounty			= !empty($data->company_addr_county) ? $data->company_addr_county: NULL; //"*** FIX ME ***"; // Company's County
		$object->CompanyEmail 			= isset($data->company_support_email) ? $data->company_support_email : (isset($this->Get_Site_Config()->company_email) ? $this->Get_Site_Config()->company_email : $this->Get_Site_Config()->customer_service_email); // Customer Service email address
		$object->CompanyFax 			= isset($data->company_support_fax) ? $data->company_support_fax : $this->Get_Site_Config()->support_fax; // Main fax number
		$object->CompanyInit 			= $this->Get_Site_Config()->property_short; // Company Initials (property short)
		$object->CompanyLogoLarge		= isset($data->company_logo_large) ? '<img src="'.$data->company_logo_large.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyLogoSmall		= isset($data->company_logo_small) ? '<img src="'.$data->company_logo_small.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyName			= isset($data->company_name) ? $data->company_name : NULL;
		$object->CompanyNameFormal		= isset($data->company_name_formal) ? $data->company_name_formal : NULL;
		$object->CompanyNameLegal		= isset($data->company_name_legal) ? $data->company_name_legal : NULL; //"*** FIX ME ***";
		$object->CompanyNameShort		= isset($data->company_name_short) ? $data->company_name_short : NULL; //"*** FIX ME ***";
		$object->CompanyPhone 			= isset($data->company_support_phone) ? $data->company_support_phone : $this->Get_Site_Config()->support_phone; // Customer Service phone number
		$object->CompanyPromoID			= $this->Get_Campaign_Info()->promo_id; //The promo ID of the company
		$object->CompanyState			= isset($data->company_addr_state) ? $data->company_addr_state : NULL; //"*** FIX ME ***"; // Company State
		$object->CompanyStreet			= isset($data->company_addr_street) ? $data->company_addr_street : NULL; //"*** FIX ME ***"; // Company Street
		$object->CompanySupportFax 		= isset($data->company_support_fax) ? $data->company_support_fax : NULL; //"*** FIX ME ***"; // Company Support Fax
		$object->CompanyUnit 			= isset($data->company_addr_unit) ? $data->company_addr_unit : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyWebSite 		= isset($data->company_site) ? $data->company_site : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyZip				= isset($data->company_addr_zip) ? $data->company_addr_zip : NULL; //"*** FIX ME ***"; // Company's Zip Code
		$object->LoginId 				= $this->Get_Login_ID(); //"*** FIX ME ***";
		$object->SourcePromoID			= $this->Get_Campaign_Info()->promo_id; //The promo ID of the company
		$object->SourceSiteName 		= $this->Get_Campaign_Info()->url; // URL of the enterprise site as used in the loan documents

		$object->CompanyDeptPhoneCollections = isset($data->company_collections_phone) ? $data->company_collections_phone : $object->CompanyPhone ;
		$object->CompanyDeptPhoneCustServ = isset($data->company_support_phone) ? $data->company_support_phone : $object->CompanyPhone;


		$object->CardNumber				= !empty($data->card_number) ? eCash_Crypt::getInstance()->decrypt($data->card_number) :$data->card_number;
		$object->CardName				= $data->company_card_name;
		$object->CardProvBankName 		= $data->company_card_prov_bank; // Company's Stored Value card provider's full name
		$object->CardProvBankShort 		= $data->company_card_prov_bank; // Company's Stored Value card provider's short name
		$object->CardProvServName 		= $data->company_card_prov_serv; // Company's Stored Value card provider's provider's service
		$object->CardProvServPhone 		= $data->company_card_prov_serv_phone; // Company's Stored Value card provider's provider's service provider'ss phoen number

		$object->MoneyGramReceiveCode   = isset($data->moneygram_receive_code) ? $data->moneygram_receive_code : NULL;

		// Process Loan Data
//		$object->LoanCollectionCode 	= 'IMPACT-'.$data->application_id;
		$object->LoanCollectionCode 	= $data->company_collections_code;
		$object->LoanDocDate 			= date("m/d/Y"); // The date of the document as used in the loan documents.
		$object->LoanStatus 			= $data->application_status; //"*** FIX ME ***";

//		$object->LoanAPR 				= number_format($data->apr, 2, '.', '') . '%';
//		$object->LoanBalance 			= "$". number_format($data->current_payoff_amount, 2, '.','');
//		$object->LoanCurrPrinAmount 	= isset($data->current_principal_payoff_amount) ? "$". number_format($data->current_principal_payoff_amount, 2, '.','') : "$0.00";
//		$object->LoanDueDate 			= isset($data->next_due_date) ? $data->next_due_date : "";
//		$object->LoanFinCharge 			= isset($data->next_service_charge_amount) ? "$". number_format($data->next_service_charge_amount, 2, '.', '') : "";
//		$object->LoanFundAmount 		= "$". number_format($data->fund_amount, 2, '.', '');
//		$object->LoanFundDate 			= $data->date_fund_estimated_month . '-' . $data->date_fund_estimated_day . '-' . $data->date_fund_estimated_year;
		$object->LoanFundAvail 			= date('m-d-Y',  ($data->current_fund_date) ? $data->current_fund_date : $data->date_fund_stored  );
		$object->LoanPayoffDate 		= isset($data->current_due_date)? date('m/d/Y',$data->current_due_date) : $data->date_first_payment;
// not used		$object->LoanRefAmount			= isset($data->finance_charge) ? "$". number_format($data->finance_charge,2,'.','') : "$0.00"; //'*** FIX ME ***';

		$object->LoanCurrAPR			= number_format($data->current_apr, 2, '.', '') . '%'; // calculated from current balance & current fin charge
		$object->LoanCurrPrincipal		= $this->Format_Money($data->current_principal_payoff_amount); //the current principal payoff amount
		$object->LoanCurrBalance		= $this->Format_Money($data->current_total_due); // current principal payment + current fin ch
		$object->LoanCurrFinCharge		= $this->Format_Money($data->current_service_charge); // finance charge of this upcoming debit event
		$object->LoanCurrPrinPmnt		= $this->Format_Money($data->current_principal); // principal payment amount of this upcoming debit event
		$object->LoanCurrDueDate		= date('m/d/Y',$data->current_due_date); // due date of upcoming debit event
		$object->LoanCurrFees			= $this->Format_Money(0); // any currently owed fees

		$object->LoanNextAPR			= number_format($data->next_apr, 2, '.', '') . '%';  // calculated from next balance & next fin charge
		$object->LoanNextPrincipal		= $this->Format_Money($data->next_principal_payoff_amount); //the current principal payoff amount
		$object->LoanNextBalance		= $this->Format_Money($data->next_total_due); // next principal + next fin ch
		$object->LoanNextFinCharge		= $this->Format_Money($data->next_service_charge); // finance charge of the debit event following the current
		$object->LoanNextPrinPmnt		= $this->Format_Money($data->next_principal); // principal amount of the debit event following the current
		$object->LoanNextDueDate		= date('m/d/Y',$data->next_due_date); // due date of debit event following the current
		$object->LoanNextFees			= $this->Format_Money(0); // any fees as of the next event
		$object->LoanInterestAccrued	= self::Format_Money($data->interest_accrued);
		$data->current_service_charge = isset($data->current_service_charge) ? $data->current_service_charge : null;
		$service_charge = !empty($data->estimated_service_charge) ? $data->estimated_service_charge : $data->current_service_charge;
		$object->LoanFinCharge			= self::Format_Money($service_charge, 0); // Curr if exists, else from DB
		$object->LoanFinanceCharge		= self::Format_Money($service_charge, 0); // Curr if exists, else from DB

		$object->LoanAPR				= ($data->current_apr) ? $object->LoanCurrAPR : number_format($data->apr, 2, '.', '') . '%' ; // Curr if exists, else from DB
		$object->LoanBalance			= $this->Format_Money($data->current_payoff_amount, $data->payment_total); // Curr if exists, else from DB
		$object->LoanFinCharge			= $this->Format_Money($data->current_service_charge, $data->finance_charge); // Curr if exists, else from DB
		$object->LoanPrincipal			= $this->Format_Money($data->current_principal_payoff_amount, $data->fund_amount); // Curr if exists, else from DB
		//$object->LoanDueDate			= ($data->current_due_date) ? $data->current_due_date : $data->date_first_payment ; // Curr if exists, else from DB
		$object->LoanDueDate			= ($data->current_due_date) ? date('m/d/Y',$data->current_due_date) : (($data->due_date_inactive) ? $data->due_date_inactive : $data->date_first_payment); // Curr if exists, then due_date_inactive, else from DB; mantis:5924
		$object->LoanFees				= $this->Format_Money(0); // Curr if exists, else from DB
		$object->LoanFundAmount			= $object->LoanPrincipal;
		$object->LoanFundDate			= ($data->current_fund_date) ? date('m/d/Y',$data->current_fund_date) : $data->date_fund_estimated_month . '-' . $data->date_fund_estimated_day . '-' . $data->date_fund_estimated_year;
		
//		$object->LoanOrigFundAmount		= $this->Format_Money(); // Original balance, from schedule or db
//		$object->LoanOrigFundDate		= ""; // original date_event of funding schedule.. or db
//		$object->LoanOrigFundAvail		= ""; // Original date_Effective of schedule, or est_fund_date from db

//		$object->TotalOfPayments		= $this->Format_Money(); // Sum of payments of the entire schedule, or est from Qualify.2
//		$object->TotalPaymentsToDate	= $this->Format_Money(); // Sum of payments to date, from schedule
//		$object->TotalPaymentsFromDate	= $this->Format_Money(); // Sum of payments after date, or est from Qualify.2

		$object->PaymentArrAmount		= $this->Format_Money($data->next_arrangement_payment);
		$object->PaymentArrDate			= $data->next_arrangement_due_date;
		$object->PaymentArrType			= $data->next_arrangement_type;

		$object->MissedArrAmount		= $this->Format_Money($data->past_arrangement_payment);
		$object->MissedArrDate			= $data->past_arrangement_due_date;
		$object->MissedArrType			= $data->past_arrangement_type;

		$object->PDAmount				= $this->Format_Money($data->current_principal);
		$object->PDFinCharge			= $this->Format_Money($data->current_service_charge);
		$object->PDTotal				= $this->Format_Money($data->current_total_due);
		$object->PDDueDate				= ($data->current_due_date) ? date('m/d/Y',$data->current_due_date) : $data->date_first_payment;

		$object->PDNextAmount			= $this->Format_Money($data->next_principal);
		$object->PDNextFinCharge		= $this->Format_Money($data->next_service_charge);
		$object->PDNextTotal			= $this->Format_Money($data->next_total_due);
		$object->PDNextDueDate			= ($data->next_due_date) ? date('m/d/Y',$data->next_due_date) : "Not Scheduled";


		$object->RefinanceAmount		= $this->Format_Money(0);
		$object->ReturnFee 				= $this->Format_Money($data->business_rules['return_transaction_fee']);
		$object->PrincipalPaymentAmount	= $this->Format_Money($data->business_rules['principal_payment_amount']);
		$object->ReturnReason 			= empty($data->reason_for_ach_return) ? 'for review' : $data->reason_for_ach_return; //'*** FIX ME ***';

		$object->TotalOfPayments 		='$' . number_format($data->payment_total, 2); // The total amount paid as used in the loan documents

		//Misc
		$object->Today 					= date("m/d/Y"); // Today's Date
		$object->GenericSubject			= $this->generic_email["subject"];
		$object->GenericMessage			= $this->generic_email["message"];
		$object->SenderName				= $this->generic_email["sender"];

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
		if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_transfer', 'payment_fee_transfer', 'writeoff_fee_transfer')) == TRUE)
		{
			$WireTransferFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_transfer','payment_fee_transfer', 'writeoff_fee_transfer'));
		}
		$principal_fees = bcadd($WireTransferFee, $principal_fees, 2);
		$object->WireTransferFee = self::Format_Money($WireTransferFee);

		// GF 8293 Check if Title loan has event types relating to delivery fees, if so, total them up and return that, else use the old method.
       	if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_delivery', 'payment_fee_delivery', 'writeoff_fee_delivery')) == TRUE)
       	{
           	$DeliveryFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_delivery','payment_fee_delivery', 'writeoff_fee_delivery'));
       	}
       	$principal_fees = bcadd($DeliveryFee, $principal_fees, 2);
		$object->DeliveryFee = self::Format_Money($DeliveryFee);
		
		//GF 5429 Only show Lien fee for Title Loans
		//GF 6334 Check if Title loan has event types relating to lien fees, if so, total them up and return that, else use the old
		//        method.
		if ($this->Application_Has_Events_By_Event_Names( array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien')) == true)
		{
			$TitleLienFee = $this->Fetch_Balance_Total_By_Event_Names(array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien'));
		}
		$principal_fees = bcadd($TitleLienFee, $principal_fees, 2);
		$object->TitleLienFee = self::Format_Money($TitleLienFee);
		
		$fees = $data->fee_balance; 
		$object->NetLoanProceeds		= self::Format_Money($data->fund_amount); //@TODO: Account for fees

		//NetProceedsAmount is used in the Delaware Payday Loan documents, but wasn't being populated with anything
		$object->NetProceedsAmount = $object->NetLoanProceeds;
		
		$object->LoanFundAmount	= self::Format_Money($data->fund_amount + $principal_fees);
		
		if ($data->current_principal_payoff_amount && (($object->LoanStatus == "Inactive (Paid)") || ($data->current_principal_payoff_amount > 0)) || ((!isset($data->fee_balance) || $data->fee_balance == 0) && $data->current_principal_payoff_amount))
		{
			$principal = $data->current_principal_payoff_amount;
		}
		else
		{	
			$principal = $data->fund_amount + $principal_fees + $fees;
		}
		
		$object->LoanPrincipal			= self::Format_Money($principal);
		$object->LoanBalance			= self::Format_Money($principal + $service_charge);
		
		$object->CustomerCounty			= $data->customer_county;
		$object->CompanyCounty			= $stat_pass = eCash_Config::getInstance()->COMPANY_COUNTY;
		
		$balance = $data->current_payoff_amount ? $data->current_payoff_amount : $data->payment_total;
		$object->SettlementOffer		= self::Format_Money($balance * $data->business_rules['settlement_offer']/100); 
		
		$object->LoanRefAmount			= &$object->LoanFinCharge;
		$object->LoanCurrPrinAmount		= &$object->LoanPrincipal;
		
		$object->MoneyGramReference		= str_replace("Check # ", '', isset($data->check_number) ? $data->check_number : null);
		
		$object->PaymentDate			= date('m/d/Y', strtotime($data->last_payment_date));
		$object->PaymentPostedAmount    = self::Format_Money($data->last_payment_amount);

	
		$object->NextBusinessDay		= date("m/d/Y", strtotime($this->pdc->Get_Business_Days_Forward(date("Y-m-d"), 1)));
		$object->CompanyClientEmail = $data->company_pre_support_email;
		$object->CompanyClientFax = $data->company_pre_support_fax;
		$object->CompanyClientPhone = $data->company_pre_support_phone;
		
		$object->CompanyCustEmail = $data->company_support_email;
		$object->CompanyCustFax = $data->company_support_fax;
		$object->CompanyCustPhone = $data->company_support_phone;
		
		$object->CompanyCollEmail = $data->company_collections_email;
		$object->CompanyCollFax = $data->company_collections_fax;
		$object->CompanyCollPhone = $data->company_collections_phone;
		
		$object->LoanNoticeDays = $data->loan_notice_days;
		$object->LoanNoticeTime = $data->LoanNoticeTime;		
		
		$object->TimeCSMFOpen = $data->TimeCSMFOpen;
		$object->TimeCSMFClose = $data->TimeCSMFClose;
		$object->TimeCSSatOpen = $data->TimeCSSatOpen;
		$object->TimeCSSatClose = $data->TimeCSSatClose;
		$object->TimeZoneCS =   $data->TimeZoneCS;

		return $object;	
	}
	


	/**
	 * Format Money
	 * 
	 * Field format for Currencey
	 *
	 * @param int $value
	 * @param unknown_type $default
	 * @return string
	 */
	private function Format_Money($value, $default = NULL)
	{
		if ($value && (is_numeric( (string) $value) || is_numeric($value))) {
			return money_format('%.2n', (float) $value);
			
		} elseif ($value && preg_match('/\$\d+\.\d{2}/',$value)) {
			return $value;
			
		} elseif (!$value && $default != NULL) {
			return $this->Format_Money($default);
			
		} else {
			return money_format('%.2n', (float) 0);
		}
	}

	/**
	 * Format Phone
	 * 
	 * Field format for Phone Numbers
	 *
	 * @param string $value
	 * @param unknown_type $incl_iac
	 * @return string
	 */
	private function Format_Phone($value, $incl_iac = FALSE)
	{
		preg_match("/1?(\d{3})(\d{3})(\d{4})/", preg_replace("/\D/","",$value), $matches);
		array_shift($matches);
		
		if ( strlen(implode("",$matches)) != 10 )
		{
			return $value;
//			throw new InvalidArgumentException(__METHOD__ . "() Error: {$value} is not a valid US formatted phone number.");
		}
		
		return ( ($incl_iac) ? "1 " : "" ) . "({$matches[0]}) {$matches[1]}-{$matches[2]}";
		
	}
}

?>
