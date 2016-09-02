<?php

/**
 * The recovery Packet
 *
 * @package ECashCra
 */
class ECashCra_Packet_RecoveryCLH extends ECashCra_Packet_UpdateCLH
{
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
	 * Creates the fund update specific part of the packet
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	protected function buildUpdateSection(DOMDocument $xml, DOMElement $data)
	{
		$data->appendChild($xml->createElement('RECOVEREDCHARGEDOFFDATE', htmlentities($this->application->getRecoveryDate())));
		$data->appendChild($xml->createElement('RECOVEREDCHARGEDOFFAMOUNT', htmlentities($this->application->getRecoveryAmount())));
	}
}
?>