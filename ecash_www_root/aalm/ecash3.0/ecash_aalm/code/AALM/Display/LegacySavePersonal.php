<?php

class AALM_Display_LegacySavePersonal extends ECash_Display_LegacySavePersonal
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		parent::toModel($request, $model);
		
		//if(ECash_Config::getInstance()->USE_CUSTOMER_CENTRIC === FALSE)
//		$model->ssn = $request->ssn;
		
		//$xtra_fields = ECash_Config::getInstance()->EXTRA_PERSONAL_FIELDS;
		$model->residence_start_date = strtotime($request->residence_start_date);
		$model->dob                  = strtotime($request->EditAppPersonalInfoCustDobyear . '-' . $request->EditAppPersonalInfoCustDobmonth . '-' . $request->EditAppPersonalInfoCustDobday);
		$model->name_middle = $request->name_middle;
		$model->name_suffix = $request->name_suffix;
	}

	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
		parent::toResponse($response, $model);

		$response->ssn = $model->ssn;		
		$response->name_middle = $model->name_middle;
		$response->name_suffix = $model->name_suffix;
		
	}
}

?>
