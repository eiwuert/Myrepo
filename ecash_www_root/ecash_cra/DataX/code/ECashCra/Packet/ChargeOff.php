<?php

/**
 * The Charge Off Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_ChargeOff extends ECashCra_Packet_Update 
{
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * @var float
	 */
	protected $amount;
	
	/**
	 * Creates a new charge off packet.
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 * @param float $amount
	 */
	public function __construct(ECashCra_Data_Application $application, $date, $amount)
	{
		parent::__construct($application);
		$this->date = $date;
		$this->amount = $amount;
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
		return 'chargeoff';
	}
	
	/**
	 * Creates the charge off specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('CHARGEOFFDATE', htmlentities($this->date)));
		$data->appendChild($xml->createElement('CHARGEOFFAMOUNT', htmlentities($this->amount)));
	}
}

?>