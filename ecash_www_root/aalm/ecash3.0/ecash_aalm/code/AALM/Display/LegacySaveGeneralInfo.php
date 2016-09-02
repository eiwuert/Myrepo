<?php

class AALM_Display_LegacySaveGeneralInfo extends ECash_Display_LegacySaveGeneralInfo
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		parent::toModel($request, $model);

		//$xtra_fields = ECash_Config::getInstance()->EXTRA_PERSONAL_FIELDS;
		$model->name_middle = $request->name_middle;
		$model->name_suffix = $request->name_suffix;
	}
	
	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
		parent::toResponse($response, $model);

		$response->name_middle = $model->name_middle;
		$response->name_suffix = $model->name_suffix;				
	}
}

?>
