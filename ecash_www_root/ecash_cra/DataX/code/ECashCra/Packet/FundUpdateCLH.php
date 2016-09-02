<?php

/**
 * The Fund Update Packet
 *
 * @package ECashCra
 */
class ECashCra_Packet_FundUpdateCLH extends ECashCra_Packet_UpdateCLH
{
	/**
	 * The type of packet
	 *
	 * @return string
	 */
	protected function getPacketType()
	{
		return 'fund_update';
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
		$data->appendChild($xml->createElement('CHANNEL', htmlentities($this->application->getChannel())));
		$data->appendChild($xml->createElement('TRADELINETYPE', htmlentities($this->application->getTradeLineType())));
		$data->appendChild($xml->createElement('TRADELINETYPECODE', htmlentities($this->application->getTradeLineTypeCode())));
		$data->appendChild($xml->createElement('APR', htmlentities($this->application->getAPR())));
		$data->appendChild($xml->createElement('PAYMENTFREQUENCY', htmlentities($this->application->getPaymentFrequency())));
		$data->appendChild($xml->createElement('PAYMENTAMOUNT', htmlentities($this->application->getPaymentAmount())));

		$data->appendChild($xml->createElement('FUNDDATE', htmlentities($this->application->getFundDate())));
		$data->appendChild($xml->createElement('FUNDAMOUNT', htmlentities($this->application->getFundAmount())));
		$data->appendChild($xml->createElement('FUNDFEE', htmlentities($this->application->getFirstServiceChargeAmount())));
		$data->appendChild($xml->createElement('DUEDATE', htmlentities($this->application->getFirstPaymentDate())));
	}
}
?>
