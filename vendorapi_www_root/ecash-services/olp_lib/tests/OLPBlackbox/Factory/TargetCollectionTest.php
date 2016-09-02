<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the generic TargetCollection factory.
 *
 * @group olpbbx_factory_test
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_TargetCollectionTest extends OLPBlackbox_Factory_Base 
{
	/**
	 * Tests rule revisions, inactive targets, inactive campaigns, nested 
	 * collections, targets with no rules and rule modes.
	 * 
	 * @dataProvider genericTargetTestData
	 * @return void
	 */
	public function testMain()
	{
		// make sure we're "in broker"
		OLPBlackbox_Config::getInstance()->blackbox_mode = OLPBlackbox_Config::MODE_BROKER;

		$factory = new OLPBlackbox_Factory_TargetCollection();
		$factory->setDbConnection($this->getFactoryConnection());
				
		$model = new Blackbox_Models_Target($this->getFactoryConnection());
		$model->loadBy(array('target_id' => 6));
		
		/* @var $collection OLPBlackbox_OrderedCollection */
		$collection = $factory->getTargetCollection($model);
		$this->assertTrue(
			$collection instanceof OLPBlackbox_OrderedCollection
		);
		
		/* the t1v target should have a minimum income rule in broker and a
		 * checking rule in prequal
		 * This tests two things:
		 * 1) Revisions. The previous revision had minimum income in prequal.
		 * 2) Modes.
		 */
		/* @var $t1v_campaign OLPBlackbox_Campaign */
		$t1v_campaign = $collection->getTargetObject('t1v');
		$this->assertThat($tlv_campaign, $this->isInstanceOf('OLPBlackbox_Campaign'));
		$t1v = $t1v_campaign->getTarget();
		
		/* @var $rules OLPBlackbox_RuleCollection */
		$rules = $t1v->getRules();
		$this->assertTrue($rules instanceof OLPBlackbox_RuleCollection);

		foreach ($rules as $rule)
		{
			$this->assertTrue($rule instanceof OLPBlackbox_Rule_MinimumIncome);
		}
	}

	/**
	 * Main data set for this test (which will get merged with parent's)
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet 
	 * @see OLPBlackbox_Factory_FactoryTestBase::getDataSet()
	 */
	protected function getFactoryDataSet() 
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/TargetTest.xml'
		);
	}
}

?>
