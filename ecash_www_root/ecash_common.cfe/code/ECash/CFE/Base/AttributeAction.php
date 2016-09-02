<?php
	abstract class ECash_CFE_Base_AttributeAction extends ECash_CFE_Base_BaseAction
	{
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('name', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('value', ECash_CFE_API_VariableDef::TYPE_NUMBER, ECash::getFactory()->getDB()),
			);
		}
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "name":
					$cfe = new ECash_CFE_API();
					foreach($cfe->getAvailableVariables() as $model) 
					{
						$retval[] = array($model->name_short, $model->name_short, 0);
					}
					break;
			}
			return $retval;
		}
	}
?>
