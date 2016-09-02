<?php
/**
 * Test the OLP_Data_Normalizer class.
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class OLP_Data_NormalizerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testing method testNormalize().
	 * @return array
	 */
	public static function dataProviderForNormalize()
	{
		return array(
			array(
				array('k1' => NULL, 'k2' => '()',         'k3' => '&);',     'k4' => '&#42;', 'k5' => "\n'\"<>()"),
				array('k1' => NULL, 'k2' => '&#40;&#41;', 'k3' => '&&#41;;', 'k4' => '&#42;', 'k5' => "\n&#40;&#41;"),
			),
			array(
				array('k1' => '(&#41;&#40;)'),
				array('k1' => '&#40;&#41;&#40;&#41;'),
			),
			array(
				array('vehicle_series' => '(())'),
				array('vehicle_series' => '(())'),
			),
			array(
				array('k1' => "\n\n", 'k2' => "\\\r", 'k3' => '\\\\'),
				array('k1' => '',     'k2' => "",     'k3' => '\\'),
			),
		);
	}

	/**
	 * Data provider for testing method testDeNormalize().
	 * @return array
	 */
	public static function dataProviderForDeNormalize()
	{
		return array(
			array(
				array(),
				array(),
			),
			array(
				array('k1' => NULL, 'k2' => '&#40;&#41;', 'k3' => '&&#41;;', 'k4' => '&#42;', 'k5' => "\n'\"<>()"),
				array('k1' => NULL, 'k2' => '()',         'k3' => '&);',     'k4' => '&#42;', 'k5' => "\n'\"<>()"),
			),
			array(
				array('k1' => '(&#41;&#40;)'),
				array('k1' => '()()'),
			),
			array(
				array('vehicle_make' => '(&#41;&#40;)'),
				array('vehicle_make' => '(&#41;&#40;)'),
			),
		);
	}

	/**
	 * Test method OLP_Data_Normalizer::normalize().
	 * 
	 * @dataProvider dataProviderForNormalize
	 * @param array $arr
	 * @param array $expected_result
	 * @return void
	 */
	public function testNormalize(array $arr, array $expected_result)
	{ 
		OLP_Data_Normalizer::normalize($arr);
		$this->assertSame($arr, $expected_result);
	}

	/**
	 * Test method OLP_Data_Normalizer::deNormalize().
	 * 
	 * @dataProvider dataProviderForDeNormalize
	 * @param array $arr
	 * @param array $expected_result
	 * @return void
	 */
	public function testDeNormalize(array $arr, array $expected_result)
	{
		OLP_Data_Normalizer::deNormalize($arr);
		$this->assertSame($arr, $expected_result);
	}
}
