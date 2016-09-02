<?php

	class ECash_CFE_DefaultContext implements ECash_CFE_IContext
	{
		/**
		 * @var DB_Database_1
		 */
		protected $db;

		/**
		 * Current application
		 *
		 * @var DB_Models_IWritableModel_1
		 */
		protected $application;

		/**
		 * @param DB_Models_IWritableModel_1 $app
		 * @param array $vars
		 */
		 
		
		public function __construct(DB_Models_IWritableModel_1 $app, DB_Database_1 $db)
		{
			$this->application = $app;
			$this->db = $db;
		}

		/**
		 * Gets an attribute; this may be an application property,
		 * a computed value, or a local variable
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function getAttribute($name)
		{
			$func = 'get'.str_replace('_', '', $name);

			/* Order of operations here:
			 *   1. Check application object
			 *   2. Check getVariable functions
			 *   3. Use local variables
			 */
			if (isset($this->application->{$name}))
			{
				return $this->application->{$name};
			}
			elseif (method_exists($this, $func))
			{
				return $this->{$func}();
			}
			elseif ($this->vars && array_key_exists($name, $this->vars))
			{
				return $this->vars[$name];
			}

			throw new ECash_CFE_RuntimeException('Invalid attribute, '.$name);
		}

		/**
		 * Sets an attribute
		 *
		 * @param string $name
		 * @param mixed $value
		 */
		public function setAttribute($name, $value)
		{
			$func = 'set'.str_replace('_', '', $name);

			/* Order of operations is the same as get:
			 *   1. Check application object
			 *   2. Check setVariable functions
			 *   3. Use local variables
			 */
			if (isset($this->application->{$name}))
			{
				$this->application->{$name} = $value;
				return;
			}
			elseif (method_exists($this, $func))
			{
				$this->{$func}($value);
				return;
			}
			elseif ($this->vars && array_key_exists($name, $this->vars))
			{
				$this->vars[$name] = $value;
				return;
			}

			throw new ECash_CFE_RuntimeException('Invalid attribute, '.$name);
		}

		protected function getAge()
		{
			$dob = $this->getAttribute('dob');

			$age = (idate('Y') - idate('Y', $dob));
			if (idate('z') > idate('z', $dob)) $age--;

			return $age;
		}
		
		protected function getAgentId()
		{
			return ECash::getAgent()->getAgentId();
		}

		/**
		 * Returns the application status as a string
		 * @return string
		 */
		protected function getApplicationStatus()
		{
			$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			return $status->toName($this->application->application_status_id);
		}

		/**
		 * Returns the application status as a string
		 * @return string
		 */
		protected function getPreviousStatus()
		{
			$query = "
				SELECT application_status_id
				FROM status_history
				WHERE application_id != ?
				ORDER BY date_status_set DESC
				LIMIT 1,1
			";

			$args = array(
				$this->getAttribute('application_id')
			);
			$retval = $this->db->querySinglecolumn($query, $args);

			return ($retval ? $retval : '0');
		}

		/**
		 * Returns the date of the last loan
		 *
		 * @return int
		 */
		protected function getLastLoanDate()
		{
			$query = "
				SELECT date_status_set
				FROM application
				WHERE ssn = ?
					AND application_id != ?
				ORDER BY date_status_set DESC
				LIMIT 1
			";

			$args = array(
				$this->getAttribute('ssn'),
				$this->getAttribute('application_id')
			);

			return $this->db->querySinglecolumn($query, $args);
		}

		/**
		 * Returns the number of previous loans by SSN
		 *
		 * @return int
		 */
		protected function getPrevLoanCount()
		{
			$query = "
				SELECT COUNT(*)
				FROM application
				WHERE ssn = ?
					AND date_fund_actual < ?
			";

			$args = array(
				$this->getAttribute('ssn'),
				$this->getAttribute('date_fund_actual')
			);

			return $this->db->querySinglecolumn($query, $args);
		}

		/**
		 * Returns TRUE/FALSE based on whether the agent has any active collections affiliations
		 * 
		 * @return boolean
		 */
		protected function getActiveCollectionsControllingAgent()
		{
			$query = "
				SELECT 
					agent_id 
				FROM 
					agent_affiliation 
				WHERE 
					affiliation_status='active' 
				AND 
					(date_available IS NULL OR date_available >= NOW()) 
				AND 
					(date_expiration_actual IS NULL OR date_expiration_actual <= NOW()) 
				AND 
					affiliation_area='collections' 
				AND 
					affiliation_type='owner' 
				AND 
					application_id = ?
				ORDER BY 
					date_created DESC
				LIMIT 1
			";

			$value = $this->db->querySingleValue($query, array($this->getAttribute('application_id')));

			return ($value == NULL) ? 0 : $value;
		}

		/**
		 * Returns the date of the last transaction
		 *
		 * @return int
		 */
		protected function getLastTransDate()
		{
			$query = "
				SELECT date_effective
				FROM transaction_register
				WHERE application_id = ?
					AND transaction_status IN ('complete', 'failed')
				ORDER BY date_effective DESC
				LIMIT 1
			";
			return $this->fromSimpleQuery($query);
		}

		/**
		 * Returns the date of the last failed transaction
		 *
		 * @return int
		 */
		protected function getLastTransFailDate()
		{
			$query = "
				SELECT hist.date_created
				FROM transaction_history AS hist
					JOIN transaction_register AS reg ON (reg.transaction_register_id = hist.transaction_register_id)
				WHERE reg.application_id = ?
					AND reg.status_after = 'failed'
				ORDER BY hist.date_created DESC
				LIMIT 1
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getNumServiceCharges()
		{
			$query = "
				SELECT COUNT(*)
				FROM transaction_register AS register
					JOIN transaction_type AS type
				WHERE register.application_id = ?
					AND type.name_short = 'assess_service_chg'
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getLastDocumentReceived()
		{
			$query = "
				SELECT
					doc_list.name_short
				FROM
					document doc
				JOIN
					document_list doc_list ON (doc_list.document_list_id = doc.document_list_id)
				WHERE
					doc.application_id = ?
				ORDER BY doc.date_modified DESC LIMIT 1
			";

			return $this->fromSimpleQuery($query);
		}

		protected function getNumEventsScheduled()
		{
			$query = "
				SELECT COUNT(*)
				FROM event_schedule
				WHERE event_schedule.application_id = ?
					AND event_schedule.status != 'registered'
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getNumReturns()
		{
			$query = "
				SELECT COUNT(*)
				FROM ach
				WHERE application_id = ?
					AND ach_status = 'returned'
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getNumReturnsFatal()
		{
			$query = "
				SELECT COUNT(*)
				FROM ach
					JOIN ach_return_code AS code ON (code.ach_return_code_id = ach.ach_return_code_id)
				WHERE application_id = ?
					AND ach_status = 'returned'
					AND code.is_fatal = 'yes'
			";
			return $this->fromSimpleQuery($query);
		}
		protected function getHasFatalAppFlag()
		{
			$app_id = $this->getAttribute('application_id');
			$app = ECash::getApplicationById($app_id);			
			$flags = $app->getFlags()->getAll();
			foreach($flags as $entry)
			{
				if($entry['name_short'] == 'has_fatal_ach_failure')
					return 1;
				if($entry['name_short'] == 'has_fatal_card_failure')
					return 1;
			}
			return 0;
		}

		protected function getNumQuickChecks()
		{
			$query = "
				SELECT COUNT(*)
				FROM event_schedule
				WHERE application_id = ?
					AND event_type_id = (SELECT event_type_id FROM event_type WHERE name_short = 'quickcheck')
					AND event_status = 'registered'
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getBalancePrincipal()
		{
			$query = "
				SELECT SUM(amount)
				FROM event_amount ea
					JOIN event_amount_type et ON (et.event_amount_type_id = ea.event_amount_type_id)
					JOIN event_schedule es ON (es.event_schedule_id = ea.event_schedule_id)
				WHERE ea.application_id = ?
					AND es.event_status = 'registered'
					AND et.name_short = 'principal'
			";
			return $this->fromSimpleQuery($query);
		}

		protected function getStatusLevel1()
		{
			$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$statuses = explode('::',$status->toName($this->application->application_status_id));
			return $statuses[1];
		}

		protected function getStatusLevel2()
		{
			$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$statuses = explode('::',$status->toName($this->application->application_status_id));
			return $statuses[2];
		}

		protected function getBalanceFees()
		{
			// @todo store fee name_shorts somewheres?
			$query = "
				SELECT SUM(amount)
				FROM event_amount ea
					JOIN event_amount_type et ON (et.event_amount_type_id = ea.event_amount_type_id)
					JOIN event_schedule es ON (es.event_schedule_id = ea.event_schedule_id)
				WHERE ea.application_id = ?
					AND es.event_status = 'registered'
					AND et.name_short IN ('service_charge', 'fee')
			";
			return $this->fromSimpleQuery($query);
		}

		/**
		 * A simple query being one that just uses the application ID and has a single column
		 *
		 * @param string $query
		 * @return mixed
		 */
		protected function fromSimpleQuery($query)
		{
			return $this->db->querySingleValue($query, array($this->getAttribute('application_id')));
		}


	}

?>
