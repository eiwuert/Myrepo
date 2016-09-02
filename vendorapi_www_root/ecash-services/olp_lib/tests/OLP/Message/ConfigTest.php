<?php
/**
 * Test the configurator of the messages
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLP_Message_ConfigTest extends PHPUnit_Framework_TestCase
{
	protected $_config;
	
	public function setup()
	{
		$this->_config = new OLP_Message_Config();
	}
	
	public function testGetConfigFileReturnsSetConfigFile()
	{
		$this->_config->setConfigFile(__FILE__);
		$this->assertEquals(__FILE__, $this->_config->getConfigFile());
	}
	
	public function testDefaultConfigFileIsReturned()
	{
		$f = $this->_config->getConfigFile();
		$this->assertTrue(!empty($f));
	}
	
	public function testSetConfigFileToNonexistentFileThrowsException()
	{
		$this->setExpectedException('InvalidArgumentException');
		$this->_config->setConfigFile('/tmp/somefilethatIreallyhopedoesnotexistorthiswholethingwillberuined');
	}
	
	public function testGetMessageClassByName() {
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertEquals('TheTestmessageClass', $this->_config->getMessageClass('message1'));
	}
	
	public function testGetMessageSourceByName()
	{
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertEquals('source1', $this->_config->getMessageSource('message1'));
	}
	
	public function testGetMessageClassBySourceReturnsClass()
	{
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertEquals('TheTestmessageClass', $this->_config->getMessageClassBySource('source1'));
	}
	
	public function testGetMessageClassBySourceReturnsFalseForNotfound()
	{
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertFalse($this->_config->getMessageClassBySource('somesourcethatIhopedoesNotExIst'));
	}

	
	/**
	 * @dataProvider messageOverrideDestinationProvider
	 */
	public function testGetMessageDestinationWorks($environ, $destination)
	{
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertEquals($destination, $this->_config->getMessageDestination('message2', $environ));
	}
	
	/**
	 * @dataProvider environmentDestinationProvider
	 */
	public function testThatGetDestinationGivesCorrectEnvironment($environ, $expected)
	{
		$this->_config->setConfigFile($this->getTestConfigFile());
		$this->assertEquals($expected, $this->_config->getDestination($environ));
	}
	
	public static function environmentDestinationProvider()
	{
		return array(
			array('live', 'live.somemessageserver.tss'),
			array('rc', 'rc.somemessageserver.tss'),
			array(NULL, 'live.somemessageserver.tss')
		);
	}
	
	public static function messageOverrideDestinationProvider()
	{
		return array(
			array('live', 'live.message2.tss'),
			array(NULL, 'live.message2.tss'),
			array('rc', 'rc.somemessageserver.tss')
		);
	}
	
	protected function getTestConfigFile()
	{
		return dirname(__FILE__).DIRECTORY_SEPARATOR.'_fixtures'.DIRECTORY_SEPARATOR.'testconfig.xml';
	}
}