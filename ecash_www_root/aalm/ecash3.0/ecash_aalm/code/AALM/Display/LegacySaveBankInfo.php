<?php

class AALM_Display_LegacySaveBankInfo extends ECash_Display_LegacySaveBankInfo
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model)
	{
		parent::toModel($request, $model);

		//$xtra_fields = ECash_Config::getInstance()->EXTRA_PERSONAL_FIELDS;
		$model->banking_start_date = strtotime($request->EditAppInfoCustBankingStartDate);
	}
	
}

?>
