<?php

class Date_DateTimeTest extends PHPUnit_Framework_TestCase
{
	public function testSerialize()
	{
		$date = new Date_DateTime_1('now');
		$new = unserialize(serialize($date));
		$this->assertEquals((string)$date, (string)$new);
	}

	public function testSerializeTZ()
	{
		$date = new Date_DateTime_1('now', new DateTimeZone('America/Chicago'));
		$new = unserialize(serialize($date));
		$this->assertEquals((string)$date, (string)$new);
	}

	public function testSerializeAZNoDST()
	{
		$date = new Date_DateTime_1('2008-01-01', new DateTimeZone('America/Phoenix'));
		$new = unserialize(serialize($date));
		$new->modify('+5 months');
		$date->modify('+5 months');
		$this->assertEquals((string)$date, (string)$new);
	}

	public function testSerializeAZDST()
	{
		$date = new Date_DateTime_1('2008-01-01', new DateTimeZone('America/Shiprock'));
		$new = unserialize(serialize($date));
		$new->modify('+5 months');
		$date->modify('+5 months');
		$this->assertEquals((string)$date, (string)$new);
	}
}

?>

