<?php

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


class Rpc_CallTest extends PHPUnit_Framework_TestCase
{
		protected $server;

		public function setUp()
		{
			$this->server = new Rpc_Server_1('DefaultService', array('Hello', 'Goodbye'), FALSE);
		}

		public function tearDown()
		{
			unset($this->server);
		}

        public function testCall()
		{
			$call = new Rpc_Call_1();
			$call->addMethod('hi', 'hello', array('llama'));
			$call->addMethod('bye', 'goodbye', array('llama'));
			$call->addMethod('exc', 'except');
			$call->addMethod('err', 'err');

			// cant actually send over http but still want to test the en/decode
			$enc = Rpc_1::encode($call);
			$dec = Rpc_1::decode($enc);

			$res = $this->server->processCall($dec);

			$this->assertRegExp('/should be in the output buffer/', $res->output);

			$this->assertSame(Rpc_1::T_RETURN, $res['hi'][0]);
			$this->assertEquals('Hello llama', $res['hi'][1]);
			$this->assertRegExp('/should be in the output buffer/', $res['hi'][2]);

			$this->assertSame(Rpc_1::T_RETURN, $res['bye'][0]);
			$this->assertEquals('Goodbye llama', $res['bye'][1]);
			$this->assertRegExp('/should be in the output buffer/', $res['bye'][2]);

			$this->assertSame(Rpc_1::T_THROW, $res['exc'][0]);
			$this->assertType('Exception', $res['exc'][1]);

			$this->assertSame(Rpc_1::T_THROW, $res['err'][0]);
			$this->assertType('Exception', $res['err'][1]);
		}

}

?>
