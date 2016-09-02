<?php
//require_once(LIB_DIR . 'Config.class.php');
/**
  * These are company specific defines
  * for generic
  */
class GENERIC_CompanyConfig extends eCash_Config
{
	protected function init()
 	{
		//define('CUSTOMER_LIB', BASE_DIR . "customer_lib/generic/");

		/**
		 * ACH Related
		 */
		$this->configVariables['ACH_BATCH_LOGIN'] = 'username';
		$this->configVariables['ACH_BATCH_PASS'] = 'passord';
		$this->configVariables['ACH_REPORT_LOGIN'] = 'username';
		$this->configVariables['ACH_REPORT_PASS'] = 'passsword';
		$this->configVariables['ACH_REPORT_RETURNS_URL'] = '/home/achtest/returns/';
		$this->configVariables['ACH_REPORT_CORRECTIONS_URL'] = '/home/achtest/corrections/';

		$this->configVariables['LIVE']['ACH_BATCH_LOGIN'] = 'username';
		$this->configVariables['LIVE']['ACH_BATCH_PASS'] = 'password';
		$this->configVariables['LIVE']['ACH_REPORT_LOGIN'] = 'username';
		$this->configVariables['LIVE']['ACH_REPORT_PASS'] = 'password';
		$this->configVariables['LIVE']['ACH_REPORT_RETURNS_URL'] = '/home/aalmach/returns/';
		$this->configVariables['LIVE']['ACH_REPORT_CORRECTIONS_URL'] = '/home/aalmach/corrections/';

		$this->configVariables['ACH_COMPANY_ID'] = '99999';
		$this->configVariables['ACH_TAX_ID'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ABA'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_TYPE'] = 'checking';
		$this->configVariables['ACH_DEBIT_BANK_ABA'] = '99999';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_TYPE'] = 'checking';
		$this->configVariables['ACH_REPORT_COMPANY_ID'] = '99999';
		
		$this->configVariables['LIVE']['ACH_COMPANY_ID'] = '99999';
		$this->configVariables['LIVE']['ACH_TAX_ID'] = '99999';
		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ABA'] = '99999';
		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['LIVE']['ACH_CREDIT_BANK_ACCOUNT_TYPE'] = 'checking';
		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ABA'] = '99999';
		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ACCOUNT_NUMBER'] = '99999';
		$this->configVariables['LIVE']['ACH_DEBIT_BANK_ACCOUNT_TYPE'] = 'checking';
		$this->configVariables['LIVE']['ACH_REPORT_COMPANY_ID'] = '99999';

		$this->configVariables['DEPOSITS_PROCESSOR_NAME'] = 'Regal';

		// Used by TeleDraft
		$this->configVariables['CLIENT_ID'] = 174;
		
		// QC Related
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
		$this->configVariables['CONDOR_SERVER'] = 'prpc://username:password@rc.condor.4.edataserver.com/condor_api.php';
		$this->configVariables['LIVE']['CONDOR_SERVER'] = 'prpc://username:password@condor.4.internal.edataserver.com/condor_api.php';

		// Documents Related
		$this->configVariables['COMPANY_ADDR]'] = '';
		$this->configVariables['COMPANY_ADDR_CITY'] = '';
		$this->configVariables['COMPANY_ADDR_STATE'] = '';
		$this->configVariables['COMPANY_ADDR_STREET'] = '';
		$this->configVariables['COMPANY_ADDR_UNIT'] = '';
		$this->configVariables['COMPANY_ADDR_ZIP'] = '';
		$this->configVariables['COMPANY_COUNTY'] = '';
		$this->configVariables['COMPANY_NAME_FORMAL'] = 'Some Company Name, Ltd. DBA someloancompany.com';
		$this->configVariables['COMPANY_DEPT_NAME'] = 'Customer Service';
		$this->configVariables['COMPANY_EMAIL'] = 'CustomerService@someloancompany.com';
		$this->configVariables['COMPANY_FAX'] = '1-877-225-0623';

		$this->configVariables['COMPANY_LOGO_LARGE'] = 'http://imagedataserver.com/SHARED/live/themes/IPS/skins/nms/ic/someloancompany.com/media/image/generic_small.gif';
		$this->configVariables['COMPANY_LOGO_SMALL'] = 'http://imagedataserver.com/SHARED/live/themes/IPS/skins/nms/ic/someloancompany.com/media/image/generic_small.gif';
		$this->configVariables['COMPANY_NAME'] = 'someloancompany.com';
		$this->configVariables['COMPANY_NAME_LEGAL'] = 'someloancompany.com';
		$this->configVariables['COMPANY_NAME_SHORT'] = 'someloancompany.com';
		$this->configVariables['COMPANY_NAME_STREET'] = '';
		$this->configVariables['COMPANY_PHONE_NUMBER'] = '1-800-000-0000';
		$this->configVariables['RC']['COMPANY_SITE'] = 'rc.someloancompany.com';
		$this->configVariables['LIVE']['COMPANY_SITE'] = 'www.someloancompany.com';
		$this->configVariables['COMPANY_DOMAIN'] = 'someloancompany.com';
		$this->configVariables['COMPANY_SUPPORT_EMAIL'] = 'CustomerService@someloancompany.com';
		$this->configVariables['COMPANY_SUPPORT_FAX'] = '1-877-000-0000';
		$this->configVariables['COMPANY_SUPPORT_PHONE'] = '1-800-000-0000';
		$this->configVariables['COMPANY_NAME_FORMAL'] = 'Some Loan Company, Ltd. DBA someloancompany.com';
		$this->configVariables['DOCUMENT_DEFAULT_ESIG_BODY'] = 'Generic Esig Email';
		$this->configVariables['DOCUMENT_DEFAULT_FAX_COVERSHEET'] = 'Fax Cover Sheet';

		$this->configVariables['EMAIL_RECEIVE_DOCUMENT'] = 'Incoming Email Document';
		$this->configVariables['EMAIL_RESPONSE_DOCUMENT'] = 'Generic Message';

		/**
		 * Link for Reacts
		 */
		$this->configVariables['RC']['REACT_URL'] = "http://rc.someloancompany.com";
		$this->configVariables['LIVE']['REACT_URL'] = 'http://someloancompany.com';
		
		/**
		 * Link for New App section in Header
		 */		
		$this->configVariables['RC']['NEW_APP_SITE'] = 'http://rc.someloancompany.com';
		$this->configVariables['LIVE']['NEW_APP_SITE'] = 'http://someloancompany.com';

		/**
		 * eSig Link
		 */
		$this->configVariables['RC']['ESIG_URL'] = 'http://rc.someloancompany.com';
		$this->configVariables['LIVE']['ESIG_URL'] = "http://someloancompany.com";
	
		$this->configVariables['DATAX_LICENSE_KEY'] = 'some_key_value';
		$this->configVariables['DATAX_PASSWORD'] = 'password';
 	}
}
