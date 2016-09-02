<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the {@see OLPBlackbox_Enterprise_TargetCollection} class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_TargetCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that when a target of an {@see OLPBlackbox_Enterprise_TargetCollection}
	 * throws an exception, the entire collection is failed.
	 *
	 * @return void
	 */
	public function testEnterpriseCollectionFailException()
	{
		$data = new OLPBlackbox_Data();
		$data->income_direct_deposit = TRUE;
		
		$state_data = new OLPBlackbox_StateData();
		
		$collection = new OLPBlackbox_Enterprise_TargetCollection('name');
		
		$target_names = array('targ1', 'targ2');
		
		foreach ($target_names as $target_name)
		{
			// this rule will work, and make each target valid
			$rule = new OLPBlackbox_Rule_Equals();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => 'income_direct_deposit',
				Blackbox_StandardRule::PARAM_VALUE => TRUE)
			);
			
			$mock_rule = $this->getMock(
				'OLPBlackbox_Rule_Equals',
				array('runRule', 'canRun')
			);
			$mock_rule->expects($this->any())
				->method('canRun')
				->will($this->returnValue(TRUE));
								
			// this rule will throw an exception if it's the first target
			if ($target_name == $target_names[0])
			{
				$mock_rule->expects($this->any())
					->method('runRule')
					->will($this->throwException(
						new OLPBlackbox_FailException('expected test exception')
					));
			}
			else
			{
				$mock_rule->expects($this->any())
					->method('runRule')
					->will($this->returnValue(TRUE));
			}
			
			$target = new OLPBlackbox_Target($target_name, 1);
			$target->setPickTargetRules($mock_rule);
			$target->setRules($rule);
			
			$collection->addTarget(
				new OLPBlackbox_Campaign("{$target_name}_cam", 2, 10, $target, $rule)
			);
		}
		
		$collection->isValid($data, $state_data);
		
		// this will pick the first target, because by default the collection is
		// going to be ordered since there was no picker set up.
		// first target should throw an exception and invalidate the others.
		$this->assertFalse($collection->pickTarget($data));
		$this->assertFalse($collection->pickTarget($data));
	}
}

?>