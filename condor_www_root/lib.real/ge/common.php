<?php



//--------------------------------------------------------------------------------
// credit card companies require an order id, usually 16 chars
// long, of which the first 8 chars must be unique.

// in order to build a unique order id,
// i translate hour, minute, and second each, into 1 character.
// so i need to be able to translate 60 values into 60 characters.
// this is how i generate unique file names, so long as i don't
// allow more than one image per second.
//--------------------------------------------------------------------------------

function new_order_id () {

// this actually gives me 62 chars to translate to.
// special chars are risky to use here, the payment gateway may not like them.
define('TRANSLATE'
	,'0123456789'
	.'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
	.'abcdefghijklmnopqrstuvwxyz'
	);

	$time = localtime();
	return ORDERID_PREFIX
		.substr(TRANSLATE,($time[5]-100),1)	// years since 2000
		.substr(TRANSLATE,$time[4],1)	// month of the year
		.substr(TRANSLATE,$time[3],1)	// day of the month
		.substr(TRANSLATE,$time[2],1)	// hour of the day
		.substr(TRANSLATE,$time[1],1)	// minute of the hour
		.substr(TRANSLATE,$time[0],1)	// second of the minute
		.substr(TRANSLATE,rand(0,59),1)	// we don't have microseconds, so generate a single random digit
		;
}




/* ----------------------------------------------------------------------
 * if a variable is set, return the quoted value, otherwise NULL
 * ----------------------------------------------------------------------
 */
	function dbval()
	{
		foreach ( func_get_args() as $arg ) {
			if ( isset($arg) ) {
				return "'".$arg."'";
			}
		}
		return "NULL";
	}

/* ----------------------------------------------------------------------
 * functions php should have
 * ----------------------------------------------------------------------
 */
function scandir($dir)
{
	$contents = array();
	if ($dh = opendir($dir)) {
		while (false !== ($entry = readdir($dh))) {
			if ( 0 !== strpos($entry,'.') ) {
				$contents[] = $entry;
			}
		}
	closedir($dh);
	}
	return $contents;
}

function dironly (&$ary,$prefix='') {
	foreach ( $ary as $file ) {
		if ( is_dir($prefix.'/'.$file) ) {
			$return[] = $file;
		}
	}
	return $return;
}

function fileonly (&$ary,$prefix='') {
	foreach ( $ary as $file ) {
		if ( is_file($prefix.'/'.$file) ) {
			$return[] = $file;
		}
	}
	return $return;
}

define ('FILE_APPEND','1');
function file_put_contents($location,$data,$flags=0)
{
	$mode = ( FILE_APPEND & $flags ) ? 'a' : 'w';
	$fh = fopen ($location,$mode);
	$n = fwrite ($fh,$data);
	fclose ($fh);

	return $n;
}

function array_combine(&$keys,&$values)
{
	for ( $i=0; $i<sizeof($keys); $i++ ) {
		$new[$keys[$i]] = $values[$i];
	}
	return $new;
}

function array_add_vector(&$ary,&$vec,$name,$ary_begin=0)
{
	for ( $i=0; $i<sizeof($vec); $i++ ) {
		$ary[$ary_begin++][$name] = $vec[$i];
	}
	return;
}

function array_tolist(&$ary,$delimiter="\n")
{
	if ( !is_array($ary)) { return; }

	$sep = '';
	foreach ( $ary as $item ) {
		if ( is_array($item) ) {
			$buf .= array_tolist($item,$sep);
		} else {
			$buf .= $sep.$item;
		}
		$sep = $delimiter;
	}
	return $buf;
}

// pathinfo() is bogus, it includes the extension in the basename
function pathparts ($file) {
	preg_match('/^(.*\/)*([^\.\/]+)([^\/]*)$/',$file,$path);
#	print_r($path);
	array_shift($path);
	return $path;
}

function http_build_query ($obj)
{
	$sep = '';
	foreach ($obj as $key => $value) {
		if (is_array($value) || is_object($value))
			continue;
		$return .= $sep.urlencode($key).'='.urlencode($value);
		$sep = '&';
	}
	return $return;
}

/* ----------------------------------------------------------------------
 * my special functions
 * ----------------------------------------------------------------------
 */
function mylog ($text)
{
	$msg = strftime('%Y.%m.%d %T: ').$text."\n";
	return file_put_contents('/tmp/webadmin.log',$msg,FILE_APPEND);
}

?>
