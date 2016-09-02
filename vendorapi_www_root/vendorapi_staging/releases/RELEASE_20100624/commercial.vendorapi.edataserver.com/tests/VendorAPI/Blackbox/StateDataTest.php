<?php

/** Tests VendorAPI Blackbox StateData.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Blackbox_StateDataTest extends PHPUnit_Framework_TestCase
{
	/** Provider for testDataValid().
	 *
	 * @return array
	 */
	public static function dataProviderMutable()
	{
		return array(
			array('is_react'),
		);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderMutable
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataInitData($variable_name)
	{
		$value = md5($variable_name);

		$init_data = array(
			$variable_name => $value,
		);

		$blackbox_statedata = new VendorAPI_Blackbox_StateData($init_data);

		$this->assertEquals($value, $blackbox_statedata->$variable_name);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderMutable
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataValid($variable_name)
	{
		$value = md5($variable_name);

		$blackbox_statedata = new VendorAPI_Blackbox_StateData();

		$blackbox_statedata->$variable_name = $value;

		$this->assertEquals($value, $blackbox_statedata->$variable_name);
	}

	/** Provider for testDataImmutableValid() and testDataImmutableInvalid().
	 *
	 * @return array
	 */
	public static function dataProviderImmutable()
	{
		return array(
			//array('customer_history'),
		);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderImmutable
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataImmutableValid($variable_name)
	{
		$value = md5($variable_name);

		$init_data = array(
			$variable_name => $value,
		);

		$blackbox_statedata = new VendorAPI_Blackbox_StateData($init_data);

		$this->assertEquals($value, $blackbox_statedata->$variable_name);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderImmutable
	 * @expectedException InvalidArgumentException
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataImmutableInvalid($variable_name)
	{
		$value = md5($variable_name);

		$blackbox_statedata = new VendorAPI_Blackbox_StateData();

		$blackbox_statedata->$variable_name = $value;
	}

	/** Provider for testDataInvalid().
	 *
	 * @return array
	 */
	public static function dataProviderInvalid()
	{
		return array(
			array('invalid_variable'),
			array('another invalid variable'),
		);
	}

	/** Tests that required fields are setable.
	 *
	 * @dataProvider dataProviderInvalid
	 * @expectedException InvalidArgumentException
	 *
	 * @param string $variable_name
	 * @return void
	 */
	public function testDataInvalid($variable_name)
	{
		$value = md5($variable_name);

		$blackbox_statedata = new VendorAPI_Blackbox_StateData();

		$blackbox_statedata->$variable_name = $value;
	}
}

?>