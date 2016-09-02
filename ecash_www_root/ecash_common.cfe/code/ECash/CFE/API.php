<?php

	class CFE_API
	{
		public function fetchAllRulesets($loan_type) {}
		public function fetchRuleset($id) {}
		public function saveRuleset($loan_type, CFE_API_RulesetDef $rs) {}

		/**
		 * @return array of CFE_VariableDef
		 */
		public function getAvailableVariables() {}

		/**
		 * @return array of CFE_IAction
		 */
		public function getAvailableActions() {}
	}

?>
