<?php
//require_once(LIB_DIR . 'Config.class.php');

class IFS_CompanyConfig extends eCash_Config   
{
	protected function init()
 	{
	//	define('CUSTOMER_LIB', BASE_DIR . "customer_lib/ic/");

		// ACH Related
		$this->configVariables['ACH_BATCH_LOGIN'] = 'achtest';
		$this->configVariables['ACH_BATCH_PASS'] = 'achtest';
		$this->configVariables['LIVE']['ACH_BATCH_LOGIN'] = 'sellsource';
		$this->configVariables['LIVE']['ACH_BATCH_PASS'] = '8uf8nu&e';
		$this->configVariables['ACH_COMPANY_ID'] = '0000000000';
		$this->configVariables['ACH_TAX_ID'] = '000000000';

		$this->configVariables['ACH_CREDIT_BANK_ABA'] = '000000000';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_NUMBER'] = '000000000000';
		$this->configVariables['ACH_CREDIT_BANK_ACCOUNT_TYPE'] = 'checking';
		
		$this->configVariables['ACH_DEBIT_BANK_ABA'] = '000000000';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_NUMBER'] = '000000000000';
		$this->configVariables['ACH_DEBIT_BANK_ACCOUNT_TYPE'] = 'checking';
		
		$this->configVariables['ACH_REPORT_COMPANY_ID'] = '000000000';
		$this->configVariables['ACH_REPORT_LOGIN'] = 'sellsource';
		$this->configVariables['ACH_REPORT_PASS'] = '8uf8nu&e';

                $this->configVariables['ACH_REPORT_RETURNS_URL'] = '/returns/IFS'.date('Ymd').'PM.csv';
                $this->configVariables['LIVE']['ACH_REPORT_RETURNS_URL'] = '/home/sellsource/returns/IFS'.date('Ymd').'PM.csv';

                $this->configVariables['ACH_REPORT_CORRECTIONS_URL'] = '/corrections/IFS'.date('Ymd').'PM.csv';
                $this->configVariables['LIVE']['ACH_REPORT_CORRECTIONS_URL'] = '/home/sellsource/corrections/IFS'.date('Ymd').'PM.csv';
		
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

		// Condor 3
		// Used in one place: client/code/display_overview.class.php:528: 
		$this->configVariables['CONDOR_DOC_URL'] =  'http://condor.3.edataserver.com/?page=admin&show=detail&type=reprint_doc&property_short=ic';
		$this->configVariables['LIVE']['CONDOR_DOC_URL'] =  'http://condor.3.edataserver.com/?page=admin&show=detail&type=reprint_doc&property_short=ic';

		// Condor 4
		$this->configVariables['CONDOR_SERVER'] = 'prpc://impact2:impactc0nd0r@rc.condor.4.edataserver.com/condor_api.php';
		$this->configVariables['LIVE']['CONDOR_SERVER'] = 'prpc://impact2:impactc0nd0r@condor.4.internal.edataserver.com/condor_api.php';

		// Documents Related
		$this->configVariables['COMPANY_ADDR]'] = '1759 North 400 East, Suite 201 Logan, Utah 84341';
		$this->configVariables['COMPANY_ADDR_CITY'] = 'Logan';
		$this->configVariables['COMPANY_ADDR_STATE'] = 'UT';
		$this->configVariables['COMPANY_ADDR_STREET'] = '1759 North 400 East';
		$this->configVariables['COMPANY_ADDR_UNIT'] = 'Suite 201';
		$this->configVariables['COMPANY_ADDR_ZIP'] = '84341';
		$this->configVariables['COMPANY_COUNTY'] = '';

		$this->configVariables['COMPANY_DEPT_NAME'] = 'Customer Service';
		$this->configVariables['COMPANY_EMAIL'] = 'customerservice@impactsolutiononline.com';
		$this->configVariables['COMPANY_FAX'] = '1-800-321-3887';

		$this->configVariables['COMPANY_LOGO_LARGE'] = 'http://impactsolutiononline.com/imgdir/live/themes/IPS/skins/nms/ic/impactsolutiononline.com/media/image/ifs_small.jpg';
		$this->configVariables['COMPANY_LOGO_SMALL'] = 'http://impactsolutiononline.com/imgdir/live/themes/IPS/skins/nms/ic/impactsolutiononline.com/media/image/ifs_small.jpg';
		$this->configVariables['COMPANY_NAME'] = 'Impact Solution Online LLC';
		$this->configVariables['COMPANY_NAME_LEGAL'] = 'Impact Solution Online LLC';
		$this->configVariables['COMPANY_NAME_SHORT'] = 'Impact Solution Online LLC';
		$this->configVariables['COMPANY_NAME_STREET'] = '1759 North 400 East';
		$this->configVariables['COMPANY_PHONE_NUMBER'] = '1-800-321-3886';
		$this->configVariables['COMPANY_SITE'] = 'http://impactsolutiononline.com';
		$this->configVariables['COMPANY_DOMAIN'] = 'impactsolutiononline.com';
		$this->configVariables['COMPANY_SUPPORT_EMAIL'] = 'customerservice@impactsolutiononline.com';
		$this->configVariables['COMPANY_SUPPORT_FAX'] = '1-800-321-3887';
		$this->configVariables['COMPANY_SUPPORT_PHONE'] = '1-800-321-3886';
		
		$this->configVariables['DOCUMENT_DEFAULT_ESIG_BODY'] = 'Generic Esig Email';
		$this->configVariables['DOCUMENT_DEFAULT_FAX_COVERSHEET'] = 'Fax Cover Sheet';
		$this->configVariables['DOCUMENT_TEST_EMAIL'] = 'ecash3drive@gmail.com';
		
		$this->configVariables['EMAIL_RECEIVE_DOCUMENT'] = 'Incoming Email Document';
		$this->configVariables['EMAIL_RESPONSE_DOCUMENT'] = 'Generic Message';
		
		/**
		 * Link for New App section in Header
		 */
		$this->configVariables['NEW_APP_SITE'] = 'http://www.impactsolutiononline.com';
		$this->configVariables['RC']['NEW_APP_SITE'] = 'http://rc.impactsolutiononline.com';
		$this->configVariables['LIVE']['NEW_APP_SITE'] = 'http://www.impactsolutiononline.com';

	
		/**
		 * eSig Link
		 */
		$this->configVariables['ESIG_URL'] = 'http://rc.impactsolutiononline.com';
		$this->configVariables['LIVE']['ESIG_URL'] = "http://impactsolutiononline.com";
				/**
		  * DataX License and Password
		  */
		 $this->configVariables['DATAX_LICENSE_KEY'] = '044a3b4e782f73d45beff1da0718d3dd';
		 $this->configVariables['DATAX_PASSWORD'] = 'password';		
 	}
}
