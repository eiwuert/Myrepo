<?php

class Deferred1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Deferred_1
	 */
	protected $deferred;

	public function setUp()
	{
		$this->deferred = new Deferred_1();
	}

	public function testComplete()
	{
		$m = $this->getMocker('woot', array(1));
		$d = Delegate_1::fromMethod($m, 'woot');

		$this->deferred->addOnComplete($d);
		$this->deferred->complete(1);
	}

	protected function getMocker($method, array $expected_params, $times = NULL)
	{
		$mock = $this->getMock('stdClass', array($method));

		$times = ($times === NULL)
			? $this->any()
			: $this->exactly($times);

		$e = $mock->expects($times)
			->method($method);
		call_user_func_array(array($e, 'with'), $expected_params);

		return $mock;
	}
}

?>