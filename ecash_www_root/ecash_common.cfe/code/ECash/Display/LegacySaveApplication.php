<?php

require_once('qualify.2.php');

class ECash_Display_LegacySaveApplication implements ECash_Display_ILegacySave
{
	/**
	 * @TODO the qualify_2 stuff here might be company specific [JustinF]
	 */
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		//Hopefully someday this stuff will all go away (hence the name 'Legacy')
		//this is kinda goofy getting an application with it's own model.   [JustinF]
		//but it makes this next stuff easier
		$application = ECash::getApplicationById($request->application_id);

		//[#38368] loan rate override
		//allow 'un' overriding the rate
		if(isset($request->rate_override) && empty($request->rate_override))
		{	$application->setRate(NULL);
			//$model->rate_override = NULL;
		}
		elseif(!empty($request->rate_override))
		{
			$application->setRate($request->rate_override);
			//$model->rate_override = $request->rate_override;
		}
		//end [#38368]

		if (($request->date_first_payment_year != '') && ($request->date_first_payment_month != '') && ($request->date_first_payment_day != ''))
		{
			$date_first_payment = strtotime("{$request->date_first_payment_year}-{$request->date_first_payment_month}-{$request->date_first_payment_day}");
			$date_fund_actual	= strtotime(ECash_Display_LegacyHandler::parseDateMDY($request->date_fund_actual_hidden));

			$rate_calc = $application->getRateCalculator();
			$apr = $rate_calc->getAPR($date_fund_actual, $date_first_payment);
			$interest_amount = $rate_calc->calculateCharge($request->fund_amount, $date_fund_actual, $date_first_payment);

			$model->apr = $apr;
			$model->finance_charge = $interest_amount;
			$model->payment_total = $interest_amount + $request->fund_amount;
			//this so if any days have passed since they filled out the application, the new document sent out reflects the changed fund date 
			$model->date_fund_estimated = $date_fund_actual;

			if( (!empty($model->fund_actual) && $model->fund_actual != $request->fund_amount) || $request->fund_amount != $model->fund_qualified)
			{
				$request->new_first_due_date = "yes";
			}
			else
			{
				$request->new_first_due_date = "no";
			}
			if($model->date_first_payment != $date_first_payment) 
			{
				$request->new_first_due_date = "yes";
				$model->date_first_payment = $date_first_payment;
			}
		}
		else
		{
			$model->finance_charge = $request->finance_charge;
			$model->payment_total = $request->payment_total;
		}
		//On Funding these are not set and blank these fields out screwing with complete schedule [GF:6681][richardb]
		if(!empty($request->income_direct_deposit) && $model->income_direct_deposit != $request->income_direct_deposit)
		{
			$model->income_direct_deposit = $request->income_direct_deposit;
			$request->new_first_due_date = "yes";
		}
		if(!empty($request->fund_amount))
		{
			$model->fund_actual = $request->fund_amount;
		}
		
		$model->modifying_agent_id = ECash::getAgent()->getAgentId();
	}
	
	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
		$response->apr = $model->apr;
		$response->date_first_payment = date('m/d/Y',$model->date_first_payment);
		$response->finance_charge = $model->finance_charge;
		$response->payment_total = $model->payment_total;
		$response->income_direct_deposit = $model->income_direct_deposit;
		$response->fund_amount = $model->fund_actual;	
	}
	


}

?>
