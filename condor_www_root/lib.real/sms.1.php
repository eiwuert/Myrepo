<?

/**
 * sms_prpc.php
 * 
 * @desc 
 * 		SMS PRPC client for sms web service
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @version 
 * 		1.0
 * @todo
 * 		
 */

include_once('prpc/client.php');

class SMS
{
	protected $mode;
	protected $sms_server;
	
	function __construct($mode)
	{
		$this->mode = $mode;
		$this->sms_server = $this->Get_Server();
	}
	
	
	/**
	 * Send_SMS() - method to send sms msg
	 *
	 * @param int $phone_number  
	 *        recipient phone number
	 * @param string $message
	 *        text message 
	 * @param string $campaign
	 *        campaign/event
	 * @param string $state
	 *        state abbrev to find time zone for recipient
	 * @param date $delivery_date
	 *        scheduled delivery date
	 * @param string $company_short
	 *        company abbrev
	 * @param string $track_key
	 *        statpro track
	 * @param string $space_key
	 *        statpro space
	 * @param bool $time_zone_chk
	 *        set to true if you DONT want this message to be sent before 7am and after 8pm
	 * 
	 * @return array $result
	 *               (
	 *                 'flag',        // true on successfully sending the message, false otherwise.
	 *                 'code',        // zero on successfully sending message, positive error number otherwise.
	 *                 'msg',         // description of error or confirmation with message_id if successful.
	 *                 'message_id'   // positive integer message_id if successfully sent, zero otherwise.
	 *               )
	 */
	public function Send_SMS( $phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key = false, $space_key = false, $time_zone_chk = false )
	{
		$result = $this->sms_server->Send_SMS( $phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key, $space_key, $time_zone_chk );
		
		return $result;
	}

	/**
	 * Check_Blacklist() - method to check if a phone number is blacklisted
	 *
	 * @param int $phone_number  
	 *        recipient phone number
	 *
	 * @return boolean $result
	 *        returns true if the phone number is blacklisted, false otherwise.
	 */
	public function Check_Blacklist( $phone_number )
	{
		$result = $this->sms_server->Check_Blacklist( $phone_number );
		return $result;
	}
	
	/**
	 * Add_To_Blacklist() - method to add a phone number to the blacklist
	 *
	 * @param int $phone_number  
	 *        recipient phone number
	 *
	 * @return boolean $result
	 *        always returns true;
	 */
	public function Add_To_Blacklist( $phone_number )
	{
		$result = $this->sms_server->Add_To_Blacklist( $phone_number );
		return $result;
	}
	
	/**
	 * Remove_From_Blacklist() - method to remove a phone number from the blacklist
	 *
	 * @param int $phone_number  
	 *        recipient phone number
	 *
	 * @return boolean $result
	 *        always returns true;
	 */
	public function Remove_From_Blacklist( $phone_number )
	{
		$result = $this->sms_server->Remove_From_Blacklist( $phone_number );
		return $result;
	}
	
	
	private function Get_Server()
	{
		switch(strtoupper($this->mode))
		{
			default:
			case 'LOCAL':
				preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $_SERVER['SERVER_NAME'], $matched);
				$local_name = isset($matched[1]) ? $matched[1] : '';
				if ( $local_name == '' )
				{
					$server = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
				}
				else
				{
					$server = "prpc://sms.$local_name.tss:8080/sms_prpc.php";
				}
				break;
				
			case 'RC':
				$server = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
				break;
				
			case 'LIVE':
				$server = 'prpc://sms.edataserver.com/sms_prpc.php';
				break;
		}
		$sms_server = new PRPC_Client($server);
		return $sms_server;
	}
	
}