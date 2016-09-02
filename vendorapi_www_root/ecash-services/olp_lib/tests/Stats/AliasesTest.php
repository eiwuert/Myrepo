<?php
/** Test case for Stats_Aliases.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class Stats_AliasesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test with an alias that exists
	 *
	 * @return void
	 */
	public function testHasAlias()
	{
		$before = 'prequal';
		$expected = array('prequal','base')	;
		$after = Stats_Aliases::getAliases($before);
		
		$this->assertEquals($expected, $after);
	}

	/**
	 * Test with an alias that does not exists
	 *
	 * @return void
	 */
	public function testNoAlias()
	{
		$before = 'testmexxx';
		$expected = array('testmexxx')	;
		$after = Stats_Aliases::getAliases($before);
		
		$this->assertEquals($expected, $after);
	}
}

?>
