<?php
require_once('status_utility.class.php');

/**
 * A customer class meant to contain help viewing/managing customers
 * and applications.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
class Customer
{
	private $db;
	
	private $company_id;
	private $agent_id;
	private $ssn;
	private $applications;
	private $customer_id;
	
	function __construct($company_id, $customer_id = NULL, $agent_id = NULL, $ssn = NULL, $app_id = NULL) //mantis:6569 - added $app_id = NULL
	{
		$this->db = ECash_Config::getMasterDbConnection();
		$this->company_id = $company_id;
		$this->customer_id = $customer_id;
		
		if($ssn != NULL)
		{
			$this->ssn = $ssn;
		}

		if(! is_null($customer_id) && $customer_id != 0) 
		{
			$this->applications = $this->Fetch_Applications();
		}
		else if ($this->ssn != null)
		{
			$this->applications = $this->Fetch_Applications_By_SSN($ssn);
		}
		else if ($app_id != null) //mantis:6569
		{
			$this->applications = $this->Fetch_Applications_By_AppId($app_id);
		}
		else
		{
			throw new Exception("Unable to retrieve customer information: No Customer ID or SSN provided!\n");
		}
		
		$this->ssn = $this->Get_SSN();
				
		if(empty($agent_id)) 
		{
			$this->agent_id = 1;
		} 
		else 
		{
			$this->agent_id = $agent_id;
		}
	}
	
	/**
	 * Creates a Customer in the customer table
	 *
	 * @param string $ssn - '123121234'
	 * @param string $login - sjones_1
	 * @param string $password - password encrypted with Crypt_3
	 * @return integer $customer_id
	 */
	public function Create_Customer($applications, $ssn)
	{
		if(! array($applications)) 
		{
			throw  new Exception ("Must pass an array of application ID's!");
		}
		
		if(empty($ssn)) 
		{
			throw new Exception ("Must supply an SSN to create a customer!");
		}
		
		if($this->Fetch_Customer_ID_By_SSN($ssn))
		{
			return false;
		}

		// Generate a login and password using the login_prefix from a previous account
		$tmp_app = $this->Fetch_Application_By_ID($applications[0]);
		list($login, $password) = $this->Generate_Login_and_Password($tmp_app->login_prefix);
		
		$sql = "
			INSERT INTO customer
			(company_id, ssn, login, password, modifying_agent_id)
			VALUES ( ?, ?, ?, ?, ?)
		";
		$this->db->queryPrepared($sql, array($this->company_id, $ssn, $login, $password, $this->agent_id));

		$this->customer_id = $this->db->lastInsertId();

		// I know, this is two steps and it could be one.
		$this->Update_Customer_ID_on_Applications($applications, $this->customer_id);
		$this->Update_Application_SSN($applications, $ssn);
		
		return $this->customer_id;
	}

	/**
	 * Sets the customer_id field on the given list of applications
	 * 
	 * $applications must be an array of objects with the following:
	 * ssn - Social Security Number
	 * application_id - Pretty obvious
	 *
	 * @param array $applications
	 * @param integer $customer_id
	 */
	public function Update_Customer_ID_on_Applications($applications, $customer_id = NULL)
	{
		if(is_null($customer_id)) 
		{
			$customer_id = $this->customer_id;
		}

		if(is_null($customer_id) && (! is_null($this->customer_id))) 
		{
			$customer_id = $this->customer_id;
		} 
		else if (is_null($customer_id) && is_null($this->customer_id)) 
		{
			throw new Exception ("Unable to set Customer ID!  No Customer ID specified and no Customer ID available in the object!");	
		}

		$sql = "
			UPDATE application
			SET customer_id = $customer_id
			WHERE application_id IN ( " . implode(',', $applications) . " ) 
			AND company_id = {$this->company_id}
		";
		return $this->db->exec($sql);
	}

	/**
	 * Set's a new SSN number in the customer table
	 *
	 * @param string $ssn (example: 123121234)
	 */
	public function Update_Customer_SSN($ssn)
	{
		$sql = "
			UPDATE customer
			SET ssn = ?
			WHERE customer_id = ?
				AND company_id = ?
		";
		$this->db->queryPrepared($sql, array($ssn, $this->customer_id, $this->company_id));
	}
		
	/**
	 * Set's a new SSN number for all of the applications in the array
	 *
	 * @param array  $applications array(123234,234235,3245234)
	 * @param string $ssn (example: 123121234)
	 */
	public function Update_Application_SSN($applications, $ssn = NULL)
	{
		if(is_null($ssn) && (! is_null($this->ssn))) 
		{
			$ssn = $this->ssn;
		} 
		else if (is_null($ssn) && is_null($this->ssn))

		{
			throw new Exception ("Unable to set SSN!  No SSN specified and no SSN available in the object!");	
		}
		
		$sql = "
			UPDATE application
			SET ssn = ?
			WHERE customer_id = ?
				AND company_id = ?
				AND application_id IN ( " . implode(',', $applications) . " )
		";
		$this->db->queryPrepared($sql, array($ssn, $this->customer_id, $this->company_id));
	}
	
	/**
	 * Finds applications with the same customer number
	 *
	 * @param <optional> integer customer_id
	 * @return array of objects
	 */
	private function Fetch_Applications($customer_id = NULL)
	{
		if($customer_id === NULL) 
		{
			$customer_id = $this->customer_id;
		}
		
		// @todo should this set $this->customer_id, like the rest?
		$where = "ap.customer_id = ? AND ap.company_id = ?";
		return $this->fetchAppsBy($where, array($customer_id, $this->company_id), FALSE);
	}

	/**
	 * Finds applications with the same SSN within the given company
	 *
	 * @param string $ssn
	 * @return array of objects
	 */
	public function Fetch_Applications_By_SSN($ssn = NULL)
	{
		if($ssn === NULL) 
		{
			$ssn = $this->ssn;
		}
		
		$where = "ap.ssn = ? AND ap.company_id = ?";
		return $this->fetchAppsBy($where, array($ssn, $this->company_id));
	}

	//mantis:6569
	/**
	 * Finds application with the same application_id
	 *
	 * @param int $application_id
	 * @return array of objects
	 */
	public function Fetch_Applications_By_AppId($app_id)
	{
		$where = "ap.application_id = ?	AND ap.company_id = ?";
		return $this->fetchAppsBy($where, array($app_id, $this->company_id));
	}
	
	protected function fetchAppsBy($where, array $args, $set_customer_id = TRUE)
	{
		$sql = "
			SELECT 	ap.ssn, 
				date_format(ap.date_created, '%m-%d-%Y') as date_created,
				ap.application_id,
				ap.customer_id,
				ap.name_last,
				ap.name_first,
				ap.email,
				ap.street,
				ap.unit,
				ap.city,
				ap.county,
				ap.state,
				ap.zip_code,
				ap.phone_home,
				ap.phone_cell,
				ap.date_application_status_set,
				ap.application_status_id, 
				ap.date_fund_actual,
				( IF(ap.fund_actual > 0, ap.fund_actual, ap.fund_qualified) ) as fund_amount,
				date_format(ap.date_first_payment,  '%m-%d-%Y') AS date_first_payment,
				ap.employer_name,
				ap.phone_work AS employer_phone,
				UCASE(CONCAT(SUBSTRING(ap.name_first, 1, 1), ap.name_last)) as login_prefix,
				ap.paydate_model as 'model_name',
				ap.paydate_model,
				ap.income_frequency as 'frequency_name', 
				ap.income_frequency,
				ap.income_direct_deposit,
				ap.day_of_week as 'day_string_one',
				ap.day_of_week,
				ap.day_of_month_1 as 'day_int_one',
				ap.day_of_month_1,
				ap.day_of_month_2 as 'day_int_two',
				ap.day_of_month_2,
				ap.week_1 as 'week_one',
				ap.week_1,
				ap.week_2 as 'week_two',
				ap.week_2,
				ap.last_paydate,
				ap.rule_set_id,
				ap.date_fund_actual as 'date_fund_stored',
				ap.fund_actual,
				ap.is_watched,
				(
					SELECT event_schedule.date_effective 
					FROM event_schedule
						JOIN event_type AS et USING (event_type_id)
						JOIN transaction_register AS tr USING (event_schedule_id)
					WHERE event_schedule.application_id = ap.application_id
						AND et.name_short = 'payment_service_chg'
						AND event_status = 'registered'
						AND origin_group_id > 0 
						AND transaction_status <> 'failed'
					ORDER BY date_effective DESC
					LIMIT 1
				) as last_payment_date,
				sm.name schedule_model
			FROM application ap
				LEFT JOIN schedule_model sm ON (sm.schedule_model_id = ap.schedule_model_id)
			WHERE {$where}
			ORDER BY date_application_status_set DESC
		";
		$db = ECash_Config::getMasterDbConnection();
		$st = $db->queryPrepared($sql, $args);
		
		$applications = array();
		$pdh = new Paydate_Handler();
		
		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			// If it's not set already, set it.
			if($set_customer_id
				&& $row->customer_id != NULL
				&& ($this->customer_id === NULL || $this->customer_id === 0))
			{
				$this->customer_id = $row->customer_id;
			}
			
			$application_id = $row->application_id;
			$row->status_long = Status_Utility::Get_Status_Name_By_ID($row->application_status_id);
			$row->status_chain = Status_Utility::Get_Status_Chain_By_ID($row->application_status_id);
			$row->formatted_ssn = $this->Format_SSN($row->ssn);

			$pdh->Get_Model($row);
			$row->paydates = $pdh->Get_Paydates($row->model, 'Y-m-d');
			$row->income_frequency = $pdh->Get_Paydate_String($row->model);
			
			$applications[$application_id] = $row;
		}

		return $applications;
	}

	/**
	 * Finds an application based on the application_id
	 *
	 * @param integer application_id
	 * @return array of objects
	 */
	public function Fetch_Application_By_ID($application_id)
	{
		$sql = "
			SELECT
				ssn, 
				company_id, 
				application_id,
				customer_id,
				name_last,
				name_first,
				email,
				employer_name,
				application_status_id, 
				date_created, 
				date_application_status_set,
				UCASE(CONCAT(SUBSTRING(name_first, 1, 1), name_last)) as login_prefix
			FROM application
			WHERE application_id = ?
			AND company_id = ?
		";
		$st = $this->db->queryPrepared($sql, array($application_id, $this->company_id));
		
		if ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$row->formatted_ssn = $this->Format_SSN($row->ssn);
		}
		return $row;
	}
	
	/**
	 * Generates a login_name and password
	 * 
	 * $login_prefix - First initial, last name
	 *
	 * @param string $login_prefix - Example: sjones
	 * @return array array($login, $password)
	 */
	public function Generate_Login_and_Password($login_prefix)
	{
		$number = 1;
		$prefix = preg_replace("/\W/", '', $login_prefix);
		
		$login = $prefix . '_' . $number;
		$found = true;
		
		while($found === true)
		{
			if($this->Login_Exists($login))
			{
				$number++;
				$login = $prefix . '_' . $number;
			}
			else
			{
				$found = false;
			}
		}
		return array($login, $this->Generate_Password());
	}

	/**
	 * Checks to see if a login name exists in the customer table
	 *
	 * @param string $login
	 * @return boolean
	 */
	public function Login_Exists($login)
	{
		$sql = "
			SELECT customer_id
			FROM customer
			WHERE login = ?
			AND company_id = ?
		";
		$id = $this->db->querySingleValue($sql, array($login, $this->company_id));
		return ($id !== FALSE);
	}
	
	/**
	 * Generates a rather generic password per OLP's specifications: cash + 3 random numbers
	 *
	 * @return string - Example: cash582
	 */
	private function Generate_Password()
	{
		$prefix = 'cash';
		$suffix = rand(100, 999);
		$password = $prefix . $suffix;
		return crypt_3::Encrypt($password);
	}
	
	public function Get_SSN()
	{
		if(empty($this->ssn) && ! is_array($this->applications)) 
		{
			$this->applications = $this->Fetch_Applications();
		} 

		if(empty($this->ssn))
		{
			// Cheating... Applications is indexed by application_id
			// which we don't know.  Just need one of them, they all 
			// share the same SSN.
			foreach($this->applications as $a) 
			{
				if(! empty($a->ssn)) 
				{
					return $a->ssn;
				}
				return false;
			}
		} 
		else 
		{
			return $this->ssn;
		}

	}
	
	/**
	 * Returns an array of objects with application data
	 * 
	 * If the customer_id is NULL, this function will return the internal list
	 * from within the object.  Otherwise, the function will do a lookup on the
	 * customer_id and return it's findings
	 *
	 * @param integer $customer_id
	 * @return array $applications - Array of objects with application data
	 */
	public function Get_Applications($customer_id = NULL)
	{
		if($customer_id != NULL) 
		{
			return $this->Fetch_Applications($customer_id);
		}
		
		if(! is_array($this->applications)) 
		{
			$this->applications = $this->Fetch_Applications();
		}

		return $this->applications;
	}
	
	/**
	 * Return the list of applications ID's for the customer the 
	 * object was initialised with.
	 *
	 * @return array - $application_ids - array of integers
	 */
	public function Get_Application_IDS()
	{
		$application_ids = array();
		
		if(is_null($this->ssn) && is_null($this->applications)) 
		{
			$this->applications = $this->Fetch_Applications();
		} 

		foreach($this->applications as $a) 
		{
			$application_ids[] = $a->application_id;
		}
	
		return $application_ids;
	}
	
	/**
	 * Fetch the customer_id by searching by SSN
	 *
	 * @return integer customer_id
	 */
	public function Fetch_Customer_ID_By_SSN($ssn)
	{
		$sql = "
			SELECT 	customer_id
			FROM customer
			WHERE ssn = ?
			AND company_id = ?
		";
		return $this->db->querySingleValue($sql, array($ssn, $this->company_id));
	}

	/**
	 * Formats an unformatted social security number
	 *
	 * @param string $ssn - example 123121234
	 * @return string $formatted_ssn - example: 123-12-1234
	 */
	public function Format_SSN($ssn)
	{
		$ssn_part_one   = substr($ssn, 0, 3);
		$ssn_part_two   = substr($ssn, 3, 2);
		$ssn_part_three = substr($ssn, 5, 4);
		
		return $ssn_part_one . '-' . $ssn_part_two . '-' . $ssn_part_three;
	}

	/**
	 * Resets the member value for customer_id and wipes out
	 * members relating to the customer
	 *
	 * @param integer $customer_id
	 */
	public function Set_Customer_ID($customer_id)
	{
		if(! empty($customer_id)) 
		{
			$this->customer_id = $customer_id;
			$this->ssn = NULL;
			$this->applications = NULL;
		}
	}
	
	/**
	 * Remove a customer ID from the customer table
	 *
	 * This function will only remove the customer_id if there 
	 * are no associated applications.
	 * 
	 * @param integer $customer_id
	 * @return boolean - true if successful, false if not
	 */
	public function Remove_Customer($customer_id)
	{
		
		
		$sql = "
			DELETE FROM customer
			WHERE customer_id = ?
				AND company_id = ? 
				AND NOT EXISTS (
					SELECT 'X'
					FROM application
					WHERE customer_id = ?
				)
		";
		$st = $this->db->queryPrepared($sql, array($customer_id, $this->company_id, $customer_id));
		return ($st->rowCount() !== 0);
	}	
	
	public function Merge_Applications($applications = NULL)
	{
		if(is_null($applications)) 
		{
			return false;
		}

		$this->Update_Customer_ID_on_Applications($applications);
		$this->Update_Application_SSN($applications);
	}
	
}
