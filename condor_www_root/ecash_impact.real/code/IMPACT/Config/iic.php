<?php
class IIC_CompanyConfig extends eCash_Config   
{
	protected function init()
 	{
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
		$this->configVariables['ACH_REPORT_LOGIN'] = 'achtest';
		$this->configVariables['ACH_REPORT_PASS'] = 'achtest';
		
		$this->configVariables['LIVE']['ACH_REPORT_LOGIN'] = 'sellsource';
		$this->configVariables['LIVE']['ACH_REPORT_PASS'] = '8uf8nu&e';

		$this->configVariables['ACH_REPORT_RETURNS_URL'] = '/home/achtestrc/returns/ICC'.date('md').'.csv';
		$this->configVariables['LIVE']['ACH_REPORT_RETURNS_URL_PREFIX'] = '/home/sellsource/returns/ICC';
		$this->configVariables['LIVE']['ACH_REPORT_RETURNS_URL_SUFFIX'] = 'PM.csv';
		
        $this->configVariables['ACH_REPORT_CORRECTIONS_URL'] = '/corrections/ICC'.date('Ymd').'PM.csv';
		$this->configVariables['LIVE']['ACH_REPORT_CORRECTIONS_URL_PREFIX'] = '/home/sellsource/corrections/ICC';
		$this->configVariables['LIVE']['ACH_REPORT_CORRECTIONS_URL_SUFFIX'] = 'PM.csv';

		// QC Related [NEED_EDITED]
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
		
		// Condor 4 [NEED_EDITED]
		$this->configVariables['CONDOR_SERVER'] = 'prpc://iicAPI:impactc0nd0r@rc.condor.4.edataserver.com/condor_api.php/condor_api.php';
		$this->configVariables['LIVE']['CONDOR_SERVER'] = 'prpc://iicAPI:impactc0nd0r@condor.4.edataserver.com/condor_api.php/condor_api.php';

		// Documents Related
		$this->configVariables['COMPANY_ADDR]'] = '1759 North 400 East, Suite 102, North Logan, UT 84341';
		$this->configVariables['COMPANY_ADDR_CITY'] = 'North Logan';
		$this->configVariables['COMPANY_ADDR_STATE'] = 'UT';
		$this->configVariables['COMPANY_ADDR_STREET'] = '1759 North 400 East';
		$this->configVariables['COMPANY_ADDR_UNIT'] = 'Suite 102';
		$this->configVariables['COMPANY_ADDR_ZIP'] = '84341';
		$this->configVariables['COMPANY_COUNTY'] = '';

		$this->configVariables['COMPANY_DEPT_NAME'] = 'Customer Service';
		$this->configVariables['COMPANY_EMAIL'] = 'customerservice@impactintacash.com';
		$this->configVariables['COMPANY_FAX'] = '1-866-974-6822';

		$this->configVariables['LIVE']['COMPANY_LOGO_LARGE'] = 'http://imagedataserver.com/SHARED/live/themes/IPS/skins/nms/ic/impactintacash.com/media/image/impactintacash_large.jpg';
		$this->configVariables['LIVE']['COMPANY_LOGO_SMALL'] = 'http://imagedataserver.com/SHARED/live/themes/IPS/skins/nms/ic/impactintacash.com/media/image/impactintacash_small.jpg';
	
		$this->configVariables['COMPANY_LOGO_LARGE'] = 'http://rc.impactintacash.com/imgdir/rc/media/image/impactintacash_large.jpg';
		$this->configVariables['COMPANY_LOGO_SMALL'] = 'http://rc.impactintacash.com/imgdir/rc/media/image/impactintacash_small.jpg';

		$this->configVariables['COMPANY_NAME'] = 'IntaCash';
		$this->configVariables['COMPANY_NAME_LEGAL'] = 'Impact IntaCash, LLC';
		$this->configVariables['COMPANY_NAME_SHORT'] = 'IntaCash';
		$this->configVariables['COMPANY_NAME_STREET'] = ''; /* IS THIS USED? [NEED_VERIFY] */
		$this->configVariables['COMPANY_PHONE_NUMBER'] = '1-866-964-6822';
		$this->configVariables['COMPANY_SITE'] = 'http://impactintacash.com';
		$this->configVariables['COMPANY_DOMAIN'] = 'impactintacash.com';
		$this->configVariables['COMPANY_SUPPORT_EMAIL'] = 'cs@impactintacash.com';
		$this->configVariables['COMPANY_SUPPORT_FAX'] = '1-866-974-6822';
		$this->configVariables['COMPANY_SUPPORT_PHONE'] = '1-866-964-6822';

		$this->configVariables['DOCUMENT_DEFAULT_ESIG_BODY'] = 'Generic Esig Email';
		$this->configVariables['DOCUMENT_DEFAULT_FAX_COVERSHEET'] = 'Fax Cover Sheet';
		$this->configVariables['DOCUMENT_TEST_EMAIL'] = 'ecash3iic@gmail.com';
		
		$this->configVariables['EMAIL_RECEIVE_DOCUMENT'] = 'Incoming Email Document';
		$this->configVariables['EMAIL_RESPONSE_DOCUMENT'] = 'Generic Message';

		/**
		 * Link for New App section in Header
		 */
		$this->configVariables['NEW_APP_SITE'] = 'http://www.impactintacash.com';
		$this->configVariables['RC']['NEW_APP_SITE'] = 'http://rc.impactintacash.com';
		$this->configVariables['LIVE']['NEW_APP_SITE'] = 'http://www.impactintacash.com';
		
		/**
		 * eSig Link
		 */
		$this->configVariables['ESIG_URL'] = 'http://rc.impactintacash.com';
		$this->configVariables['LIVE']['ESIG_URL'] = "http://impactintacash.com";
				/**
		  * DataX License and Password
		  */
		 $this->configVariables['DATAX_LICENSE_KEY'] = '015761d1eb5a3dd486225015708f43d0';
		 $this->configVariables['DATAX_PASSWORD'] = 'password';					
 	}
}
