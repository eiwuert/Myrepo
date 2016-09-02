<?php
/**
	An abstract base for prpc objects.
*/

abstract class SourcePro_Prpc_Base
{
	const PRPC_COMPRESS_NO = 0;
	const PRPC_COMPRESS_GZ = 1;
	const PRPC_COMPRESS_BZ = 2;
	const PRPC_COMPRESS_ANY = 99;
	
	const PRPC_SERIALIZE_NONE = 0;
	const PRPC_SERIALIZE_STANDARD = 1;
	const PRPC_SERIALIZE_BINARY = 2;
	const PRPC_SERIALIZE_WDDX = 3;
	const PRPC_SERIALIZE_SOAP = 4;
	const PRPC_SERIALIZE_AMF = 5;
	const PRPC_SERIALIZE_XMLRPC = 6;
	const PRPC_SERIALIZE_POST = 7;
	const PRPC_SERIALIZE_GET = 8;
	
	const PRPC_EXCEPTION_PHP5 = 0;
	const PRPC_EXCEPTION_PHP4 = 1;
	const PRPC_EXCEPTION_ARRAY = 2;
	const PRPC_EXCEPTION_XML = 3;
	const PRPC_EXCEPTION_STRING = 4;
	
	const PRPC_DIRECTION_IN = 0;
	const PRPC_DIRECTION_OUT = 1;

	protected $_serialize_method;
	protected $_compress_method;
	protected $_exception_method;
	
	protected function _pack ($data)
	{
		$ser = new SourcePro_Prpc_Utility_Serialization ($data, $this->_serialize_method, self::PRPC_DIRECTION_IN);
		$pack = new SourcePro_Prpc_Utility_Compress ($ser->result, $this->_compress_method, self::PRPC_DIRECTION_IN);
		
		return $pack->result;
	}

	protected function _unpack ($pack)
	{
		$comp = new SourcePro_Prpc_Utility_Compress ($pack, $this->_compress_method, self::PRPC_DIRECTION_OUT);
		$data = unserialize($comp->result);
		return $data;
		//this &$#%*) crashes php
		//$data = new SourcePro_Prpc_Utility_Serialization ($comp->result, $this->_serialize_method, self::PRPC_DIRECTION_OUT);
		//return $data->result;
	}
}
?>
