<?php

class AALM_Display_LegacySaveWizard implements ECash_Display_ILegacySave
{
	/**
	 * makes the data from the input conform to the model
	 *
	 * @param stdClass $request
	 * @param DB_Models_IWritableModel_1 $model
	 */
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		$model->paydate_model = '';
		$model->day_of_week = '';
	//	$model->last_paydate = 'NULL';
		$model->day_of_month_1 = '';
		$model->day_of_month_2 = '';
		$model->week_1 = '';
		$model->week_2 = '';
		$model->income_frequency = $request->paydate['frequency'];
						
		switch(strtolower($model->income_frequency))
		{
			case "weekly":
			if(empty($request->paydate['weekly_day'])) throw new Exception();
			$model->paydate_model = "dw";
			$model->day_of_week = strtolower($request->paydate['weekly_day']);
			break;

			case "bi_weekly":
			if( empty($request->paydate['biweekly_day']) ||
			empty($request->paydate['biweekly_date']) ||
			strtolower(date("D", strtotime($request->paydate['biweekly_date']))) != strtolower($request->paydate['biweekly_day']))
			{
				throw new Exception('No biweekly day, date, or they were not equal.');
			}
			$model->paydate_model = "dwpd";
			$model->day_of_week = $request->paydate['biweekly_day'];
			$model->last_paydate = strtotime($request->paydate['biweekly_date']);
			break;

			case "twice_monthly":
			if($request->paydate['twicemonthly_type'] == 'date')
			{
				if( (empty($request->paydate['twicemonthly_date1']) ||
				empty($request->paydate['twicemonthly_date2'])) ||
				($request->paydate['twicemonthly_date1'] == $request->paydate['twicemonthly_date2']))
				{
					throw new Exception();
				}
				$model->paydate_model =  "dmdm";
				//just incase they entered the dates in reverse order
				if($request->paydate['twicemonthly_date1'] > $request->paydate['twicemonthly_date2'])
				{
					$model->day_of_month_1 = $request->paydate['twicemonthly_date2'];
					$model->day_of_month_2 = $request->paydate['twicemonthly_date1'];
				}
				else
				{
					$model->day_of_month_1 = $request->paydate['twicemonthly_date1'];
					$model->day_of_month_2 = $request->paydate['twicemonthly_date2'];
				}
			}
			else
			{
				if(empty($request->paydate['twicemonthly_day']) || empty($request->paydate['twicemonthly_week'])) throw new Exception();
				$model->paydate_model =  "wwdw";
				$model->day_of_week = $request->paydate['twicemonthly_day'];
				$weeks = explode("-", $request->paydate['twicemonthly_week']);
				$model->week_1 = $weeks[0];
				$model->week_2 = $weeks[1];
			}
			break;

			case "monthly":
			if($request->paydate['monthly_type'] == 'date')
			{
				if(empty($request->paydate['monthly_date'])) throw new Exception();
				$model->paydate_model = "dm";
				$model->day_of_month_1 = $request->paydate['monthly_date'];
			}
			elseif($request->paydate['monthly_type'] == 'day')
			{
				if(empty($request->paydate['monthly_day']) || empty($request->paydate['monthly_week'])) throw new Exception();
				$model->paydate_model = "wdw";
				$model->day_of_week = $request->paydate['monthly_day'];
				$model->week_1 = $request->paydate['monthly_week'];
			}
			else //$request->paydate['monthly_type'] == 'after'
			{
				if(empty($request->paydate['monthly_after_day']) || empty($request->paydate['monthly_after_date'])) throw new Exception();
				$model->paydate_model = "dwdm";
				$model->day_of_week = $request->paydate['monthly_after_day'];
				$model->day_of_month_1 = $request->paydate['monthly_after_date'];
			}
			break;
			default:
			throw new Exception();
		}
	}
	
	/**
	 * doesn't actually do anything since the wizard doesn't give a response
	 *
	 * @param stdClass $response
	 * @param DB_Models_IWritableModel_1 $model
	 */
	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
	}
}

?>
