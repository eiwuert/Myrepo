<?php
	class ECash_Models_AchProviderBankAba extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'ach_provider_bank_aba_id', 'ach_provider_id', 'bank_aba', 'active_status', 'agent_id'
			);
			return $columns;
		}

		public function getPrimaryKey()
		{
			return array('ach_provider_bank_aba_id');
		}

		public function getAutoIncrement()
		{
			return 'ach_provider_bank_aba_id';
		}

		public function getTableName()
		{
			return 'ach_provider_bank_aba';
		}
	}
?>