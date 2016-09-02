<?php

	class ECash_Application_Flags extends ECash_Application_Component
	{
		public function set($flag)
		{
			ECash::getFactory()->getData('Application')->setFlag($flag,ECash::getAgent()->getAgentId(),$this->application_id, $this->company_id);
		}

		public function clear($flag)
		{
			ECash::getFactory()->getData('Application')->clearFlag($flag,ECash::getAgent()->getAgentId(),$this->application_id, $this->company_id);
		}

		public function get($flag)
		{
			return ECash::getFactory()->getData('Application')->getFlag($flag, $this->application_id);
		}

		public function getAll()
		{
			$results = array();			
			$flags = ECash::getFactory()->getData('Application')->getFlags($this->application_id);

			foreach ($flags as $row)
			{
				$results[$row->name_short] = $row;
			}

			return $results;
		}
	}

?>