<?php

require_once 'mode_test.php';

// These constants need to be defined before
// we include the prpc classes

// define applog constants
DEFINE('APPLOG_SIZE_LIMIT', 5000000000);
DEFINE('APPLOG_FILE_LIMIT', 20);

$mode = Mode_Test::Get_Mode();

switch($mode)
{
	// RC
	case Mode_Test::$RC:
		DEFINE('APPLOG_SUBDIRECTORY', 'rc_olp');
		DEFINE('APPLICATION', 'rc_olp');
		break;
	// live
	case Mode_Test::$LIVE:
    case Mode_Test::$LOCAL:
	default:
		DEFINE('APPLOG_SUBDIRECTORY', 'olp');
		DEFINE('APPLICATION', 'olp');
		break;
}
		
?>
