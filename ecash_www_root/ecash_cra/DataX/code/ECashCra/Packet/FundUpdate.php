<?php

/**
 * The Fund Update Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_FundUpdate extends ECashCra_Packet_Update 
{
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
		return 'fund_update';
	}
	
	/**
	 * Creates the fund update specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('FUNDDATE', htmlentities($this->application->getFundDate())));
		$data->appendChild($xml->createElement('FUNDAMOUNT', htmlentities($this->application->getFundAmount())));
		$data->appendChild($xml->createElement('FUNDFEE', htmlentities($this->application->getFirstServiceChargeAmount())));
		$data->appendChild($xml->createElement('DUEDATE', htmlentities($this->application->getFirstPaymentDate())));
	}
}
?>