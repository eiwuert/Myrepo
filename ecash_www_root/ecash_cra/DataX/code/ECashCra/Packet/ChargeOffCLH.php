<?php

/**
 * The chargeoff Packet
 *
 * @package ECashCra
 */
class ECashCra_Packet_ChargeOffCLH extends ECashCra_Packet_UpdateCLH
{
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
	 * Creates the fund update specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('CHARGEDOFFDATE', htmlentities($this->application->getChargeOffDate())));
		$data->appendChild($xml->createElement('CHARGEDOFFAMOUNT', htmlentities($this->application->getBalance())));
	}
}
?>