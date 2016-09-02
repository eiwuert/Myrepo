#!/usr/bin/php
<?php
/**
 * Just  runs around and collects emails from the Mail_Queue sends them then does 
 * some awesome with it.
 */
/* Changelog
 * 
 * 2008-07-24: There's an issue where this script quits in the middle of operation
 *             and it does not remove the old lockfile. This now checks for stale 
 *             lockfiles. This will likely only cover up the other issue, but this is 
 *             needed.
 */
ini_set('include_path', '.:/usr/share/php:'.ini_get('include_path'));
require_once('Mail.php');

require_once('Mail/mail.php');
require_once('Mail/mime.php');
require_once('Mail/smtp.php');
require_once('../lib/smtp_mail.php');
require_once('mysqli.1.php');
require_once('../lib/config.php');
require_once('../lib/mail_queue.php');
require_once('../lib/condor.class.php');
require_once('../lib/security.php');
require_once('prpc/client.php');

define('RUN_AS_CRON',TRUE);

class Send_Mails
{
	const FAIL = -1;
	const RETRY = 0;
	const PASS = 1;
	const LOCK_FILE_FORMAT = '/tmp/condor_send_mail_acc%d.lock';
	const MAX_REATTEMPTS = 5;
	const DEBUG_MODE = TRUE;
	const FAIL_ALL = FALSE;
	
	private $mysqli;
	private $mode;
	private $mail_queue;
	private $condor;
	private $account;
	private $user;
	private $account_id;
	private $smtp;
	private $condor_api;
	private $condor_applog;
	
	private static $response_code_map = array(
		'-1' => 'no_response',
		200 => 'success',
		211 => 'system_status_message',
		214 => 'help_message',
		220 => 'service_ready',
		221 => 'service_closing',
		250 => 'request_completed',
		251 => 'user_nonlocal_will_forward',
		252 => 'accept_and_attempt_delivery',
		354 => 'start_message_input',
		421 => 'service_unavailable',
		450 => 'mailbox_unavailable',
		451 => 'command_aborted_server_error',
		452 => 'command_aborted_insufficient_storage',
		500 => 'command_syntax_error',
		501 => 'command_argument_error',
		502 => 'command_unimplemented',
		503 => 'bad_command_sequence',
		504 => 'command_parameter_unimplemented',
		521 => 'domain_not_accepting_mail',
		530 => 'access_denied',
		550 => 'user_mailbox_unavailable',
		551 => 'user_notlocal_try',
		552 => 'storage_allocation_exceeded',
		553 => 'invalid_mailbox_name',
		554 => 'transaction_failed',
	);
	
	public function __construct($mode,$account_id)
	{
		$this->mysqli = MySQL_Pool::Connect('condor_' . $mode);
		$this->mode = $mode;
		$this->account_id = $account_id;
		if(!$this->mysqli instanceof MySQLi_1)
		{
			throw new Exception("could not connect to $mode database.");
		}
		$this->mail_queue = new Mail_Queue($mode, $this->mysqli);
		$this->account = $this->Load_Pop_Account_By_Id($account_id);
		if(is_object($this->account))
		{
			$this->user = $this->Get_Company_Data($this->account->company_id);
		}
		else 
		{
			throw new Exception("Could not load account data for $account_id");
		}
		$opts = Array(
				'Host' => $this->account->mail_server,
				'Port' => $this->account->mail_port,
				'User' => $this->account->mail_user,
				'Password' => $this->account->mail_pass,
				'persist' => false
			);
		$this->smtp = new SMTP_Mail($opts);
		
		//Setup our applog with a sitename that might help us track this
		//stuff
        $applog_site = (isset($this->user->name)) ? $this->user->name : $account_id;
        $this->condor_applog = Condor_Applog::getInstance(NULL, NULL, NULL, "SendMail($applog_site)", NULL);

	}
	
	/**
 	 * Locks things down
 	 *
 	 */
	public function Lock()
	{
		$file = $this->Get_Lock_File();
		if(!file_exists($file))
		{
			//If we lock things, make sure we unlock if 
			//the script exits.
			register_shutdown_function(array($this,'Unlock'));
			file_put_contents($file, getmypid());
		}
		else
		{
			// Check if the process is still alive
			$lockpid = file_get_contents($file);
			
			$running = posix_kill($lockpid, 0);

			if (posix_get_last_error() == 1)
				$running = TRUE;

			if ($running == FALSE)
			{
				// Stale lockfile
				register_shutdown_function(array($this,'Unlock'));
				file_put_contents($file, getmypid());
			}

		}
	}

	/**
 	 * Are things locked?
 	 *
 	 * @return boolean
 	 */
	public function isLocked()
	{
		$file = $this->Get_Lock_File();

		// If the file does not exist, return FALSE
		if (file_exists($file) == FALSE)
			return FALSE;
		
		$lockpid = file_get_contents($file);

		$running = posix_kill($lockpid, 0);

		if (posix_get_last_error() == 1)
			$running = TRUE;

		// FALSE if the lockfile is stale
		// TRUE  if the lockfile is there and not stale
		return $running;
	}

	/**
 	* Unlocks things
 	*
 	*/
	public function Unlock()
	{
		$file = $this->Get_Lock_File();
		if(file_exists($file))
		{
			unlink($file);
		}
	}
	/**
	 * returns the name of the lock file
	 */
	public function Get_Lock_File()
	{
		return sprintf(self::LOCK_FILE_FORMAT,$this->account_id);	
	}
	
	/**
	 * Sends all new mails
	 *
	 */
	public function Get_New_Mails()
	{
		//I only really care about newly queued mails for the last like 
		//12 hours or so just to kind of put a limit on this deal.
		self::Display("Gathering 'queued' emails");
		$start_date = date('YmdHis',(time() - 86400));
		$types = array(
			'queued',
		);
		$mails = $this->mail_queue->Get_Queued_Mails($types, $start_date, NULL, $this->account_id);

		$total = count($mails);
		echo "Found $total messages to send.\n";
		$i=0;
		foreach($mails as $mail)
		{
			$i++;
			echo "Sending $i of $total\n";
			$this->Send_Mail($mail);
		}
	}
	
	/**
	 * Grab anything that's in the requeued status from 24hours to 6 minutes
	 * ago and try and send it again.
	 */
	public function Get_Requeue_Mails()
	{
		$start_date = date('YmdHis',(time() - 86400));
		$end_date = date('YmdHis',(time() - 360));
		$types = array(
			array('type' => 'RETRY'),
		);
		$mails = $this->mail_queue->Get_Queued_Mails($types, $start_date, $end_date, $this->account_id);
		foreach($mails as $mail)
		{
			$this->Send_Mail($mail);
		}
	}
	
	/**
	 * Mark anything that's in queued or requeued and is older
	 * than 24 hours as failed since we won't ever try and send it again.
	 */
	public function Mark_Old_Failed()
	{
		$end_date = date('YmdHis',(time() - 86401));
		$types = array(
			'queued',
			'requeued',
			array('type' => 'RETRY'),
		);
		$mails = $this->mail_queue->Get_Queued_Mails($types, NULL, $end_date, $this->account_id);
		foreach($mails as $key=>$mail)
		{
			$this->mail_queue->Update_Status($mail->mail_queue_id,'failed', 'FAIL','Queue entry over 24 hours old.');
		}
	}
	
	
	/**
	 * Takes an object representing a row in the mail_queue
	 * and then actually sends the mail and updates the status accordingly
	 */
	private function Send_Mail($obj)
	{
		$to = $this->Get_To($obj->dispatch_id);
		echo "To is: $to\n";

		$queue_id = $obj->mail_queue_id;

		echo "Queue ID is $queue_id\n";

		if(is_string($to) && strpos($to,'@') !== FALSE)
		{
			//if we're set to fail, don't even try, just fail
			if(self::FAIL_ALL === TRUE)
			{
				self::Display("Failing $queue_id because FAIL_ALL is set");
				$this->condor_applog->Write("Failing $queue_id because FAIL_ALL was set.",1);
				$this->mail_queue->Update_Status($queue_id,'failed', 'FAIL', 'FAIL_ALL debug mode.');
				return;
			}
			self::Display("Attempting to send document: {$obj->document_id}");
			try
			{
			$doc_obj = $this->condor_api->Find_By_Archive_Id($obj->document_id);
			}
			catch (Exception $e)
			{
				print_r($e);
				die();
			}
			var_dump($doc_obj);
			if($doc_obj !== FALSE && !empty($doc_obj->data))
			{
				$mime = new Mail_mime("\n");
				 
				        
				//var to keep count of all the number of attachments.
				$attachments = 0;
				//Send plain text as plain text, HTML as html and anything else
				//send as an attachment just to make sure
				if($doc_obj->content_type == CONTENT_TYPE_TEXT_PLAIN)
				{
					self::Display("\tPreparing to send as plain text.");
					$mime->setTXTBody($doc_obj->data);
				}
				elseif($doc_obj->content_type == CONTENT_TYPE_TEXT_HTML)
				{
					self::Display("\tPreparing to send html.");
					$mime->setHTMLBody($doc_obj->data);
					//provide a plain text version just incase
					$mime->setTXTBody($this->Clean_Email($doc_obj->data));
				}
				else
				{
					self::Display("\tPreparing to send unknown format as attachment.");
					$this->Add_Attachment($mime, array($doc_obj), $attachments);
					$mime->setTXTBody('The document was not of a standard email type. It has been sent as an attachment.');
				}
				$this->Add_Attachment($mime, $doc_obj->attached_data, $attachments);
				
				if(isset($doc_obj->subject) && !empty($doc_obj->subject))
				{
					$subject = $doc_obj->subject;
				}
				else 
				{
					$subject = "Important Document";
				}
				$head = Array(
					'From' => $this->account->mail_from,
					'To' => $to,
					'Reply-To' => $this->account->reply_to,
					'Subject' => $subject,
					'x-archive-id' => $obj->document_id,
					'x-dispatch-id' => $obj->dispatch_id,
					'x-queue-id' => $obj->mail_queue_id
				);
				$body = $mime->get();
				$head = $mime->headers($head);
				$mail_from = 'bounces+'.$obj->document_id.'@condoremailservices.com';
				
				try 
				{
					self::Display("\tSending to $to");
					//Basically reconnect every 10 emails
					//to hopefully keep us from timing out.
					$ret = $this->smtp->send($to, $head, $body, $mail_from);
					//We probably failed for some reason
					if($ret === FALSE)
					{
						$code = $this->smtp->getResponseCode();
						if($code == -1)
						{
							//WE failed with no response from the server, generally
							//means the connection timed out. Reconnect to the smtp
							//server and try again.
							$this->smtp->Connect(true);
							$this->condor_applog->Write("No response from server. Reconnecting and reattempting.");
							$ret = $this->smtp->send($to, $head, $body, $mail_from);
						}
						//Now we know we've either had a real problem with the message format
						//and got no response even after reconnecting, or we actually got 
						//a response and need to check to see what it is to determine what
						//to do.
						if($ret === FALSE)
						{
							$code = $this->smtp->getResponseCode();
							$mesg = $this->smtp->getResponseMessage();
							self::Display("\tFailed - $code / $mesg");
							if($code == 250 || $code == 251 || $code == 252)
							{
								//For some reason you get this back sometimes, and
								//it's not an actual failure, so just update it to SENT
								//and move along
								self::Display("\tIt successfully sent.");
								$type = 'SENT';
							}
							elseif($code >= 500)
							{
								$type = 'FAIL';
								self::Display("\t\t500 Level error. Failing forever.");
								$this->condor_applog->Write("Encountered 500 level error($code) mailing $queue_id");
							}
							else 
							{
								$types = array(
									'requeued',
									array('type' => 'RETRY'),
								);
								$attempts = $this->mail_queue->Get_History_Count($obj->dispatch_id, $types);
								if($attempts > self::MAX_REATTEMPTS)
								{
									self::Display("\t\tExceeded max retry attempts. Failing forever.");
									$this->condor_applog->Write("Exceeded max reattempts. Failing $queue_id. ($code|$mesg)");
									$type = 'FAIL';
								}
								else
								{
									self::Display("\tRetrying for the ".($attempts+1)."time.");
									$type = 'RETRY';
								}
							}
							if($code != '-1')
							{
								$status = $type.'_'.self::$response_code_map[$code].'_'.$code;
							}
							else 
							{
								$status = 'FAIL_NO_RESPONSE';
							}
						}
						else 
						{
							//The Retry worked, so succcess!
							self::Display("\tIt successfully sent.");
							$status = 'SENT';
							$type = 'SENT';
							$mesg = false;
						}
					}
					else 
					{
						//Initial sending worked!
						self::Display("\tIt successfully sent.");
						$status = 'SENT';
						$type = 'SENT';
						$mesg = false;	
					}
					//Time to rset the connection, just to make sure.
					$this->smtp->rset();
					echo "Updating queue id: $queue_id\n";
					$this->mail_queue->Update_Status($queue_id, $status, $type, $mesg);
					echo "Updated..\n";
															
				}
				catch(Exception $e)
				{
					//just to make sure!
					self::Display("Error : ".$e->getMessage());
					$types = array(
						'requeued',
						array('type' => 'RETRY'),
					);
					$attempts = $this->mail_queue->Get_History_Count($obj->dispatch_id, $types);
					if($attempts > self::MAX_REATTEMPTS)
					{
						self::Display("\t\tExceeded max retry attempts. Failing forever.");
						$type = 'FAIL';
						$status = 'MAIL_EXCEPTION';
					}
					else
					{
						self::Display("\tRetrying for the ".($attempts+1)."time.");
						$type = 'RETRY';
						$status = 'requeued';
					}
					//now that we've got that we'll remove all remove this account/connection
					$this->mail_queue->Update_Status($queue_id, $status, $type, $e->getMessage());
				}
			}
			else 
			{
				$this->condor_applog->Write("Failed to load document {$obj->document_id}");
			}
		}
		else 
		{
			$this->mail_queue->Update_Status($queue_id,'failed');
		}
	}
	
	/**
	 * Adds an attachment to a mime message. Assumes that it'll be 
	 *
	 * @param object $mime
	 * @param object $attached_data
	 */
	private function Add_Attachment($mime, $attached_data, &$attachments)
	{
		if(!is_array($attached_data)) $attached_data = array($attached_data);
		foreach($attached_data as $object)
		{
			if(is_object($object))
			{
				if(isset($object->uri) && $object->uri != 'NULL' && !empty($object->uri))
				{
					$sugg_name = $object->uri;
				}
				else 
				{
					//if we have no URI try and do the best we can to get a name
					if(isset($object->template_name))
					{
						$sugg_name = str_replace(
							Array(' ','/',"\\"),
							Array('_','',''),
							$object->template_name);
					}
					else 
					{
						$sugg_name = 'document';
					}
					$sugg_name = $sugg_name.'_'.($attachments + 1).Filter_Manager::Get_Extension($object->content_type);
				}
				$res = $mime->addAttachment(
					$object->data,
					$object->content_type,
					$sugg_name,
					FALSE
				);
				if($res === true)
				{
					$attachments++;
				}
			}
		}
	}
	
	/**
	 * Load up the recipient based on a dispatch id
	 *
	 * @param int $dispatch_id
	 * @return string
	 */
	private function Get_To($dispatch_id)
	{
		$query = "
			SELECT
				recipient
			FROM
				document_dispatch
			WHERE
				document_dispatch_id = $dispatch_id
		";
		try 
		{
			$ret_val = FALSE;
			$res = $this->mysqli->Query($query);
			if(($row = $res->Fetch_Object_Row()))
			{
				$ret_val = $row->recipient;	
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return $ret_val;
	}
	
	/**
	 * Takes an HTML email thing and cleans it up as plain text 
	 * The formatting will be ugly I'm sure.
	 *
	 * @param string $data
	 */
	private function Clean_Email($data)
	{
		static $replace = array(
			'&nbsp;' => '&#32;',
			'<br>' => "\r\n",
			'<br />' => "\r\n",
			'<br/>' => "\r\n",
			'<tr>' => "\r\n",
		);
		//I know this seems dumb, but we first replace \r\n with \n and
		//then \n with \r\n so that if there are any existing \r\n they do 
		//not become \r\r\n
		$str = str_replace("\n","\r\n",str_replace("\r\n","\n",$data));
		$str = str_replace(array_keys($replace),$replace,$str);
		$str = html_entity_decode(strip_tags($str));
		$str = preg_replace('/[ \t]{2,}/',' ',$str);

		$return = '';
		$str_a = array_map('trim',explode("\r\n",$str));
		$prepend_str = '';
		foreach($str_a as $key => $line)
		{
			//Basically prepend anything left over from the last line to this one
			$line = $prepend_str.$line;
			if(strlen($line) <= 998)
			{
				$return .= "$line\r\n";
				$prepend_str = '';
			}
			else 
			{
				$pos = 998;
				while($line[$pos] != ' ' && $line[$pos] != "\t" && $pos > 0)
				{
					$pos--;
				}
				//Add up to $pos to the return value, set the rest of the line to be prepended
				//to the next line
				$return .= substr($line,0,$pos)."\r\n";
				$prepend_str = substr($line,$pos+1);
			}
		}	
		//If there's anything remaining, break it apart aswell.
		if(!empty($prepend_str))
		{
			$len = strlen($prepend_str);
			while($len > 998)
			{
				$pos = 998;
				while($prepend_str[$pos] != ' ' && $prepend_str[$pos] != "\t" && $pos > 0)
				{
					$pos--;
				}
				$return .= substr($prepend_str, 0, $pos)."\r\n";
				$prepend_str = substr($prepend_str, $pos+1);
				$len = strlen($prepend_str);
			}
			$return .= $prepend_str."\r\n";
		}
		return trim($return);
	}
	
	/**
	 * Kind of a hack to get the company_id,user_id,api_auth so that 
	 * we can load documents up properly
	 *
	 * @param int $company_id
	 */
	private function Get_Company_Data($company_id)
	{
			$query = 'SELECT 
				agent.login,
				agent.crypt_password,
				company.name
			FROM
				condor_admin.agent
			JOIN condor_admin.system USING (system_id)
			JOIN condor_admin.company ON (agent.company_id = company.company_id)
			WHERE
				system.name_short=\'condorapi\'
			AND
				condor_admin.agent.company_id = '.$company_id.';
			';
		
		try 
		{
			$res = $this->mysqli->Query($query);
			if(($row = $res->Fetch_Object_Row()))
			{
				$username = $row->login;
				$password = Security::Decrypt($row->crypt_password);
				$api_auth = "$username:$password";
				switch($this->mode)
				{
					case MODE_DEV:
						$url = 'condor.4.edataserver.com.ds79.tss/condor_api.php';
						break;
					case MODE_RC:
						$url = 'rc.condor.4.edataserver.com/condor_api.php';
						break;
					case MODE_LIVE:
						$url = 'condor.4.edataserver.com/condor_api.php';
						break;
				}
				$prpc_url = 'prpc://'.$api_auth.'@'.$url;
				$this->condor_api = new PRPC_Client($prpc_url, TRUE);
				$this->condor_api->_prpc_use_pack = FALSE;
			}
		}
		catch (Exception $e)
		{
			Reported_Exception::Report($e);
			return FALSE;
		}
		
		return $row;
	}
	
	/**
	 * Load the Pop Account Info based on Id
	 */
	private function Load_Pop_Account_By_Id($id)
	{
		$s_id = $this->mysqli->Escape_String($id);
		$query = "
			SELECT
				company_id,
				account_id,
				reply_to,
				from_domain,
				mail_server,
				mail_port,
				mail_box,
				mail_user,
				mail_pass,
				mail_from,
				direction
			FROM
				condor_admin.pop_accounts
			WHERE
				account_id = '{$s_id}'
			AND 
				direction
			IN
				('OUTGOING','BOTH')
			LIMIT 1
		";
		try 
		{
			$res = $this->mysqli->Query($query);
			if($row = $res->Fetch_Object_Row())
			{
				$row->mail_pass = Security::Decrypt($row->mail_pass);
				
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		return $row;
	}
	
	/**
	 * Dispalys text if we're in debug mode, otherwise does ntohing
	 *
	 * @param string $str
	 * */
	public static function Display($str)
	{
		if(self::DEBUG_MODE === TRUE)
		{
			echo $str."\n";
		}
	}
}



/**
 * Takes a Send_Mails object and runs 
 * through the process of sending/resending
 * and failing emails. Returns the time
 * it took to execute.
 *
 * @param Send_Mails $send_mailer
 * @return float
 */
function Run_Job(Send_Mails $send_mailer)
{
	$start_time = microtime(TRUE);
	$send_mailer->Get_New_Mails();
	$send_mailer->Get_Requeue_Mails();
	$send_mailer->Mark_Old_Failed();
	return (microtime(TRUE) - $start_time);
}

$mode = strtoupper($argv[1]);
if(!in_array($mode,array(MODE_LIVE,MODE_RC,MODE_DEV)))
{
	throw new Exception("Invalid mode $mode");
}
$account_id = $argv[2];
if(!is_numeric($account_id))
{
	throw new Exception("Invalid account id.");
}
$send_mailer = new Send_Mails($mode,$account_id);
//we only ever want one copy running
//at a time.
if(!$send_mailer->isLocked())
{
	$send_mailer->Lock();
	//setup the INI so we don't die.
	//We use such a high memory limit, 
	ini_set('max_execution_time',0);
	ini_set('memory_limit',"512M");
	try 
	{
		/**
		 * Run the job as a cron. So just poll once
		 */
		if(defined('RUN_AS_CRON') && RUN_AS_CRON === TRUE)
		{
			Run_Job($send_mailer);
		}
		else 
		{
			/**
			 * Run until the second coming
			 */
			while(1)
			{
				$elapsed_time = Run_Job($send_mailer);
				//if we take more than 25 seconds to run, just start over,
				//otherwise take a break for a few seconds!
				if($elapsed_time < 25)
				{
					sleep(25 - $elapsed_time);
				}
			}
		}
	}
	catch(Exception $e)
	{
		//make sure we unlock things
		$send_mailer->Unlock();
	}
	$send_mailer->Unlock();
}
else 
{
	$file = $send_mailer->Get_Lock_File();
	if(time() - filemtime($file) > 3600)
	{
		$x = new CondorException("Lock File($file) (Mode: $mode, Account: $account_id has been in place for a while.",CondorException::Error_EMAIL);
		touch($file);
	}
}


