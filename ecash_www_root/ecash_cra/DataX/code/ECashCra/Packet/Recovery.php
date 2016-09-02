<?php

/**
 * The Recovery Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_Recovery extends ECashCra_Packet_Update 
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
	 * @var float
	 */
	protected $remaining_balance;

	/**
	 * Creates a new recovery packet.
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 * @param float $amount
	 * @param float $remaining_balance
	 */
	public function __construct(ECashCra_Data_Application $application, $date, $amount, $remaining_balance)
	{
		parent::__construct($application);
		$this->date = $date;
		$this->amount = $amount;
		$this->remaining_balance = $remaining_balance;
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
		return 'recovery';
	}
	
	/**
	 * Creates the recovery specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('RECOVERYDATE', htmlentities($this->date)));
		$data->appendChild($xml->createElement('RECOVERYAMOUNT', htmlentities($this->amount)));
		$data->appendChild($xml->createElement('REMAININGBALANCE', htmlentities($this->remaining_balance)));
	}
}

?>