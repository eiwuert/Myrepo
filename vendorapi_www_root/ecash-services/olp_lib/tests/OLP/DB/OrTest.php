<?php

/**
 * Test OLP_DB_Or objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_OrTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Test that OLP_DB_Or objects use the right glue.
	 * @return void
	 */
	public function testAssembly()
	{
		$a = 'a = 1';
		$b = 'b = 2';
		
		$and = new OLP_DB_Or($a, $b);
		$glue = new OLP_DB_WhereGlue(OLP_DB_WhereGlue::OR_GLUE, array($a, $b));
		$this->assertEquals($and->toWhere(), $glue->toWhere());
	}
}

?>
