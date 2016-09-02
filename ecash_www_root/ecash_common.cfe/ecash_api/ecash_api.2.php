<?php

/**
 * eCash API v2
 * 
 * A basic set of functions for OLP to use to Query the eCash database for information about an application
 * 
 * 
 * @author Josef Norga <josef.norgan@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 * @author Marc Cartright
 * @todo Make customer-centric
 * @todo Add a way to grab the schedule, count number of payments to go
 */
class eCash_API_2
{
	/**
	 * Valid payment types for _Add_API_Payment.
	 */
	const PAYMENT_TYPE_PAYOUT = 'payout';
	const PAYMENT_TYPE_PAYDOWN = 'paydown';
	
	/**
	 * A list of constants for queues that may need to be written to. Pass 
	 * these as arguments to Push_To_Queue.
	 */
	const QUEUE_ACCOUNT_SUMMARY = 'Account Summary';
	
	protected $db;
	protected $biz_rules;
	protected $status_map;
	protected $application_id;
	protected $application_status_id;
	protected $company_id;
	protected $company_short;
	protected $date_funded;
	protected $date_fund_estimated;
	protected $balance_info;
	protected $next_due_info;
	protected $current_due_info;
	protected $payoff_amount;
	protected $loan_status;
	protected $loan_type;
	protected $loan_type_description;
	protected $rule_set_id;
	protected $rule_set;
	protected $is_react;
	protected $income_monthly;
	protected $income_direct_deposit;
	protected $fund_amount;
	protected $regulatory_flag;
	protected $rate_calculator;
	protected $rate_override;

	private $last_payment_date;
	private $last_payment_amount;
	private $returned_item_count;
	private $status_dates;
	private $agent_id;
	private $paid_out_date;
	private $active_paid_out_date;
		
	public function __construct( $db, $application_id, $company_id = NULL)
	{
		$this->db = $db;

		if(empty($application_id) || ! is_numeric($application_id))
		{
			throw new Exception ('Invalid application_id passed to ' . __CLASS__ );
		}
		else
		{
			$this->application_id = $application_id;
		}
		
		// If the company_id is not provided, look it up.  This is
		// required for event_type maps on a per-company basis.
		if($company_id === NULL || ! is_numeric($company_id))
		{
			$this->company_id = $this->_Get_Company_ID_by_Application();
		}
		else
		{
			$this->company_id = $company_id;
		}
	}
	
	/**
	 * Factory method for returning an Enterprise specific API
	 *
	 * @param strng $company_short
	 * @param DBDatabase_1 $db
	 * @param int $app_id
	 * @param int $company_id
	 * @return eCash_API_2
	 */
	static function Get_eCash_API($company_short = NULL, $db, $application_id, $company_id = NULL)
	{
		
		if ($company_short == NULL)
		{
			$company_short = self::getNameShortByAppId($application_id, $db);
		}
		
		switch(strtolower($company_short))
		{
			//Companies that use CFE
			case 'generic':
				$api_name = 'CFE_eCash_API_2';
				require_once('cfe_api.php');
			break;
			case '':
			default:
				throw new Exception("Invalid company '{$company_short}'");
		}
		
		return new $api_name($db, $application_id, $company_id);

	}
	
	/**
	 * getNameShortByAppId
	 * Gives you the name short of the company the application belongs to.
	 *
	 * @param int $application_id
	 * @param $db
	 * @return string  the name short of the company.
	 */
	public static function getNameShortByAppId($application_id, $db)
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT c.name_short FROM company c
		JOIN application a ON a.company_id = c.company_id
		WHERE a.application_id = {$application_id}";

		$result = $db->query($query);
		if( $row = $result->fetch(PDO::FETCH_OBJ))
		{
			return $row->name_short;
		}
		return null;
	}
	
	/**
	 * getApplicationIdBySsn
	 * Provides either the most recent application_id or an array of application_ids that match up with an SSN, based on the $most_recent
	 * parameter.
	 * This function is for cases where you don't necessarily know if they're an existing customer or not, now you can look them
	 * up based on SSN and determine everything you need to know from there.
	 * The ordering of the applications is determined by the date the application's status was last changed.
	 *
	 * @param int $ssn - The applicant's SSN in ######### format.
	 * @param $db
	 * @param bool $most_recent (defaults to true) - This determines what data is returned by the function
	 * 												 true returns the single most recent application_id. 
	 *					 							 false returns an array of application_ids that can be used to look up any pertinent information. 
	 * @return returns the most recent application_id if $most_recent is set to true. 
	 * 		   returns an array of all application_ids with this SSN 
	 * 		   returns null if there are no application_ids corresponding with this SSN
	 */
	public static function getApplicationIdBySsn($ssn,$db, $most_recent = true)
	{
		$applications = array();
		
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT a.application_id FROM application a
		WHERE a.ssn = {$db->quote($ssn)}
		ORDER BY date_application_status_set DESC
		";

		$result = $db->query($query);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($most_recent)
			{
				return $row->application_id;
			}
			
			$applications[] = $row->application_id;
		}
		if(!empty($applications))
		{
			return $applications;
		}
		
		return null;

	}
	
	/**
	 * Returns the value for the current rule set for a given loan_type
	 *
	 * @param string $loan_type - Example: delaware_title
	 * @param string $company_short - Example: pcal
	 * @param string $rule_name - Example: moneygram_fee
	 * @return string or array depending on if the rule has one or multiple rule component parameters
	 */
	protected function getCurrentRuleValue($loan_type, $company_short, $rule_name)
	{
		//$rules = new Business_Rules($this->db);
		$rules = new ECash_BusinessRulesCache($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);

		return $rule_set[$rule_name];
	}
	
	/**
	 * Get the property short for the current company.
	 * 
	 * @return string
	 */	
	private function _Get_Property_Short()
	{
		$query = "SELECT name_short FROM company WHERE company_id = {$this->company_id}";

		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			throw new Exception ("Cannot determine the name_short for {$this->application}");
		}
		
		return $row->name_short;
	}
	
	/**
	 * The date the application was funded
	 *
	 * @return string YYYY-MM-DD or FALSE
	 */
	public function Get_Date_Funded()
	{
		if(is_null($this->date_funded))
		{
			// Populate class members with Application Info
			$this->_Get_Application_Info();
			
			return $this->date_funded;
		}
		else
		{
			return $this->date_funded;
		}
	}
	
	/**
	 * The date the application is estimated to be funded
	 *
	 * @return string YYYY-MM-DD
	 */
	public function Get_Date_Fund_Estimated()
	{
		$date_funded = $this->Get_Date_Funded();
		if (!$date_funded)
		{
			$pd_calc = new Pay_Date_Calc_3($this->Fetch_Holiday_List());
			return $pd_calc->Get_Business_Days_Forward(date("Y-m-d"), 1);
		}
		else
		{
			return $date_funded;
		}
	}
	
	/**
	 * Get's the applicant's next next due date
	 *
	 * @return string YYYY-MM-DD or FALSE
	 */
	public function Get_Third_Due_Date()
	{
		if(is_null($this->third_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->third_due_info)
		{
			return $this->third_due_info->date_due;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get's the applicant's next next due amount
	 *
	 * @return float amount or FALSE
	 */
	public function Get_Third_Due_Amount($adjust_for_payment = 0)
	{
		if(is_null($this->third_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->third_due_info)
		{
			return abs($this->third_due_info->amount_due) - ($adjust_for_payment * 0.30);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get's the applicant's next due date
	 *
	 * @return string YYYY-MM-DD or FALSE
	 */
	public function Get_Next_Due_Date()
	{
		if(is_null($this->next_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->next_due_info)
		{
			return $this->next_due_info->date_due;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get's the applicant's next due amount
	 *
	 * @return float amount or FALSE
	 */
	public function Get_Next_Due_Amount($adjust_for_payment = 0)
	{
		if(is_null($this->next_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->next_due_info)
		{
			return abs($this->next_due_info->amount_due) - ($adjust_for_payment * 0.30);
		}
		else
		{
			return false;
		}
	}

	public function Get_Current_Due_Date()
	{
		if(is_null($this->current_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->current_due_info)
		{
			return $this->current_due_info->date_due;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 
	 * 
	 * Revision History:
	 * 		asaa - 8/28/2008 - Added a flag for whether or not a payout is counted. [#16624]
	 *
	 * @param int $adjust_for_payment
	 * @param bool $include_payout
	 * @return int|bool
	 */
	public function Get_Current_Due_Amount($adjust_for_payment = 0, $include_payout = TRUE)
	{
		if (is_null($this->current_due_info))
		{
			$this->_Get_Due_Info();
		}
		
		if ($this->current_due_info)
		{
			$amount_due = ($include_payout)?"amount_due":"amount_due_no_payout";
			return abs($this->current_due_info->$amount_due) + $adjust_for_payment;
		}
		else
		{
			return FALSE;
		}
	}

	public function Get_Current_Due_Principal_Amount($adjust_for_payment = 0)
	{
		if(is_null($this->current_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->current_due_info)
		{
			return abs($this->current_due_info->principal_amount_due) + $adjust_for_payment;
		}
		else
		{
			return false;
		}
	}

	public function Get_Current_Due_Service_Charge_Amount()
	{
		if(is_null($this->current_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->current_due_info)
		{
			return abs($this->current_due_info->service_charge_amount_due);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @var boolean
	 */
	protected $loaded_future_due_info = FALSE;

	/**
	 * @return boolean
	 */
	protected function loadFutureCurrentDueInfo()
	{
		if (!$this->loaded_future_due_info)
		{
			$this->_getFutureDueInfo();
			$this->loaded_future_due_info = TRUE;
		}

		return (bool)($this->future_current_due_info);
	}

	/**
	 * @return boolean
	 */
	protected function loadFutureNextDueInfo()
	{
		if (!$this->loaded_future_due_info)
		{
			$this->_getFutureDueInfo();
			$this->loaded_future_due_info = TRUE;
		}

		return (bool)($this->future_next_due_info);
	}

	/**
	 * @return string|FALSE
	 */
	public function getFutureCurrentDueDate()
	{
		if ($this->loadFutureCurrentDueInfo())
		{
			return $this->future_current_due_info->date_due;
		}

		return FALSE;
	}

	/**
	 * @param float $adjust_for_payment
	 * @param boolean $include_payout
	 * @return float|FALSE
	 */
	public function getFutureCurrentDueAmount($adjust_for_payment = 0, $include_payout = TRUE)
	{
		if ($this->loadFutureCurrentDueInfo())
		{
			$amount_due = ($include_payout) ? 'amount_due' : 'amount_due_no_payout';
			return abs($this->future_current_due_info->$amount_due) + $adjust_for_payment;
		}

		return FALSE;
	}

	/**
	 * @param float $adjust_for_payment
	 * @return float|FALSE
	 */
	public function getFutureCurrentDuePrincipalAmount($adjust_for_payment = 0)
	{
		if ($this->loadFutureCurrentDueInfo())
		{
			return abs($this->future_current_due_info->principal_amount_due) + $adjust_for_payment;
		}

		return FALSE;
	}

	/**
	 * @return float|FALSE
	 */
	public function getFutureCurrentDueServiceChargeAmount()
	{
		if ($this->loadFutureCurrentDueInfo())
		{
			return abs($this->future_current_due_info->service_charge_amount_due);
		}

		return FALSE;
	}

	/**
	 * @return string|FALSE
	 */
	public function getFutureNextDueDate()
	{
		if ($this->loadFutureNextDueInfo())
		{
			return $this->future_next_due_info->date_due;
		}

		return FALSE;
	}

	/**
	 * @param float $adjust_for_payment
	 * @param boolean $include_payout
	 * @return float|FALSE
	 */
	public function getFutureNextDueAmount($adjust_for_payment = 0, $include_payout = TRUE)
	{
		if ($this->loadFutureNextDueInfo())
		{
			$amount_due = ($include_payout) ? 'amount_due' : 'amount_due_no_payout';
			return abs($this->future_next_due_info->$amount_due) + $adjust_for_payment;
		}

		return FALSE;
	}

	/**
	 * @param float $adjust_for_payment
	 * @return float|FALSE
	 */
	public function getFutureNextDuePrincipalAmount($adjust_for_payment = 0)
	{
		if ($this->loadFutureNextDueInfo())
		{
			return abs($this->future_next_due_info->principal_amount_due) + $adjust_for_payment;
		}

		return FALSE;
	}

	/**
	 * @return float|FALSE
	 */
	public function getFutureNextDueServiceChargeAmount()
	{
		if ($this->loadFutureNextDueInfo())
		{
			return abs($this->future_next_due_info->service_charge_amount_due);
		}

		return FALSE;
	}

	/**
	 * Get's the amount  needed to payoff the loan
	 *
	 * @return integer amount or FALSE
	 */
	public function Get_Payoff_Amount()
	{
		if(is_null($this->payoff_amount))
		{
			$this->_Get_Payoff_Amount();
			return $this->payoff_amount;
		}
		else
		{
			return $this->payoff_amount;
		}
	}

	/**
	 * Get's the applicant's last payment date
	 *
	 * @return string YYYY-MM-DD or FALSE
	 */
	public function Get_Last_Payment_Date()
	{
		if(is_null($this->last_payment_date))
		{
			list($last_payment_amount, $last_payment_date) = $this->_Get_Last_Payment();
			$this->last_payment_amount = $last_payment_amount;
			$this->last_payment_date = $last_payment_date;
			
			return $this->last_payment_date;
		}
		else
		{
			return $this->last_payment_date;
		}		
	}
	
	/**
	 * Get's the applicant's last payment amount
	 *
	 * @return float amount or FALSE
	 */
	public function Get_Last_Payment_Amount()
	{
		if(is_null($this->last_payment_amount))
		{
			list($last_payment_amount, $last_payment_date) = $this->_Get_Last_Payment();
			$this->last_payment_amount = $last_payment_amount;
			$this->last_payment_date = $last_payment_date;
			
			return $this->last_payment_amount;
		}
		else
		{
			return $this->last_payment_amount;
		}		
	}

	/**
	 * Get the first date that a status (or set of statuses) was set on an application.
	 * $name is just an identifier
	 * $statuses is a string or array containing a status chain
	 *
	 * @return string Date - Example: '2006-09-27 12:09:41'
	 */
	public function Get_Status_Date($name, $statuses, $application_id = NULL)
	{
		if(!isset($this->status_dates[$name]))
		{
			if(is_string($statuses)) $statuses = array($statuses);
			
			$found_statuses = array();
			foreach($statuses as $key => $status)
			{
				$status = $this->_Search_Status_Map($status);

				if(!is_null($status))
				{
					$found_statuses[] = $status;
				}
			}
			
			if(!empty($found_statuses))
			{
				$status_string = implode(', ', $found_statuses);
				return $this->_Get_Status_Date($status_string, $application_id);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return $this->status_dates[$name];
		}
	}
	
	/**
	 * Returns the number of transaction items that have been failed/returned
	 *
	 * @return integer
	 */
	public function Get_Returned_Item_Count()
	{
		if(is_null($this->last_payment_amount))
		{
			$this->returned_item_count = $this->_Get_Returned_Item_Count();
			return $this->returned_item_count;
		}
		else
		{
			return $this->returned_item_count;
		}	
	}
	
	/**
	 * Get the Loan Amount for a Customer
	 * 
	 * If the applicant is active, the amount will be the current funded amount.
	 * If the applicant is paid, the amount will be the max funded amount
	 * based on the SSN & company_id of the applicant.
	 *
	 * @param integer $application_id If empty, will use the one created with the class
	 * @return float amount of loan
	 */
	public function Get_Loan_Amount($application_id = NULL)
	{
		if($application_id === NULL)
		{
			$application_id = $this->application_id;
			$company_id = $this->company_id;
		}
		else
		{
			$company_id = $this->_Get_Company_ID_by_Application($application_id);
		}
		
		// Inactive Paid Loans should scan for the max loan ammount
		// Based on SSN for Max Possible fund amount (Paid or Recovered)
		if($this->_Loan_Status_by_Application_ID($application_id) == "paid")
		{
			$query = "
            SELECT MAX(fund_actual) as fund_actual
            FROM application
            JOIN application_status using (application_status_id)
            WHERE 
                ssn = (SELECT ssn FROM application WHERE application_id = {$application_id})
            AND
            	company_id = $company_id
            AND 
                name_short IN ('paid','recovered') ";
		}
		else 
		{
			$query = "
            SELECT fund_actual
            FROM application
            WHERE application_id = {$application_id} ";
		}
		$st = $this->db->query($query);
		$row = $st->fetch(PDO::FETCH_OBJ);
		$val = floatval($row->fund_actual);	
		return $val;
	}

	/**
	 * Get the APR for a Customer
	 * 
	 * @param integer $application_id If empty, will use the one created with the class
	 * @return apr of loan
	 */
	public function Get_Loan_APR($application_id = NULL)
	{
		if($application_id === NULL)
		{
			$application_id = $this->application_id;
		}
		
		$query = "
            SELECT apr
            FROM application
            WHERE application_id = {$application_id} ";
	
		$st = $this->db->query($query);
		$row = $st->fetch(PDO::FETCH_OBJ);
		$val = floatval($row->apr);	
		return $val;
	}
		
	/**
	 * Get's the 'short_name' from application_status_flat for the applicant's current status
	 *
	 * @param integer $application_id
	 * @return string short status name
	 */
	public function Get_Loan_Status($application_id = NULL)
	{
		if($application_id === NULL) {
			$application_id = $this->application_id;
			if(is_null($this->loan_status)) 
			{
				// Populate class members with Application Info
				$this->_Get_Application_Info();
			}

			return $this->loan_status;

		} else {
			return $this->_Loan_Status_by_Application_ID($application_id);
		}
	}
	
	/**
	 * Get's the long 'status chain' based on the applicant's status
	 *
	 * @param integer $application_id
	 * @return string - example: 'active::servicing::customer::*root'
	 */
	public function Get_Application_Status_Chain($application_id = NULL)
	{
		if($application_id === NULL) {
			$application_id = $this->application_id;
			if(is_null($this->application_status_id)) 
			{
				// Populate class members with Application Info
				$this->_Get_Application_Info();
			}

			return $this->status_map[$this->application_status_id]['chain'];
		} 
		else 
		{
			list($date_funded, $loan_status, $application_status_id, $loan_type) = $this->_Get_Application_Info($application_id, FALSE);
			return $this->status_map[$application_status_id]['chain'];
		}
	}

	public function Has_Paydown($application_id = NULL)
	{
		if($application_id === NULL)
		{
				$application_id = $this->application_id;
		}
		
		$has_paydown = false;
		
		$query = "
			SELECT COUNT(*) AS total,
				(
					SELECT COUNT(*)
					FROM api_payment
					WHERE application_id = {$application_id} 
					AND active_status = 'active'
				) AS api_total
			FROM event_schedule es
			JOIN event_type et USING (event_type_id)
			WHERE es.application_id = {$application_id}
				AND et.name_short IN ('paydown', 'payout')
				AND es.event_status = 'scheduled'";

		$result = $this->db->query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if(intval($row->total) > 0 || intval($row->api_total) > 0)
			{
				$has_paydown = true;
			}
		}
		
		return $has_paydown;
	}

	public function Has_Pending_Transactions($application_id = NULL)
	{
		if($application_id === NULL)
		{
				$application_id = $this->application_id;
		}

		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
			select count(*) as total
			from event_schedule as es
			join transaction_register as tr using (event_schedule_id)
			where es.application_id = {$application_id}
			and tr.transaction_status = 'pending'
			AND tr.date_effective <= CURDATE()";

		$result = $this->db->query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			return (intval($row->total) > 0);
		}
	}
	
	/**
	 * Adds a paydown to the account.
	 *
	 * @param float $amount
	 * @param string $date
	 */
	public function Add_Paydown($amount, $date)
	{
		$this->_Add_API_Payment($amount, $date, self::PAYMENT_TYPE_PAYDOWN);
	}
	
	/**
	 * Adds a payout to the account.
	 *
	 * @param float $amount
	 * @param string $date
	 */
	public function Payout($amount, $date)
	{
		$this->_Add_API_Payment($amount, $date, self::PAYMENT_TYPE_PAYOUT);
	}
	
	/**
	 * Adds a comment to the application. Before calling this you must set the 
	 * objects agent_id using Set_Agent_Id().
	 *
	 * @param string $comment
	 */
	public function Add_Comment($comment)
	{
		$query = "
			INSERT INTO comment
			  (
				date_created,
				company_id,
				application_id,
				source,
				type,
				agent_id,
				comment
			) VALUES (
				now(),
		  		{$this->company_id},
				{$this->application_id},
				'system',
				'standard',
		  		'{$this->agent_id}',
		  		{$this->db->quote($comment)}
			)
		";
	
		$this->db->exec($query);
	}
	
	/**
	 * Adds the application to a named queue for immediate availability.
	 * 
	 * This function is not safe for an automated queue. You should pass a 
	 * QUEUE_* constant as the $queue_name parameter.
	 *
	 * @param string $queue_name
	 */
	public function Push_To_Queue($queue_name) {
		$query = "
			INSERT IGNORE INTO queue
	          SET
	          	date_created = UNIX_TIMESTAMP(),
	          	created_by = {$this->agent_id},
	          	date_available = UNIX_TIMESTAMP(),
	          	date_unavailable = NULL,
	          	queue_name = {$this->db->quote($queue_name)},
	          	company_id = {$this->company_id},
	          	key_value = {$this->application_id},
	          	sortable = ''
	        ";
	    $this->db->exec($query);
	}

	/**
	 * Set the ecash object's agent id.
	 * 
	 * Pass an agent login and system name (if applicable.)
	 *
	 * @param string $agent_name
	 * @param string $system_name
	 */
	public function Set_Agent_Id($agent_name, $system_name = NULL)
	{
		$this->agent_id = $this->_Get_Agent_Id($agent_name, $system_name);
	}
	
	/**
	 * Return the agent id
	 *
	 * @return int
	 */
	public function Get_Agent_Id()
	{
		return $this->agent_id;
	}
	
	/**
	 * Return the date an inactive account was paid out. If the account is not 
	 * paid out it will return false.
	 * 
	 * @return string
	 */
	public function Get_Paid_Out_Date()
	{
		if (!isset($this->paid_out_date))
		{
			$this->paid_out_date = $this->_Get_Paid_Out_Date();
		}
		
		return $this->paid_out_date;
	}

	/**
	 * Return the date an active account with no pending balance will be paid out. 
	 * If the account is not active or has a pending balance it will return false.
	 *
	 * @return string
	 */
	public function Get_Active_Paid_Out_Date()
	{
		if (!isset($this->active_paid_out_date))
		{
			$this->active_paid_out_date = $this->_Get_Active_Paid_Out_Date(); 
		}
		
		return $this->active_paid_out_date;
	}

	/**
	 * Gets a rule from the current business rules.  If it can't be found or an error
	 * occurs, it can use the passed-in default value.
	 * 
	 * @param string $rule
	 * @param mixed $default
	 * 
	 * @return mixed
	 */
	public function Get_Rule($rule, $default = null)
	{
		if(empty($this->rule_set))
		{
			$this->_Get_Rule_Set();
		}

		return (!empty($this->rule_set[$rule])) ? $this->rule_set[$rule] : $default;
	}

	/**
	 * Returns TRUE if a regulatory flag is set on the customer
	 *
	 * @return boolean $this->regulatory_flag
	 */
	public function Is_Regulatory_Flag()
	{
		if(is_null($this->regulatory_flag))
		{
			// Populate class members with Application Info
			$this->_Get_Application_Info();
		}

		return $this->regulatory_flag;
	}
	
	/**
	 * Gets the business rules for the current company
	 */
	protected function _Get_Rule_Set()
	{
		try
		{
			$this->biz_rules = new ECash_BusinessRulesCache($this->db);

			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info();
			}

			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		catch(Exception $e)
		{
			$this->rule_set = null;
		}
	}
	
	protected function _Get_Company_ID_by_Application()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT company_id FROM application WHERE application_id = '{$this->application_id}' ";

		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			// Set this to false in case the result returned 
			// is something unexpected
			throw new Exception ("Cannot determine the company_id for {$this->application}");
		}
		
		return $row->company_id;
	}

	public function _Get_Application_Info($application_id = NULL, $set_class_members = TRUE)
	{
		if(! is_array($this->status_map))
		{
			$this->status_map = $this->_Fetch_Status_Map($this->db);
		}

		if($application_id === NULL) {
			$application_id = $this->application_id;
		}

		$query = "
		SELECT  
			app.date_fund_actual,
			app.fund_actual,
			app.date_fund_estimated,
			app.company_id,
			c.name_short as company_short,
			app.income_monthly,
			app.income_direct_deposit,
			app.application_status_id,
			lt.name_short as loan_type,
			lt.name as loan_type_description,
			app.rule_set_id,
			app.is_react,
			app.rate_override,
			IF(rf.regulatory_flag_id IS NULL, FALSE, TRUE) as regulatory_flag
		FROM 
			application AS app
		JOIN 
			loan_type AS lt USING (loan_type_id)
		JOIN 
			company AS c ON (c.company_id = app.company_id)
		LEFT JOIN 
			regulatory_flag AS rf ON (rf.customer_id = app.customer_id AND rf.active_status = 'active')
		WHERE 
			app.application_id = '{$application_id}' ";
		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			// Set this to false in case the result returned 
			// is something unexpected
			return array(FALSE, FALSE, FALSE, FALSE, FALSE);
		}
		$return_array = array (	$row->date_fund_actual, 
								$this->status_map[$row->application_status_id]['name_short'],
								$row->application_status_id,
								$row->loan_type,
								(boolean)$row->regulatory_flag);

		if($set_class_members)
		{
			$this->income_direct_deposit = $row->income_direct_deposit;
			$this->company_short 		 = $row->company_short;
			$this->date_funded 			 = $row->date_fund_actual;
			$this->is_react 			 = $row->is_react;
			$this->income_monthly 		 = $row->income_monthly;
			$this->loan_status 			 = $this->status_map[$row->application_status_id]['name_short'];
			$this->application_status_id = $row->application_status_id;
			$this->loan_type 			 = $row->loan_type;
			$this->loan_type_description = $row->loan_type_description;
			$this->rule_set_id 			 = $row->rule_set_id;
			$this->regulatory_flag 		 = (boolean)$row->regulatory_flag;
			$this->fund_amount			 = $row->fund_actual;
			$this->date_fund_estimated	 = $row->date_fund_estimated;
			$this->rate_override		 = $row->rate_override;
		}

		return $return_array;		
	}

	/**
	 * 
	 * 
	 * Revision History:
	 * 		asaa - 8/28/2008 - Changed the query to pull the info with and without payouts. [#16624]
	 *
	 */
	protected function _Get_Due_Info()
	{
        $query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
                SELECT
                    es.date_effective                                  AS date_due,
                    SUM(es.amount_principal + es.amount_non_principal) AS amount_due,
                    SUM(es.amount_non_principal)                       AS service_charge_amount_due,
                    SUM(es.amount_principal)                           AS principal_amount_due,
                    SUM(IF(et.name_short <> 'payout', es.amount_principal + es.amount_non_principal, 0)) AS amount_due_no_payout,
                    SUM(IF(et.name_short <> 'payout', es.amount_non_principal, 0))                       AS service_charge_amount_due_no_payout,
                    SUM(IF(et.name_short <> 'payout', es.amount_principal, 0))                           AS principal_amount_due_no_payout
				FROM
                    event_schedule es,
                    event_type et 
                WHERE
                    es.application_id = '{$this->application_id}' 
                    AND et.event_type_id = es.event_type_id 
                    AND et.company_id = {$this->company_id}
                    AND es.date_effective >= CURDATE()
                    AND (et.name_short = 'payment_service_chg' 
                     OR  et.name_short = 'repayment_principal'
					 OR  et.name_short = 'paydown'
					 OR  et.name_short = 'payout'
					 OR  et.name_short = 'payment_fee_ach_fail'
					 OR  es.context = 'arrangement'
					) 
                GROUP BY
                    date_effective 
                ORDER BY
                    date_effective ASC
                LIMIT 3 ";

		$result = $this->db->query($query);
		$this->current_due_info = $result->fetch(PDO::FETCH_OBJ);
		$this->next_due_info = $result->fetch(PDO::FETCH_OBJ);
		$this->third_due_info = $result->fetch(PDO::FETCH_OBJ);
	}

	protected function _getFutureDueInfo()
	{
		$query = "
			-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT
				es.date_effective                                  AS date_due,
				SUM(es.amount_principal + es.amount_non_principal) AS amount_due,
				SUM(es.amount_non_principal)                       AS service_charge_amount_due,
				SUM(es.amount_principal)                           AS principal_amount_due,
				SUM(IF(et.name_short <> 'payout', es.amount_principal + es.amount_non_principal, 0)) AS amount_due_no_payout,
				SUM(IF(et.name_short <> 'payout', es.amount_non_principal, 0))                       AS service_charge_amount_due_no_payout,
				SUM(IF(et.name_short <> 'payout', es.amount_principal, 0))                           AS principal_amount_due_no_payout
			FROM event_schedule AS es
				JOIN event_type AS et ON (et.event_type_id = es.event_type_id)
			WHERE
				es.application_id = '{$this->application_id}'
				AND et.company_id = {$this->company_id}
				AND es.date_effective > CURDATE()
				AND (
					et.name_short = 'payment_service_chg'
					OR et.name_short = 'repayment_principal'
					OR et.name_short = 'paydown'
					OR et.name_short = 'payout'
					OR et.name_short = 'payment_fee_ach_fail'
					OR es.context = 'arrangement'
				)
			GROUP BY date_effective
			ORDER BY date_effective ASC
			LIMIT 3
		";

		$result = $this->db->Query($query);
		$this->future_current_due_info = $result->fetch(PDO::FETCH_OBJ);
		$this->future_next_due_info = $result->fetch(PDO::FETCH_OBJ);
		$this->future_third_due_info = $result->fetch(PDO::FETCH_OBJ);
	}

 	public function Get_Balance_Information()
	{
		return $this->_Fetch_Balance_Information($this->application_id);
	}

	protected function _Get_Payoff_Amount()
	{
		if(is_null($this->balance_info)) {
			$this->balance_info = $this->_Fetch_Balance_Information($this->application_id);
		}

		$this->payoff_amount = $this->balance_info->total_pending;
	}

	/**
	 * These are the private functions that retrieve all of the data
	 */
	
	private function _Get_Last_Payment()
	{
		$query = "
                SELECT
                    es.date_effective                                  AS date_due,
                    SUM(es.amount_principal + es.amount_non_principal) AS amount_due 
                FROM
                    event_schedule es,
                    event_type et 
                WHERE
                    es.application_id = '{$this->application_id}' 
                    AND et.event_type_id = es.event_type_id 
                    AND et.company_id = {$this->company_id}
                    AND es.date_effective <= '".date('Y-m-d')."' -- CURDATE()
					AND (
						et.name_short = 'payment_service_chg'
						OR et.name_short = 'repayment_principal'
						OR et.name_short = 'paydown'
						OR et.name_short = 'payout'
						OR et.name_short = 'payment_fee_ach_fail'
						OR es.context = 'arrangement'
					)
                GROUP BY
                    date_effective 
                ORDER BY
                    date_effective DESC
                LIMIT 1 ";

		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			// Set this to false in case the result returned 
			// is something unexpected
			return array(false, false);
		}
		
		if(!empty($row->due_date) || ! empty($row->amount_due))
		{
			return array(abs($row->amount_due), $row->date_due);
		}
		else
		{
			return array(false, false);
		}
	}

	private function _Get_Returned_Item_Count($application_id = NULL) 
	{
		if(is_null($application_id)) {
			$application_id = $this->application_id;
		}

		$query = "
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE application_id = {$application_id}
        AND transaction_status = 'failed'";

		$result = $this->db->query($query);
		if(! $row = $result->fetch(PDO::FETCH_OBJ))
		{
			return false;
		}

		return $row->count;
	}
	
	private function _Get_Status_Date($statuses, $application_id = NULL) 
	{
		if(is_null($application_id)) {
			$application_id = $this->application_id;
		}
		
		$query = "
		SELECT
		        sh.date_created
		FROM    status_history AS sh
		WHERE   sh.application_id = {$application_id}
		AND     sh.application_status_id IN ($statuses)
		ORDER BY date_created ASC
		LIMIT 1
		";
		$result = $this->db->query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{	/**
	 * Returns the date the account was paid out. 
	 */

			return $row->date_created;
		}

		return false;
	}

	private function _Was_In_Collections() 
	{
		$query = "
        SELECT count(*) as 'count'
        FROM status_history
        WHERE application_id = {$this->application_id}
        AND application_status_id in 
            (SELECT application_status_id
             FROM application_status_flat
             WHERE (level1='external_collections' and level0 != 'recovered')
             OR (level2='collections') OR (level1='collections'))";

		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		$val = intval($row->count);
		return (($val > 0) ? true : false);
	}

	private function _QuickChecks_Pending() 
	{
		$query = "
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE transaction_status = 'pending'
        AND application_id = {$this->application_id}
        AND transaction_type_id in (SELECT transaction_type_id
                            FROM transaction_type
                            WHERE name_short = 'quickcheck') ";
	
		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		$val = intval($row->count);
		return (($val > 0) ? true : false);
	}	

	private function _Get_Balance() 
	{
		$query = "
        SELECT sum(amount) as 'total'
        FROM transaction_register
        WHERE transaction_status = 'complete'
        AND application_id = {$this->application_id} ";
		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		$val = floatval($row->total);
		return $val;
	}

	private function _Has_Completed_Quickchecks()
	{
		$query = "
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE transaction_status = 'complete'
        AND application_id = {$this->application_id}
        AND transaction_type_id in (SELECT transaction_type_id
                                    FROM transaction_type
                                    WHERE name_short = 'quickcheck') ";
		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		$val = intval($row->count);
		
		if ($val == 0)
		{
			$query = "
				SELECT count(*) as 'count'
				FROM cl_transaction t
				JOIN cl_customer c ON t.customer_id = c.customer_id
				WHERE t.transaction_type = 'deposited check'
				AND c.application_id = {$acct_id}
			";

			$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
			$val = intval($row->count);
		}
		
		return (($val > 0) ? true : false);
	}

	private function _Second_Tier_Collections_Paid()
	{
		$query = "
        SELECT app.application_status_id 'actual', asf.application_status_id as 'recovered'
        FROM application app, application_status_flat asf
        WHERE app.application_id = {$this->application_id}
        AND asf.level0='recovered'
        AND asf.level1='external_collections'
        AND asf.level2='*root' ";
		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		return ($row->actual == $row->recovered);
	}

	/**
	 * This function is primarily used by Get_Loan_Status when being passed an
	 * application_id that is foreign to that of what is in the Class.  Typically
	 * the application_status_id is retrieved when Get_Application_Info is run.
	 *
	 * @param integer $application_id
	 * @return string short name of application status
	 */
	private function _Loan_Status_by_Application_ID($application_id = NULL) 
	{
		if($application_id === NULL)
		{
			if(! empty($this->loan_status)) {
				return $this->loan_status;
			}

			$application_id = $this->application_id;
		}
		
		if(! is_array($this->status_map))
		{
			$this->status_map = $this->_Fetch_Status_Map($this->db);
		}
				
		$query = "
        SELECT application_status_id
        FROM application
        WHERE application_id = {$application_id} ";
		$row = $this->db->query($query)->fetch(PDO::FETCH_OBJ);
		
		return $this->status_map[$row->application_status_id]['name_short'];
	}

	/**
	 * Fetches all of the active statuses and sets an
	 * associative array with statuses by id and named
	 * 'chains' such as 'active::servicing::customer::*root'
	 *
	 * @return array Associative array of statuses
	 */
	function _Fetch_Status_Map($db)
	{
		$statuses = array();

		$query = "
        SELECT  ass.application_status_id,
                ass.name,
                ass.name_short,
                asf.level0, asf.level1, asf.level2, asf.level3, asf.level4
        FROM application_status ass
        LEFT JOIN application_status_flat AS asf ON (ass.application_status_id = asf.application_status_id)
        WHERE ass.application_status_id NOT IN
              (   SELECT application_status_parent_id
                  FROM application_status
                  WHERE active_status = 'active'
                  AND application_status_parent_id IS NOT NULL  )
                  AND ass.active_status='active'
                 ORDER BY name";

		$result = $db->query($query);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$chain = $row->level0;
			if($row->level1 != null) { $chain .= "::" . $row->level1; }
			if($row->level2 != null) { $chain .= "::" . $row->level2; }
			if($row->level3 != null) { $chain .= "::" . $row->level3; }
			if($row->level4 != null) { $chain .= "::" . $row->level4; }

			$statuses[$row->application_status_id]['id'] = $row->application_status_id;
			$statuses[$row->application_status_id]['name_short'] = $row->name_short;
			$statuses[$row->application_status_id]['name'] = $row->name;
			$statuses[$row->application_status_id]['chain'] = $chain;
		}
		return $statuses;
	}

	/*
	 * Search the Status Map for a status_id by the status chain
	 *
	 * @param string Status chain (example: 'active::servicing::customer::*root')
	 * @return integer status id
	 */
	protected function _Search_Status_Map($chain) 
	{
		if(! is_array($this->status_map))
		{
			$this->status_map = $this->_Fetch_Status_Map($this->db);
		}

		foreach ($this->status_map as $id => $info) {
			if ($info['chain'] == $chain) {
				return $id;
			}
		}
	}

	/**
	 * Fetches the full balance information for an account including pending/posted principal
	 *
	 * @depricted use ECash_Data_Schedule::getBalanceInformation($application_id, TRUE) instead
	 * @param integer $application_id
	 * @return stdClass object containing members with balance information
	 */
	protected function _Fetch_Balance_Information($application_id = NULL)
	{
		if($application_id === NULL) {
			$application_id = $this->application_id;
		}

		settype($application_id, 'integer');
	
		// This should eventually pull from loan_snapshot_fly or loan_snapshot
		$query = "
		SELECT
		    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
		    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
	    	SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
		    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
		    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
			SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= CURDATE(), ea.amount, 0)) principal_pending,
			SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= CURDATE(), ea.amount, 0)) service_charge_pending,
			SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= CURDATE(), ea.amount, 0)) fee_pending,
			SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= CURDATE(), ea.amount, 0)) total_pending,
	    	SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'principal', ea.amount, 0)) principal_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'fee', ea.amount, 0)) fee_not_reatt,
			SUM(IF(ea.num_reattempt = 0, ea.amount, 0)) total_not_reatt,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'principal', ea.num_reattempt, 0)) principal_num_reattempts,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'service_charge', ea.num_reattempt, 0)) service_charge_num_reattempts,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'fee', ea.num_reattempt, 0)) fee_num_reattempts,
			SUM(IF( tt.name_short IN ('loan_disbursement','card_loan_disbursement','converted_principal_bal') AND tr.transaction_status IN ('complete', 'pending'), 1,0)) funded_transactions,
			SUM(IF(tr.amount > 0 AND tr.transaction_status = 'pending' AND eat.name_short <> 'irrecoverable', ea.amount, 0)) pending_credits,
			COUNT(DISTINCT IF(tt.name_short IN ('converted_sc_event', 'payment_service_chg') AND tr.transaction_status IN ('complete', 'pending'), tr.event_schedule_id, NULL)) sc_count
  		FROM
			event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id, event_schedule_id)
			JOIN transaction_type tt USING(transaction_type_id)
  		WHERE
			ea.application_id = $application_id
  		GROUP BY ea.application_id ";

     	$result = $this->db->query($query);
     	return $result->fetch(PDO::FETCH_OBJ); 
	}

	/**
	 * Writes an api payment to the database for a given amount and action 
	 * date.
	 * 
	 * Please note that with some payment tapes amount and/or date can be 
	 * overwritten. (if a payment must fall on a holiday the date may be 
	 * changed. If the payment is a payout then it will ignore the amount and 
	 * payout the entire balance. payment type should be one of the 
	 * PAYMENT_TYPE_* constants.
	 *
	 * @param unknown_type $amount
	 * @param unknown_type $date
	 * @param unknown_type $payment_type
	 */
	private function _Add_API_Payment($amount, $date, $payment_type)
	{
		$amount = round($amount, 2);
		$query = "
			INSERT INTO api_payment
			  (
				date_created,
				company_id,
				application_id,
				event_type_id,
				amount,
				date_event,
				active_status
			) VALUES (
				now(),
		  		{$this->company_id},
				{$this->application_id},
				IFNULL(
				  (
				  	SELECT 
				  		event_type_id 
				  	  FROM
				  	  	event_type 
				  	  WHERE 
				  	  	name_short = {$this->db->quote($payment_type)} AND
				  	  	company_id = {$this->company_id}
				  	  LIMIT 1
				  ), 0),
				$amount,
				{$this->db->quote($date)},
				'active'
			)
		";
	
		$this->db->exec($query);
	}
	
	/**
	 * Return an agent ID based off of an agent login and system short name.
	 *
	 * @param string $agent_name
	 * @param string $system_name
	 * @return int
	 */
	private function _Get_Agent_Id($agent_name, $system_name = NULL)
	{
		if (!empty($system_name)) {
			$system_join = "JOIN system USING (system_id)";
			$system_where = "AND system.name_short = {$this->db->quote($system_name)}";
		} else {
			$system_join = "";
			$system_where = "";
		}
		
		$query = "
			SELECT agent_id	
			  FROM
			  	agent
			  	{$system_join}
			  WHERE
			  	login = {$this->db->quote($agent_name)}
			  	{$system_where}
		";
		
		$result = $this->db->query($query);
		if ($value = $result->fetch(PDO::FETCH_OBJ)) 
		{
			return $value->agent_id;
		} 
		else 
		{
			return 0;
		}
	}

	/**
	 * Returns the date the account was paid out. 
	 */
	private function _Get_Paid_Out_Date()
	{
		$query = "
			SELECT
			  DATE(th.date_created) paid_out
			FROM
			  transaction_history th
			  JOIN application USING (application_id)
			WHERE
			  application_id = {$this->application_id} AND
			  status_after = 'complete' AND
			  application_status_id IN 
			  	({$this->_Search_Status_Map('paid::customer::*root')}, 
			  	{$this->_Search_Status_Map('recovered::external_collections::*root')}) AND
			  th.date_created <= (
			    SELECT
			      date_application_status_set
			    FROM
			      application
			    WHERE
			      application_id = {$this->application_id}
			  )
			ORDER BY th.date_created DESC
			LIMIT 1;
		";
		
		$result = $this->db->query($query);
		if ($row = $result->fetch(PDO::FETCH_OBJ)) 
		{
			return $row->paid_out;
		} 
		else 
		{
			$query = "
				SELECT t.transaction_date_paid AS paid_out 
					FROM cl_customer c 
				JOIN cl_transaction t USING(customer_id) 
				WHERE application_id = {$this->application_id};";
			
			$result = $this->db->query($query);
			if($row = $result->fetch(PDO::FETCH_OBJ)) 
			{
				return $row->paid_out;
			}
			else 
			{
				return false;
			}
		}
	}

	/**
	 * Returns the date an active account with no pending balance will pay out.
	 *
	 * @return string
	 */
	public function _Get_Active_Paid_Out_Date()
	{
		$query = "
			SELECT
				MAX(tr.date_effective) paid_out
			FROM
				transaction_register tr
				JOIN event_amount ea USING (application_id, transaction_register_id, event_schedule_id)
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN application a USING (application_id)
			WHERE
				eat.name_short <> 'irrecoverable'
				AND tr.transaction_status <> 'failed'
				AND tr.application_id = {$this->application_id}
				AND a.application_status_id = {$this->_Search_Status_Map('active::servicing::customer::*root')}
			GROUP BY
				tr.application_id
			HAVING
				SUM(ea.amount) = 0;
		";
		
		$result = $this->db->Query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ)) 
		{
			return $row->paid_out;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Returns the archive_id of the most recent, non-failed,
	 * specified document associated with the specified application_id.
	 *
	 * @param mixed $document
	 * @param int $application_id
	 * @param string $type
	 */
	public function Get_Last_Document_Id($document, $application_id = NULL, $type = NULL)
	{
		$application_id = (is_null($application_id)) ? $this->application_id : $application_id;
		
		if(is_array($document))
		{
			$document = implode("', '", $document);
		}

		if ($type === NULL)
		{
			$document_event_type = "!= 'failed'";
		}
		else
		{
			$document_event_type = "= " . $this->db->quote($type);
		}

		$query = "
			SELECT d.archive_id
			FROM document d
			JOIN document_list dl ON (d.document_list_id = dl.document_list_id)
			WHERE d.application_id = {$application_id}
				AND d.document_event_type {$document_event_type}
				AND dl.name_short IN ('{$document}')
				AND dl.document_api = 'condor'
			ORDER BY d.date_created DESC
			LIMIT 1";

		$result = $this->db->query($query);
		if ($row = $result->fetch(PDO::FETCH_OBJ)) 
		{
			return $row->archive_id;
		} 
		else 
		{
			return FALSE;
		}
	}



	/**
	 * Fetches the appropriate 2 Tier Company phone number.
	 * Returns phone number in format 1-XXX-XXX-XXXX 
	 * or 'N/A' if the external collection batch is not created for the application
	 *
	 * Revision History:
	 *		alexanderl - 09-04-2008 - created this function
	 *
	 * @param int $application_id
	 * @return string $phone
	 */
	public function Get_2_Tier_Phone($application_id = NULL)
	{
		if($application_id === NULL)
		{
			$application_id = $this->application_id;
		}

		$query = "
       			-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 	
				ecb.ext_collections_co
			FROM	
				application ap

			LEFT JOIN ext_collections ec ON ec.application_id = ap.application_id				
			LEFT JOIN ext_collections_batch ecb ON ecb.ext_collections_batch_id = ec.ext_collections_batch_id

			WHERE
				ap.application_id = {$application_id}
			";
			
		$result = $this->db->query($query);
		$ext_coll_co = str_replace(' ', '_', strtoupper($result->fetch(PDO::FETCH_OBJ)->ext_collections_co));

		if(empty($ext_coll_co))
		{
			$property = 'COMPANY_COLLECTIONS_PHONE';
		}
		else
		{
			$property = $ext_coll_co . '_PHONE';
		}

		$query = "
       		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT 
				value
			FROM 
				company_property
			WHERE 
				company_id = {$this->company_id}
			AND
				property = '{$property}'
			";

		$result = $this->db->query($query);
		$phone = $result->fetch(PDO::FETCH_OBJ)->value;

		return $phone;
	}
	
	/**
	 * getDelinquencyDate
	 * Gives you the application's delinquency date, if it has one, and if its a company that uses it.
	 *
	 * @param integer $application_id
	 * @return null if no delinquency date, 'YYYY-MM-DD' date if it does.
	 */
	public function getDelinquencyDate($application_id){}
	
	/**
	 * Method used to determine the Principal Payment Amount for the application
	 *
	 * - Used for the PrincipalPaymentAmount Token by OLP [BR]
	 *
	 * @param string $company_short - Abbreviated name of the company
	 * @param string $loan_type     - Loan Type name (e.g. 'standard')
	 * @param integer $fund_amount  - the fund amount
	 * @return integer
	 */
	public function Get_Payment_Amount($company_short, $loan_type, $fund_amount)
	{
		if(empty($company_short) || empty($loan_type) || ! is_numeric($fund_amount)) return 0;

		$rules = new ECash_BusinessRules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		
		if(empty($loan_type_id)) return 0;
		
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);
		
		if(! is_array($rule_set)) return 0;
			
		// Try new rules, else fall back.
		if(isset($rule_set['principal_payment']))
		{
			if($rule_set['principal_payment']['principal_payment_type'] === 'Percentage')
			{
				$p_amount = (($fund_amount / 100) * $rule_set['principal_payment']['principal_payment_percentage']);
			}
			else
			{
				$p_amount = $rule_set['principal_payment']['principal_payment_amount'];
			}

			return $p_amount;

		}
		else
		{
			return $rule_set['principal_payment_amount'];
		}

	}

	/**
	 * Returns the list of holidays in an array.  
	 * Stolen from eCash for the API use.
	 *
	 * @return array $holiday_list
	 */
	protected function Fetch_Holiday_List()
	{
		static $holiday_list;
	
		if(empty($holiday_list))
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
							SELECT  holiday
							FROM    holiday
							WHERE   active_status = 'active'";
		
			$result = $this->db->query($query);	
			$holiday_list = array();
			while( $row = $result->fetch(PDO::FETCH_OBJ) )
			{
				$holiday_list[] = $row->holiday;
			}
		}

		return $holiday_list;
	}

	protected function getRateCalculator()
	{
		if(!$this->rate_calculator)
		{
			$this->_Get_Rule_Set();
			$ratebuilder = new ECash_Transactions_RateCalculatorBuilder($this->rule_set,
																		$this->loan_type,
																		$this->countNumberPaidApplications($this->application_id),
																		$this->rate_override);
			$this->rate_calculator = $ratebuilder->buildRateCalculator();
		}
		return $this->rate_calculator;		
	}

	/**
	 * Finds applications with the same ssn of the given application_id
	 * that are in the Inactive Paid status and returns the total count
	 *
	 * @param integer application_id
	 * @return integer $num_paid
	 */
	public function countNumberPaidApplications($application_id=NULL)
	{
		if($application_id === NULL)
		{
			$application_id = $this->application_id;
		}
		
		if(empty($this->status_map))
		{
			$this->_Fetch_Status_Map($this->db);
		}
		
		$paid_status_id = $this->_Search_Status_Map('paid::customer::*root');
		$settled_status_id = $this->_Search_Status_Map('settled::customer::*root');
		
		$sql = "
        -- eCashApi File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
        SELECT 	count(application_id) as num_paid
		FROM application
		WHERE ssn = (
						SELECT ssn
						FROM application
						WHERE application_id = {$application_id})
		AND company_id = {$this->company_id} 
		AND application_status_id IN ({$paid_status_id}, {$settled_status_id})";

		$result = $this->db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);

		return $row->num_paid;
	}

	/**
	 * Fetches the Pay Date Calculator v2
	 *
	 * @return object Pay_Date_Calc_2
	 */
	protected function getPDC()
	{
		static $pdc;
		
		if(! is_a($pdc, 'Pay_Date_Calc_2'))
		{
			require_once('pay_date_calc.2.php');
			$pdc = new Pay_Date_Calc_2($this->Fetch_Holiday_List());
		}
		
		return $pdc;
	}
	
	/**
	 * Calculates the maximum loan amount the applicant can receive
	 * 
	 * This uses the LoanAmountCalculator class which currently
	 * is Agean specific and uses the loan type to determine which
	 * formula to use and the applicant's business rules for rates.
	 * 
	 * @return integer $max_loan_amount
	 */
	public function calculateMaxLoanAmount()
	{
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->loan_type_name = $this->loan_type_description;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		$data->num_paid_applications = $this->countNumberPaidApplications();

		require_once('loan_amount_calculator.class.php');

		$loan_amount_calc = LoanAmountCalculator::Get_Instance($this->db, $this->company_short);
		return $loan_amount_calc->calculateMaxLoanAmount($data);
	
	}

	/**
	 * Returns an array of the available loan amount choices for the applicant
	 * 
 	 * This uses the LoanAmountCalculator class which currently
	 * is Agean specific and uses the loan type to determine which
	 * formula to use and the applicant's business rules for rates.
	 *
	 * @return array
	 */
	public function calculateLoanAmountsArray()
	{
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->loan_type_name = $this->loan_type_description;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		$data->num_paid_applications = $this->countNumberPaidApplications();
		
		require_once('loan_amount_calculator.class.php');
		
		$loan_amount_calc = LoanAmountCalculator::Get_Instance($this->db, $this->company_short);
		return $loan_amount_calc->calculateLoanAmountsArray($data);
		
	}
	
	/**
	 * Returns the current Transfer Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getTransferFeeAmount($company_short, $loan_type)
	{
		$transfer_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'moneygram_fee');
		return (! empty($transfer_fee)) ? $transfer_fee : 0;
	}

	/**
	 * Returns the current Delivery Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getDeliveryFeeAmount($company_short, $loan_type)
	{
		$delivery_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'ups_label_fee');
		return (! empty($delivery_fee)) ? $delivery_fee : 0;		
	}

	/**
	 * Returns the current Delivery Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getReturnFeeAmount($company_short, $loan_type)
	{
		$return_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'return_transaction_fee');
		return (! empty($return_fee)) ? $return_fee : 0;		
	}

	/**
	 * Returns the Lien Fee amounts for a given state
	 *
	 * @param string $state - Example: nv
	 * @return string - Example: 10, 10.55
	 */
	public function getLienFeeAmount($state)
	{
		$query = "
       	-- eCash_API ".__FILE__.":".__LINE__.":".__METHOD__."()
       		SELECT
					lf.fee_amount
			FROM 	lien_fees AS lf
	       	WHERE 	lf.state = '{$state}'";
		
		if($result = $this->db->Query($query))
		{
			return $result->fetch(PDO::FETCH_OBJ)->fee_amount;
		}

		return 0;		
	}		
}
