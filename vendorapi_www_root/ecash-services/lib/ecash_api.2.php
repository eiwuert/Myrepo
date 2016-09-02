<?php

require_once('/virtualhosts/lib/mysqli.1.php');

/**
 * eCash API v2
 * 
 * A basic set of functions for OLP to use to Query the eCash database for information about an application
 * 
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 * @author Marc Cartright
 * @todo Make customer-centric
 * @todo Add a way to grab the schedule, count number of payments to go
 * @todo Add a way for the customer to add a paydown
 * @todo Add a way for the customer to add a payout
 */
Class eCash_API_2
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
	
	private $mysqli;
	private $status_map;
	private $application_id;
	private $application_status_id;
	private $company_id;
	private $date_funded;
	private $balance_info;
	private $next_due_info;
	private $current_due_info;
	private $last_payment_date;
	private $last_payment_amount;
	private $payoff_amount;
	private $returned_item_count;
	private $loan_status;
	private $status_dates;
	private $agent_id;
	private $paid_out_date;
		
	public function __construct( $mysqli, $application_id, $company_id = NULL)
	{
		$this->mysqli = $mysqli;

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
	 * The date the application was funded
	 *
	 * @return string YYYY-MM-DD or FALSE
	 */
	public function Get_Date_Funded()
	{
		if(is_null($this->date_funded))
		{
			list($date_funded, $loan_status, $application_status_id) = $this->_Get_Application_Info();
			$this->date_funded = $date_funded;
			$this->loan_status = $loan_status;
			$this->application_status_id = $application_status_id;
			
			return $this->date_funded;
		}
		else
		{
			return $this->date_funded;
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

	public function Get_Current_Due_Amount($adjust_for_payment = 0)
	{
		if(is_null($this->current_due_info))
		{
			$this->_Get_Due_Info();
		}

		if ($this->current_due_info)
		{
			return abs($this->current_due_info->amount_due) + $adjust_for_payment;
		}
		else
		{
			return false;
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
			$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
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
			$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
            SELECT fund_actual
            FROM application
            WHERE application_id = {$application_id} ";
		}
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
		$val = floatval($row->fund_actual);	
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
				list($date_funded, $loan_status, $application_status_id) = $this->_Get_Application_Info();
				$this->date_funded = $date_funded;
				$this->loan_status = $loan_status;
				$this->application_status_id = $application_status_id;
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
				list($date_funded, $loan_status, $application_status_id) = $this->_Get_Application_Info();
				return $this->status_map[$application_status_id]['chain'];
			}

			return $this->status_map[$this->application_status_id]['chain'];

		} 
		else 
		{
			list($date_funded, $loan_status, $application_status_id) = $this->_Get_Application_Info($application_id);
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
		
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
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

		$result = $this->mysqli->Query($query);
		if($row = $result->Fetch_Object_Row())
		{
			if(intval($row->total) > 0 || intval($row->api_total) > 0)
			{
				$has_paydown = true;
			}
		}
		
		return $has_paydown;
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
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
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
		  		'{$this->mysqli->Escape_String($comment)}'
			)
		";
	
		$this->mysqli->Query($query);
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
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
			INSERT IGNORE INTO queue
	          SET
	          	date_created = UNIX_TIMESTAMP(),
	          	created_by = {$this->agent_id},
	          	date_available = UNIX_TIMESTAMP(),
	          	date_unavailable = NULL,
	          	queue_name = '{$this->mysqli->Escape_String($queue_name)}',
	          	company_id = {$this->company_id},
	          	key_value = {$this->application_id},
	          	sortable = ''
	        ";
	    $this->mysqli->Query($query);
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
	 * These are the private functions that retrieve all of the data
	 */

	private function _Get_Company_ID_by_Application()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT company_id FROM application WHERE application_id = '{$this->application_id}' ";

		$result = $this->mysqli->Query($query);
		if(! $row = $result->Fetch_Object_Row())
		{
			// Set this to false in case the result returned 
			// is something unexpected
			throw new Exception ("Cannot determine the company_id for {$this->application}");
		}
		
		return $row->company_id;
	}

	private function _Get_Application_Info($application_id = NULL)
	{
		if(! is_array($this->status_map))
		{
			$this->_Fetch_Status_Map();
		}

		if($application_id === NULL) {
			$application_id = $this->application_id;
		}

		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT  date_fund_actual, 
				application_status_id 
		FROM application WHERE application_id = '{$application_id}' ";

		$result = $this->mysqli->Query($query);
		if(! $row = $result->Fetch_Object_Row())
		{
			// Set this to false in case the result returned 
			// is something unexpected
			return array(false, false);
		}
		
		$return_array = array (	$row->date_fund_actual, 
								$this->status_map[$row->application_status_id]['name_short'],
								$row->application_status_id);
		return $return_array;		
	}

	private function _Get_Due_Info()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
                SELECT
                    es.date_effective                                  AS date_due,
                    SUM(es.amount_principal + es.amount_non_principal) AS amount_due,
					SUM(es.amount_non_principal)                       AS service_charge_amount_due,
					SUM(es.amount_principal)                           AS principal_amount_due
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
					 OR  et.name_short = 'payout') 
                GROUP BY
                    date_effective 
                ORDER BY
                    date_effective ASC
				LIMIT 3 ";

		$result = $this->mysqli->Query($query);
		$this->current_due_info = $result->Fetch_Object_Row();
		$this->next_due_info = $result->Fetch_Object_Row();
		$this->third_due_info = $result->Fetch_Object_Row();
	}

	public function Get_Balance_Information()
	{
		return $this->_Fetch_Balance_Information($this->application_id);
	}

	private function _Get_Payoff_Amount()
	{
		if(is_null($this->balance_info)) {
			$this->balance_info = $this->_Fetch_Balance_Information($this->application_id);
		}

		$this->payoff_amount = $this->balance_info->total_pending;
	}

	private function _Get_Last_Payment()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
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
                    AND es.date_effective <= CURDATE()
                    AND (et.name_short IN ('payment_service_chg', 
                     			   'repayment_principal',
                     			   'payout'))
                GROUP BY
                    date_effective 
                ORDER BY
                    date_effective DESC
                LIMIT 1 ";

		$result = $this->mysqli->Query($query);
		if(! $row = $result->Fetch_Object_Row())
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

		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE application_id = {$application_id}
        AND transaction_status = 'failed'";

		$result = $this->mysqli->Query($query);
		if(! $row = $result->Fetch_Object_Row())
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
		
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT
		        sh.date_created
		FROM    status_history AS sh
		WHERE   sh.application_id = {$application_id}
		AND     sh.application_status_id IN ($statuses)
		ORDER BY date_created ASC
		LIMIT 1
		";
		$result = $this->mysqli->Query($query);
		if($row = $result->Fetch_Object_Row())
		{	/**
	 * Returns the date the account was paid out. 
	 */

			return $row->date_created;
		}

		return false;
	}

	private function _Was_In_Collections() 
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT count(*) as 'count'
        FROM status_history
        WHERE application_id = {$this->application_id}
        AND application_status_id in 
            (SELECT application_status_id
             FROM application_status_flat
             WHERE (level1='external_collections' and level0 != 'recovered')
             OR (level2='collections') OR (level1='collections'))";

		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
		$val = intval($row->count);
		return (($val > 0) ? true : false);
	}

	private function _QuickChecks_Pending() 
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE transaction_status = 'pending'
        AND application_id = {$this->application_id}
        AND transaction_type_id in (SELECT transaction_type_id
                            FROM transaction_type
                            WHERE name_short = 'quickcheck') ";
	
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
		$val = intval($row->count);
		return (($val > 0) ? true : false);
	}	

	private function _Get_Balance() 
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT sum(amount) as 'total'
        FROM transaction_register
        WHERE transaction_status = 'complete'
        AND application_id = {$this->application_id} ";
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
		$val = floatval($row->total);
		return $val;
	}

	private function _Has_Completed_Quickchecks()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT count(*) as 'count'
        FROM transaction_register
        WHERE transaction_status = 'complete'
        AND application_id = {$this->application_id}
        AND transaction_type_id in (SELECT transaction_type_id
                                    FROM transaction_type
                                    WHERE name_short = 'quickcheck') ";
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
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

			$row = $mysqli->Query($query)->Fetch_Object_Row();
			$val = intval($row->count);
		}

		return (($val > 0) ? true : false);
	}

	private function _Second_Tier_Collections_Paid()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT app.application_status_id 'actual', asf.application_status_id as 'recovered'
        FROM application app, application_status_flat asf
        WHERE app.application_id = {$this->application_id}
        AND asf.level0='recovered'
        AND asf.level1='external_collections'
        AND asf.level2='*root' ";
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
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
			$this->_Fetch_Status_Map();
		}
				
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
        SELECT application_status_id
        FROM application
        WHERE application_id = {$application_id} ";
		$row = $this->mysqli->Query($query)->Fetch_Object_Row();
		
		return $this->status_map[$row->application_status_id]['name_short'];
	}

	/**
	 * Fetches all of the active statuses and sets an
	 * associative array with statuses by id and named
	 * 'chains' such as 'active::servicing::customer::*root'
	 *
	 * @return array Associative array of statuses
	 */
	function _Fetch_Status_Map()
	{
		$statuses = array();

		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
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

		$result = $this->mysqli->Query($query);
		while($row = $result->Fetch_Object_Row())
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
		$this->status_map = $statuses;
	}

	/*
	 * Search the Status Map for a status_id by the status chain
	 *
	 * @param string Status chain (example: 'active::servicing::customer::*root')
	 * @return integer status id
	 */
	private function _Search_Status_Map($chain) 
	{
		if(! is_array($this->status_map))
		{
			$this->_Fetch_Status_Map();
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
	 * @param integer $application_id
	 * @return stdClass object containing members with balance information
	 */
	private function _Fetch_Balance_Information($application_id = NULL)
	{
		if($application_id === NULL) {
			$application_id = $this->application_id;
		}

		settype($application_id, 'integer');
	
		// This should eventually pull from loan_snapshot_fly or loan_snapshot
		$query = "-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT
		    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
		    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
	    	SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
		    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
		    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
	    	SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
		    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
		    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
	    	SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'principal', ea.amount, 0)) principal_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_not_reatt,
			SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'fee', ea.amount, 0)) fee_not_reatt,
			SUM(IF(ea.num_reattempt = 0, ea.amount, 0)) total_not_reatt,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'principal', ea.num_reattempt, 0)) principal_num_reattempts,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'service_charge', ea.num_reattempt, 0)) service_charge_num_reattempts,
			MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'fee', ea.num_reattempt, 0)) fee_num_reattempts
  		FROM
			event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id)
  		WHERE
			ea.application_id = $application_id
  		GROUP BY ea.application_id ";

     	$result = $this->mysqli->Query($query);
     	return $result->Fetch_Object_Row(); 
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
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
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
				  	  	name_short = '{$this->mysqli->Escape_String($payment_type)}' AND
				  	  	company_id = {$this->company_id}
				  	  LIMIT 1
				  ), 0),
				$amount,
				'{$this->mysqli->Escape_String($date)}',
				'active'
			)
		";
	
		$this->mysqli->Query($query);
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
			$system_where = "AND system.name_short = '{$this->mysqli->Escape_String($system_name)}'";
		} else {
			$system_join = "";
			$system_where = "";
		}
		
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT agent_id	
			  FROM
			  	agent
			  	{$system_join}
			  WHERE
			  	login = '{$this->mysqli->Escape_String($agent_name)}'
			  	{$system_where}
		";
		
		$result = $this->mysqli->Query($query);
		if ($value = $result->Fetch_Object_Row()) {
			return $value->agent_id;
		} else {
			return 0;
		}
	}
	
	/**
	 * Returns the date the account was paid out. 
	 */
	private function _Get_Paid_Out_Date()
	{
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT
			  DATE(date_created) paid_out
			FROM
			  transaction_history
			  JOIN application USING (application_id)
			WHERE
			  application_id = {$this->application_id} AND
			  status_after = 'complete' AND
			  application_status_id IN 
			  	({$this->_Search_Status_Map('paid::customer::*root')}, 
			  	{$this->_Search_Status_Map('recovered::external_collections::*root')}) AND
			  date_created <= (
			    SELECT
			      date_application_status_set
			    FROM
			      application
			    WHERE
			      application_id = {$this->application_id}
			  )
			ORDER BY date_created DESC
			LIMIT 1;
		";
		
		$result = $this->mysqli->Query($query);
		if ($row = $result->Fetch_Object_Row()) {
			return $row->paid_out;
		} else {
			return false;
		}
	}

	/**
	 * Returns the (Condor) archive_id of the most recent non-failed
	 * Account Summary document associated with the specified application_id.
	 *
	 * @param int $application_id
	 */
	public function Get_Last_Account_Summary_Id($application_id)
	{
		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT
			 d.archive_id
			FROM
			 document d
			JOIN
			 document_list dl
			ON
			 (d.document_list_id = dl.document_list_id)
			WHERE
			 d.application_id = {$application_id}
			AND
			 d.document_event_type != 'failed'
			AND
			 dl.name_short = 'account_summary'
			AND
			 dl.document_api = 'condor'
			ORDER BY
			 d.date_created DESC
			LIMIT 1
			";

		$result = $this->mysqli->Query($query);
		if ($row = $result->Fetch_Object_Row()) {
			return $row->archive_id;
		} else {
			return FALSE;
		}
	}

}
