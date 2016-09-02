<?php

/** Tests VendorAPI Blackbox Data.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Blackbox_DataTest extends PHPUnit_Framework_TestCase
{
	/** Provider for testDataValid().
	 *
	 * @return array
	 */
	public static function dataProviderValid()
	{
		return array(
			array('target'),
		);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderValid
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataValid($variable_name)
	{
		$value = md5($variable_name);

		$blackbox_data = new VendorAPI_Blackbox_Data();

		$blackbox_data->$variable_name = $value;

		$this->assertEquals($value, $blackbox_data->$variable_name);
	}

	/** Provider for testDataInvalid().
	 *
	 * @return array
	 */
	public static function dataProviderInvalid()
	{
		$variable_names = array(
			'invalid_variable',
			'another invalid variable',
		);

		$data = array();
		foreach ($variable_names AS $variable_name)
		{
			$data[] = array($variable_name);
		}

		return $data;
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderInvalid
	 * @expectedException Blackbox_Exception
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataInvalid($variable_name)
	{
		$value = md5($variable_name);

		$blackbox_data = new VendorAPI_Blackbox_Data();

		$blackbox_data->$variable_name = $value;
	}
	
	public function testDataLoadFromDataBankAccount()
	{
		$var_name 	= "bank_account";
		$var_value 	= 123456789;
		
		$data[$var_name] = $var_value;
		
		$blackbox_data = new VendorAPI_Blackbox_Data();
		
		$blackbox_data->$var_name = $var_value;		
		
		$blackbox_data->loadFrom($data);

		$this->assertEquals($var_value, $blackbox_data->permutated_bank_account[0]);
		$this->assertEquals("0".$var_value, $blackbox_data->permutated_bank_account[1]);
		$this->assertEquals("00".$var_value, $blackbox_data->permutated_bank_account[2]);
		$this->assertEquals("000".$var_value, $blackbox_data->permutated_bank_account[3]);
		$this->assertEquals("0000".$var_value, $blackbox_data->permutated_bank_account[4]);
		$this->assertEquals("00000".$var_value, $blackbox_data->permutated_bank_account[5]);
		$this->assertEquals("000000".$var_value, $blackbox_data->permutated_bank_account[6]);
		$this->assertEquals("0000000".$var_value, $blackbox_data->permutated_bank_account[7]);
		$this->assertEquals("00000000".$var_value, $blackbox_data->permutated_bank_account[8]);	
		$this->assertEquals(strlen($var_value), count($blackbox_data->permutated_bank_account));
	}	

	
}

?>