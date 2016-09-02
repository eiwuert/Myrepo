<?php
/**
 * Unit tests for the Pixel message processor
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_MessageProcessor_PixelTest
		extends PHPUnit_Framework_TestCase {

	/**
	 * Data provider for testIsValidMessage
	 * @return array
	 */
	public function isValidMessageProvider() {
		
		return array(
			// Valid message
			array(
				(object)array(
					"bucket" => "bucket",
					"pixelURL" => "URL",
					"date" => "3",
				),
				TRUE),
			// Missing bucket
			array(
				(object)array(
					"pixelURL" => "URL",
					"date" => "3",
				),
				FALSE),
			// Missing pixelURL
			array(
				(object)array(
					"pageId" => "1",
					"date" => "3",
				),
				FALSE),
			// Missing date
			array(
				(object)array(
					"bucket" => "bucket",
					"pixelURL" => "URL",
				),
				FALSE),
			// Invalid date
			array(
				(object)array(
					"bucket" => "bucket",
					"pixelURL" => "URL",
					"date" => "three",
				),
				FALSE),
		);
	}

	/**
	 * 
	 * @param $message Message to validate
	 * @param $expected Expected validation return
	 * @dataProvider isValidMessageProvider
	 */
	public function testIsValidMessage($message, $expected) {
		$processor = new StatsService_MessageProcessor_Pixel();
		$this->assertEquals($expected, $processor->isValidMessage($message));
	}

	/**
	 * Test the message processor 
	 */
	public function testProcessMessage() {
		// Create the message ot process
		$message = (object)array(
					"bucket" => "spc_client_test",
					"pixelURL" => "URL",
					"date" => 1);

		// Mock the statpro client expectations
		$statProClient = $this->getMock(
			"statProClient", array(), array(), "", FALSE);
		$statProClient->expects($this->once())
			->method("urlEvent")
			->with(
				$this->equalTo(
					StatsService_Util::getCustomerFromBucket($message->bucket)),
				$this->equalTo(NULL),
				$this->equalTo($message->pixelURL),
				$this->equalTo($message->date));
				
		// mock the processor to return the mocked stat pro client
		$processor = $this->getMock(
			"StatsService_MessageProcessor_Pixel", array("getStatProClient"));
		$processor->expects($this->once())
			->method("getStatProClient")
			->with($this->equalTo($message->bucket))
			->will($this->returnValue($statProClient));

		// Process the message
		$processor->processMessage($message);	
	}
}