<?php
require_once "crypt.3.php";

/**
 * Shared code for eCash AMG/Commercial for providing the LoanAPI
 *
 * @package ECash_Loan
 */
abstract class ECash_Service_Loan_API implements ECash_Service_Loan_IAPI
{
	/**
	 * @var ECash_Factory
	 */
	protected $ecash_factory;
	
	/**
	 * @var ECash_Service_Loan_ICustomerLoanProvider
	 */
	protected $ecash_api_factory;

	/**
	 * @var ECash_Service_Loan_ICustomerLoanProvider
	 */
	private $loan_provider;

	/**
	 * @var int
	 */
	private $company_id;
	
	/**
	 * @var string
	 */
	private $agent_login;

	/**
	 * @var bool
	 */
	private $use_web_services;

	public function __construct(
			ECash_Factory $ecash_factory,
			ECash_Service_Loan_IECashAPIFactory $ecash_api_factory,
			ECash_Service_Loan_ICustomerLoanProvider $loan_provider,
			$company_id,
			$agent_login,
			$use_web_services) {
		$this->ecash_factory = $ecash_factory;
		$this->ecash_api_factory = $ecash_api_factory;
		$this->loan_provider = $loan_provider;
		$this->company_id = $company_id;
		$this->agent_login = $agent_login;
		$this->use_web_services = $use_web_services;
	}

	protected function getCompanyID()
	{
		return $this->company_id;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#testConnection
	 * @return bool
	 */
	public function testConnection()
	{
		return TRUE;
	}
	
	public function getCustomerLoginInfo($application_id)
	{
		// setup the response object
		$response = new stdClass();
		$response->result = false;
		$response->error = NULL;
		$response->application_id = $application_id;
		$response->login = "";
		$response->password = "";
			
		$account_found = false;
		$applicantAccountInfo = NULL;
								
		try
		{
			$client = $this->ecash_factory->getWebServiceFactory()->getWebService('application');
				
			$applicantAccountInfo = $client->getApplicantAccountInfo($application_id);	
		}
		catch (Exception $e)
		{				
			$response->error = "service_error";								
			$this->insertLogEntry("Unable to load customer login info: " . $e->getMessage());
		}
						
			
		$account_found = (!empty($applicantAccountInfo) && 
				$applicantAccountInfo->application_id == $application_id && 
				$applicantAccountInfo->login != "");	
		
		if ($account_found)
		{
			$response->login = $applicantAccountInfo->login;
			$response->password = $applicantAccountInfo->password;
			
			if (empty($response->error))
			{
				$response->result = true;
			}			
		}
		else	
		{
			$response->error = "unable_to_find_customer";
		}
		
				
		return $response;
	}

	/**
	 * Updates the customer password.
	 *
	 * @param string $login The customer's unique username.
	 * @param string $old_password The current plaintext password to change.
	 * @param string $new_password The new plaintext password to change to.
	 * @return boolean TRUE if the password was changed successfully; otherwise, FALSE.
	 */
	public function changeCustomerPassword($application_id, $login, $old_password, $new_password)
	{
		$client = NULL;
		$result = NULL;
		$web_service_success = NULL;
		$ldb_success = NULL;
		$account_exists = FALSE;

		$old_encrypted_password = is_null($old_password) ? NULL : crypt_3::Encrypt($old_password);
		$new_encrypted_password = is_null($new_password) ? NULL : crypt_3::Encrypt($new_password);
		
		try
		{
			$client = $this->ecash_factory->getWebServiceFactory()->getWebService('application');
			$web_service_success = $client->updateApplicantAccount($login, $old_password, $new_password);
			
			// If we were unable to update the user by user an password, we have to determine if 
			if (!$web_service_success)
			{
				$applicant = $client->getApplicantAccountInfo($application_id);

				if (!empty($applicant))
				{
					$account_exists = ($applicant->login == $login);
				}
			}
		}
		catch (Exception $e)
		{
			$web_service_success = FALSE;
			$result = "unable_to_save";
		}
		
		if ($web_service_success)
		{
			try 
			{
				$customer = $this->ecash_factory->getModel("Customer");
				$loaded = $customer->loadBy(array(
					'company_id' => $this->getCompanyID(),
					'login' => $login,
					'password' => $old_encrypted_password
				));
				
				if ($loaded)
				{
					$customer->password = $new_encrypted_password;
					$customer->save();
					$ldb_success = TRUE;
				}
				else
				{
					$ldb_success = FALSE;
				}

				if (!$ldb_success)
				{
					if ($web_service_success)
					{
						$result = "data_inconsistency";
					}
				}
			}
			catch (Exception $e)
			{
				$ldb_success = FALSE;
				$result = "unable_to_save";
			}
		}

		// If the web service successfully updated but LDB did not, update web service to old password
		if ($web_service_success && !$ldb_success)
		{
			$client->updateApplicantAccount($login, $new_password, $old_password);
		}
		
		// If both saves succeeded, return success
		if ($web_service_success && $ldb_success)
		{
			$result = "success";
		}
		// If the account exists the password must have been invalid
		elseif ($account_exists)
		{
			$result = "invalid_old_password";
		}
		// If there was no override from errors,
		// we have to assume we could not locate the account
		elseif (empty($result))
		{
			$result = "no_account";
		}
		return $result;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#getLoanData($application_id)
	 * @param int $application_id
	 * @return array
	 */
	public function getLoanData($application_id)
	{
		/* PayDateCalc3 is only required by this method */
		require_once "pay_date_calc.3.php";

		$loan_data = array();
		$api = $this->ecash_api_factory->createECashApi($application_id);

		$loan_data["apr"] = NULL; // AMG has no method for determining APR in the API

		$loan_data['date_received'] = $this->formatXsdDate($api->Get_Status_Date("received", "pending::prospect::*root"));
		$loan_data['date_confirmed'] = $this->formatXsdDate($api->Get_Status_Date("confirmed", "confirmed::prospect::*root"));
		$loan_data['date_approved'] = $this->formatXsdDate($api->Get_Status_Date("approved",
			array(
				"queued::underwriting::applicant::*root",
				"dequeued::underwriting::applicant::*root")));
		/* Sometimes date_confirmed is empty when date_approved is not */

		if (empty($loan_data['date_confirmed']))
		{
			$loan_data['date_confirmed'] = $loan_data['date_approved'];
		}

		$loan_data['date_funded'] = $this->formatXsdDate($api->Get_Date_Funded());
		$loan_data["date_fund_estimated"] = $this->formatXsdDate($api->Get_Date_Fund_Estimated());
		$loan_data["bus_day_after_fund_estimated"] = $this->formatXsdDate($this->advanceBusinessDay($api->Get_Date_Fund_Estimated(), 1));

		$loan_data['payoff_amount'] = $api->Get_Payoff_Amount();

		$active_paid_out_date = $api->Get_Active_Paid_Out_Date();
		$loan_data["has_active_paid_out_date"] = !empty($active_paid_out_date);
		$loan_data["paid_out_date"] = $this->formatXsdDate($api->Get_Paid_Out_Date());

		$loan_data["has_pending_transactions"] = $api->Has_Pending_Transactions();

		$is_regulatory_flag = $api->Is_Regulatory_Flag();
		$loan_data["is_regulatory_flag"] = empty($is_regulatory_flag) ? FALSE : TRUE;

		$loan_data["has_paydown"] = $api->Has_Paydown();
		$loan_data["date_allowed_to_paydown"] = $this->formatXsdDate($this->getDateAllowedToPaydown());

		$loan_data["date_withdrawn"] = $this->formatXsdDate($api->Get_Status_Date("withdrawn", "withdrawn::applicant::*root"));

		$loan_data["tier_2_collections_phone"] = $api->Get_2_Tier_Phone();

		$loan_data["react_date"] = $this->formatXsdDate($this->advanceBusinessDay($api->Get_Last_Payment_Date(), 4));

		$loan_data["fund_amount"] = $api->Get_Loan_Amount();

		return $loan_data;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#getLastPayment($application_id)
	 * @param int $application_id
	 * @return array
	 */
	public function getLastPayment($application_id)
	{
		$last_payment = array();
		$api = $this->ecash_api_factory->createECashApi($application_id);
		$last_payment["date"] = $this->formatXsdDate($api->Get_Last_Payment_Date());
		$last_payment["amount"] = $api->Get_Last_Payment_Amount();

		return $last_payment;
	}

	/**
	 * @see ECash_Service_Loan_IAPI#getBalance($application_id)
	 * @param int $application_id
	 * @return array
	 */
	public function getBalance($application_id)
	{
		$api = $this->ecash_api_factory->createECashAPI($application_id);
		$balance_info = array();

		/*
		 * If the application has pending transactions, the current due information
		 * will include the pending transaction which may be in the past.  To avoid
		 * this, we will use the "future" funcitons to determine next payment and
		 * "last" funciton to determine the current payment info
		 */
		if ($api->Has_Pending_Transactions())
		{
			$balance_info['current_due_date'] = $this->formatXsdDate($api->Get_Last_Payment_Date());
			$balance_info['current_amount_due'] = $api->Get_Last_Payment_Amount();
			$balance_info['next_due_date'] = $this->formatXsdDate($api->getFutureCurrentDueDate());
			$balance_info['amount_due'] = $api->getFutureCurrentDueAmount();
			$balance_info['principle_amount_due'] = $api->getFutureCurrentDuePrincipalAmount();
			$balance_info['service_charge_amount_due'] = $api->getFutureCurrentDueServiceChargeAmount();
			$balance_info['payoff_amount'] = $api->Get_Payoff_Amount();
		}
		/*
		 * With no pending transactions, current and future are the same.  We will return current even
		 * though it will likely not be used just to be consistent
		 */
		else
		{
			$balance_info['current_due_date'] = NULL;
			$balance_info['current_amount_due'] = NULL;
			$balance_info['next_due_date'] =  $this->formatXsdDate($api->Get_Current_Due_Date());
			$balance_info['amount_due'] = $api->Get_Current_Due_Amount();
			$balance_info['principle_amount_due'] = $api->Get_Current_Due_Principal_Amount();
			$balance_info['service_charge_amount_due'] = $api->Get_Current_Due_Service_Charge_Amount();
			$balance_info['payoff_amount'] = $api->Get_Payoff_Amount();
		}

		return $balance_info;
	}

	/**
	 * Finds applications associated with a customer login.
	 *
	 * @param $username string Customer username
	 * @param $password string Customer password
	 * @return unknown_type
	 */
	public function getCustomerLoans($username, $password)
	{
		$loans = $this->loan_provider->findLoansForCustomer($username, $password);
		return $loans;
	}
	
	/**
	 * Requests a payout.
	 *
	 * @param int $application_id The customer's application id.
	 * @return string success if successful otherwise an error
	 */
	public function requestPayout($application_id)
	{
		$ecash_api = NULL;
		try
		{
			$ecash_api = $this->ecash_api_factory->createECashAPI($application_id);
		}
		catch (Exception $e)
		{	
			$this->insertLogEntry("Unable to get eCash_API_2 instance: " . $e->getMessage());
			
			return "invalid_application_id";
		}
		
		$ableToPaydown = $this->isAbleToPaydown($ecash_api);
		if ($ableToPaydown != "yes")
		{
			return $ableToPaydown;
		}
		
		try 
		{
			$ecash_api->Set_Agent_Id($this->agent_login);
			
			$payoffAmount = $ecash_api->Get_Payoff_Amount();
			$currentDueDate = $ecash_api->Get_Current_Due_Date();
	
			$ecash_api->Payout($payoffAmount, $currentDueDate);
			$ecash_api->Add_Comment('Web Payout received for $' . $payoffAmount . ' for ' . date('m/d/Y', strtotime($currentDueDate)));
				
			$ecash_api->Push_To_Queue('Account Summary');
		}
		catch(Exception $e)
		{
			$this->insertLogEntry("Failed to esign Account Summary doc: " . $e->getMessage());
			
			return "esign_failed";
		}
		
		return "success";
	}
	
	/**
	 * Requests a paydown.
	 *
	 * @param int $application_id The customer's application id.
	 * @param int $amount The amount the customer is requesting to paydown
	 * @return string success if successful otherwise an error
	 */
	public function requestPaydown($application_id, $amount)
	{
		$ecash_api = NULL;
		try
		{
			$ecash_api = $this->ecash_api_factory->createECashAPI($application_id);
		}
		catch (Exception $e)
		{	
			$this->insertLogEntry("Unable to get eCash_API_2 instance: " . $e->getMessage());
			
			return "invalid_application_id";
		}
		
		$amount = intval($amount);
		
		if ($amount <= 0)
		{
			return("invalid_amount");
		}
		
		$ableToPaydown = $this->isAbleToPaydown($ecash_api);
		if ($ableToPaydown != "yes")
		{
			return $ableToPaydown;
		}
		
		try 
		{
			$ecash_api->Set_Agent_Id($this->agent_login);
			
			$currentDueDate = $ecash_api->Get_Current_Due_Date();
			$payDownAmount = $ecash_api->Get_Current_Due_Principal_Amount($amount);
				
			$ecash_api->Add_Paydown($amount, $currentDueDate);
			// Modify to include paydown amount and original amount due. - GForge #8764 [DW]
			$ecash_api->Add_Comment('Web Paydown received for $' . $amount . ', adding to the original amount due of $' . ($payDownAmount-$amount) . ', for ' . date('m/d/Y', strtotime($currentDueDate)));
			
			$ecash_api->Push_To_Queue('Account Summary');
		}
		catch(Exception $e)
		{
			$this->insertLogEntry("Failed to esign Account Summary doc: " . $e->getMessage());
			
			return "esign_failed";
		}
		
		return "success";
	}
	
	/**
	 * Checks to see if customer is able to paydown.
	 * 
	 * @param eCash_API_2 $ecash_api The ECashAPI2 instance.
	 * @return string yes if customer is able to paydown, otherwise error message
	 */
	protected function isAbleToPaydown($ecash_api)
	{
		$applicationStatusChain = $ecash_api->Get_Application_Status_Chain();
		
		if ($applicationStatusChain != "active::servicing::customer::*root")
		{
			if ($applicationStatusChain == "")
			{
				return "invalid_application_id";
			}
			else
			{
				return "invalid_status";
			}
		}
		
		if ($ecash_api->Has_Paydown())
		{
			return "already_exists";
		}
		
		require_once "pay_date_calc.3.php";

		$nextDueDate = $ecash_api->Get_Current_Due_Date();
		
		$dateAllowedToPaydown = $this->getDateAllowedToPaydown();
		
		if((strtotime($nextDueDate) - strtotime($dateAllowedToPaydown)) <= 0)
		{
			return "within_two_days";
		}
		
		return "yes";
	}

	/**
	 * Form the privided date string to be compatible with xsd:date standards
	 *
	 * @param string $date
	 * @return string
	 */
	protected function formatXsdDate($date)
	{
		$xsd_string_date = NULL;

		if (!empty($date))
		{
			$xsd_date = strtotime($date);
			if ($xsd_date !== FALSE) $xsd_string_date = date("Y-m-d\TH:i:s", $xsd_date);
		}
		return $xsd_string_date;
	}

	/**
	* Get the date the customer can pay down their loan
	* @return string YYY-MM-DD formatted date the customer can paydown the loan
	*/
	private function getDateAllowedToPaydown()
	{
		$today = date("Y-m-d H:i:s");
		$allowed = $this->advanceBusinessDay($today, 2);
		return $allowed;
	}

	/**
	 * Advances some business days. Supports positive/negative days.
	 * Taken from OLP customer service
	 *
	 * @param string $date
	 * @param int $days
	 * @return string
	 */
	protected function advanceBusinessDay($date, $days)
	{
		if (($timestamp = strtotime($date)) !== FALSE)
		{
			// Grab bank holidays
			$holidays = new Date_BankHolidays_1($timestamp);
			$date_normalizer = new Date_Normalizer_1($holidays, $timestamp);

			// Advance X days
			if ($days >= 0)
			{
				$timestamp = $date_normalizer->advanceBusinessDays($timestamp, $days);
			}
			else
			{
				$timestamp = $date_normalizer->rewindBusinessDays($timestamp, -$days);
			}

			// Convert back to correct format
			$date = strftime('%Y-%m-%d', $timestamp);
		}

		return $date;
	}
	
	/**
	 * Inserts a message into the log.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	abstract protected function insertLogEntry($message);
}
?>
