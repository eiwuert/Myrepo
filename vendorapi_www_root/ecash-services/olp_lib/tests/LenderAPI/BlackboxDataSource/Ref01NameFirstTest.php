<?php
/**
 * Test the LenderAPI_BlackboxDataSource_Ref01NameFirst class to see if we retrieve reference's first name properly.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 * @subpackage LenderAPI
 */
class LenderAPI_BlackboxDataSource_Ref01NameFirstTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testing method testValue().
	 * @return array
	 */
	public static function dataProvider()
	{
		$first_name = 'fIrStNaMe';
		$last_name = 'LaStNaMe';

		return array(
			array(NULL, NULL),
			array("{$first_name}", "{$first_name}"),
			array(" {$first_name}", "{$first_name}"),
			array("{$first_name} ", "{$first_name}"),
			array("{$first_name} {$last_name}", "{$first_name}"),
			array(" {$first_name} {$last_name}", "{$first_name}"),
			array("{$first_name} {$last_name} ", "{$first_name}"),
			array(" {$first_name} {$last_name} ", "{$first_name}"),
		);		
	}

	/**
	 * Tests LenderAPI_BlackboxDataSource_Ref01NameFirst->value()
	 * @dataProvider dataProvider
	 * @param string $name_full
	 * @param string $expected_first_name
	 * @return void
	 */
	public function testValue($name_full, $expected_first_name)
	{
		$obj_blackbox_data = new OLPBlackbox_Data();
		$obj_blackbox_data['ref_01_name_full'] = $name_full;
		
		$obj_ref_01_name_first = new LenderAPI_BlackboxDataSource_Ref01NameFirst($obj_blackbox_data);
		$this->assertSame($expected_first_name, $obj_ref_01_name_first->value());
	}
}
