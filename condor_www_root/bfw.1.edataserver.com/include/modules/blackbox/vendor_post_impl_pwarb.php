<?php

/**
 * @desc A concrete implementation class for posting to ezm
 */
class Vendor_Post_Impl_PWARB extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
	(
		// Params which will be passed regardless of $this->mode
		'ALL'     => Array(
		//    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/EZMPAN',
			),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'   => Array(
			),
		'RC'      => Array(
			),
		'LIVE'    => Array(
			//'post_url' => 'http://americacashadvance.com/applyonline.php',
			),
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
	);
					
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		
		
		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
			$_SESSION['data']['pwarb_exit'] = TRUE;
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Next_Page( 'pwarb_exit' );
			$result->Set_Vendor_Decision('ACCEPTED');
		return $result;
	}

	//Uncomment the next line to use HTTP GET instead of POST
	public static function Get_Post_Type() {
		return Http_Client::HTTP_GET;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [PW_ARB]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		return TRUE;
	}
	
	public static function Set_Session_Data($target)
	{
		
	}
	
}
