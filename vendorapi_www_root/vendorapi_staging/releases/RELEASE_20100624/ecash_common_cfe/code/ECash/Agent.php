<?php

	/**
	 * Agent business object
	 *
	 */
	class ECash_Agent extends Object_1
	{
		/**
		 * @var int
		 */
		protected $agent_id;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @var ECash_Models_Agent
		 */
		protected $model;

		/**
		 * @var ECash_Agent_Affiliations
		 */
		protected $affiliations;

		/**
		 * @var ECash_Agent_Tracking
		 */
		protected $tracking;

		/**
		 * @var ECash_AgentQueue_Queue
		 */
		protected $queue;

		/**
		 * @param DB_IConnection_1 $db
		 * @param int $company_id
		 */
		private function __construct(DB_IConnection_1 $db, $company_id = NULL)
		{
			$this->db = $db;
			if($company_id === NULL && ($company = ECash::getCompany()))
			{
				$this->company_id = $company->company_id;
			}
			else
			{
				$this->company_id = $company_id;
			}
		}

		/**
		 * @return ECash_Models_Agent
		 */
		public function getModel()
		{
			if ($this->model === NULL)
			{
				$this->model = ECash::getFactory()->getModel('Agent');

				if (!$this->model->loadBy(array('agent_id' => $this->agent_id)))
				{
					throw new Exception("Unable to load agent using agent_id. Agent ID provided was {$this->agent_id}");
				}
			}

			return $this->model;
		}

		/**
		 * @return ECash_Agent_Tracking
		 */
		public function getTracking()
		{
			if ($this->tracking === NULL)
			{
				$this->tracking = new ECash_Agent_Tracking($this->db, $this->agent_id, $this->company_id);
			}

			return $this->tracking;
		}

		/**
		 * @return ECash_Agent_Affiliations
		 */
		public function getAffiliations()
		{
			if ($this->affiliations === NULL)
			{
				$this->affiliations = new ECash_Agent_Affiliations();
			}
			return $this->affiliations;
		}

		/**
		 *
		 * @return ECash_AgentQueue_Queue
		 */
		public function getQueue()
		{
			if ($this->queue === NULL)
			{
				$this->queue = ECash::getFactory()->getQueueManager()->getQueue('Agent');
				$this->queue->setAgentId($this->agent_id);
			}

			return $this->queue;
		}

		/**
		 * Determines if this is a valid login for this agent
		 *
		 * @param string $login the username
		 * @param string $password the password (cleartext)
		 *
		 * @return bool
		 */
		public function authenticate($login, $password)
		{
			$model = $this->getModel();

			return
				(strtolower($model->login) == strtolower($login)
				&& $model->crypt_password == $this->cryptPassword(strtolower($password))
			);
		}


		public function getAgentId()
		{
			if ($this->model === NULL && $this->agent_id !== NULL)
			{
				return $this->agent_id;
			}
			return $this->getModel()->agent_id;
		}

		/**
		 * Returns the first name.
		 *
		 * @return string
		 */
		public function getNameFirst()
		{
			return $this->getModel()->name_first;
		}

		/**
		 * Returns the first name.
		 *
		 * @return string
		 */
		public function getNameLast()
		{
			return $this->getModel()->name_last;
		}

		/**
		 * returns first and last name of agent
		 *
		 * @return first name <SPACE> last name
		 */
		public function getFirstLastName()
		{
			$model = $this->getModel();
			return $model->name_first . ' ' . $model->name_last;
		}

		protected function cryptPassword($password)
		{
			return md5($password);
		}

		/**
		 * creates a new agent object
		 *
		 * @param DB_IConnection_1 $db
		 * @param int $agent_id
		 * @param int $company_id
		 * @return ECash_Agent
		 */
		public static function getByAgentId(DB_IConnection_1 $db, $agent_id)
		{
			$agent = new ECash_Agent($db);
			$agent->agent_id = $agent_id;

			return $agent;
		}

		/**
		 * creates a new agent object using the login and system name
		 *
		 * @param DB_IConnection_1 $db
		 * @param string $system_name_short
		 * @param string $login
		 * @param int $company_id
		 * @return ECash_Agent
		 */
		public static function getBySystemLogin(DB_IConnection_1 $db, $system_name_short, $login)
		{
			$agent = new ECash_Agent($db);
			$agent->model = ECash::getFactory()->getModel('Agent', $db);
			$agent->model->loadBySystemLogin($system_name_short, $login);

			return $agent;
		}

		/**
		 * Updates the date_last_login column in the database
		 */
		public function updateLogin()
		{
			$model = $this->getModel();
			$model->date_last_login = time();
			$model->save();
		}

		public function hasFlag($flag)
		{
			return ECash::getFactory()->getData('Agent')->hasFlag($this->getAgentId(), $flag);
		}
	}
