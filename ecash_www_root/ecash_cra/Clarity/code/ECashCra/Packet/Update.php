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
		$data = $xml->createElement('tradeline');
		$data->appendChild($xml->createElement('account-status', htmlentities($this->application->getVoidStatus())));
		$data->appendChild($xml->createElement('account-type', htmlentities($this->application->getAccountType())));
		$data->appendChild($xml->createElement('portfolio-type', htmlentities($this->application->getPortfolioType())));
		$data->appendChild($xml->createElement('consumer-account-number', htmlentities($this->application->getLoanID())));
		$data->appendChild($xml->createElement('consumer-account-number-old', ''));

		$data->appendChild($xml->createElement('account-information-date', htmlentities(date('Y-m-d'))));
		$data->appendChild($xml->createElement('account-opened', htmlentities($this->application->getLoanDate())));
		$data->appendChild($xml->createElement('first-due-date', htmlentities($this->application->getFirstDate())));
		$data->appendChild($xml->createElement('last-payment-date', htmlentities($this->application->getLastDate())));
		$data->appendChild($xml->createElement('delinquency-date', htmlentities($this->application->getPastDate())));
		$data->appendChild($xml->createElement('closed-date', htmlentities($this->application->getCloseDate())));

		$data->appendChild($xml->createElement('actual-payment', htmlentities($this->application->getPaymentAmt())));
		$data->appendChild($xml->createElement('current-balance', htmlentities($this->application->getBalance())));
		$data->appendChild($xml->createElement('highest-credit', htmlentities($this->application->getPrincipal())));
		$data->appendChild($xml->createElement('scheduled-payment', htmlentities($this->application->getSchedPayment())));
		$data->appendChild($xml->createElement('amount-past-due', htmlentities($this->application->getPastDue())));
		$data->appendChild($xml->createElement('first-payment-default', htmlentities($this->application->getFirstPaymentBad())));
        
		$data->appendChild($xml->createElement('terms-frequency', htmlentities($this->application->getFrequency())));
		$data->appendChild($xml->createElement('terms-duration', htmlentities($this->application->getDuration())));
		$data->appendChild($xml->createElement('payment-rating', htmlentities($this->application->getRating())));
		//$data->appendChild($xml->createElement('payment-history-profile', htmlentities($this->application->getHistory())));
        
		$data->appendChild($xml->createElement('social-security-number', htmlentities($this->application->getSSN())));
		$data->appendChild($xml->createElement('first-name', htmlentities($this->application->getNameFirst())));
		$data->appendChild($xml->createElement('last-name', htmlentities($this->application->getNameLast())));
		$data->appendChild($xml->createElement('date-of-birth', htmlentities($this->application->getDOB())));
		$data->appendChild($xml->createElement('address1', htmlentities($this->application->getStreet1())));
		$data->appendChild($xml->createElement('address2', htmlentities($this->application->getStreet2())));
		$data->appendChild($xml->createElement('city', htmlentities($this->application->getCity())));
		$data->appendChild($xml->createElement('state', htmlentities($this->application->getState())));
		$data->appendChild($xml->createElement('zip', htmlentities($this->application->getZip())));
		$data->appendChild($xml->createElement('phone', htmlentities($this->application->getPhoneHome())));
		$data->appendChild($xml->createElement('bank-account-number', htmlentities($this->application->getBankAcct())));
		$data->appendChild($xml->createElement('bank-routing-number', htmlentities($this->application->getBankABA())));
		$data->appendChild($xml->createElement('xml-response-tracking-number', htmlentities($this->application->getAppID())));
        
		return $data;
	}
}

?>
