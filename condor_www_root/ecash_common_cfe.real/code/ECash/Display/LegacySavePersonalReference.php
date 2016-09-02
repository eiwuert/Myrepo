<?php

class ECash_Display_LegacySavePersonalReference implements ECash_Display_ILegacySave
{
	public static function toModel(ECash_Request $request, DB_Models_WritableModel_1 &$model, $ref_num = NULL)
	{		
		if(!empty($request->{'personal_ref_id_' . $ref_num}))
		{
			//existing model, force update
			$model->setDataSynched();
			$model->personal_reference_id = $request->{'personal_ref_id_' . $ref_num};
		}
		else
		{
			//new model, insert
			$model->company_id = ECash::getApplication()->company_id;
			$model->application_id = $request->application_id;
			$model->reference_verified = 'verified';
			$model->contact_pref = 'ok to contact';
		}
		
		$model->name_full = $request->{'ref_name_' . $ref_num};
		$model->phone_home = $request->{'ref_phone_' . $ref_num};
		$model->relationship = $request->{'ref_relationship_' . $ref_num};
		$model->agent_id = ECash::getAgent()->getAgentId();
	}

	public static function toResponse(stdClass &$response, DB_Models_WritableModel_1 $model, $ref_num = NULL)
	{		
		$reference = new stdClass();
		$reference->personal_reference_id = $model->personal_reference_id;
		$reference->relationship = $model->relationship;
		$reference->name_full = $reference->full_name = $model->name_full;
		$reference->phone_home = $reference->phone = $model->phone_home;

		$response->references[] = $reference;
	}	
}

?>
