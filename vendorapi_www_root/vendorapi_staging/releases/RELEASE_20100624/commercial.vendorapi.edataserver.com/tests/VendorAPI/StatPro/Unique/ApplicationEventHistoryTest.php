<?php
/**
 * Unit tests for VendorAPI_StatPro_Unique_ApplicationEventHistory
 * @see VendorAPI_StatPro_Unique_ApplicationEventHistory
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_StatPro_Unique_ApplicationEventHistoryTest
		extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		$this->markTestSkipped("Broken test");
	}
	
	/**
	 * Test adding an event to the history will persist the name and 
	 * application association properly
	 */
	public function testAddEvent()
	{
		$application_id = 1;
		$event_name = "addEventEvent";
		$stat_name_id = 100;
		$now = "String for date";
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->exactly(4))
			->method("__set");
		$history_model
			->expects($this->never())
			->method("loadBy");
		$history_model
			->expects($this->once())
			->method("save");
				
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadBy")
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->once())
			->method("__get")
			->with($this->equalTo("stat_name_id"))
			->will($this->returnValue($stat_name_id));
		
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->once())
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->once())
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->once())
			->method("getEventNameModel")
			->will($this->returnValue($name_model));
		
		$event_history->addEvent($event_name, $application_id);
	}

	/**
	 * Test that contains event from the history will properly idenify existing
	 * relationships in the event history
	 */
	public function testContainsEvent()
	{
		$application_id = 1;
		$event_name = "containsEventEvent";
		$stat_name_id = 300;
		$now = "String for date";
		$data_rows = new ArrayIterator();
		$row = new stdClass();
		$row->stat_name_id = $stat_name_id;
		$data_rows->append($row);
		
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->once())
			->method("loadAllBy")
			->with($this->equalTo(array("application_id" => $application_id)))
			->will($this->returnValue($data_rows));
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadByKey")
			->with(array($stat_name_id))
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->once())
			->method("__get")
			->with($this->equalTo("name"))
			->will($this->returnValue(strtolower($event_name)));
		
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->never())
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->once())
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->once())
			->method("getEventNameModel")
			->will($this->returnValue($name_model));

		$this->assertTrue(
			$event_history->containsEvent($event_name, $application_id));
		$this->assertFalse(
			$event_history->containsEvent("UNKNOWN_EVENT", $application_id));
	}
	
	/**
	 * Test that clontains event will cache the history load for the same app
	 */	
	public function testContainsEventCachesLoad()
	{
		$application_id = 1;
		$event_name = "containsEventCacheEvent";
		$stat_name_id = 400;
		$now = "String for date";
		$data_rows = new ArrayIterator();
		$row = new stdClass();
		$row->stat_name_id = $stat_name_id;
		$data_rows->append($row);
		
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->once())
			->method("loadAllBy")
			->with($this->equalTo(array("application_id" => $application_id)))
			->will($this->returnValue($data_rows));
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadByKey")
			->with(array($stat_name_id))
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->once())
			->method("__get")
			->with($this->equalTo("name"))
			->will($this->returnValue(strtolower($event_name)));
		
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->never())
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->once())
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->once())
			->method("getEventNameModel")
			->will($this->returnValue($name_model));

		$this->assertTrue(
			$event_history->containsEvent($event_name, $application_id));
		$this->assertTrue(
			$event_history->containsEvent($event_name, $application_id));
	}

	/**
	 * Test that the event name name lookup uses cached results when appropriate
	 */
	public function testEventNameFromIdCaches()
	{
		$application_id = 1;
		$event_name = "eventNameFromIdCache";
		$stat_name_id = 500;
		$now = "String for date";
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->exactly(8))
			->method("__set");
		$history_model
			->expects($this->never())
			->method("loadBy");
		$history_model
			->expects($this->exactly(2))
			->method("save");
				
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadBy")
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->once())
			->method("__get")
			->with($this->equalTo("stat_name_id"))
			->will($this->returnValue($stat_name_id));
		
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->exactly(2))
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->exactly(2))
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->once())
			->method("getEventNameModel")
			->will($this->returnValue($name_model));
		
		$event_history->addEvent($event_name, $application_id);
		$event_history->addEvent($event_name, $application_id);
	}
	
	/**
	 * Test that the event name id lookup uses cached results when appropriate
	 */
	public function testEventIdFromNameCaches()
	{
		$application_id = 1;
		$event_name = "eventIdFromNameCache";
		$stat_name_id = 600;
		$now = "String for date";
		$data_rows = new ArrayIterator();
		$row = new stdClass();
		$row->stat_name_id = $stat_name_id;
		$data_rows->append($row);
		
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->exactly(2))
			->method("loadAllBy")
			->will($this->returnValue($data_rows));
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadByKey")
			->with(array($stat_name_id))
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->once())
			->method("__get")
			->with($this->equalTo("name"))
			->will($this->returnValue(strtolower($event_name)));
		
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->never())
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->exactly(2))
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->once())
			->method("getEventNameModel")
			->will($this->returnValue($name_model));

		$this->assertTrue(
			$event_history->containsEvent($event_name, $application_id));
		$this->assertTrue(
			$event_history->containsEvent($event_name, 0));
	}
	
	/**
	 * Test that contains event from the history will properly identify existing
	 * relationships cached locally from addEvent witout having to re-load the 
	 * relathionships from the database
	 */
	public function testContainsEventUsesCachedAddEvent()
	{
		$application_id = 1;
		$event_name = "containsEventEvent";
		$new_event = "NEW EVENT";
		$stat_name_id = 1000;
		$new_stat_name_id = 1001;
		$now = "String for date";
		$data_rows = new ArrayIterator();
		$row = new stdClass();
		$row->stat_name_id = $stat_name_id;
		$data_rows->append($row);
		
		$history_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$history_model
			->expects($this->once())
			->method("loadAllBy")
			->with($this->equalTo(array("application_id" => $application_id)))
			->will($this->returnValue($data_rows));
		
		$name_model = $this->getMock(
				"DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$name_model
			->expects($this->once())
			->method("loadByKey")
			->with(array($stat_name_id))
			->will($this->returnValue(TRUE));
		$name_model
			->expects($this->exactly(2))
			->method("__get")
			->will(
				$this->returnValue(strtolower($event_name)),
				$this->returnValue($stat_name_id),
				$this->returnValue($new_stat_name_id));
		$name_model
			->expects($this->once())
			->method("loadBy")
			->will($this->returnValue(TRUE));
			
		$event_history = $this->getMock(
			"VendorAPI_StatPro_Unique_ApplicationEventHistory",
			array("getNowString", "getEventHistoryModel", "getEventNameModel"),
			array(),
			"",
			FALSE);
		$event_history
			->expects($this->once())
			->method("getNowString")
			->will($this->returnValue($now));
		$event_history
			->expects($this->exactly(2))
			->method("getEventHistoryModel")
			->will($this->returnValue($history_model));
		$event_history
			->expects($this->exactly(2))
			->method("getEventNameModel")
			->will($this->returnValue($name_model));

		$this->assertTrue(
			$event_history->containsEvent($event_name, $application_id));
		$this->assertFalse(
			$event_history->containsEvent($new_event, $application_id));
		$event_history->addEvent($new_event, $application_id);
		$this->assertTrue(
			$event_history->containsEvent($new_event, $application_id));
	}
}