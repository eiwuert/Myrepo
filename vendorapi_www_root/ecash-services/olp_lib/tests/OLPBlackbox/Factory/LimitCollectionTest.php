<?php

/**
 * Tests the LimitCollection factory
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class OLPBlackbox_Factory_LimitCollectionTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Reference to Blackbox_Models_Target model.
	 *
	 * @var Blackbox_Models_Target
	 */
	protected $target_model;
	
	/**
	 * Reference to Blackbox_Models_Rule model.
	 *
	 * @var Blackbox_Models_Rule
	 */
	protected $daily_model;
	
	/**
	 * Reference to Blackbox_Models_Rule model.
	 *
	 * @var Blackbox_Models_Rule
	 */
	protected $hourly_model;
	
	/**
	 * Reference to OLPBlackbox_Factory_Rule.
	 *
	 * @var OLPBlackbox_Factory_Rule
	 */
	protected $rule_factory;
	
	/**
	 * Reference to OLPBlackbox_Factory_RuleCollection.
	 *
	 * @var OLPBlackbox_Factory_RuleCollection
	 */
	protected $rule_collection_factory;
	
	/**
	 * Mocked DB_IConnection_1 object.
	 *
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * Sets up the different mocked views and db model factory.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$this->db = $this->getMock('DB_IConnection_1');
		
		$this->target_model = $this->getMock(
			'Blackbox_Models_Target',
			array('save', 'loadByKey', 'isStored'),
			array($this->db)
		);
		$this->daily_model = $this->getMock(
			'Blackbox_Models_Rule',
			array('save'),
			array($this->db)
		);
		$this->hourly_model = $this->getMock(
			'Blackbox_Models_Rule',
			array('save'),
			array($this->db)
		);
		$this->rule_factory = $this->getMock(
			'OLPBlackbox_Factory_Rule',
			array(),
			array()
		);
	}
	
	public function testGetLimitCollection()
	{
		$this->target_model->property_short = 'testa';
		
		$limit_collection_factory = $this->getMock('OLPBlackbox_Factory_LimitCollection', array('addLimitRules'));
		$limit_collection_factory->expects($this->once())->method('addLimitRules')
			->with(
				$this->isInstanceOf('OLPBlackbox_RuleCollection'),
				$this->equalTo($this->target_model->property_short),
				$this->equalTo($this->daily_model),
				$this->equalTo($this->hourly_model)
			);
			
		$collection = new OLPBlackbox_RuleCollection();
		$collection->setEventName(OLPBlackbox_Config::EVENT_LIMITS);
			
		$limit_collection = $limit_collection_factory->getLimitCollection($this->target_model, $this->daily_model, $this->hourly_model);
		
		$this->assertType('OLPBlackbox_RuleCollection', $limit_collection);
		$this->assertEquals($collection, $limit_collection);
	}
	
	public function testAddLimitRules()
	{
		$property_short = 'testa';
		$this->daily_model->rule_value = 'test';
		$this->hourly_model->rule_value = 'test2';
		
		$collection = $this->getMock('OLPBlackbox_RuleCollection');
		$collection->setEventName(OLPBlackbox_Config::EVENT_LIMITS);
		
		$limit_collection_factory = $this->getMock(
			'OLPBlackbox_Factory_LimitCollection',
			array('getDOWLimit', 'getHourlyLimit', 'createLimitRule')
		);
		
		$rule = new OLPBlackbox_Rule_Limit();
		
		$limit_collection_factory->expects($this->once())->method('getDOWLimit')
			->with($this->equalTo('test'))
			->will($this->returnValue(5));
			
		$limit_collection_factory->expects($this->once())->method('getHourlyLimit')
			->with($this->equalTo('test2'), $this->equalTo(5))
			->will($this->returnValue(10));

		$limit_collection_factory->expects($this->at(1))->method('createLimitRule')
			->with($this->anything(), $this->equalTo($property_short), $this->equalTo(5), $this->anything())
			->will($this->returnValue($rule));

		$limit_collection_factory->expects($this->at(3))->method('createLimitRule')
			->with($this->anything(), $this->equalTo($property_short), $this->equalTo(10), $this->anything())
			->will($this->returnValue($rule));
			
		$collection->expects($this->exactly(2))->method('addRule')
			->with($this->equalTo($rule));
		
		$limit_collection_factory->addLimitRules($collection, $property_short, $this->daily_model, $this->hourly_model);
	}
}

?>
