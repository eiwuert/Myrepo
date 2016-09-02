<?php

if (! function_exists ('version_compare') || version_compare (phpversion (), '4.3.0', '<'))
{
    die("Requires PHP 4.3.0 or higher\n");
}

ini_set ('magic_quotes_runtime', 0);

define ('PRPC2_PROT_VER', '2.0');

/*
define ('PRPC_PACK_NO', 0);
define ('PRPC_PACK_GZ', 1);
define ('PRPC_PACK_BZ', 2);
*/

define ('PRPC_TRACE_NONE', 0);
define ('PRPC_TRACE_CALL', 1);
define ('PRPC_TRACE_ARGS', 2);
define ('PRPC_TRACE_FILE_CLIENT', 4);
define ('PRPC_TRACE_FILE_SERVER', 8);
define ('PRPC_TRACE_RESULT', 16);

define ('PRPC_TRACE_FILE', PRPC_TRACE_FILE_CLIENT + PRPC_TRACE_FILE_SERVER);
define ('PRPC_TRACE_ALL', 255);

// Added for PHP5 compatibility
define ('PRPC_COMPRESS_NO', 0);
define ('PRPC_COMPRESS_GZ', 1);
define ('PRPC_COMPRESS_BZ', 2);
define ('PRPC_SERIALIZE_STANDARD', 1);
define ('PRPC_EXCEPTION_PHP4', 1);


/**
	@publicsection
	@public
	@brief
		Base class for Prpc Server/Client.

	Implements functionality common to the server and client.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
		2.0.0 2004-11-19 - Paul Strange
			- Update to support PHP5 version

	@todo
		- Nothing
*/
class Prpc_Base2
{
	const PRPC_PACK_NO = 0;
	const PRPC_PACK_GZ = 1;
	const PRPC_PACK_BZ = 2;
	
	var $_prpc_use_pack;
	var $_prpc_use_debug;
	var $_prpc_use_trace;

	var $_prpc_debug;

	// Added for PHP5 compatibility
	var $_serialize_method;
	var $_compress_method;
	var $_exception_method;
	
	function _Prpc_Pack ($data)
	{
		switch ($this->_prpc_use_pack)
		{
			case self::PRPC_PACK_GZ:
				$pack = gzcompress (serialize ($data));
				break;
			case self::PRPC_PACK_BZ:
				$pack = bzcompress (serialize ($data));
				break;
			default:
				$pack = serialize ($data);
		}
		return $pack;
	}

	function _Prpc_Unpack ($pack)
	{
		switch ($this->_prpc_use_pack)
		{
			case self::PRPC_PACK_GZ:
				$data = @gzuncompress ($pack);
				break;
			case self::PRPC_PACK_BZ:
				$data = @bzdecompress ($pack);
				break;
			default:
				$data = $pack;
		}
		
		return @unserialize ($data);
	}

	function _Prpc_Get_Host ()
	{
		if (! isset ($this->_prpc_get_host_cache))
		{
			ob_start ();
			$rc = system ('hostname -f');
			$ob = trim (ob_get_clean ());
			$this->_prpc_get_host_cache = ($rc === FALSE ? $_SERVER['SERVER_NAME'] : $ob);
		}
		return $this->_prpc_get_host_cache;
	}

	function _Debug ($msg, $type = NULL)
	{
		if (@$_SERVER ["HTTP_X_PRPC_DEPTH"])
		{
			echo $msg, "\n";
		}
		else
		{
			$this->_prpc_debug .= $msg."\n";
		}
	}

	function _Trace ($frame, $type, $opt = NULL)
	{
		if (($type = ($this->_prpc_use_trace & $type)))
		{
			$btrace = debug_backtrace ();
			$host = $this->_Prpc_Get_Host ();
			$level = number_format (@$_SERVER ['HTTP_X_PRPC_DEPTH']);

			switch (TRUE)
			{
				case ($type & PRPC_TRACE_ARGS):
					ob_start (); print_r ($btrace[$frame]['args']); $args = ob_get_clean ();
					$this->_Debug ("\nTRACE:".$level.":PRPC_ARGS @ ".$host.":".$btrace[$frame]['file'].", line = ".$btrace[$frame]['line'].", method = ".ucwords($opt->method)."(), target = ".$this->_prpc_url."\n".$args."\n");
					break;

				case ($type & PRPC_TRACE_RESULT):
					ob_start (); print_r ($opt['result']); $res = ob_get_clean ();
					$this->_Debug ("\nTRACE:".$level.":PRPC_RESULT @ ".$host.":".$btrace[$frame]['file'].", line = ".$btrace[$frame]['line'].", method = ".ucwords($opt['method'])."(), target = ".$this->_prpc_url."\n".$res."\n");
					break;

				case ($type & PRPC_TRACE_CALL):
					$this->_Debug ("\nTRACE:".$level.":PRPC_CALL @ ".$host.":".$btrace[$frame]['file'].", line = ".$btrace[$frame]['line'].", method = ".ucwords($opt->method)."(), target = ".$this->_prpc_url);
					break;

				case ($type & PRPC_TRACE_FILE_SERVER):
					$this->_Debug ("\nTRACE:".$level.":PRPC_SERVER @ ".$host.":".$btrace[$frame]['file'].", line = ".$btrace[$frame]['line'].", method = ".ucwords($opt->method)."()");
					break;

				case ($type & PRPC_TRACE_FILE_CLIENT):
					if (! $level)
						$this->_Debug ("\nTRACE:".$level.":PRPC_CLIENT @ ".$host.":".$btrace[$frame]['file'].", line = ".$btrace[$frame]['line']);
					break;
			}
		}
	}
}
?>
