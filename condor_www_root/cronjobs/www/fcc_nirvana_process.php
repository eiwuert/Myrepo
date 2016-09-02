<?PHP

// Live
DEFINE ("DIR_INCLUDE", "/virtualhosts/soapdataserver.com/fcc.1/live/include/");
DEFINE ("SESSION_DB","fcccard");        	// Session Database
DEFINE ("DIR_LIB", "/virtualhosts/lib/");	// Library Directory
DEFINE ("DIR_CODE", DIR_INCLUDE."code/");	// Code Directory
DEFINE ("DIR_PRPC", "prpc/");				// Prpc Directory
DEFINE ("SESSION_TABLE","session");       	// Session Table
DEFINE ("HOST","selsds001");				// DB Host
DEFINE ("USER","sellingsource");           	// DB User
DEFINE ("PWD","password");            	// DB Password
DEFINE ('FCC_CARD_DB','fcccard');          	// FCC Card DB
DEFINE ('LICENSE_KEY', 'b71ab92977bc88085cf4fed4663e6485');
DEFINE ('MAIL_SERVER', 'prpc://smtp.2.soapdataserver.com/smtp.1.php');

// Required Files
require_once (DIR_LIB."debug.1.php");			// Debug Include
require_once (DIR_LIB."error.2.php");			// Error Include
require_once (DIR_LIB."mysql.3.php");			// Mysql Include
require_once (DIR_LIB."crypt.3.php");			// Crypt Include
require_once (DIR_LIB."session.4.php");			// Session Include
require_once (DIR_LIB."ole_mail.2.php");		// OLE mail Include
require_once (DIR_LIB."lib_mail.1.php");		// Mail Include
require_once (DIR_PRPC."server.php");			// Prpc Server Include
require_once (DIR_CODE."cashcard.class.php");	// Cash Card Class
require_once (DIR_CODE."fcccard.class.php");		// Fcc Card Class

// Instantiate the MySQL object
$sql = new MySQL_3();

// Connect to the DB
$result = $sql->Connect (NULL, HOST, USER, PWD, Debug_1::Trace_Code(__FILE__, __LINE__));
Error_2::Error_Test($result);

// Instantiate the Cash Card Class
$fcc = new Fcc_Card(FALSE);

// Instantiate the process for nirvana
$fnp = new Fcc_Nirvana_Process($sql,$fcc);

// Start processing
$fnp->Start_Process();

/**
	@publicsection
	@public
	Cron job to handle the sending of the nirvana emails for FCC.
	
	@brief


	@version
		1.0.0 2004-03-12 - Nick
			
	@change_log
		1.0.0
			- Initial class creation

	@todo
*/



class Fcc_Nirvana_Process
{
	var $sql;
	var $fcc;
		
	/**
	 * @return boolean
	 * @param $sql
	 * @desc Constructor to setp the class
      */
	function Fcc_Nirvana_Process($sql,$fcc)
	{
		$this->sql = $sql;
		$this->fcc = $fcc;
		return TRUE;
	}

	/**
	 * @return boolean
	 * @desc Start the processing and building of the email data to go out.
      */
	function Start_Process()
	{
		$delivery_list = new stdClass();
		
		$delivery_list->email_24 = $this->Generate_24_Email_List();
		$delivery_list->email_48 = $this->Generate_48_Email_List();
		$delivery_list->email_72 = $this->Generate_72_Email_List();
		$delivery_list->email_96 = $this->Generate_96_Email_List();
		
		$this->Send_Email($delivery_list);
		
		$this->Update_Nirvana($delivery_list);
		
		return TRUE;
	}
	
	/**
	 * @return boolean
	 * @desc Generate a list of email address and application id for the 24 hour email
      */
	function Generate_24_Email_List()
	{
		$list = new stdClass();
		$query = "
			SELECT
				contact.email,
				nirvana.application_id
			FROM 
				contact,
				personal,
				nirvana
			WHERE
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) >= 1
			AND
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) < 2	
			AND
				nirvana.24_email = 0
			AND
				nirvana.application_id = personal.application_id
			AND
				personal.contact_id = contact.contact_id";
		
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		$i = 0;
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list->{'id_'.$i} = $row_data;
			$i++;
		}
		
		return $list;
	}
	
	/**
	 * @return boolean
	 * @desc Generate a list of email address and application id for the 48 hour email
      */
	function Generate_48_Email_List()
	{
		$list = new stdClass();
		$query = "
			SELECT
				contact.email,
				nirvana.application_id
			FROM 
				contact,
				personal,
				nirvana
			WHERE
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) >= 2
			AND
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) < 3	
			AND
				nirvana.48_email = 0
			AND
				nirvana.application_id = personal.application_id
			AND
				personal.contact_id = contact.contact_id";
		
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		$i = 0;
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list->{'id_'.$i}=$row_data;
			$i++;
		}
		
		return $list;
	}
	
	/**
	 * @return boolean
	 * @desc Generate a list of email address and application id for the 72 hour email
      */
	function Generate_72_Email_List()
	{
		$list = new stdClass();
		$query = "
			SELECT
				contact.email,
				nirvana.application_id
			FROM 
				contact,
				personal,
				nirvana
			WHERE
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) >= 3
			AND
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) < 4
			AND
				nirvana.72_email = 0
			AND
				nirvana.application_id = personal.application_id
			AND
				personal.contact_id = contact.contact_id";
		
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		$i = 0;
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list->{'id_'.$i}=$row_data;
			$i++;
		}
		
		return $list;
	}
	
	/**
	 * @return boolean
	 * @desc Generate a list of email address and application id for the 96 hour email
      */
	function Generate_96_Email_List()
	{
		$list = new stdClass();
		$query = "
			SELECT
				contact.email,
				nirvana.application_id
			FROM 
				contact,
				personal,
				nirvana
			WHERE
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) >= 4
			AND
				TO_DAYS(NOW()) - TO_DAYS(nirvana.origination_date) < 5
			AND
				nirvana.96_email = 0
			AND
				nirvana.application_id = personal.application_id
			AND
				personal.contact_id = contact.contact_id";
		
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		$i = 0;
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list->{'id_'.$i}=$row_data;
			$i++;
		}
		
		return $list;
	}
		
	/**
	* @return boolean
	* @param $data object
	* @desc Prepair the instant email and send
 	*/
	function Send_Email($delivery_list)
	{
		foreach($delivery_list AS $field=>$list)
		{
			foreach($list AS $id=>$data)
			{
				switch($field)
				{
					case "email_24":
					$email_file = 'email_3.html';
					$email_name = 'email3';
					$subject = 'Your Fast Cash Card will arrive soon.';					
					break;
					
					case "email_48":
					$email_file = 'email_4.html';
					$email_name = 'email4';
					$subject = 'Did your Fast Cash Card arrive yet?';
					break;
					
					case "email_72":
					$email_file = 'email_6.html';
					$email_name = 'email6';
					$subject = 'Fast Cash Card Survey - Earn a FREE Phone Card';
					break;
					
					case "email_96":
					$email_file = 'email_7.html';
					$email_name = 'email7';
					$subject = 'Free Upgrade To Fast Cash Preferred Card';
					break;				
				}
				if($email_file)
				{
					// Build the email header
					$header = new stdClass();
					$header->port = 25;
					$header->url = "http://www.fastcashcard.com";
					$header->subject = $subject;
					$header->sender_name = "fastcashcard.com";
					$header->sender_address = "customerservice@fastcashcard.com";
					
					// Build the recipient information
					$recipient1 = new stdClass();
					$recipient1->type = "to";
					$recipient1->name = ucwords($data->first_name)." ".ucwords($data->last_name);
					$recipient1->address = $data->email;
					
					// Build the message to be sent
					$message = new stdClass();
					ob_start();
					include('fcc_email/'.$email_file);
					$message->html = ob_get_contents();
					ob_end_clean();
					
					// Package and send the data
					$mail = new Prpc_Client(MAIL_SERVER);
					$mailing_id = $mail->CreateMailing('FCC_'.$email_name,$header,NULL,NULL);
					$package_id = $mail->AddPackage($mailing_id,array($recipient1),$message,array($attachment1));
					$result = $mail->SendMail($mailing_id);
				}				
			}
		}
				
		return TRUE;
	}
	
	/**
	 * @return boolean
	 * @param $delivery_list object
	 * @desc determine which field in nirvana to update and hit the parent class for updates
      */
	function Update_Nirvana($delivery_list)
	{
		foreach($delivery_list AS $field=>$list)
		{
			foreach($list AS $id=>$data)
			{
				switch($field)
				{
					case "email_24":
					$list = "24_email";					
					break;
					
					case "email_48":
					$list = "48_email";
					break;
					
					case "email_72":
					$list = "72_email";
					break;
					
					case "email_96":
					$list = "96_email";
					break;
					
					default:
					$list = NULL;
					break;
				}
				$this->fcc->Nirvana_Update($data->application_id,$list,FCC_CARD_DB);	
			}	
		}
		
		return TRUE;
	}
}
?>