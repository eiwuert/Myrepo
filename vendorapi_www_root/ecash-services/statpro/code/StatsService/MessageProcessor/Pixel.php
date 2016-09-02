<?php
/**
 * Message processor for StatPro event messages
 * Message properties:
 * 		bucket - Bucket name for stat
 * 		pixelURL - URL at which the pixel should be hit
 * 		date - Unix timestamp
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_MessageProcessor_Pixel
		implements StatsService_MessageProcessor_IMessageProcessor {

	/**
	 * (non-PHPdoc)
	 * @see code/StatsService/MessageProcessor/StatsService_MessageProcessor_IMessageProcessor#isValidMessage($message)
	 */
	public function isValidMessage(stdClass $message) {
		if (!isset($message->bucket) || empty($message->bucket)) {
			return FALSE;
		}
		if (!isset($message->pixelURL) || empty($message->pixelURL)) {
			return FALSE;
		}
		if (!isset($message->date) || !is_numeric($message->date)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/StatsService/MessageProcessor/StatsService_MessageProcessor_IMessageProcessor#processMessage($message)
	 */
	public function processMessage(stdClass $message) {
		$client = $this->getStatProClient($message->bucket);
		$client->urlEvent(
			StatsService_Util::getCustomerFromBucket($message->bucket),
			NULL,
			$message->pixelURL,
			$message->date);
	}
	
	/**
	 * Get a Stat Pro client based on the bucket name provided
	 * @param string $bucket
	 * @return statProClient
	 */
	protected function getStatProClient($bucket) {
		return new statProClient($bucket);
	}
}