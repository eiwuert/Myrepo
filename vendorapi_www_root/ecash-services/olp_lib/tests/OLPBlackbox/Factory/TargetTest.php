<?php
/**
 * Tests for the OLPBlackbox_Factory_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Factory_TargetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for the testGetEnterpriseFactoryClassName() test
	 *
	 * @return array
	 */
	public static function dpGetEnterpriseFactoryClassName()
	{
		return array(
			array('IMPACT', 'OLPBlackbox_Enterprise_Generic_Factory_Target'),
			array('QEASY', 'OLPBlackbox_Enterprise_Generic_Factory_Target'),
			array('CLK', 'OLPBlackbox_Enterprise_CLK_Factory_Target')
		);
	}
	
	/**
	 * Tests that we get back the appropriate class
	 *
	 * @dataProvider dpGetEnterpriseFactoryClassName
	 * @param string $company
	 * @param string $expected
	 * @return void
	 */
	public function testGetEnterpriseFactoryClassName($company, $expected)
	{
		$factory = new OLPBlackbox_Factory_Target();
		
		$this->assertEquals($expected, $factory->getEnterpriseFactoryClassName('Target', $company));
	}
}
