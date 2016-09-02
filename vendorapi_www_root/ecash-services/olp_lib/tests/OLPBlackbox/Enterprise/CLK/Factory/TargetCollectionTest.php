<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the CLK Target Collection factory.
 * 
 * @group olpbbx_factory_test
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Enterprise_CLK_Factory_TargetCollectionTest extends OLPBlackbox_Factory_Base
{
	/** 
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet 
	 * @see OLPBlackbox_Factory_BaseTest::getFactoryDataSet()
	 */
	protected function getFactoryDataSet() 
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/TargetCollectionTest.xml'
		);
	}
	
	/**
	 * Test inactive targets, inactive campaigns, target collection class, and
	 * picker class creation.
	 * 
	 * @return void
	 */
	public function testMain()
	{
		// this test will run in BROKER mode, we want rules to run.
		OLPBlackbox_Config::getInstance()->blackbox_mode = OLPBlackbox_Config::MODE_BROKER;
		
		// even though we're testing CLK, we want the generic factory to
		// make the decision to use the CLK TargetCollection Factory.
		$factory = new OLPBlackbox_Factory_TargetCollection();
		$factory->setDbConnection($this->getFactoryConnection());

		$model = new Blackbox_Models_Target($this->getFactoryConnection());
		$model->loadBy(array('target_id' => 7));	// CLK collection object
		
		/* @var $collection OLPBlackbox_Enterprise_TargetCollection */
		$collection = $factory->getTargetCollection($model);
		$this->assertTrue(
			$collection instanceof OLPBlackbox_Enterprise_TargetCollection
		);
		$this->assertTrue(
			$collection->getPicker() instanceof OLPBlackbox_PercentPicker
		);

		// pcl is set up properly (getTargetObject will find the campaign)
		$pcl = $collection->getTargetObject('pcl')->getTarget();
		$this->assertTrue($pcl instanceof OLPBlackbox_Enterprise_CLK_Target);

		// the campaign for ucl is inactive.
		$this->assertNull($collection->getTargetObject('ucl'));

		// the target for ca is inactive
		$this->assertNull($collection->getTargetObject('ca'));
	}
}

?>
