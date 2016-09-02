<?php
/**
 * Very basic message server. Just loads the message
 * from the factory and then dumps it out.
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLP_Message_RpcServer
{
	/** 
	 * @var OLP_Message_Factory 
	 */
	protected $message_factory;
	
	/**
	 *  @var OLP_Factory 
	 */
	protected $olp_factory;
	
	/**
	 * 
	 * @param OLP_Message_Factory $message_factory
	 * @param OLP_Factory $olp_factory
	 * @return void
	 */
	public function __construct(OLP_Message_Factory $message_factory, OLP_Factory $olp_factory)
	{
		$this->message_factory = $message_factory;
		$this->olp_factory = $olp_factory;
	}
	
	/**
	 * Create the message, and handle it.
	 * 
	 * @param Message_Container_1 $container
	 * @return void
	 */
	public function consumeMessage(Message_Container_1 $container)
	{
		$message = $this->message_factory->getMessageFromContainer($container);
		
		// Injects an olp factory if the message needs 
		// one. 
		if (method_exists($message, 'setOlpFactory'))
		{
			$message->setOlpFactory($this->olp_factory);
		}
		$message->handle();
	}
}
