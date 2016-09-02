<?php

/**
 * The Tradeline Payment Packet
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Packet_TradelinePayments extends ECashCra_Packet_Base 
{
	/**
	 * @var ECashCra_Data_Payment
	 */
	protected $payment;
	
	/**
	 * Creates a new tradeline payment packet
	 *
	 * @param ECashCra_Data_Payment $payment
	 */
	public function __construct(ECashCra_Data_Payment $payment)
	{
		$this->payment = $payment;
		parent::__construct($payment->getApplication());
	}
	
	////
	// ECashCra_Packet_Base
	////
	
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
		$data->appendChild($xml->createElement('PAYMENTID', htmlentities($this->payment->getId())));
		$data->appendChild($xml->createElement('TYPE', htmlentities($this->payment->getType())));
		$data->appendChild($xml->createElement('METHOD', htmlentities($this->payment->getMethod())));
		$data->appendChild($xml->createElement('PAYMENTDATE', htmlentities($this->payment->getDate())));
		$data->appendChild($xml->createElement('AMOUNT', htmlentities($this->payment->getAmount())));
		$data->appendChild($xml->createElement('RETURNCODE', htmlentities($this->payment->getReturnCode())));
		$data->appendChild($xml->createElement('BANKNAME', htmlentities($this->payment->getApplication()->getPersonal()->getBankName())));
		$data->appendChild($xml->createElement('BANKABA', htmlentities($this->payment->getApplication()->getPersonal()->getBankAba())));
		$data->appendChild($xml->createElement('BANKACCTNUMBER', htmlentities($this->payment->getApplication()->getPersonal()->getBankAcctNumber())));
		
		return $data;
	}
}

?>
