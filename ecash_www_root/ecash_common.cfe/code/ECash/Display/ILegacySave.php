<?php

interface ECash_Display_ILegacySave
{
	public static function toModel(ECash_Request $request, DB_Models_IWritableModel_1 &$model);

	public static function toResponse(stdClass &$response, DB_Models_IWritableModel_1 $model);	
}

?>