<?php
/**
 * OLPBlackbox_Factory_RuleCollection test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Factory_RuleCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mocked rule defintion that will return a withheld targets rule
	 *
	 * @var Blackbox_Models_Rule
	 */
	protected $withheld_targets_rule_model;
	
	/**
	 * Mocked rule defintion that will return a duplicate lead rule
	 *
	 * @var Blackbox_Models_Rule
	 */
	protected $duplicate_lead_rule_model;
	
	/**
	 * Mocked rule collection factory
	 *
	 * @var OLPBlackbox_Factory_RuleCollection
	 */
	protected $rule_collection_factory;
	
	/**
	 * Mocked Blackbox_Models_Target which mocks __get() to return the key given 
	 *
	 * @var Blackbox_Models_Target
	 */
	protected $target_model;

	/**
	 * Array of rules to be returned by the mocked getActive rules method in
	 * the rule collection factory.  Defaults to an empty array.
	 *
	 * @var unknown_type
	 */
	protected $active_rules = array();

	/**
	 * Array of rule classes defined by getRuleModelMock to allow
	 * getRuleFromModel to return the rule
	 *
	 * @var array
	 */
	protected $rule_classes = array();

	/**
	 * Sets up the mocked objects
	 *
	 * @return void
	 */
	public function setUp()
	{
		$rule_factory = $this->getMock("OLPBlackbox_Factory_Rule", array("getRule"));
		// Set factory to pass back what was passed to it
		$rule_factory->expects($this->any())
			->method("getRule")
			->will($this->returnCallback(array($this, "getRuleFromModel")));
			
		$rule_definition_model = new StdClass();
		$rule_definition_model->name_short = "NONE_OF_THE_ABOVE";

		// Active rules will be an array of actual rule that will be passed back through the mocked factory
		$this->rule_collection_factory = $this->getMock(
			"OLPBlackbox_Factory_RuleCollection",
			array("getActiveRules", "newRuleCollection", "getRuleDefinition","addWithheldTargetsRule"),
			array($rule_factory));
		$this->rule_collection_factory->expects($this->any())
			->method("getActiveRules")
			->will($this->returnCallback(array($this, "getActiveRules")));
		$this->rule_collection_factory->expects($this->any())
			->method("newRuleCollection")
			->will($this->returnValue(new OLPBlackbox_RuleCollection()));
		$this->rule_collection_factory->expects($this->any())
			->method("getRuleDefinition")
			->will($this->returnValue($rule_definition_model));
		
		$this->target_model = $this->getMock("Blackbox_Models_Target", array("__get"), array(), "", FALSE);
		$this->target_model->expects($this->any())->method("__get")->will($this->returnArgument(0));
	}
	
	/**
	 * Reset the instance variables to the defaults
	 *
	 * @return void
	 */
	public function tearDown()
	{
		$this->duplicate_lead_rule_model = NULL;
		$this->withheld_targets_rule_model = NULL;
		$this->rule_collection_factory = NULL;
		$this->target_model = NULL;
		$this->active_rules = array();
	}

	/**
	 * Get a rule model that will allow the mocked rule factory
	 * to use it to get a rule
	 *
	 * @param string $rule_name
	 * @return Blackbox_Models_Rule
	 */
	protected function getRuleModel($rule_name)
	{
		$db = $this->getMock("DB_IConnection_1", array(), array(), "", FALSE);
		$rule = $this->getMock($rule_name);
		$this->rule_classes[$rule_name] = $rule;
		
		$rule_model = new Blackbox_Models_Rule($db);
		$rule_model->rule_definition_id = 1;
		$rule_model->name = $rule_name; 

		return $rule_model;
	}

	/**
	 * Get the rule class defined by getRuleModel using the provided Blackbox_Models_Rule
	 *
	 * @param Blackbox_Models_Rule $model
	 * @return OLPBlackbox_Rule
	 */
	public function getRuleFromModel(Blackbox_Models_Rule $model)
	{
		return $this->rule_classes[$model->name];
	}

	/**
	 * Get the active_rules value
	 *
	 * @return array
	 */
	public function getActiveRules()
	{
		return $this->active_rules;
	}

	/**
	 * Test to make sure that when the factory is provided a duplicate lead rule as an active rule
	 * that it will do the following:
	 * #1 - Add no duplicate lead rule to pick targets
	 * #2 - Add one and only one duplicate lead rule to pick targets
	 * #3 - The duplicate lead rule is always the last rule in pick targets
	 *
	 * @return void
	 */
	public function testDuplicateLeadRulePlacement()
	{
		// Get the rule models and set them as the active rules array for the factory to return
		// with the getActiveRules method
		$duplicate_lead_rule_model = $this->getRuleModel("OLPBlackbox_Rule_DuplicateLead");
		$withheld_targets_rule_model = $this->getRuleModel("OLPBlackbox_Rule_WithheldTargets");
		$this->active_rules = array($duplicate_lead_rule_model, $withheld_targets_rule_model);

		// Make our target for the factory
		$target = new OLPBlackbox_Target("TARGET", 1);
		
		// Have the factory add rules
		$this->rule_collection_factory->setRuleCollections($this->target_model, $target);

		// Count pick target duplicate lead rules and get the last pick target rule
		$last_rule = NULL;
		$duplicate_lead_prick_target_rule_count = 0;
		$duplicate_lead_rule = $this->getRuleFromModel($duplicate_lead_rule_model);
		foreach ($target->getPickTargetRules()->getIterator() as $rule)
		{
			$last_rule = $rule;
			if ($rule == $duplicate_lead_rule) $duplicate_lead_prick_target_rule_count++;
		}

		// Count duplicate lead rules
		$duplicate_lead_rule_count = 0;
		$duplicate_lead_rule = $this->getRuleFromModel($duplicate_lead_rule_model);
		foreach ($target->getRules()->getIterator() as $rule)
		{
			if ($rule == $duplicate_lead_rule) $duplicate_lead_rule_count++;
		}
		
		// Validate that the last rule is the duplicate lead rule 
		$this->assertEquals($duplicate_lead_rule, $last_rule);
		
		// The duplicate leads rule should never be in the isValid rules
		$this->assertEquals(0, $duplicate_lead_rule_count); 
		
		// The count of the number of duplicate lead rules should not exceed 1.  This test will
		// assure we don't place the same rule twice
		$this->assertEquals(1, $duplicate_lead_prick_target_rule_count); 
	}

	/**
	 * There can be only one duplicate lead rule.  An Exception should be
	 * thrown if otherwise
	 *
	 * @return void
	 */
	public function testHighlander()
	{
		$this->setExpectedException("Exception");
		// Get the rule models and set them as the active rules array for the factory to return
		// with the getActiveRules method
		$duplicate_lead_rule_model = $this->getRuleModel("OLPBlackbox_Rule_DuplicateLead");
		$this->active_rules = array($duplicate_lead_rule_model, $duplicate_lead_rule_model);

		// Make our target for the factory
		$target = new OLPBlackbox_Target("TARGET", 1);
		
		// Have the factory add rules
		$this->rule_collection_factory->setRuleCollections($this->target_model, $target);
		
	}
}