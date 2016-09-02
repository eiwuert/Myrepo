<?php

/**
 * Test OLP_DB_WhereGlue objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_WhereGlueTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that the callback passed to the where glue makes it to each piece
	 * of the whole compound object.
	 *
	 * @return void
	 */
	public function testEscapeCallback()
	{
		$escape_function = 'mysqli_real_escape_string';
		$table_override = 'table_override';
		$where_cond = $this->getMock(
			'OLP_DB_WhereCond', 
			array('toSql'),
			array('fielda', OLP_DB_WhereCond::EQUALS, 'hello')
		);
		$where_cond->expects($this->once())
			->method('toSql')
			->with($this->equalTo($table_override), $this->equalTo($escape_function));
		
		$where_glue = new OLP_DB_WhereGlue();
		$where_glue->add($where_cond);
		$where_glue->setEscapeCallback($escape_function);
		
		$where_glue->toWhere($table_override);
	}
	/**
	 * Tests basic "AND" where clause assembly.
	 * @return void
	 */
	public function testAndCallsChildren()
	{
		$where_a = $this->getMockWhere('a');
		$where_b = $this->getMockWhere('b');
		$where_a->expects($this->once())->method('toSql');
		$where_b->expects($this->once())->method('toSql');
		
		$and = new OLP_DB_WhereGlue(OLP_DB_WhereGlue::AND_GLUE, array($where_a, $where_b));
		$this->assertEquals(strtoupper(substr($and->__toString(), 0, 6)), 'WHERE ');
	}
	
	/**
	 * Make sure And objects call the proper method with only one Where.
	 * @return void
	 */
	public function testAndCallsChild()
	{
		$where_c = $this->getMockWhere('c');
		$where_c->expects($this->exactly(2))->method('toSql');
		$and = new OLP_DB_And($where_c);
		// make sure the WHERE prefix gets added.
		$this->assertEquals(strtoupper(substr($and->toWhere(), 0, 6)), 'WHERE ');
		// also check that there's only 1 WHERE in here.
		$this->assertEquals(substr_count(strtoupper($and->toWhere()), 'WHERE'), 1);
	}
	
	/**
	 * make sure that And objects behave properly when acting as a fragment
	 * @return void
	 */
	public function testAndAsFragment()
	{
		$where_d = $this->getMockWhere('d');
		$where_d->expects($this->exactly(3))->method('toSql');
		$where_e = $this->getMockWhere('e');
		$where_e->expects($this->exactly(3))->method('toSql');
		$and = new OLP_DB_And($where_d, $where_e);
		// and fragments should be wrapped in parens
		$this->assertEquals(strtoupper(substr($and->toSql(), 0, 1)), '(');
		$this->assertEquals(
			strtoupper(substr($and->toSql(), strlen($and->toSql()) - 1, 1)), 
			')'
		);
	}
	
	/**
	 * make sure that And items can accept other And objects and tables are
	 * propogated from And objects properly.
	 *
	 * @return void
	 */
	public function testCompositeAnds()
	{
		$where_f = $this->getMockWhere('f');
		$where_f->expects($this->once())->method('toSql')->with($this->equalTo('z'));
		$little_and = $this->getMock('OLP_DB_And', array('toSql'), array('x = 2'));
		$little_and->expects($this->once())->method('toSql')->with($this->equalTo('z'));
		$and = new OLP_DB_And($where_f, $little_and);
		$and->setTable('z');
		$this->assertEquals(substr($and->__toString(), 0, 6), 'WHERE ');
	}
	
	/**
	 * Returns a mocked up OLP_DB_WhereCond object.
	 *
	 * @param string $value The value this should return when methods are called
	 * mostly irrelevant. (Methods in question are toWhere and toSql)
	 * @return OLP_DB_WhereCond
	 */
	protected function getMockWhere($value)
	{
		$where = $this->getMock(
			'OLP_DB_WhereCond', 
			array('toWhere', 'toSql'), 
			array($value)
		);
		$where->expects($this->any())
			->method('toWhere')
			->will($this->returnValue("WHERE $value"));
		$where->expects($this->any())
			->method('toSql')
			->will($this->returnValue("$value"));
		
		return $where;
	}
}

?>
