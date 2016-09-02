<?php

	require_once 'blackbox_test_setup.php';

	/**
	 * Tests for the Blackbox_Data object
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Blackbox_DataTest extends PHPUnit_Framework_TestCase
	{
		/**
		 * Test for __get with an item that exists
		 *
		 * @return void
		 */
		public function testGetExists()
		{
			$data = $this->getData();
			$this->assertEquals('woot', $data->test1);
		}

		/**
		 * Test for __set with an item that exists; should modify the item
		 *
		 * @return void
		 */
		public function testSetExists()
		{
			$data = $this->getData();
			$data->test2 = 'foo';

			$this->assertEquals('foo', $data->test2);
		}

		/**
		 * Test for __set with an item that doesn't exist... should get an exception
		 *
		 * @return void
		 */
		public function testSetNotExists()
		{
			$this->setExpectedException('Blackbox_Exception');

			$data = $this->getData();
			$data->test3 = 'hoopy';
		}

		/**
		 * Test for __unset with an item that exists; should unset the item
		 *
		 * @return void
		 */
		public function testUnsetExists()
		{
			$data = $this->getData();
			unset($data->test2);
			$this->assertEquals(NULL, $data->test2);
		}

		/**
		 * Test for __isset with an item that exists; should return TRUE
		 *
		 * @return void
		 */
		public function testIssetExists()
		{
			$data = $this->getData();
			$this->assertTrue(isset($data->test2));
		}

		/**
		 * Test for __unset with an item that doesn't exist; should return FALSE
		 *
		 * @return void
		 */
		public function testIssetNotExists()
		{
			$data = $this->getData();
			$this->assertFalse(isset($data->test3));
		}

		/**
		 * Ensure that getKeys() is returning the proper values.
		 *
		 * @return void
		 */
		public function testKeysValid()
		{
			$data = $this->getData();
			$this->assertEquals($data->getKeys(), array('test1', 'test2'));
		}

		/**
		 * Gets a test data object for the test
		 *
		 * @return Blackbox_DataTestObj
		 */
		protected function getData()
		{
			return new Blackbox_DataTestObj(array(
				'test1' => 'woot',
				'test2' => 'blah',
			));
		}
	}

?>
