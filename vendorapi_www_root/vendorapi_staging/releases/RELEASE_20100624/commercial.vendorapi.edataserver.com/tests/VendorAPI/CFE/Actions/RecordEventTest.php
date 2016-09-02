<?php
/**
 * Unit tests for VendorAPI_CFE_Actions_RecordEvent
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_CFE_Actions_RecordEventTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_StatPro_Unique_Client
	 */
	private $statpro;

	/**
	 * @var ECash_CFE_IContext
	 */
	private $context; 

	/**
	 * @var VendorAPI_IDriver
	 */
	private $driver;

	/**
	 * @var array
	 */
	private $attributes = array();

	/**
	 * Set up for a test
	 */
	public function setUp()
	{
		$this->statpro =
			$this->getMock(
			"VendorAPI_StatPro_Unique_Client",
			array(),
			array(),
			"",
			FALSE);
		$this->context = $this->getMock("ECash_CFE_IContext");
		$this->driver = $this->getMock("VendorAPI_IDriver");
		$this->context
			->expects($this->any())
			->method("getAttribute")
			->will($this->returnCallback(array($this, "getAttribute")));

		$this->setAttribute("track_key","track");
		$this->setAttribute("space_key","space");
		$this->setAttribute("application_id","1234567890");
		$this->setAttribute("driver", $this->driver);
	}

	/**
	 * Tear down after a test
	 */
	public function tearDown()
	{
		$this->statpro = NULL;
	}

	/**
	 * Test that a unique event calls hitUniqueStat
	 */
	public function testUniqueEvent()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->once())
			->method("hitUniqueStat")
			->with(
				$this->equalTo($event),
				$this->equalTo($this->getAttribute("application_id")),
				$this->equalTo($this->getAttribute("track_key")),
				$this->equalTo($this->getAttribute("space_key")));
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("unique" => "TRUE", "event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that a non-unique event calls hitStat
	 */
	public function testNonUniqueEvent()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->once())
			->method("hitStat")
			->with(
				$this->equalTo($event),
				$this->equalTo($this->getAttribute("track_key")),
				$this->equalTo($this->getAttribute("space_key")));
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("unique" => "FALSE", "event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that a no unique paramter defined event calls hitStat
	 */
	public function testNoUniqueEventParam()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->once())
			->method("hitStat")
			->with(
				$this->equalTo($event),
				$this->equalTo($this->getAttribute("track_key")),
				$this->equalTo($this->getAttribute("space_key")));
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}

	/**
	 * Test that an exception is retuned when no driver is returned from the
	 * context
	 * @expectedException RuntimeException
	 */
	public function testNoDriver()
	{
		$event = "event";
		$this->setAttribute("driver", NULL);
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->never())
			->method("getStatProClient");

		$params = array("event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that an exception is retuned when no statpro client is returned from
	 * the driver
	 * @expectedException RuntimeException
	 */
	public function testNoStatProClient()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue(NULL));

		$params = array("event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that an exception is retuned when no event is returned from the
	 * context
	 * @expectedException RuntimeException
	 */
	public function testNoEvent()
	{
		$event = NULL;
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that an exception is retuned when no track_key is returned from the
	 * context
	 * @expectedException RuntimeException
	 */
	public function testNoTrackKey()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event);
		
		$this->setAttribute("track_key", NULL);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that an exception is retuned when no space_key is returned from the
	 * context
	 * @expectedException RuntimeException
	 */
	public function testNoSpaceKey()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event);
		
		$this->setAttribute("space_key", NULL);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}

	/**
	 * Test that no exception is thrown when the statpro client does not
	 * implement VendorAPI_StatPro_Unique_IClient but the call is not unique
	 */
	public function testNoAppIdNonUnique()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->once())
			->method("hitStat")
			->with(
				$this->equalTo($event),
				$this->equalTo($this->getAttribute("track_key")),
				$this->equalTo($this->getAttribute("space_key")));
				
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event);
		
		$this->setAttribute("application_id", NULL);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}

	/**
	 * Test that an exception is thrown when no application_id is returned
	 * from the context and the call is unique
	 * @expectedException RuntimeException
	 */
	public function testNoAppUnique()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event, "unique" => "true");
		
		$this->setAttribute("application_id", NULL);
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	/**
	 * Test that an exception is thrown when the statpro client does not
	 * implement VendorAPI_StatPro_Unique_IClient and the call is unique
	 * @expectedException RuntimeException
	 */
	public function testUniqueNoIUnique()
	{
		$event = "event";
		
		$this->statpro
			->expects($this->never())
			->method("hitStat");
			
		$this->statpro =
			$this->getMock(
			"VendorAPI_StatPro_Client",
			array(),
			array(),
			"",
			FALSE);
		
		$this->driver
			->expects($this->once())
			->method("getStatProClient")
			->will($this->returnValue($this->statpro));

		$params = array("event" => $event, "unique" => "true");
		
		$action = new VendorAPI_CFE_Actions_RecordEvent($params);

		$action->execute($this->context);
	}
	
	public function getAttribute($key)
	{
		return $this->attributes[$key];
	}
	
	public function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
}