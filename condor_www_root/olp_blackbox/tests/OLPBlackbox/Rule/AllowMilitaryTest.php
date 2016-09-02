<?php
/**
 * AllowMilitaryTest PHPUnit test file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_AllowMilitary class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_AllowMilitaryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for the test cases we want to run..
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		// Expected Return, Allow Military, Customer Email, Military Flag Value
		return array(
			array(TRUE, 'ALLOW', 'test@blah.mil', 'TRUE'), // Allow military email and military flag
			array(TRUE, 'ALLOW', 'test@blah.mil', 'FALSE'), // Allow military email only
			array(TRUE, 'ALLOW', 'test@blah.mil', ''), // Allow military email only even with no flag
			array(TRUE, 'ALLOW', 'test@blah.com', 'TRUE'), // Allow military flag only
			array(TRUE, 'ALLOW', 'test@blah.com', 'FALSE'), // Allow no military email or flag
			array(TRUE, 'ALLOW', 'test@blah.com', ''), // Allow no military email or flag

			array(TRUE, 'DENY', 'test@blah.com', 'FALSE'), // Not military, dont deny

			array(TRUE, 'ONLY', 'test@blah.mil', 'TRUE'), // Allow military email and military flag
			array(TRUE, 'ONLY', 'test@blah.mil', 'FALSE'), // Allow military email only
			array(TRUE, 'ONLY', 'test@blah.mil', ''), // Allow military email only even with no flag
			array(TRUE, 'ONLY', 'test@blah.com', 'TRUE'), // Allow military flag only


			array(FALSE, 'DENY', 'test@blah.mil', 'TRUE'), // Deny military email and military flag
			array(FALSE, 'DENY', 'test@blah.mil', 'FALSE'), // Deny military email only
			array(FALSE, 'DENY', 'test@blah.com', 'TRUE'), // Deny military flag only
			array(FALSE, 'DENY', 'test@blah.com', ''), // Deny if flag is not set
			array(FALSE, 'DENY', 'test@blah.com', 'n/a'), // Deny if flag is n/a
			array(FALSE, 'DENY', 'test@blah.com', 'blah'), // Deny if flag is anything other then TRUE

			array(FALSE, 'ONLY', 'test@blah.com', 'FALSE'), // Fail on non-military
			array(FALSE, 'ONLY', 'test@blah.com', ''), // Fail if flag is not set
			array(FALSE, 'ONLY', 'test@blah.com', 'n/a'), // Fail if flag is n/a
			array(FALSE, 'ONLY', 'test@blah.com', 'blah'), // Fail if flag is anything other then TRUE
		);
	}

	/**
	 * Run all of our test cases to make sure the different data combinations
	 * return the expected result.
	 *
	 * @param bool $expected The expected result of the test
	 * @param string $mode The mode to test
	 * @param string $email The customers email address
	 * @param string $military The customers military flag value
	 *
	 * @return void
	 *
	 * @dataProvider dataProvider
	 */
	public function testAllowMilitary($expected, $mode, $email, $military)
	{
		$data = new OLPBlackbox_Data();
		$data->email_primary = $email;
		$data->military = $military;
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_AllowMilitary',
			array('hitStat', 'hitEvent')
		);
		$rule->setupRule(array(
			Blackbox_StandardRule::PARAM_FIELD => array('email_primary','military'),
			Blackbox_StandardRule::PARAM_VALUE => $mode,
		));

		$v = $rule->isValid($data, $state_data);

		if ($expected)
		{
			$this->assertTrue($v);
		}
		else
		{
			$this->assertFalse($v);
		}
	}

}
?>
