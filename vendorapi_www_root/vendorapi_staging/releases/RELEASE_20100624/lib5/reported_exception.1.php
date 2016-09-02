<?php
	
	/**
	 *
	 * Reported exceptions. Two methods:
	 *
	 * throw new Reported_Exception("I'm dying here."); // dying, as in colors
	 *
	 * [...]
	 * catch (Exception $e)
	 * {
	 *	Reported_Exception::Report($e);
	 * }
	 *
	 * Main difference between the two methods: if you throw a new
	 * exception, that means you have to catch it or you'll get
	 * a FATAL error.
	 *
	 * @todo Exception levels? Recipient Groups? Escalation?
	 *
	 * @author Andrew Minerd
	 *
	 */
	class Reported_Exception extends Exception
	{
		
		protected static $recipients = array();
		
		/**
		 *
		 * Add recipients for any exceptions. $recipient should be
		 * a valid address for the given $method (EMAIL/SMS/etc.)
		 *
		 */
		public static function Add_Recipient($method, $recipient)
		{
			
			self::$recipients[] = array(
				'method' => $method,
				'address' => $recipient,
			);
			
			return;
			
		}
		
		/**
		 *
		 * In some cases, you'll want to report an exception that's already
		 * occured and been caught, rather than throwing a new Reported_Exception.
		 *
		 */
		public static function Report(Exception $exception)
		{
			
			foreach (self::$recipients as $recipient)
			{
				
				if ($class = self::Reporter_Class($recipient['method']))
				{
					call_user_func(array($class, 'Report'), $recipient['address'], $exception);
				}
				
			}
			
			return;
			
		}
		
		/**
		 *
		 * In other cases, you'll just want to send a message.
		 *
		 */
		public static function Send($message, $subject = NULL)
		{
			
			foreach (self::$recipients as $recipient)
			{
				
				if ($class = self::Reporter_Class($recipient['method']))
				{
					call_user_func(array($class, 'Send'), $recipient['address'], $message, $subject);
				}
				
			}
			
			return;
			
		}
		
		/**
		 *
		 * Othertimes, you're going to be throwing Exceptions that you want
		 * to be reported, as well.
		 *
		 */
		public function __construct($desc, $code = NULL)
		{
			
			// let the Exception do it's thang
			parent::__construct($desc, $code);
			
			// now, tell on ourselves
			self::Report($this);
			
			return;
			
		}
		
		protected static function Reporter_Class($type)
		{
			
			$map = array(
				'EMAIL' => 'Email_Reporter',
				'SMS' => 'SMS_Reporter',
			);
			
			$class = isset($map[strtoupper($type)]) ? $map[strtoupper($type)] : FALSE;
			return $class;
			
		}
		
	}
	
	/**
	 *
	 * An interface for the classes that will actually handle
	 * reporting the exceptions.
	 *
	 */
	interface iReporter
	{
		public static function Report($recipient, Exception $exception);
		public static function Send($recipient, $message, $subject = NULL);
	}
	
	/**
	 *
	 * Sends reports via PHP's mail command.
	 *
	 */
	class EMail_Reporter implements iReporter
	{
		
		public static function Report($recipient, Exception $exception)
		{
			
			$vars = get_defined_vars();
			
			$message = $exception->getMessage();
			$subject = (strlen($message) > 33) ? substr($message, 0, 30).'...' : $message;
			
			$email = array();
			$email[] = "DESCRIPTION";
			$email[] = str_repeat('-', 72);
			$email[] = wordwrap($message, 72);
			$email[] = '';
			$email[] = "BACK TRACE";
			$email[] = str_repeat('-', 72);
			$email[] = wordwrap(print_r($exception->getTrace(), TRUE), 72);
			$email[] = '';
			$email[] = "DEFINED VARIABLES";
			$email[] = str_repeat('-', 72);
			$email[] = wordwrap(print_r($vars, TRUE), 72);
			
			self::Send($recipient, implode("\n", $email), 'EXCEPTION: '.$subject);
			return;
			
		}
		
		public static function Send($recipient, $message, $subject = NULL)
		{
			
			// prepare variables
			if (!is_array($recipient)) $recipient = array($recipient);
			
			// now mail it off
			foreach ($recipient as $address)
			{
				mail($address, $subject, $message);
			}
			
			return;
			
		}
		
	}
	
	/**
	 *
	 * Sends reports via SMS. Using the RC interface because no
	 * billing is performed there. :-)
	 *
	 */
	class SMS_Reporter implements iReporter
	{
		
		public static function Report($recipient, Exception $exception)
		{
			
			$message = $exception->getMessage();
			if (strlen($message) > 103) substr($message, 0, 100).'...';
			
			self::Send($recipient, $message);
			return;
			
		}
		
		public static function Send($recipient, $message, $subject = NULL)
		{
			
			// prepare variables
			if (!is_array($recipient)) $recipient = array($recipient);
			
			@include_once('prpc/client.php');
			
			if (class_exists('PRPC_Client'))
			{
				
				$sms = new PRPC_Client('prpc://sms.edataserver.com/sms_prpc.php');
				
				// send out an SMS
				foreach ($recipient as $number)
				{
					
					try
					{
						$sms->Send_SMS($number, $message, 'REPORTED_EXCEPTION', NULL, NULL, 'TSS');
					}
					catch (Exception $e)
					{
					}
					
				}
				
			}
			
			return;
			
		}
		
	}
	
?>
