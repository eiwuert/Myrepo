<?php

class ECash_Display_LegacySaveGeneralInfo implements ECash_Display_ILegacySave
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		$model->name_first = $request->name_first;
		$model->name_last = $request->name_last;
		$model->phone_home = $request->phone_home;
		$model->phone_cell = $request->phone_cell;
		$model->phone_work = $request->phone_work;
		$model->phone_work_ext = $request->phone_work_ext;
		$model->email = $request->customer_email;
		$model->income_monthly = $request->income_monthly;
		$model->modifying_agent_id = ECash::getAgent()->getAgentId();
	}
	
	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
		$response->name_first = $model->name_first;
		$response->name_last = $model->name_last;
		$response->phone_home = $model->phone_home;
		$response->phone_cell = $model->phone_cell;
		$response->phone_work = $model->phone_work;
		$response->phone_work_ext = $model->phone_work_ext;
		$response->customer_email = $model->email;
		$response->income_monthly = $model->income_monthly;
	}
}

?>
