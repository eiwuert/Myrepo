<?php

if (! function_exists ('version_compare') || version_compare (phpversion (), '4.2.0', '<'))
{
    die("Requires PHP 4.2.0 or higher\n");
}

ini_set ('magic_quotes_runtime', 0);

define ('PRPC_PROT_VER', '1.0');

define ('PRPC_PACK_NO', 0);
define ('PRPC_PACK_GZ', 1);
define ('PRPC_PACK_BZ', 2);

/**
	@publicsection
	@public
	@brief
		Base class for Prpc Server/Client.

	Implements functionality common to the server and client.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
		1.1.0 2004-07-01 - Rodric Glaser
			- Modified for limited hosting servers.

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
}
?>
