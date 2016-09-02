<?php
/**
 * ECash_Documents_SMS
 * SMS Transport for MRS Text
 * 
 */
class ECash_Documents_SMS
{
	const SMS_GOOD = false;
	
	private $mrs_api;
	private $log;

	private $msg_q = array();	
	
	public function __construct() {
		if (ECash::getConfig()->SMS_SERVICE_GOOD) {
			$this->mrs_api = new SoapClient(ECash::getConfig()->SMS_SERVICE_URL);
			$this->log = ECash::getLog('sms');
		}
	}
	
	public function send($message_id, $number) {

		if (!ECash::getConfig()->SMS_SERVICE_GOOD) return false;
		if (! $number = $this->sanitizeNumber()) return false;
			
		
		$config = ECash::getConfig();
		
		$auth = array(
			'api_key' => $config->SMS_API_KEY,
			'username' => $config->SMS_USERNAME,
			'password' => $config->SMS_PASSWORD,
			'shortcode' => $config->SMS_SHORTCODE,
			'keyword' => $config->SMS_KEYWORD,
			'thread' => NULL,
		);

		$send = array(
			'customfields' => array(),
			'sendTo' => $number,
			'type' => 5,
			'carrier' => NULL,
		);

		$message = $this->msg_list->getMessage($message_id);
		
		$result = $this->mrs_api->sendTextNumberMessage($auth, $send, $message);
		
		$this->log->write("Sent {$number}:{$message}");
	}

	private function sanitizeNumber($number) {
		if (!ECash::getConfig()->SMS_SERVICE_GOOD) return false;
		$number = preg_replace('/[^0-9]/', '', $number);

		if($number[0] == '1')
			$number = substr($number, 1);

		if(strlen($number) != 10) {
			$this->log->write("SMS Number must be 10 digits: {$number}, not sending");
			return false;
		}

		return $number;
	}
	
	public function sendTemplates() {
		if (!ECash::getConfig()->SMS_SERVICE_GOOD) return false;
	
		$config = ECash::getConfig();
		
		$auth = array(
			'api_key' => $config->SMS_API_KEY,
			'username' => $config->SMS_USERNAME,
			'password' => $config->SMS_PASSWORD,
			'shortcode' => $config->SMS_SHORTCODE,
			'keyword' => $config->SMS_KEYWORD,
			'thread' => NULL,
		);

		foreach($this->msg_q as $message_id => $recipients) {
			$template = array(
				'alias' => $message_id,
				'description' => NULL,
				'subject' => NULL,
				'text' => NULL,
				'title' => NULL,
			);

			$this->log->write("Sending SMS #{$message_id} to " . count($recipients) . " recipients");
			$result = $this->mrs_api->sendTemplateMessage($auth, array('recipients' => $recipients), $template);
		}		
	}

	public function queueTemplate( $application_id, $message_id ) {		
		if (!ECash::getConfig()->SMS_SERVICE_GOOD) return false;
		if($data = $this->getData( $application_id, $message_id ))
			$this->msg_q[$message_id][] = $data;
	}

	private function getData( $application_id, $message_id ) {
		$tokens = @ECash::getApplicationById($application_id)->getTokenProvider()->getTokens();

		//skip this if the number is bad
		if(! $number = $this->sanitizeNumber($tokens['CustomerPhoneCell']) )
			return false;

		$data = array(
			'sendTo' => $number,
			'type' => '1',
			'carrier' => NULL,
			'customfields' => array(),
		);

		switch( $message_id ) {
			case 6801: //activation {url}
				$link = $this->getShortLink( $tokens['CSLoginLink'] );
				$data['customfields'][] = array( 'name' => 'url', 'value' => $link );
				break;

			case 6803: //activation {url}
				$link = $this->getShortLink( $tokens['CSLoginLink'] );
				$data['customfields'][] = array( 'name' => 'url', 'value' => $link );
				break;

			case 6806: //loan {DueDate}
				$data['customfields'][] = array( 'name' => 'DueDate', 'value' => $tokens['LoanDueDate'] );
				break;
								
			default:
				break;
		}

		return $data;
	}

	private function getShortLink( $url ) {
		$json_data = json_encode(array('longUrl' => $url) );//, JSON_FORCE_OBJECT);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json') ); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
		$response = curl_exec($curl);		
		curl_close($curl);

		$short_data = json_decode($response);
		if( isset( $short_data->id ) )
			return $short_data->id;

		//failsafe
		return $url;
	}
	
}