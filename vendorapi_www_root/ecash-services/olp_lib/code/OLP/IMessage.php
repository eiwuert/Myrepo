<?php
interface OLP_IMessage 
{
	/**
	 * Set the source of this message
	 * @param string $source
	 * @return void
	 */
	public function setSource($source);
	
	/**
	 * Set the destination of this message
	 * @param string $destination
	 * @return void
	 */
	public function setDestination($destination);
	
	/**
	 * Return a message container
	 * @return Message_Container_1
	 */
	public function getMessageContainer();
	
	/**
	 * Create/populate this message from 
	 * a message container
	 * @param Message_Container_1 $container
	 * @return void
	 */
	public function createFromContainer(Message_Container_1 $container);
	
	/**
	 * Processes this message really
	 * @return void
	 */
	public function handle();
	
	/**
	 * Send this message
	 * @return void
	 */
	public function send();
}