<?php

define ('PRPC_PROT_VER', '2.0');

if (! function_exists ('version_compare') || version_compare (phpversion (), '5.0.1', '<'))
{
    exit("PRPC v".PRPC_PROT_VER." requires PHP 5.0.1 or higher\n");
}


define ('PRPC_PACK_NO', 0);
define ('PRPC_PACK_GZ', 1);
define ('PRPC_PACK_BZ', 2);


ini_set ('magic_quotes_runtime', 0);

/**
	@publicsection
	@public
	@brief
		Base class for Prpc Server/Client.

	Implements functionality common to the server and client.

	@version
		2.0.0 2004-09-21 - Rodric Glaser
			- Update for PHP5
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision

	@todo
		- Nothing
*/
class Prpc_Base
{
	var $_prpc_use_pack;
	var $_prpc_use_debug;

	var $_prpc_debug;

	function _Prpc_Pack ($data)
	{
		switch ($this->_prpc_use_pack)
		{
			case PRPC_PACK_GZ:
				$pack = gzcompress (serialize ($data));
				break;
			case PRPC_PACK_BZ:
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
			case PRPC_PACK_GZ:
				$data = @gzuncompress ($pack);
				break;
			case PRPC_PACK_BZ:
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
}
?>
