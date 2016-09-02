<?php

require_once('AutoLoad.1.php');


class DefaultService extends Object_1
{
	protected $hi;
	protected $bye;

	function __construct($hi, $bye)
	{
		$this->hi = $hi;
		$this->bye = $bye;
		echo date('Y-m-d H:i:s ').__METHOD__.": This should be in the output buffer\n";
	}

	public function hello($name)
	{
		$r = $this->hi.' '.$name;
		echo __METHOD__." This should be in the output buffer";
		return $r;
	}

	public function goodbye($name)
	{
		$r = $this->bye.' '.$name;
		echo __METHOD__." This should be in the output buffer";
		return $r;
	}

	public function except(Exception $e = NULL)
	{
		throw $e === NULL ? new Exception('exceptFoo') : $e;
	}

	public function err($msg = NULL, $err = E_USER_ERROR)
	{
		trigger_error($msg === NULL ? 'errFoo' : $msg, $err);
	}
}

class AnotherService extends Object_1
{
	protected $key;
	public function __construct($key)
	{
		$this->key = $key;
		echo date('Y-m-d H:i:s ').__METHOD__.": This should be in the output buffer\n";
	}
	public function getKey()
	{
		return $this->key;
	}
}

$rpc = new Rpc_Server_1('DefaultService', array('Hello', 'Goodbye'), FALSE);
$rpc->addService('a', 'AnotherService', array('myKey'));

$rpc->processPost();

?>
