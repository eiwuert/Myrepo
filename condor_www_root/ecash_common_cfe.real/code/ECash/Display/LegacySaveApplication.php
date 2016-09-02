<?php

require_once('qualify.2.php');

class ECash_Display_LegacySaveApplication implements ECash_Display_ILegacySave
{
	/**
	 * @TODO the qualify_2 stuff here might be company specific [JustinF]
	 */
	public static function toModel(ECash_Request $request, DB_Models_WritableModel_1 &$model)
	{
		if (($request->date_first_payment_year != '') && ($request->date_first_payment_month != '') && ($request->date_first_payment_day != ''))
		{
			$date_first_payment = strtotime("{$request->date_first_payment_year}-{$request->date_first_payment_month}-{$request->date_first_payment_day}");
			$date_fund_actual	= Date_Format_MDY_To_YMD($request->date_fund_actual_hidden);
			
			$ecash_api = eCash_API_2::Get_eCash_API(ECash::getCompany()->name_short, ECash::getFactory()->getDB(), $model->application_id);
			$apr = $ecash_api->getAPR($request->loan_type, ECash::getCompany()->name_short, strtotime($date_fund_actual), $date_first_payment);
			$rules = new ECash_BusinessRules(ECash::getFactory()->getDB());
			$rule_set     = $rules->Get_Rule_Set_Tree($model->rule_set_id);
			$interest_amount = Interest_Calculator::calculateDailyInterest($rule_set, $request->fund_amount, $date_fund_actual, date('Y-m-d',$date_first_payment));

			$model->apr = $apr;
			$model->finance_charge = $interest_amount;
			$model->payment_total = $interest_amount + $request->fund_amount;
			//this so if any days have passed since they filled out the application, the new document sent out reflects the changed fund date 
			$model->date_fund_estimated = strtotime($date_fund_actual);

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
	
	public static function toResponse(stdClass &$response, DB_Models_WritableModel_1 $model)
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
