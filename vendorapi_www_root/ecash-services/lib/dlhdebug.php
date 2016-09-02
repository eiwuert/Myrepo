<?

defined('DLHDEBUG_DEFAULT_DIR') || define('DLHDEBUG_DEFAULT_DIR', '/tmp/dlhdebug.log');

// Super simple methods to help with debugging and tracing code.  Just
// put an "include_once 'dlhdebug.php';" into common_functions.php
// and then call dlhlog('some message')
// or dlhlog( 'some message:' . dlhvardump($field,false) ) at any time.


function dlhdebug_open_logfile( $logfile = DLHDEBUG_DEFAULT_DIR )
{
	@$fp = fopen( $logfile, 'a' );
	if ( $fp )
	{
		@chmod( $logfile, 0666 );
	}
	return $fp;
}

function dlhvardump( $var, $stripnewlines=true, $usePrintrOrVarDump = 1 ) {
	ob_start();

	// 1 ==> use print_r
	// 2 ==> use var_dump (or any value other than 1)
	if ( is_bool($var) || $usePrintrOrVarDump != 1 )
	{
		var_dump( $var );
	}
	else
	{
		print_r( $var );
	}
	$output = ob_get_contents();
	ob_end_clean();
	if ( $stripnewlines || is_bool($var) ) $output = preg_replace( '/(\s)+/', ' ', $output );
	return $output;
}


function dlhlogblank( $logfile = DLHDEBUG_DEFAULT_DIR ) {

	// this method simply inserts a blank line.

	$fp = dlhdebug_open_logfile( $logfile );

	if ( $fp )
	{
		fwrite($fp, "\r\n");
		fclose($fp);
	}

}

// This method will write a line to a logfile and will automatically
// append information revealing the filename and line number from which
// this method was called.

function dlhlog( $msg, $logfile = DLHDEBUG_DEFAULT_DIR ) {

	$fp = dlhdebug_open_logfile( $logfile );

	$line = '';
	$file = '';
	    
	$bt = debug_backtrace();
	
	if ( is_array($bt) ) {
		if ( isset($bt[0]['line']) ) {
			$line = ' line=' . $bt[0]['line'] . ' ';
		}
		if ( isset($bt[0]['file']) ) {
			$file = ', file=' . $bt[0]['file'] . ' ';
		}
	}

	if ( $fp )
	{
		fwrite($fp, date('Y-m-d H:i:s: ') . "$msg$file$line\r\n");
		fclose($fp);
	}
}

// This method will write a line to a logfile and will automatically
// append information revealing the filename and line number from which
// this method was called in addition to tracing the call stack.

function dlhlogtrace( $msg, $logfile = DLHDEBUG_DEFAULT_DIR )
{
	$fp = dlhdebug_open_logfile( $logfile );

	$line = '';
	$file = '';
	    
	$bt = debug_backtrace();

	$trace = '';
	foreach( $bt as $key => $val )
	{
		if ( $trace != '' ) $trace .= ', ';
		if ( isset($bt[$key]['file']) ) {
			$trace .= ' file=' . $bt[$key]['file'];
		}
		if ( isset($bt[$key]['line']) ) {
			$trace .= ' line=' . $bt[$key]['line'];
		}
	}
	
	if ( $fp )
	{
		fwrite($fp, date('Y-m-d H:i:s: ') . "$msg$trace\r\n");
		fclose($fp);
	}
}

?>