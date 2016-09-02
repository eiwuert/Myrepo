<?php
/**
 * ReferencesCountTest PHPUnit test file.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_ReferencesCount class.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */
class OLPBlackbox_Rule_ReferencesCountTest extends PHPUnit_Framework_TestCase
{
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
		$data = new OLPBlackbox_Data();
		
		$state_data = new OLPBlackbox_TargetStateData();
		
		foreach($data_test as $key=>$value)
		{
			//echo "\n KEY: $key VALUE: $value \n";
			//$data->email="chowder@home.com";
			$data->{$key}=$value;
			
		}
		
		//$data=array_merge($data, $data_test);

		//$req_obj = $this->getMock('OLPBlackbox_Rule_ReferencesCount', array('testLimits', 'addPost'));
		//$req_obj->expects($this->once())->method('testLimits')->will($this->returnValue($freq_return));

		$rule = $this->getMock(
			'OLPBlackbox_Rule_ReferencesCount',
			array('hitStat', 'hitEvent')
		);
		//$rule->expects($this->once())->method('getFrequencyScoreInstance')->will($this->returnValue($freq_obj));

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
		// Expected Return, array(ref_01_name_full, ref_01_phone_home, ref_01_relationship,
		//					ref_02_name_full, ref_02_phone_home, ref_02_relationship, required_references
		return array(
			array(TRUE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			array(TRUE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'1' //number of required references	for this test		
			), 
			//expect fail on blank name for ref 2
			array(FALSE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			//expect fail on ref_01_phone_home too short
			array(FALSE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '70200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			array(TRUE, array('ref_01_name_full'=>'Short',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'uncle',
								'ref_02_name_full'=>'Long', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			array(TRUE, array('ref_01_name_full'=>'asdjhs asjhd ajshd',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 

			array(TRUE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 

			array(TRUE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			//expect pass on blank name for ref 2 with only 1 required ref
			array(TRUE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020000200',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'', 
								'ref_02_phone_home' => '8005355998',
								'ref_02_relationship' =>'father',
								),
					'1' //number of required references	for this test		
			), 
			
			//expect pass on anything for ref 2 with 0 refs required
			array(TRUE, array('ref_01_name_full'=>'',  //data overlay for this test
								'ref_01_phone_home' => '000000',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'', 
								'ref_02_phone_home' => NULL,
								'ref_02_relationship' =>'father',
								),
					'0' //number of required references	for this test		
			), 
			
			//expect fail on ref_02_phone_home too short
			array(FALSE, array('ref_01_name_full'=>'Biggle Otoole',  //data overlay for this test
								'ref_01_phone_home' => '7020023333',
								'ref_01_relationship' =>'cousin',
								'ref_02_name_full'=>'Jaspar Obercrombie', 
								'ref_02_phone_home' => '800535599',
								'ref_02_relationship' =>'father',
								),
					'2' //number of required references	for this test		
			), 
			
		);
	}
	
	

}
?>
