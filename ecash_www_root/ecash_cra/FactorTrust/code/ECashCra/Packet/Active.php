<?php

/**
 * The Active Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_Active extends ECashCra_Packet_Update
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
		return 'active';
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
	}
}
?>