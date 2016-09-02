<?php

/**
 * Base Packet Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
abstract class ECashCra_Packet_Base implements ECashCra_IPacket 
{
	/**
	 * @var ECashCra_Data_Application
	 */
	protected $application;
	
	/**
	 * Creates a new packet using the given application
	 *
	 * @param array $application
	 */
	public function __construct($application)
	{
		$this->application = $application;
	}
	
	/**
	 * Returns the type of the packet.
	 *
	 * @return string
	 */
	abstract protected function getPacketType();
	
	/**
	 * Returns the XML for the packet.
	 *
	 * @return DOMDocument
	 */
	protected function buildXml()
	{
		$xml = new DOMDocument('1.0', 'utf8');
		$cra_inquiry = $xml->createElement('CRAINQUIRY');
		$cra_inquiry->appendChild($this->buildQuery($xml));
		
		$xml->appendChild($cra_inquiry);
		
		return $xml;
	}
	
	/**
	 * Builds the query portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @return DOMElement
	 */
	protected function buildQuery(DOMDocument $xml)
	{
		$query = $xml->createElement('QUERY');
		$query->appendChild($xml->createElement('TYPE', $this->getPacketType()));
		$query->appendChild($xml->createElement('EXTERNALID', $this->application->getApplicationId()));
		$query->appendChild($this->buildData($xml));
		
		return $query;
	}
	
	/**
	 * Builds the data portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @return DOMElement
	 */
	abstract protected function buildData(DOMDocument $xml);
	
	////
	// ECashCra_IPacket
	////
	
	/**
	 * Returns the DOM XML object for the packet.
	 *
	 * @return DOMDocument
	 */
	public function getXml()
	{
		return $this->buildXml();
	}
	
}

?>