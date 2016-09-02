<?php

/**
 * The paid_off Packet
 *
 * @package ECashCra
 */
class ECashCra_Packet_PaidOffCLH extends ECashCra_Packet_UpdateCLH
{
	/**
	 * The type of packet
	 *
	 * @return string
	 */
	protected function getPacketType()
	{
		return 'paid_off';
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
		$data->appendChild($xml->createElement('PAIDOFFDATE', htmlentities($this->application->getPaidOffDate())));
	}
}
?>