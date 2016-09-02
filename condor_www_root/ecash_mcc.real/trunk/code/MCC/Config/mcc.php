<?php
//require_once(LIB_DIR . 'Config.class.php');
/**
  * These are company specific defines
  * for MCC
  */
class MCC_CompanyConfig extends eCash_Config
{
	protected function init()
 	{

		/**
		 * ACH Related
		 */

		// Proper live info.
		$this->configVariables['ACH_TAX_ID'] = '261605805';

		// Probably needs changed for magic number 174 FIXME
		$this->configVariables['ACH_REPORT_RETURNS_URL'] = '/home/teledraft/download/348_returns_'.date('Ymd',strtotime("-1 day")).'.csv';
		$this->configVariables['ACH_REPORT_CORRECTIONS_URL'] = '/home/teledraft/download/348_returns_'.date('Ymd',strtotime("-1 day")).'.csv';
		
		// These are new for confirmation emails and encryption of batch files

		$this->configVariables['ACH_BATCH_NOTIFY_FROM'] = 'batchconfirmation@sellingsource.com';

		// This might be replaceable, but there's no guaranteeing the company abbreviations will be the same as what is required
		$this->configVariables['ACH_BATCH_ACRONYM']     = 'M3X';

		// They want contact information for two separate people, I'm labelling them administrative and technical contacts
		$this->configVariables['ACH_ADMIN_CONTACT_NAME']  = "Brian Ronald";
		$this->configVariables['ACH_ADMIN_CONTACT_EMAIL'] = "brian.ronald@sellingsource.com";
		$this->configVariables['ACH_ADMIN_CONTACT_PHONE'] = "(702) 407-0707 Ext#2381";
	 
		$this->configVariables['ACH_TECH_CONTACT_NAME']  = "Ben Burkhart";
		$this->configVariables['ACH_TECH_CONTACT_EMAIL'] = "ben.burkhart@sellingsource.com";
		$this->configVariables['ACH_TECH_CONTACT_PHONE'] = "(702) 407-0707 Ext#2500";

		// Batch Encryption
		// This is who we use for the public key encryption and the digital signature
		// this must be setup prior to use via
		// GNUPGHOME=/virtualhosts/mcc/ecash3.0/ecash_mcc/code/MCC/Config/gpg_keyring gpg --gen-key
		// Of course having the proper path in GNUPGHOME
		$this->configVariables['ACH_BATCH_USE_PGP']          = TRUE;
		$this->configVariables['ACH_SENDER']                 = "mccbatch@sellingsource.com";

		// This is the private key's passphrase, it's not the safest to put it here,
		// but if you have access to this file, you have access to all the DB info anyways.
		$this->configVariables['ACH_PRIVATE_KEY_PASSPHRASE'] = "DRUCRe8ese";

		// Need to go through and verify these are needed with AdvantageACH
		$this->configVariables['ACH_COMPANY_ID'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ABA'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_TYPE'] = 'checking';

		$this->configVariables['ACH_DEBIT_BANK_ABA'] = '99999';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_TYPE'] = 'checking';

		$this->configVariables['ACH_REPORT_COMPANY_ID'] = '99999';
		$this->configVariables['ACH_REPORT_LOGIN'] = 'achtest';
		$this->configVariables['ACH_REPORT_PASS'] = 'achest';

		/**
		 * IT Settlement Report information, for CSOs
		 */
		$this->configVariables['CSO_ENABLED'] = true;
		
		
		$this->configVariables['IT_SETTLEMENT_SUMMARY_FILE'] = 'mcc_summary';
		$this->configVariables['IT_SETTLEMENT_SUMMARY_EXTENSION'] = 'Pdf';
		
		$this->configVariables['IT_SETTLEMENT_DETAILS_FILE'] = 'mcc_details';
		$this->configVariables['IT_SETTLEMENT_DETAILS_EXTENSION'] = 'Csv';
		
		$this->configVariables['IT_SETTLEMENT_CUSTOMER_FILE'] = 'mcc_customer';
		$this->configVariables['IT_SETTLEMENT_CUSTOMER_EXTENSION'] = 'Csv';
		$this->configVariables['IT_SETTLEMENT_NOTIFICATION_LIST'] = 'william.parker@cubisfinancial.com';
		
		//Local/General Settings
		$this->configVariables['IT_SETTLEMENT_TRANSPORT_TYPE'] = 'SFTP';
		$this->configVariables['IT_SETTLEMENT_URL'] = '/home/achtest/it_settlement';
		$this->configVariables['IT_SETTLEMENT_LOGIN'] = 'achtest';
		$this->configVariables['IT_SETTLEMENT_PASS'] = 'achtest';
		$this->configVariables['IT_SETTLEMENT_SERVER'] = 'ds86.tss';
		$this->configVariables['IT_SETTLEMENT_SERVER_PORT'] = 22;
		
		
		//RC Settings
		$this->configVariables['RC']['IT_SETTLEMENT_TRANSPORT_TYPE'] = 'SFTP';
		$this->configVariables['RC']['IT_SETTLEMENT_URL'] = '/home/cso/it_settlement';
		$this->configVariables['RC']['IT_SETTLEMENT_LOGIN'] = 'cso';
		$this->configVariables['RC']['IT_SETTLEMENT_PASS'] = '-50+35+';
		$this->configVariables['RC']['IT_SETTLEMENT_SERVER'] = 'ps23.ept.tss';
		$this->configVariables['RC']['IT_SETTLEMENT_SERVER_PORT'] = 22;

		
		// No Info for MCC FIXME
		$this->configVariables['LIVE']['ACH_BATCH_LOGIN'] = 'QQAcc348';
		$this->configVariables['LIVE']['ACH_BATCH_PASS'] = 'SSGbb348';
		$this->configVariables['LIVE']['ACH_COMPANY_ID'] = '99999';
		$this->configVariables['LIVE']['ACH_TAX_ID'] = '99999';

		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ABA'] = '99999';
		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ACCOUNT_TYPE'] = 'checking';

		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ABA'] = '99999';
		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ACCOUNT_TYPE'] = 'checking';
		$this->configVariables['LIVE']['ACH_REPORT_COMPANY_ID'] = '99999';
		$this->configVariables['LIVE']['ACH_REPORT_LOGIN'] = 'QQAcc348';
		$this->configVariables['LIVE']['ACH_REPORT_PASS'] = 'SSGbb348';
		
		$this->configVariables['LIVE']['ACH_REPORT_RETURNS_URL'] = '/download/348_returns_'.date('Ymd',strtotime("-1 day")).'.csv';
		$this->configVariables['LIVE']['ACH_REPORT_CORRECTIONS_URL'] = '/download/348_returns_'.date('Ymd',strtotime("-1 day")).'.csv';

		$this->configVariables['DEPOSITS_PROCESSOR_NAME'] = 'AdvantageACH';

		// Used by TeleDraft FIXME
		$this->configVariables['CLIENT_ID'] = 348;
		$this->configVariables['MERCHANT_ID'] = 1174;
		
		// QC Related FIXME
		$this->configVariables['QC_COMPANY'] = 'ImpactCsh';
		$this->configVariables['QC_DEPOSIT_PASS'] = '00000';
		$this->configVariables['QC_DEPOSIT_PORT'] = '990';
		$this->configVariables['QC_DEPOSIT_URL'] = 'ftps://preprod.itms-online.com';
		$this->configVariables['QC_DEPOSIT_USER'] = '00000';
		$this->configVariables['QC_EXECUTION_MODE'] = 'T';
		$this->configVariables['QC_OWNER_CODE'] = 'PRE0000000000';
		$this->configVariables['QC_RETURN_CODE'] = '00000';
		$this->configVariables['QC_RETURN_HOST'] = 'secureftp.solutran.com';
		$this->configVariables['QC_RETURN_PASS'] = '';
		$this->configVariables['QC_RETURN_URL'] = 'secureftp.solutran.com:21';
		$this->configVariables['QC_RETURN_USER'] = '';
		$this->configVariables['QC_STATUS_PASS'] = '';
		$this->configVariables['QC_STATUS_URL'] = 'https://preprod.itms-online.com/ITMSWebService/ITMSWebService.asmx?wsdl';
		$this->configVariables['QC_STATUS_USER'] = '';
		$this->configVariables['QC_TRN_PREFIX'] = 'IC-';

		// Condor 4
		$this->configVariables['RC']['COMPANY_LOGO_LARGE'] = 'http://rc.mycashcenter.com/imgdir/rc/themes/DMP/skins/nms/blackbox/mycashcenter.com/media/image/mcc_lrg.gif';
		$this->configVariables['RC']['COMPANY_LOGO_SMALL'] = 'http://rc.mycashcenter.com/imgdir/rc/themes/DMP/skins/nms/blackbox/mycashcenter.com/media/image/mcc_sml.gif';
		$this->configVariables['LIVE']['COMPANY_LOGO_LARGE'] = 'http://mycashcenter.com/imgdir/live/themes/DMP/skins/nms/blackbox/mycashcenter.com/media/image/mcc_lrg.gif';
		$this->configVariables['LIVE']['COMPANY_LOGO_SMALL'] = 'http://mycashcenter.com/imgdir/live/themes/DMP/skins/nms/blackbox/mycashcenter.com/media/image/mcc_sml.gif';
		
		$this->configVariables['CONDOR_SERVER'] = 'prpc://mccAPI:mccc0nd0r@rc.condor.4.edataserver.com/condor_api.php';
		$this->configVariables['LIVE']['CONDOR_SERVER'] = 'prpc://mccAPI:mccc0nd0r@condor.4.internal.edataserver.com/condor_api.php';

		// Documents Related
		$this->configVariables['COMPANY_ADDR]'] = '1614 Hampton Road';
		$this->configVariables['COMPANY_ADDR_CITY'] = 'Texarkana';
		$this->configVariables['COMPANY_ADDR_STATE'] = 'TX';

		$this->configVariables['COMPANY_ADDR_STREET'] = '1614 Hampton Road'; // Different from ADDR?
		$this->configVariables['COMPANY_ADDR_UNIT'] = '';
		$this->configVariables['COMPANY_ADDR_ZIP'] = '75503';
		$this->configVariables['COMPANY_COUNTY'] = '';
		$this->configVariables['COMPANY_NAME_FORMAL'] = 'MyCashCenter, LLC';
		$this->configVariables['COMPANY_DEPT_NAME'] = 'Customer Service';
		$this->configVariables['COMPANY_EMAIL'] = 'CustomerService@mycashcenter.com';
		$this->configVariables['COMPANY_FAX'] = '1-866-248-9042';

		//They provided no "DBA" - "My Cash Center" is being used in the provided loan docs (With spaces)
		$this->configVariables['COMPANY_NAME'] = 'My Cash Center';
		$this->configVariables['COMPANY_NAME_LEGAL'] = 'MyCashCenter, LLC'; 
		$this->configVariables['COMPANY_NAME_SHORT'] = 'My Cash Center'; 
		$this->configVariables['COMPANY_NAME_STREET'] = '';
		$this->configVariables['COMPANY_PHONE_NUMBER'] = '1-866-248-9041';

		$this->configVariables['RC']['COMPANY_SITE'] = 'rc.mycashcenter.com';
		$this->configVariables['LIVE']['COMPANY_SITE'] = 'www.mycashcenter.com';
		$this->configVariables['COMPANY_DOMAIN'] = 'mycashcenter.com';
		$this->configVariables['COMPANY_SUPPORT_EMAIL'] = 'CustomerService@mycashcenter.com';

		$this->configVariables['COMPANY_SUPPORT_FAX'] = '1-866-248-9042';
		$this->configVariables['COMPANY_SUPPORT_PHONE'] = '1-866-248-9041';
		$this->configVariables['COMPANY_NAME_FORMAL'] = 'MyCashCenter, LLC DBA My Cash Center';


		
		//Now we're doing client services tokens. [#20747]
		// Client Services tokens (Pre Support) tokens (which right now is the same as customer service info)
		$this->configVariables['PRE_SUPPORT_EMAIL'] = 'CustomerService@mycashcenter.com';
		$this->configVariables['PRE_SUPPORT_PHONE'] = '1-866-248-9041';
		$this->configVariables['PRE_SUPPORT_FAX']   = '1-866-248-9042';
		
		$this->configVariables['DOCUMENT_DEFAULT_ESIG_BODY'] = 'Generic Esig Email';
		$this->configVariables['DOCUMENT_DEFAULT_FAX_COVERSHEET'] = 'Fax Cover Sheet';

		$this->configVariables['EMAIL_RECEIVE_DOCUMENT'] = 'Incoming Email Document';
		$this->configVariables['EMAIL_RESPONSE_DOCUMENT'] = 'Generic Message';
		
		//CSO Lender
		$this->configVariables['CSO_LENDER_NAME_LEGAL'] = 'NCP Finance Limited Partnership';

		/**
		 * Link for Reacts
		 */
		$this->configVariables['RC']['REACT_URL'] = "http://rc.mycashcenter.com";
		$this->configVariables['LIVE']['REACT_URL'] = 'http://mycashcenter.com';
		
		/**
		 * Link for New App section in Header
		 */		
		$this->configVariables['RC']['NEW_APP_SITE'] = 'http://rc.mycashcenter.com';
		$this->configVariables['LIVE']['NEW_APP_SITE'] = 'http://mycashcenter.com';

		/**
		 * eSig Link
		 */
		$this->configVariables['RC']['ESIG_URL'] = 'http://rc.mycashcenter.com';
		$this->configVariables['LIVE']['ESIG_URL'] = "http://mycashcenter.com";

		/**
		 * eSig Link
		 */
		$this->configVariables['COLLECTIONS_EMAIL'] = 'Collections@mycashcenter.com';
		$this->configVariables['COLLECTIONS_PHONE'] = '1-866-248-9041';
		$this->configVariables['COLLECTIONS_FAX']   = '1-866-248-9042';
		
		$this->configVariables['SUGGESTED_PAYMENT_INCREMENT'] = '50.00';
 	}
}
