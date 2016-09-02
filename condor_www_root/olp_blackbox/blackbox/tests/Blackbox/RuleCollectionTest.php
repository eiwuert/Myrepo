<?php

	require_once 'blackbox_test_setup.php';

	/**
	 * Tests for the Blackbox_RuleCollection object
	 * @author Andrew Miner <andrew.minerd@sellingsource.com>
	 */
	class Blackbox_RuleCollectionTest extends PHPUnit_Framework_TestCase
	{
		/**
		 * Ensures that TRUE + TRUE = TRUE
		 *
		 * @return void
		 */
		public function testRuleAndTrue()
		{
			$data = new Blackbox_Data();
			$state_data = new Blackbox_StateData();

			$rule1 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule1->expects($this->any())
				->method('isValid')
				->will($this->returnValue(TRUE));

			$rule2 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule2->expects($this->any())
				->method('isValid')
				->will($this->returnValue(TRUE));

			$collection = new Blackbox_RuleCollection();
			$collection->addRule($rule1);
			$collection->addRule($rule2);

			$valid = $collection->isValid($data, $state_data);
			$this->assertTrue($valid);
		}

		/**
		 * Ensures that TRUE + FALSE = FALSE
		 *
		 * @return void
		 */
		public function testRuleAndFalse()
		{
			$data = new Blackbox_Data();
			$state_data = new Blackbox_StateData();

			$rule1 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule1->expects($this->any())
				->method('isValid')
				->will($this->returnValue(TRUE));

			$rule2 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule2->expects($this->any())
				->method('isValid')
				->will($this->returnValue(FALSE));

			$collection = new Blackbox_RuleCollection();
			$collection->addRule($rule1);
			$collection->addRule($rule2);

			$valid = $collection->isValid($data, $state_data);
			$this->assertFalse($valid);
		}

		/**
		 * Ensures that, if the first rule returns FALSE, the second rule is not run
		 *
		 * @return void
		 */
		public function testRuleShortcut()
		{
			$data = new Blackbox_Data();
			$state_data = new Blackbox_StateData();

			$rule1 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule1->expects($this->exactly(1))
				->method('isValid')
				->with($this->equalTo($data))
				->will($this->returnValue(FALSE));

			$rule2 = $this->getMock('Blackbox_IRule', array('isValid'));
			$rule2->expects($this->never())
				->method('isValid');

			$collection = new Blackbox_RuleCollection();
			$collection->addRule($rule1);
			$collection->addRule($rule2);

			$valid = $collection->isValid($data, $state_data);
		}
	}

?>
