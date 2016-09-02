<?php
/**
 * Status Update request for FactorTrust
 */
class ECash_FactorTrust_Requests_Status_Update extends FactorTrust_UW_Request
{
	/**
	 * Construct a basic FactorTrust request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($license, $password, $call_name)
	{
		parent::__construct($license, $password, $call_name);
		
		self::$data_map = array(
				'SSN' => 'ssn',
				'TRANSACTIONCODE'  => 'transaction_code',
				'STATUS'=> 'status'
				);
	}
}
