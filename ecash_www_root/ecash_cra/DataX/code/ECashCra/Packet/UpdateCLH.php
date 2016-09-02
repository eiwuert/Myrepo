<?php

/**
 * The base update packet
 *
 * @package ECashCra
 */
abstract class ECashCra_Packet_UpdateCLH extends ECashCra_Packet_Base 
{
	/**
	 * Used to create the individual update specific portion of the packet.
	 *
	 * @param DOMDocument $xml
	 * @param DOMElement $data
	 * @return null
	 */
	abstract protected function buildUpdateSection(DOMDocument $xml, DOMElement $data);

	/**
	 * Builds the data portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @return DOMElement
	 */
	protected function buildData(DOMDocument $xml)
	{
		$data = $xml->createElement('DATA');
		$data->appendChild($xml->createElement('NAMEFIRST', htmlentities($this->application->getNameFirst())));
		$data->appendChild($xml->createElement('NAMEMIDDLE', NULL));
		$data->appendChild($xml->createElement('NAMELAST', htmlentities($this->application->getNameLast())));
		$data->appendChild($xml->createElement('STREET1', htmlentities($this->application->getStreet1())));
		$data->appendChild($xml->createElement('STREET2', htmlentities($this->application->getStreet2())));
		$data->appendChild($xml->createElement('CITY', htmlentities($this->application->getCity())));
		$data->appendChild($xml->createElement('STATE', htmlentities($this->application->getState())));
		$data->appendChild($xml->createElement('ZIP', htmlentities($this->application->getZip())));
		$data->appendChild($xml->createElement('PHONEHOME', htmlentities($this->application->getPhoneHome())));
		$data->appendChild($xml->createElement('PHONECELL', htmlentities($this->application->getPhoneCell())));
		$data->appendChild($xml->createElement('PHONEWORK', htmlentities($this->application->getPhoneWork())));
		$data->appendChild($xml->createElement('PHONEEXT', htmlentities($this->application->getPhoneExt())));
		$data->appendChild($xml->createElement('EMAIL', htmlentities($this->application->getEmail())));
		$data->appendChild($xml->createElement('DOB', htmlentities($this->application->getDOB())));
		$data->appendChild($xml->createElement('SSN', htmlentities($this->application->getSSN())));
		$data->appendChild($xml->createElement('DRIVERLICENSENUMBER', htmlentities($this->application->getDriverLicenseNumber())));
		$data->appendChild($xml->createElement('DRIVERLICENSESTATE', htmlentities($this->application->getDriverLicenseState())));
		$data->appendChild($xml->createElement('WORKNAME', htmlentities($this->application->getWorkName())));
		$data->appendChild($xml->createElement('WORKSTREET1', htmlentities($this->application->getWorkStreet1())));
		$data->appendChild($xml->createElement('WORKSTREET2', htmlentities($this->application->getWorkStreet2())));
		$data->appendChild($xml->createElement('WORKCITY', htmlentities($this->application->getWorkCity())));
		$data->appendChild($xml->createElement('WORKSTATE', htmlentities($this->application->getWorkState())));
		$data->appendChild($xml->createElement('WORKZIP', htmlentities($this->application->getWorkZip())));
		$data->appendChild($xml->createElement('BANKNAME', htmlentities($this->application->getBankName())));
		$data->appendChild($xml->createElement('BANKABA', htmlentities($this->application->getBankAba())));
		$data->appendChild($xml->createElement('BANKACCTNUMBER', htmlentities($this->application->getBankAcctNumber())));
		$data->appendChild($xml->createElement('PAYPERIOD', htmlentities($this->application->getPayPeriod())));
		$data->appendChild($xml->createElement('DIRECTDEPOSIT', htmlentities($this->application->getDirectDeposit())));
		$data->appendChild($xml->createElement('MONTHLYINCOME', htmlentities($this->application->getMonthlyIncome())));
		$this->buildUpdateSection($xml, $data, $this->application);
		
		return $data;
	}
}

?>
