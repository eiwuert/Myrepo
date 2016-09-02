<?php
/**
 * ECash_Documents_Fax
 * Fax Transport for Condor Docs
 * 
 */
class ECash_Documents_Fax implements ECash_Documents_ITransport
{
	protected $phone_number;
	protected $cover_sheet;
	protected $sender;
	
	public function __construct($phone_number = null, $cover_sheet = null, $sender = null)
	{
		$this->phone_number = $phone_number;
		$this->senders = $sender;		
		$this->cover_sheet = $cover_sheet;
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
		if(empty($this->phone_number))
			throw new exception('No Phone Number Set');
		if(empty($this->cover_sheet))
			throw new exception('No Cover Sheet Set');
		$transporttypes = $doc->getTransportTypes();
		if(empty($transporttypes['fax']))
			throw new exception('Invalid transport type for Document');
		
		$recp = array();
		$recp['fax_number'] = $this->phone_number;
		$send_arr = $doc->getTokens();
		$send_arr['template_name'] = $this->cover_sheet;
		return ECash_Documents_Condor::Prpc()->Send($arch_id, $recp,  'FAX', $send_arr, isset($data->SenderName) ? $data->SenderName : null);
			
	}
	/**
	 * getPhoneNumber
	 * 
	 * @return string
	 */
	public function getPhoneNumber()
	{
		return $this->phone_number;
	}
	/**
	 * getType
	 * 
	 * @return string
	 */
	public function getType()
	{
		return 'fax';
	}
	/**
	 * setPhoneNumber
	 * 
	 * @param string $phone_number
	 */
	public function setPhoneNumber($phone_number)
	{
		$this->phone_number = $phone_number;
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
	/**
	 * setCoverSheet
	 * 
	 * @param string $cover_sheet
	 */
	public function setCoverSheet($cover_sheet)
	{
		$this->cover_sheet = $cover_sheet;	
	}
	/**
	 * getCoverSheet
	 * 
	 * @return string
	 */
	public function getCoverSheet()
	{
		return $this->cover_sheet;
	}

}


?>