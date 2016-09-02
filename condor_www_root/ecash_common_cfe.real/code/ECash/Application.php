<?php

	/**
	 * business object representing the ecash application
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Application extends Object_1
	{
		/**
		 * @var int
		 */
		protected $application_id;

		/**
		 * @var ECash_Models_Application
		 */
		protected $model = NULL;

		/**
		 * @var bool
		 */
		protected $have_model = FALSE;

		/**
		 * @var ECash_Application_Flags
		 */
		protected $flags = NULL;

		/**
		 * @var ECash_Application_Comments
		 */
		protected $comments = NULL;

		/**
		 * @var int
		 */
		protected $company_id = NULL;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var ECash_Application_ContactFlags
		 */
		protected $contact_flags;

		/**
		 * @var ECash_Fraud_Application
		 */
		protected $fraud_application;

		/**
		 * @var ECash_Application_Affiliations
		 */
		protected $affiliations;
		
		/**
		 * @var ECash_Transactions_Schedule
		 */
		protected $schedule;

		/**
		 * @var ECash_Scheduling_ScheduleBuilder
		 */
		protected $schedule_builder;
		
		/**
		 * @var ECash_Application_AuditLog
		 */
		protected $audit_log;
		
		/**
		 * @var ECash_Qualify
		 */
		protected $qualify;

		/**
		 * @var Date_PayDateModel_1
		 */
		protected $paydate_model;

		/**
		 * @var Date_PayDateCalculator_1
		 */
		protected $paydate_calc;
		
		/**
		 * @var Date_PayDateCalculator_1
		 */
		protected $loan_amount_calc;

		/**
		 * @var ECash_Data_Application
		 */
		protected $data;
		
		/**
		 * @var ECash_Scheduling_InterestCalculator
		 */
		protected $interest_calculator;

		/**
		 * @var ECash_CFE_DefaultContext
		 */
		private $context;
		
		/**
		 * @param int $application_id
		 */
		public function __construct(DB_IConnection_1 $db, $application_id, $company_id = NULL)
		{
			$this->application_id = $application_id;
			$this->db = $db;
			$this->data = ECash::getFactory()->getData('Application', $this->db);
		}

		/**
		 * @return int
		 */
		public function getId()
		{
			return $this->application_id;
		}

		/**
		 * Returns the comments business object instance, initialized for this application
		 *
		 * @return ECash_Application_Comments
		 */
		public function getComments()
		{
			if ($this->comments === NULL)
			{
				$this->comments = new ECash_Application_Comments($this->db, $this->application_id, $this->getCompanyId());
			}

			return $this->comments;
		}

		/**
		 * Gets the current instance of the CFE engine
		 *
		 * @return ECash_CFE_Engine
		 */
		public function getEngine()
		{
			$f = new ECash_CFE_RulesetFactory($this->db);

			//temporary hack to insure we have a model and context
			$this->getModel();
			$engine = ECash_CFE_Engine::getInstance($this->context);
			$engine->setRuleset($f->fetchRuleset($this->model->cfe_rule_set_id));

			return $engine;
		}

		/**
		 * Returns the application model instance for this application
		 *
		 * @return ECash_Models_Application
		 */
		public function getModel()
		{
			if ($this->have_model === FALSE)
			{
				$this->model = ECash::getFactory()->getModel('Application', $this->db);

				if (!$this->model->loadBy(array('application_id' => $this->application_id)))
				{
					throw new ECash_Application_NotFoundException("Unable to locate application in the databases. (application ID: {$this->application_id})");
				}
				
				$this->context = new ECash_CFE_DefaultContext(
					$this->model,
					$this->db,
					array()
					);

				// attach to the new application				
				$app_observer = new ECash_CFE_ApplicationModelObserver();
				$app_observer->attach($this->model);
				
				$this->have_model = TRUE;
			}

			return $this->model;
		}
		/**
		 * Returns whether application exists
		 *
		 * @return boolean
		 */
		public function exists()
		{
			try
			{
				$this->getModel();
				return true;
			}
			catch(ECash_Application_NotFoundException $e)
			{
				return false;
			}
		}
		/**
		 * Note: This needs to go away. It is only here for the trans_obj hack I found.
		 *  - johnh
		 *
		 *  @depricated
		 * @param ECash_Models_WritableModel $model
		 */
		public function setModel(ECash_Models_Application $model)
		{
			//throw new Exception('what are you doing?');
			$this->model = $model;
			$this->have_model = TRUE;
		}

		/**
		 * returns the application flags business object for this application
		 *
		 * @return ECash_Application_Flags
		 */
		public function getFlags()
		{
			if ($this->flags === NULL)
			{
				$this->flags = new ECash_Application_Flags($this->db, $this->application_id, $this->getCompanyId());
			}

			return $this->flags;
		}

		/**
		 * returns the application audit log business object for this application
		 *
		 * @return ECash_Application_AuditLog
		 */
		public function getAuditLog()
		{
			if ($this->audit_log === NULL)
			{
				$this->audit_log = new ECash_Application_AuditLog($this->db, $this->application_id, $this->getCompanyId());
			}

			return $this->audit_log;
		}

		/**
		 * Returns the application contact flags object for this application
		 *
		 * @return ECash_Application_ContactFlags
		 */
		public function getContactFlags()
		{
			if ($this->contact_flags === NULL)
			{
				$this->contact_flags = new ECash_Application_ContactFlags($this->db, $this->application_id, $this->getCompanyId());
			}

			return $this->contact_flags;
		}

		/**
		 * Returns the fraud application object for this application
		 *
		 * @TODO not sure if this is bothersome that it's in the 'Fraud' namespace rather than 'Application' [JustinF]
		 * @return ECash_Fraud_Application
		 */
		public function getFraud()
		{
			if ($this->fraud_application === NULL)
			{
				$this->fraud_application = new ECash_Fraud_Application($this->db, $this->application_id);
			}

			return $this->fraud_application;
		}
		
		/**
		 * Returns the affiliations component for this application
		 *
		 * @return ECash_Application_Affiliations
		 */
		public function getAffiliations()
		{
			if ($this->affiliations === NULL)
			{
				$this->affiliations = new ECash_Application_Affiliations($this->db, $this->application_id, $this->getCompanyId());
			}
			
			return $this->affiliations;
		}

		/**
		 * Returns the appropriate ecash qualify class
		 *
		 * @return ECash_Qualify
		 */
		public function getQualify()
		{
			if($this->qualify == NULL)
			{
				$business_rules = new ECash_BusinessRules($this->db);
				$qualify_class = ECash::getFactory()->getClassString('Qualify');
				$this->qualify = new $qualify_class(ECash::getFactory()->getDateNormalizer(), $business_rules, $this->Model->loan_type_id);
			}
			
			return $this->qualify;
		}
		
		/**
		 * returns the application pay dates business object for this application
		 * 'paydate_model',
		 * 'day_of_week', 'last_paydate', 'day_of_month_1',
		 * 'day_of_month_2', 'week_1', 'week_2',
		 * @return Date_PayDateModel_1
		 */
		public function getPayDateModel()
		{
			if ($this->paydate_model === NULL)
			{
				$model = $this->getModel();
				$this->paydate_model = Date_PayDateModel_1::getModel(
								$model->paydate_model,
								$model->day_of_week,
								$model->last_paydate,
								$model->day_of_month_1,
								$model->day_of_month_2,
								$model->week_1,
								$model->week_2);
			}
			
			return $this->paydate_model;
		}
		
		/**
		 * @return Date_PayDateCalculator_1
		 */
		public function getPayDateCalculator()
		{
			if($this->paydate_calc === NULL)
			{
				$this->paydate_calc = new Date_PayDateCalculator_1($this->getPayDateModel(), new Date_PayDateNormalizer_1(new Date_BankHolidays_1(), $this->getModel()->income_direct_deposit));
			}
			return $this->paydate_calc;
		}

		/**
		 * Returns the schedule object for this application
		 *
		 * @return ECash_Transactions_Schedule
		 */
		public function getSchedule()
		{
			if ($this->schedule === NULL)
			{
				$this->schedule = new ECash_Transactions_Schedule($this);
			}

			return $this->schedule;
		}
	
		/**
		 * Gets a schedule builder based on this applications data
		 *
		 * @return ECash_Scheduling_ScheduleBuilder
		 */
		public function getScheduleBuilder()
		{
			if ($this->schedule_builder === NULL)
			{
				$schedule = $this->getSchedule();

				$fund_type = NULL;
				if($fund_tx = $schedule->Analyzer->getFund())
				{
					$fund_type = $fund_tx->getType();
				}
				else
				{
					/** @TODO get the fund type for this app based on loan_type or whatever */
					$fund_type = NULL;
				}

				/** @TODO instantiate the correct interest calculator and schedule builder based on loan_type or whatever */
				$int_calc = $this->getInterestCalculator();	
				$this->schedule_builder = new ECash_Scheduling_ScheduleBuilder();
				$this->schedule_builder->setInterestCalculator($int_calc);
				$this->schedule_builder->setSchedule($schedule);
				$this->schedule_builder->setFundType($fund_type);
				$this->schedule_builder->setFundAmount($this->Model->fund_actual);
				$this->schedule_builder->setFundDate($this->Model->date_fund_actual);
				$this->schedule_builder->setFirstPaymentDate($this->Model->date_first_payment);
				$this->schedule_builder->setPayDateCalculator($this->PayDateCalculator);
			}

			return $this->schedule_builder;			
		}		
		/**
		 * Returns whether or not this app is in watch status.
		 *
		 * @return bool
		 */
		public function getWatchStatus()
		{
			return ($this->getModel()->is_watched == 'yes');
		}

		/**
		 * Sets this app to watch status
		 *
		 */
		public function setWatchStatus()
		{
			$model = $this->getModel();
			$model->is_watched = 'yes';
			$model->modifying_agent_id = ECash::getAgent()->getAgentId();
			$model->save();
		}

		/**
		 * Clears watch status from this app.
		 *
		 */
		public function clearWatchStatus()
		{
			$model = $this->getModel();
			$model->is_watched = 'no';
			$model->modifying_agent_id = ECash::getAgent()->getAgentId();
			$model->save();
		}

		/**
		 * Returns brief summary info about this applications
		 * react children
		 *
		 * @return stdClass[]
		 * @todo No queries in business objects
		 */
		public function getReactChildren()
		{
			$query = "
				SELECT
				    app.application_id,
				    app.olp_process,
				    ra.agent_id,
				    app.application_status_id
				FROM react_affiliation ra
				JOIN application app on (app.application_id = ra.react_application_id)
				where
				  ra.application_id = :application_id
				  and ra.company_id = :company_id";

			$result = DB_Util_1::queryPrepared(
				$this->db,
				$query,
				array(
					'application_id' => $this->application_id,
					'company_id' => $this->getCompanyId()
				)
			);
			return $result->fetchAll(PDO::FETCH_OBJ);
		}

		/**
		 * Return Stats for Application
		 * 
		 * @return stdClass[]
		*/
		protected function getStat()
		{
			if (empty($stat))
			{
				$this->stat = new Stat();
			}
	
			return $this->stat;
		}
			
		/**
		 * Returns the app status id for this application
		 *
		 * @return int
		 */
		public function getStatusId()
		{
				return $this->getModel()->application_status_id;
		}

		/**
		 * Returns the app status model for this application.
		 *
		 * @return ECash_Models_Reference_ApplicationStatusFlat
		 */
		public function getStatus()
		{
			$status_id = $this->getStatusId();
			$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			return $asf[$status_id];
		}

		/**
		 * Returns the app status model for the previous
		 *
		 * @return ECash_Models_Reference_ApplicationStatusFlat
		 */
		public function getPreviousStatus()
		{
			$status_id = $this->data->getPreviousStatusId($this->application_id);

			if ($status_id !== FALSE && $status_id !== null)
			{
				$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
				return $asf[$status_id];
			}

			return FALSE;
		}

		/**
		 * Returns the app status model(s) this app has been in
		 *
		 * @return ECash_Models_Reference_ApplicationStatusFlat[]
		 */
		public function getStatusHistory()
		{
			$list = ECash::getFactory()->getModel("StatusHistoryList");
			$list->loadBy(array("application_id" => $this->application_id));

			return $list->toList();
		}

		/*
		 *  Returns the site model for this application
		 * 
		 *  @return ECash_Models_Reference_Site 
		 */
		public function getSite()
		{
			$ref_list = ECash::getFactory()->getReferenceList('Site');
			$list_item = isset($ref_list[$this->model->enterprise_site_id]) ? $ref_list[$this->model->enterprise_site_id] : null;
			return $list_item;			
		}

		/**
		 * Returns the loan type id for this application
		 *
		 * @return int
		 */
		public function getLoanTypeId()
		{
			return $this->getModel()->loan_type_id;
		}

		/**
		 * This might be able to go away
		 *
		 * @return bool
		 */
		public function isInHoldingStatus()
		{
			$disallowed_statuses = array();

			$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

			$disallowed_statuses[] = $asf->toId('hold::arrangements::collections::customer::*root');
			$disallowed_statuses[] = $asf->toId('unverified::bankruptcy::collections::customer::*root');
			$disallowed_statuses[] = $asf->toId('verified::bankruptcy::collections::customer::*root');
			$disallowed_statuses[] = $asf->toId('amortization::bankruptcy::collections::customer::*root');
			$disallowed_statuses[] = $asf->toId('skip_trace::collections::customer::*root');

			$db = ECash_Config::getMasterDbConnection();

			$query = "
				SELECT application_status_id, is_watched
				FROM   application
				WHERE  application_id = ?";

			$st = DB_Util_1::queryPrepared($this->db, $query, array($this->application_id));
			$row = $st->fetch(PDO::FETCH_OBJ);

			return ((
				in_array($row->application_status_id, $disallowed_statuses))
				|| $row->is_watched == 'yes');
		}

		/**
		 * Returns the loan type model for this application
		 *
		 * @return ECash_Models_Reference_LoanType
		 */
		public function getLoanType()
		{
			$loan_type_id = $this->getLoanTypeId();
			$ref_list = ECash::getFactory()->getReferenceList('LoanType');
			return $ref_list[$loan_type_id];
		}

		/**
		 * returns a stdClass of info needed for loan amount calculator.
		 * This is for the old school loan amount calc and will be (hopefully)
		 * eliminated with the integration of justin's replacement.
		 *
		 * @return stdClass
		 */
		public function getLoanAmountMetrics()
		{
			$business_rules = new ECash_BusinessRulesCache($this->db);

			$metrics = $this->data->getLoanAmountMetrics($this->application_id);
			$metrics->business_rules = $business_rules->Get_Rule_Set_Tree($metrics->rule_set_id);

			// :(
			$company_list = ECash::getFactory()->getReferenceList('Company');
			$metrics->display_short = $company_list->toName($this->getCompanyId());
		}

		/**
		 * Returns information about this app's personal references
		 * in array form
		 *
		 * @return stdClass[]
		 */
		public function getPersonalReferences()
		{
			return $this->data->getPersonalReferences($this->application_id);
		}
	
		/*
		 * Returns count of ABA's associated with this applicant
		 */
		public function getABACount()
		{
			return $this->data->getABACount($this->model->bank_aba, $this->getCompanyId());
		}
		
		public function getCollectionsCompany()
		{
			$query = "
				SELECT ecb.ext_collections_co
				FROM application app
				LEFT JOIN ext_collections ec ON ec.application_id = app.application_id
				LEFT JOIN ext_collections_batch ecb ON ecb.ext_collections_batch_id = ec.ext_collections_batch_id
				WHERE
					app.application_id = ?
			";

			return DB_Util_1::querySingleValue($this->db, $query, array($this->application_id));
		}

		/**
		 * This is especially stupid. It's called all over the place.
		 * Time is forcing me to move the code in without much changing ..
		 * but this MUST CHANGE. IT IS HORRIBLE.
		 *
		 * @todo Fix this...
		 */
		public function inactiveCheck()
		{
			$schedule = $this->getSchedule();
			if ($schedule->getAnalyzer()->getBalance() <= 0)
			{
				$as = $_SESSION['current_app']; // App Status can be grabbed from here

				if ($as->level1 == 'external_collections')
				{
					Update_Status(NULL, $this->getId(), array("recovered","external_collections","*root"));
				}
				else
				{
					Update_Status(NULL, $this->getId(), array("paid","customer","*root"));
				}

				$this->Affiliations->expireAll();
				$schedule->removeScheduledTransactions();
				$schedule->save();

				$queue_manager = ECash::getFactory()->getQueueManager();
				$queue_item = new ECash_Queues_BasicQueueItem($this->getId());
				$queue_manager->getQueueGroup('automated')->remove($queue_item);

				return TRUE;
			}

			return FALSE;

		}

		public function getCompanyId()
		{
			return $this->getModel()->company_id;
		}

		/**
		 * The interest calculator to use for this application.
		 *
		 * @return ECash_Scheduling_IInterestCalculator
		 * @todo When biz rules get refactored this needs to change accordingly
		 */
		public function getInterestCalculator()
		{
			if (empty($this->interest_calculator))
			{
				$business_rules = new ECash_BusinessRules($this->db);
				$rules = $business_rules->Get_Rule_Set_Tree($this->getModel()->rule_set_id);
				
				if($rules['service_charge']['svc_charge_type'] === 'Daily')
				{
					$this->interest_calculator = new ECash_Scheduling_DailyInterestCalculator();
				}
				else
				{
					$this->interest_calculator = new ECash_Scheduling_FixedInterestCalculator();
				}
			}
			
			return $this->interest_calculator;
		}


		/**
		 * Default implementation for "new" style (range + increment) loan amounts
		 * "old" style (table lookup) will be overridden by CLK & Impact
		 *
		 * @return ECash_Transactions_ILoanAmountCalculator
		 */
		public function getLoanAmountCalculator()
		{
			if($this->loan_amount_calc === NULL)
			{
				$business_rules = new ECash_BusinessRules($this->db);
				$rules = $business_rules->Get_Rule_Set_Tree($this->getModel()->rule_set_id);

				$is_react = $this->Model->is_react == 'yes' ? TRUE : FALSE;
				$min_amount = $is_react ? $rules['minimum_loan_amount']['min_react'] : $rules['minimum_loan_amount']['min_non_react'];

				$customer = ECash_Customer::getByCustomerId($this->db, $this->Model->customer_id, $this->getCompanyId());
				
				$this->loan_amount_calc = new ECash_Transactions_LoanAmountRangeCalculator();
				$this->loan_amount_calc->setMonthlyNet($this->Model->income_monthly);
				$this->loan_amount_calc->setMinLoanAmount($min_amount);
				$this->loan_amount_calc->setLoanAmountIncrement($rules['loan_amount_increment']);
				$this->loan_amount_calc->setNumPaid($customer->getPaidCount());
				$this->loan_amount_calc->setLoanCap($rules['loan_cap']);
				$this->loan_amount_calc->setLoanPercentage($rules['loan_percentage']);
			}
			return $this->loan_amount_calc;
		}

		/**
		 * Returns the application's ECash Business Rule set
		 *
		 * @return array $rules
		 */
		public function getBusinessRules()
		{
			$business_rules = new ECash_BusinessRules($this->db);
			return $business_rules->Get_Rule_Set_Tree($this->getModel()->rule_set_id);
		}
		
		/**
		 * "magic method" for processing getting a property on the application model
		 *
		 * @param string $property_name
		 * @return mixed
		 */
		public function __get($property_name)
		{
			if (in_array($property_name, $this->getModel()->getColumns()))
			{
				return $this->getModel()->$property_name;
			}
			else
			{
				return parent::__get($property_name);
			}
		}
	
		/**
		 * "magic method" for processing setting a property on the application model
		 *
		 * @param string $property_name
		 * @param mixed $value
		 * @return mixed
		 */
		public function __set($property_name, $value)
		{
			if (in_array($property_name, $this->getModel()->getColumns()))
			{
				return $this->getModel()->$property_name = $value;
			}
			else
			{
				return parent::__set($property_name, $value);
			}
		}
	
		/**
		 * "magic method" for processing methods on the application model
		 *
		 * @param string $function
		 * @param mixed $value
		 * @return mixed
		 */	
		public function __call($function, $args)
		{
			if(method_exists($this->getModel(), $function))
			{
			      $arrCaller = Array( $this->getModel() , $function );
	                      return call_user_func_array( $arrCaller, $args );
			}
			throw new InvalidPropertyException_1($function);
		}
	/**
	 * "magic method" for checking if a property isset on the application model
	 *
	 * @param string $property_name
	 * @return bool
	 */
	public function __isset($property_name)
	{
		if (in_array($property_name, $this->getModel()->getColumns()))
		{
			return $this->getModel()->$property_name != NULL;
		}
		else
		{
			return parent::__isset($property_name);
		}
	}		
}

?>
