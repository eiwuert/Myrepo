<?php
/**
 * ECash_Documents_Email
 * Email Transport for Condor Docs
 * 
 */
class ECash_Documents_Upload implements ECash_Documents_ITransport
{
	protected $file;
	protected $prpc;
	protected $sender;

	public function __construct($file = null, $cover_sheet = null, $sender = null)
	{
		$this->email = $email;
		$this->senders = $sender;
		$this->file = $file;
		$this->cover_sheet = $cover_sheet;
		$this->prpc = new ECash_Documents_Condor();
	}
	/**
	 * This sets the prpc used in this class
	 * 
	 * @param object
	 */
	public function setPrpc($prpc)
	{
		$this->prpc = $prpc;
	}
	/**
	 * send
	 * 
	 * @param ECash_Documents_Document $doc
	 * 
	 * @return bool return if document was successfully sent
	 * 
	 */
	public function upload(ECash_Documents_Document $doc, $body_name = null)
	{
		$file = strtolower($this->file);
		$arch_id = $doc->getArchiveID();
		
		if(empty($arch_id))
			throw new exception('No Archive ID in document');
		if(empty($this->file))
			throw new exception('No File Set');
		$transporttypes = $doc->getTransportTypes();
		if(empty($transporttypes['upload']))
			throw new exception('Invalid transport type for Document');

		if(!empty($body_name))
		{
			$tokens = $doc->getTokens();
			$app = $doc->getApp();
			//$condor_doc = $this->prpc->Create_As_Attachment($body_name, array($arch_id), "application/pdf", $tokens, TRUE, $app->getID(), $app->getTrackId(), null);						
		
			// Create Document
			$a_id = $this->prpc->Incoming_Document('UPLOAD',
							   $document['FROM'], 
							   $document['TO'], 
							   $document['TYPE'], 
							   $document['CONTENT'],
							   1, //Num of pages
							   $document['SUBJECT'],
							   $document['ID']);
                                      
			//Insert PDF
			file_get_contents($this->file);
			if(!$this->prpc->Create_As_Email_Attachment($a_id, $part['TYPE'], $part['CONTENT'], $part['SUBJECT'])) {
			}
 		
		}
	}
	/**
	 * getEmail
	 * 
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}
	/**
	 * getType
	 * 
	 * @return string
	 */
	public function getType()
	{
		return 'upload';
	}
	/**
	 * setEmail
	 * 
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->file = $file;
	}
	/**
	 * setSender
	 * 
	 * @param string $sender
	 */
	public function setSender($sender)
	{
		$this->sender = $sender;
	}
	/**
	 * getSender
	 * 
	 * @return string
	 */
	public function getSender()
	{
		return $this->sender;
	}

}


?>
