<?php

	interface ECash_CFE_IRulesetFactory
	{
		public function fetchRuleset($ruleset_id);
	}

	class ECash_CFE_CachingRulesetFactory  implements ECash_CFE_IRulesetFactory
	{
		/**
		 * @var Cache_IStore
		 */
		protected $store;

		/**
		 * @var ECash_CFE_IRulesetFactory
		 */
		protected $factory;

		public function __construct(Cache_IStore $store, ECash_CFE_IRulesetFactory $factory)
		{
			$this->store = $store;
			$this->factory = $factory;
		}

		/**
		 * Attempts to fetch a ruleset from the cache, then the database
		 *
		 * @param int $ruleset_id
		 * @return array
		 */
		public function fetchRuleset($ruleset_id)
		{
			$ruleset = $this->store->get($ruleset_id);

			// if we didn't find it in the cache, get it
			if ($ruleset === FALSE)
			{
				$ruleset = $this->factory->fetchRuleset($ruleset_id);
				$this->store->put($ruleset_id, $ruleset);
			}

			return $ruleset;
		}
	}

	class ECash_CFE_RulesetFactory implements ECash_CFE_IRulesetFactory
	{
		/**
		 * @var DB_Database_1
		 */
		protected $db;

		protected $event_map = array();
		protected $action_map = array();

		/**
		 * @param DB_Database_1 $db
		 */
		public function __construct(DB_Database_1 $db)
		{
			$this->db = $db;

			$this->fetchEventMap();
			$this->fetchActionMap();
		}

		/**
		 * Fetches a ruleset from the database and "compiles" it into something the engine can execute
		 * The format of the result structure returned by this method is
		 * considerably different from that returned by the editing API
		 * (ECash_CFE_API); the API was intentionally kept simple and simply
		 * describes the rules; this builds something that is executable
		 *
		 * @param int $ruleset_id
		 * @return array
		 */
		public function fetchRuleset($ruleset_id)
		{
			$st_cond = $this->queryRulesetConditions($ruleset_id);
			$st_act = $this->queryRulesetActions($ruleset_id);
			
			$condition_table = array();
			$action_table = array();
			
			//Build Conditions Table
			while($cond = $st_cond->fetch())
			{
				$event = $this->event_map[$cond['cfe_event_id']];
				if (!isset($condition_table[$event])) $condition_table[$event] = array();
				
				if (!isset($condition_table[$event][$cond['cfe_rule_id']])) $condition_table[$event][$cond['cfe_rule_id']] = array();
				
				$condition_table[$event][$cond['cfe_rule_id']][] = $this->conditionFromRow($cond);
				
			}
			
			// Build Action Table
			while($act = $st_act->fetch())
			{
				$event = $this->event_map[$act['cfe_event_id']];
				if (!isset($action_table[$event])) $action_table[$event] = array();
				
				if (!isset($action_table[$event][$act['cfe_rule_id']]))
				{
					$action_table[$event][$act['cfe_rule_id']] = array();
					$action_table[$event][$act['cfe_rule_id']]['matched'] = array();
					$action_table[$event][$act['cfe_rule_id']]['unmatched'] = array();
				}
				
				switch ($act['rule_action_type'])
				{
					case 0:
						$action_table[$event][$act['cfe_rule_id']]['matched'][] = $this->actionFromRow($act);
						break;
					case 1:
						$action_table[$event][$act['cfe_rule_id']]['unmatched'][] = $this->actionFromRow($act);
						break;
				}
			}
			
			//Get all unique events
			$events = array_unique(array_merge(array_keys($condition_table), array_keys($action_table)));
			$ruleset = array();
			foreach ($events as $current_event)
			{
				if(empty($condition_table[$current_event])) $condition_table[$current_event] = array();
				if(empty($action_table[$current_event])) $action_table[$current_event] = array();
				//Get all unique rules in event
				$rules = array_unique(array_merge(array_keys($condition_table[$current_event]), array_keys($action_table[$current_event])));
				
				foreach ($rules as $rule) {
					$matched_actions = $action_table[$current_event][$rule]['matched'];
					$unmatched_actions = $action_table[$current_event][$rule]['unmatched'];
					$conditions = empty($condition_table[$current_event][$rule]) ? NULL : $condition_table[$current_event][$rule];
					
					if(empty($conditions)) $conditions = array();
					if(empty($unmatched_actions)) $unmatched_actions = array();
					if(empty($matched_actions)) $matched_actions = array();
					
					
					if (!isset($ruleset[$current_event])) $ruleset[$current_event] = array();
					$ruleset[$current_event][] = new ECash_CFE_Rule($conditions, $matched_actions, $unmatched_actions);
										
				}
								
			}

			return $ruleset;
		
		}

		public function fetchEvent($event, $company_id)
		{
			if (($event_id = array_search($event, $this->event_map)) === FALSE)
			{
				throw new InvalidArgumentException('Invalid event, '.$event);
			}
			
			$st_cond = $this->queryEventConditions($event_id, $company_id);
			$st_act = $this->queryEventActions($event_id, $company_id);

			// fetch the first rows to get us going
			$cond = $st_cond->fetch();
			$act = $st_act->fetch();

			// this will contain our rules
			$ruleset = array();

			while ($cond || $act)
			{
				// because we could have rules with conditions but no actions (??!),
				// or vice versa (more likely, for a default auction), and we're
				// in rule_id order, we take the minimum rule ID
				if ($act && (!$cond || $act['cfe_rule_id'] < $cond['cfe_rule_id']))
				{
					$rule_id = $act['cfe_rule_id'];
					$ruleset_id = $act['cfe_rule_set_id'];
				}
				else
				{
					$rule_id = $cond['cfe_rule_id'];
					$ruleset_id = $cond['cfe_rule_set_id'];
				}

				$conditions = array();
				$match = array();
				$unmatch = array();

				// add all conditions for the current rule
				while ($cond && ($cond['cfe_rule_id'] === $rule_id))
				{
					$conditions[] = $this->conditionFromRow($cond);
					$cond = $st_cond->fetch();
				}

				// add all actions for the current rule
				while ($act && ($act['cfe_rule_id'] === $rule_id))
				{
					// rule_action_type indicates whether the rule gets
					// executed when it matches, or when it stops matching
					// @todo this should rule rule_action_type
					switch (0) //$act['rule_action_type'])
					{
						case 0: $match[] = $this->actionFromRow($act); break;
						case 1: $unmatch[] = $this->actionFromRow($act); break;
					}
					$act = $st_act->fetch();
				}

				// add the rule to our ruleset
				if (!isset($ruleset[$ruleset_id]))
				{
					$ruleset[$ruleset_id] = new ECash_CFE_CompositeCondition(
						array(),
						ECash_CFE_CompositeCondition::OP_OR
					);
				}

				$rule = new ECash_CFE_Rule($conditions, $match, $unmatch);
				$ruleset[$ruleset_id]->addCondition($rule);
			}

			return $ruleset;
		}

		/**
		 * "Compiles" a condition row into a ECash_CFE_ICondition instance
		 *
		 * @param array $row
		 * @return ECash_CFE_ICondition
		 */
		public function conditionFromRow(array $row)
		{
			// @todo this should be indicated in the row
			$value1 = new ECash_CFE_Expression_Variable($row['operand1']);
			$value2 = $row['operand2'];

			return $this->factoryCondition($row['operator'], array($value1, $value2));
		}

		/**
		 * "Compiles" an action row into an ECash_CFE_IAction instance
		 *
		 * @param array $row
		 * @return ECash_CFE_IAction
		 */
		public function actionFromRow(array $row)
		{
			$params = unserialize($row['params']);
			if (!is_array($params)) $params = array();

			$action = $this->action_map[$row['cfe_action_id']];
			return $this->factoryAction($action, $params);
		}

		/**
		 * Factories the proper ICondition implementor based on name
		 *
		 * @param string $name
		 * @param array $params
		 * @return ECash_CFE_ICondition
		 */
		public function factoryCondition($name, array $params)
		{
			// @todo use proper capitalization in db
			$name = ucfirst(strtolower($name));

			$class = 'ECash_CFE_Condition_'.$name;
			return new $class($params[0], $params[1]);
		}

		/**
		 * Factories the proper IAction implementor based on name
		 *
		 * @param string $name
		 * @param array $params
		 * @return ECash_CFE_IAction
		 */
		public function factoryAction($name, array $params)
		{
			$class = 'ECash_CFE_Action_'.$name;
			return new $class($params);
		}

		/**
		 * Queries all the conditions for a given ruleset
		 *
		 * @param int $ruleset_id
		 * @return PDOStatement
		 */
		protected function queryRulesetConditions($ruleset_id)
		{
			$query = "
				SELECT
					cfe_event_id,
					r.cfe_rule_id,
					rc.operator,
					operand1,
					operand1_type,
					operand2,
					operand2_type
				FROM
					cfe_rule AS r
					JOIN cfe_rule_condition AS rc ON (rc.cfe_rule_id = r.cfe_rule_id)
				WHERE
					r.cfe_rule_set_id = ?
				ORDER BY
					r.cfe_event_id,
					r.salience,
					r.cfe_rule_id,
					rc.sequence_no
			";
			return $this->db->queryPrepared($query, array($ruleset_id));
		}

		/**
		 * Queries all the conditions for a given event
		 *
		 * @param int $event_id
		 * @return PDOStatement
		 */
		protected function queryEventConditions($event_id, $company_id)
		{
			$query = "
				SELECT
					r.cfe_rule_set_id,
					r.cfe_rule_id,
					rc.operator,
					operand1,
					operand1_type,
					operand2,
					operand2_type
				FROM
					cfe_rule AS r
					JOIN cfe_rule_condition AS rc ON (rc.cfe_rule_id = r.cfe_rule_id)
					JOIN cfe_rule_set AS rs ON (r.cfe_rule_set_id = rs.cfe_rule_set_id)
					JOIN loan_type AS lt ON (lt.loan_type_id = rs.loan_type_id)
				WHERE
					r.cfe_event_id = ? and
					lt.company_id = ? and
					lt.active_status = 'active'
							
								
				ORDER BY
					r.cfe_rule_set_id,
					r.salience,
					r.cfe_rule_id,
					rc.sequence_no
			";
			return $this->db->queryPrepared($query, array($event_id, $company_id));
		}

		/**
		 * Queries all the actions for a given ruleset
		 *
		 * @param int $ruleset_id
		 * @return PDOStatement
		 */
		protected function queryRulesetActions($ruleset_id)
		{
			$query = "
				SELECT
					cfe_event_id,
					r.cfe_rule_id,
					cfe_action_id,
					0 as rule_action_type,
					params
				FROM
					cfe_rule AS r
					JOIN cfe_rule_action AS ra on (ra.cfe_rule_id = r.cfe_rule_id)
				WHERE
					r.cfe_rule_set_id = ?
				ORDER BY
					r.cfe_event_id,
					r.salience,
					r.cfe_rule_id,
					rule_action_type,
					ra.sequence_no
			";
			return $this->db->queryPrepared($query, array($ruleset_id));
		}

		/**
		 * Queries all the actions for a given event
		 *
		 * @param int $event_id
		 * @return PDOStatement
		 */
		protected function queryEventActions($event_id, $company_id)
		{
			$query = "
				SELECT
					r.cfe_rule_set_id,
					r.cfe_rule_id,
					cfe_action_id,
					0 as rule_action_type,
					params
				FROM
					cfe_rule AS r
					JOIN cfe_rule_action AS ra on (ra.cfe_rule_id = r.cfe_rule_id)
					JOIN cfe_rule_set AS rs ON (r.cfe_rule_set_id = rs.cfe_rule_set_id)
					JOIN loan_type AS lt ON (lt.loan_type_id = rs.loan_type_id)
				WHERE
					r.cfe_event_id = ? and
					lt.company_id = ? and
					lt.active_status = 'active'
				ORDER BY
					r.cfe_rule_set_id,
					r.salience,
					r.cfe_rule_id,
					rule_action_type,
					ra.sequence_no
			";
			return $this->db->queryPrepared($query, array($event_id, $company_id));
		}

		/**
		 * Fetches a map of cfe_event IDs -> values
		 */
		protected function fetchEventMap()
		{
			// @todo This column should be name_short
			$query = "
				SELECT cfe_event_id,
					short_name AS name_short
				FROM
					cfe_event
			";
			$st = $this->db->query($query);

			$this->event_map = array();
			foreach ($st as $row)
			{
				$this->event_map[$row['cfe_event_id']] = $row['name_short'];
			}
		}

		/**
		 * Fetches a map of cfe_action IDs -> values
		 */
		protected function fetchActionMap()
		{
			$query = "
				SELECT cfe_action_id,
					name
				FROM
					cfe_action
			";
			$st = $this->db->query($query);

			$this->action_map = array();
			foreach ($st as $row)
			{
				$this->action_map[$row['cfe_action_id']] = $row['name'];
			}
		}
	}

?>