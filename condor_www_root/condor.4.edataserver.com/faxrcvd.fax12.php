#!/usr/bin/php
<?php
	
define('MODE_LIVE', 'LIVE');
define('MODE_RC', 'RC');
define('MODE_DEV', 'LOCAL');

// our mode of operation
define('EXECUTION_MODE', MODE_LIVE);

define('DIR_CONDOR', '/virtualhosts/condor.4.edataserver.com/live');
define('DIR_LIB', '/virtualhosts/lib5');

define('CORRUPT_TIFF', '/virtualhosts/condor.4.edataserver.com/data/corrupt.tiff');
define('BIN_FAXINFO', '/usr/sbin/faxinfo');
define('BIN_TIFFINFO', '/usr/bin/tiffinfo');
define('BIN_TIFF2PDF', 'tiff2pdf');
define('DID_ROUTING',FALSE);
include_once(DIR_CONDOR.'/faxrcvd.php');
