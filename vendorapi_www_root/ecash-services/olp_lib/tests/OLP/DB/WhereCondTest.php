<?php

/**
 * Test OLP_DB_WhereCond objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_WhereCondTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test passing an escape method to a WhereCond object.
	 *
	 * @return void
	 */
	public function testEscapingFunction()
	{
		$escaped_string = "where fielda = '&&cookie&&'";
		
		$where = new OLP_DB_WhereCond('fielda', OLP_DB_WhereCond::EQUALS, 'cookie');
		$where->setEscapeCallback(array($this, 'specialEscape'));
		$sql = strtolower(trim($where->toWhere()));
		$this->assertEquals(
			$escaped_string, $sql, "string [$escaped_string] did not match sql [$sql]"
		);
	}
	
	/**
	 * Special string escaping function used in {@see testEscapingFunction}.
	 *
	 * This actually just sandwiches the string in && bookends so we can verify
	 * it was run on the right part of the where cond.
	 * 
	 * @param string $string The string to "escape."
	 * @return string The "escaped" string.
	 */
	public function specialEscape($string)
	{
		return "&&$string&&";
	}
	
	/**
	 * Test the basic functionality of a "where" object using just the constructor.
	 * 
	 * @dataProvider basicWhereDataProvider
	 * @param array $args The arguments to instantiate the where object with.
	 * @param string $expected_sql The resulting SQL we expect to see.
	 * @param string $method The method to call on the where object.
	 * @return void
	 */
	public function testBasicWhere($args, $expected_sql, $method = '__toString')
	{
		$reflection_class = new ReflectionClass('OLP_DB_WhereCond');
		$where = $reflection_class->newInstanceArgs($args);
		$this->assertEquals(
			trim(strtolower($expected_sql)),
			trim(strtolower($where->$method()))
		);
	}
	
	/**
	 * Provides data for basic where test.
	 * @return array List of arrays.
	 */
	public static function basicWhereDataProvider()
	{
		return array(
			array(array('fielda', OLP_DB_WhereCond::GREATER_THAN, 1000), 'WHERE fielda > 1000'),
			array(array('fieldb', OLP_DB_WhereCond::EQUALS, 2, 'tableb'), 'WHERE tableb.fieldb = 2'),
			array(array('fieldc', OLP_DB_WhereCond::LIKE, '%something%'), "WHERE fieldc LIKE '%something%'"),
			array(array('fieldd', OLP_DB_WhereCond::LESS_THAN, 'abc'), "WHERE fieldd < 'abc'"),
			array(array('fielde', OLP_DB_WhereCond::REGEXP, '^start'), "WHERE fielde REGEXP '^start'"),
			
			// test that toWhere works the same as __toString
			array(array('fieldf', OLP_DB_WhereCond::LESS_THAN, 'abc'), "WHERE fieldf < 'abc'", 'toWhere'),
			array(array('fieldg', OLP_DB_WhereCond::REGEXP, '^start'), "WHERE fieldg REGEXP '^start'", 'toWhere'),
			
			// test the output of toSql fragments
			array(array('fieldh'), 'fieldh', 'toSql'),
			array(array('fieldi', OLP_DB_WhereCond::REGEXP, '^start'), "fieldi REGEXP '^start'", 'toSql'),
		);
	}
}

?>
