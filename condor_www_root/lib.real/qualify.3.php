<?php
/**
	@publicsection
	@public
	@brief
		Qualify Application and Loan Validation

	Qualify performs calculations for Income validation and Loan
	estamates.

	@version
		1.0.0 2008-02-18 - Justin Foell
			- Passthrough functionality to new ecash_common Qualify

	@todo


*/

//you're probably best using AutoLoad.1.php
//with /virtualhosts/ecash_common/code in your path
//rather than these
require_once('ecash_common/code/ECash/Qualify.php');
require_once('ecash_common/code/ECash/BusinessRules.php');

class Qualify_3
{
	public $errors;

	private $use_old_config;
	private $is_wap;

	private $qualify;

	/**
		@publicsection
		@public
		@brief
			Qualify_3

    	@param $holiday_array array Holiday Array
    	@param $sql object OLP MySQL4 SQL
    	@param $ldb object LDB MySQLi SQL REQUIRED FOR THIS VERSION
    	@param $applog object App Logg
    */

	public function Qualify_3($prop, $holiday_array = NULL, &$sql = NULL, &$ldb, &$applog = NULL, $mode = NULL)
	{
		
		$ecash_array = @is_array($_SESSION['config']->ecash3_prop_list)
						? $_SESSION['config']->ecash3_prop_list
						: array();


		$prop = strtolower($prop);

		if(is_null($ldb) && !is_null($mode))
		{
			$ldb = &Setup_DB::Get_Instance('mysql', $mode . '_READONLY', $prop);
		}

		if(in_array($prop,$ecash_array))
		{
			$this->use_old_config = FALSE;
		}
		else
		{
			$this->use_old_config = TRUE;
		}		   

		$this->errors = array();

		$biz_rules = new ECash_BusinessRules(new DB_MySQLiAdapter_1($ldb));
		$this->qualify = new CLK_Qualify(new Date_Normalizer_1(new ArrayIterator($holiday_array)), $biz_rules, $biz_rules->Get_Loan_Type_For_Company($prop));
	}
	
	// Mantis #11748 - function to set the is_wap flag. I dub thee Weapp (WAP React)  [RV]
	public function Set_Is_Weapp($is_wap)
	{
		$this->qualify->setIsWap($is_wap);
	}
	
    /**
		@publicsection
		@public
		@brief
			Calculate From Monthly Net

    	@param $pay_span array Payment Span
    	@param $pay string Payment
    	@return $monthly_net array Monthly Span, FALSE on failure
    */
	public function Calculate_Monthly_Net($pay_span, $pay)
	{
		try
		{
			$paycheck = $this->qualify->getMonthlyNet(strtoupper($pay_span), $pay);
		}
		catch(Exception $e)
		{
			$this->errors[] = "Invalid pay span, or monthly net pay is zero.";
		}

		if($paycheck == NULL)
			return FALSE;

		return $paycheck;
	}

	/**
		@publicsection
		@public
		@brief
			Calculate React Loan Amount (Calculate Loan Amount wrapper)
	* Revision History:
	*		alexanderl - 01/11/2008 - commented rule 6b [alexander][mantis:13793]

    	@param $monthly_net string Monthly Income
    	@param $direct_deposit string Direct Deposit
    	@param int $react_app_id Application ID used to check eCash react rules
    	@return $fund_amount string Fund Amount
    */
	public function Calculate_React_Loan_Amount($monthly_net, $direct_deposit, $react_app_id = 0, $frequency_name = NULL) //mantis:9786 - added $frequency_name
	{
		//@TODO see what we need to keep
		if($this->use_old_config)
		{
			return $this->qualify->getLoanAmount($monthly_net, $direct_deposit);
		}
		return $this->qualify->getReactLoanAmount($monthly_net, $direct_deposit, $react_app_id);
	}

    /**
		@publicsection
		@public
		@brief
			Calculate Loan Amount

		Revision History:
			alexanderl - 09/13/2007 - changed logic for ecash3.0 [mantis:8330]
			alexanderl - 09/14/2007 - returned to the previous state [mantis:8330]

    	@param $monthly_net string Monthly Income
    	@param $direct_deposit string Direct Deposit
    	@return $fund_amount string Fund Amount
    */
	public function Calculate_Loan_Amount($monthly_net, $direct_deposit)
	{
		return $this->qualify->getLoanAmount($monthly_net, $direct_deposit);
	}

	/**
		@publicsection
		@public
		@brief
			 Finance_Info

    	@param $payoff_date timestamp Pay Off Date
    	@param $fund_date timestamp Fund Date
    	@param $loan_amount string Loan Amount
    	@return $finance array Financial Infor
    */
	public function Finance_Info($payoff_date, $fund_date, $loan_amount, $finance_charge = NULL)
	{

		return $this->qualify->getFinanceInfo($payoff_date, $fund_date, $loan_amount, $finance_charge);
	}


    /**
		@publicsection
		@public
		@brief
			Qualify_Person

    	@param $pay_dates array dates as a string (not sure about format)
    	@param $pay_span string MONTHLY, WEEKLY, TWICE_MONTHLY, BI_WEEKLY
    	@param $pay int Monthly income
    	@param $direct_deposit bool Boolean Direct Deposit [NOT USED (should be accounted for in pay_dates)]
    	@param $job_length date Job Length (YYYY-MM-DD) [NOT USED]
    	@param $loan_amount
    	@param $react_loan
    	@param $react_app_id
    	@param $preact [NOT USED (intrinsically set to TRUE if $react_app_id is supplied)]
    	@param $fund_date
    	@return $loan_info array Loan Information
    */
	public function Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length, $loan_amount = NULL, $react_loan = FALSE, $react_app_id = NULL, $preact = FALSE, $fund_date = NULL)
	{
		foreach($pay_dates as $key => $string)
		{
			$pay_dates[$key] = strtotime($string);
		}
		return $this->qualify->getQualifyInfo(new ArrayIterator($pay_dates), $pay_span, $pay, $loan_amount, $react_loan, $react_app_id, $fund_date);
	}
}

?>
