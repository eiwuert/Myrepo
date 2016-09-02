<?php
	
	class ECash_CFE_Action_Break extends ECash_CFE_Base_BaseAction
	{
		public function getType()
		{
			return "Break";
		}
		
		public function getParameters()
		{
			return array();
		}
		
		public function execute(ECash_CFE_IContext $c)
		{
			throw new ECash_CFE_Break();
		}
	}
	
?>