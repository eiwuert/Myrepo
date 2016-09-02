<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Test that when ZipCash targets are assembled they have the bad customer rule.
 * 
 * @group olpbbx_factory_test
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_ZipCashTargetTest extends OLPBlackbox_Factory_Base
{
	/**
	 * Test assembling a Zip Cash target.
	 * @return void
	 */
	public function testMain()
	{
		OLPBlackbox_Config::getInstance()->blackbox_mode = OLPBlackbox_Config::MODE_BROKER;
		
		$factory = new OLPBlackbox_Factory_TargetCollection();
		$factory->setDbConnection($this->getFactoryConnection());
		
		$target_model = new Blackbox_Models_Target($this->getFactoryConnection());
		$target_model->loadByKey(3);
		
		/* @var $collection OLPBlackbox_TargetCollection */
		$collection = $factory->getTargetCollection($target_model);
		
		$campaign = $collection->getTargetObject('zip_t1');
		$this->assertThat($campaign, $this->isInstanceOf('OLPBlackbox_Campaign'));
		
		/* @var $target OLPBlackbox_Target */
		$target = $campaign->getTarget();
		foreach ($target->getRules() as $rule)
		{
			$this->assertTrue(
				$rule instanceof OLPBlackbox_Rule_BadCustomer_ZipCash
			);
		}
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet 
	 * @see OLPBlackbox_Factory_Base::getFactoryDataSet()
	 */
	protected function getFactoryDataSet() 
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/ZipCashTargetTest.xml'
		);
	}
}

?>
