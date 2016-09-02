<?php

	class ECash_BusinessRulesCache extends ECash_BusinessRules
	{
		static $rule_sets = array();
		static $application_rule_set_ids = array();

		/**
		 * Caches results of parent business_rules function.
		 *
		 * @param integer $application_id
		 * @param boolean $cache FALSE to override any existing cached values.
		 */
		public function Get_Rule_Set_Id_For_Application($application_id, $cache = TRUE)
		{
			if (empty(self::$application_rule_set_ids[$application_id]) || !$cache)
			{
				self::$application_rule_set_ids[$application_id] = parent::Get_Rule_Set_Id_For_Application($application_id);
			}

			return self::$application_rule_set_ids[$application_id];
		}

		/**
		 * Caches results of parent business_rules function.
		 *
		 * @param integer $rule_set_id
		 * @param boolean $cache FALSE to override any existing cached values.
		 */
		public function Get_Rule_Set_Tree($rule_set_id, $cache = TRUE)
		{
			$rule_set_id = (int)$rule_set_id;
			if (empty(self::$rule_sets[$rule_set_id]) || !$cache)
			{
				self::$rule_sets[$rule_set_id] = parent::Get_Rule_Set_Tree($rule_set_id);
			}

			return self::$rule_sets[$rule_set_id];
		}
	}

?>