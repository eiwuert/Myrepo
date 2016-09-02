<?php

	class Analysis
	{
		const SYSTEM_ECASH = 1;
		const SYSTEM_CASHLINE = 2;

		/**
		 * Truncates ALL tables for ALL companies -- use with care!
		 *
		 * @param string $mode
		 */
		public static function truncateTables($mode)
		{
			$a = new self($mode);

			$a->analysis_db->Query("truncate customer");
			$a->analysis_db->Query("truncate loan");
			$a->analysis_db->Query("truncate loan_performance");
			$a->analysis_db->Query("truncate quickcheck");
		}

		/**
		 * @var DB_IConnection_1
		 */
		protected $analysis_db;
		protected $company_id;
		protected $system;

		/**
		 * @var array
		 */
		protected $cached_model_class_names = array();

		private $in_batch = false;
		private $in_customer = false;
		private $customer = null;
		private $loans = null;
		private $batch_id = null;
		private $status_cache = array();
		
		private $customer_name;
		
		private $batch_statistics = array();
		
		public function __construct(DB_IConnection_1 $db)
		{
			$this->analysis_db = $db;
			$this->loadStatusCache();
		}

		public function setCustomerName($name)
		{
			$this->customer_name = $name;
		}
		
		public function beginBatch($name_short, $system)
		{
			if ($this->in_batch == true)
				throw new Exception("You must call End_Batch() before attempting to start a new batch.");

			$system_name = "";

			if ($system == self::SYSTEM_ECASH)
				$system_name = "ecash_30";
			else if ($system == self::SYSTEM_CASHLINE)
				$system_name = "cashline";
			else
				throw new Exception("Unrecognized system identifier.");

			$this->company_id = $this->acquireCompanyId($name_short, $system_name);

			try
			{
				$result = $this->analysis_db->exec(
					"insert into batch
					set
						company_id = {$this->company_id},
						date_begin = unix_timestamp()
				");
			}
			catch (MySQL_Exception $me)
			{
				throw new Exception("Unable to begin batch. ($query)");
			}

			$this->batch_id = $this->analysis_db->lastInsertId();
			$this->in_batch = true;
			echo "Starting batch [{$this->batch_id}] for $name_short [{$this->company_id}]\n";
		}

		public function truncateCompany()
		{
			if ($this->in_batch == false)
				throw new Exception("truncateCompany() may only be called during an active batch.");

			try
			{
				$this->analysis_db->exec("delete from customer where company_id = {$this->company_id}");
				$this->analysis_db->exec("delete from loan where company_id = {$this->company_id}");
				$this->analysis_db->exec("delete from loan_performance where company_id = {$this->company_id}");
				$this->analysis_db->exec("delete from quickcheck where company_id = {$this->company_id}");
			}
			catch (PDOException $me)
			{
				throw new Exception("Unable to truncate all tables: ".$me->getMessage());
			}
		}

		public function endBatch()
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
							'active',
							'past_due'
						), 1, 0) is_active,
					if (
						st.name_short in (
							'paid',
							'recovered'
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
					null external_cost,
					0.0 cost,
					((l.fund_amount - l.principal_paid - l.fees_paid) * -1) profit
				from loan l
				join status st on st.status_id = l.status_id
				where l.company_id = {$this->company_id}
			";

			try
			{
				echo "Populating Loan Performance Table\n";
				$result = $this->analysis_db->exec($query);
			}
			catch (PDOException $e)
			{
				throw new Exception ("Unable to repopulate performance tables. (".$e->GetMessage().")");
			}

			try
			{
				$result = $this->analysis_db->exec("
					update batch
					set date_end = unix_timestamp()
					where batch_id = {$this->batch_id}
				");
			}
			catch (PDOException $me)
			{
				throw new Exception("Unable to close out batch.");
			}

			$this->in_batch = false;

			// Batch Statistics
			if(! empty($this->batch_statistics))
			{
				foreach($this->batch_statistics as $key => $val)
				{
					echo "$key: $val\n";
				}
			}
		}

		public function beginCustomer($customer_data)
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
				// normalize optional data
				if (!isset($customer_data['application_id'])) $customer_data['application_id'] = NULL;
				if (!isset($customer_data['cashline_id'])) $customer_data['cashline_id'] = NULL;

				$this->customer = $customer_data;
				$this->loans = array();
			}
			else
			{
				throw new Analysis_CustomerException("Customer data is incomplete.", $customer_data);
			}
		}

		public function endCustomer()
		{
			if ($this->in_customer == false)
				throw new Exception("You must call Begin_Customer() before attempting to call End_Customer()");

			try
			{
				$customer_model = $this->getModel('Customer');
				$columns = $customer_model->getColumns();
				
				// Iterate through $this->customer and add
				// any keys that match members of the customer module
				foreach($this->customer as $key => $value)
				{
					if(in_array($key, $columns))
					{
						$customer_model->{$key} = $value;
					}
				}

				$customer_model->company_id = $this->company_id;
				$customer_model->save();
				
			}
			catch (PDOException $me)
			{
				throw new Exception("There was an error (".$me->getMessage().") while inserting the customer in query: $query");
			}

			$customer_id = $customer_model->customer_id;
			$loan_number = 0;

			usort($this->loans, array(&$this, "compareLoanDate"));

			$funded_statuses = array('active','paid','bankruptcy','internal_collections','quickcheck','external_collections');
			
			foreach ($this->loans as $loan)
			{
				$paid = ($loan['principal_paid'] + $loan['fees_paid']);
				$balance = ($loan['fund_amount'] + $loan['fees_accrued'] - $loan['principal_paid'] - $loan['fees_paid']);

				if(in_array($loan['status'], $funded_statuses)) $loan_number++;

				try
				{
					$loan_model = $this->getModel('Loan');
					$columns = $loan_model->getColumns();

					// Iterate through $this->customer and add
					// any keys that match members of the customer module
					foreach($loan as $key => $value)
					{
						if(in_array($key, $columns))
						{
							$loan_model->{$key} = $value;
						}
					}

					$loan_model->company_id   = $this->company_id;
					$loan_model->customer_id  = $customer_id;
					$loan_model->status_id    = $this->status_cache[$loan['status']];
					$loan_model->amount_paid  = $paid;
					$loan_model->loan_balance = $balance;
					$loan_model->loan_number  = $loan_number;

					/// NOTE: Added to conform to old version using a blank string instead of NULL
					if ($loan_model->promo_id === NULL)
					{
						$loan_model->promo_id = '';
					}

					$loan_model->save();
					unset($loan_model);
					
				}
				catch (PDOException $me)
				{
					throw new Exception("Unable to insert loan: ".$me->getMessage());
				}
			}

			unset($customer_model);

			$this->customer = null;
			$this->loans = null;
			$this->in_customer = false;
		}

		public function addLoan($loan_data)
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
				// Statistics
				if(! isset($loan_data['status'])) $this->batch_statistics['status']++;
				if(! isset($loan_data['date_advance'])) $this->batch_statistics['date_advance']++;
				if(! isset($loan_data['fund_amount'])) $this->batch_statistics['fund_amount']++;
				if(! isset($loan_data['principal_paid'])) $this->batch_statistics['principal_paid']++;
				if(! isset($loan_data['fees_accrued'])) $this->batch_statistics['fees_accrued']++;
				if(! isset($loan_data['fees_paid'])) $this->batch_statistics['fees_paid']++;
				if(! isset($loan_data['current_cycle'])) $this->batch_statistics['current_cycle']++;

				throw new Analysis_LoanException("Loan data is incomplete.", $loan_data);
			}
		}

		public function abortCustomer()
		{
			if ($this->in_customer == true)
			{
				$this->customer = null;
				$this->loans = null;
				$this->in_customer = false;
			}
		}

		public function abortBatch()
		{
			if ($this->in_batch == true)
			{
				$this->in_batch = false;
			}
		}

		public function inCustomer()
		{
			return $this->in_customer;
		}

		public function inBatch()
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
					name_short = " . $this->analysis_db->quote($name_short) . "
					and originating_system = " . $this->analysis_db->quote($system_name);

			$result = $this->analysis_db->Query($query);

			if ($result->rowCount() < 1)
			{
				throw new Exception("Unable to select the specified company ($name_short, $system_name)");
			}

			return $result->fetch(PDO::FETCH_OBJ)->company_id;
		}

		private function loadStatusCache()
		{
			$result = $this->analysis_db->Query("select * from status");

			while ($status = $result->fetch(PDO::FETCH_OBJ))
				$this->status_cache[$status->name_short] = $status->status_id;
		}
		
	    /**
	     * @param string $model_name
	     * @param DB_IConnection_1 Optional override database
	     * @return DB_Models_WritableModel_1
	     */
	    protected function getModel($model_name, DB_IConnection_1 $database = NULL)
	    {
			if ($database === NULL)
			{
				$database = $this->analysis_db;
			}

			if (empty($this->cached_model_class_names[$model_name]))
			{
				$this->cached_model_class_names[$model_name] = $this->getClassString('Models_' . $model_name);
				if (empty($this->cached_model_class_names[$model_name]))
				{
					throw new Exception('Unable to determine model class name.');
				}
			}

			$class_name = $this->cached_model_class_names[$model_name];

			return new $class_name($database);
	    }
	
	    /**
	     * @param string $class_name
	     * @return string
	     */
	    protected function getClassString($class_name)
	    {
			$customer_class = $this->customer_name . '_' . $class_name;
			$customer_file = AutoLoad_1::classToPath($customer_class);
			$customer_dir = dirname(__FILE__) . "/";
			
			if (file_exists($customer_dir . $customer_file))
			{
				include_once $customer_dir . $customer_file;
				return $customer_class;
			}
			return 'Analysis_' . $class_name;
	    }
	}

?>
