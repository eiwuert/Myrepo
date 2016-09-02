<?php

/**
 * The Past Due Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_PastDue extends ECashCra_Packet_Update 
{
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * Create a past due packet
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 */
	public function __construct(ECashCra_Data_Application $application, $date)
	{
		parent::__construct($application);
		$this->date = $date;
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
		return 'past_due';
	}
	
	/**
	 * Creates the paid off specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('PASTDUEDATE', htmlentities($this->date)));
	}
}
?>