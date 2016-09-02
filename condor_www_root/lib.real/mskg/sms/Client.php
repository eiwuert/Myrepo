<?php
require_once('/virtualhosts/lib5/prpc2/client.php');

set_time_limit(0);

/**
 * MSKG API
 *
 * All functions return an array like this:
 * array($error, $warnings_array, $result[, $result2])
 *
 * $error:
 *   if null, there was no error.
 *   otherwise, it's: array($error_number, $error_description)
 * $warnings_array:
 *   if null, there were no warnings.
 *   otherwise, it's: array(array($warning_number, $warning_description), ...)
 * 
 * @package MSKG
 * @subpackage API
 * @author Matt Wiseman, Tim Styer
 * @version $Id: Client.php 3238 2007-10-02 22:48:02Z toddh $
 * 
 */
class mskg_api
{
	private $conn;

	/**
	 * Basic Constructor.
	 *
	 * @param string $env
	 */
	function __construct($env)
	{
		switch ($env)
		{
			case 'LIVE':
				$this->conn = new Prpc_Client2('prpc://www.mskgapi.com/mskg_sys.php');
				break;
			case 'RC':
				$this->conn = new Prpc_Client2('prpc://rc.mskgapi.com/mskg_sys.php');
				break;
			case 'DEV':
				$this->conn = new Prpc_Client2('prpc://mskg.ds78.tss/mskg_sys.php');
				break;
		}
		$this->conn->setPrpcDieToFalse();
	}
	/**
	 * Single OptIn a user.
	 *
	 * @param int $campaign_id
	 * @param int $cell
	 * @param int/str $message_key_or_id
	 * @param array $tokens
	 * @return array array($error, $warnings_array, $transaction_id, $message_sent)
	 */
	public function optin_confirmed($campaign_id, $cell, $message_key_or_id=null, $tokens=array())
	{
		return $this->conn->optin_confirmed($campaign_id, $cell, $message_key_or_id, $tokens);
	}

	/**
	 * Double OptIn a user.
	 *
	 * @param int $campaign_id
	 * @param int $cell
	 * @param int/str $message_key_or_id
	 * @param array $tokens
	 * @return array array($error, $warnings_array, $transaction_id, $message_sent)
	 */
	public function optin_pending($campaign_id, $cell, $message_key_or_id=null, $tokens=array())
	{
		return $this->conn->optin_pending($campaign_id, $cell, $message_key_or_id, $tokens);
	}

	/**
	 * Opt Out a user.
	 *
	 * @param int $campaign_id
	 * @param int $cell
	 * @param int/str $message_key_or_id
	 * @param array $tokens
	 * @return array array($error, $warnings_array, $transaction_id, $message_sent)
	 */
	public function optout($campaign_id, $cell, $message_key_or_id=null, $tokens=array())
	{
		return $this->conn->optout($campaign_id, $cell, $message_key_or_id, $tokens);
	}

	/**
	 * Send a message to a user.
	 *
	 * @param int $campaign_id
	 * @param int $cell
	 * @param int/str $message_key_or_id
	 * @param array $tokens
	 * @return array array($error, $warnings_array, $transaction_id, $message_sent)
	 */
	public function msg($campaign_id, $cell, $message_key_or_id, $tokens=array())
	{
		return $this->conn->msg($campaign_id, $cell, $message_key_or_id, $tokens);
	}

	/**
	 * Send a message to an OPTED-OUT user.
	 * ** USE SPARINGLY (read: never) **
	 *
	 * @param int $campaign_id
	 * @param int $cell
	 * @param int/str $message_key_or_id
	 * @param array $tokens
	 * @return array array($error, $warnings_array, $transaction_id, $message_sent)
	 */
	public function force_msg($campaign_id, $cell, $message_key_or_id, $tokens=array())
	{
		return $this->conn->force_msg($campaign_id, $cell, $message_key_or_id, $tokens);
	}

	/**
	 * Verify a number's validity and find the carrier.
	 *
	 * @param int $cell
	 * @return array array($error, $warnings_array, $carrier)
	 */
	public function verify_number($cell)
	{
		return $this->conn->verify_number($cell);
	}

	/**
	 * Get an array of associative arrays of message information that can be sent.
	 *
	 * @param int $company_id
	 * @return array array($error, $warnings_array, $messages)
	 */
	public function get_messages($company_id)
	{
		return $this->conn->get_messages($company_id);
	}

	/**
	 * Get an array of associative arrays of campaign information that can be used.
	 *
	 * @param int $company_id
	 * @return array array($error, $warnings_array, $messages)
	 */
	public function get_campaigns($company_id)
	{
		return $this->conn->get_campaigns($company_id);
	}

	/**
	 * Get the message history for a phone number.
	 *
	 * @param int $company_id
	 * @param int $cell
	 * @return array array($error, $warnings_array, $cell_message_history)
	 */
	public function get_cell_message_history($company_id, $cell)
	{
		return $this->conn->get_cell_message_history($company_id, $cell);
	}

	/**
	 * Get the history of a transaction.
	 * Pass in the transaction id obtained from one of these functions:
	 *   optin_confirmed(), optin_pending(), optout(), msg(), or force_msg()
	 *
	 * @param int $transaction_id
	 * @return array array($error, $warnings_array, $transaction_history)
	 */
	public function get_transaction_history($transaction_id)
	{
		return $this->conn->get_transaction_history($transaction_id);
	}
}