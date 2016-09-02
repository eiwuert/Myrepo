<?php

defined('OPTOUT_CONFIRMATION_MSG') || define('OPTOUT_CONFIRMATION_MSG', 'You have been opted out of further text messages.');
defined('DEFAULT_TRACK_KEY')       || define('DEFAULT_TRACK_KEY', 'GxCE9M3wBLvo1qZLa1qwAI8UBt8');  // generated 2006.05.10, 1:02pm, from http://ds57.tss:8080/test_spacekey_generate.php
defined('TEST_STATPRO_EXCEPTION')  || define('TEST_STATPRO_EXCEPTION', 0);  // for testing Sms_Statpro_Exception, should be 0 except when testing.

/**
 * sms.php 
 * 
 * @desc 
 * 		
 * @version 
 * 		1.0
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @todo 
 *		
 */

require_once('applog.1.php');
require_once('dlhdebug.php');
require_once('logsimple.php');
require_once 'sms.config.php';
require_once 'mysql.4.php';
require_once 'sms.iface.php';
require_once 'statProClient.php';
require_once INCLUDE_DIR.'sms_query.class.php';
require_once INCLUDE_DIR.'kannel.class.php';
require_once INCLUDE_DIR.'timezone.class.php';

class SMS implements SMS_IFace
{
	
	protected $mysql;
	protected $database = 'sms';
	protected $applog;
	protected $sms_query_obj;
	protected $kannel_obj;
	
	const MAX_ATTEMPTS = 4;  // max number of attempts prior to being set to undeliverable status
	const ATTEMPT_GRACE_PERIOD = 30; // grace period after an attempt has failed once in minutes
	const SEND_DELAY = 2250000;
	
	// cache timezone information so we don't
	// have to hit the database every time
	protected $timezones = array();
	
	// military hr/min range that we are allowed to send
	const DEFAULT_TIMEZONE = 'PST';
	const TIMEZONE_START = '0800'; // start
	const TIMEZONE_END = '2000'; // end
	
	// statuses
	const PROCESSING = 'processing';
	const QUEUED = 'queued';
	const DELIVERED = 'delivered';
	const UNDELIVERABLE = 'undeliverable';
	const BLACKLISTED = 'blacklisted';
	const STATHIT_SUFFIX = '_stathit';
	
	// statpro
	const STATPRO_KEY = 'clk';
	const STATPRO_PASS = 'dfbb7d578d6ca1c136304c845';
	const SENT_STAT = 'kannel_sent';
	const FAIL_STAT = 'kannel_fail';
	const UNDELIVERABLE_STAT = 'kannel_undeliverable';
	const REPLY_STAT = 'kannel_reply';
	const REPLY_FAILED = 'kannel_reply_fail';
	const OPT_OUT = 'kannel_opt_out';
	const STATPRO_BLACKLISTED = 'kannel_blacklist';
	const CAMPAIGN_KEYWORD_RECOGNIZED = 'sms_market_reply_';   // to be appended with {CAMPAIGN}
	const QUEUE_EXCEEDED_RESCHED_MINUTES = 1;
	
	function __construct()
	{
		// setup db conn
		$this->Setup_DB();
		
		// instantiate query class
		$this->sms_query_obj = new SMS_Query($this->mysql, DB_NAME);
		
		// instantiate kannel class
		$this->kannel_obj = new Kannel($this->sms_query_obj);

		$this->applog = new Applog('sms');
	}
	
	/**
	 * @desc Send_SMS is used to send an SMS message
	 *	
	 * @param int $phone_number
	 * @param string $message
	 * @param string $campaign
	 * @param string $state
	 * @param date $delivery_date
	 * @param string $company_short
	 * @param string $track_key
	 * @param string $space_key
	 * @param boolean $time_zone_chk
	 * @param int $message_id
	 * @return $message_id
	 */
	public function Send_SMS( $phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key = false, $space_key = false, $time_zone_chk = false)
	{
		if ( !isset($track_key) || trim($track_key) == '' ) $track_key = DEFAULT_TRACK_KEY;
	
		// This method will return an array of 3 variables.
		//   flag:        true on successfully sending the message, false otherwise.
		//   code:        zero on successfully sending message, positive error number otherwise.
		//   msg:         description of error or confirmation with message_id if successful.
		//   message_id:  positive integer message_id if successfully sent, zero otherwise.
		$result_array = array( 'flag' => true, 'code' => 0, 'msg' => '', 'message_id' => 0 );

		try
		{
			// valid company is required to send messages
			if ( !$company_id = $this->sms_query_obj->Get_Company_ID($company_short) )
			{
				$result_array['flag'] = false;
				$result_array['code'] = 1;
				$result_array['msg'] = "invalid company short ($company_short)";
				return $result_array;
			}
			else if ( $this->Check_Blacklist($phone_number) )
			{
				$result_array['flag'] = false;
				$result_array['code'] = 2;
				$result_array['msg'] = "phone number ($phone_number) is blacklisted";
	
				$new_space_key = $this->Fix_Space_Key( $company_short, $space_key );
				$this->Hit_StatPro(self::STATPRO_BLACKLISTED, $track_key, $new_space_key);
	
				return $result_array;
			}
				
			// normalize phone number
			$phone_number = $this->Normalize_Phone($phone_number);
			$phone_number_len = strlen($phone_number);
			if ( $phone_number_len != 7 && $phone_number_len != 10 )
			{
				$result_array['flag'] = false;
				$result_array['code'] = 3;
				$result_array['msg'] = "phone number ($phone_number) has an invalid length ($phone_number_len)";
				return $result_array;
			}
	
			// normalize delivery date
			$delivery_date = ($delivery_date) ? date("YmdHis", strtotime($delivery_date)) : false;
	
			// get our time zone ID
			$time_zone_abbrev = $this->Get_Time_Zone($state);
	
			// We are going to modify the page id on outgoing messages to make sure that our
			// space keys are always accurate.
			$new_space_key = $this->Fix_Space_Key( $company_short, $space_key );
	
			// add message to database
			$message_id = $this->sms_query_obj->Add_Message($campaign, $phone_number, $message, $time_zone_abbrev, $time_zone_chk, $delivery_date, $company_id, $track_key, $new_space_key, $priority);
	
			if ( $new_space_key != $space_key )
			{
				$this->applog->Write(__METHOD__ . ": Changed space key: old_spacekey=$space_key, new_spacekey=$new_space_key, company_short=$company_short, message_id=$message_id" );
			}
	
			// return message id
			$result_array['message_id'] = $message_id;
			$result_array['msg'] = "message successfully sent, phone_number=$phone_number, message_id=$message_id, company short=$company_short";
		}
		catch( Sms_Statpro_Exception $sms_statpro_exception )
		{
			$result_array['flag'] = false;
			$result_array['code'] = 4;
			$result_array['msg'] = __METHOD__ . ": received Sms_Statpro_Exception: " . $sms_statpro_exception->getMessage();
			$this->applog->Write(__METHOD__ . ": received Sms_Statpro_Exception: " . $sms_statpro_exception->getMessage(), LOG_ERR);
			throw $sms_statpro_exception;   // DLH, 2006.05.11, we want Don to get emailed with these kind of exceptions.
		}
		catch( Exception $e )
		{
			$result_array['flag'] = false;
			$result_array['code'] = 4;
			$result_array['msg'] = __METHOD__ . ": received exception: " . $e->getMessage();
			$this->applog->Write(__METHOD__ . ": received exception: " . $e->getMessage(), LOG_ERR);
		}
		
		return $result_array;
	}
	
	public function SMS_Reply($phone_number, $message, $modem_name)
	{
		try
		{
			// In the future, this will have to change: we
			// can't just assume that every response will
			// be routed via eMail to cusomter service
			
			$phone_number = $this->Normalize_Phone($phone_number);
			
			// get modem info
			$modems = $this->sms_query_obj->Get_Modem_Data(NULL, NULL, 'modem_name');
			$modem_data = $modems[$modem_name];
			
			// get company_info
			$company_info = $this->sms_query_obj->Get_Company_Data($modem_data['company_id']);
	
			// insert message into database
			// For date_scheduled - set to '' or false if you want it to be now().
			// Don't set to "now()" because it will get single quotes placed around it which will force it to 0000-00-00 in the database.
			$opt_out_msg_chk = strtr($message, '"', ' ');  // translate all double quotes to spaces
			$opt_out_msg_chk = strtr($message, "'", ' ');  // translate all single quotes to spaces
			$opt_out_msg_chk = strtolower(trim($message));
			if (
				$opt_out_msg_chk == 'out' 
				|| (preg_match('/optout/i', $opt_out_msg_chk) && strlen($opt_out_msg_chk) < 80) 
				)
			{
				// Add their number to the blacklist and send an optout confirmation message.
				$this->Add_To_Blacklist($phone_number);
				$message_id = $this->sms_query_obj->Add_Message(FALSE, $phone_number, $message, 'PST', '', '', $company_info['company_id'], FALSE, FALSE, FALSE, 'incoming', $modem_name, 'blacklisted');
				$this->sms_query_obj->Add_Message('optout', $phone_number, OPTOUT_CONFIRMATION_MSG, 'PST', '', '', $company_info['company_id'], FALSE, FALSE, FALSE, 'outgoing', $modem_name);
				$stat_stuff = array( 'track_key' => '', 'space_key' => '' );
				$this->sms_query_obj->Get_Last_Track_And_Space_Keys($stat_stuff, $phone_number, $modem_name);
				$this->Hit_StatPro(self::OPT_OUT, $stat_stuff['track_key'], $stat_stuff['space_key']);
	
				return $message_id;
			}
				
			$message_id = $this->sms_query_obj->Add_Message(FALSE, $phone_number, $message, 'PST', 'no', '', $company_info['company_id'], FALSE, FALSE, FALSE, 'incoming', $modem_name);
	
			// forward message to modem owner
			if ($company_info['reply_url'])
			{
				$url_array = parse_url($company_info['reply_url']);
				$scheme = strtolower($url_array['scheme']);
	
				// prepare reply url
				$url = $scheme.'://'.((MODE == 'RC') ? 'rc.' : '').$url_array['host'].$url_array['path'];
	
				try
				{
					// forward reply depending on url scheme
					switch($scheme)
					{
						// send data via PRPC
						case 'prpc':
							$result = $this->FWD_Reply_PRPC($url, $phone_number, $message, $message_id, $company_info['company_short'], $stat_stuff['track_key'], $stat_stuff['space_key']);
							if ( !$result ) $this->applog->Write(__METHOD__ . ": FWD_Reply_PRPC failed!: url=$url, phone_number=$phone_number, message=$message, message_id=$message_id, company_short=$company_info[company_short], track_key=$stat_stuff[track_key], space_key=$stat_stuff[space_key]", LOG_ERR);
							break;
		
						// send reply via HTTP post
						default:
						case 'http':
						case 'https':
							$result = $this->FWD_Reply_HTTP($url, $phone_number, $message, $message_id, $company_info['company_short'], $stat_stuff['track_key'], $stat_stuff['space_key']);
							if ( !$result ) $this->applog->Write(__METHOD__ . ": FWD_Reply_HTTP failed!: url=$url, phone_number=$phone_number, message=$message, message_id=$message_id, company_short=$company_info[company_short], track_key=$stat_stuff[track_key], space_key=$stat_stuff[space_key]", LOG_ERR);
							break;
					}
				}
				catch ( Exception $e )
				{
					$this->applog->Write(__METHOD__ . ": Exception trying to forward reply, e=" . $e->getMessage(), LOG_ERR);
					$result = false;
				}
	
				$campaign_info = $this->sms_query_obj->Get_Campaign_Data( $modem_data['company_id'], $message );
				$campaign_id = $campaign_info ? $campaign_info['campaign_id'] : 0;
				
				$stat_stuff = array( 'track_key' => '', 'space_key' => '' );
				$this->sms_query_obj->Get_Last_Track_And_Space_Keys($stat_stuff, $phone_number, $modem_name, $campaign_id);
	
				if ($result)
				{
					$this->sms_query_obj->Update_Message_Status($message_id, self::DELIVERED );
					$this->Hit_StatPro(self::REPLY_STAT, $stat_stuff['track_key'], $stat_stuff['space_key']);
				}
				else
				{
					// We don't have a process for reattempting to forward the reply so
					// I don't think there will ever be more than one attempt.
					$total_attempts = $this->sms_query_obj->Register_Attempt($message_id);
					$status_update = ($total_attempts >= self::MAX_ATTEMPTS) ? self::UNDELIVERABLE : self::QUEUED;
					$this->sms_query_obj->Update_Message_Status($message_id, $status_update );
					$this->Hit_StatPro(self::REPLY_FAILED, $stat_stuff['track_key'], $stat_stuff['space_key']);
				}
			
			
				if ( $campaign_info	)
				{
					// $stat_qualifier = "kannel_" . $campaign_info['campaign'];  // 2006.04.14 - apparently this was supposed to be "kannel_keyword"
					$stat_qualifier = "kannel_keyword";
					$stat_qualifier2 = self::CAMPAIGN_KEYWORD_RECOGNIZED . $campaign_info['campaign']; 
	
					$this->Hit_StatPro($stat_qualifier,  $stat_stuff['track_key'], $stat_stuff['space_key']);
					$this->Hit_StatPro($stat_qualifier2, $stat_stuff['track_key'], $stat_stuff['space_key']);
	
					if ( $result )
					{
						$status_update = self::DELIVERED . self::STATHIT_SUFFIX;
						$this->sms_query_obj->Update_Message_Status_Campaign_Track_Space($message_id, $status_update, $campaign_id, $stat_stuff['track_key'], $stat_stuff['space_key'] );
					}
					else
					{
						$total_attempts = $this->sms_query_obj->Register_Attempt($message_id);
						$status_update = ($total_attempts >= self::MAX_ATTEMPTS) ? self::UNDELIVERABLE : self::QUEUED;
						$status_update .=  self::STATHIT_SUFFIX;
						$this->sms_query_obj->Update_Message_Status_Campaign_Track_Space($message_id, $status_update, $campaign_id, $stat_stuff['track_key'], $stat_stuff['space_key'] );
					}
				}
			}
		}
		catch ( Exception $e )
		{
			return -1;
		}
		
		return $message_id;

	}
	
	public function Scheduled_SMS()
	{
		// no time limit
		set_time_limit(0);
		
		// get all queued messages from database
		if ($messages = $this->sms_query_obj->Get_Scheduled_Messages())
		{
			// send out the queued messages
			foreach ($messages as $message)
			{
				// check message status
				if (!$check = $this->sms_query_obj->Check_Message_Status($message['message_id'], 'queued'))
					continue;
				
				// normalize_phone
				$message['phone_number'] = $this->Normalize_Phone($message['phone_number']);
				
				if ( ($message['message'] != OPTOUT_CONFIRMATION_MSG) && $this->Check_Blacklist($message['phone_number']) )
				{
					$this->sms_query_obj->Update_Message_Status($message['message_id'], self::BLACKLISTED);
					continue;
				}
			
				// time zone check
				if ($message['time_zone_id'] && $message['time_zone_restrict'] == 'yes')
				{
					if (!$this->Check_Time_Zone($message['abbrev']))
					{
						// generate next delivery date
						// $next_delivery_date = date("Ymdhis", strtotime("+ ".self::ATTEMPT_GRACE_PERIOD." minutes", mktime()));
							
						// update message back to queued with a new delivery date
						// $this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, $next_delivery_date);	
						$this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, self::ATTEMPT_GRACE_PERIOD);
						
						// props out to mike g
						continue;
					}
				}
				
				if($message['attempts'] >= self::MAX_ATTEMPTS)
				{
					$this->sms_query_obj->Update_Message_Status($message['message_id'], self::UNDELIVERABLE );	
					// hit stat pro stat
					$this->Hit_StatPro(self::UNDELIVERABLE_STAT, $message['track_key'], $message['space_key']);
				}
				else 
				{
					// update status to processing			
					$this->sms_query_obj->Update_Message_Status($message['message_id'], self::PROCESSING);	
					
					// send message
					$sent_result = $this->kannel_obj->Send_Message($message['phone_number'], $message['message'], $message['company_id'], $message['message_id']);
					
					if ( $sent_result == Kannel::STATUS_MESSAGE_QUEUE_EXCEEDED )
					{
						// update message back to queued with a new delivery date
						// $this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, $next_delivery_date);
						$this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, self::QUEUE_EXCEEDED_RESCHED_MINUTES);

						continue;
					}

					// Do NOT register attempt here because this is not a true attempt.  Success or failure
					// here merely indicates if we inserted the message to the kannel queue.  If we got a
					// password wrong or kannel went down, we wouldn't be able to insert to the queue but this
					// wouldn't mean the message is undeliverable.
					// ----------------------------------------------------------------------------------------
					// $total_attempts = $this->sms_query_obj->Register_Attempt($message['message_id']);

					if ( $sent_result == Kannel::STATUS_MESSAGE_SENT )
					{
						// half second delay so it wont choke the kannel server
						usleep(self::SEND_DELAY);
					}
					else 
					{
						$this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, self::ATTEMPT_GRACE_PERIOD);
						// Do NOT update statpro here for the reason explained above.
						// $this->Hit_StatPro(self::FAIL_STAT.$total_attempts, $message['track_key'], $message['space_key']);
					}
				}
				
			}
			
			return true;
		}
		else 
		{
			return false;
		}
	}


	// called by the cron
	public function Catch_Messages_Stuck_In_Processing()
	{
		$affected_rows = $this->sms_query_obj->Catch_Messages_Stuck_In_Processing();
		if ( $affected_rows > 0 ) $this->applog->Write(__METHOD__ . ": moved $affected_rows message from processing back to queued");
	}
	
	
	private function Setup_DB()
	{
		if (!$this->mysql)
		{
			$this->mysql = new Mysql_4(DB_HOST, DB_USER, DB_PASS, DEBUG);
			$this->mysql->Connect();
		}
	}
	
	public function Get_Time_Zone($state)
	{
		
		// assume we can't find it
		$zone_abbrev = FALSE;
		
		$zone = new Timezone($this->mysql, $this->database);
		
		if ($zone->Find_By_State($state))
		{
			
			// returning this...
			$zone_abbrev = $zone->Abbreviation();
			
			// caching this here saves a query later
			$this->timezones[$zone->Abbreviation()] = $zone->Offset();
			
		}
		
		return ($zone_abbrev) ? $zone_abbrev : self::DEFAULT_TIMEZONE;
		
	}
	
	public function Check_Time_Zone($abbrev)
	{
		if (!isset($this->timezones[$abbrev]))
		{
			// get our timezone information
			$zone = new Timezone($this->mysql, $this->database);
		
			if ($zone->Load($abbrev))
			{
				// cache this for later
				$offset = $zone->Offset();
				$this->timezones[$abbrev] = $offset;
			}
		}
		else
		{
			// get our cached offset
			$offset = $this->timezones[$abbrev];
		}

		// 1 hour == 3600 seconds
		$time = (time() + ($offset * 3600));
		$hour = gmdate('Hi', $time);
		
		// are we between our valid times?
		$valid = (($hour >= self::TIMEZONE_START) && ($hour <= self::TIMEZONE_END));
		
		//  always return true until we apply the timezone check
		return $valid;
	}
	
	private function Valid_Message($message_id)
	{
		$message_data = $this->sms_query_obj->Get_Message_Data($message_id);
		return ($message_data['message_status'] == self::QUEUED && $message_data['attempts'] < self::MAX_ATTEMPTS );
		
	}
	
	private function Hit_StatPro($stat, $track_key, $space_key)
	{
		try
		{
			// if ( defined(MODE) && 'RC' == strtoupper(MODE) ) $this->applog->Write("Hit_StatPro: stat=$stat, track_key=$track_key, space_key=$space_key");
			$this->applog->Write(__METHOD__ . ": Hit_StatPro: stat=$stat, track_key=$track_key, space_key=$space_key");
		
			$input_space_key = $space_key;
			
			if ($track_key && $space_key && $stat)
			{
				if (!$this->statpro_client)	
				{
					// instantiate statproskie
					$sp_bin = 'spc_';
					$stat_pro_exe_key = $sp_bin . self::STATPRO_KEY . ((MODE == 'LIVE') ? '_live' : '_test');
					$this->statpro_client = new statProClient($stat_pro_exe_key);
				}
				
				$this->statpro_client->recordEvent( self::STATPRO_KEY , self::STATPRO_PASS , $track_key, $space_key, $stat);
			}
		}
		catch( Exception $e )
		{
			$this->applog->Write(__METHOD__ . ": exception hitting stat: stat=$stat, track_key=$track_key, space_key=$space_key, e=" . $e->getMessage(), LOG_ERR);
		}
	}
	
	protected function Normalize_Phone($phone_number)
	{
		
		$phone_number = preg_replace ('/\D+/', '', $phone_number);
		if (strlen($phone_number) > 10) $phone_number = substr($phone_number, -10);
		
		return $phone_number;
		
	}
	

	public function Check_Blacklist( $phone_number )
	{
		$phone_number = $this->Normalize_Phone($phone_number);
		return $this->sms_query_obj->Check_Blacklist($phone_number);
	}
	
	public function Add_To_Blacklist( $phone_number )
	{
		$phone_number = $this->Normalize_Phone($phone_number);
		$this->sms_query_obj->Update_Blacklist($phone_number);
		return true;
	}
	
	public function Remove_From_Blacklist( $phone_number )
	{
		$phone_number = $this->Normalize_Phone($phone_number);
		$this->sms_query_obj->Remove_Blacklist($phone_number);
		return true;
	}
	
	private function FWD_Reply_PRPC($url, $phone_number, $message, $message_id, $company_short, $track_key, $space_key)
	{
		if ( MODE == 'LOCAL' )
		{
			return true;          // DO NOT FORWARD REPLY IN LOCAL MODE!  TESTING ONLY!
		}		
		
		include_once('prpc/client.php');
		try 
		{
			$prpc_obj = new PRPC_Client($url);	
			return $prpc_obj->SMS_Reply($phone_number, $message_id, $message, $company_short, $track_key, $space_key);
		}
		catch(Exception $e)
		{
			return FALSE;
		}
		
	}
	
	private function FWD_Reply_HTTP($url, $phone_number, $message, $message_id, $company_short, $track_key, $space_key)
	{
		if ( MODE == 'LOCAL' )
		{
			return true;          // DO NOT FORWARD REPLY IN LOCAL MODE!  TESTING ONLY!
		}		
		
		$field_array['phone_number'] = $phone_number;
		$field_array['message'] = $message;
		$field_array['company_short'] = $company_short;
		$field_array['message_id'] = $message_id;
		$field_array['track_key'] = $track_key;
		$field_array['space_key'] = $space_key;
		
		foreach ($field_array as $key => $val)
		{
			$url_fields .= urlencode($key) .'='. urlencode($val) .'&';
		}
		$url_fields = substr ($url_fields, 0, -1);
		
		$curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, 15);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $url_fields);
		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}
		$response = curl_exec($curl);
		
		return preg_match('/SUCCESS/i', $reponse);
	}


	// This is called from kannel
	public function Update_Message_Status( $message_id, $status )
	{
		if ( is_numeric($message_id) && $message_id > 0 )
		{
			$message = $this->sms_query_obj->Get_Message_Data($message_id);
					
			$total_attempts = $this->sms_query_obj->Register_Attempt($message['message_id']);

			// dlhlog("message_status=" . $message['message_status'] . ", status=$status, message_id=" . $message['message_id'] . ", total_attempts=$total_attempts");
			
			switch( strtolower(trim($status)) )
			{
				case 'success' :
					$this->sms_query_obj->Update_Message_Status($message['message_id'], self::DELIVERED);
					$this->Hit_StatPro(self::SENT_STAT , $message['track_key'] , $message['space_key']);
					break;
	
				case 'failed'  :
					// Once a message is marked DELIVERED, we will not go back and mark it UNDELIVERABLE.
					// This is to try and be safe in case there are multiple attempts to send a message
					// and a failed attempt comes after a successful attempt.
					if ( $message['message_status'] != self::DELIVERED )
					{
						if ($total_attempts >= self::MAX_ATTEMPTS)
						{
							$this->sms_query_obj->Update_Message_Status($message['message_id'], self::UNDELIVERABLE );
							$this->Hit_StatPro(self::UNDELIVERABLE_STAT, $message['track_key'], $message['space_key']);
						}
						else
						{
							$this->sms_query_obj->Update_Message_Status($message['message_id'], self::QUEUED, self::ATTEMPT_GRACE_PERIOD);
							$this->Hit_StatPro(self::FAIL_STAT.$total_attempts, $message['track_key'], $message['space_key']);
						}
					}
					break;
			}
		}
	}
	

	private function Fix_Space_Key( $company_short, $space_key )
	{
		if ( TEST_STATPRO_EXCEPTION == 1 ) throw new Sms_Statpro_Exception("TEST: Sms_Statpro_Exception: Unable to fix space_key: company_short=$company_short, space_key=$space_key");
		if ( TEST_STATPRO_EXCEPTION == 2 ) throw new Exception("TEST: Exception: Unable to fix space_key: company_short=$company_short, space_key=$space_key");
	
		$new_space_key = $space_key;
	
		if ( !isset($company_short) || trim($company_short) == '' ) return $new_space_key;
	
		$company_short = strtolower($company_short);

		// the enterprise page_id values were obtained from Andrew who looked them up in some database.
		// the default space keys were generated 2006.05.10, 1:07 pm,
		// from http://ds57.tss:8080/test_spacekey_generate.php and assumed promo_id = 10000, promo_sub_code = ''.
		$enterprise_pageid_array = array( 
			  'pcl' => array( '1807', '-gW1mMj1dJkIWvU8j6Y2R3gRFbf' )    // preferredcashloans.com
			, 'ufc' => array('17212', 'd83xosrGwgVTOn5LosFjPyJs1gd' )    // usfastcash.com
			, 'ucl' => array('39417', 'P2HmtUxDitmlZj8E7TKISSZOHFe' )    // unitedcashloans.com
			, 'ca'  => array('39413', 'mtWQLpUeBPJOYuFbqRZSDjRpE4c' )    // ameriloan.com
			, 'd1'  => array('39121', 'vVOoCZT2LPlpyio01ZNlBj0EbR1' )    // 500fastcash.com
			, 'ic'  => array('48230', 'DvKDyikmT-Orot6QceavaP5HQ4d' )    // ecash_impact
			, 'ssc' => array('46215', 'TxuFnlZkQ3-QxQuBeHcX-SaIaJe' )    // smart shopper card (Matt Piper)
		);

		if ( ! isset($enterprise_pageid_array[$company_short]) ) return $new_space_key;
		
		$enterprise_page_array = $enterprise_pageid_array[$company_short];

		// if the space_key is empty then return the default space key for the company short.
		if ( !isset($space_key) || trim($space_key) == '' ) return $enterprise_page_array[1];

		require_once('statpro_client.php'); 
		require_once('prpc2/client.php'); 
		
		$got_new_space_key = false;
		
		for ( $attempt_counter = 1; $attempt_counter <= 5 && ! $got_new_space_key; $attempt_counter++ )
		{
			try
			{
				$ent_pro = new Prpc_Client2('prpc://live.1.enterprisepro.epointps.net/'); 
				$space_info = $ent_pro->Get_Space_Definition($space_key); 
			
				if ( is_array($space_info) && isset($space_info['space_id']) )
				{
					$mode = (MODE == 'LIVE') ? 'live' : 'test';
					$exe = '/opt/statpro/bin/spc_catch_'.$mode;
					$statpro_client = new StatPro_Client($exe,'-v','catch','bd27d44eb515d550d43150b9b'); 
					$new_space_key = $statpro_client->Get_Space_Key($enterprise_page_array[0], $space_info['promo_id'], $space_info['promo_sub_code']);
					$got_new_space_key = true;  // obsolete now that we are returning the new_space_key in the next statement.
					return $new_space_key;
				}
				else
				{
					$this->applog->Write(__METHOD__ . ": space_info is NOT valid, spacekey=$space_key, company_short=$company_short, attempt_counter=$attempt_counter, space_info=" . logsimpledump($space_info), LOG_NOTICE );
				}
			}
			catch ( Exception $e )
			{
				// do nothing, loop will try 5 times and then default to current space key.
				$this->applog->Write(__METHOD__ . ": exception trying to get new space_key, spacekey=$space_key, company_short=$company_short, attempt_counter=$attempt_counter, e=" . dlhvardump($e,false), LOG_ERR );
			}
		}

		// Approach changed - now we want to throw an exception if we can't fix the space key
		// in order that Don will automatically get an email and be able to tell Rodric exactly
		// when we were sent invalid space keys or when the prpc call to decode a space_key fails.
		throw new Sms_Statpro_Exception("Unable to fix space_key: company_short=$company_short, space_key=$space_key");

		// if we get here it means we were not able to "fix" the space_key for some reason.
		return $enterprise_page_array[1];
	}


	public function Count_Outgoing_Queued()
	{
		return $this->sms_query_obj->Count_Outgoing_Queued();
	}
	
	public function Get_Unsuccessful_Modems( $last_n_hours = 3 )
	{
		return $this->sms_query_obj->Get_Unsuccessful_Modems($last_n_hours);
	}
	
}

class Sms_Statpro_Exception extends Exception
{
}
	
