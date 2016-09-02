<?php

class ECash_Display_LegacySavePersonal implements ECash_Display_ILegacySave
{
	public static function toModel(ECash_Request $request, DB_Models_WritableModel_1 &$model)
	{
		$model->name_first = $request->name_first;
		$model->name_last = $request->name_last;
		$model->street = $request->street;
		$model->unit = $request->unit;
		$model->city = $request->city;
		$model->state = $request->state;
		$model->county = $request->county;
		$model->legal_id_number = $request->legal_id_number;
		$model->legal_id_state = $request->legal_id_state;

		//transposed vars
		//this first one could probably be done with a helper method
		$model->dob = strtotime($request->dob_year .'-'. $request->dob_month .'-'. $request->dob_day);
		$model->zip_code = $request->zip;
		$model->email = $request->customer_email;
		$model->residence_start_date = strtotime($request->residence_start_date);
		
		//automagic stuff
		$model->modifying_agent_id = ECash::getAgent()->getAgentId();
	}

	public static function toResponse(stdClass &$response, DB_Models_WritableModel_1 $model)
	{
		$response->name_first = $model->name_first;
		$response->name_last = $model->name_last;
		$response->street = $model->street;
		$response->unit = $model->unit;
		$response->city = $model->city;
		$response->state = $model->state; 
		$response->county = $model->county;
		$response->legal_id_number = $model->legal_id_number; 
		$response->legal_id_state = $model->legal_id_state; 
		$response->residence_start_date = date('m/d/Y',$model->residence_start_date); 
		
		//transposed vars
		ECash_Display_LegacyHandler::setMDYFromTS('dob', $model->dob, $response);
		$response->zip = $model->zip_code;
		$response->customer_email = $model->email;
	}
}

?>
