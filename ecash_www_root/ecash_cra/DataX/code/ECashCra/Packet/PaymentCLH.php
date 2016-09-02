<?php

/**
 * The Payment Packet
 *
 * @package ECashCra
 */
class ECashCra_Packet_PaymentCLH extends ECashCra_Packet_Base 
{
	/**
	 * The type of packet
	 *
	 * @return string
	 */
	protected function getPacketType()
	{
		return 'payment';
	}

	/**
	 * Builds the data portion of the packet
	 *
	 * @param DOMDocument $xml
	 * @return DOMElement
	 */
	protected function buildData(DOMDocument $xml)
	{
		$data = $xml->createElement('DATA');
		$data->appendChild($xml->createElement('PAYMENTID', htmlentities($this->application->getPaymentId())));
		$data->appendChild($xml->createElement('TYPE', htmlentities($this->application->getType())));
		$data->appendChild($xml->createElement('METHOD', htmlentities($this->application->getMethod())));
		$data->appendChild($xml->createElement('PAYMENTDATE', htmlentities($this->application->getPaymentDate())));
		$data->appendChild($xml->createElement('AMOUNT', htmlentities($this->application->getPayment_Amount())));
		$data->appendChild($xml->createElement('RETURNCODE', htmlentities($this->application->getReturnCode())));
		$data->appendChild($xml->createElement('BANKNAME', htmlentities($this->application->getBankName())));
		$data->appendChild($xml->createElement('BANKABA', htmlentities($this->application->getBankAba())));
		$data->appendChild($xml->createElement('BANKACCTNUMBER', htmlentities($this->application->getBankAcctNumber())));
		
		return $data;
	}
}

?>
