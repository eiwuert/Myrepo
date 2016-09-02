<?php

/**
 * The interface for responses from CRA
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface ECashCRA_IPacketResponse
{
	/**
	 * Loads an xml string into the response.
	 *
	 * @param string $xml_string
	 * @return null
	 * @throws ECashCRA_PacketResponse_Exception
	 */
	public function loadXml($xml_string);
}

?>
