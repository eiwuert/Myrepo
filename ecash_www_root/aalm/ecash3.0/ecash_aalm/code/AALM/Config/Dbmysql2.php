<?php

require_once('Base.php');

/**
 * Execution Mode
 */
define('EXECUTION_MODE', 'LIVE');

/**
 * Enterprise - ENVIRONMENT SPECIFIC - Overrides General
 */
class AALM_Config_Dbmysql2 extends AALM_Config_Base
{
        protected function init()
        {
                parent::init();

                /**
                 * Mode - Used for the log file directory
                 */
                $this->configVariables['mode'] =  'mls_live';

                /**
                 * Common Library Directories
                 */
                if (!defined('COMMON_LIB_DIR')) define('COMMON_LIB_DIR', '/virtualhosts/lib/');
                if (!defined('COMMON_LIB_ALT_DIR')) define('COMMON_LIB_ALT_DIR', '/virtualhosts/lib5/');
                if (!defined('LIBOLUTION_DIR')) define('LIBOLUTION_DIR', '/virtualhosts/libolution/');

                /**
                 * Database connections - Live Environment
                 */
                $this->configVariables['DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['DB_NAME'] = 'ldb_generic';
                $this->configVariables['DB_USER'] = 'superuser';
                $this->configVariables['DB_PASS'] = 'gh0stbusters';
                $this->configVariables['DB_PORT'] = 3306;

                $this->configVariables['SLAVE_DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['SLAVE_DB_NAME'] = 'ldb_generic';
                $this->configVariables['SLAVE_DB_USER'] = 'superuser';
                $this->configVariables['SLAVE_DB_PASS'] = 'gh0stbusters';
                $this->configVariables['SLAVE_DB_PORT'] = 3306;

                $this->configVariables['API_DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['API_DB_NAME'] = 'ldb_generic';
                $this->configVariables['API_DB_USER'] = 'superuser';
                $this->configVariables['API_DB_PASS'] = 'gh0stbusters';
                $this->configVariables['API_DB_PORT'] = 3306;
/*
                $this->configVariables['APPSERVICE_DB_HOST'] = 'sql1.loanservicingcompany.com';
                $this->configVariables['APPSERVICE_DB_NAME'] = 'aalm';
                $this->configVariables['APPSERVICE_DB_USER'] = 'ecash_aalm';
                $this->configVariables['APPSERVICE_DB_PASS'] = 'yUbrAq8SPUsA';
                $this->configVariables['APPSERVICE_DB_PORT'] = 1433;

                $this->configVariables['STATEOBJECT_DB_HOST'] = 'sql1.loanservicingcompany.com';
                $this->configVariables['STATEOBJECT_DB_NAME'] = 'aalm';
                $this->configVariables['STATEOBJECT_DB_USER'] = 'aalm';
                $this->configVariables['STATEOBJECT_DB_PASS'] = 's3tecR5m';
                $this->configVariables['STATEOBJECT_DB_PORT'] = 1433;

                $this->configVariables['STATUNIQUE_DB_HOST'] = 'sql1.loanservicingcompany.com';
                $this->configVariables['STATUNIQUE_DB_NAME'] = 'aalm';
                $this->configVariables['STATUNIQUE_DB_USER'] = 'stats_writer';
                $this->configVariables['STATUNIQUE_DB_PASS'] = 'da39swUbrut4';
                $this->configVariables['STATUNIQUE_DB_PORT'] = 1433;
*/

                $this->configVariables['APPSERVICE_DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['APPSERVICE_DB_NAME'] = 'ldb_generic';
                $this->configVariables['APPSERVICE_DB_USER'] = 'superuser';
                $this->configVariables['APPSERVICE_DB_PASS'] = 'gh0stbusters';
                $this->configVariables['APPSERVICE_DB_PORT'] = 3306;

                $this->configVariables['STATEOBJECT_DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['STATEOBJECT_DB_NAME'] = 'ldb_generic';
                $this->configVariables['STATEOBJECT_DB_USER'] = 'superuser';
                $this->configVariables['STATEOBJECT_DB_PASS'] = 'gh0stbusters';
                $this->configVariables['STATEOBJECT_DB_PORT'] = 3306;

                $this->configVariables['STATUNIQUE_DB_HOST'] = 'mysql2.loanservicingcompany.com';
                $this->configVariables['STATUNIQUE_DB_NAME'] = 'ldb_generic';
                $this->configVariables['STATUNIQUE_DB_USER'] = 'superuser';
                $this->configVariables['STATUNIQUE_DB_PASS'] = 'gh0stbusters';
                $this->configVariables['ASTATUNIQUE_DB_PORT'] = 3306;

                /**
                 * Statistics database connection constants
                 */
                $this->configVariables['STAT_MYSQL_HOST'] =  'mysql2.loanservicingcompany.com:3306';
                $this->configVariables['STAT_MYSQL_USER'] =  'superuser';
                $this->configVariables['STAT_MYSQL_PASS'] =  'gh0stbusters';

                /**
                 * QuickChecks Return Files Directory
                 */
                if (!defined('QC_RETURN_FILE_DIR')) define('QC_RETURN_FILE_DIR', '/tmp/');

                /**
                 * Paths to directories and files
                 */
                $this->configVariables['NSF_MAILER_DIR'] =  '/tmp/ecash3.0/ach_mailer';
                $this->configVariables['PDFLIB_LICENSE_FILE'] =  "/etc/pdflib/pdflib_licenses.txt";

                /**
                 * Force Redirection to SSL
                 */
                $this->configVariables['FORCE_SSL_LOGIN'] = 'ON';

                /**
                 * Master DOMAIN, used for requests that MUST be executed on the same server
                 * ie, ach batches, quick check batches, etc and should be a subdomain of
                 * the load balanced domain.
                 */
                $this->configVariables['MASTER_DOMAIN'] = 'dbmysql2.ecash.loanservicingcompany.com';
                $this->configVariables['LOAD_BALANCED_DOMAIN'] = 'dbmysql2.ecash.loanservicingcompany.com';
                $this->configVariables['COOKIE_DOMAIN'] = '.loanservicingcompany.com';

                /**
                 * ACH Overrides
                 */
                $this->configVariables['ACH_BATCH_SERVER'] = 'drop1.sellingsource.com';
                $this->configVariables['ACH_BATCH_URL'] = '/home/aalmach/upload';
                //$this->configVariables['ACH_BATCH_SERVER'] = 'ftps.teledraft.com';
                //$this->configVariables['ACH_BATCH_URL'] = '/upload';

                /**
                 * Other data
                 */
                $this->configVariables['REACT_SOAP_KEY'] = 'b4b84688c8055f2896ed5b98843a7bf1 ';
                $this->configVariables['ECASH_APP'] = 'http://ecashapp.com/';
                $this->configVariables['REACT_SOAP_URL'] = 'https://www.tridentsecuredata.com/cm_soap.php?wsdl';
                $this->configVariables['VAPI_PRPC_URL'] = 'https://commercial.vendorapi.loanservicingcompany.com/index.php';

                /**
                 * Notification Recipients
                 *
                 * NOTIFICATION_ERROR_RECIPIENTS is more for administrative use.  ACH processing errors, Alerts, etc.
                 * ECASH_NOTIFICATION_ERROR_RECIPIENTS is for Exceptions.
                 */
                $this->configVariables['NOTIFICATION_ERROR_RECIPIENTS'] = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net'; //'richard.bunce@sellingsource.com';
                $this->configVariables['ECASH_NOTIFICATION_ERROR_RECIPIENTS'] = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net'; //'richard.bunce@sellingsource.com,justin.foell@sellingsource.com';

                /**
                 * Online Help Root URL (Live)
                 */
                $this->configVariables['ONLINE_HELP_ADMIN_URL_ROOT'] = 'http://aalm.help.ecash.edataserver.com/admin/';
                $this->configVariables['ONLINE_HELP_FUNDING_URL_ROOT'] = 'http://aalm.help.ecash.edataserver.com/funding/';
                $this->configVariables['ONLINE_HELP_SERVICING_URL_ROOT'] = 'http://aalm.help.ecash.edataserver.com/servicing/';
                $this->configVariables['ONLINE_HELP_COLLECTIONS_URL_ROOT'] = 'http://aalm.help.ecash.edataserver.com/collections/';
                $this->configVariables['ONLINE_HELP_REPORTING_URL_ROOT'] = 'http://aalm.help.ecash.edataserver.com/reporting/';
                $this->configVariables['ONLINE_HELP_DEFAULT_INDEX'] = 'ecash_online_help.htm';

                /**
                 * Database connections
                 */
        $this->configVariables['DB_MASTER_CONFIG'] = new ECash_DB_CachedConfig(new DB_MySQLConfig_1(
                $this->configVariables['DB_HOST'],
                $this->configVariables['DB_USER'] ,
                $this->configVariables['DB_PASS'],
                $this->configVariables['DB_NAME'],
                $this->configVariables['DB_PORT']
                ));
         $this->configVariables['DB_SLAVE_CONFIG'] = new ECash_DB_CachedConfig(new DB_MySQLConfig_1(
                $this->configVariables['SLAVE_DB_HOST'],
                $this->configVariables['SLAVE_DB_USER'] ,
                $this->configVariables['SLAVE_DB_PASS'],
                $this->configVariables['SLAVE_DB_NAME'],
                $this->configVariables['SLAVE_DB_PORT']
                ));
         $this->configVariables['DB_API_CONFIG'] = new ECash_DB_CachedConfig(new DB_MySQLConfig_1(
                $this->configVariables['API_DB_HOST'],
                $this->configVariables['API_DB_USER'] ,
                $this->configVariables['API_DB_PASS'],
                $this->configVariables['API_DB_NAME'],
                $this->configVariables['API_DB_PORT']
                ));
        $this->configVariables['DB_APPSERVICE_CONFIG'] = new DB_MSSQLConfig_2(
                $this->configVariables['APPSERVICE_DB_HOST'],
                $this->configVariables['APPSERVICE_DB_USER'] ,
                $this->configVariables['APPSERVICE_DB_PASS'],
                $this->configVariables['APPSERVICE_DB_NAME'],
                $this->configVariables['APPSERVICE_DB_PORT']
                );
        $this->configVariables['DB_STATEOBJECT_CONFIG'] = new DB_MSSQLConfig_2(
                $this->configVariables['STATEOBJECT_DB_HOST'],
                $this->configVariables['STATEOBJECT_DB_USER'] ,
                $this->configVariables['STATEOBJECT_DB_PASS'],
                $this->configVariables['STATEOBJECT_DB_NAME'],
                $this->configVariables['STATEOBJECT_DB_PORT']
                );
                $this->configVariables['DB_STATUNIQUE_CONFIG'] = new DB_MSSQLConfig_2(
                        $this->configVariables['STATUNIQUE_DB_HOST'],
                        $this->configVariables['STATUNIQUE_DB_USER'] ,
                        $this->configVariables['STATUNIQUE_DB_PASS'],
                        $this->configVariables['STATUNIQUE_DB_NAME'],
                        $this->configVariables['STATUNIQUE_DB_PORT']
                );

                /**
                 * Path To File System Encryption Key
                 */
                $this->configVariables['FILE_SYSTEM_KEY_PATH'] = $this->configVariables['CUSTOMER_BASE_DIR'] .
                                                                                                                 '../../encryption/' . strtolower($this->configVariables['ENTERPRISE_PREFIX']) .
                                                                                                                 '/live/fs_key.dat';

                $this->configVariables['FACTORY'] = new ECash_Factory(
                        $this->configVariables['ENTERPRISE_PREFIX'],
                        $this->configVariables['DB_MASTER_CONFIG']
                );

                /**
                 * Application Site Redirection
                 */
                $this->configVariables['IS_HTTPS'] = TRUE;

                /**
                 * Application Service URLs
                 */
                $this->configVariables['APP_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/app/index.php?wsdl';
                $this->configVariables['INQUIRY_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/inquiry/index.php?wsdl';
                $this->configVariables['REFERENCES_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/reference/index.php?wsdl';
                $this->configVariables['DOCUMENT_HASH_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/document_hash/index.php?wsdl';
                $this->configVariables['DOCUMENT_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/document/index.php?wsdl';
                $this->configVariables['AGGREGATE_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/aggregate/index.php?wsdl';
                $this->configVariables['LOAN_ACTION_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/loanaction/index.php?wsdl';
                $this->configVariables['QUERY_SERVICE_URL'] = 'https://live.ecash.loanservicingcompany.com/api/query/index.php?wsdl';

        $this->configVariables['VAPI_MEMCACHE_SERVERS'] = array(
            array('host' => 'ps31.ept.tss', 'port' => 11211),
            array('host' => 'ps32.ept.tss', 'port' => 11211),
            array('host' => 'ps33.ept.tss', 'port' => 11211),
        );
        }
}
