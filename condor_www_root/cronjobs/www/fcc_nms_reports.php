<?PHP

// Live
DEFINE ("DIR_INCLUDE", "/virtualhosts/soapdataserver.com/fcc.1/live/include/");
//DEFINE ("DIR_INCLUDE", "/virtualhosts/fcc.1.soapdataserver.com/include/");
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
require_once(DIR_LIB."debug.1.php");			// Debug Include
require_once(DIR_LIB."error.2.php");			// Error Include
require_once(DIR_LIB."mysql.3.php");			// Mysql Include
require_once(DIR_LIB."db2.1.php");				// DB2 Include
require_once(DIR_LIB."crypt.3.php");			// Crypt Include
require_once(DIR_LIB."session.5.php");			// Session Include
require_once(DIR_LIB."ole_mail.2.php");			// OLE mail Include
require_once(DIR_LIB."lib_mail.1.php");			// Mail Include
require_once(DIR_PRPC."server.php");			// Prpc Server Include
require_once(DIR_CODE."cashcard.class.php");		// Cash Card Class
require_once(DIR_CODE."fcccard.class.php");		// Fcc Card Class

// Instantiate the MySQL object
$sql = new MySQL_3();

// Connect to the DB
$result = $sql->Connect (NULL, HOST, USER, PWD, Debug_1::Trace_Code(__FILE__, __LINE__));
Error_2::Error_Test($result);

// Instantiate the Cash Card Class
$fcc = new Fcc_Card(FALSE);

// Instantiate the mail class
$mail = new Prpc_Client(MAIL_SERVER);

// Instantiate the process for nirvana
$fnr = new Fcc_Nms_Reports($sql,$fcc,$mail);

// Start processing
$fnr->Run_Report();

/**
	@public

	@version
		1.0.0 2004-03-26 - Nick
			
	@change_log
		1.0.0
			- Initial class creation
	@todo
*/

class Fcc_Nms_Reports
{
	var $sql;
	var $fcc;
	var $mail;
		
	/**
	 * @return boolean
	 * @param $sql obj
	 * @param $fcc obj
	 * @desc Constructor to setp the class
      */
	function Fcc_Nms_Reports($sql,$fcc,$mail)
	{
		$this->sql = $sql;
		$this->fcc = $fcc;
		$this->mail = $mail;
		return TRUE;
	}

	function Run_Report()
	{
		// Set the dates to run
		$this->start_date = date('YmdHis');
		
		// Build the list for the files	
		$full_list = new stdClass();
		
		// If it is a friday, run the virtis list and fulfillment,... else run just the fulfillment
		if(date('w') == '5')
		{
			// Virtis list is minus -1 week
			$this->end_date = date('YmdHis',strtotime('-1 week'));
			$full_list->virtis = $this->Get_Virtis_List();
			
			// Fulfillment list is -1 day
			$this->end_date = date('YmdHis',strtotime('-1 day'));
			$full_list->fulfillment = $this->Get_Fulfillment_List();
			
			// Activation list is -1 day, so we'll use the one from above
			$full_list->activation = $this->Get_Activation_List();
			
			if($full_list->fulfillment > 0)
			{
				$fulfillment_file = $this->Build_Fulfillment($full_list->fulfillment);
			}
			
			if($full_list->activation > 0)
			{
				$activation_file = $this->Build_Activation($full_list->activation);	
			}
			
			if($full_list->virtis > 0)
			{
				$vertis_file = $this->Build_Virtis($full_list->virtis);
			}
		}
		else
		{
			$this->end_date = date('YmdHis',strtotime('-1 day'));
			$full_list->fulfillment = $this->Get_Fulfillment_List();
			$full_list->activation = $this->Get_Activation_List();
			
			if($full_list->fulfillment > 0)
			{
				$fulfillment_file = $this->Build_Fulfillment($full_list->fulfillment);
			}
			
			if($full_list->activation > 0)
			{
				$activation_file = $this->Build_Activation($full_list->activation);	
			}
		}
				
		$this->Mail_Files($fulfillment_file,$vertis_file,$activation_file);
		
		return TRUE;
	}
	
	function Get_Fulfillment_List()
	{
		$query = "
		SELECT
			activation.origination_date,
			activation.card_number,
			personal.first_name,
			personal.last_name,
			residence.address,
			residence.city,
			residence.state,
			residence.zip,
			contact.home_phone,
			contact.work_phone,
			contact.email,
			application.promo_code
		FROM
			activation,
			personal,
			residence,
			contact,
			application
		WHERE
			activation.origination_date BETWEEN ".$this->end_date." AND ".$this->start_date." 
		AND
			activation.application_id = application.application_id
		AND
			application.application_id = personal.application_id
		AND
			personal.contact_id = contact.contact_id
		AND
			personal.residence_id = residence.residence_id";
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list[] = $row_data;
		}

		return $list;	
	}
	
	function Get_Activation_List()
	{
		$query = "
		SELECT
			activation.modified_date,
			activation.card_number,
			personal.first_name,
			personal.last_name,
			residence.address,
			residence.city,
			residence.state,
			residence.zip,
			contact.home_phone,
			contact.work_phone,
			contact.email,
			application.promo_code,
			application.marketing_id
		FROM
			activation,
			personal,
			residence,
			contact,
			application
		WHERE
			activation.modified_date BETWEEN ".$this->end_date." AND ".$this->start_date." 
		AND
			activation.application_id = application.application_id
		AND
			application.application_id = personal.application_id
		AND
			personal.contact_id = contact.contact_id
		AND
			personal.residence_id = residence.residence_id
		AND
			activation.active = 'T'";

		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list[] = $row_data;
		}

		return $list;		
	}
	
	function Get_Virtis_List()
	{
		$query = "
		SELECT
			activation.origination_date,
			activation.card_number,
			personal.first_name,
			personal.last_name,
			residence.address,
			residence.city,
			residence.state,
			residence.zip,
			contact.home_phone,
			contact.work_phone,
			contact.email,
			application.promo_code,
			application.marketing_id
		FROM
			activation,
			personal,
			residence,
			contact,
			application
			LEFT JOIN nirvana ON application.application_id = nirvana.application_id
		WHERE
			nirvana.application_id IS NULL
		AND
			activation.origination_date BETWEEN ".$this->end_date." AND ".$this->start_date." 
		AND
			activation.application_id = application.application_id
		AND
			application.application_id = personal.application_id
		AND
			personal.contact_id = contact.contact_id
		AND
			personal.residence_id = residence.residence_id";

		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		while ($row_data = $this->sql->Fetch_Object_Row ($result))
		{
			$list[] = $row_data;
		}

		return $list;	
	}
	
	function Build_Fulfillment($list)
	{
		$file = "DATE,CARD NUMBER,FIRST NAME,LAST NAME,ADDRESS,CITY,STATE,ZIP,HOME PHONE,WORK PHONE,EMAIL,PROMO CODE\n";
		foreach($list AS $line)
		{
			$file .= $line->origination_date.",".$line->card_number.",".$line->first_name.",".$line->last_name.",".$line->address.",".
				    $line->city.",".$line->state.",".$line->zip.",".$line->home_phone.",".$line->work_phone.",".$line->email.",".$line->promo_code."\n";	
		}
		return $file;
	}
	
	function Build_Activation($list)
	{
		$file = "DATE ACTIVATED,CARD NUMBER,FIRST NAME,LAST NAME,ADDRESS,CITY,STATE,ZIP,HOME PHONE,WORK PHONE,EMAIL,PROMO CODE,MARKETING ID\n";
		foreach($list AS $line)
		{
			$file .= $line->modified_date.",".$line->card_number.",".$line->first_name.",".$line->last_name.",".$line->address.",".
				    $line->city.",".$line->state.",".$line->zip.",".$line->home_phone.",".$line->work_phone.",".$line->email.",".$line->promo_code.",".$line->marketing_id."\n";	
		}
		return $file;	
	}
	
	function Build_Virtis($list)
	{
		$file = "DATE,CARD NUMBER,FIRST NAME,LAST NAME,ADDRESS,CITY,STATE,ZIP,HOME PHONE,WORK PHONE,EMAIL,PROMO CODE,MARKETING ID\n";
		foreach($list AS $line)
		{
			$file .= $line->origination_date.",".$line->card_number.",".$line->first_name.",".$line->last_name.",".$line->address.",".
				    $line->city.",".$line->state.",".$line->zip.",".$line->home_phone.",".$line->work_phone.",".$line->email.",".$line->promo_code.",".$line->marketing_id."\n";	
		}
		return $file;	
	}
	
	function Mail_Files($file_1,$file_2,$file_3)
	{
		
		// Build the header
		$header = new stdClass ();
		$header->port = 25;
		$header->url = "fastcashcard.com";
		$header->subject = "FastCash Card Reports - ".date("Y-m-d h:i:s")."";
		$header->sender_name = "FastCash Card";
		$header->sender_address = "no-reply@fastcashcard.com";
	
		// Build the recipient
		$recipient1 = new stdClass ();
		$recipient1->type = "to";
		$recipient1->name = 'Diane';
		$recipient1->address = 'diane@fc500.com';
		//$recipient1->address = 'nick.white@thesellingsource.com';
		
		$recipient2 = new stdClass ();
		$recipient2->type = "cc";
		$recipient2->name = 'NMS';
		$recipient2->address = 'edurr@fc500.com';
		//$recipient2->address = 'nick.white@thesellingsource.com';
			     
		// Build the message
		$message = new stdClass ();
		$message->text = "Attached Report Files";
	
		// Build the attachment
		$attachment1 = new StdClass ();
		$attachment1->name = "Fulfillment_List.csv";
		$attachment1->content = base64_encode($file_1);
		$attachment1->content_type = "plain/text";
		$attachment1->content_length = strlen ($file_1);
		$attachment1->encoded = "TRUE";
		
		$attachment2 = new StdClass ();
		$attachment2->name = "Virtis_List.csv";
		$attachment2->content = base64_encode($file_2);
		$attachment2->content_type = "plain/text";
		$attachment2->content_length = strlen ($file_2);
		$attachment2->encoded = "TRUE";
		
		$attachment3 = new StdClass ();
		$attachment3->name = "Activation_List.csv";
		$attachment3->content = base64_encode($file_3);
		$attachment3->content_type = "plain/text";
		$attachment3->content_length = strlen ($file_3);
		$attachment3->encoded = "TRUE";
		
		$mailing_id = $this->mail->CreateMailing ("FCC_NMS_REPORTS", $header, NULL, NULL);
	
		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}
	
		$package_id = $this->mail->AddPackage ($mailing_id, array ($recipient1,$recipient2), $message, array ($attachment1,$attachment2,$attachment3));
		$sender = $this->mail->SendMail($mailing_id);
		
		/*
		$fp = fopen('/tmp/Fulfillment_List.csv','a');
		$fw = fwrite($fp,$file_1);
		fclose($fp);
		
		if(date('w') == '5')
		{
			$fp = fopen('/tmp/Virtis_List.csv','a');
			$fw = fwrite($fp,$file_2);
			fclose($fp);
		}
		*/
	}
}
?>