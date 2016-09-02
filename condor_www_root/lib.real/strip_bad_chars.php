<?php

// Quickie hack... D. Harris.
function Alt_Strip_Bad($var, $sgl_quote_handling='STRIP')
{
	$sgl_quote_handling = strtoupper($sgl_quote_handling);
	
	$result = preg_replace("/[^ a-zA-Z0-9-,.#'\/]/", "", $var);
	
	switch ($sgl_quote_handling)
	{
		case 'STRIP':
			$result = str_replace("'", ""  , $result);
			break;

		case 'ESCAPE':
			$result = str_replace("'", "''", $result);
			break;
		case 'NONE':
		default:
	}
	
	return $result;
}
?>
