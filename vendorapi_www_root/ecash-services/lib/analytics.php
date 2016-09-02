<?php
	
	require_once('mysqli.1.php');

	define('ANALYTICS_SYSTEM_ECASH', 1);
	define('ANALYTICS_SYSTEM_CASHLINE', 2);
	
	class Analytics
	{
		const LIVE_DB_NAME = 'analysis';
		const LIVE_DB_HOST = 'serenity2.verihub.com';
		const LIVE_DB_PORT = 3306;
		const LIVE_DB_USER = 'serenity';
		const LIVE_DB_PASS = 'firefly';
		
		const RC_DB_NAME = 'analysis_rc';
		const RC_DB_HOST = 'serenity.verihub.com';
		const RC_DB_PORT = 3306;
		const RC_DB_USER = 'serenity';
		const RC_DB_PASS = 'firefly';
		
		private $analysis_db;
		private $company_id;
		private $system;
		
		private $in_batch = false;
		private $in_customer = false;
		private $customer = null;
		private $loans = null;
		private $batch_id = null;
		private $status_cache = array();
		
		public function __construct($mode)
		{
			switch (strtoupper($mode))
			{
				case 'LIVE':
					$this->analysis_db = new MySQLi_1(
						self::LIVE_DB_HOST,
						self::LIVE_DB_USER,
						self::LIVE_DB_PASS,
						self::LIVE_DB_NAME,
						self::LIVE_DB_PORT
					);
					break;
				case 'RC':
					$this->analysis_db = new MySQLi_1(
						self::RC_DB_HOST,
						self::RC_DB_USER,
						self::RC_DB_PASS,
						self::RC_DB_NAME,
						self::RC_DB_PORT
					);
					break;
				default:
					throw new Exception("Invalid mode.");
					break;
						
			}
			
			$this->loadStatusCache();
		}
		
		public function Begin_Batch($name_short, $system)
		{
			if ($this->in_batch == true)
				throw new Exception("You must call End_Batch() before attempting to start a new batch.");
			
			$system_name = "";
			
			if ($system == ANALYTICS_SYSTEM_ECASH)
				$system_name = "ecash_30";
			else if ($system == ANALYTICS_SYSTEM_CASHLINE)
				$system_name = "cashline";
			else 
				throw new Exception("Unrecognized system identifier.");
			
			$this->company_id = $this->acquireCompanyId($name_short, $system_name);
			
			try
			{
				$result = $this->analysis_db->Query(
					"insert into 
					batch 
					set 
						company_id = {$this->company_id}, 
						date_begin = unix_timestamp()
				");
			}
			catch (MySQL_Exception $me)
			{
				throw new Exception("Unable to begin batch. ($query)");
			}
			
			$this->batch_id = $this->analysis_db->Insert_Id();			
			$this->in_batch = true;
		}
		
		public function Truncate_All()
		{
			if ($this->in_batch == false)
				throw new Exception("Truncate_All() may only be called during an active batch.");
			
			try
			{
				$this->analysis_db->Query("delete from customer where company_id = {$this->company_id}");
				$this->analysis_db->Query("delete from loan where company_id = {$this->company_id}");
				$this->analysis_db->Query("delete from loan_performance where company_id = {$this->company_id}");
				$this->analysis_db->Query("delete from quickcheck where company_id = {$this->company_id}");
			}
			catch (MySQL_Exception $me)
			{
				throw new Exception("Unable to truncate all tables: ".$me->getMessage());
			}
		}
		
		public function End_Batch() 
		{
			if ($this->in_batch == false)
				throw new Exception("You must call Begin_Batch() before attempting to call End_Batch()");
			
			if ($this->in_customer == true)
				throw new Exception("You must call End_Customer() for an open customer before calling End_Batch()");
			
			$query = "
				insert into loan_performance
				select
					l.loan_id,
					l.company_id,
					if (
						st.name_short in (
							'active',
							'paid',
							'bankruptcy', 
							'internal_collections', 
							'quickcheck', 
							'external_collections'
						), 1, 0) is_funded,
					if (
						st.name_short in (
							'active'
						), 1, 0) is_active,
					if (
						st.name_short in (
							'paid'
						), 1, 0) is_paidout,
					if (
						st.name_short in (
							'bankruptcy',
							'internal_collections',
							'quickcheck',
							'external_collections'
						), 1, 0) is_baddebt,
					(l.fund_amount - l.principal_paid - l.fees_paid) net_balance,
					(l.fund_amount + l.fees_accrued) principal_and_fees,
					if (
						st.name_short in (
							'bankruptcy',
							'internal_collections',
							'quickcheck',
							'external_collections'
						), l.fund_amount, 0) baddebt_principal,
					if (
						st.name_short in (
							'bankruptcy',
							'internal_collections',
							'quickcheck',
							'external_collections'
						), l.fees_accrued, 0) baddebt_fees,
					if (
						st.name_short in (
							'bankruptcy',
							'internal_collections',
							'quickcheck',
							'external_collections'
						), l.fees_paid + l.principal_paid, 0) baddebt_paid_principal_and_fees,
					if (
						st.name_short in (
							'bankruptcy',
							'internal_collections',
							'quickcheck',
							'external_collections'
						), l.fees_accrued + l.fund_amount, 0) baddebt_principal_and_fees,
					null overhead_cost,
					null acquisition_cost,
					0.0 cost,
					((l.fund_amount - l.principal_paid - l.fees_paid) * -1) profit
				from loan l
				join status st on st.status_id = l.status_id
				where l.company_id = {$this->company_id}";
			
			try
			{
				$result = $this->analysis_db->Query($query);
			}
			catch (MySQL_Exception $e)
			{
				throw new Exception ("Unable to repopulate performance tables. (".$e->GetMessage().")");
			}
			
			try
			{
				$result = $this->analysis_db->Query(
					"update batch
					set
						date_end = unix_timestamp()
					where
						batch_id = {$this->batch_id}");
			}
			catch (MySQL_Exception $me)
			{
				throw new Exception("Unable to close out batch.");
			}			
			
			$this->in_batch = false;
		}
		
		public function Begin_Customer($customer_data)
		{
			if ($this->in_customer == true)
				throw new Exception("You must call End_Customer() before attempting to call Begin_Customer()");

			if ($this->in_batch == false)
				throw new Exception("You must call Begin_Batch() before attempting to call Begin_CustomeR()");
				
			$this->in_customer = true;
			
			if (
				isset($customer_data['ssn']) &&  // string (10)
				isset($customer_data['name_last']) && // string
				isset($customer_data['name_first']) && // string
				isset($customer_data['phone_home']) && // string (10)
				isset($customer_data['address_street']) && // string
				isset($customer_data['address_city']) && // string 
				isset($customer_data['address_state']) && // string (2)
				isset($customer_data['address_zipcode']) && // string (9)
				isset($customer_data['ip_address']) && // string (40)
				isset($customer_data['email_address']) && // string 
				isset($customer_data['date_origination']) && // date (YYYY-mm-dd)
				isset($customer_data['dob']) && // date (YYYY-mm-dd)
				isset($customer_data['pay_frequency']) && // weekly, twice_monthly, bi_weekly, monthly
				isset($customer_data['income_monthly']) && // float
				isset($customer_data['bank_aba']) && // string (9)
				isset($customer_data['bank_account']) // string (17)
				)
			{
				$this->customer = $customer_data;
				$this->loans = array();
			}
			else 
			{
				throw new Exception("Customer data is incomplete.");
			}
		}
		
		public function End_Customer()
		{
			if ($this->in_customer == false)
				throw new Exception("You must call Begin_Customer() before attempting to call End_Customer()");
			
			$query = "
				insert into customer
				set
					customer_id = null,
					company_id = {$this->company_id},
					application_id = " . (isset($this->customer['application_id']) ? $this->customer['application_id'] : 'null') . ",
					cashline_id = " . (isset($this->customer['cashline_id']) ? $this->customer['cashline_id'] : 'null') . ",
					ssn = '" . $this->customer['ssn'] . "',
					name_last = '" . $this->analysis_db->Escape_String($this->customer['name_last']) . "',
					name_first = '" . $this->analysis_db->Escape_String($this->customer['name_first']) . "',
					name_middle = '" . $this->analysis_db->Escape_String($this->customer['name_middle']) . "',
					phone_home = '" . $this->analysis_db->Escape_String($this->customer['phone_home']) . "',
					phone_cell = '" . $this->analysis_db->Escape_String($this->customer['phone_cell']) . "',
					phone_work = '" . $this->analysis_db->Escape_String($this->customer['phone_work']) . "',
					employer_name = '" . $this->analysis_db->Escape_String($this->customer['employer_name']) . "',
					address_street = '" . $this->analysis_db->Escape_String($this->customer['address_street']) . "',
					address_unit = '" . $this->analysis_db->Escape_String($this->customer['address_unit']) . "',
					address_city = '" . $this->analysis_db->Escape_String($this->customer['address_city']) . "',
					address_state = '" . $this->analysis_db->Escape_String($this->customer['address_state']) . "',
					address_zipcode = '" . $this->analysis_db->Escape_String($this->customer['address_zipcode']) . "',
					drivers_license = '" . $this->analysis_db->Escape_String($this->customer['drivers_license']) . "',
					ip_address = '" . $this->analysis_db->Escape_String($this->customer['ip_address']) . "',
					email_address = '" . $this->analysis_db->Escape_String($this->customer['email_address']) . "',
					date_origination = '" . $this->analysis_db->Escape_String($this->customer['date_origination']) . "',
					dob = '" . $this->analysis_db->Escape_String($this->customer['dob']) . "',
					pay_frequency = '" . $this->analysis_db->Escape_String($this->customer['pay_frequency']) ."',
					income_monthly = '" . $this->analysis_db->Escape_String($this->customer['income_monthly']) . "',
					bank_aba = '" . $this->analysis_db->Escape_String($this->customer['bank_aba']) . "',
					bank_account = '" . $this->analysis_db->Escape_String($this->customer['bank_account']) . "'";
			
			try
			{
				$this->analysis_db->Query($query);
			}
			catch (MySQL_Exception $me)
			{
				throw new Exception("There was an error (".$me->getMessage().") while inserting the customer in query: $query");
			}
			
			$customer_id = $this->analysis_db->Insert_Id();
			$loan_number = 0;
			
			usort($this->loans, array(&$this, "compareLoanDate"));

			foreach ($this->loans as $loan)
			{
				$query = "
					insert into loan
					set
						customer_id = $customer_id,
						company_id = {$this->company_id},
						loan_id = null,
						application_id = ".(isset($loan['application_id']) ? $loan['application_id'] : 'NULL').",
						status_id = " . $this->status_cache[$loan['status']] . ",
						date_advance = '" . $this->analysis_db->Escape_String($loan['date_advance']) . "',
						fund_amount = '" . $this->analysis_db->Escape_String($loan['fund_amount']) . "',
						amount_paid = '" . ($loan['principal_paid'] + $loan['fees_paid']). "',
						principal_paid = '" . $this->analysis_db->Escape_String($loan['principal_paid']) . "',
						fees_accrued = '" . $this->analysis_db->Escape_String($loan['fees_accrued']) . "',
						fees_paid = '" . $this->analysis_db->Escape_String($loan['fees_paid']) . "',
						loan_balance = '" . ($loan['fund_amount'] + $loan['fees_accrued'] - $loan['principal_paid'] - $loan['fees_paid']) . "',
						first_return_pay_cycle = '" . $this->analysis_db->Escape_String($loan['first_return_pay_cycle']) . "',
						current_cycle = '" . $this->analysis_db->Escape_String($loan['current_cycle']) . "',
						loan_number = '" . (++$loan_number) . "'
						".(isset($loan['date_loan_paid']) ? ",date_loan_paid = '" . $this->analysis_db->Escape_String($loan['date_loan_paid']) . "'" : "") . "
						".(isset($loan['first_return_code']) ? ",first_return_code = '" . $this->analysis_db->Escape_String($loan['first_return_code']) . "'" : "") . "
						".(isset($loan['first_return_msg']) ? ",first_return_msg = '" . $this->analysis_db->Escape_String($loan['first_return_msg']) . "'" : "") . "
						".(isset($loan['first_return_date']) ? ",first_return_date = '" . $this->analysis_db->Escape_String($loan['first_return_date']) . "'" : "") . "
						".(isset($loan['last_return_code']) ? ",last_return_code = '" . $this->analysis_db->Escape_String($loan['last_return_code']) . "'" : "") . "
						".(isset($loan['last_return_msg']) ? ",last_return_msg = '" . $this->analysis_db->Escape_String($loan['last_return_msg']) . "'" : "") . "
						".(isset($loan['last_return_date']) ? ",last_return_date = '" . $this->analysis_db->Escape_String($loan['last_return_date']) . "'" : "");
				
				try
				{
					$result = $this->analysis_db->Query($query);
				}
				catch (MySQL_Exception $me)
				{
					throw new Exception("Unable to insert loan: ".$me->getMessage()." ($query)");
				}
			}
			
			$this->customer = null;
			$this->loans = null;
			$this->in_customer = false;
		}
		
		public function Add_Loan($loan_data)
		{
			if ($this->in_customer == false)
				throw new Exception("You must call Begin_Customer() before attempting to call Add_Loan()");
				
			if (
				isset($loan_data['status']) && // string
				isset($loan_data['date_advance']) && // date (YYYY-mm-dd)
				isset($loan_data['fund_amount']) && // float
				isset($loan_data['principal_paid']) && // float
				isset($loan_data['fees_accrued']) && // float
				isset($loan_data['fees_paid']) && // float
				isset($loan_data['current_cycle'])  // int 
				)
			{
				$this->loans[] = $loan_data;
			}
			else
			{
				throw new LoanException("Loan data is incomplete.", $loan_data);
			}
		}
		
		public function Abort_Customer()
		{
			if ($this->in_customer == true)
			{
				$this->in_customer = false;
			}
		}
		
		public function Abort_Batch()
		{
			if ($this->in_batch == true)
			{
				$this->in_batch = false;
			}
		}
		
		public function In_Customer()
		{
			return $this->in_customer;
		}
		
		public function In_Batch()
		{
			return $this->in_batch;
		}
		
		private function compareLoanDate($loan_a, $loan_b)
		{
			$time_a = strtotime($loan_a['date_advance']);
			$time_b = strtotime($loan_b['date_advance']);
			
			if ($time_a == $time_b)
				return 0;			
			return ($time_a > $time_b) ? 1 : -1;
		}

		private function acquireCompanyId($name_short, $system_name)
		{
			$query = "
				select company_id
				from company
				where
					name_short = '" . $this->analysis_db->Escape_String($name_short) . "'
					and originating_system = '" . $this->analysis_db->Escape_String($system_name) . "'";
			
			$result = $this->analysis_db->Query($query);
			
			if ($result->Row_Count() < 1)
			{
				throw new Exception("Unable to select the specified company ($name_short, $system_name)");
			}
			
			return $result->Fetch_Object_Row()->company_id;
		}
		
		private function loadStatusCache()
		{
			$result = $this->analysis_db->Query("select * from status");
			
			while ($status = $result->Fetch_Object_Row())
				$this->status_cache[$status->name_short] = $status->status_id;
		}
	}
	
	class LoanException extends Exception
	{
		
		protected $loan;
		
		function __construct($message, $loan)
		{
			$this->loan = $loan;
			parent::__construct($message);
			
			return;
		}
		
		public function getLoan()
		{
			return $this->loan;
		}
		
	}

?>
