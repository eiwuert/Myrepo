<?php
	
	class ECash_CFE_Action_FlagSet extends ECash_CFE_Base_FlagAction
	{
		public function getType()
		{
			return "FlagSet";
		}
		
		public function execute(ECash_CFE_IContext $c)
		{
			$params = $this->evalParameters($c);
			
			// set the flag
			$af = new Application_Flags(
				ECash::getServer(),
				$c->getAttribute('application_id')
			);
			$af->Set_Flag($params['name']);
		}
	}
	
?>