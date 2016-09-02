<?php
/**
 * Script to process a Metalog style log for PostFix and report bounced
 * messages back to Condor.
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */

require_once('general_exception.1.php');
require_once('prpc/client.php');

$util = new Condor_Bounce_Processor();
$util->run();

class Condor_Bounce_Processor
{
	/**
	 * The following two regexes match full log entry line and pulls out the timestamp, facility, postfix message id, and message
	 */

	/**
	 * Matches the full log entry line for Metalog
	 */
	private $r_metalog_logentry = "/^(\w{3}\s{1,2}\d{1,2} \d{2}:\d{2}:\d{2}) \[postfix\/(qmgr|smtp)\] ([A-Z0-9]{12}): (.*)$/";

	/**
	 * Matches the fulle log entry line for Syslog-compatible log formats
	 */
	private $r_logentry = "/^(\w{3}\s{1,2}\d{1,2} \d{2}:\d{2}:\d{2}) [A-z0-9]{3,10} postfix\/(qmgr|smtp)\[\d{1,6}\]: ([A-Z0-9]{12}): (.*)$/";
	
	/**
	 * Matches the from line from the qmgr entries to get the condor archive_id
	 */
	private $r_from = "/^from=<bounces\+(\d{1,16})@condoremailservices\.com>/";

	/**
	 * Matches the to, relay, delay, dsn, status, and remote message from the messages from smtp
	 */
	private $r_message = "/to=<([^>]*)>, (?:orig_to=<[^>]*>, )?relay=([^,]+), (?:conn_use=[^,]+, )?delay=([^,]+), (?:delays=[^,]+, )?(?:dsn=[^,]+, )?status=(\S+)(.*)$/";

	/**
	 * Matches the response from the remote SMTP server
	 */
	private $r_smtp_message = "/\(host ([^[]+)\[([^]]+)\] said: (\d{3})([^,]+)/";
	
	/**
	 * Hash table of messages indexed by the postfix message id
	 */
	private $message_table = array();
	
	/**
	 * Hash table of bounces indexed by the postfix message id
	 */
	private $bounce_table  = array();
	
	/**
	 * Unix timestamp - Log entry must be on or after this time
	 */
	private $start_time;
	
	/**
	 * Unix timestamp - Log entry must be on or before this time
	 */
	private $end_time;

	/**
	 * List of files to process
	 */
	private $files = array();

	/**
	 * PRPC URL used to connec to Condor
	 */
	private $url;

	/**
	 * Main function used to execute the tasks in the class
	 */
	public function run()
	{
		$this->parseOptions();
		if(! empty($this->files))
		{
			foreach($this->files as $filename)
			{
				$this->processFile($filename);
			}
		}
		else
		{
			die('No files to process!\n');
		}
		
		if(! empty($this->bounce_table))
		{
			$this->sendBounces();
		}
	}

	/**
	 * Function used to parse through the command line options,
	 * validate them, and set the options inside the class object
	 */
	private function parseOptions()
	{
		/**
		 * Command Line Options:
		 * 
		 * f (required) - Filename
		 * s (optional) - Start time
		 * e (optional) - End time
		 * a (optional) - Age (Now - # minutes)
		 * m (optional) - Mode (Local, RC, Live)
		 * 
		 * Note: Age is exclusive.  You cannot mix start or end with age.
		 */
		$options = getopt('f:s::e::a::m::');
		
		if(! isset($options['f']))
		{
			$this->showHelp();
			die();
		}
		else
		{
			if(is_array($options['f']))
			{
				foreach($options['f'] as $filename)
				{
					$this->addFile($filename);
				}
			}
			else
			{
				$this->addFile($options['f']);
			}
		}

		if(isset($options['a']) && (isset($options['s']) || isset($options['e'])))
		{
			die("Age is exclusive and cannot be used with a start and/or end date!\n");
		}
		
		/**
		 * Initialize some default values
		 */
		$this->start_time = 0;
		$this->end_time   = time();
		
		/**
		 * Age Option - Parameter specified in minutes
		 */
		if(isset($options['a']))
		{
			if(is_numeric($options['a']))
			{
				$this->start_time = (time() - ($options['a'] * 60));
				$this->end_time = time();
			}
		}
		
		/**
		 * Start Time - Parameter must be a string supported by strtotime()
		 */
		if(isset($options['s']))
		{
			if(! $this->start_time = strtotime($options['s']))
			{
				$this->showHelp();
				die("Invalid Start Time!\n");
			}
		}

		/**
		 * End Time - Parameter must be string supported by strtotime()
		 */
		if(isset($options['e']))
		{
			if(! $this->end_time = strtotime($options['e']))
			{
				$this->showHelp();
				die("Invalid End Time!\n");
			}
		}

		$this->setMode($options['m']);
		
		// Some Debug Output for use before production
		// echo "URL: {$this->url}\n";
		// echo "Start Time: " . date('Y-m-d H:i:s', $this->start_time) . "\n";
		// echo "End Time:   " . date('Y-m-d H:i:s', $this->end_time) . "\n";
		// echo "File List:\n";
		// var_dump($this->files);

	}

	private function showHelp()
	{
		global $argv;
		$me = $argv[0];

		echo "Usage: $me [OPTIONS]\n";
		echo "Parses Postfix log files for bounces and reports them back to Condor.\n";
		echo "Start and End times can be specified, or Age can be specified\n";
		echo "Age will use the specified number of minutes from now as the start time.\n";
		echo "More than one file can be specified using multiple -f arguments.\n";
		echo "\n";
		echo "Options:\n";
		echo " -f - Filename\n";
		echo " -a - Age (in minutes)\n";
		echo " -s - Start Time - Default is unix epoch\n";
		echo " -e - End Time - Current time is default\n";
		echo " -m - Mode (Local, RC, Live) - Local is default\n";
		echo "\n";
		echo "Examples:\n";
		echo "$me -fmaillog -fmaillog.1\n";
		echo "$me -flogfile -s\"2009-11-05 05:00:00\"\n";
		echo "$me -flogfile -a60\n";
	}
	
	/**
	 * Checks for the file's existence and adds it to the file list
	 * @param string $filename
	 */
	private function addFile($filename)
	{
		if(! file_exists($filename))
		{
			echo "Unable to open '$filename'\n";
		}
		else
		{
			$this->files[] = $filename;
		}
	}
	
	/**
	 * Sets some default values based on the operating mode
	 * @param string $mode
	 */
	private function setMode($mode)
	{
		switch(strtoupper($mode))
		{
			case 'RC':
				$hostname = 'rc.condor.4.edataserver.com';
				$login    = 'tss_api';
				$password = 'password';
				break;
			case 'LIVE':
				$hostname = 'condor.4.edataserver.com';
				$login    = 'tss_api';
				$password = 'password';
				break;
			case 'LOCAL':
			default:
				$hostname = 'condor.4.edataserver.com.ds68.tss';
				$login    = 'tss_api';
				$password = 'password';
				break;	
		}

		$this->url = "prpc://$login:$password@$hostname/condor_api.php";

	}

	/**
	 * Processes the specified log file
	 * @param string $filename
	 */
	public function processFile($filename)
	{
		if(! $fp = fopen($filename, 'r'))
		{
			echo "Unable to open '$filename'\n";
			return;
		}
		
		/**
		 * Facilities:
		 * smtpd - Ignored, this is for incoming messages only
		 * qmgr  - Handles queued messages.  Required to get the from address
		 * smtp  - Handles the sending of email.  Required for the return codes
		 * cleanup - I'm not so sure about
		 */
		while (!feof($fp))
		{
			$log_entry = fgets($fp);
		  	if(preg_match($this->r_logentry, $log_entry, $line_matches))
		  	{
				$timestamp		= strtotime($line_matches[1]);
				$facility		= $line_matches[2];
				$postfix_mid	= $line_matches[3];
				$message		= $line_matches[4];
			  
				if($timestamp < $this->start_time || $timestamp > $this->end_time)
				{
					continue;
				}

				/**
				 * Get Condor's archive_id from the "from" address output by the qmgr
				 */
				if($facility == 'qmgr')
				{
					if(preg_match($this->r_from, $message, $from_matches))
					{
						$condor_id = $from_matches[1];
						$this->message_table[$postfix_mid]['archive_id'] = $condor_id;
					}
				}

				/**
				 * Get the message status from smtp
				 */
				if($facility == 'smtp')
				{
					if(preg_match($this->r_message, $message, $m_matches))
					{
						$recipient 	= $m_matches[1];
						$relay 		= $m_matches[2];
						$delay		= $m_matches[3];
						$status		= $m_matches[4];
						$smtp_reply	= substr($m_matches[5], 0, strlen($m_matches[5]) -1);
						
						$this->message_table[$postfix_mid]['recipient']   = strtolower($recipient);
						$this->message_table[$postfix_mid]['status']      = $status;
						$this->message_table[$postfix_mid]['smtp_reply']  = $smtp_reply;
		
						if($status == 'bounced')
						{
							if(preg_match($this->r_smtp_message, $smtp_reply, $smtp_matches))
							{
								$this->message_table[$postfix_mid]['smtp_code']     = $smtp_matches[3];
								$this->message_table[$postfix_mid]['smtp_response'] = ltrim($smtp_matches[4]);
							}
							
							$this->bounce_table[] = $postfix_mid;  
						}
					}
				}
		  	}
		}
		
		fclose($fp);

	}
	
	/**
	 * Hits the Condor PRPC API with each bounced message 
	 */
	private function sendBounces()
	{
		$condor_api = new Prpc_Client($this->url);
		
		foreach($this->bounce_table as $id)
		{
			$data = $this->message_table[$id];
			
			if(isset($data['archive_id']) && is_numeric($data['archive_id']))
			{
				if($condor_api->Add_Bounce($data['archive_id'], $data['recipient'], $data['smtp_response']))
				{
					echo "Added Bounce for {$data['archive_id']} - {$data['recipient']}\n";
				}
				else
				{
					echo "Failed adding Bounce for {$data['archive_id']}\n";
				}
				
			}
			else
			{
				echo "No archive ID found for bounce message: \n" . print_r($data, true) . "\n";
			}
			
		}

		echo "Processed " . count($this->bounce_table) . " bounces\n";

	}
}
