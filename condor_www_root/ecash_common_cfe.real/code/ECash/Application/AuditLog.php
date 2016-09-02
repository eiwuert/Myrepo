<?php

	class ECash_Application_AuditLog extends ECash_Application_Component
	{
		public function getAll()
		{
			return ECash::getFactory()->getData('Application')->getAuditLog($this->application_id);

		}
	}

?>