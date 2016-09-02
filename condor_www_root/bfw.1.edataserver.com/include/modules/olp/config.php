<?php

// set constants //
DEFINE('PASSWORD_ENCRYPTION',TRUE);
DEFINE('SMTP_OLE' , 'prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');
DEFINE('OLP6_GOLIVE_TIMESTAMP', mktime(0, 0, 0, 6, 1, 2005)); //official complete live date of OLP6, June 1, 2005
DEFINE('STAT_DB', 'STATS');
// moved similar constants here for marquisnet setup
DEFINE('DIR_OLP', BFW_BASE_DIR.'include/modules/olp');
DEFINE('PRPC5', DIR_LIB5 . 'prpc/');
DEFINE('BASE_DB', 'blackbox');
DEFINE('OLP_DIR', BFW_BASE_DIR.'include/modules/olp/');
DEFINE('BLACKBOX_DIR', BFW_BASE_DIR.'include/modules/blackbox/');
DEFINE('OUT_PHP_DIR' , BFW_BASE_DIR.'include/code/');
define('ECASH_COMMON_DIR', '/virtualhosts/ecash_common/');
define('LIB_DIR','/virtualhosts/lib/');

// DEFINED VARS PER MODE//

switch(strtoupper(BFW_MODE))
{

	// LIVE
	case 'LIVE':

		DEFINE("DEBUG", FALSE);
		// CONDOR
		DEFINE('CONDOR_SERVER' , 'prpc://condor.3.edataserver.com/');

        break;

	//LOCAL and RC
    case 'LOCAL':
    case 'RC':
    default:
		DEFINE("DEBUG", TRUE);
		// CONDOR
		DEFINE('CONDOR_SERVER' , 'prpc://rc.condor.3.edataserver.com/');
}

// required library files
require_once('aba.bad.php');
require_once('data_validation.2.php');
// do we need this??
require_once('cash_lynk.2.php');
include_once('security.3.php');
include_once('crypt.3.php');
include_once(BFW_BASE_DIR . 'include/code/OLP_Applog.php');
include_once('timer.class.php');
include_once('pay_date_calc.1.php');
include_once(BFW_CODE_DIR . 'OLP_Qualify_2.php');
include_once(BFW_CODE_DIR . 'OLPECashHandler.php');
include_once('ole_mail.2.php');
include_once('condor_client.php');

// required OLP FILES
include_once('olp.php');
include_once('payroll.php');
include_once('stat_limits.php');
include_once('pre_qualify.php');
//include_once('olp.db2.class.php');
//include_once('olp.mysql.class.php');
include_once(BLACKBOX_DIR . 'blackbox.php');
include_once('fcna_handler.php');
include_once('app_campaign_manager.php');
include_once('return_handler.php');
include_once('populate.2.php');
include_once(BFW_CODE_DIR.'template_messages.php');
require_once BFW_CODE_DIR . 'OLP_Fraud.php';
OLPBlackbox_Setup::doSetup();

// stat class
include_once('stats.php');
include_once('stats_spaces.php');

include_once(OUT_PHP_DIR . 'data_preparation.php');
include_once(OUT_PHP_DIR . 'check_page_order.php');
include_once(OUT_PHP_DIR . 'db_exception_handler.php');
include_once(OUT_PHP_DIR . 'pay_date_validation.php');

// setup up classes for posting
include_once(BLACKBOX_DIR . 'vendor_post.php');
include_once(BLACKBOX_DIR . 'abstract_vendor_post_implementation.php');
include_once(BLACKBOX_DIR . 'http_client.php');
include_once(BLACKBOX_DIR . 'log_vendor_post.php');

require_once(ECASH_COMMON_DIR . 'ecash_api/ecash_api.2.php');
require_once('libolution/AutoLoad.1.php');

?>
