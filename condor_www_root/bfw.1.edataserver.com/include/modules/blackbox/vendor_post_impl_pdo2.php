<?php

include_once('vendor_post_impl_pdo.php');

/**
 * @desc A concrete implementation class for posting to pdo2/pdo3/pdo5 (Payday One)
 * 
 * @see Mantis #12049 - avery.harris - BBx - Payday One Lead Posting Changes - Military (posting instruction changes) [DY] 
 */
class Vendor_Post_Impl_PDO2 extends Vendor_Post_Impl_PDO
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_urls' => array(
					'http' => 'https://post.tcleadsgateway.com/httpPost.aspx',
					'xml'  => 'https://post.tcleadsgateway.com/xmlPost.aspx',
					),
				 /* 'headers' => array( // Added To Header per Client Server change. [AuMa]
					'Content-Type: application/x-www-form-urlencoded',
				  ), */ 
				'postXML' => FALSE, //XML format or not. Must be FALSE now coz XML method hasn't been implemented. [DY]
				'testApp' => TRUE,
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'testApp' => FALSE,
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'pdo2'    => Array(
				'ALL'      => Array(
					'id' => 'LG028',
					),
				),
			'pdo3'    => Array(
				'ALL'      => Array(
					'id' => 'LG029',
					),
				),
			'pdo5'    => Array(
				'ALL'      => Array(
					'id' => 'LG029', // GForge #7711 [DY]
					),
				),
		);
		
	public function __construct(&$lead_data, $mode, $property_short)
	{
		parent::__construct($lead_data, $mode, $property_short);

		if ($this->rpc_params['ALL']['postXML']) {
			$this->rpc_params['ALL']['post_url'] = $this->rpc_params['ALL']['post_urls']['xml'];
		} else {
			$this->rpc_params['ALL']['post_url'] = $this->rpc_params['ALL']['post_urls']['http'];
		}
		
		unset($this->rpc_params['ALL']['post_urls']);
	}
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		$fields = parent::Generate_Fields($lead_data, $params);
		unset($fields['LeadGenID']);
		unset($fields['homephone']);
		$fields['leadGenID'] = $params['id'];
		$fields['homePhone'] = $lead_data['data']['phone_home'];
		$fields['workPhone'] = $lead_data['data']['phone_work'];
		
		if ($params['postXML']) { // XML
			$dom = new DOMDocument('1.0','utf-8');
			$root_element = $dom->createElement('application');
			$dom->appendChild($root_element);
			
			foreach ($fields as $key => $val)
			{
				$root_element->appendChild($dom->createElement($key, $val));
			}
				
			return $dom->saveXML();
		} else { // HTTP Post
			return $fields;
		}
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{	
		$result = new Vendor_Post_Result();
		$cookies = array_change_key_case($cookies, CASE_LOWER); // be careful: case changed.

		if (empty($cookies['response']))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif(strcasecmp(trim($cookies['response']), 'success') === 0)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($cookies));
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason('N/A');
		}

		return $result;
	}
	
	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [PDO2]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		if (isset($data_received['message'])){
			$url = urldecode($data_received['message']);
			$content = parent::Generic_Thank_You_Page($url);
		} else {
			$content = parent::Generic_Thank_You_Page('');
		}
			
		return($content);
	}
}

?>
