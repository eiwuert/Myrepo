<?php
class OLP_Message_FactoryTest extends PHPUnit_Framework_TestCase
{
	const SOURCE = 'mysupersource';
	const DESTINATION = 'mysuperdestination';
	const BODY = 'body';
	
	protected $_config;
	protected $_factory;
	protected $_mock_olp_message;
	
	public function setup()
	{
		$this->_config = $this->getMock('OLP_Message_Config');
		$this->_factory = new OLP_Message_Factory($this->_config);
		$this->_mock_olp_message = $this->getMock('OLP_Message_Container');
		
	}
	
	public function testGetMessageReturnsContainerWhenNotProvided()
	{
		$this->getMessageClassReturnMock($this->_config, 'message1');
		$this->assertThat($this->_factory->getMessage('message1'), $this->isInstanceOf('OLP_Message_Container'));
	}
	
	public function testGetMessageSetsSourceOnMessage()
	{
		$this->getMessageClassReturnMock($this->_config, 'message1');
		$this->_config->expects($this->once())
			->method('getMessageSource')
			->with('message1')
			->will($this->returnValue(self::SOURCE));
		$this->mockFactoryCreateMessage($this->mockMessageWithSource(self::SOURCE), $this->_config)
			->getMessage('message1');
	}
	
	public function testGetMessageSetsDestinationOnMessage()
	{
		$this->getMessageClassReturnMock($this->_config, 'message1');
		$this->_config->expects($this->once())
			->method('getMessageDestination')
			->with('message1')
			->will($this->returnValue(self::DESTINATION));
		$this->mockFactoryCreateMessage($this->mockMessageWithDestination(self::DESTINATION), $this->_config)
			->getMessage('message1');
	}
	
	public function testGetMessageFromContainerReturnsOLPMessage()
	{
		$container = $this->createContainer();
		$this->getMessageClassBySourceReturnFalse($this->_config, self::SOURCE);
		
		$this->assertThat(
			$this->_factory->getMessageFromContainer($container),
			$this->isInstanceOf('OLP_Message_Container'));
	}
	
	public function testGetMessageFromContainerCallsCreateFromContainer()
	{
		$container = $this->createContainer();
		$mock_message = $this->getMock('OLP_IMessage');
		$mock_message->expects($this->once())->method('createFromContainer')
			->with($container);
		$this->getMessageClassBySourceReturnFalse($this->_config, self::SOURCE);
		
		$factory = $this->mockFactoryCreateMessage($mock_message, $this->_config);
		
		$factory->getMessageFromContainer($container);
	}
	
	/**
	 * Create a new message container
	 * @return Message_Container_1
	 */
	protected function createContainer()
	{
		$container = new Message_Container_1(self::SOURCE, self::DESTINATION);
		$container->setBody(self::BODY);
		
		return $container;
	}
	
	/**
	 * Creates an OLP_IMessage that has is expecting
	 * setSource to be called with $expecting
	 * @param string $expecting
	 * @return OLP_IMessage
	 */
	protected function mockMessageWithSource($expecting)
	{
		$mock_message = $this->getMock('OLP_IMessage');
		$mock_message->expects($this->once())
			->method('setSource')
			->with($expecting);
		return $mock_message;
	}
	
	/**
	 * Creates an OLP_IMessage mock that has an expectation
	 * of setDestination being called atleast once with $expecting
	 * @param string $expecting
	 * @return OLP_IMessage
	 */
	protected function mockMessageWithDestination($expecting)
	{
		$mock_message = $this->getMock('OLP_IMessage');
		$mock_message->expects($this->once())
			->method('setDestination')
			->with($expecting);
		return $mock_message;
	}
	
	/**
	 * Mocks the factory, and the createFactory message to return
	 * $mock_message
	 * @param OLP_IMessage $mock_message
	 * @return OLP_Message_Factory
	 */
	protected function mockFactoryCreateMessage($mock_message, $config)
	{
		$factory = $this->getMock('OLP_Message_Factory', array('createMessage'), array($config));
		$factory->expects($this->once())
			->method('createMessage')
			->with(get_class($this->_mock_olp_message))
			->will($this->returnValue($mock_message));
		return $factory;
		
	}
	
	protected function getMessageClassBySourceReturnFalse($config, $source)
	{
		$config->expects($this->once())->method('getMessageClassBySource')
			->with($source)->will($this->returnValue(get_class($this->_mock_olp_message)));
	}
	
	protected function getMessageClassReturnMock($config, $message)
	{
		$config->expects($this->once())->method('getMessageClass')
			->with($message)->will($this->returnValue(get_class($this->_mock_olp_message)));
	}
}