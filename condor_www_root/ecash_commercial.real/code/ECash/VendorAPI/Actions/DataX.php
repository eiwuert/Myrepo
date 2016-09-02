<?php

/** DataX class for commercial customers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class ECash_VendorAPI_Actions_DataX extends VendorAPI_Actions_DataX
{
	/**
	 * @var string
	 */
	protected $call_type;
	
	/** Determine if can run this call or not.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return bool
	 */
	protected function isValid(array $data, VendorAPI_StateObject $state)
	{
		return TRUE;
	}
	
	
	/** Gets the DataX object.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return VendorAPI_IDataX
	 */
	protected function getDataX(array $data, VendorAPI_StateObject $state)
	{
		$this->call_type = 'idv-l5';
		
		$datax = new ECash_VendorAPI_DataX($this->call_type);
		
		return $datax;
	}
	
	/** Converts all data needed for DataX into a nice array to be passed
	 * in.
	 *
	 * @param array $data
	 * @param VendorAPI_StateObject $state
	 * @return array
	 */
	protected function getDataXData(array $data, VendorAPI_StateObject $state)
	{
		$datax_data = array(
		);
		
		return $datax_data;
	}
	
	/** Does any storage of DataX packets.
	 *
	 * @param mixed $datax_result
	 * @param string $request_packet
	 * @param string $response_packet
	 * @param float $datax_timer
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	protected function storeDataXResult($datax_result, $request_packet, $response_packet, $datax_timer, VendorAPI_StateObject $state)
	{
		parent::storeDataXResult($datax_result, $request_packet, $response_packet, $datax_timer, $state);
		
		$state->datax['call_type'] = $this->call_type;
	}
	
	/** Processes the DataX response and returns an array containing all
	 * resulting information to return for the API.
	 *
	 * @param mixed $datax_result
	 * @return array
	 */
	protected function processDataXResult($datax_result)
	{
		$result = array(
			'result' => TRUE,
			'adverse_actions' => array(
			),
		);
		
		return $result;
	}
}

?>