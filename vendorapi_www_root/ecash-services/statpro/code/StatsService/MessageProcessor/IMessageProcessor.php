<?php
interface StatsService_MessageProcessor_IMessageProcessor
{
	/**
	 * Determine if the message provided is valid for the message processor
	 * @param stdClass $message
	 * @return boolean
	 */
	public function isValidMessage(stdClass $message);

	/**
	 * Process the supplied message
	 * @param stdClass $message
	 * @return void
	 */
	public function processMessage(stdClass $message);
}