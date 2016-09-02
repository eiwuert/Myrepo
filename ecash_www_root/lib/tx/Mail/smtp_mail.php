<?php
require_once('SMTP.php');

class SMTP_Mail {
	private $mail_from;
	private $from;
	private $host;
	private $recipient;
	private $subject;
	private $auth_type;
	private $user;
	private $pwd;
	private $port;
	private $connected;
	private $localhost;
	private $smtp;
	
	public function __construct($params = array()) {
		$this->from = FALSE;
		$this->host = NULL;
		$this->recipient = FALSE;
		$this->subject = FALSE;
		$this->auth_type = FALSE;
		$this->user = FALSE;
		$this->pwd = FALSE;
		$this->port = 25;
		$this->connected = FALSE;
		$this->smtp = FALSE;
		$this->localhost = 'localhost';
		
		foreach($params as $key=>$val) {
			$func = 'Set_'.$key;
			if(is_callable(array($this,$func))) {
				$this->$func($val);
			}
		}
	}
	
	public function __destruct() {
		$this->Disconnect();
	}
	
	/**
	 * Sends an email to everyone in the "recipients". Recipients can 
	 * either be an array of email addresses or just a string containing
	 * one. Headers is an array with keys of the header name and values of 
	 * the values. body is just the body and mail_from is what you want 
	 * to set as the return path for the mail.
	 *
	 * @param mixed $recipients
	 * @param array $headers
	 * @param unknown_type $body
	 * @param unknown_type $mail_from
	 * @param int $priority
	 */
	public function Send($recipients, $headers, $body, $mail_from = NULL ) {
		try {
			$this->Connect(TRUE, $priority);
			if(is_array($headers)) {
				$headText = $this->Get_Header_String($headers);
			} else {
				$headText = $headers;
			}
			if($mail_from === NULL) {
				$mail_from = $this->from;
			}
			$ret = $this->smtp->mailFrom($mail_from);
			if(Pear::isError($ret)) {
				throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
			}
			if(!is_array($recipients)) $recipients = array($recipients);
			if(is_array($this->recipient)) {
				$recipients = array_merge($recipients, $this->recipient);
			}
			foreach($recipients as $email) {
				$ret = $this->smtp->rcptTo($email);
				if(PEAR::isError($ret)) {
					$this->smtp->rset();
					throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
				}
			}
			$ret = $this->smtp->data($headText."\r\n\r\n".$body);
			if(PEAR::isError($ret)) {
				$this->smtp->rset();
				throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
			}
			return TRUE;
		}
		catch (Exception $e) {
			// To fix this
			// [23-Aug-2008 03:53:02] PHP Fatal error:  Call to a member function getMessage() on a non-object in /virtualhosts/condor.4.edataserver.com/lib/smtp_mail.php on line 114
			if (is_object($ret))
				Condor_Applog::Log('ERROR '.$ret->getMessage().': '.$e->getMessage());
			else
				Condor_Applog::Log('ERROR : '.$e->getMessage());
		
			
			return FALSE;
		}
		
	}
		
	public function rset() {
		if($this->smtp instanceof Net_SMTP) {
			$this->smtp->rset();
		}
	}
	
	/**
	 * Return the last smtp commands response code.
	 *
	 * @return unknown
	 */
	public function getResponseCode() {
		$code = false;
		if($this->smtp instanceof Net_SMTP) {
			$response = $this->smtp->getResponse();
			//This is the code according the PEAR/Net_SMTP::_parseResponse
			$code = !empty($response[0]) ? $response[0] : -1;
		}
		return $code;
	}
	
	/**
	 * Return the last smtp commands response message
	 *
	 * @return unknown
	 */
	public function getResponseMessage() {
		$message = false;
		if($this->smtp instanceof Net_SMTP) {
			$response = $this->smtp->getResponse();
			//The message according to the PEAR/Net_SMTP::_parseResponse method
			$message = !empty($response[1]) ? $response[1] : 'No Response';
		}
		return $message;
	}
	
	
	/**
	 * Opens the connection to the thingy
	 *
	 * @param boolean $reconnect
	 * @param int $priority
	 */
	public function Connect($reconnect = FALSE, $priority = PriorityServer::MAX_PRIORITY) {
		if($reconnect === TRUE || !$this->smtp instanceof Net_SMTP) {
			$this->Disconnect();
			unset($this->smtp);
			
			list($host, $port) = $this->getServerInfo($priority);
			
			$this->smtp = new Net_SMTP($host, $port, $this->localhost);
			//$this->smtp->setDebug(TRUE);
			
			$ret = $this->smtp->Connect(NULL,true);
			if(Pear::isError($ret)) {
				unset($this->smtp);
				throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
			}
			$ret = $this->smtp->helo($this->localhost);
			if(Pear::isError($ret)) {
				$this->Disconnect();
				unset($this->smtp);
				throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
			}
			if($this->auth_type !== FALSE) {
				$ret = $this->smtp->auth($this->user,$this->pwd,$this->auth_type);
				if(Pear::isError($ret)) {
					$this->smtp->rset();
					$this->Disconnect();
					unset($this->smtp);
					throw new Exception('Pear Error: '.$ret->getMessage(),$ret->getCode());
				}
			}
			$this->connected = true;
		}
	}
	
	/**
	 * Returns the host and port for the server that corresponds with the specified priority.
	 * 
	 * @param int $priority
	 * @return array
	 */
	private function getServerInfo($priority) {

        return array($this->host, $this->port);
	}
	
	public function Disconnect() {
		if($this->smtp instanceof Net_SMTP) {
			$this->smtp->disconnect();
			$this->connected = FALSE;
		}
	}
	
	/**
	 * Sets the localhost to identify itself during HELO
	 *
	 * @param string $host
	 */
	public function Set_Localhost($host) {
		$this->localhost = $host;
	}
	
	/**
	 * Returns the currently set localhost.
	 *
	 * @return string
	 */
	public function Get_Localhost() {
		return $this->localhost;
	}
	
	/**
	 * Sets the port and reconnects to the 
	 * server if it's changed and we were
	 * already connected
	 *
	 * @param string $port
	 */
	public function Set_Port($port) {
		if(is_numeric($port)) {
			if($this->port !== $port) {
				$this->port = $port;
				if($this->connected) {
					$this->Connect(true);
				}
			}
		}
	}
	
	/**
	 * Returns the currently set port.
	 *
	 * @return port
	 */
	public function Get_Port() {
		return $this->port;
	}
	
	/**
	 * Sets the user to use during authorization
	 *
	 * @param string $user
	 */
	public function Set_User($user) {
		$this->user = $user;
	}
	
	/**
	 * Returns the user that is currently
	 * being used to log in
	 *
	 * @return string
	 */
	public function Get_User() {
		return $this->user;
	}
	
	/**
	 * Sets the password to use during
	 * authorization
	 *
	 * @param string $pwd
	 */
	public function Set_Password($pwd) {
		$this->pwd = $pwd;
	}
	
	/**
	 * Returns the currently set password
	 *
	 * @return string
	 */
	public function Get_Password() {
		return $this->pwd;
	}
	
	/**
	 * Sets the authorization type
	 *
	 * @param string $auth_type
	 */
	public function Set_Auth_Type($auth_type) {
		if(in_array($auth_type,array('DIGEST_MD5','CRAM-MD5','LOGIN','PLAIN'))) {
			$this->auth_type = $auth_type;
		}
	}
	
	/**
	 * Returns the current auth_type
	 *
	 * @return string
	 */
	public function Get_Auth_Type() {
		return $this->auth_type;
	}
	
	
	/**
	 * Sets the host
	 * 
	 * @param string $host
	 */
	public function Set_Host($host) {
		if($host !== $this->host) {
			$this->host = $host;
			//only reconnect if we were connected
			if($this->connected) {
				$this->Connect(true);
			}
		}
	}
	
	/**
	 * Returns the host
	 */
	
	/**
	 * Returns an a array containing all recipients
	 *
	 * @return  array
	 * 
	 */
	public function Get_Recipient() {
		return $this->recipient;
	}
	
	/**
	 * Add an address to the list of recipients
	 *
	 * @param string $recipient
	 */
	public function Add_Recipient($recipient) {
		if(!is_array($this->recipients))
			$this->recipients = array();
		$this->recipient[] = $recipient;
	}
	
	/**
	 * Sets the current recipient value. Can take either
	 * an array for mulitple recipients or string.
	 *
	 * @param mixed $recipients
	 */
	public function Set_Recipient($recipients) {
		if(!is_array($recipients)) $recipients = array($recipients);
		$this->recipient = $recipients;
	}
	
	/**
	 * Sets the current subject
	 *
	 * @param string $subject
	 */
	public function Set_Subject($subject) {
		$this->subject = $subject;
	}
	
	/**
	 * Returns the current subject
	 *
	 * @return string
	 */
	public function Get_Subject() {
		return $this->subject;
	}
	
	/**
	 * Parses headers and forms them in a way to send to stuff
	 * Based rpetty closely on the PEAR prepareHeaders.
	 *
	 * @param unknown_type $headers
	 * @return string
	 */
	private function Get_Header_String($headers) {
        $lines = array();
               
		foreach ($headers as $key => $value) {
			//sanitize the thingy
			$value = preg_replace('=((<CR>|<LF>|0x0A/%0A|0x0D/%0D|\\n|\\r)\S).*=i',
				null, $value);
			if(strcasecmp($key, 'From') === 0) {
				$lines[] = $key. ': '.$value;
				$this->from = $value;	
			} elseif(strcasecmp($key, 'Received') === 0) {
				$received = array();
				if(!is_array($value)) $value = array($value);
				foreach($value as $recip) {
					$received[] = $key . ': '. $recip;
				}
				//make sure it's on the top. 
				$lines = array_merge($received,$lines);
			} else {
				if(is_array($value)) {
					$value = join(',',$value);
				}
				$lines[] = $key.': '.$value;
			}
        }
        
        return join("\r\n",$lines);
    }

}
