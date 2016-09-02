<?php

/**
 * Tests OLP_DB_And objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_AndTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Simply make sure that OLP_DB_And objects use the right glue.
	 *
	 * @return void
	 */
	public function testAssembly()
	{
		$a = 'a = 1';
		$b = 'b = 2';
		
		$and = new OLP_DB_And($a, $b);
		$glue = new OLP_DB_WhereGlue(OLP_DB_WhereGlue::AND_GLUE, array($a, $b));
		$this->assertEquals($and->toWhere(), $glue->toWhere());
	}
}

?>
