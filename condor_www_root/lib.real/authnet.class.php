<?php
/**
 * Authnet
 *
 * Sends, receives and processes CC data.
 *
 * @author	original author unknown but
 * 			modified and put into this format by
 * 			seth.long@selling-source.com on 10/21/2005.
 */
class Authnet
{
	// attributes
	private $_sql;
	private $_db;
	private $_login;
	private $_key;
	private $_mode;
	public  $status;
	
	/**
	 * Method __construct
	 *
	 * Default constructor.
	 *
	 * @param	object	$sql
	 * 					The mysql object.
	 * @param 	string	$db_process
	 * 					The process database name.
	 * @param 	string	$mode
	 * 					The mode we are operating in. 
	 */
	public function __construct($sql, $db, $mode = "LIVE")
	{
		// set attributes
		$this->_sql		= $sql;
		$this->_db		= $db;
		$this->_mode	= $mode;
		$this->_login 	= 'gre481202256';
		$this->_key		= 'ADOqtXYOUH558iHc';
		
		// LIVE mode
		if ($mode == "LIVE")
		{
			$fields		= $this->_getData();
			$results	= $this->_sendData($fields);
		}
		// TEST mode
		else
		{
			$results	= $this->_simResponse();
		}
		
		// process the incoming data
		$this->_processResults($results);
		
		// write the response to the db
		//$this->_writeResults();
		
		// update the transaction table
		//$this->_updateTransaction();
	}
	
	/**
	 * Method _getData
	 *
	 * Retreives info and puts into an array.
	 *
	 * @return	array	$fields
	 * 
	 */
	private function _getData()
	{
		$fields["x_first_name"]			= $_SESSION["data"]["consumer"]["name_first"];
		$fields["x_last_name"] 			= $_SESSION["data"]["consumer"]["name_last"];
		$fields["x_address"] 			= $_SESSION["data"]["consumer"]["street"] . " " . $_SESSION["data"]["consumer"]["unit"];
		$fields["x_city"] 				= $_SESSION["data"]["consumer"]["city"];
		$fields["x_state"] 				= $_SESSION["data"]["consumer"]["state"];
		$fields["x_zip"] 				= $_SESSION["data"]["consumer"]["zip_code"];
		
/*		$fields["x_ship_to_first_name"]	= $_SESSION["data"]["name_first"];
		$fields["x_ship_to_last_name"] 	= $_SESSION["data"]["name_last"];
		$fields["x_ship_to_address"] 	= $_SESSION["data"]["address_1"] . " " . $_SESSION["data"]["address_2"];
		$fields["x_ship_to_city"] 		= $_SESSION["data"]["city"];
		$fields["x_ship_to_state"] 		= $_SESSION["data"]["state"];
		$fields["x_ship_to_zip"] 		= $_SESSION["data"]["zip"];
*/		
		$fields["x_email"]				= $_SESSION["data"]["consumer"]["email"];
		$fields["x_phone"] 				= $_SESSION["data"]["consumer"]["phone_home"];
		
		$fields["x_card_type"] 			= $_SESSION["data"]["cc_type"];
		$fields["x_card_num"]			= $_SESSION["data"]["cc_number"];
		$fields["x_exp_date"]			= $_SESSION["data"]["cc_exp_month"] . "/" . $_SESSION["data"]["cc_exp_year"];
		$fields["x_card_code"] 			= $_SESSION["data"]["cc_verification"];

		$fields["x_po_num"] 			= date("m/d/Y g:i:s A", mktime());

		$fields["x_login"] 				= $this->_login;
		$fields["x_country"] 			= "US";
		$fields["x_cust_id"] 			= $_SESSION["application_id"];
		$fields["x_fp_timestamp"] 		= time();
		$fields["x_fp_sequence"] 		= rand(1,1000);
		$fields["x_invoice_num"] 		= $fields[x_cust_id];		
/*		$fields["x_description"] 		= $_SESSION['product_details']['product_code']." ".$_SESSION['product_details']['product_name'];
		$fields["x_amount"] 			= $_SESSION['product_details']['initial_amount'];
*/		
		$fields["x_amount"] 			= $_SESSION['data']['total'];

		$login_data  = $fields[x_login]."^";
		$login_data .= $fields[x_fp_sequence]."^";
		$login_data .= $fields[x_fp_timestamp]."^";
		$login_data .= $fields[x_amount]."^";
		
		$fields[x_fp_hash] = $this->hmac($this->_key, $login_data);
		
		if ($_SESSION['data']["consumer"]['name_last'] == "TEST" && $_SESSION['data']["consumer"]['name_first'] == "TEST")
			$fields["x_test_request"] = "TRUE";
		
		return $fields;
	}
	
	/**
	 * Method _sendData
	 *
	 * Sends data to authnet and returns the response.
	 *
	 * @return	array	$result
	 * 
	 */
	private function _sendData($fields)
	{
		$transaction_url	= "https://secure.authorize.net/gateway/transact.dll";
		$result 			= $this->sendRequest($fields, $transaction_url);
		
		$_SESSION["data"]['authnet']["authnet_response"]["complete_result"] = $result;
		
		return $result;
	}
	
	/**
	 * Method _processResults
	 *
	 * Processes the data received from authnet 
	 * and sets them into the session.
	 *
	 * @return	void
	 * 
	 */
	private function _processResults($result)
	{
		$authnet_response = unserialize($result);
		
		foreach($authnet_response as $key => $val)
		{
			 $_SESSION["data"]['authnet']["authnet_response"][$key] = $val;
		}
		
		if ($_SESSION["data"]['authnet']["authnet_response"]["x_response_code"] == 1)
		{
			$_SESSION["data"]['authnet']["authnet_response"]["status"] = "APPROVED";
		}
		else
		{
			$_SESSION["data"]['authnet']["authnet_response"]["status"] = "DECLINED";
		}
	}
	
	/**
	 * Method _writeResults
	 *
	 * Writes the results from authnet to
	 * the DB.
	 *
	 * @return	void
	 * 
	 */
	private function _writeResults()
	{
		$query = "
		INSERT INTO
			response_authnet_cc
		SET
			date_created			= NOW(),
			process_id				= '" . mysql_escape_string($_SESSION['ids']['process_id']) . "',
			transaction_id			= '" . mysql_escape_string($_SESSION["ids"]["transaction_id"]). "',
			x_cust_id				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_cust_id]). "',
			x_response_code			= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_response_code]). "',
			x_response_sub_code		= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_response_subcode]). "',
			x_response_reason_code	= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_response_reason_code]). "',
			x_response_reason_text	= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_response_reason_text]). "',
			x_auth_code				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_auth_code]). "',
			x_avs_code				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_avs_code]). "',
			x_trans_id				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_trans_id]). "',
			x_invoice_num			= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_invoice_num]). "',
			x_description			= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_description]). "',
			x_amount				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_amount]). "',
			x_method				= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_method]). "',
			x_type					= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][x_type]). "',
			process_details			= '" . mysql_escape_string(serialize($_SESSION["data"]['process_request'][$transaction_url])). "',
			status					= '" . mysql_escape_string($_SESSION["data"]['authnet'][authnet_response][status]) . "',
			MODE					= '" . $this->_mode . "' 
		";

		$this->_sql->Query($this->_db, $query);
	}
	
	/**
	 * Method _updateTransaction
	 *
	 * Updates the transaction table with
	 * the result from authnet.
	 *
	 * @return	void
	 * 
	 */
	private function _updateTransaction()
	{
		$query = "
			UPDATE
				transaction
			SET
				transaction_status = '{$_SESSION["data"]['authnet']["authnet_response"]["status"]}'
			WHERE
				transaction_id = {$_SESSION["ids"]["transaction_id"]}
		";
	
		$this->_sql->Query($this->_db, $query);
	}
	
	/**
	 * Method _simResponse
	 *
	 * Simulates a response for testing purposes.
	 *
	 * @return	void
	 * 
	 */
	private function _simResponse()
	{		
		$_SESSION["data"]['authnet']["authnet_response"]["x_cust_id"] 				= $_SESSION["data"][visitor_id];
		$_SESSION["data"]['authnet']["authnet_response"]["x_response_subcode"] 		= "1";
		$_SESSION["data"]['authnet']["authnet_response"]["x_response_reason_code"] 	= "1";
		$_SESSION["data"]['authnet']["authnet_response"]["x_response_reason_text"] 	= "FAKE POST";
		$_SESSION["data"]['authnet']["authnet_response"]["x_auth_code"] 			= "0";
		$_SESSION["data"]['authnet']["authnet_response"]["x_avs_code"] 				= "0";
		$_SESSION["data"]['authnet']["authnet_response"]["x_trans_id"] 				= rand(100000000, 999999999);
		$_SESSION["data"]['authnet']["authnet_response"]["x_invoice_num"] 			= $_SESSION["data"][visitor_id];
		$_SESSION["data"]['authnet']["authnet_response"]["x_description"] 			= "FAKE POST";
		$_SESSION["data"]['authnet']["authnet_response"]["x_amount"] 				= "1.00";
		$_SESSION["data"]['authnet']["authnet_response"]["x_method"] 				= "0";
		$_SESSION["data"]['authnet']["authnet_response"]["x_type"] 					= "0";
		
		if ($_SESSION["data"]["cc_number"] == "9999999999999999")
		{
			$_SESSION["data"]['authnet']["authnet_response"]["x_response_code"] = "2"; //declined
		}
		else
		{
			$_SESSION["data"]['authnet']["authnet_response"]["x_response_code"] = "1"; //approved
		}
	}
	
	/**
	 * Method hmac
	 *
	 * RFC 2104 HMAC implementation for php.
	 * Creates an md5 HMAC. Eliminates the need
	 * to install mhash to compute a HMAC
	 *
	 * @param 	string	$key
	 * @param 	string	$data
	 * @return	String
	 * 
	 */
	public static function hmac($key, $data)
	{
	   $b = 64; // byte length for md5
	   
	   if (strlen($key) > $b)
	   {
	       $key = pack("H*",md5($key));
	   }
	   
	   $key  	= str_pad($key, $b, chr(0x00));
	   $ipad 	= str_pad('', $b, chr(0x36));
	   $opad 	= str_pad('', $b, chr(0x5c));
	   $k_ipad 	= $key ^ $ipad ;
	   $k_opad	= $key ^ $opad;
	
	   return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
	}
	
	/**
	 * Method sendRequest
	 *
	 * Sends the fields entered to
	 * the url entered.
	 *
	 * @param 	array	$fields
	 * @param 	string	$url
	 * @return	array	$result
	 * 
	 */
	public static function sendRequest($fields, $url)
	{
		$content="";
		
		foreach($fields as $key => $val)
		{
			$content .= $key."=".urlencode($val)."&";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		$result = curl_exec($ch);
		
		$_SESSION[process_request][$url] = curl_getinfo($ch);
		
		return $result;
	}
}
?>