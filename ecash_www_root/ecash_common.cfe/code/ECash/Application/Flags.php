<?php

	class ECash_Application_Flags extends ECash_Application_Component
	{
		public function set($flag)
		{
			ECash::getFactory()->getData('Application')->setFlag($flag,ECash::getAgent()->getAgentId(),$this->application->getId(), $this->application->getCompanyId());
		}

		public function clear($flag)
		{
			ECash::getFactory()->getData('Application')->clearFlag($flag,ECash::getAgent()->getAgentId(),$this->application->getId(), $this->application->getCompanyId());
		}

		public function get($flag)
		{
			return ECash::getFactory()->getData('Application')->getFlag($flag, $this->application->getId());
		}

		public function getAll()
		{
			$results = array();			
			$flags = ECash::getFactory()->getData('Application')->getFlags($this->application->getId());

			foreach ($flags as $row)
			{
				$results[$row['name_short']] = $row;
			}

			return $results;
		}
	}

?>