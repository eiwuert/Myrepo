<?php
/**
 * ECash_Documents_Email
 * Email Transport for Condor Docs
 * 
 */
class ECash_Documents_Email implements ECash_Documents_ITransport
{
	protected $email;
	protected $sender;
	
	public function __construct($email = null, $sender = null)
	{
		$this->email = $email;
		$this->senders = $sender;
	}
	/**
	 * send
	 * 
	 * @param ECash_Documents_Document $doc
	 * 
	 * @return bool return if document was successfully sent
	 * 
	 */
	public function send(ECash_Documents_Document $doc)
	{
		$arch_id = $doc->getArchiveID();
		
		if(empty($arch_id))
			throw new exception('No Archive ID in document');
		if(empty($this->email))
			throw new exception('No Email Set');
		$transporttypes = $doc->getTransportTypes();
		if(empty($transporttypes['email']))
			throw new exception('Invalid transport type for Document');
		
		$recp = array();
		$recp['email_primary'] = $this->email;
		return ECash_Documents_Condor::Prpc()->Send($arch_id, $recp, 'EMAIL',  NULL, !empty($this->sender) ? $this->sender : null);
		
		
	}
	/**
	 * send
	 * 
	 * @param ECash_Documents_DocumentPackage $package
	 * 
	 * @return bool return if package was successfully sent
	 * 
	 */
	public function sendPackage(ECash_Documents_DocumentPackage $package)
	{
		$arch_ids = array();
		
		foreach($package as $doc)
		{
			$arch_ids[] = $doc->getArchiveID();
			$tokens = $doc->getTokens();
			$app = $doc->getApp();
		}
		$body_name = $package->getBodyName();
		
		if(empty($arch_ids))
			throw new exception('No Archive ID in document');
		if(empty($this->email))
			throw new exception('No Email Set');
		if(empty($body_name))
			throw new exception('No Package Body Set');
		if(empty($app))
			throw new exception('No Application Set');
		
		$condor_doc = ECash_Documents_Condor::Prpc()->Create_As_Attachment($body_name, $arch_ids, "application/pdf", $tokens, TRUE, $app->getID(), $app->getTrackId(), null);						
			
		$recp = array();
		$recp['email_primary'] = $this->email;
		return ECash_Documents_Condor::Prpc()->Send($condor_doc['archive_id'], $recp, 'EMAIL',  NULL, !empty($this->sender) ? $this->sender : null);

	}
	/**
	 * getEmail
	 * 
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}
	/**
	 * getType
	 * 
	 * @return string
	 */
	public function getType()
	{
		return 'email';
	}
	/**
	 * setEmail
	 * 
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
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