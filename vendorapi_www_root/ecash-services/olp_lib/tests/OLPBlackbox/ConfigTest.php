<?php

class OLPBlackbox_ConfigTest extends PHPUnit_Framework_TestCase
{
	
	public static function dataSourceProvider()
	{
		return array(
			array(true, OLPBlackbox_Config::DATA_SOURCE_BLACKBOX),
			array(true, OLPBlackbox_Config::DATA_SOURCE_CONFIG),
			array(true, OLPBlackbox_Config::DATA_SOURCE_STATE),
			array(false, "SOME_INVALID_SOURCE"),
		);
	}	
	/**
	 * 
	 * @dataProvider dataSourceProvider
	 */
	public function testIsValidDataSource($expected, $source)
	{
		$this->assertEquals($expected, OLPBlackbox_Config::isValidDataSource($source));
	}

}
