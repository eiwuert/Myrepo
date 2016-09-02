<?PHP

//Live
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
$fia = new Fcc_Ivr_Activation($sql,$fcc);

// Start processing
$fia->Start_Process();

/**
	@publicsection
	@public
	Cron job to handle the hitting of stats and activation for IVR activations
	
	@brief


	@version
		1.0.0 2004-03-15 - Nick
			
	@change_log
		1.0.0
			- Initial class creation

	@todo
*/

class Fcc_Ivr_Activation
{
	var $sql;
	var $fcc;
		
	/**
	 * @return boolean
	 * @param $sql
	 * @desc Constructor to setp the class
      */
	function Fcc_Ivr_Activation($sql,$fcc)
	{
		$this->sql = $sql;
		$this->fcc = $fcc;
		return TRUE;
	}
	
	/**
	 * @return boolean
	 * Start the processing for activation
	 */
	function Start_Process()
	{
		$activation_list = $this->Get_Activation_List();
		
		if($activation_list)
		{
			foreach($activation_list AS $record)
			{
				$this->fcc->Activate_Card($record);
				$this->fcc->Ivr_Activate_Stat($record);	
			}
			
			$this->Update_Ivr_Activation($activation_list);
		}
		return TRUE;	
	}
	
	/**
	 * @return $list array
	 * @desc Get a list of ivr activations that need processing
      */
	function Get_Activation_List()
	{
		$query = "
			SELECT
				cc_number
			FROM
				`ivr_activation`
			WHERE
				process_status = 'F'";
		
		$result = $this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		$i = 0;
		while ($row_data = $this->sql->Fetch_Array_Row ($result))
		{
			$list['id_'.$i] = $row_data;
			$i++;
		}
		
		if($i > 0)
		{
			return $list;
		}
		else
		{
			return FALSE;	
		}
	}
	
	/**
	 * @return boolean
	 * @param $data array
	 * @desc Update the IVR Activation table and set the process to true
      */
	function Update_Ivr_Activation($data)
	{
		foreach($data AS $record)
		{
			$cc_list .= "'".$record['cc_number']."',";	
		}
		
		$query = "
			UPDATE
				`ivr_activation`
			SET
				process_status = 'T'
			WHERE
				process_status = 'F'
			AND
				cc_number IN(".substr($cc_list,0,-1).")";
		
		$this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
	}
}
?>
