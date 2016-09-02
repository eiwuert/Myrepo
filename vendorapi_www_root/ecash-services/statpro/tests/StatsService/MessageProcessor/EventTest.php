<?php
/**
 * Unit tests for the Event message processor
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_MessageProcessor_EventTest
		extends PHPUnit_Framework_TestCase {

	/**
	 * Test data provider testIsValidMessage 
	 * @return array
	 */
	public function isValidMessageProvider() {
		
		return array(
			// Valid message
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				TRUE),
			// Missing subCode
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				TRUE),
			// Missing bucket
			array(
				(object)array(
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Non-numeric pageId
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "one",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Missing pageId
			array(
				(object)array(
					"bucket" => "bucket",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Non-numeric promoId
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "two",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Missing promoId
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Missing Track
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Invalid track
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "1234567890123456789012345678",
					"event" => "event",
					"date" => "3",
				),
				FALSE),
			// Missing event
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"date" => "3",
				),
				FALSE),
			// Missing date
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
				),
				FALSE),
			// Invalid date
			array(
				(object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "three",
				),
				FALSE),
		);
	}

	/**
	 * Test the mesage validator
	 * @param $message Message to validate
	 * @param $expected Expected rreturn from validation
	 * @dataProvider isValidMessageProvider
	 */
	public function testIsValidMessage($message, $expected) {
		$processor = new StatsService_MessageProcessor_Event();
		$this->assertEquals($expected, $processor->isValidMessage($message));
	}

	/**
	 * Test processing the message
	 */
	public function testProcessMessage() {
		// Define the message to pass
		$message = (object)array(
					"bucket" => "bucket",
					"pageId" => "1",
					"promoId" => "2",
					"subCode" => "subCode",
					"track" => "track",
					"event" => "event",
					"date" => "3");

		// Get a statpro client mock
		$statProClient = $this->getMock(
			"Stats_StatPro_Client_1", array(), array(), "", FALSE);
		
		// Mock the processor to override the getting of stat pto client
		$processor = $this->getMock(
			"StatsService_MessageProcessor_Event", array("getStatProClient"));
		$processor->expects($this->once())
			->method("getStatProClient")
			->with($this->equalTo($message->bucket))
			->will($this->returnValue($statProClient));
			
		// Define the getSpaceKey mock expectations
		$space_key = "space_key";
		$space_key_def = array(
			"page_id" => $message->pageId,
			"promo_id" => $message->promoId,
			"promo_sub_code" => $message->subCode,
		);
		$statProClient->expects($this->once())
			->method("getSpaceKey")
			->with($this->equalTo($space_key_def))
			->will($this->returnValue($space_key));
			
		// Define the recordEvent mock expectations
		$statProClient->expects($this->once())
			->method("recordEvent")
			->with(
				$this->equalTo($message->track),
				$this->equalTo($space_key),
				$this->equalTo($message->event),
				$this->equalTo($message->date));
		
		// Make the call
		$processor->processMessage($message);
	}
}