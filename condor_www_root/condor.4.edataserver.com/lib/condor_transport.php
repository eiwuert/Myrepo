<?php

	/**
	 *
	 * Interface for Condor "transports" (i.e., the classes
	 * that actually send the documents!)
	 *
	 * @author Andrew Minerd
	 * @date 2006-03-20
	 *
	 */
	interface Condor_Transport
	{

		/**
		 *
		 * @param string $mode The mode of operation
		 * @param string $api_auth The authentication credentials for the Condor API
		 * @param object $condorObject The Condor object
		 *
		 */
		public function __construct($mode, $api_auth,$condorObject, $link);

		/**
		 *
		 * @param $recipient string A single valid recipient for the given transport
		 * @param $document Document The document that's being sent
		 * @param $dispatch_id int ID of the dispatch record
		 * @param string $from
		 * @param string $cover_sheet
		 *
		 */
		public function Send($recipient, Document &$document, $dispatch_id, $from, $cover_sheet = NULL);

		/**
		 *
		 * @param $recipient string A single valid recipient for the given transport
		 * @return boolean Valid or not.
		 *
		 */
		public static function Valid_Recipient($recipient);

	}

?>