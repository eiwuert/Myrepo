<?php
	
	class ECash_CFE_Action_FlagUnset extends ECash_CFE_Base_FlagAction
	{
		public function getType()
		{
			return "FlagUnset";
		}
		
		public function execute(ECash_CFE_IContext $c)
		{
			$params = $this->evalParameters($c);
			
			// set the flag
			// @todo Cache application_flags on the context?
			$af = new Application_Flags($c->getServer(), $c->getAttribute('application_id'));
			$af->Clear_Flag($params['name']);
		}
	}
	
?>
