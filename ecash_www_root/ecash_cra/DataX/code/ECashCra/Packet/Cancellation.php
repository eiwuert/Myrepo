<?php

/**
 * The Cancel Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_Cancellation extends ECashCra_Packet_Update 
{
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * @var string
	 */
	protected $reason;
	
	/**
	 * Creates a new cancel packet
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 * @param string $reason [unused]
	 */
	public function __construct(ECashCra_Data_Application $application, $date, $reason = NULL)
	{
		parent::__construct($application);
		$this->date = $date;
		$this->reason = $reason;
	}
	
	////
	// ECashCra_Packet_Update
	////
	
	/**
	 * The type of packet
	 *
	 * @return string
	 */
	protected function getPacketType()
	{
		return 'cancel';
	}
	
	/**
	 * Creates the cancel specific portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('CANCELDATE', htmlentities($this->date)));
		$data->appendChild($xml->createElement('CANCELREASON'));
	}
}

?>