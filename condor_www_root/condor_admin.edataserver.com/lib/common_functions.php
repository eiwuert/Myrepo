<?php

set_error_handler('Ccsadmin_Error_Handler');

function Ccsadmin_Error_Handler($error_type, $error_string, $error_file, $error_line)
{
	if ($error_type & E_ERROR)
	{
		echo( "error_type=$error_type, error_string=$error_string, error_file=$error_file, error_line=$error_line" );
	}
	else
	{
		// UNCOMMENT this (or comment out set_error_handler()) if you want to see all errors, even notice errors.
		// echo( "error_type=$error_type, error_string=$error_string, error_file=$error_file, error_line=$error_line" );
	}
}

if (!function_exists ('mime_content_type'))
{
   function mime_content_type($f)
   {
       return system(trim('file -bi ' . escapeshellarg($f)));
   }
} 

function __autoload($class_name)
{
	$partial_path = strtolower($class_name) . ".class.php";
	if(file_exists(CLIENT_CODE_DIR . $partial_path))
	{
		include_once(CLIENT_CODE_DIR . $partial_path);
	}
	elseif(file_exists(SERVER_CODE_DIR . $partial_path))
	{
		include_once(SERVER_CODE_DIR . $partial_path);
	}
	elseif(file_exists(LIB_DIR . $partial_path))
	{
		include_once(LIB_DIR . $partial_path);
	}
}


function To_String($object)
{
	ob_start();
	if(!method_exists($object, '__toString'))
	{
		print_r($object);
	}
	else
	{
		echo $object;
	}
	return ob_get_clean();
}

	
function Db2_Escape_Chars ($string)
{
	if (is_string ($string))
	{
		$string = str_replace ("'", "''", $string);
			
	}
		
	return $string;
}


Function IsIntegerValue( $input )
{
	if (is_integer($input))
	{
		return true;
	}
	elseif (strval(intval($input)) === $input)
	{
		return true;
	}
	elseif (str_pad(strval(intval($input)), strlen($input), "0", STR_PAD_LEFT) === $input)
	{
		return true;
	}
	else
	{
		return false;
	}
}

// Globally scoped variable
$debug_output = "";


// Quietly outputs the passed variable (and optional name) at the end of parsed output.
// Encased in HTML comments, so it'll lamost never show up on the rendered page, but you can see it
// in the HTML source.
function dvar_dump($var, $varname = null)
{
  if (EXECUTION_MODE == 'LIVE') return;
  global $debug_output;
  
  ob_start();
  var_dump($var);
  if ($varname == null)
  {
    $out = "\n<!-- " . ob_get_clean() . " -->\n"; 
  }
  else
  {
    $out = "\n<!-- ${varname}: \n" . ob_get_clean() . " -->\n";
  }

  $debug_output .= $out;
}

// When dumping into the parsed output isn't an option (some parse-ending errors occurs)
// use this diddy to just pipe it straight to the screen. Encases the output in "preformat"
// tags to make it readable.
function dvar_echo($var, $varname = NULL)
{
  if (EXECUTION_MODE == 'LIVE') return;
  $out = "<pre>";
  if (!is_null($varname)) $out .= "${varname}:\n\n";
  ob_start();
  var_dump($var);
  $out .= ob_get_clean() . "</pre>";
  echo $out;
}


// This function will strip out these characters: < > " ' &
// replacing them with html entities.  Using this on all fields coming from
// forms will help prevent cross-site scripting and sql injection attacks.
// This function should be called rather than just calling htmlspecialchars()
// directly because then we'll have a single spot where we can monitor and control
// all form input and if we need to do additional filtering, we'll be able
// to do it in just this one spot.
function filter_form_input($s)
{
	$s = isset($s) ? trim($s) : '';
	$result = htmlspecialchars($s, ENT_QUOTES);
	return $result;
}


function getprintr( $var, $stripnewlines=true ) {
	ob_start();
	print_r( $var );
	$output = ob_get_contents();
	ob_end_clean();
	if ( $stripnewlines ) $output = preg_replace( '/(\s)+/', ' ', $output );
	return $output;
}


?>