<?php

	/**
	 * Customer business object
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Customer extends Object_1
	{
		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var ECash_Models_Customer
		 */
		protected $model;

		/**
		 * @var int
		 */
		protected $ssn;

		/**
		 * @var int
		 */
		protected $customer_id;

		/**
		 * @var ECash_Customer_DoNotLoan
		 */
		protected $do_not_loan;

		/**
		 * @var ECash_Data_Customer
		 */
		protected $data;
		
		/**
		 * @param DB_IConnection $db Connection to use when fetching data
		 * @param int $company_id defaults to company_id associated with the record
		 */
		private function __construct(DB_IConnection_1 $db, $company_id = NULL)
		{
			$this->db = $db;
			$this->company_id = $company_id;
			$this->data = ECash::getFactory()->getData('Customer', $this->db);
		}

		/**
		 * Returns the model representing this customer in the database.
		 *
		 * @return ECash_Models_Customer
		 */
		public function getModel()
		{
			$load_by_array = array();
			if ($this->model === NULL)
			{
				$this->model = ECash::getFactory()->getModel('Customer', $this->db);

				if($this->customer_id !== NULL)
				{
					$load_by_array = array('customer_id' => $this->customer_id);
				}
				elseif ($this->ssn !== NULL)
				{
					$load_by_array = array('ssn' => $this->ssn);
				} 
				else
				{
					throw new Exception("Unable to locate customer in the databases. No SSN or Customer ID.");
				}
				
				if(count($load_by_array))
				{
//					if($this->company_id !== NULL) $load_by_array['company_id'] = $this->company_id;
					if(!$this->model->loadBy($load_by_array))
						throw new Exception("Unable to locate customer in the databases. ");
				}

			}

			return $this->model;
		}

		/**
		 * Returns the DoNotLoan object for this customer
		 *
		 * @return ECash_Customer_DoNotLoan
		 */
		public function getDoNotLoan()
		{
			if ($this->do_not_loan === NULL)
			{
				$this->do_not_loan = new ECash_Customer_DoNotLoan(
					$this->db,
					$this,
					$this->getCompanyId()
				);
			}

			return $this->do_not_loan;
		}

		/**
		 * Constructs and configures a customer object by SSN. Called by the Factory.
		 * Use the ECash ease-of-use method: ECash::getCustomerBySSN
		 *
		 * @param DB_IConnection $db database connection to use when fetching data
		 * @param string $ssn 9-digit social security for this customer
		 * @param int $company_id company_id may be null; will substitute owner company of this row
		 * @return ECash_Customer
		 */
		public static function getBySSN(DB_IConnection_1 $db, $ssn, $company_id = NULL)
		{
			$customer = new ECash_Customer($db, $company_id);
			$customer->ssn = $ssn;

			return $customer;
		}

		/**
		 * Constructs and configures a customer object by customer id. Called by the factory.
		 * Use the ECash ease-of-use-method: ECash::getCustomerById()
		 *
		 * @param DB_IConnection_1 $db
		 * @param int $customer_id
		 * @param int $company_id
		 * @return ECash_Customer
		 */
		public static function getByCustomerId(DB_IConnection_1 $db, $customer_id, $company_id = NULL)
		{
			$customer = new ECash_Customer($db, $company_id);
			$customer->customer_id = $customer_id;

			return $customer;
		}

		public static function getByApplicationId(DB_IConnection_1 $db, $application_id, $company_id = NULL)
		{
			$customer = new ECash_Customer($db, $company_id);
			$customer->customer_id = DB_Util_1::querySingleValue($db, "select customer_id from application where application_id = ?", array($application_id));

			return $customer;
		}

		/**
		 * Returns an array of ECash_Application(s) based on customer_id
		 *
		 */
		public function getApplications()
		{
			$applications = array();
			$application_models = $this->getApplicationList();
			
			foreach($application_models as $model)
			{
				$application = ECash::getFactory()->getApplication($model->application_id, $model->company_id);
				$application->setModel($model);
				$applications[$model->application_id] = $application;
			}
			
			return $applications;
		}
		
		/**
		 * Returns application list model
		 * 
		 * @return ECash_Models_ApplicationList
		 */
		protected function getApplicationList()
		{
			$application_list = ECash::getFactory()->getModel('ApplicationList');
			$application_list->loadBy(array('customer_id' => $this->customer_id, 'company_id' => $this->company_id));

			return $application_list;			
		}

		public function getPaidCount()
		{
			return $this->data->getPaidCount($this->customer_id, $this->company_id);
		}
		
		/**
		 * Finds the company_id
		 *
		 * @return int
		 */
		protected function getCompanyId()
		{
			if ($this->model === NULL && $this->company_id !== NULL)
			{
				return $this->company_id;
			}

			return $this->getModel()->company_id;
		}

		/**
		 * Finds the customer_id
		 *
		 * @return int
		 */
		public function getCustomerId()
		{
			if ($this->customer_id !== NULL)
			{
				return $this->customer_id;
			}

			return $this->getModel()->customer_id;
		}

		/* 
		 * ReCash ReFactor from here Down 
		 */
			
		/**
		 * Creates a Customer in the customer table
		 *
		 * @TODO rename
		 * @param string $ssn - '123121234'
		 * @param string $login - sjones_1
		 * @param string $password - password encrypted with Crypt_3
		 * @return integer $customer_id
		 */
		public function Create_Customer($applications)
		{
			$customer_model = $this->getModel();
			if(! array($applications))
			{
				throw  new Exception ("Must pass an array of application ID's!");
			}

			if(!$customer_model->ssn)
			{
				throw new Exception ("Customer must have SSN.");
			}			

			if($this->data->getCustomerIDBySSN($customer_model->ssn,$this->company_id))
			{
				return false;
			}
			
			$app_model = new ECash_Application();
			$app_model->application_id = $applications[0];
			$login_prefix = substr($app_model->model->first_name,1,1).$app_model->model->last_name;
			list($customer_model->login, $customer_model->password) = $this->Generate_Login_and_Password($login_prefix);
			
			$customer_model->save();
			$this->customer_id = $customer_model->customer_id;

	
			// I know, this is two steps and it could be one.
			$this->data->Update_Customer_ID_on_Applications($applications, $this->customer_id,$this->company_id);
			$this->data->Update_Application_SSN($this->ssn, $applications, $this->customer_id,$this->company_id);
	
			return $this->customer_id;
		}
	

	
		/**
		 * Set's a new SSN number in the customer table
		 *
		 * @TODO rename
		 * @param string $ssn (example: 123121234)
		 */
		public function Update_Customer_SSN($ssn)
		{
			$model = $this->getModel();
			$model->ssn = $ssn;
			$model->save();
		}
	

		/**
		 * Generates a login_name and password
		 *
		 * $login_prefix - First initial, last name
		 *
		 * @TODO rename
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
				if($this->data->Login_Exists($login,$this->company_id))
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
		 * Generates a rather generic password per OLP's specifications: cash + 3 random numbers
		 *
		 * @TODO rename
		 * @return string - Example: cash582
		 */
		private function Generate_Password()
		{
			$prefix = 'cash';
			$suffix = rand(100, 999);
			$password = $prefix . $suffix;
			return $password;
		}

		/**
		 * @TODO rename, and I don't think $this->applications should be set here, yes/no?
		 */
		public function Get_SSN()
		{			
			if(empty($this->ssn) && ! is_array($this->applications))
			{
				$this->applications = $this->getApplicationList();
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
		 * Formats an unformatted social security number
		 *
		 * @TODO rename
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
		 * @TODO rename
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
		 * @TODO rename
		 * @param integer $customer_id
		 * @return boolean - true if successful, false if not
		 */
	
		public function Merge_Applications($applications = NULL)
		{
			if(is_null($applications))
			{
				return false;
			}
	
			$this->date->Update_Customer_ID_on_Applications($applications, $this->customer_id, $this->company_id);
			$this->date->Update_Application_SSN($this->ssn, $applications, $this->customer_id, $this->company_id);
		}

	}
?>
