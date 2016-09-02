<?php
/**
 * Test the LenderAPI_BlackboxDataSource_Ref04NameLast class to see if we retrieve reference's last name properly.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 * @subpackage LenderAPI
 */
class LenderAPI_BlackboxDataSource_Ref04NameLastTest extends PHPUnit_Framework_TestCase
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
			array("{$first_name}", NULL),
			array(" {$first_name}", NULL),
			array("{$first_name} ", NULL),
			array("{$first_name} {$last_name}", "{$last_name}"),
			array(" {$first_name} {$last_name}", "{$last_name}"),
			array("{$first_name} {$last_name} ", "{$last_name}"),
			array(" {$first_name} {$last_name} ", "{$last_name}"),
		);		
	}

	/**
	 * Tests LenderAPI_BlackboxDataSource_Ref04NameLast->value()
	 * @dataProvider dataProvider
	 * @param string $name_full
	 * @param string $expected_last_name
	 * @return void
	 */
	public function testValue($name_full, $expected_last_name)
	{
		$obj_blackbox_data = new OLPBlackbox_Data();
		$obj_blackbox_data['ref_04_name_full'] = $name_full;
		
		$obj_ref_04_name_last = new LenderAPI_BlackboxDataSource_Ref04NameLast($obj_blackbox_data);
		$this->assertSame($expected_last_name, $obj_ref_04_name_last->value());
	}
}
