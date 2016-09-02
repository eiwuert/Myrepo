<?php

/**
 * Tests the Rule Factory for various special case circumstances.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_RuleTest extends OLPBlackbox_Factory_Base
{
	/**
	 * Tests that reusable rules are cached properly.
	 * @return void
	 */
	public function testReusableRule()
	{
		$rule_factory = new OLPBlackbox_Factory_Rule();
		$rule_factory->setDbConnection($this->getFactoryConnection());
		
		$rule_model = new Blackbox_Models_Rule($this->getFactoryConnection());
		$this->assertTrue($rule_model->loadBy(array('rule_id' => 3)));
		
		$rule1 = $rule_factory->getRule($rule_model);
		$rule2 = $rule_factory->getRule($rule_model);
		
		// alter the rule model so it's not cache-able
		$rule_model->rule_value = 2981;
		
		$rule3 = $rule_factory->getRule($rule_model);
		
		// should be the same instance.
		$this->assertTrue($rule1 === $rule2);
		$this->assertFalse($rule2 === $rule3);
	}

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet 
	 * @see OLPBlackbox_Factory_Base::getFactoryDataSet()
	 */
	protected function getFactoryDataSet() 
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/RuleTest.xml'
		);
	}

	public function testCreateLeadSenToCampaign()
	{
		$mock_olp_factory = $this->getMock('OLP_Factory', array(), array(), '', FALSE);
		$rule_factory = $this->getMock('OLPBlackbox_Factory_Rule', 
			array('getOLPFactory'));
		$connection = $this->getFactoryConnection();
		
		$rule_factory->setDbConnection($connection);
		$rule_factory->expects($this->any())->method('getOLPFactory')
			->will($this->returnValue($mock_olp_factory));

		$rule_factory->setDbConnection($connection);

		$rule_model = new Blackbox_Models_Rule($connection);
		$this->assertTrue($rule_model->loadBy(array('rule_id' => 4)));

		$rule = $rule_factory->getRule($rule_model);

		$this->assertThat($rule, $this->isInstanceOf('OLPBlackbox_Rule_LeadSentToCampaign'));
	}
}

?>
