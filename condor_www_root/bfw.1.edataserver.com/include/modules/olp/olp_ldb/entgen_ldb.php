<?php

	require_once('olp_ldb.php');

	/**
	 * A generic OLP/eCash class implementation for generic eCash3 customers derived from impact_ldb.php
	 * GFORGE_3981
	 * @author Tym Feindel
	 * @version 0.8
	 * 
	 */
	class Entgen_LDB extends OLP_LDB
	{
		public function __construct(&$mysql, $property_short = null, $data = array())
		{
			$this->ent_prop_list = Enterprise_Data::getCompanyData(Enterprise_Data::COMPANY_GENERIC);
			parent::__construct($mysql, $property_short, $data);
		}
				
		/**
		 * Override the olp_ldb function of the same name-- ecash3 is set up for this
		 * 
		 */
		protected function Get_Loan_Type()
		{
			if(strcasecmp(strtolower($this->property_short), 'generic') === 0)
			{
				$this->loan_type = 'payday_loan';
			}
			else
			{
				$this->loan_type = 'payday_loan';
			}
		}

		// Killed on CB's advice [TF] //resucitated as per Brian F!
		protected function Get_Mail_Template()
		{
			return (isset($this->data['ecashapp'])) ? 'ImpactAppCashReact' : 'OLP_MLS_PAPERLESS_FUNDER_REVIEW';
		}
	}

?>