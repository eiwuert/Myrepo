<?php
class OLP_UtilTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Some unit tests require us to be on a little-endian machine. If we are
	 * not, set this to false.
	 */
	const ARE_WE_LITTLE_ENDIAN = TRUE;
	
	/**
	 * Data provider for testCalculatePayPeriodNet() method.
	 *
	 * @return array
	 */
	public static function dpTestCalculatePayPeriodNet()
	{
		return array(
			array(OLP_Util::PAY_PERIOD_WEEKLY, 3000, 692),
			array(OLP_Util::PAY_PERIOD_BI_WEEKLY, 3000, 1385),
			array(OLP_Util::PAY_PERIOD_FOUR_WEEKLY, 3000, 2769),
			array(OLP_Util::PAY_PERIOD_TWICE_MONTHLY, 3000, 1500),
			array(OLP_Util::PAY_PERIOD_MONTHLY, 3000, 3000)
		);
	}
	
	/**
	 * Tests various combinations of the calculatePayPeriodNet() method.
	 *
	 * @dataProvider dpTestCalculatePayPeriodNet
	 * @param string $pay_period
	 * @param float $monthly_net
	 * @param float $expected_result
	 */
	public function testCalculatePayPeriodNet($pay_period, $monthly_net, $expected_result)
	{
		$this->assertEquals($expected_result, OLP_Util::calculatePayPeriodNet($pay_period, $monthly_net));
	}
	
	/**
	 * Tests that we get an InvalidArgumentException if we provide an invalid pay period.
	 *
	 * @return void
	 */
	public function testCalculatePayPeriodNetException()
	{
		$this->setExpectedException('InvalidArgumentException', 'pay period \'foobar\' is not valid');
		OLP_Util::calculatePayPeriodNet('foobar', 6999);
	}
	
	/**
	 * Data provider for testDataMap().
	 *
	 * @return array
	 */
	public static function dataProviderDataMap()
	{
		return array(
			array(
				array(),
				array(),
				array(),
				array(),
				'No data, no actions, no result.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(),
				array(),
				array(),
				'Data, no actions, no result.',
			),
			
			array(
				array(),
				array(
					'ssn' => 'social_security_number',
				),
				array(),
				array(),
				'No data, an action, no result.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => 'social_security_number',
				),
				array(
					'social_security_number' => '012345678',
				),
				array(
					'social_security_number' => '012345678',
				),
				'Simple mapping test.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => 'social_security_number',
					'social_security_number' => 'ssn',
				),
				array(
					'social_security_number' => '012345678',
				),
				array(
					'social_security_number' => '012345678',
					'ssn' => '012345678',
				),
				'Simple mapping test that can use recursion.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => array(
						'social_security_number',
						'ssn',
					),
				),
				array(
					'social_security_number' => '012345678',
					'ssn' => '012345678',
				),
				array(
					'social_security_number' => '012345678',
					'ssn' => '012345678',
				),
				'An array of actions applied to a key.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => '/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
				),
				array(
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				array(
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				'Applying a regular expression with named subpatterns.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'social_security_number' => '/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
					'ssn' => 'social_security_number',
				),
				array(
					'social_security_number' => '012345678',
				),
				array(
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				'Applying a regular expression with named subpatterns and recursion.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => array(
						'social_security_number',
						'/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
					),
				),
				array(
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				array(
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				'Using multiple actions, including a regular expression, on one key.',
			),
			
			array(
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => array(
						'social_security_number',
						'/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
					),
					'social_security_number' => array(
						'ssn',
						'/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
					),
				),
				array(
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				array(
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
					'ssn' => '012345678',
				),
				'Using multiple actions, including a regular expression, on one key and testing that recusion escapes.',
			),
			
			array(
				array(
					'letter1' => 'a',
					'letter2' => 'b',
				),
				array(
					'letter1' => 'letter2',
					'letter2' => 'letter1',
				),
				array(
					'letter1' => 'b',
					'letter2' => 'a',
				),
				NULL,
				'Testing a recursive action (swapping two variables) that will never stablize.',
			),
			
			array(
				array(
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				array(
					'@%%%ssn_part_1%%%%%%ssn_part_2%%%%%%ssn_part_3%%%' => 'ssn',
				),
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => '012345678',
				),
				'Testing building a value from multiple values.',
			),
			
			array(
				array(
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
				),
				array(
					'@%%%ssn_part_1%%%%%%ssn_part_2%%%%%%ssn_part_3%%%' => 'ssn',
					'ssn' => array(
						'social_security_number',
						'/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
					),
				),
				array(
					'ssn' => '012345678',
				),
				array(
					'ssn' => '012345678',
					'social_security_number' => '012345678',
					'ssn_part_1' => '012',
					'ssn_part_2' => '34',
					'ssn_part_3' => '5678',
 				),
				'Testing building a value from multiple values. Recursion is required to stablize from data source.',
			),
			
			array(
				array(
					'first' => 'this is first',
					'second' => 'this is second',
				),
				array(
					'first' => 'unit',
					'second' => 'unit',
				),
				array(
					'unit' => 'this is second',
				),
				array(
					'unit' => 'this is second',
				),
				'Testing that we overwrite with the last value found.',
			),
		);
	}
	
	/**
	 * Tests dataMap().
	 *
	 * @dataProvider dataProviderDataMap
	 *
	 * @param array $source_data
	 * @param array $data_map
	 * @param array $expected_data
	 * @param array|NULL $expected_recursive_data
	 * @param string $message
	 */
	public function testDataMap(array $source_data, array $data_map, array $expected_data, $expected_recursive_data, $message)
	{
		$data = OLP_Util::dataMap($source_data, $data_map);
		$this->assertEquals($expected_data, $data, 'Non-recursive: ' . $message);
		
		if ($expected_recursive_data === NULL)
		{
			$this->setExpectedException('Exception');
		}
		
		$data = OLP_Util::dataMap($source_data, $data_map, TRUE);
		$this->assertEquals($expected_recursive_data, $data, 'Recursive: ' .  $message);
	}
	
	/**
	 * Simple test for getEndian().
	 *
	 * @return void
	 */
	public function testGetEndian()
	{
		if (!self::ARE_WE_LITTLE_ENDIAN) $this->markTestSkipped();
		
		$this->assertEquals(OLP_Util::LITTLE_ENDIAN, OLP_Util::getEndian());
	}
	
	/**
	 * Data privoder for testNormalizeLineEndings().
	 *
	 * @return array
	 */
	public static function dataProviderNormalizeLineEndings()
	{
		return array(
			array(
				'',
				'',
			),
			
			array(
				'test string',
				'test string',
			),
			
			array(
				"linux\nstring",
				'linux' . PHP_EOL . 'string',
			),
			
			array(
				"mac\rstring",
				'mac' . PHP_EOL . 'string',
			),
			
			array(
				"windows\r\nstring",
				'windows' . PHP_EOL . 'string',
			),
			
			array(
				"crazy\n\rstring",
				'crazy' . PHP_EOL . 'string',
			),
			
			array(
				"no\ridea\nwhat\r\nis\n\rhappening\r\r\n\rhere\n\n",
				'no' . PHP_EOL . 'idea' . PHP_EOL . 'what' . PHP_EOL . 'is' . PHP_EOL . 'happening' . PHP_EOL . PHP_EOL . PHP_EOL . 'here' . PHP_EOL . PHP_EOL,
			),
		);
	}
	
	/**
	 * Tests normalizeLineEndings().
	 *
	 * @dataProvider dataProviderNormalizeLineEndings
	 *
	 * @return void
	 */
	public function testNormalizeLineEndings($input, $expected_result)
	{
		$output = OLP_Util::normalizeLineEndings($input);
		
		$this->assertEquals($expected_result, $output);
	}
	
	/**
	 * Data privoder for testNormalizeNonPrintable().
	 *
	 * @return array
	 */
	public static function dataProviderNormalizeNonPrintable()
	{
		return array(
			array(
				'',
				'',
			),
			
			array(
				'test string',
				'test string',
			),
			
			array(
				chr(0x00) . chr(0xFF),
				'',
			),
			
			array(
				chr(0x00) . 'a' . chr(0xFF),
				'a',
			),
			
			array(
				chr(0x00) . "a\n" . chr(0xFF) . 'b',
				"a\nb",
			),
		);
	}
	
	/**
	 * Tests normalizeNonPrintable().
	 *
	 * @dataProvider dataProviderNormalizeNonPrintable
	 *
	 * @return void
	 */
	public function testNormalizeNonPrintable($input, $expected_result)
	{
		$output = OLP_Util::normalizeNonPrintable($input);
		
		$this->assertEquals($expected_result, $output);
	}
	
	/**
	 * Data privoder for testNormalizeUTF16().
	 *
	 * @return array
	 */
	public static function dataProviderNormalizeUTF16()
	{
		return array(
			array(
				'',
				'',
			),
			
			array(
				'test string',
				'test string',
			),
			
			array(
				chr(0xFF) . chr(0xFE),
				'',
			),
			
			array(
				chr(0xFE) . chr(0xFF),
				'',
			),
			
			array(
				chr(0xFF) . chr(0xFF),
				chr(0xFF) . chr(0xFF),
			),
			
			array(
				chr(0xFE) . chr(0xFE),
				chr(0xFE) . chr(0xFE),
			),
			
			array(
				chr(0xFF) . chr(0xFE) . chr(0x40) . chr(0x00),
				'@',
			),
			
			array(
				chr(0xFE) . chr(0xFF) . chr(0x00) . chr(0x40),
				'@',
			),
			
			array(
				chr(0xFF) . chr(0xFE) .
				chr(0x40) . chr(0x00) .
				chr(0x48) . chr(0x00),
				'@H',
			),
			
			array(
				chr(0xFE) . chr(0xFF) .
				chr(0x00) . chr(0x40) .
				chr(0x00) . chr(0x48),
				'@H',
			),
		);
	}
	
	/**
	 * Tests normalizeUTF16().
	 *
	 * @dataProvider dataProviderNormalizeUTF16
	 *
	 * @return void
	 */
	public function testNormalizeUTF16($input, $expected_result)
	{
		if (!self::ARE_WE_LITTLE_ENDIAN) $this->markTestSkipped();
		
		$output = OLP_Util::normalizeUTF16($input);
		
		$this->assertEquals($expected_result, $output);
	}
	
	/**
	 * Data privoder for testNormalizeString().
	 *
	 * @return array
	 */
	public static function dataProviderNormalizeString()
	{
		return array(
			array(
				'',
				'',
			),
			
			array(
				'test string',
				'test string',
			),
			
			array(
				chr(0xFF) . chr(0xFE),
				'',
			),
			
			array(
				chr(0xFE) . chr(0xFF),
				'',
			),
			
			array(
				chr(0xFF) . chr(0xFF),
				'',
			),
			
			array(
				chr(0xFE) . chr(0xFE),
				'',
			),
			
			array(
				chr(0xFF) . chr(0xFE) . chr(0x40) . chr(0x00),
				'@',
			),
			
			array(
				chr(0xFE) . chr(0xFF) . chr(0x00) . chr(0x40),
				'@',
			),
			
			array(
				chr(0xFF) . chr(0xFE) .
				chr(0x40) . chr(0x00) .
				chr(0x48) . chr(0x00),
				'@H',
			),
			
			array(
				chr(0xFE) . chr(0xFF) .
				chr(0x00) . chr(0x40) .
				chr(0x00) . chr(0x48),
				'@H',
			),
			
			array(
				"linux\nstring",
				'linux' . PHP_EOL . 'string',
			),
			
			array(
				"mac\rstring",
				'mac' . PHP_EOL . 'string',
			),
			
			array(
				"windows\r\nstring",
				'windows' . PHP_EOL . 'string',
			),
			
			array(
				"crazy\n\rstring",
				'crazy' . PHP_EOL . 'string',
			),
			
			array(
				"no\ridea\nwhat\r\nis\n\rhappening\r\r\n\rhere\n\n",
				'no' . PHP_EOL . 'idea' . PHP_EOL . 'what' . PHP_EOL . 'is' . PHP_EOL . 'happening' . PHP_EOL . PHP_EOL . PHP_EOL . 'here' . PHP_EOL . PHP_EOL,
			),
			
			array(
				chr(0xFE) . chr(0xFF) .
				chr(0x00) . chr(0x40) .
				chr(0x00) . chr(0x0A) .
				chr(0x00) . chr(0x4C),
				'@' . PHP_EOL . 'L',
			),
			
			array(
				chr(0xFE) . chr(0xFF) .
				chr(0x00) . chr(0x40) .
				chr(0x00) . chr(0x0A) .
				chr(0x00) . chr(0x0D) .
				chr(0x00) . chr(0x4C),
				'@' . PHP_EOL . 'L',
			),
			
			array(
				chr(0xFF) . chr(0xFE) .
				chr(0x40) . chr(0x00) .
				chr(0x0D) . chr(0x00) .
				chr(0x0A) . chr(0x00) .
				chr(0x4C) . chr(0x00),
				'@' . PHP_EOL . 'L',
			),
		);
	}
	
	/**
	 * Tests normalizeString().
	 *
	 * @dataProvider dataProviderNormalizeString
	 *
	 * @return void
	 */
	public function testNormalizeString($input, $expected_result)
	{
		if (!self::ARE_WE_LITTLE_ENDIAN) $this->markTestSkipped();
		
		$output = OLP_Util::normalizeString($input);
		
		$this->assertEquals($expected_result, $output);
	}
}
