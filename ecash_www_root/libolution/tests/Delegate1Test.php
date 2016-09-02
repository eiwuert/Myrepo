<?php

class Delegate1Test extends PHPUnit_Framework_TestCase
{
	public static function uncallableProvider()
	{
		return array(
			array(1),
			array(new stdClass()),
			array(array(new stdClass())),
		);
	}

	/**
	 * @dataProvider uncallableProvider
	 */
	public function testConstructorRequiresCallable($call)
	{
		$this->setExpectedException('InvalidArgumentException', 'Must be callable');
		$d = new Delegate_1($call);
	}

	public function testFromFunction()
	{
		$expected = new Delegate_1('test');
		$actual = Delegate_1::fromFunction('test');

		$this->assertEquals($expected, $actual);
	}

	public function testFromMethod()
	{
		$expected = new Delegate_1(array('woot', 'test'));
		$actual = Delegate_1::fromMethod('woot', 'test');

		$this->assertEquals($expected, $actual);
	}

	public function testInvokeCallsMethod()
	{
		$mock = $this->getMock('stdClass', array('test'));
		$mock->expects($this->once())->method('test');
		$d = Delegate_1::fromMethod($mock, 'test');
		$d->invoke();
	}

	public function testInvokeCallsFunction()
	{
		$f = create_function('', 'throw new Exception("** CALLED");');
		$this->setExpectedException('Exception', '** CALLED');

		$d = Delegate_1::fromFunction($f);
		$d->invoke();
	}

	public function testInvokePassesParametersToDelegate()
	{
		$mock = $this->getMock('stdClass', array('test'));
		$mock->expects($this->atLeastOnce())
			->method('test')
			->with('a');

		$d = Delegate_1::fromMethod($mock, 'test');
		$d->invoke('a');
		$d->invokeArray(array('a'));
	}

	public function testInvokePrependsParams()
	{
		$mock = $this->getMock('stdClass', array('test'));
		$mock->expects($this->any())
			->method('test')
			->with('a', 'b');

		$d = Delegate_1::fromMethod($mock, 'test', array('a'));

		// test both here, to avoid a duplicate method
		$d->invoke('b');
		$d->invokeArray(array('b'));
	}
}

?>