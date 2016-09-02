#!/usr/bin/php
<?php
/*
 --- simple class that sends a single email via smtp.
*/
require_once('Pear/mime.php');
require_once('smtp_mail.php');

class Send_Mail {
	const RETRY = 0;
	const PASS = 1;
	const MAX_REATTEMPTS = 5;
	const DEBUG_MODE = TRUE;
	const FAIL_ALL = FALSE;
	const MIN_PRIORITY = 3;
	
	private $log;
	private $smtp;
	
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
	
	public function __construct($mode) {
        $config = ECash::getConfig();
		$opts = Array(
				'Host' => $config->EMAIL_HOST,
				'Port' => $config->EMAIL_PORT,
				'User' => $config->EMAIL_USER,
				'Password' => $config->EMAIL_PSWD,
				'persist' => FALSE,
			);
		$this->smtp = new SMTP_Mail($opts);

		$this->log = ECash::getLog('email');
        $this->log->Write("Constructed Send_Email class");
	}
    
	private function Send_EMail($obj) {
        $this->log->Write("Sending email");
		$to = $obj->to;
		if(is_string($to) && strpos($to,'@') !== FALSE) {
            $this->log->Write(" To: ".$to);
            $mime = new Mail_mime("\n");
            $attachments = 0;
            //Send plain text as plain text, HTML as html and anything else
            //send as an attachment just to make sure
            if($obj->content_type == CONTENT_TYPE_TEXT_PLAIN) {
                $this->log->Write(" Preparing to send as plain text.");
                $mime->setTXTBody($obj->data);
            } elseif($obj->content_type == CONTENT_TYPE_TEXT_HTML) {
                $this->log->Write(" Preparing to send as html.");
                $mime->setHTMLBody($obj->data);
                //provide a plain text version just incase
                $mime->setTXTBody($this->Clean_Email($doc_obj->data));
            } else {
                $this->log->Write(" Preparing to send unknown format as attachment.");
                $this->Add_Attachment($mime, array($obj), $attachments);
                $mime->setTXTBody('The document was not of a standard email type. It has been sent as an attachment.');
            }
            
            if(isset($obj->subject) && !empty($obj->subject)){
                $subject = $obj->subject;
            } else {
                $subject = "Important Document";
            }
            
            if(isset($obj->from) && !empty($obj->from)){
                $from = $obj->from;
            } else {
                $from = 'customerservice@someloancompany.com';
            }
            
            if(isset($obj->reply_to) && !empty($obj->reply_to)){
                $reply_to = $obj->reply_to;
            } else {
                $reply_to = $from;
            }
            
            $head = Array(
                'From' => $from,
                'To' => $to,
                'Reply-To' => $reply_to,
                'Subject' => $subject,
            );
            
            $body = $mime->get();
            $head = $mime->headers($head);
            
            try {
                $this->log->Write(" Trying to send now.");
                $ret = $this->smtp->Send($to, $head, $body, $from);

                if($ret === FALSE) {
                    $code = $this->smtp->getResponseCode();
                    $mesg = $this->smtp->getResponseMessage();
                    $this->log->Write("  returned FALSE:  ".$code." / ".$mesg);
                    if($code == 200 ||$code == 250 || $code == 251 || $code == 252) {
                        //For some reason you get this back sometimes, and
                        //it's not an actual failure, so just update it to SENT
                        //and move along
                        $this->log->Write("  Code means it successfully sent");
                    } else {
                        $status = false;
                    }
                 } else {
                    //Initial sending worked!
                    $this->log->Write("  It successfully sent");
                    $status = 'SENT';
                    $mesg = false;	
                }
                //Time to rset the connection, just to make sure.
                $this->smtp->rset();
            }
            
            catch(Exception $e) {
                $this->log->Write("Error : ".$e->getMessage());
            }
        }
	}
	
	/**
	 * Strips all characters except numbers, uppercase letters, lowercase letters, and hyphens from the specified value.
	 * 
	 * @param string $value
	 * @return string
	 */
	private function Normalize_Custom_Headers($value)
	{
		return preg_replace('/[^0-9A-Za-z-]/', '', $value);
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



