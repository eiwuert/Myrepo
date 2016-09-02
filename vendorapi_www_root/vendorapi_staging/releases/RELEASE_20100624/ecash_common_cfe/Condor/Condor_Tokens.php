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
//require_once("prpc/client.php");
require_once('business_rules.class.php');
require_once("config.6.php");
require_once("mysql.4.php");
require_once("qualify.2.php");
require_once("pay_date_calc.3.php");

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
		try {
			$this->site_config = $this->config_6->Get_Site_Config($this->campaign_info['license_key'], $this->campaign_info['promo_id'], $this->campaign_info['promo_sub_code']);
		}
		catch (Exception $e)
		{
			ECash::getLog()->Write("Unable to get Siteconfig: " . $e->getMessage());
			ECash::getLog()->Write($e->getTraceAsString());
		}
		$this->site_config = NULL;
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
	public function Map_Condor_Data($data, $check_for_disburs = TRUE)
	{				
		preg_match('/(\d{3})(\d{2})(\d{4})/', $data->ssn, $ssn_matches);
		if (isset($data->references) && is_array($data->references))
		{
			$references = $data->references;
		}
		else
		{
			$references = $this->Fetch_References($data->application_id);
		}

		$esig_site = isset($data->new_app_url) ? split("\?",$data->new_app_url) : array();

		$object = new stdclass;
		// retrieved customer data
		$object->CustomerCity 			= ucwords($data->city); // Customer City
		$object->CustomerCounty 		= ucwords($data->county); // Customer City
		$object->CustomerDOB 			= $data->dob;
		$object->CustomerEmail 			= $data->customer_email;
		$object->CustomerESig 			= ""; //strtoupper(trim($data->name_first) . ' ' . trim($data->name_last)); //"*** FIX ME ***";
		$object->CustomerESigIPAddress		= $data->esig_ip_address;
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
		$object->EmployerLength 		= $data->EmployerLength;
		$object->EmployerName			= ucwords($data->employer_name); // Customer's Employer
		$object->EmployerPhone 			= $this->Format_Phone($data->phone_work ); // Customer Employer Phone
		$object->EmployerShift 			= ucwords($data->shift); // The customer's work shift or hours as used in the load documents
		$object->EmployerTitle 			= ucwords($data->job_title);
		$object->IncomeDD 				= ($data->income_direct_deposit == "yes") ? "TRUE" : "FALSE";
		$object->IncomeFrequency 		= ucwords(str_replace("_", " ", strtolower($data->income_frequency)));
		$object->IncomeMonthlyNet 		= $this->Format_Money($data->income_monthly); // number_format($data->income_monthly, 0, '.', '');
		$object->IncomeNetPay 			= $this->Format_Money($this->Calculate_Monthly_Net($data->income_frequency, $data->income_monthly)); // number_format($data->income_monthly, 0, '.', '');
		$object->BankABA				= $data->bank_aba;
		$object->BankAccount			= $data->bank_account;
		$object->BankName 				= ucwords($data->bank_name);
		$object->BankAccountType		= strtolower($data->bank_account_type);
		$object->IncomeType 			= strtolower($data->income_source);
		$object->LoanApplicationID 		= $data->application_id;
		$object->LoanDateCreated		= isset($data->date_app_created) ? $data->date_app_created : '';
		$object->LoanTimeCreated		= isset($data->time_app_created) ? $data->time_app_created : '';
		$object->CustomerIPAddress		= isset($data->client_ip_address) ? $data->client_ip_address : '';
		$object->CustomerInitialPayDown = '';
		$object->CustomerInitialInFull = '';
		$is_military = (strcasecmp($data->income_source, 'military') === 0);

		$object->MilitaryYes = $is_military ? 'X' : '';
		$object->MilitaryNo = $is_military ? '' : 'X';

		$object->CustomerCoBorrower		= empty($data->co_borrower) ? '' : $data->co_borrower;

		$object->CustomerCoBorrower		= empty($data->co_borrower) ? '' : $data->co_borrower;

		if (isset($data->crypt_password) && strlen($data->crypt_password))
		{
			if(strlen($data->crypt_password) > 30)
			{
				$decrypted_password = Crypt_3::Decrypt($data->crypt_password);
				if(strlen($decrypted_password) < 30)
				{
					$object->Password = $decrypted_password;
				}
				else
				{
					$object->Password = "Decryption Error";
				}
			}
			else
			{
				$object->Password = $data->crypt_password;
			}
		}
		else 
		{
			$object->Password= 'UNKNOWN';
		}		
		$object->Username 				= $data->login_id;

		// derived customer data
		$object->ConfirmLink			= &$object->eSigLink; //"*** FIX ME ***";
		$object->GenericEsigLink 		= &$object->eSigLink;
		$object->eSigLink 	 		= $data->esig_url;
		$object->ReactLink			= $data->react_link;
		$object->AGEANeSigLink			= isset($data->agean_esig_url) ? $data->agean_esig_url : '';
		$object->CSLoginLink			= $data->cs_login_link;
		$object->SpamLink			= $data->spam_link;

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

			$object->$name_2 			= ucwords($ref->name_full);
			$object->$phone_2 			= $this->Format_Phone( $ref->phone_home );
			$object->$relationship_2 	= ucwords($ref->relationship);

			$ref_num++;
		}

		//Company Data
		$site_config = $this->Get_Site_Config();
		$object->CompanyCity			= ($data->company_addr_city) ? $data->company_addr_city: NULL; //"*** FIX ME ***"; // Company's City
		$object->CompanyDept			= ($data->company_dept_name) ? $data->company_dept_name : NULL; //"*** FIX ME ***"; // Company Department handling loans
		$object->CompanyCounty			= !empty($data->company_addr_county) ? $data->company_addr_county: NULL; //"*** FIX ME ***"; // Company's County
		$object->CompanyEmail 			= isset($data->company_email) ? $data->company_email : (isset($this->Get_Site_Config()->company_email) ? $this->Get_Site_Config()->company_email : $this->Get_Site_Config()->customer_service_email); // Customer Service email address
		$object->CompanyFax 			= isset($data->company_fax) ? $data->company_fax : $this->Get_Site_Config()->support_fax; // Main fax number
		$object->CompanyInit 			= !empty($site_config) ? $site_config->property_short : NULL; // Company Initials (property short)
		$object->CompanyLogoLarge		= isset($data->company_logo_large) ? '<img src="'.$data->company_logo_large.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyLogoSmall		= isset($data->company_logo_small) ? '<img src="'.$data->company_logo_small.'">' : NULL; //"*** FIX ME ***";
		$object->CompanyName			= isset($data->company_name) ? $data->company_name : NULL;
		$object->CompanyNameFormal		= isset($data->company_name_formal) ? $data->company_name_formal : NULL;
		$object->CompanyNameLegal		= isset($data->company_name_legal) ? $data->company_name_legal : NULL; //"*** FIX ME ***";
		$object->CompanyNameShort		= isset($data->company_name_short) ? $data->company_name_short : NULL; //"*** FIX ME ***";
		$object->CompanyPhone 			= isset($data->company_support_phone) ? $data->company_support_phone : $this->Get_Site_Config()->support_phone; // Customer Service phone number
		$object->CompanyPromoID			= $this->campaign_info['promo_id']; //The promo ID of the company
		$object->CompanyState			= isset($data->company_addr_state) ? $data->company_addr_state : NULL; //"*** FIX ME ***"; // Company State
		$object->CompanyStreet			= isset($data->company_addr_street) ? $data->company_addr_street : NULL; //"*** FIX ME ***"; // Company Street
		$object->CompanySupportFax 		= isset($data->company_support_fax) ? $data->company_support_fax : NULL; //"*** FIX ME ***"; // Company Support Fax
		$object->CompanyUnit 			= isset($data->company_addr_unit) ? $data->company_addr_unit : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyWebSite 		= isset($data->company_site) ? $data->company_site : NULL; //"*** FIX ME ***"; // Company's unit Address
		$object->CompanyZip				= isset($data->company_addr_zip) ? $data->company_addr_zip : NULL; //"*** FIX ME ***"; // Company's Zip Code
		$object->LoginID = $object->LoginId = strtoupper($this->Get_Login_ID()); //"*** FIX ME ***";
		$object->SourcePromoID			= $this->campaign_info['promo_id']; //The promo ID of the company
		$object->SourceSiteName 		= $this->campaign_info['url']; // URL of the enterprise site as used in the loan documents

		$object->CompanyDeptPhoneCollections = isset($data->company_collections_phone) ? $data->company_collections_phone : $object->CompanyPhone ;
		$object->CompanyDeptPhoneCustServ = isset($data->company_support_phone) ? $data->company_support_phone : $object->CompanyPhone;
		
		///////////////
		$object->CompanyLegalName		= isset($data->company_name_legal) ? $data->company_name_legal : 'Clear Lake Holdings';
		$object->CompanyStreet			= isset($data->company_addr_street) ? $data->company_addr_street : '621 Medicine Way Suite 3';
		$object->CompanyCity			= isset($data->company_addr_city) ? $data->company_addr_city: 'Ukiah';
		$object->CompanyState			= isset($data->company_addr_state) ? $data->company_addr_state : 'CA';
		$object->CompanyZip			= isset($data->company_addr_zip) ? $data->company_addr_zip : '95482';
		
		$object->CompanyLegalAddress		= isset($data->company_addr_street) ? $data->company_addr_street : '621 Medicine Way Suite 3';
		$object->CompanyLegalCity		= isset($data->company_addr_city) ? $data->company_addr_city: 'Ukiah';
		$object->CompanyLegalState		= isset($data->company_addr_state) ? $data->company_addr_state : 'CA';
		$object->CompanyLegalZip		= isset($data->company_addr_zip) ? $data->company_addr_zip : '95482';
		
		$object->CompanyWebSite1		= 'https://someloancompany.com';
		////////////////

		$object->CardNumber				= !empty($data->card_number) ? eCash_Crypt::getInstance()->decrypt($data->card_number) : NULL;
		$object->CardName				= isset($data->company_card_name) ? $data->company_card_name : NULL;
		$object->CardProvBankName 		= isset($data->company_card_prov_bank) ? $data->company_card_prov_bank : NULL; // Company's Stored Value card provider's full name
		$object->CardProvBankShort 		= isset($data->company_card_prov_bank) ? $data->company_card_prov_bank : NULL; // Company's Stored Value card provider's short name
		$object->CardProvServName 		= isset($data->company_card_prov_serv) ? $data->company_card_prov_serv : NULL; // Company's Stored Value card provider's provider's service
		$object->CardProvServPhone 		= isset($data->company_card_prov_serv_phone) ? $data->company_card_prov_serv_phone : NULL; // Company's Stored Value card provider's provider's service provider'ss phoen number

		$object->MoneyGramReceiveCode   = isset($data->moneygram_receive_code) ? $data->moneygram_receive_code : NULL;

		// Process Loan Data
//		$object->LoanCollectionCode 	= 'IMPACT-'.$data->application_id;
		$object->LoanCollectionCode 	= isset($data->company_collections_code) ? $data->company_collections_code : NULL;
		$object->LoanDocDate 			= date("m/d/Y"); // The date of the document as used in the loan documents.
		$object->LoanStatus 			= $data->application_status; //"*** FIX ME ***";

//		$object->LoanAPR 				= number_format($data->apr, 2, '.', '') . '%';
//		$object->LoanBalance 			= "$". number_format($data->current_payoff_amount, 2, '.','');
//		$object->LoanCurrPrinAmount 	= isset($data->current_principal_payoff_amount) ? "$". number_format($data->current_principal_payoff_amount, 2, '.','') : "$0.00";
//		$object->LoanDueDate 			= isset($data->next_due_date) ? $data->next_due_date : "";
//		$object->LoanFinCharge 			= isset($data->next_service_charge_amount) ? "$". number_format($data->next_service_charge_amount, 2, '.', '') : "";
//		$object->LoanFundAmount 		= "$". number_format($data->fund_amount, 2, '.', '');
//		$object->LoanFundDate 			= $data->date_fund_estimated_month . '-' . $data->date_fund_estimated_day . '-' . $data->date_fund_estimated_year;

		/**
		 * LoanFundAvail is not currently used by any eCash Commercial customers,
		 * but it's assumed the value should reflect when the customer should receive
		 * their loan disbursement, so it's being set to the Due Date on the loan disbursement
		 * or at least what that is estimated to be. [BR]
		 */
		$object->LoanFundAvail 			= date('m-d-Y',  strtotime($data->fund_due_date));

		$object->LoanPayoffDate 		= isset($data->current_due_date)? date('m/d/Y', strtotime($data->current_due_date)) : $data->date_first_payment;
// not used		$object->LoanRefAmount			= isset($data->finance_charge) ? "$". number_format($data->finance_charge,2,'.','') : "$0.00"; //'*** FIX ME ***';

		//Setting these tokens to the same values so I don't have to break the scheduling code that retrieves info, but this is done to match old token generation
		//So even though current values should be different than the next set of scheduled values they are not because the tokens in the documents setup already use them 
		//interchangibly because that was the hip and cool thing to do, and fixing them would fuck companies shit up 
		$object->LoanNextAPR = $object->LoanCurrAPR			= number_format($data->current_apr, 2, '.', '') . '%'; // calculated from current balance & current fin charge
		$object->LoanNextPrincipal = $object->LoanCurrPrincipal		= isset($data->current_principal_payoff_amount) ? $this->Format_Money($data->current_principal_payoff_amount) : NULL; //the current principal payoff amount
		$object->LoanNextFinCharge = $object->LoanCurrFinCharge		= isset($data->current_service_charge) ? $this->Format_Money($data->current_service_charge) : NULL; // finance charge of this upcoming debit event
		$object->LoanNextPrinPmnt = $object->LoanCurrPrinPmnt		= isset($data->current_principal) ? $this->Format_Money($data->current_principal) : NULL; // principal payment amount of this upcoming debit event
		$object->LoanNextDueDate = $object->LoanCurrDueDate		= isset($data->current_due_date) ? $data->current_due_date : null ; // due date of upcoming debit event
		$object->LoanNextFees = $object->LoanCurrFees			= $this->Format_Money(0); // any currently owed fees
		$funding_loan_balance = $data->fund_amount + (isset($data->current_service_charge) ? $data->current_service_charge : 0);
		$object->LoanNextBalance = $object->LoanCurrBalance		= self::Format_Money(isset($data->current_payoff_amount) ? $data->current_payoff_amount : null, $funding_loan_balance); // current principal payment + current fin ch

		//$object->LoanNextAPR			= number_format($data->next_apr, 2, '.', '') . '%';  // calculated from next balance & next fin charge
		//$object->LoanNextPrincipal		= $this->Format_Money($data->next_principal_payoff_amount); //the current principal payoff amount
		//$object->LoanNextBalance		= $this->Format_Money($data->next_total_due); // next principal + next fin ch
		//$object->LoanNextFinCharge		= $this->Format_Money($data->next_service_charge); // finance charge of the debit event following the current
		//$object->LoanNextPrinPmnt		= $this->Format_Money($data->next_principal); // principal amount of the debit event following the current
		//$object->LoanNextDueDate		= isset($data->next_due_date) ? $data->next_due_date : null; // due date of debit event following the current
		//$object->LoanNextFees			= $this->Format_Money(0); // any fees as of the next event
		$object->LoanInterestAccrued	= self::Format_Money($data->interest_accrued);
		$data->current_service_charge = isset($data->current_service_charge) ? $data->current_service_charge : null;
		$service_charge = !empty($data->estimated_service_charge) ? $data->estimated_service_charge : $data->current_service_charge;
		$object->LoanFinCharge			= self::Format_Money($service_charge, 0); // Curr if exists, else from DB
		$object->LoanFinChargeMax = self::Format_Money($data->loan_fin_charge_max, 0);
		$object->LoanFinChargeAdd = self::Format_Money($data->loan_fin_charge_max - $service_charge, 0);
		$object->TotalOfPaymentsMax = self::Format_Money($data->total_of_payments_max, 0);
		$object->LoanFinanceCharge		= self::Format_Money($service_charge, 0); // Curr if exists, else from DB

		$object->LoanAPR				= ($data->current_apr) ? $object->LoanCurrAPR : number_format($data->apr, 2, '.', '') . '%' ; // Curr if exists, else from DB
		$object->MaxAPR = isset($data->max_apr) ? number_format($data->max_apr, 2, '.', '') . '%' : NULL;
		//$object->LoanBalance			= isset($data->current_payoff_amount) ? $this->Format_Money($data->current_payoff_amount, $data->payment_total) : NULL; // Curr if exists, else from DB
		//$object->LoanFinCharge			= $this->Format_Money($data->current_service_charge, $data->finance_charge); // Curr if exists, else from DB
		$object->LoanPrincipal			= isset($data->current_principal_payoff_amount) ? $this->Format_Money($data->current_principal_payoff_amount, $data->fund_amount) : NULL; // Curr if exists, else from DB
		//$object->LoanDueDate			= ($data->current_due_date) ? $data->current_due_date : $data->date_first_payment ; // Curr if exists, else from DB
		$object->LoanDueDate			= isset($data->current_due_date) ? date('m/d/Y',strtotime($data->current_due_date)) : (isset($data->due_date_inactive) ? $data->due_date_inactive :	$data->date_first_payment); // Curr if exists, then due_date_inactive, else from DB; mantis:5924
		$object->LoanFees				= isset($data->fee_balance) ? self::Format_Money($data->fee_balance) : NULL; // Curr if exists, else from DB
		$object->LoanFundAmount			= $object->LoanPrincipal;
	//	$object->LoanFundDate			= ($data->current_fund_date) ? date('m/d/Y',$data->current_fund_date) : $data->date_fund_estimated_month . '/' . $data->date_fund_estimated_day . '/' . $data->date_fund_estimated_year;
		$object->LoanFundDate			= date('m/d/Y', strtotime($data->fund_action_date));
		$object->LoanFundDate2			= date('m/d/Y', strtotime($data->fund_due_date));		
//		$object->LoanOrigFundAmount		= $this->Format_Money(); // Original balance, from schedule or db
//		$object->LoanOrigFundDate		= ""; // original date_event of funding schedule.. or db
//		$object->LoanOrigFundAvail		= ""; // Original date_Effective of schedule, or est_fund_date from db

//		$object->TotalOfPayments		= $this->Format_Money(); // Sum of payments of the entire schedule, or est from Qualify.2
//		$object->TotalPaymentsToDate	= $this->Format_Money(); // Sum of payments to date, from schedule
//		$object->TotalPaymentsFromDate	= $this->Format_Money(); // Sum of payments after date, or est from Qualify.2
		$object->LoanCancellationDelay  = isset($data->business_rules['cancelation_delay']) ? $data->business_rules['cancelation_delay'] : NULL;
		$object->PaymentArrAmount		= isset($data->next_arrangement_payment) ? $this->Format_Money($data->next_arrangement_payment) : NULL;
		$object->PaymentArrDate			= isset($data->next_arrangement_due_date) ? $data->next_arrangement_due_date : NULL;
		$object->PaymentArrType			= isset($data->next_arrangement_type) ? $data->next_arrangement_type : NULL;

		$object->MissedArrAmount		= isset($data->past_arrangement_payment) ? $this->Format_Money($data->past_arrangement_payment) : NULL;
		$object->MissedArrDate			= isset($data->past_arrangement_due_date) ? $data->past_arrangement_due_date : NULL;
		$object->MissedArrType			= isset($data->past_arrangement_type) ? $data->past_arrangement_type : NULL;

		//Setting these tokens to the same values so I don't have to break the scheduling code that retrieves info, but this is done to match old token generation
		//So even though current values should be different than the next set of scheduled values they are not because the tokens in the documents setup already use them 
		//interchangibly because that was the hip and cool thing to do, and fixing them would fuck companies shit up 
		$object->PDNextAmount = $object->PDAmount				= isset($data->current_principal) ? $this->Format_Money($data->current_principal) : NULL;
		$object->PDNextFinCharge = $object->PDFinCharge			= $this->Format_Money($data->current_service_charge);
		$object->PDNextTotal = $object->PDTotal				= isset($data->current_total_due) ? $this->Format_Money($data->current_total_due) : NULL;
		$object->PDNextDueDate = $object->PDDueDate				= isset($data->current_due_date) ? date('m/d/Y', strtotime($data->current_due_date)) : "Not Scheduled";

		//$object->PDNextAmount			= $this->Format_Money($data->next_principal);
		//$object->PDNextFinCharge		= $this->Format_Money($data->next_service_charge);
		//$object->PDNextTotal			= $this->Format_Money($data->next_total_due);
		//$object->PDNextDueDate			= ($data->next_due_date) ? date('m/d/Y', strtotime($data->next_due_date)) : "Not Scheduled";

		$object->LastPaymentDate		= isset($data->last_payment_date) ? $data->last_payment_date : NULL;
		$object->LastPaymentAmount		= isset($data->last_payment_amount) ? $this->Format_Money($data->last_payment_amount) : NULL;
		$object->CardAuthCode			= isset($data->card_auth_code) ? $data->card_auth_code : NULL;

		$object->RefinanceAmount		= $this->Format_Money(0);
		$object->ReturnFee 				= $this->Format_Money($data->business_rules['return_transaction_fee']);
		$object->PrincipalPaymentAmount	= $this->Format_Money($this->Get_Payment_Amount($data->business_rules, $data->fund_amount));
		$object->ReturnReason 			= empty($data->reason_for_ach_return) ? 'for review' : $data->reason_for_ach_return; //'*** FIX ME ***';

		//Misc
		$object->Today 					= date("m/d/Y"); // Today's Date
		$object->GenericSubject			= $this->generic_email["subject"];
		$object->GenericMessage			= $this->generic_email["message"];
		$object->SenderName				= $this->generic_email["sender"];

		$object->VIN					= isset($data->vehicle_vin) ? $data->vehicle_vin : NULL;
		$object->Year					= isset($data->vehicle_year) ? $data->vehicle_year : NULL;
		$object->Model					= isset($data->vehicle_model) ? $data->vehicle_model : isset($data->vehicle_series) ? $data->vehicle_series : NULL;
		$object->Make					= isset($data->vehicle_make) ? $data->vehicle_make : NULL;
		$object->VehicleMileage			= isset($data->vehicle_mileage) ? $data->vehicle_mileage : NULL;
		
		$object->AccountRep				= isset($data->agent_name) ? $data->agent_name : NULL;
		$object->CustomerResidenceLength = $data->CustomerResidenceLength;

		$object->WireTransferFee = $this->Format_Money($data->WireTransferFee);
		$object->DeliveryFee = $this->Format_Money($data->DeliveryFee);
		$object->TitleLienFee = $data->TitleLienFee;
		
		$fees = isset($data->fee_balance) ? $data->fee_balance : NULL; 
		$object->NetLoanProceeds		= self::Format_Money($data->fund_amount); //@TODO: Account for fees

		//NetProceedsAmount is used in the Delaware Payday Loan documents, but wasn't being populated with anything
		$object->NetProceedsAmount = $object->NetLoanProceeds;

		$object->ACHDisbursementAmount = self::Format_Money($data->fund_amount - $data->converted_principal_bal_amount);
		
		$object->LoanFundAmount	= self::Format_Money($data->fund_amount + $data->principal_fees);
		
		//tribal
		$object->TribalIP = isset($data->tribal_ip) ? $data->tribal_ip : NULL;
		$object->TribalResponseDateTime = isset($data->tribal_response_date_time) ? date('m/d/Y H:i:s',  strtotime($data->tribal_response_date_time)) : NULL;

		if ($check_for_disburs)
		{
			//Because documents are being sent that contain balances in them before the loan has even been funded
			//we need to verify whether or not the loan disbursement has taken place yet. AGEAN LIVE #14863
			$funded_amount = $this->Fetch_Balance_Total_By_Event_Names(array('loan_disbursement','card_disbursement'));
		}
		//If there's a current principal amount due and the disbursement is pending or complete, we use the current principal amount due
		if (isset($data->current_principal_payoff_amount) && (($object->LoanStatus == "Inactive (Paid)") || ($data->current_principal_payoff_amount > 0)) && ($funded_amount))
		{
			$principal = $data->current_principal_payoff_amount;
		}
		//If not, we predict the principal amount by taking the application's fund amount and adding all the pending/complete principal fees to it.
		else
		{	
			$principal = $data->fund_amount + $data->principal_fees;
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
			$balance = $this->Fetch_Application_Balance($data);

			$service_charge = $balance->service_charge_pending + $fees;
		}
		
		//$object->LoanBalance			= self::Format_Money($principal + $service_charge);
		$object->LoanBalance = self::Format_Money($data->posted_total);
		// The total amount paid as used in the loan documents
		$object->TotalOfPayments        = self::Format_Money(isset($data->current_payoff_amount) ? $data->current_payoff_amount : ($principal + $service_charge + $fees));
		
		$object->CustomerCounty			= $data->customer_county;
		$object->CompanyCounty			= $stat_pass = ECash::getConfig()->COMPANY_COUNTY;
		
		$balance = isset($data->current_payoff_amount) ? $data->current_payoff_amount : $data->payment_total;
		$object->SettlementOffer		= self::Format_Money($balance * $data->business_rules['settlement_offer']['settlement_offer']/100);
		
		$object->LoanRefAmount			= &$object->LoanFinCharge;
		$object->LoanCurrPrinAmount		= &$object->LoanPrincipal;
		
		$object->MoneyGramReference		= str_replace("Check # ", '', isset($data->check_number) ? $data->check_number : null);
		
		$object->PaymentDate			= isset($data->last_payment_date) ? date('m/d/Y', strtotime($data->last_payment_date)) : NULL;
		$object->PaymentPostedAmount    = isset($data->last_payment_amount) ? self::Format_Money($data->last_payment_amount) : NULL;

		
		$object->NextBusinessDay		= date("m/d/Y", strtotime($this->pdc->Get_Business_Days_Forward(date("Y-m-d"), 1)));
		$object->CompanyClientEmail = $data->pre_support_email;
		$object->CompanyClientFax = $data->pre_support_fax;
		$object->CompanyClientPhone = $data->pre_support_phone;
		
		$object->CompanyCustEmail = $data->company_support_email;
		$object->CompanyCustFax = $data->company_support_fax;
		$object->CompanyCustPhone = $data->company_support_phone;
		
		$object->CompanyCollEmail = $data->collections_email;
		$object->CompanyCollFax = $data->collections_fax;
		$object->CompanyCollPhone = $data->collections_phone;
		
		$object->TimeCSMFOpen = $data->TimeCSMFOpen;
		$object->TimeCSMFClose = $data->TimeCSMFClose;
		$object->TimeCSSatOpen = $data->TimeCSSatOpen;
		$object->TimeCSSatClose = $data->TimeCSSatClose;
		$object->TimeZoneCS =   $data->TimeZoneCS;
		$object->Time		= date("h:ia");
		$object->Day 					= date("d"); // Today's Day
		
		$object->PDIncrement = $data->suggested_payment_increment;
		$object->AccountRep				= ECash::getAgent()->getFirstLastName();
	
		$object->RenewalDocument = 'document_1.pdf';
		$object->LoanNoticeDays = $data->loan_notice_days;
		$object->LoanNoticeTime = $data->LoanNoticeTime . ' ' . $data->TimeZoneCS;		
		$object->PDPercent = $data->paydown_percent;
		//CSO Tokens [#17240]
		$service_charge 				= !empty($data->estimated_service_charge) ? $data->estimated_service_charge : $data->current_service_charge;
		$object->CSOApplicationFee 		= isset($data->cso_assess_fee_app) ? self::Format_Money($data->cso_assess_fee_app) : NULL; //Value of eCash Business Rule w/ same name Ex �$30�
		$object->CSOBrokerFee 			= isset($data->cso_assess_fee_broker) ? self::Format_Money($data->cso_assess_fee_broker) : NULL; //Value of eCash Business Rule w/ same name Ex. �$90�
		$object->CSOLenderACHReturnFee 	= isset($data->lend_assess_fee_ach) ? self::Format_Money($data->lend_assess_fee_ach) : NULL; //Value of eCash Business Rule w/ same name Ex. �$20�
		// It is stupid we're attaching arbitrary formatting specifiers to token values rather than putting them in the document itself [benb]
		$object->CSOLenderInterest 		= isset($data->svc_charge_percentage) ? ($data->svc_charge_percentage . '%') : NULL; //Value of eCash Business Rule w/ same name Ex. �10%�
		$object->CSOLenderLateFee 		= isset($data->cso_assess_fee_late) ? $data->cso_assess_fee_late : NULL; //Value of eCash Business Rule w/ same name.  Ex. �$7.50 or 5% of the payment amount, whichever is greater�
		$object->CSOTotalFinanceCost 	= isset($data->cso_assess_fee_broker) ? self::Format_Money($service_charge + $data->cso_assess_fee_broker) : NULL; //$ sum of the values of CSO Broker Fee and Lender Interest business rules.  Ex. �$91.15� ($90 + $1.15)
		$object->CSOAmountFinanced 		= self::Format_Money($data->fund_amount); //$ sum of Loan Principal and CSO Broker Fee.  Ex. �$390.00� ($300 + $90)

		/**
		 * This value is currently only set if the loan type is CSO
		 * See Condor_Commercial::feeTokens()
		 */
		if(isset($data->loan_cancellation_date))
		{
			// Calendar date that a cancellation notice must be received by.  
			// Derived from the values of estimated funding date and the eCash 
			// business rule "Cancellation delay" Ex. 8/18/2008
			$object->LoanCancellationDate 	= date('m/d/Y', strtotime($data->loan_cancellation_date)); 
		}
		
		//CSO Tokens [#18142]
		$object->CSOBrokerFeePercent	= isset($data->business_rules['cso_assess_fee_broker']['percent_amount']) ? ($data->business_rules['cso_assess_fee_broker']['percent_amount'] . '%') : NULL;
		$object->CSOTotalOfPayments		= isset($data->cso_assess_fee_broker) ? self::Format_Money($data->fund_amount + $data->cso_assess_fee_broker + $service_charge) : NULL; //Total of Principal, CSO Fee, and Interest
		
		//Lender Legal Name [#18923]
		$object->CSOLenderNameLegal             = isset($data->cso_lender_name_legal) ? $data->cso_lender_name_legal : NULL;
		
		// Tokens added for HMS [#19277]
		$object->CompanyPaymentStreet           = isset($data->payment_street) ? $data->payment_street : NULL;
		$object->CompanyPaymentCity             = isset($data->payment_city) ?$data->payment_city  : NULL;
		$object->CompanyPaymentState            = isset($data->payment_state) ? $data->payment_state : NULL;
		$object->CompanyPaymentZip              = isset($data->payment_zip) ? $data->payment_zip : NULL;
		$object->CompanyPaymentBank             = isset($data->payment_bank) ? $data->payment_bank : NULL;
		$object->CompanyPaymentABA              = isset($data->payment_aba) ? $data->payment_aba : NULL;
		$object->CompanyPaymentAccount          = isset($data->payment_account) ? $data->payment_account : NULL;

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
	protected function Format_Money($value, $default = NULL)
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
	static public function Format_Phone($value, $incl_iac = false)
	{
		preg_match("/1?(\d{3})(\d{3})(\d{4})/", preg_replace("/\D/","",$value), $matches);
		array_shift($matches);

		if ( strlen(implode("",$matches)) != 10 )
		{
			return $value;
		}

		return ( ($incl_iac) ? "1 " : "" ) . "({$matches[0]}) {$matches[1]}-{$matches[2]}";
	}
}

?>
