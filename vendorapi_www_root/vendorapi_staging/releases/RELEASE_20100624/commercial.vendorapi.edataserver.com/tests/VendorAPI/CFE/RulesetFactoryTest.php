<?php

class VendorAPI_CFE_RulesetFactoryTest extends PHPUnit_Framework_TestCase
{
	protected $_factory;
	protected $_mock_action;
	protected $_mock_condition;
	protected $_mock_expr;

	public function setUp()
	{
		$this->_mock_expr = $this->getMock('ECash_CFE_IExpression');
		$this->_factory = $this->getMock('VendorAPI_CFE_IFactory');
	}

	public function tearDown()
	{
		$this->_factory = null;
		$this->_mock_action = null;
		$this->_mock_condition = null;
		$this->_mock_expr = null;
	}

	public function testAddsRulesToEvents()
	{
		$xml = <<<XML
<?xml version="1.0"?>
<ruleset>
	<node id="test">
	</node>
</ruleset>
XML;

		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$f = new VendorAPI_CFE_RulesetFactory($this->_factory);
		$ruleset = $f->getRuleset($doc);

		$this->assertArrayHasKey('test', $ruleset);
		$this->assertType('array', $ruleset['test']);
	}

	public function testCreatesExpressions()
	{
		$this->_factory->expects($this->once())
			->method('getExpression')
			->with('Test', array())
			->will($this->returnValue($this->_mock_expr));

		$xml = <<<XML
<?xml version="1.0"?>
<ruleset>
	<node id="test">
		<expression type="Test"/>
	</node>
</ruleset>
XML;

		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$f = new VendorAPI_CFE_RulesetFactory($this->_factory);
		$ruleset = $f->getRuleset($doc);
	}

	public function testUsesExpressionsAsExpressionParameters()
	{
		$expr1 = $this->getMock('ECash_CFE_IExpression');
		$expr2 = $this->getMock('ECash_CFE_IExpression');

		$this->_factory->expects($this->at(0))
			->method('getExpression')
			->with('Expression2', array())
			->will($this->returnValue($expr1));

		$this->_factory->expects($this->at(1))
			->method('getExpression')
			->with('Expression1', array($expr1))
			->will($this->returnValue($expr2));

		$xml = <<<XML
<?xml version="1.0"?>
<ruleset>
	<node id="test">
		<expression type="Expression1"><param type="Expression2"/></expression>
	</node>
</ruleset>
XML;

		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$f = new VendorAPI_CFE_RulesetFactory($this->_factory);
		$ruleset = $f->getRuleset($doc);
	}
}

?>
