<?php

include_once BFW_MODULE_DIR . BFW_USE_MODULE . '/fraud_scan.php';

/**
 * == Test SOAP functions ==
 * This code is used to test action functions in the webservice because it's
 * hard to debug SOAP operations through SOAP requests. 

 * $test = new TSS_Service(NULL, NULL, NULL);
 * echo $test->Is_Fraud('test@test.com', 'sth', '11111', 'promo_sub_code') . "\n";
 * echo $test->Is_Fraud('test@test.com', 'c8323b6c2c5819e6a9fffaf8a439fa2c', '10000') . "\n";
 * exit(); 
 * 
 * @author Demin Yin <Demin.Yin@SellingSource.com> 
 * @see    GForge #3837 - API for Fraud Scan
 * @since  Thu 27 Dec 2007 02:55:00 PM PST
 */
class FraudCheck extends OLPWebService
{
	/**
	 * Maximum length of email address
	 * @var int
	 */
	const EMAIL_LENGTH = 100; // the column length is 100.
	

	/**
	 * Maximum length of promo sub code.
	 * @var int 
	 */
	const PROMO_SUB_CODE_LENGTH = 250; // the column length is 250.
	

	/**
	 * Constructor.
	 * 
	 * Add some customized return messages.
	 */
	public function __construct()
	{
		parent::__construct();
		
		OLPWebService_Message::addMessage(
			'PASS_FRAUD_CHECK', 
			array(
				'type'    => 'PASS', 
				'content' => 'No fraud data found for this email address.',
			)
		);
		
		OLPWebService_Message::addMessage(
			'HAS_FRAUD_DATA',
			array(
				'type'    => 'FAIL',
				'content' => 'Field \'%s\' contains fraud data.',
			)
		);
	}
	
	/**
	 * Check if given email address has failed fraud scan or not. If not failed, return a string staring with 'PASS'; otherwise, return a string starting with 'FAIL' and followed by failed reason. 
	 * 
	 * @param string $email Email address.
	 * @param string $license_key License key.
	 * @param string $promo_id Promo ID.
	 * @param string $promo_sub_code This is an optional parameter.
	 * @return string If not failed, return a string staring with 'PASS'; otherwise, return a string starting with 'FAIL' and followed by failed reason.
	 */
	public function isFraud($email, $license_key, $promo_id, $promo_sub_code = '')
	{
		$status = $this->hasValidEmail($email, self::EMAIL_LENGTH);
		if ($status !== TRUE)
		{
			return $status;
		}
		
		$status = $this->hasAccessPermission($license_key, $promo_id, $promo_sub_code);
		if ($status !== TRUE)
		{
			return $status;
		}
		
		$promo_sub_code = substr(trim($promo_sub_code), 0, self::PROMO_SUB_CODE_LENGTH);
		
		$this->setConnection();
		$femail = new FEmail($email, $promo_id, $promo_sub_code);
		$result = Fraud_Scan::Is_Fraud_Email($femail, $this->sql, $this->database);
		
		if ($result)
		{
			return OLPWebService_Message::getMessage('HAS_FRAUD_DATA', $result);
		}
		else
		{
			return OLPWebService_Message::getMessage('PASS_FRAUD_CHECK');
		}
	}
}
