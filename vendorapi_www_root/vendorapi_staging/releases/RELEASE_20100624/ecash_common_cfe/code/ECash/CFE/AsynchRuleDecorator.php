<?php

	/**
	 * Decorates an array of rules and keeps track of which ruleset matched
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_AsynchRuleDecorator implements ECash_CFE_ICondition, ECash_CFE_ISchedulable
	{
		protected $rules = array();

		protected $ruleset_id;
		protected $rule;
		protected $is_test;

		public function __construct(array $rules, $db, $is_test = false)
		{
			$this->db = $db;
			$this->rules = $rules;
			$this->is_test = $is_test;
		}

		/**
		 * Returns the cfe_rule_set_id for the matching ruleset
		 *
		 * @return int
		 */
		public function getRulesetId()
		{
			return $this->ruleset_id;
		}

		/**
		 * Evaluates all of the rules, stopping at the first that matches
		 *
		 * @param ECash_CFE_IContext $c
		 * @return bool
		 */
		public function isValid(ECash_CFE_IContext $c)
		{
			$this->ruleset_id = NULL;

			foreach ($this->rules as $ruleset_id=>$rule)
			{
				if ($valid = $rule->isValid($c))
				{
					if($this->is_test || $this->qualify($ruleset_id, $c)) 
					{
						$this->ruleset_id = $ruleset_id;
						$this->rule = $rule;
						return TRUE;
					}
				}
			}
			return FALSE;
		}

		/**
		 * Adds the actions for the matched rules to the agenda
		 *
		 * @param ECash_CFE_Agenda $a
		 */
		public function addToAgenda(ECash_CFE_Agenda $a)
		{
			if ($this->rule instanceof ECash_CFE_ISchedulable)
			{
				$this->rule->addToAgenda($a);
			}
			else
			{
				$a->addAction($this->rule);
			}
		}
		
		protected function qualify($rule_set_id, $c)
		{
			$factory = new ECash_CFE_RulesetFactory($this->db);
			$rule_set = $factory->fetchRuleset($rule_set_id);
			if (!empty($rule_set[ECash_CFE_AsynchEngine::EVENT_QUALIFY]) && is_array($rule_set[ECash_CFE_AsynchEngine::EVENT_QUALIFY]))
			{
				foreach ($rule_set[ECash_CFE_AsynchEngine::EVENT_QUALIFY] as $rule) 
				{
					if (!$rule->isValid($c)) 
					{
						return false;
					}
				}
			}
			return true;
		}
	}

?>
