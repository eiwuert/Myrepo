<?php
/**
 * Message processor for StatPro event messages
 * Message properties:
 * 		bucket - Bucket name for stat
 * 		pageId - Page ID
 * 		promoId - Promo ID
 * 		track - Track key
 * 		event - Name of event
 * 		date - Unix timestamp
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class StatsService_MessageProcessor_Event
		implements StatsService_MessageProcessor_IMessageProcessor {

	/**
	 * (non-PHPdoc)
	 * @see code/StatsService/MessageProcessor/StatsService_MessageProcessor_IMessageProcessor#isValidMessage($message)
	 */
	public function isValidMessage(stdClass $message) {
		if (!isset($message->bucket) || empty($message->bucket)) {
			return FALSE;
		}
		if (!isset($message->pageId) || !is_numeric($message->pageId)) {
			return FALSE;
		}
		if (!isset($message->promoId) || !is_numeric($message->promoId)) {
			return FALSE;
		}
		if (!isset($message->track) || empty($message->track) || strlen($message->track) > 27) {
			return FALSE;
		}
		if (!isset($message->event) || empty($message->event)) {
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
	
		$space_key_def = array(
			'page_id' => $message->pageId,
			'promo_id' => $message->promoId,
			'promo_sub_code' => isset($message->subCode) ? $message->subCode : '',
		);

		$client = $this->getStatProClient($message->bucket);

		$space_key = $client->getSpaceKey($space_key_def);
		$client->recordEvent(
			$message->track,
			$space_key,
			$message->event,
			$message->date);
	}

	/**
	 * Get a Stat Pro client based on the bucket name provided
	 * @param string $bucket
	 * @return Stats_StatPro_Client_1
	 */
	protected function getStatProClient($bucket) {
		return new Stats_StatPro_Client_1(
			$bucket,
			StatsService_Util::getCustomerFromBucket($bucket),
			null);
	}
}
		