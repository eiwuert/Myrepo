<?php
/**
 * DenySoap PHPUnit test file.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_DenySoap class.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */
class OLPBlackbox_Rule_DenySoapTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Data provider for the OLPBlackbox_Rule_DenySoap test cases
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		// Expected Return from OLPBlackbox_Rule_DenySoap::isValid(), data->is_soap value
		$test_datae = array(
			array(TRUE, NULL), 
			array(TRUE, '0'),
			array(TRUE, ''),
			array(FALSE, '1'),
			array(FALSE, TRUE),
			array(FALSE, 'iamsoap'),
			array(FALSE, 'MARS'),
			array(TRUE, FALSE),
			);
		
		return array_merge($test_datae, $test_datae);
	}
	
	/**
	 * Run all of our test cases to make sure OLPBlackbox_Rule_DenySoap::isValid()
	 * returns the expected result.
	 *
	 * @param boolean $expected the expected pass/fail result
	 * @param mixed $soap_data_value a value theoretically from OLPBlackbox_Data()->is_soap
	 *
	 * @return void
	 *
	 * @dataProvider dataProvider
	 */
	public function testPassFail($expected, $soap_data_value)
	{
		$data = new OLPBlackbox_Data();
		$data->is_soap = $soap_data_value;
		
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_DenySoap',
			array('hitStat', 'hitEvent')
		);

		$is_valid = $rule->isValid($data, $state_data);

		if ($expected)
		{
			$this->assertTrue($is_valid);
		}
		else
		{
			$this->assertFalse($is_valid);
		}
	}
}
?>
