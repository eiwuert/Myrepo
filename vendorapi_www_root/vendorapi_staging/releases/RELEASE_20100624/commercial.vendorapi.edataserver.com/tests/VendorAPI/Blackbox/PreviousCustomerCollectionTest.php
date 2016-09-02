<?php

// PHPUnit apparently still includes the bootstrap file at different times
// depending upon whether it's specified on the command line (includes it
// AFTER executing the dataProvider, which breaks this test) or the
// configuration file... this allows us to run this test individually
require_once 'bootstrap.php';

/** Tests previous customer collection.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class VendorAPI_Blackbox_PreviousCustomerCollectionTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->markTestSkipped('Previous Customer Collections are no longer used.');
	}
	/** Provider for testGetExpireApplications().
	 *
	 * @return array
	 */
	public static function dataProviderGetExpireApplications()
	{
		return array(
			array(
				FALSE,
				FALSE,
			),
			array(
				TRUE,
				TRUE,
			),
		);
	}
	
	/** Tests getExpireApplications().
	 *
	 * @dataProvider dataProviderGetExpireApplications
	 *
	 * @param bool $expire_apps
	 * @param bool $expected_result
	 * @return void
	 */
	public function testGetExpireApplications($expire_apps, $expected_result)
	{
		$collection = new VendorAPI_Blackbox_PreviousCustomerCollection($expire_apps);
		$result = $collection->getExpireApplications();
		
		$this->assertEquals($expected_result, $result);
	}
	
	/** Provider for testAddRulePass().
	 *
	 * @return array
	 */
	public static function dataProviderAddRulePass()
	{
		return array(
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhone'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_EmailSSN'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_EmailDob'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountSsn'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountDob'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_License'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_SSN'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhoneDob'),
			array('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccount'),
		);
	}
	
	/** Tests addRule() by adding valid rules.
	 *
	 * @dataProvider dataProviderAddRulePass
	 *
	 * @param string $rule_class_name
	 * @return void
	 */
	public function testAddRulePass($rule_class_name)
	{
		$collection = new VendorAPI_Blackbox_PreviousCustomerCollection();
		
		$rule = $this->getMock(
			$rule_class_name,
			array(),
			array(),
			'',
			FALSE
		);
		$collection->addRule($rule);
	}
	
	/** Provider for testAddRuleFail().
	 *
	 * @return array
	 */
	public static function dataProviderAddRuleFail()
	{
		return array(
			array('Blackbox_Rule_Compare'),
			array('Blackbox_Rule_Required'),
		);
	}
	
	/** Tests addRule() by adding invalid rules.
	 *
	 * @dataProvider dataProviderAddRuleFail
	 * @expectedException Blackbox_Exception
	 *
	 * @param string $rule_class_name
	 * @return void
	 */
	public function testAddRuleFail($rule_class_name)
	{
		$collection = new VendorAPI_Blackbox_PreviousCustomerCollection();
		
		$rule = $this->getMock(
			$rule_class_name,
			array(),
			array(),
			'',
			FALSE
		);
		$collection->addRule($rule);
	}
	
	/** Provider for testIsValidFail().
	 *
	 * @return array
	 */
	public static function dataProviderIsValidFail()
	{
		return array(
			array(NULL),
			array(TRUE),
		);
	}
	
	/** Tests isValid() by giving it an incorrect state data.
	 *
	 * @dataProvider dataProviderIsValidFail
	 * @expectedException Blackbox_Exception
	 *
	 * @param mixed $customer_history
	 * @return void
	 */
	public function testIsValidFail($customer_history)
	{
		$collection = new VendorAPI_Blackbox_PreviousCustomerCollection();
		$data = new VendorAPI_Blackbox_Data();
		$state_data = new VendorAPI_Blackbox_StateData(array('customer_history' => $customer_history));
		
		$collection->isValid($data, $state_data);
	}
	
	/** Provider for testIsValidPass().
	 *
	 * @return array
	 */
	public static function dataProviderIsValidPass()
	{
		// The rule is completely mocked, but still needs to be a valid
		// instance of VendorAPI_Blackbox_Rule_PreviousCustomer.
		$default_rule_name = 'VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhone';
		
		return array(
			array(
				array(),
				TRUE,
				'No rules, should pass.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => FALSE,
					),
				),
				TRUE,
				'One single rule that passes.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => FALSE,
						'getResult' => FALSE,
					),
				),
				FALSE,
				'One single rule that fails.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => FALSE,
					),
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => FALSE,
					),
				),
				TRUE,
				'Two rules that pass, both must be called.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => FALSE,
						'getResult' => FALSE,
					),
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => FALSE,
					),
				),
				FALSE,
				'Testing shortcutting of rules, first rule fails, so the second never rules.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => TRUE,
					),
				),
				TRUE,
				'Verify that getResult and setResult get called.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => TRUE,
					),
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => FALSE,
					),
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => TRUE,
					),
				),
				TRUE,
				'Testing get/setResult with multiple rule calls, but not all with decisions.',
			),
			array(
				array(
					array(
						'rule' => $default_rule_name,
						'isValid' => FALSE,
						'getResult' => TRUE,
					),
					array(
						'rule' => $default_rule_name,
						'isValid' => TRUE,
						'getResult' => TRUE,
					),
				),
				FALSE,
				'Shortcutting will not call the second rule\'s getResult.',
			),
		);
	}
	
	/** Tests isValid() by giving it a correct state data. Yes, this is a
	 * horrible blob of mocks, but this allows us to test every condition of
	 * is valid without requiring other classes to work correctly. Every
	 * external call is mocked, with the required number of times called
	 * estimated.
	 *
	 * @dataProvider dataProviderIsValidPass
	 *
	 * @param mixed $customer_history
	 * @return void
	 */
	public function testIsValidPass(array $rules, $expected_result, $message)
	{
		// Gets the collection.
		$collection = new VendorAPI_Blackbox_PreviousCustomerCollection();
		
		// Add rules to the collection.
		$set_result_count = 0;
		$will_be_valid = TRUE;
		foreach ($rules AS $rule)
		{
			// Mock the decision.
			$mocked_result = $this->getMock(
				'VendorAPI_Blackbox_Generic_Decision',
				array('getDecision'),
				array(),
				'',
				FALSE
			);
			$mocked_result->expects($rule['getResult'] && $will_be_valid ? $this->once() : $this->never())
				->method('getDecision')
				->will($this->returnValue(NULL));
			
			// Mock the rule
			$mocked_rule = $this->getMock(
				$rule['rule'],
				array(
					'isValid',
					'getResult',
					'getName',
				),
				array(),
				'',
				FALSE
			);
			$mocked_rule->expects($will_be_valid ? $this->once() : $this->never())
				->method('isValid')
				->will($this->returnValue($rule['isValid']));
			$mocked_rule->expects($will_be_valid ? $this->once() : $this->never())
				->method('getResult')
				->will($this->returnValue($rule['getResult'] ? $mocked_result : FALSE));
			
			// Add the mocked rule to the collection
			$collection->addRule($mocked_rule);
			
			// For each valid rule, certain calls get called more often.
			if ($will_be_valid)
			{
				if ($rule['getResult'])
				{
					$set_result_count++;
				}
				
				if (!$rule['isValid'])
				{
					$will_be_valid = FALSE;
				}
			}
		}
		
		// Setup data, which doesn't need anything because all rules should be mocked.
		$data = new VendorAPI_Blackbox_Data();
		
		// Setup customer history for state data.
		$customer_history = $this->getMock(
			'ECash_CustomerHistory',
			array('setResult')
		);
		$customer_history->expects($this->exactly($set_result_count))
			->method('setResult');
		$state_data = new VendorAPI_Blackbox_StateData(array('customer_history' => $customer_history));
		
		// Run the test now.
		$result = $collection->isValid($data, $state_data);
		
		$this->assertEquals($expected_result, $result, $message);
	}
}

?>