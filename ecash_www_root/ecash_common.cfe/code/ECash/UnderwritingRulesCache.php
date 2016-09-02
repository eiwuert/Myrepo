<?php
/**
 * Cache for the model (database/queries) for the admin access of the campaign to inquiry rules.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

	class ECash_UnderwritingRulesCache extends ECash_UnderwritingRules
	{
		static $rule_sets = array();

		/**
		 * Caches results of parent underwriting_rules function.
		 *
		 * @param integer $rule_set_id
		 * @param boolean $cache FALSE to override any existing cached values.
		 */
		public function Get_Underwriting_Rules($cache = TRUE)
		{
			if (empty(self::$rule_sets[0]) || !$cache)
			{
				self::$rule_sets[0] = parent::Get_Underwriting_Rules($rule_set_id);
			}

			return self::$rule_sets[0];
		}
	}

?>
