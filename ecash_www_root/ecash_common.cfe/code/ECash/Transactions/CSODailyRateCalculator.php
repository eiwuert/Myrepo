<?php

/**
 * Performs CSO calculation charges.
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @package Transactions
 */
class ECash_Transactions_CSODailyRateCalculator extends ECash_Transactions_DailyRateCalculator
{
	protected $cso_fee_percent;

	public function __construct(ECash_Transactions_Rounder $rounder, $percent, $accrual_limit, $cso_fee_percent)
	{
		parent::__construct($rounder, $percent, $accrual_limit);
		$this->cso_fee_percent = $cso_fee_percent;
	}

	/**
	 * Returns the APR.
	 * 
	 * Originally from CFE_eCash_API_2 getAPR()
	 * 
	 * @param int $date_fund_actual Unix Timestamp
	 * @param int $date_first_payment Unix Timestamp
	 * @return float
	 */
	public function getAPR($date_fund_actual = NULL, $date_first_payment = NULL)
	{
		//@TODO not sure if the same rounder is supposed to be used for APR
		$svc_chg_apr = $this->round($this->percent * 52); 
			
		if($date_fund_actual && $date_first_payment)
		{
			$num_days = Date_Util_1::dateDiff($date_fund_actual, $date_first_payment);
			$num_days = ($num_days < 1) ? 1 : $num_days;
			
			//THAT'S RIGHT, THIS WILL EXPLODE IF WE CHANGE IT FROM A STRAIGHT PERCENTAGE!
			//@TODO not sure if the same rounder is supposed to be used for APR
			$cso_broker_apr = $this->round(($this->cso_fee_percent / $num_days) * 365);
			return $cso_broker_apr + $svc_chg_apr;
		}
		else
		{
			throw new Exception (__METHOD__ . ": CSO applications require starting and ending timestamps for the relevant time period.");
		}
	}
}

?>
