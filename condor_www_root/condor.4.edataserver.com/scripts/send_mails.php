#!/usr/bin/php
<?php
/**
 * Just  runs around and collects emails from the Mail_Queue sends them then does 
 * some awesome with it.
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

define('MODE','LIVE');
define('RUN_AS_CRON',TRUE);

class Send_Mails
{
	const FAIL = -1;
	const RETRY = 0;
	const PASS = 1;
	const LOCK_FILE = '/tmp/condor_send_mail.lock';
	const MAX_REATTEMPTS = 5;
	const DEBUG_MODE = TRUE;
	const FAIL_ALL = FALSE;
	
	private $mysqli;
	private $mode;
	private $mail_queue;
	private $condor;
	private $companies;
	private $accounts;
	private $mail_connections;
	private $condor_api;
	
	public function __construct($mode)
	{
		$this->mysqli = MySQL_Pool::Connect('condor_' . $mode);
		$this->mode = $mode;
		if(!$this->mysqli instanceof MySQLi_1)
		{
			throw new Exception("could not connect to $mode database.");
		}
		$this->mail_queue = new Mail_Queue($mode, $this->mysqli);
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
		$mails = $this->mail_queue->Get_Queued_Mails('queued',$start_date);
		foreach($mails as $mail)
		{
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
		$mails = $this->mail_queue->Get_Queued_Mails('requeued',$start_date,$end_date);
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
		$mails = $this->mail_queue->Get_Queued_Mails(Array('requeued','queued'),NULL,$end_date);
		foreach($mails as $key=>$mail)
		{
			$this->mail_queue->Update_Status($mail->mail_queue_id,'failed');
		}
	}
	
	
	/**
	 * Takes an object representing a row in the mail_queue
	 * and then actually sends the mail and updates the status accordingly
	 */
	private function Send_Mail($obj)
	{
		//Create the Pop Account
		$to = $this->Get_To($obj->dispatch_id);
		if(is_string($to) && strpos($to,'@') !== FALSE)
		{
			$queue_id = $obj->mail_queue_id;
			//if we're set to fail, don't even try, just fail
			if(self::FAIL_ALL === TRUE)
			{
				self::Display("Failing $queue_id because FAIL_ALLL is set");
				$this->mail_queue->Update_Status($queue_id,'failed');
				return;
			}
			$account = $this->Load_Pop_Account_By_Id($obj->account_id);
			$user = $this->Get_Company_Data($account->company_id);
			//WE actually load by PRPC incase the condor mount and
			//the send script are NOT on the same server. Since they 
			//won't be.
			$doc_obj = $user->condor_api->Find_By_Archive_Id($obj->document_id);
			if($doc_obj !== FALSE && !empty($doc_obj->data))
			{
				$mime = new Mail_mime("\n");
				//var to keep count of all the number of attachments.
				$attachments = 0;
				//Send plain text as plain text, HTML as html and anything else
				//send as an attachment just to make sure
				if($doc_obj->content_type == CONTENT_TYPE_TEXT_PLAIN)
				{
					self::Display("Preparing to send as plain text.");
					$mime->setTXTBody($doc_obj->data);
				}
				elseif($doc_obj->content_type == CONTENT_TYPE_TEXT_HTML)
				{
					self::Display("Preparing to send html.");
					$mime->setHTMLBody($doc_obj->data);
					//provide a plain text version just incase
					$mime->setTXTBody($this->Clean_Email($doc_obj->data));
				}
				else
				{
					self::Display("Preparing to send unknown format as attachment.");
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
					'From' => $account->mail_from,
					'To' => $to,
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
					$mail_obj = $this->Get_Mail_Connection($account);
					$ret = $mail_obj->send($to, $head, $body, $mail_from);
					if($ret === FALSE)
					{
						$attempts = $this->mail_queue->Get_History_Count($obj->dispatch_id);
						if($attempts > self::MAX_REATTEMPTS)
						{
							self::Display("It failed $attempts times. Permanently failing.");
							$status = 'failed';
						}
						else
						{
							self::Display("It failed $attempts times. requeuing for later.");
							$status = 'requeued';
						}
					}
					else 
					{
						self::Display("It successfully sent.");
						$status = 'SENT';				
					}
					unset($this->accounts[$obj->account_id]);
					unset($this->mail_connections[$obj->account_id]);
					$this->mail_queue->Update_Status($queue_id,$status);
															
				}
				catch(Exception $e)
				{
					//just to make sure!
					self::Display("Error : ".$ret->getMessage());
					$attempts = $this->mail_queue->Get_History_Count($obj->dispatch_id);
					if($attempts > self::MAX_REATTEMPTS)
					{
						self::Display("We've attempted $attempts, failing message.");
						$status = 'failed';
					}
					else 
					{
						self::Display("Attempting to requeue to try again later.");
						$status = 'requeued';
					}
					//now that we've got that we'll remove all remove this account/connection
					unset($this->accounts[$obj->account_id]);
					unset($this->mail_connections[$obj->account_id]);
					$this->mail_queue->Update_Status($queue_id,$status);
					
				}
			}
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
	 * Makes a mail connection or returns the one that already exists 
	 * the account provided
	 *
	 * @param int $account
	 * @return object
	 */
	private function Get_Mail_Connection($account)
	{
		if(!isset($this->mail_connections[$account->account_id]))
		{
			$opts = Array(
				'Host' => $account->mail_server,
				'Port' => $account->mail_port,
				'User' => $account->mail_user,
				'Password' => $account->mail_pass,
				'persist' => true
			);
			$this->mail_connections[$account->account_id] = new SMTP_Mail($opts);
		}
		return $this->mail_connections[$account->account_id];
	}
	
	/**
	 * Takes an HTML email thing and cleans it up as plain text 
	 * The formatting will be ugly I'm sure.
	 *
	 * @param string $data
	 */
	private function Clean_Email($data)
	{
		return trim(html_entity_decode(strip_tags($data)));
	}
	
	/**
	 * Kind of a hack to get the company_id,user_id,api_auth so that 
	 * we can load documents up properly
	 *
	 * @param int $company_id
	 */
	private function Get_Company_Data($company_id)
	{
		//If I already know about this company,
		//I don't really think it's necessary for 
		//me to laod it again
		if(!isset($this->companies[$company_id]))
		{
			$query = "
				SELECT
					c.company_id,
					a.agent_id,
					c.api_auth,
					c.name_short
				FROM
					condor_admin.agent a
				JOIN
					condor_admin.company c
				USING
					(company_id)
				WHERE
					a.company_id = $company_id
				AND
					a.system_id = 2
			";
			try 
			{
				$res = $this->mysqli->Query($query);
				if(($row = $res->Fetch_Object_Row()))
				{
					/*
					$sec = new Security($this->mode);
					$row->api_auth = $sec->Decrypt($row->api_auth,md5($row->name_short));
					switch($this->mode)
					{
						case MODE_DEV:
							$url = 'condor.ds79.tss:8080/condor_api.php';
							break;
						case MODE_RC:
							$url = 'rc.condor.4.edataserver.com/condor_api.php';
							break;
						case MODE_LIVE:
							$url = 'condor.4.edataserver.com/condor_api.php';
							break;
					}
					$row->condor_api = new PRPC_Client('prpc://'.$row->api_auth.'@'.$url);
					*/
					$row->condor_api = new Condor($this->mode, $row->agent_id, $row->company_id);
					$this->companies[$company_id] = clone $row;	
				}
			}
			catch (Exception $e)
			{
				return FALSE;
			}
		}
		return $this->companies[$company_id];
	}
	
	/**
	 * Load the Pop Account Info based on Id
	 */
	private function Load_Pop_Account_By_Id($id)
	{
		//Only load it if i've never heard 
		//of this account before. Really
		if(!isset($this->account_info[$id]))
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
					$this->account_info[$id] = clone $row;
				}
			}
			catch (Exception $e)
			{
				return FALSE;
			}
		}
		return $this->account_info[$id];
	}
	
	/**
	 * This goes through and disconnects all the stuff so that
	 * we have to reconnect on our next go around.
	 *
	 */
	public function Clear_Connections()
	{
		if(count($this->mail_connections) > 0)
		{
			unset($this->mail_connections);
			$this->mail_connections = array();
		}
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
 * Locks things down
 *
 */
function Lock()
{
	if(!file_exists(Send_Mails::LOCK_FILE))
	{
		//If we lock things, make sure we unlock if 
		//the script exits.
		register_shutdown_function('Unlock');
		touch(Send_Mails::LOCK_FILE);
	}
}

/**
 * Are things locked?
 *
 * @return boolean
 */
function isLocked()
{
	return file_exists(Send_Mails::LOCK_FILE);
}

/**
 * Unlocks things
 *
 */
function Unlock()
{
	if(file_exists(Send_Mails::LOCK_FILE))
	{
		unlink(Send_Mails::LOCK_FILE);
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
	$send_mailer->Clear_Connections();
	return (microtime(TRUE) - $start_time);
}

//we only ever want one copy running
//at a time.
if(!isLocked())
{
	Lock();
	//setup the INI so we don't die.
	//We use such a high memory limit, 
	ini_set('max_execution_time',0);
	ini_set('memory_limit',"512M");
	try 
	{
		echo("Starting.");
		$send_mailer = new Send_Mails(MODE);
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
		Unlock();
	}
	Unlock();
}

