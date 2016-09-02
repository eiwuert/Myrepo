<?php
/**
 * OLPECash_CFE_Rules
 * 
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
require_once 'ecash_common/ecash_api/loan_amount_calculator.class.php';

/**
 * OLPECash_CFE_Rules class to front end ECash Rule access to OLP
 * applciations
 * 
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 *
 */
class OLPECash_CFE_Rules
{
	/**
	 * LDB connection
	 *
	 * @var DB_Database_1
	 */
	protected $ldb;
	
	/**
	 * Loan type for rules
	 *
	 * @var OLPECash_LoanType
	 */
	protected $loan_type;
	
	/**
	 * eCash loan type short
	 * 
	 * @var string
	 */
	protected $loan_type_short;
	
	/**
	 * Property_short of enterprise customer
	 *
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * Collection of user rule names and value pairs in the format
	 * array(array('name','value'))
	 *
	 * @var array
	 */
	protected $user_rules = array();
	
	/**
	 * Rule set object
	 *
	 * @var std_class
	 */
	protected $rule_set;
	
	/**
	 * Class constructor creates database connection and determines loan type
	 *
	 * @param string $property_short Property_short of enterprise customer
	 * @param string $mode Operating mode
	 * @param string $loan_type Loan type from OLPECash_LoanType constants
	 */
	public function __construct($property_short, $mode, $loan_type = OLPECash_LoanType::TYPE_PAYDAY)
	{
		$this->property_short = $property_short;

		// Make LDB connection
		try 
		{
			$server_data = DBInfo_Enterprise::getDBInfo($property_short, $mode);
			$config = new DB_MySQLConfig_1(
				$server_data['host'],
				$server_data['user'],
				$server_data['password'],
				$server_data['db'],
				$server_data['port']
			);
			$this->ldb = $config->getConnection();
		}
		catch (Exception $dberror)
		{
			throw $dberror;
		}
		
		$this->loan_type_short = $loan_type;
		
		// Grab loan type and rule set information
		$this->loan_type = new OLPECash_LoanType($property_short, $this->ldb, $loan_type);
	}
	
	/**
	 * Gets rule set based on the previously determined property_short and loan_type
	 *
	 * @param boolean $add_user_rules Should the function add user rules to the rule set
	 * @return stdClass
	 */
	public function getRuleSet($add_user_rules = TRUE)
	{
		if (!$this->rule_set instanceof stdClass )
		{
			$rule_set = new stdClass();
			$rule_set->company_id = OLPECash_Util::getCompanyID($this->ldb, $this->property_short);
			
			$loan_type_map = array(
				'loan_type_id',
				'loan_type_name',
				'rule_set_id',
			);
			
			foreach ($loan_type_map as $field)
			{
				$rule_set->{$field} = $this->loan_type->{$field};
			}
			
			$rule_set->business_rules = $this->loan_type->rule_set;
			
		
			if ($add_user_rules)
			{
				foreach ($this->getUserRules() as $user_rule)
				{
					$rule_set->{$user_rule['name']} = $user_rule['value'];
				}
			}
			
			$this->rule_set = $rule_set;
		}
		
		return $this->rule_set;
	}
	
	/**
	 * Get the maximum fund amount from ECash
	 *
	 * @param double $income_monthly_net Monthly net income of applicant
	 * @param boolean $is_react Is application a react?
	 * @param integer $paid_apps Number of apps that are paid off loans
	 * @return double
	 */
	public function getMaxFundAmount($income_monthly_net, $is_react, $paid_apps = 0)
	{
		$rule_set = $this->getRuleSet();
		$rule_set->income_monthly = $income_monthly_net;
		$rule_set->is_react = $is_react?'yes':'no';
		$rule_set->num_paid_applications = $paid_apps;
		$calc = LoanAmountCalculator::Get_Instance($this->ldb, $this->property_short);
		return $calc->calculateMaxLoanAmount($rule_set);
	}
	
	/**
	 * Get the minimum fund amount to offer the applicant
	 *
	 * @param boolean $is_react Is the application and react?
	 * @return double
	 */
	public function getMinFundAmount($is_react)
	{
		$rule_set = $this->getRuleSet();
		return ($is_react)
				? $rule_set->business_rules['minimum_loan_amount']['min_react']
				: $rule_set->business_rules['minimum_loan_amount']['min_non_react'];
	}
	
	/**
	 * Get the fund amount offer increment
	 *
	 * @return double
	 */
	public function getFundAmountIncrement()
	{
		$rule_set = $this->getRuleSet();
		return $rule_set->business_rules['loan_amount_increment'];
	}
	
	/**
	 * Set a single user rule.
	 *
	 * @param string $rule_name Name of the rule
	 * @param mixed $value Value of the rule
	 * @return NULL
	 */
	public function setUserRule($rule_name, $value)
	{
		$this->user_rules[] = array('name' => $rule_name, 'value' => $value);
	}

	/**
	 * Set all user rules overriding any previous rules.
	 *
	 * @param array $rules_array Array of name vakue pairs for user rules
	 * @return NULL
	 */
	public function setUserRules(array $rules_array)
	{
		$this->user_rules = $rules_array;
	}
	
	/**
	 * Get the array of user rule name value pairs
	 *
	 * @return array
	 */
	public function getUserRules()
	{
		return $this->user_rules;
	}
	
	/**
	 * Get finance information from ECash API based on the loan
	 * amount and period determined by the days between the
	 * fund date and payoff date
	 *
	 * @param timastamp $payoff_date End date for the loan period
	 * @param timestamp $fund_date Start date for teh loan period
	 * @param double $loan_amount Amount of the loan
	 * @return array Finance information
	 */
	public function getFinanceInfo($payoff_date, $fund_date, $loan_amount)
	{
		/* getInterestAmount requires date string not timestamp */
		$start_date = date('Ymd', $fund_date);
		$end_date = date('Ymd', $payoff_date);

		/* Get the ECash company ID to pass to Get_eCash_API */
		$ecash_company_id = OLPECash_Util::getCompanyID($this->ldb, $this->property_short);

		/* Use eCash_API_2 to return an instance of the proper eCash API
			Be warned all APIs are not the same
			Also, the API requires an application ID but as long as you pass the 
			eCash company ID, it won't use the application ID during this call.
			All that being said, we pass a dummy valuer of 1 for the app ID */
		$ecash_api = eCash_API_2::Get_eCash_API($this->property_short, $this->ldb, 1, $ecash_company_id);

		/* Get the APR from eCash API */
		$apr = $ecash_api->getAPR($this->loan_type_short, $this->property_short, $fund_date, $payoff_date);

		/* Get the finance charge */
		$finance_charge = $ecash_api->getInterestChargeAmount(
			$this->property_short,
			$this->loan_type_short,
			$loan_amount,
			$start_date,
			$end_date
		);
		$total_payments = ($loan_amount + $finance_charge);
		
		/* Prepare and return the expected finance information */
		if (is_numeric($finance_charge) && is_numeric($apr) && is_numeric($total_payments))
		{

			$fi = array();
			$fi['finance_charge'] = $finance_charge;
			$fi['apr'] = $apr;
			$fi['total_payments'] = $total_payments;

		}
		else
		{
			$fi = FALSE;
		}

		return $fi;
	}
}
?>
