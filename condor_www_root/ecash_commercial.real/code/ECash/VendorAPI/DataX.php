<?php

/** The ECash Commercial DataX adapter.
 *
 * This class adapts DataX_2 to the Vendor API
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class ECash_VendorAPI_DataX implements VendorAPI_IDataX
{
	/**
	 * @var DataX_2
	 */
	protected $datax;

	/** Sets up the DataX call.
	 *
	 * @param string $call_type
	 */
	public function __construct($call_type)
	{
		$this->datax = new Data_X(FALSE); // Don't use DataX's MiniXML
	}

	/** Executes the DataX call.
	 *
	 * @param array $datax_data
	 * @return array
	 */
	public function execute(array $datax_data)
	{
		$result = array(
		);

		return $result;
	}

	/** Returns the Request packet.
	 *
	 * @return string
	 */
	public function getRequestPacket()
	{
		$packet = $this->datax->Get_Sent_Packet();

		return $packet;
	}

	/** Returns the Response packet.
	 *
	 * @return string
	 */
	public function getResponsePacket()
	{
		$packet = $this->datax->Get_Received_Packet();

		return $packet;
	}
}

?>