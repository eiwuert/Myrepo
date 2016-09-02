<?php

/**
 * Tests that the ConstantDataSource class works properly.
 *
 * The class being tested here is one to present campaign constants as an iterable
 * data source, which essentially just means picking out particular key/values
 * from an associative array.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 */
class LenderAPI_ConstantDataSourceTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests making a LenderAPI from different data sources.
	 * @dataProvider constructionProvider
	 * @param array|Traversable $data Data which would come from the target data
	 * table in blackbox admin.
	 * @param bool $exception Whether the data should cause an exception when 
	 * fed to the constructor of the data source.
	 * @param bool $unset_mode Whether to unset the keys from $data when constructed.
	 * @return void
	 */
	public function testConstruction($data, $exception, $unset_mode = FALSE)
	{
		try
		{
			new LenderAPI_ConstantDataSource($data, $unset_mode);
			$this->assertFalse(
				$exception, 'An exception was not thrown but was required.'
			);
			
			if ($unset_mode)
			{
				try
				{
					new LenderAPI_ConstantDataSource($data);
					$this->assertTrue(
						FALSE, "The first construction of a ConstantDataSource
						did not properly strip out campaign constant entries!"
					);
				}
				catch (InvalidArgumentException $e)
				{
					$this->assertTrue(
						TRUE, "This exception cannot fail, but is meant to verify
						that all the campaign constant items have been stripped
						out of the \$data when unset_mode was true in the first
						ConstantDataSource construction."
					);
				}
			}
		}
		catch (InvalidArgumentException $e)
		{
			$this->assertTrue(
				$exception, 'An exception was thrown parsing the data but was not expected.'
			);
		}
	}

	/**
	 * Tests that invalid XML identifiers get changed into something valid.
	 * @return void
	 */
	public function testIteration()
	{
		$value = 'a value for the weird key';
		$data = array(
			'vendor_api_constant_name_1' => 'a weird #*@ key',
			'vendor_api_constant_value_1' => $value,
		);
		$source = new LenderAPI_ConstantDataSource($data);
		foreach ($source as $key => $val)
		{
			$this->assertEquals('a_weird__key', $key);
			$this->assertEquals($val, $value);
		}
	}
	
	// --------------- data providers ----------------
	
	/**
	 * Data provider to test the construction of a ConstantDataSource class.
	 * @see testConstruction
	 * @return array
	 */
	public static function constructionProvider()
	{
		$valid_data = array(
			'vendor_api_constant_name_1' => 'username',
			'vendor_api_constant_value_1' => 'sellingsource1',
			'other_key' => 'useless info',
		);
		$valid_iterator = new ArrayObject(array(
			'vendor_api_constant_name_1' => 'username',
			'vendor_api_constant_value_1' => 'sellingsource1',
			'other_key' => 'useless info',
		));
		$invalid_data = array(
			'contains_no_keys' => 'that are valid',
		);
		$more_invalid_data = array(
			'contains_empty_keys' => 'which should not be sent',
			'vendor_api_constant_name_1' => '',
			'vendor_api_constant_value_1' => NULL,
		);
		return array(
			array($valid_data, FALSE, TRUE),
			array($valid_iterator, FALSE, TRUE),
			array($invalid_data, TRUE),
			array($more_invalid_data, TRUE),
		);
	}
}
?>
