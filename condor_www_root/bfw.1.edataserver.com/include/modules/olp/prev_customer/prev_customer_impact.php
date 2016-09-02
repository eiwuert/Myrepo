<?php


	class Previous_Customer_Impact extends Previous_Customer_Check
	{
		public function __construct($sql, $db, $property, $mode, $bb_mode = NULL)
		{
			$this->properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_IMPACT);
			parent::__construct($sql, $db, $property, $mode, $bb_mode);
		}
		
		/**
		 * Find applications by bank account number.  Overloaded from
		 *    Previous_Customer_Check::Find_By_Account so it can bypass
		 *    the ssn portion of the check.
		 *
		 * @param string $account_number The account number of the application
		 * @param string $aba The bank routing number
		 * @param string $ssn The SSN - Passed in, but NOT used!
		 * @return array
		 */
		protected function Find_By_Account($account_number, $aba, $ssn)
		{
			// get an array of all possible leading zero permutations
			$accounts = implode("', '", self::Permutate_Account($account_number));
			
			$where = "
				bank_aba = '{$aba}'
				AND bank_account IN ('{$accounts}')
			";
			$_SESSION['where'] = $where;
			return $this->From_Query($where);
		}
		/**
		 * Decide what to do if we find Active Applications
		 * deny if we find any already going on applications and remove impact targets
		 * known codecheck error on name because of overriding a legacy method
		 *
		 *  @param array $results an array of status and mixed results from Check
		 *  @param array $targets list of current targets
		 *  @return string
		 */
		protected function Decide_Active($results, &$targets)
		{
			// check if we are at or above the current threshold for active applications
			// set to 1 in prev_customer_check.php
			if(count($results[self::STATUS_ACTIVE]) >= $this->active_threshold)
			{
				$result = self::RESULT_OVERACTIVE;
				$targets = array();
			}
			return $result;
		}
	}


?>
