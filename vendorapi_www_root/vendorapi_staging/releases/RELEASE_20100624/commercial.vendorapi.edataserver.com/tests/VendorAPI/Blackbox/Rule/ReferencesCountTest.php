<?php

/**
 * PHPUnit test class for the VendorAPI_Blackbox_Rule_ReferencesCount class.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_ReferencesCountTest extends PHPUnit_Framework_TestCase
{
	protected $event_log = "";
	
	public function setUp()
	{
		$this->event_log = $this->getMock("VendorAPI_Blackbox_EventLog", array(), array(), '', FALSE);
	}
	
	/**
	 * Tests that the required_references rule runs correctly.
	 *
	 * @param array $data the the data values
	 * @param bool $expected the expected return from the rule
	 * @dataProvider dataProvider
	 * @return void
	 */
	public function testReqRefs($expected, $data_test, $ruleval)
	{
		$data = new VendorAPI_Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$data->personal_reference = $data_test;

		$rule = $this->getMock(
			'VendorAPI_Blackbox_Rule_ReferencesCount',
			array('hitStat', 'hitEvent'),
			array($this->event_log)
		);

		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'required_references',
				Blackbox_StandardRule::PARAM_VALUE => $ruleval
			)
		);

		$this->assertEquals($expected, $rule->isValid($data, $state_data));
	}
	
	/**
	 * Data provider for the test cases we want to run..
	 *
	 * @return array
	 */
	public static function dataProvider()
	{
		return array(
			array(TRUE, 
				array(
					array('name_full'=>'Biggle Otoole',  
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			array(TRUE,
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'1' //number of required references	for this test		
			), 
			//expect fail on blank name for ref 2
			array(FALSE,
				array( 
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			//expect fail on ref_01_phone_home too short
			array(FALSE,
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '70200',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			array(TRUE,
				array(
					array('name_full'=>'Short',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'uncle'),
					array('name_full'=>'Long', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			array(TRUE,
				array(
					array('name_full'=>'asdjhs asjhd ajshd',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 

			array(TRUE,
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array(
								'name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 

			array(TRUE,
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			//expect pass on blank name for ref 2 with only 1 required ref
			array(TRUE, 
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020000200',
								'relationship' =>'cousin'),
					array('name_full'=>'', 
								'phone_home' => '8005355998',
								'relationship' =>'father')
				),
				'1' //number of required references	for this test		
			), 
			
			//expect pass on anything for ref 2 with 0 refs required
			array(TRUE,
				array(
					array('name_full'=>'',  //data overlay for this test
								'phone_home' => '000000',
								'relationship' =>'cousin'),
					array('name_full'=>'', 
								'phone_home' => NULL,
								'relationship' =>'father')
				),
				'0' //number of required references	for this test		
			), 
			
			//expect fail on ref_02_phone_home too short
			array(FALSE,
				array(
					array('name_full'=>'Biggle Otoole',  //data overlay for this test
								'phone_home' => '7020023333',
								'relationship' =>'cousin'),
					array('name_full'=>'Jaspar Obercrombie', 
								'phone_home' => '800535599',
								'relationship' =>'father')
				),
				'2' //number of required references	for this test		
			), 
			
		);
	}
	
	

}
?>
