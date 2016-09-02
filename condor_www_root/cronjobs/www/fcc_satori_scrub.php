<?PHP

// Live
//DEFINE("DIR_INCLUDE", "/virtualhosts/soapdataserver.com/fcc.1/live/include/");
DEFINE("DIR_INCLUDE", "/virtualhosts/fcc.1.soapdataserver.com/include/");
DEFINE("SESSION_DB","fcccard");        	// Session Database
//DEFINE("DIR_LIB", "/virtualhosts/lib/");	// Library Directory
//DEFINE("DIR_CODE", DIR_INCLUDE."code/");	// Code Directory
DEFINE("DIR_LIB", "/virtualhosts/lib/");	// Library Directory
DEFINE("DIR_CODE", DIR_INCLUDE."code/");	// Code Directory
DEFINE("DIR_PRPC", "prpc/");				// Prpc Directory
DEFINE("SESSION_TABLE","session");       	// Session Table
DEFINE("HOST","ds001.ibm.tss");				// DB Host
DEFINE("USER","sellingsource");           	// DB User
DEFINE("PWD","password");            	// DB Password
DEFINE('FCC_CARD_DB','fcccard');          	// FCC Card DB
DEFINE('LICENSE_KEY', 'b71ab92977bc88085cf4fed4663e6485');
DEFINE('MAIL_SERVER', 'prpc://smtp.2.soapdataserver.com/smtp.1.php');

// Required Files
require_once(DIR_LIB."debug.1.php");			// Debug Include
require_once(DIR_LIB."error.2.php");			// Error Include
require_once(DIR_LIB."mysql.3.php");			// Mysql Include
require_once(DIR_LIB."db2.1.php");				// DB2 Include
require_once(DIR_LIB."crypt.3.php");			// Crypt Include
require_once(DIR_LIB."session.5.php");			// Session Include
require_once(DIR_LIB."ole_mail.2.php");			// OLE mail Include
require_once(DIR_LIB."lib_mail.1.php");			// Mail Include
require_once(DIR_LIB."satori.1.php");			// Satori IncludeI 
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
$fnr = new Fcc_Satori_Reports($sql,$fcc,$mail);

// Start processing
$fnr->Run_Report();

/**
	@public

	@version
		1.0.0 2004-04-28 - Nick
			
	@change_log
		1.0.0
			- Initial class creation
	@todo
	-Add bad address stat
	-Hit bad address for each failed satori find
	-Add satori_status field to the application table
	-update the users application record and set as neede (pass,fail)
*/

class Fcc_Satori_Reports
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
	function Fcc_Satori_Reports($sql,$fcc,$mail)
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
		$this->end_date = date('YmdHis',strtotime('-1 year'));
		
		// Build the list for the files	
		$full_list = new stdClass();
		$full_list->error_list = $this->Get_Error_List();


		
		// Build the fulfillment file
		if($full_list->error_list > 0 )
		{
			$error_file = $this->Build_Error_File($full_list->error_list);
		}
				
		$this->Mail_Files($error_file);
		
		return TRUE;
	}
	
	function Get_Error_List()
	{
		$query = "
		SELECT
			activation.origination_date,
			activation.card_number,
			personal.first_name,
			personal.last_name,
			residence.*,
			contact.home_phone,
			contact.work_phone,
			contact.email,
			application.promo_code,
			application.application_id
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
			$valid_status = $this->Satori_Check($row_data);
			if ($valid_status=='FALSE')
			{
				// hit bad_address stat
				$this->fcc->Bad_Address_Stat($row_data->application_id);
				// update satori_status to 0
				$this->Update_Satori_Status($row_data->application_id, 0);
				$list[] = $row_data;
			}
		}
		
		return $list;	
	}
	
	function Update_Satori_Status($application_id, $status)
	{
		echo $query = "
		UPDATE application
		SET 
			satori_status = '".$status."' 
		WHERE 
			application_id = ".$application_id."
		";
		$query = ereg_replace("\t", " ", $query);
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		return TRUE;	
	}	
		
	function Build_Error_File($list)
	{
		$file = "DATE,CARD NUMBER,FIRST NAME,LAST NAME,ADDRESS,CITY,STATE,ZIP,HOME PHONE,WORK PHONE,EMAIL,PROMO CODE\n";
		foreach($list AS $line)
		{
			$file .= $line->origination_date.",".$line->card_number.",".$line->first_name.",".$line->last_name.",".$line->address.",".
				    $line->city.",".$line->state.",".$line->zip.",".$line->home_phone.",".$line->work_phone.",".$line->email.",".$line->promo_code."\n";	
		}
		
		return $file;
	}
	
		
	function Mail_Files($file_1)
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
		$recipient1->name = 'Terry';
		//$recipient1->address = 'terryb@sellingsource.com';
		$recipient1->address = 'don.adriano@thesellingsource.com';
		
		// Build the message
		$message = new stdClass ();
		$message->text = "Attached Report Files";
	
		// Build the attachment
		$attachment1 = new StdClass ();
		$attachment1->name = "Fulfillment_List_".date("Y-m-d").".txt";
		//$attachment1->content = base64_encode(gzencode($file_1, 9));
		$attachment1->content = base64_encode($file_1);
		$attachment1->content_type = "plain/text";
		$attachment1->content_length = strlen ($file_1);
		$attachment1->encoded = "TRUE";
		
		$mailing_id = $this->mail->CreateMailing ("FCC_Satori_REPORTS", $header, NULL, NULL);
	
		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}
	
		$package_id = $this->mail->AddPackage ($mailing_id, array ($recipient1), $message, array ($attachment1));
		$sender = $this->mail->SendMail($mailing_id);
		
		/*
		$fp = fopen('/tmp/Fulfillment_List.csv','a');
		$fw = fwrite($fp,$file_1);
		fclose($fp);
		
		$fp = fopen('/tmp/Virtis_List.csv','a');
		$fw = fwrite($fp,$file_2);
		fclose($fp);		
		*/
	}
	
	function Satori_Check($address)
	{
		$satori = new Satori_1();

		$request_object = new stdClass();
		$request_object->request_id = 123;
		$request_object->organization = "";
		$request_object->address_1 = $address->address;
		$request_object->address_2 = "";
		$request_object->city = $address->city;
		$request_object->state = $address->state;
		$request_object->zip = $address->zip;
		$request_object->user_defined_1 = "";
		$request_object->user_defined_2 = "";
		
		//print '<pre>$request_object: '.print_r($request_object,true).'</pre>';

		$satori_result = $satori->Validate_Address($request_object, Debug_1::Trace_Code(__FILE__, __LINE__));
		
		return $satori_result->valid;
	}
}