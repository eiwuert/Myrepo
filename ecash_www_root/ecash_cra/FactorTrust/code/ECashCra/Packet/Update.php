<?php

/**
 * The base update packet
 *
 * @package ECashCra packet for factor trust
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
abstract class ECashCra_Packet_Update extends ECashCra_Packet_Base 
{
	/**
	 * Used to create the individual update specific portion of the packet.
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	abstract protected function buildUpdateSection(DOMDocument $xml, DOMElement $data);
	
	////
	// ECashCra_Packet_Base
	////
	
	/**
	 * Builds the data portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @return DOMElement
	 */
	protected function buildData(DOMDocument $xml)
	{
		$data = $xml->createElement('LoanInfo');
		$data->appendChild($xml->createElement('Type', htmlentities($this->application->getType())));
		$data->appendChild($xml->createElement('TranDate', htmlentities($this->application->getTranDate())));
		$data->appendChild($xml->createElement('SSN', htmlentities($this->application->getSSN())));
		$data->appendChild($xml->createElement('AppID', htmlentities($this->application->getAppID())));
		$data->appendChild($xml->createElement('LoanID', htmlentities($this->application->getLoanID())));
		$data->appendChild($xml->createElement('LoanDate', htmlentities($this->application->getLoanDate())));
		$data->appendChild($xml->createElement('DueDate', htmlentities($this->application->getDueDate())));
		$data->appendChild($xml->createElement('PaymentAmt', htmlentities($this->application->getPaymentAmt())));
		$data->appendChild($xml->createElement('Balance', htmlentities($this->application->getBalance())));
		$data->appendChild($xml->createElement('ReturnCode', htmlentities($this->application->getReturnCode())));
		$data->appendChild($xml->createElement('RollOverRef', htmlentities($this->application->getRollOverRef())));
		$data->appendChild($xml->createElement('RollOverNumber', htmlentities($this->application->getRollOverNumber())));
		$data->appendChild($xml->createElement('BankABA', htmlentities($this->application->getBankABA())));
		$data->appendChild($xml->createElement('BankAcct', htmlentities($this->application->getBankAcct())));
		$data->appendChild($xml->createElement('ProductType', htmlentities($this->application->getProductType())));
		
		return $data;
	}
}

?>