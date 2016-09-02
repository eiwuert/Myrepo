<?

/**
 * kannel.class.php
 * 
 * @desc 
 * 		class for managing sms messaging via kannel server
 * @version 
 * 		1.0
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @todo 
 *		comments
 */

defined('MODE') || define('MODE', 'RC');   // this should be defined by the cron

class Kannel
{
	private $sms_query_obj;

	const FORCE_MODEM_RESTART_TEST = false;

	const SUCCESS_MSG              = 'Sent.';
	const MAX_QUEUE_COUNT          = 3;
	const MAX_QUEUE_COUNT_RC_LOCAL = 1;   // For easy testing, use a really low threshhold for RC & LOCAL

	const STATUS_MESSAGE_SENT           = 1;
	const STATUS_MESSAGE_NOT_SENT       = 2;
	const STATUS_MESSAGE_QUEUE_EXCEEDED = 3;

	private $SEND_URL_SUFFIX;
	private $STATUS_URL_SUFFIX; 
	private $MODEM_STOP_SUFFIX; 
	private $MODEM_START_SUFFIX;
	
	function __construct(&$sms_query_obj)
	{
		$this->sms_query_obj = $sms_query_obj;

		// $mode = strtoupper(MODE);
		$mode = 'LIVE';   // quick fix because now there is only one instance of kannel running on kannel.edataserver.com
		$port_send = $mode == 'LIVE' ? ':13013' : ':14013';
		$port_stat = $mode == 'LIVE' ? ':13000' : ':14000';
	
		$this->SEND_URL_SUFFIX    = $port_send . '/cgi-bin/sendsms';   
		$this->STATUS_URL_SUFFIX  = $port_stat . '/status.xml?password=snailmail';
		$this->MODEM_STOP_SUFFIX  = $port_stat . '/stop-smsc?password=snailmail&smsc={MODEM_NAME}';
		$this->MODEM_START_SUFFIX = $port_stat . '/start-smsc?password=snailmail&smsc={MODEM_NAME}';
	}
	
	public function Send_Message($phone_number, $message, $company_id, $message_id)
	{
		// get modem data for company
		$modem = $this->Get_Modems($company_id);

		$modem_status_array = $this->Get_Modem_Status($modem);
		$failed_count       = 0;
		$queue_count        = 99;     // default to NOT sending message if we can't get the modem status
		$modem_not_online   = true;
		if ( is_array($modem_status_array) )
		{
			$failed_count = (int) $modem_status_array['failed'];
			$queue_count = $modem_status_array['queued'];
			$modem_not_online = strpos( $modem_status_array['status'], 'online' ) === false ? true : false;
			// require_once('dlhdebug.php');			
			// dlhlog("failed_count=$failed_count, queue_count=$queue_count, modem_not_online=$modem_not_online, modem_status_array=" . dlhvardump($modem_status_array), '/tmp/debug.log' );
		}

		$max_queue_count = self::MAX_QUEUE_COUNT;
		if ( MODE == 'LOCAL' || MODE == 'RC' ) $max_queue_count = self::MAX_QUEUE_COUNT_RC_LOCAL;

		if ( ($queue_count > $max_queue_count) || $modem_not_online || self::FORCE_MODEM_RESTART_TEST || $failed_count >= 5)
		{
			if ( $failed_count >= 5 || self::FORCE_MODEM_RESTART_TEST )
			{
				$this->Restart_Modem($modem_status_array, $modem);
			}
			return self::STATUS_MESSAGE_QUEUE_EXCEEDED;  // now this flag does double duty - queue count exceeded OR modem not online.
		}
		
		// build kannel url
		$kannel_url = $modem['url'] . $this->SEND_URL_SUFFIX . "?username=".$modem['user_name']."&password=".$modem['password'] . "&message_id=" . $message_id . "&to=".$phone_number."&text=".urlencode($message);

		// get url via curl
		$curl = curl_init($kannel_url);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);

		if ( $result === self::SUCCESS_MSG ) return self::STATUS_MESSAGE_SENT;

		return self::STATUS_MESSAGE_NOT_SENT;

	}
	
	
	
	private function Get_Modems($company_id = false, $modem_id = false, $key_by = 'modem_id')
	{
		// get modems
		if ($modems = $this->sms_query_obj->Get_Modem_Data($company_id, $modem_id, $key_by))
		{
			return $modems;
		}
		
		return false;
	}

	
	public function Get_Modem_Status( $modem_in )
	{
		$result_array = array(
			'modem_id'    => ''
			, 'status'    => ''
			, 'received'  => ''
			, 'sent'      => ''
			, 'failed'    => ''
			, 'queued'    => ''
		);
	
		$status_url = $modem_in['url'] . $this->STATUS_URL_SUFFIX;
		$desired_modem = $modem_in['modem_name'];
	
		// get the status information from the server
		$curl = curl_init($status_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = @curl_exec($curl);

		if ($response)
		{
			$xml = @simplexml_load_string($response);

			if ( $xml )
			{
				$modems = $xml->xpath('//smscs/smsc');
				foreach ($modems as $modem)
				{
					// pull out modem data
					$modem_id = (string)$modem->id;         // ex: <id>modem_3924</id>
					$status   = (string)$modem->status;     // ex: <status>online 642s</status>
					$received = (int)$modem->received;      // ex: <received>0</received>
					$sent     = (int)$modem->sent;          // ex: <sent>4</sent>
					$failed   = (int)$modem->failed;        // ex: <failed>0</failed>
					$queued   = (int)$modem->queued;        // ex: <queued>0</queued>
	
					if ( $desired_modem == $modem_id )
					{
						// return $queued;
						
						$result_array['modem_id'] = $modem_id;
						$result_array['status']   = $status;
						$result_array['received'] = $received;
						$result_array['sent']     = $sent;
						$result_array['failed']   = $failed;
						$result_array['queued']   = $queued;
						
						return $result_array;
					}
				}
			}
			
			// didn't find the desired modem.
			return -1;
		
		}
		else
		{
			// unable to get status because we didn't get a response from the status query.
			return -2;
		}
	}


	public function Restart_Modem($modem_status_array, $modem)
	{
		$modem_id  = $modem_status_array['modem_id'];
		$stop_url  = str_replace( '{MODEM_NAME}', $modem_id, $modem['url'] . $this->MODEM_STOP_SUFFIX );
		$start_url = str_replace( '{MODEM_NAME}', $modem_id, $modem['url'] . $this->MODEM_START_SUFFIX );

		// require_once('dlhdebug.php');
		// dlhlog( "modem_id=$modem_id, stop_url=$stop_url, start_url=$start_url", '/tmp/debug.log' );
	
		$curl = curl_init($stop_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($curl);
		// dlhlog( "stop response=" . dlhvardump($response), '/tmp/debug.log' );

        sleep(5);

		$curl = curl_init($start_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($curl);
		// dlhlog( "start response=" . dlhvardump($response), '/tmp/debug.log' );
	}
	
}
