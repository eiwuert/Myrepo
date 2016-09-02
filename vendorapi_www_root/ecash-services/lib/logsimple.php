<?

defined('LOGSIMPLE_DEFAULT_DIR') || define('LOGSIMPLE_DEFAULT_DIR', '/tmp/logsimple.log');

// DLH, 2006.04.05
// Super simple methods to help with debugging and tracing code.  Just
// use "include_once 'logsimple.php'" and then call logsimplewrite('some message');
// or logsimplewrite( 'some message: ' . dlhvardump($field,false) ) at any time.
//
// The good thing about this is that it automatically appends info showing the exact
// source file and line number from which it was called.
// 
// Method logsimpledump() provides flexibility for either doing print_r() or var_dump()
// with an option of stripping out all contiguous spaces and line breaks.  By default, this option
// is turned on so large arrays or objects will be conveniently displayed on a single line.
// To turn it off just pass false as the second parameter.
// For example:
//   logsimplewrite('large array is: ' . logsimpledump($myLargeArray));        // printed on a single line.
//   logsimplewrite('large array is: ' . logsimpledump($myLargeArray,false));  // array printed with line breaks.
//
// Method logsimpledump() automatically displays booleans using var_dump so if the boolean
// is false it will be displayed as "bool(false)" rather than just being displayed as spaces.


function logsimplewrite( $msg, $logfile = LOGSIMPLE_DEFAULT_DIR ) {

	$fp = logsimple_open_logfile( $logfile );

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


function logsimpledump( $var, $stripnewlines=true, $usePrintrOrVarDump = 1 ) {
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


// write message and add a full trace of the call history
function logsimplewrite_t( $msg, $logfile = LOGSIMPLE_DEFAULT_DIR )
{
	$fp = logsimple_open_logfile( $logfile );

	$line = '';
	$file = '';
	    
	$bt = debug_backtrace();

	$back_trace_count = 0;
	$back_trace_indicator = '';  // delimiter so that on a long like it will be easy to spot backtrace versus the real source of the log message.
	$trace = '';
	foreach( $bt as $key => $val )
	{
		if ( $trace != '' ) $trace .= ', ' . $back_trace_indicator;
		if ( isset($bt[$key]['file']) ) {
			$trace .= ' file=' . $bt[$key]['file'];
		}
		if ( isset($bt[$key]['line']) ) {
			$trace .= ' line=' . $bt[$key]['line'];
		}

		$back_trace_count++;
		$back_trace_indicator = " (###$back_trace_count) ";
	}
	
	if ( $fp )
	{
		fwrite($fp, date('Y-m-d H:i:s: ') . "$msg$trace\r\n");
		fclose($fp);
	}
}


function logsimple_open_logfile( $logfile = LOGSIMPLE_DEFAULT_DIR )
{
	@$fp = fopen( $logfile, 'a' );
	if ( $fp )
	{
		@chmod( $logfile, 0666 );
	}
	return $fp;
}


// insert a blank line in the log file.
function logsimpleblank( $logfile = LOGSIMPLE_DEFAULT_DIR ) {

	$fp = logsimple_open_logfile( $logfile );

	if ( $fp )
	{
		fwrite($fp, "\n");
		fclose($fp);
	}
}


function logsimpleecho($s) {
	if( php_sapi_name() != 'cli' ) {
		$s = str_replace('<', '&lt;', $s);
		$s = str_replace('>', '&gt;', $s);
		echo "<pre>$s\n</pre>";
	} else {
		echo "\n$s\n";
	}
}

?>