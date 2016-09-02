<?php

/**
 * The base update packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
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
		$data = $xml->createElement('DATA');
		$data->appendChild($xml->createElement('NAMEFIRST', htmlentities($this->application->getPersonal()->getNameFirst())));
		$data->appendChild($xml->createElement('NAMEMIDDLE', htmlentities($this->application->getPersonal()->getNameMiddle())));
		$data->appendChild($xml->createElement('NAMELAST', htmlentities($this->application->getPersonal()->getNameLast())));
		$data->appendChild($xml->createElement('STREET1', htmlentities($this->application->getPersonal()->getStreet1())));
		$data->appendChild($xml->createElement('STREET2', htmlentities($this->application->getPersonal()->getStreet2())));
		$data->appendChild($xml->createElement('CITY', htmlentities($this->application->getPersonal()->getCity())));
		$data->appendChild($xml->createElement('STATE', htmlentities($this->application->getPersonal()->getState())));
		$data->appendChild($xml->createElement('ZIP', htmlentities($this->application->getPersonal()->getZip())));
		$data->appendChild($xml->createElement('PHONEHOME', htmlentities($this->application->getPersonal()->getPhoneHome())));
		$data->appendChild($xml->createElement('PHONECELL', htmlentities($this->application->getPersonal()->getPhoneCell())));
		$data->appendChild($xml->createElement('PHONEWORK', htmlentities($this->application->getEmployer()->getPhoneWork())));
		$data->appendChild($xml->createElement('PHONEEXT', htmlentities($this->application->getEmployer()->getPhoneExt())));
		$data->appendChild($xml->createElement('EMAIL', htmlentities($this->application->getPersonal()->getEmail())));
		$data->appendChild($xml->createElement('IPADDRESS', htmlentities($this->application->getPersonal()->getIPAddress())));
		$data->appendChild($xml->createElement('DOB', htmlentities($this->application->getPersonal()->getDOB())));
		$data->appendChild($xml->createElement('SSN', htmlentities($this->application->getPersonal()->getSSN())));
		$data->appendChild($xml->createElement('DRIVERLICENSENUMBER', htmlentities($this->application->getPersonal()->getDriverLicenseNumber())));
		$data->appendChild($xml->createElement('DRIVERLICENSESTATE', htmlentities($this->application->getPersonal()->getDriverLicenseState())));
		$data->appendChild($xml->createElement('WORKNAME', htmlentities($this->application->getEmployer()->getWorkName())));
		$data->appendChild($xml->createElement('WORKSTREET1', htmlentities($this->application->getEmployer()->getWorkStreet1())));
		$data->appendChild($xml->createElement('WORKSTREET2', htmlentities($this->application->getEmployer()->getWorkStreet2())));
		$data->appendChild($xml->createElement('WORKCITY', htmlentities($this->application->getEmployer()->getWorkCity())));
		$data->appendChild($xml->createElement('WORKSTATE', htmlentities($this->application->getEmployer()->getWorkState())));
		$data->appendChild($xml->createElement('WORKZIP', htmlentities($this->application->getEmployer()->getWorkZip())));
		$data->appendChild($xml->createElement('BANKNAME', htmlentities($this->application->getPersonal()->getBankName())));
		$data->appendChild($xml->createElement('BANKACCTNUMBER', htmlentities($this->application->getPersonal()->getBankAcctNumber())));
		$data->appendChild($xml->createElement('BANKABA', htmlentities($this->application->getPersonal()->getBankAba())));
		$data->appendChild($xml->createElement('PAYPERIOD', htmlentities($this->application->getEmployer()->getPayPeriod())));
		$this->buildUpdateSection($xml, $data, $this->application);
		
		return $data;
	}
}

?>