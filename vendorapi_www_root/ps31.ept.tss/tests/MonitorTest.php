<?php
class MonitorTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we storeStatuses stores the correct information and that we can then retrieve it from
	 * getRunningScrubbers.
	 *
	 * @return void
	 */
	public function testStoreStatuses()
	{
		$host = 'my_host';
		$xml = <<<XML
<?xml version="1.0" ?>
<monitor>
	<scrubber>
		<module>clk</module>
		<last_update>2009-06-03 12:00:00</last_update>
	</scrubber>
</monitor>
XML;
		
		$reader = new XMLReader();
		$reader->XML($xml);
		
		$monitor = new Monitor();
		$monitor->storeStatuses($reader, $host);
		
		$array = array(
			$host => array(
				'clk' => '2009-06-03 12:00:00'
			)
		);
		
		$this->assertEquals($array, $monitor->getRunningScrubbers());
	}
}
