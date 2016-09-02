<?php

/**
 * Test harness for the OLPBlackbox_Event class.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Events
 */
class OLPBlackbox_EventTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that the OLPBlackbox event type implements the right interface.
	 * @return void
	 */
	public function testInterface()
	{
		$this->assertTrue(
			$this->freshEvent('event_type') instanceof OLP_IEvent,
			'OLPBlackbox_Event is supposed to implement OLP_IEvent'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Return a fresh event for testing with.
	 * @return OLPBlackbox_Event
	 */
	protected function freshEvent($event_type, $attrs = array())
	{
		return new OLPBlackbox_Event($event_type, $attrs);
	}
}

?>