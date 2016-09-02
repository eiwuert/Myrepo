<?php
	/**
	 *
	 * Factory class for Condor's Transports.
	 *
	 * @author Andrew Minerd
	 * @date 2006-03-20
	 *
	 */
	class Transport_Manager
	{

		const METHOD_EMAIL = 'EMAIL';
		const METHOD_FAX = 'FAX';

		/**
		 *
		 * Returns a class implementing the Transport interface
		 * for the given transportation $method.
		 *
		 * @param $method string Transport method (i.e., FAX)
		 * @param $mode string Current mode (i.e., LIVE)
		 * @param string $api_auth Condor API authentication credentials
		 * @param object $condorObject THe condor object doing the transporting.
		 *
		 */
		public static function Get_Transport($method, $mode, $api_auth, $condorObject, $link)
		{

			$transport = FALSE;
			switch (strtoupper($method))
			{
				case self::METHOD_EMAIL:
					require_once('transport_email.php');
					$transport = new Transport_Email($mode, $api_auth, $condorObject, $link);
					break;
				case self::METHOD_FAX:
					require_once('transport_efax.php');
					$transport = new Transport_Efax($mode, $api_auth, $condorObject, $link);
					break;

			}

			return $transport;

		}

		/**
		 *
		 * Validates the given $recipient for the
		 * specified transportation $method.
		 *
		 * @param $method string Transport method (i.e., FAX)
		 * @param $recipient string A single recipient
		 *
		 *
		 *
		 */
		public static function Valid_Recipient($method, $recipient)
		{

			$valid = FALSE;

			switch (strtoupper($method))
			{

				case self::METHOD_EMAIL:
					$valid = Transport_OLE::Valid_Recipient($recipient);
					break;

				case self::METHOD_FAX:
					$valid = Transport_HylaFax::Valid_Recipient($recipient);
					break;

			}

			return $valid;

		}

	}

?>
