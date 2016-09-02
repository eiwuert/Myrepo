<?php
/**
 * Abstract class representing an OLP message
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
abstract class OLP_Message_Container implements OLP_IMessage
{
	/** 
	 * @var String 
	 */
	protected $source;
	
	/**
	 * @var String 
	 */
	protected $destination;
	
	/**
	 * @var mixed
	 */
	protected $body;
	
	// Defined by OLP_IMessage but not defined in this class
	//abstract public function handle();
	
	/**
	 * The body of this message
	 * @param mixed $body
	 * @return void
	 */
	public function setBody($body)
	{
		$this->body = $body;
	}
	
	/**
	 * return the body of this message
	 * @return arrays
	 */
	public function getBody()
	{
		return $this->body;
	}
	
	/**
	 * Set the source of this message
	 * @param string $source
	 * @return void
	 */
	public function setSource($source)
	{
		$this->source = $source;
	}
	
	/**
	 * Set the destination of this message
	 * @param string $destination
	 * @return void
	 */
	public function setDestination($destination)
	{
		$this->destination = $destination;
	}
	
	/**
	 * Return a message container
	 * @return Message_Container_1
	 */
	public function getMessageContainer()
	{
		return new Message_Container_1($this->source, $this->destination, $this->body);
	}
	
	/**
	 * Create/populate this message from 
	 * a message container
	 * @param Message_Container_1 $container
	 * @return void
	 */
	public function createFromContainer(Message_Container_1 $container)
	{
		$this->setSource($container->getSrc());
		$this->setDestination($container->getDst());
		$this->setBody($container->getBody());	
	}
	
	
	/**
	 * Send this message
	 * @return void
	 */
	public function send()
	{
		Message_1::enqueue($this->getMessageContainer());
	}
	
}