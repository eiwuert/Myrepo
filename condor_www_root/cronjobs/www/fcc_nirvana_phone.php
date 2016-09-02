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
$fnp = new Fcc_Nirvana_Phone($sql,$fcc);

// Start processing
$fnp->Start_Process();

/**
	@publicsection
	@public
	Cron job to handle the generation of the 1 hour phone list
	
	@brief


	@version
		1.0.0 2004-03-15 - Nick
		1.0.1 2004-04-08 - Nick
			
	@change_log
		1.0.0
			- Initial class creation
		1.0.1
			- Changed teleweb database phone list is written 

	@todo
*/

class Fcc_Nirvana_Phone
{
	var $sql;
	var $fcc;
		
	/**
	 * @return boolean
	 * @param $sql
	 * @desc Constructor to setp the class
      */
	function Fcc_Nirvana_Phone($sql,$fcc)
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
		$delivery_list->phone_list = $this->Generate_1_Hour();
		
		$this->Chase_Call_Insert($delivery_list);
		
		$this->Update_Nirvana($delivery_list);
		
		return TRUE;
	}
	
	/**
	 * @return boolean
	 * @desc Generate the list of customers to add to the phone list where they have been inserted within the last hour
      */
	function Generate_1_Hour()
	{
		// Get the prev hour
		$hour = date('H') - 1;
		$exclude_promo_id = 24296;
		
		$list = new stdClass();
		
		// Select records that were created in the last hour
		$query = "
			SELECT
				contact.email,
				contact.home_phone,
				application.account_id,
				personal.first_name,
				personal.last_name,
				residence.address,
				residence.city,
				residence.state,
				residence.zip,
				nirvana.application_id
			FROM 
				contact,
				application,
				personal,
				residence,
				nirvana
			WHERE
				".$hour." = HOUR(nirvana.origination_date)
			AND
				nirvana.origination_date between ".date('Y-m-d')." AND ".date('Y-m-d', strtotime('+1 day'))."	
			AND
				nirvana.phone_list = 0
			AND
				nirvana.application_id = personal.application_id
			AND
				nirvana.application_id = application.application_id
			AND
				application.application_id != ".$exclude_promo_id."
			AND
				personal.residence_id = residence.residence_id
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
	 * @param $delivery_list object
	 * @desc Insert into the the chasecall table the data for teleweb
      */
	function Chase_Call_Insert($delivery_list)
	{
		foreach($delivery_list AS $field=>$list)
		{
			foreach($list AS $id=>$data)
			{
				$query = "
					INSERT INTO
						`chasecall`
						(account_id,
						created_date,
						site_name,
						application_id,
						first_name,
						last_name,
						email,
						home_phone,
						address,
						city,
						state,
						zip,
						chase_type)
					VALUES
						('".$data->account_id."',
						NOW(),
						'fastcashcard.com',
						'".trim($data->application_id)."',
						'".trim($data->first_name)."',
						'".trim($data->last_name)."',
						'".trim($data->email)."',
						'".trim($data->home_phone)."',
						'".trim($data->address)."',
						'".trim($data->city)."',
						'".trim($data->state)."',
						'".trim($data->zip)."',
						'FCC_1_HOUR')";
				$this->sql->Query ('olp_d1_chasecall', $query, "\t".__FILE__."->".__LINE__."\n");
			}
				
			return TRUE;		
		}
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
				$this->fcc->Nirvana_Update($data->application_id,$field,FCC_CARD_DB);	
			}	
		}
		
		return TRUE;
	}
}
?>
