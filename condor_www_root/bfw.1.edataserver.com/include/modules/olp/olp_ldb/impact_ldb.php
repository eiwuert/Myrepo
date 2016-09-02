<?php

	require_once('olp_ldb.php');
	require_once('cfe_ldb.php');
	class Impact_LDB extends CFE_LDB
	{
		public function __construct(&$mysql, $property_short = null, $data = array())
		{
			$this->ent_prop_list = Enterprise_Data::getCompanyData(Enterprise_Data::COMPANY_IMPACT);
			parent::__construct($mysql, $property_short, $data);
		}
		
		/**
		 * Mail Process Done
		 * 
		 * Sends out the email at the end of the process
		 */
		/*public function Mail_Confirmation()
		{
			parent::Mail_Confirmation();

			if(isset($this->data['ecashapp']))
			{
				$data = $this->Get_Mail_Data();
				$data['email_primary'] = 'support@impactcashusa.com';
				$this->Send_Mail($this->Get_Mail_Template(), $data);
			}
		}*/
		
		protected function Get_Mail_Template()
		{
			return (isset($this->data['ecashapp'])) ? 'ImpactAppCashReact' : 'OLP_PAPERLESS_FUNDER_REVIEW_IMPACT';
		}
	}

?>
