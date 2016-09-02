<?php

class ECash_Display_LegacySaveBankInfo implements ECash_Display_ILegacySave
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		$model->income_direct_deposit = $request->income_direct_deposit;
		$model->bank_aba = $request->bank_aba;
		$model->bank_name = $request->bank_name;
		$model->bank_account = $request->bank_account;
		$model->bank_account_type = $request->bank_account_type;
		$model->banking_start_date = strtotime($request->banking_start_date);
		$model->modifying_agent_id = ECash::getAgent()->getAgentId();
	}
	
	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model)
	{
		$response->income_direct_deposit = $model->income_direct_deposit;
		$response->bank_aba = $model->bank_aba;
		$response->bank_name = $model->bank_name;
		$response->bank_account = $model->bank_account;
		$response->bank_account_type = $model->bank_account_type;
		$response->banking_start_date = Date('m/d/Y',$model->banking_start_date);
		
	}
}

?>
