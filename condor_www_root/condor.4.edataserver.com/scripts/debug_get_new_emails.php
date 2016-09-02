<?php
/**
 * Get New Emails
 * 
 * Gets new emails from a specified inbox.
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Feb 19, 2007 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
/* ChangeLog:
 *
 * 2008-08-18: Added check for DEBUG_MODE when complaining that there's no from header to
 *             prevent flooding our inboxes. Added William Parker to the recipient list.
 *             Also removed Josef. [benb]
 *
 * 2008-07-16: Added PIDs to lock/warn files and also added a check to detect stale
 *             lockfiles. [benb]
 *
 * 2008-07-11: Made it look for all lowercase content types, because some had some
 *             screwy data. [benb]
 *
 * 2008-07-10: Updated the unique ID to hash the sender/date/body [W!]
 *
 * 2008-05-13: Added different return codes for lockfile versus invalid information
 *             Changed how it uniquely identifies emails, set a future date fix of
 *             Tuesday the 20th of May, 2008 for the change to take place. A temporary hack
 *             will have to remain until no sooner than 2 days after so emails are
 *             not inserted twice due to the changeover of the unique id. [benb]
 *
 * 2008-05-21: Pushed message-id switchover date to 5-21 at 2pm [benb]
 *
 */
define('CONDOR_DIR','/virtualhosts/condor.4.edataserver.com');
define('LOCK_FILE_FORMAT','/tmp/condor_get_new_%s.lock');
define('WARN_FILE_FORMAT','/tmp/condor_get_new_%s.lock.warn');
require_once 'Mail.php';
require_once 'Mail/mimeDecode.php';
require_once 'Mail/IMAPv2.php';
require_once 'Console/Getopt.php';
require_once CONDOR_DIR.'/lib/config.php';
require_once CONDOR_DIR.'/lib/condor.class.php';
require_once CONDOR_DIR.'/lib/security.php';
require_once 'prpc/client.php';
require_once('reported_exception.1.php');

// Error Codes [benb]
// 1 - Invalid Options
// 2 - Lock file still open

// List of users that will be alerted when lock file has been open for too long
Reported_Exception::Add_Recipient('email', 'brian.ronald@sellingsource.com');

//Parse command line options
$args = Console_Getopt::readPHPArgv(); 
$short_opts = "m::u::p::v::";
$long_opts = array('mode=','user=','pass=','verbose');
$options = Console_Getopt::getOpt($args,$short_opts,$long_opts); 

// Check the options are valid
if(PEAR::isError($options))
{
   echo $options->getMessage()."\n";
   exit(1);
}
elseif(empty($options[0]))
{
	echo "You need to set the username/password and mode using --user, --pass and --mode\n";
	exit(1);
}
//Loop through options because Console Getopt isn't good enough to do it for us
$parsed_opts = array();
foreach($options[0] as $opt)
{
	$name = substr(trim($opt[0],"-"),0,1);
	if(is_null($opt[1]))
	{
		$parsed_opts[$name] = true;
	}
	else
	{
		$parsed_opts[$name] = $opt[1];
	}
}
//Make sure we have all the options we need
if(!isset($parsed_opts['u']))
{
	echo "You must set a user\n";
	exit(1);
}
elseif(!isset($parsed_opts['p']))
{
	echo "You must set a password\n";
	exit(1);
}
elseif(!isset($parsed_opts['m']) || !in_array(strtolower($parsed_opts['m']), array("rc", "live"), TRUE))
{
	echo "You must set a mode\n";
	exit(1);
}

$parsed_opts['m'] = strtolower($parsed_opts['m']);

$DEBUG_MODE = false;
if(isset($parsed_opts['v']))
{
	echo "Debug Mode On\n";
	$DEBUG_MODE = true;
}

define('LOCK_FILE',sprintf(LOCK_FILE_FORMAT,md5($parsed_opts['m'].$parsed_opts['p'].$parsed_opts['u'])));
define('WARN_FILE',sprintf(WARN_FILE_FORMAT,md5($parsed_opts['m'].$parsed_opts['p'].$parsed_opts['u'])));

if(file_exists(LOCK_FILE))
{
	if($DEBUG_MODE) echo "Another process may be running\n";
	// Check to see if the lock file is legit
	$checkpid = file_get_contents(LOCK_FILE);

	if($DEBUG_MODE) echo "Checking to see if PID {$checkpid} is running (and is killable)\n";
	$running = posix_kill($checkpid, 0);

	$posix_ret = posix_get_last_error();
	if($DEBUG_MODE) echo "posix_get_last_error() ", var_export($posix_ret, TRUE), "\n";
	//$posix_ret != 0: not running
	//$posix_ret == 0: running and killable
	if ($posix_ret == 0)
	{
		if (file_exists(WARN_FILE) || filemtime(LOCK_FILE) + (10 * 60 /* 10 minutes */) > time())
		{
			if($DEBUG_MODE) echo "Lock file still open\n";
			die(WARN_FILE);
			exit(2);
		}
		else
		{
			if($DEBUG_MODE) echo "Condor lock file has been open for too long\n";
			
			//Don't send out multiple exceptions for the same lock
			file_put_contents(WARN_FILE, getmypid());

			//Lock has been open for too long, send a message
			$email = array();
			$email[] = 'Condor lock file has been open for too long.';
			$email[] = '';
			$email[] = 'Lock file: ';
			$email[] = wordwrap(LOCK_FILE, 72);
			$email[] = '';
			$email[] = 'Mode: ';
			$email[] = wordwrap($parsed_opts['m'], 72);
			$email[] = '';
			$email[] = 'User: ';
			$email[] = wordwrap($parsed_opts['u'], 72);
			$email[] = '';
			$email[] = 'md5(Password): ';
			$email[] = wordwrap(md5($parsed_opts['p']), 72);
			$email[] = '';
			$email[] = 'Time lock file opened:';
			$email[] = date("F d, Y, H:i:s", filemtime(LOCK_FILE));

			$message = implode("\n", $email);
			Reported_Exception::Send($message, 'EXCEPTION: Condor send email lock file');
			exit;
		}
	}
	else
	{
		if($DEBUG_MODE) echo "Process not running, removing stale lock file\n";
		// Stale lock file
		unlink(LOCK_FILE);

		if (file_exists(WARN_FILE))
			unlink(WARN_FILE);
	}
}

$my_pid = getmypid();
if($DEBUG_MODE) echo "My PID {$my_pid}\n";
file_put_contents(LOCK_FILE, $my_pid);

$url = FALSE;
switch($parsed_opts['m'])
{
	case "rc":
		$url = 'rc.condor.4.edataserver.com/condor_api.php';
		break;
	case "live":
		$url = 'condor.4.edataserver.com/condor_api.php';
		break;
}
if(strlen($url) > 0)
{
	$url = $parsed_opts['u'].':'.$parsed_opts['p'].'@'.$url;
}
$condor = new PRPC_Client("prpc://$url");
if(!is_object($condor))
{
	echo("Could not create condor object.");
}
//Setup condor instance
//$security = new Security($parsed_opts['m']);
//$logged_in = $security->Login_User('condorapi', $parsed_opts['u'], $parsed_opts['p']);

$user_id = $condor->Get_Agent_Id();
$company_id = $condor->Get_Company_Id();
if(!is_numeric($user_id) || !is_numeric($company_id))
{
	echo("The company id ($company_id) or user_id ($user_id) did not exist.");
}
$mail_servers = $condor->Get_All_Pop_Accounts('INCOMING');
if(!is_array($mail_servers))
{
	die("NO mailservers");
	return;
}

foreach($mail_servers as $mail_settings)
{
	$mail = Get_Mail_Object($mail_settings);
	if($mail === FALSE)
		continue;

	//Grab New Emails
	//Default Params
	// These options are horribly explained at
	// http://pear.php.net/package/Mail_mimeDecode/docs/latest/Mail_Mime/Mail_mimeDecode.html
	$params = array();
	$params['include_bodies'] = true;
	$params['decode_bodies'] = true;
	$params['decode_headers'] = true;

	//Get Message Count
	if($DEBUG_MODE) echo "Getting message count\n";
	$msg_count = $mail->messageCount();

	if($msg_count == 0)
	{
		if($DEBUG_MODE) echo "No Messages\n";
		continue;
	}
	elseif($DEBUG_MODE) 
	{
		echo("There's $msg_count messages\n");
	}

	// Used for calculating whether an email has reached deletion age
	$two_days_ago = strtotime('-2 days');

	// mid is provided by the POP3 server.
	for($mid = 1; $mid <= $msg_count; $mid++)
	{
		//Decode Message using the mimeDecode class because it's way better than the IMAP parser
		$header = $mail->getRawHeaders($mid);
		$body   = $mail->getRawMessage($mid);
	
		$params['input'] = $header . $body;
		$structure = Mail_mimeDecode::decode($params);
	
		//Header
		// I'm assuming this is limited by 255 chars by the VARCHAR(255) field in document
		$message_subject = substr($structure->headers['subject'],0,255);

		//See if postfix original to is set
		if(isset($structure->headers['x-original-to']))
		{
			$message_to = clean_email($structure->headers['x-original-to']);
		}
		else
		{
			$message_to = clean_email($structure->headers['to']);
		}
		$message_delivered_to = clean_email($structure->headers['delivered_to']);
		$message_from = clean_email($structure->headers['from']);
		$message_type = $structure->ctype_primary . '/' . $structure->ctype_secondary;
		$message_date = strtotime($structure->headers['date']);


		if($message_from == 'info@realestateworldnews.com')
		{
			echo "Found scumbag email, deleting\n";
			$mail->delete($mid);
			continue;
		}


		// Old "unique" ID
		$message_id = $structure->headers['message-id'];

		// New unique_id
		//Message hash
		//We can't hash the body or header of an E-mail because there are some E-mails which inexplicably change when downloaded
		$old_hash = md5($message_from . $message_date . $body);
		
		//The new hash is going to use a hash of the sender/date/subject.  This should be a unique enough identifier.
		//unless time starts repeating itself, or somebody sends multiple different messages at the exact same time with the exact same
		//subject. 
		$message_hash = md5($message_from . $message_date . $message_subject . $message_id);

		// The old way was relying on the CLIENT PROVIDED message-id being in the email
		// in order to uniquely identify it in condor. That was bad, as the Does_Unique_ID_Exist
		// will always return TRUE _past_ the first email it received lacking a message-id
		// causing any email lacking a message-id after the first one to just simply get deleted
		// without being realized by condor. This change will be applied to all condor cron scripts
		// by 2008-05-22 at 10am, I set it to switchover to a hash of the message rather than using the message
		// ID at 2008-05-22 14:00:13. [benb]
		/////////////////////////////////
		//The wrong way, or as I like to call it, the "Ben way" was hashing the header and body.  The problem with this is that
		// occasionally there were situations where the header will change whenever the message was downloaded.  
		//This was resulting in messages being repeatedly imported by Condor.  [W!][#15343][#14853]
		
		//To fix this, we're going to hash the sender/date/subject instead
		
		//For now, we're going to use the old hash:
		$message_id = $old_hash;
		
		// Note: This can be simplified to "$message_id = $message_hash" no sooner than 2 days after
		// this modification is in place.
		if ($message_date >= strtotime('2008-07-15 14:00:00'))
		{
			$message_id = $message_hash;
		}

		//Check if message-id has already been processed
		if($condor->Does_Unique_Id_Exist($message_id)) 
		{
			if($DEBUG_MODE) echo "Existing message found in condor: ID {$message_id}\n";
			//Delete old email
			echo "Message date: " . date('Y-m-d', $message_date) . "\n";

			if($message_date <= $two_days_ago) //2 Days
			{
				$mail->delete($mid);
				if($DEBUG_MODE) echo "Deleted {$mid}\n";
			}
			continue;
		}

		//Document and Parts
		$document = array();
		$parts = array();
		if(isset($structure->parts))
		{
			//Break out email into its constituent parts
			$parts = Get_Parts($structure);
			//Grab first plain text message to use for document
			for($x=0; $x < count($parts); $x++)
			{
				// We were getting some messages where it was not importing them and complaining about
				// having no main body even though it had one. This bottom part is looking for specific
				// content types, and it was not importing it because it was "Text/Plain" not "text/plain"
				// [benb]
				if(strtolower($parts[$x]['TYPE']) == 'text/plain' || strtolower($parts[$x]['TYPE']) == 'text/html')
				{
					$document = array("FROM"     => $message_from,
									  "TO"       => $message_to,
									  "TYPE"     => $parts[$x]['TYPE'],
									  "CONTENT"  => $parts[$x]['CONTENT'],
									  "SUBJECT"  => $message_subject,
									  "ID"       => $message_id);
					unset($parts[$x]); //Remove part because we are using it as the document
					break;
				}
			}
			if(!isset($document['FROM']) && $DEBUG_MODE) 
			{
				echo "Document has no main body!\n";
				var_dump($header);
			}
		}
		else //No attachments
		{
			//Insert just the one part
			$document = array("FROM"     => $message_from,
							  "TO"       => $message_to,
							  "TYPE"     => $message_type,
							  "CONTENT"  => $body,
							  "SUBJECT"  => $message_subject,
						  	"ID"       => $message_id);
		}
		//Insert Document
		$a_id = $condor->Incoming_Document('EMAIL',
										   $document['FROM'], 
										   $document['TO'], 
										   $document['TYPE'], 
										   $document['CONTENT'],
										   1, //Num of pages
										   $document['SUBJECT'],
										   $document['ID']);
								  
		if($DEBUG_MODE) echo "\nInserting Document: \n";
		if($DEBUG_MODE) echo "Archive ID: " . var_export($a_id, TRUE) . "\n";

		if($DEBUG_MODE && empty($body)) echo "Empty body!\n";

		if(empty($a_id))
		{
			echo "Empty Archive ID!\n";
			echo "Body size: " . strlen($body) . "\n";
			echo "Found " . count($parts) . " parts\n";
			foreach($parts as $part)
			{
				echo "\t{$part['TYPE']} - {$part['SUBJECT']}\n";
			}


//			var_dump($header);
		}
		else
		{
			//Insert Parts
			foreach($parts as $part)
			{
				if($DEBUG_MODE) echo "Inserting Attachment: \n";
				if($condor->Create_As_Email_Attachment($a_id, $part['TYPE'], $part['CONTENT'], $part['SUBJECT']))
				{
					if($DEBUG_MODE) echo("Inserted successfully.\n");
				}
				else
				{
					if($DEBUG_MODE) echo "Not so successful.\n";
				}
			}
		}
	}
	// This will delete all messages previously "deleted" during the unique id and age test
	$mail->expunge();
}
//Unlock the thing if it exists
if(file_exists(LOCK_FILE))
{
	unlink(LOCK_FILE);
}
//Remove any warning files
if (file_exists(WARN_FILE))
{
	unlink(WARN_FILE);
}

/**
 * Clean Email
 * 
 * @param string Email
 */
function Clean_Email($email) 
{
	$parts = array();
	if(strncmp($email,"<",1)===0)
	{
		$email = trim($email, '<> ');
	}
	elseif(preg_match("/<([^>]+)>/",$email,$parts))
	{
		$email = $parts[1];
	}
	
	return $email;
}

/**
 * Get Parts
 * 
 * Recursively get parts of email since
 * a part can have parts
 * @param object Message
 * @return array Parts
 */
function Get_Parts($message)
{
	$parts = array();
	if(!isset($message->parts) || empty($message->parts)) return $parts;
	
	foreach($message->parts as $part)
	{
		//Check if the part has parts
		if(isset($part->parts) && !empty($part->parts))
		{
			$parts = array_merge($parts, Get_Parts($part));
		}
		else
		{
			$part_type = $part->ctype_primary . '/' . $part->ctype_secondary;
			if(!isset($part->d_parameters['filename']) && 
			   ($part_type == 'text/plain' || $part_type == 'text/html')) //Plain Text
			{
				//Set subject if it's there
				if(isset($part->headers['subject']))
				{
					$document_subject = $part->headers['subject'];
				}
				else
				{
					$document_subject = '';
				}
				$document_content = $part->body;
			}
			else //Binary
			{
				$document_subject = $part->d_parameters['filename'];
				$document_content = $part->body;
			}
			
			//Trim Subject
			$document_subject = substr($document_subject, 0, 255);
			
			$parts[] = array("TYPE"    => $part_type, 
							 "SUBJECT" => $document_subject, 
							 "CONTENT" => $document_content);
		}
	}
	
	return $parts;
}

/**
 * Creates a pear Mail object and returns it
 */
function Get_Mail_Object($config)
{
	global $DEBUG_MODE; //meh
	$mail = new Mail_IMAPv2();
	
	// If the mail server port is 995, it's most likely an SSL connection
	// so set the SSL flag.
	$options = ($config->mail_port === '995') ? '#ssl/novalidate-cert' : '#notls';
	
	$mconnect = 'pop3://'.urlencode($config->mail_user).':'.
		$config->mail_pass.'@'.$config->mail_server.':'.
		$config->mail_port.'/'.$config->mail_box.$options;

	if($DEBUG_MODE) echo "Trying to connect to {$mconnect}...";
	if(!$mail->connect($mconnect))
	{
		echo "Mail server error:\n";
		echo "Account: {$config->mail_user} @ {$config->mail_server}:{$config->port}\n";

		echo print_r($mail->alerts(FALSE, "\n"),TRUE)."\n";
		echo print_r($mail->errors(FALSE, "\n"),TRUE)."\n\n";

		return FALSE;
	}
   	if($DEBUG_MODE) echo "Success\n";
	return $mail;
}
?>
