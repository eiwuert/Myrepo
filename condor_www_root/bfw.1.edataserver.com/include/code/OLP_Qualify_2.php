<?php

	require_once('qualify.2.php');
/**
 * OLP_Qualify_2 Extends the Qualify_2 base library for OLP specific
 * handling
 * 
 * @author Unknown Author <unknown@unknown.unk>
 */
	class OLP_Qualify_2 extends Qualify_2
	{
		/**
		 * Property Short
		 *
		 * @var string
		 */
		protected $property_short;
		
		/**
		 * Is this a titel loan
		 *
		 * @var bool
		 */
		protected $title_loan;

		/**
		 * Construct for class
		 *
		 * @param string $prop Propery short
		 * @param array $holiday_array Holiday array
		 * @param object &$sql OLP SQL
		 * @param object &$ldb LDB SQL
		 * @param object &$applog App Log
		 * @param string $mode System mode
		 * @param bool $title_loan Is this for a title loan
		 * @param bool $loan_type_id eCash Loan type ID
		 * @return void
		 */
		public function __construct($prop, $holiday_array = NULL, &$sql = NULL, &$ldb = NULL, &$applog = NULL, $mode = NULL, $title_loan = FALSE, $loan_type_id = NULL)
		{
			if (is_string($prop))
			{
				$this->property_short = strtolower(Enterprise_Data::resolveAlias($prop));
			}

			$this->title_loan = $title_loan;

			// allow CFE to override the automatic choice of loan type
			$this->loan_type_id = $loan_type_id;

			parent::Qualify_2($prop, $holiday_array, $sql, $ldb, $applog, $mode);
		}

		/**
		 * Get the eCash Loan Type Short based on the class instantiation parameters
		 *
		 * @return string
		 */
		protected function Get_Rule_Config_Loan_Type()
		{
			return self::Get_Loan_Type($this->property_short, $this->title_loan);
		}

		/**
		 * Get the eCash Loan Type Short based on the property_short and loan type
		 *
		 * @param string $property_short Property short
		 * @param bool $title_loan Is this for a title loan
		 * @return string
		 */
		public static function Get_Loan_Type($property_short, $title_loan = FALSE)
		{
			$type = OLPECash_LoanType::TYPE_PAYDAY;

			if ($title_loan)
			{
				$type = OLPECash_LoanType::TYPE_TITLE;
			}
			elseif ($_SESSION['data']['loan_type'] == 'card' || $_SESSION['cs']['loan_type'] == 'card')
			{
				$type = OLPECash_LoanType::TYPE_CARD;
			}

			return OLPECash_LoanType::getLoanTypeShort($property_short, $type);
		}

		/**
		 * Determine if the rule config passed is valid
		 *
		 * @param object $config Config object
		 * @return bool
		 */
		protected function Validate_Rule_Config($config)
		{
			$valid = TRUE;

			if (!Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $this->property_short))
			{
				$valid = parent::Validate_Rule_Config($config);
			}

			return $valid;
		}

		/**
		 * Finance_Info extends the Qualify_2 function of the same name 
		 * 
		 * @param timestamp $payoff_date Pay Off Date
		 * @param timestamp $fund_date Fund Date
		 * @param string $loan_amount Loan Amount
		 * @param double $finance_charge Finance sharge to override function findings
		 * @return array
		 * 
		 * @author Adam Englander <adam.englander@sellingsource.com>
		 * 
		 * This extension was created to replace Qualify_2 functionality with 
		 * the eCash API. As eCash has not completed the API, not all of the
		 * functions used by this function are available to all enterprise clients.
		 * This creates the need for the ugly hack determining which method to use.
		 * The hack should be replaced as the ECash API's are stabilized.
	    */
		public function Finance_Info($payoff_date, $fund_date, $loan_amount, $finance_charge = NULL)
		{
			/* This is the ugly hack that should be removed ASAP */
			if (isset($this->property_short) && Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_LCS, $this->property_short))
			{
				$cfe_rules = new OLPECash_CFE_Rules(
								$this->property_short,
								$mode,
								self::Get_Loan_Type($this->property_short,$this->title_loan));
				$fi = $cfe_rules->getFinanceInfo($payoff_date, $fund_date, $loan_amount);
			}
			else
			{
				$fi = parent::Finance_Info($payoff_date, $fund_date, $loan_amount, $finance_charge);
			}
			return $fi;
		}
		
	}

?>
