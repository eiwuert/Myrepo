<?php
/**
 * ECash_Documents_DocumentPackage
 * represents a Packaged Document
 * 
 */
class ECash_Documents_DocumentPackage extends ECash_Documents_DocumentList
{
	protected $name;
	protected $name_short;
	protected $package_body;
	public function __construct(array $documents, $name, $name_short, $package_body)
	{
		$this->name = $name;
		$this->name_short = $name_short;
		$this->documents = $documents;
		$this->package_body = $package_body;
		$this->pointer = 0;
		
	}
	/**
	 * getName
	 * 
	 * @return string 
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * getNameShort
	 * 
	 * @return string 
	 */
	public function getNameShort()
	{
		return $this->name_short;
	}
	/**
	 * getName
	 * 
	 * @return string 
	 */
	public function getBodyName()
	{
		return $this->package_body;
	}
	/**
	 * send
	 * 
	 * @param ECash_Documents_ITransport $transport
	 * @param int $agent_id
	 * @return bool returns if package was sent and saved successfully 
	 */
	public function send(ECash_Documents_ITransport $transport, $agent_id = null)
	{
		if($transport->getType() != 'email')
			throw new exception('Invalid Transport to send Package');
		$email = $transport->getEmail();
		if(empty($email))
			throw new exception('No Email Set');
			
		if($transport->sendPackage($this))
		{
			return $this->save($transport->getType(), 'sent', $email, $agent_id);
		}
		else
		{
			return false;
		}
	}
	/**
	 * getTransportTypes
	 * 
	 * 
	 * @return ECash_Documents_ITransport returns valid transport objects for package
	 */
	public function getTransportTypes()
	{
		return array('email' => new ECash_Documents_Email());
	}
	/**
	* save
	* 
	* @param string $transport_method
	* @param string $event
	* @param string $sent_to
	* @param int $agent_id
	* @param bool $signed
	* 
	* @return bool returns whether record is saved or not
	*/
	public function save($transport_method, $event, $sent_to, $agent_id = null, $signed = false)
	{
		$return = true;
		foreach($this->documents as $doc)
		{
			if(!$doc->save($transport_method, $event, $sent_to, $agent_id, $signed))
				$return = false;
		}
		return $return;
	}
	
	
}



?>
