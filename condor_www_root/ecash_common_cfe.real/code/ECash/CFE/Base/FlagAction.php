<?php
	abstract class ECash_CFE_Base_FlagAction extends ECash_CFE_Base_BaseAction
	{
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('name', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
			);
		}
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "name":
					$flag_type_list = ECash::getFactory()->getModel('FlagTypeList');
					$flag_type_list->loadBy(array());
					foreach($flag_type_list as $model) 
					{
						$retval[] = array($model->name_short, $model->name_short, 0);
					}
					break;
			}
			return $retval;			
		}
	}
?>
