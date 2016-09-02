<?php
/**
 * Tests the TSS_SuppressionList_1 class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class TSS_SuppressionList1Test extends PHPUnit_Framework_TestCase
{
	public static function dpTestMatchPass()
	{
		return array(
			array(array('test'), 'test'),
			array(array('/^test.*/'), 'test'),
			array(array('123123123'), 123123123),
			array(array('192.168.*'), '192.168.1.1'),
			array(array('*bob'), 'billy-bob'),
			array(array('bob+'), 'bobby'),
			array(array('bill%'), 'billy'),
			array(array('bill?'), 'bill'),
			array(array('bill?'), 'billy'),
			array(array('bill?'), 'bill'),
			array(array('?ill'), 'jill'),
			array(array('?ill'), 'ill'),
			array(array('wal\*mart'), 'wal*mart'),
			array(array('hello\?'), 'hello?'),
			array(array('wal\*mart*'), 'wal*marts'),
			array(array('Yo dude!'), 'yo Dude!'),
		);
	}

	/**
	 * Tests that various values match on the list
	 *
	 * @dataProvider dpTestMatchPass
	 * @param array $list_values
	 * @param mixed $value
	 * @return void
	 */
	public function testMatchPass(array $list_values, $value)
	{
		$list = new TSS_SuppressionList_1($list_values);
		$this->assertTrue($list->match($value));
	}

	public function testCanExecuteMatchTwice()
	{
		$list = new TSS_SuppressionList_1(array('test', 'woot'));
		$list->match('hi');
		$this->assertTrue($list->match('test'));
	}

	public static function dpTestMatchFail()
	{
		return array(
			array(array('test'), 'fail-test'),
			array(array('/^test.*/'), 'fail-test'),
			array(array('bob+'), 'bob'),
			array(array('bob+'), 'abob'),
			array(array('bill%'), 'billy guy'),
			array(array('bill%'), 'bill'),
			array(array('bill%'), 'obilly'),
			array(array('bill?'), 'billys'),
			array(array('bill\?'), 'billy'),
			array(array('wal\*mart'), 'wal-mart'),
		);
	}

	/**
	 * Tests that various values do not match on the list.
	 *
	 * @dataProvider dpTestMatchFail
	 * @param array $list_values
	 * @param mixed $value
	 * @return void
	 */
	public function testMatchFail(array $list_values, $value)
	{
		$list = new TSS_SuppressionList_1($list_values);
		$this->assertFalse($list->match($value));
	}
}
