<?php

	/**
	 * the AsynchEngine allows you to separate the two phases out.
	 * This makes it possible for OLP to run the rules and save the application
	 * with the possibly modified data and then pass the action with the application
	 * to ECash to save and move to the appropriate queue, etc.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_AsynchEngine
	{
		const EVENT_BEGIN = 'APPLICATION';
		const EVENT_END = 'ACCEPT';
		const EVENT_QUALIFY = "QUALIFY";

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @param DB_IConnection_1 $db
		 */
		public function __construct(DB_IConnection_1 $db, $company_id)
		{
			$this->db = $db;
			$this->company_id = $company_id;
		}

		/**
		 * Runs applicable business rules and returns a result
		 * In addition to a pass/fail, the result contains extra
		 * state information that indicates which actions should
		 * be run upon insertion to the database
		 *
		 * @param array $data OLP application data
		 * @param bool $is_test
		 * @return ECash_CFE_AsynchResult
		 */
		public function beginExecution(array $data, $is_test = false)
		{
			try
			{
				$factory = new ECash_CFE_RulesetFactory($this->db);

				$rules = new ECash_CFE_AsynchRuleDecorator(
					$factory->fetchEvent(self::EVENT_BEGIN, $this->company_id),
					$this->db,
					$is_test
				);

				// can't use the model context here because
				// we don't actually have an application ID yet
				$context = new ECash_CFE_ArrayContext($data);

				$engine = ECash_CFE_Engine::getInstance($context);
				$engine->setRuleset(array(self::EVENT_BEGIN => array($rules)));

				// execute the rules and return the set attributes
				$attr = $engine->executeEvent(self::EVENT_BEGIN);

				$ruleset_id = $rules->getRulesetId();
				$loan_type = ($ruleset_id ? $this->getLoanTypebyCFE($ruleset_id) : NULL);

				$result = new ECash_CFE_AsynchResult(
					$ruleset_id,
					$loan_type,
					$attr
				);
			}
			catch (Exception $e)
			{
				$result = new ECash_CFE_AsynchResult();
			}
			return $result;
		}

		/**
		 * Executes appropriate actions for the previously matched rules
		 *
		 * @param ECash_Models_Application $app
		 * @param ECash_CFE_AsynchResult $result
		 * @return void
		 */
		public function endExecution(ECash_Models_Application $app, ECash_CFE_AsynchResult $result)
		{
			// load the ruleset that accepted the lead
			$factory = new ECash_CFE_RulesetFactory($this->db);
			$rules = $factory->fetchRuleset($result->getRulesetId());

			$context = new ECash_CFE_DefaultContext($app, $this->db);

			// reset attributes that were set during beginExecution
			foreach ($result->getAttributes() as $name=>$value)
			{
				$context->setAttribute($name, $value);
			}

			$engine = ECash_CFE_Engine::getInstance($context);
			$engine->setRuleset($rules);

			return $engine->executeEvent(self::EVENT_END);
		}

		//adding this to pass the loan type based on rule set, should be rewritten to use models if they exist
		protected function getLoanTypebyCFE($CFE_Rule_Set_Id)
		{
			$sql = "
				select loan_type_id,
					name_short
				from cfe_rule_set
					join loan_type using (loan_type_id)
				where cfe_rule_set_id = $CFE_Rule_Set_Id
			";

			return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
		}
	}

?>
