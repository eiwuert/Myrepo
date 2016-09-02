<?php
/**
 * Transport for sending emails through a mail server for relay
 * 
 * Relays emails through a mail server
 *
 * @author Jason Gabriele
 */
require_once 'condor_transport.php';

class Transport_Email implements Condor_Transport
{

	const TEMP_DIR = '/tmp';
	
	private $mime;

	protected $mode;
	protected $api_auth;
	protected $condorObject;
	protected $db_link;

	public function __construct($mode, $api_auth, $condorObject, $link)
	{
		$debug = FALSE;
		$this->condorObject = $condorObject;
		$this->mode = $mode;
		$this->api_auth = $api_auth;
		$this->db_link = $link;
	}
	


	/**
	 * Just inserts the document into the Mail_Queue so the Send stuff 
	 * can attempt and send it when it gets a chance.
	 *
	 * @param array $recipient
	 * @param Document $document
	 * @param int $dispatch_id
	 * @param string $cover_sheet
	 * @return int
	 */
	public function Send($recipient, Document &$document, $dispatch_id, $from, $cover_sheet = NULL)
	{
		//Queue it up to be sent
		$doc_id = $document->Get_Archive_Id();
		if($this->Valid_Recipient($recipient['email_primary']))
		{
			$queue = new Mail_Queue($this->mode,$this->db_link);
			return $queue->Insert_Queue($doc_id, $from, $dispatch_id, $document->Get_Send_Priority());
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
 	 * The worlds worst email validation
	 *
 	 * @param unknown_type $recipient
 	 * @return boolean
 	 */
	public static function Valid_Recipient($recipient)
	{

		return (strpos($recipient, '@') !== FALSE);

	}
}
?>