<?php

// too much info
define ('BO_GOBS',		1);
// optional info
define ('BO_TRACE',		2);
define ('BO_INFO',		4);
define ('BO_WARN',		8);
// always displayed/sent at ERROR or higher
define ('BO_ERROR',		32);
// sender should die after a FATAL or higher
define ('BO_FATAL',		64);
define ('BO_PANIC',		128);

class Bugout
{
	function msg ($msg, $lvl = BO_TRACE)
	{
		$name = array (1 => 'TRACE', 2 => 'INFO', 4 => 'WARN', 32 => 'ERROR', 64 => 'FATAL', 128 => 'PANIC');
		if ($lvl > 31 || $lvl & $GLOBALS ['bugout_level'])
		{
			$bt = debug_backtrace ();
			$out = $name[$lvl].'::'.$bt[0]['file'].'::'.$bt[0]['line']."\t".$msg;
			echo $out, "\n";
		}
	}
}

?>