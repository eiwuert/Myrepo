<?php
/**
 * Factory to aid in creating new messages for OLP
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLP_Message_Factory
{
	/** 
	 * @var OLP_Message_Config 
	 */
	protected $config;
	
	/**
	 * 
	 * @param OLP_Message_Config $config
	 */
	public function __construct(OLP_Message_Config $config)
	{
		$this->config = $config;
	}
	
	/**
	 * Return a message object based on the name of the
	 * message
	 * @param string $message
	 * @return OLP_IMessage
	 */
	public function getMessage($message)
	{
		$c = $this->config->getMessageClass($message);
		$message_object = $this->createMessage($c);
		$message_object->setSource($this->config->getMessageSource($message));
		$message_object->setDestination($this->config->getMessageDestination($message));
		return $message_object;
	}
	
	/**
	 * Create a new message object based on the source provided
	 * @param Message_Container_1 $container
	 * @return OLP_IMessage
	 */
	public function getMessageFromContainer(Message_Container_1 $container)
	{
		$message_object = $this->createMessage($this->config->getMessageClassBySource($container->getSrc()));
		//var_dump(spl_object_hash($container));
		$message_object->createFromContainer($container);
		return $message_object;
	}
	
	/**
	 * creates a new OLP_IMessage class
	 * @param mixed $c
	 * @return OLP_IMessagel
	 */
	protected function createMessage($c)
	{
		if ($c === FALSE)
		{
			throw new InvalidArgumentException("No message class.");
		}
		return new $c;
	}
}