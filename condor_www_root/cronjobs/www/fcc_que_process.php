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

// Instantiate the process for Que
$fnp = new Fcc_Que_Process($sql,$fcc);

// Start processing
$fnp->Start_Process();

/**
	@publicsection
	@public
	Cron job to handle the processing of qued uploads
	
	@brief


	@version
		1.0.0 2004-03-12 - Nick
			
	@change_log
		1.0.0
			- Initial class creation

	@todo
*/



class Fcc_Que_Process
{
	var $sql;
	var $fcc;
		
	/**
	 * @return boolean
	 * @param $sql
	 * @desc Constructor to setp the class
      */
	function Fcc_Que_Process($sql,$fcc)
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
		// Lock the tables so we don't get partial rows
		$query = "
			LOCK TABLES
			upload_que WRITE";
		$this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		
		// Get the data to process
		$query = "
			SELECT
				data
			FROM
				`upload_que`
			WHERE
				processed = '0'";
		$result = $this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		$row_count = $this->sql->Row_Count($result);
		
		// Make sure we found records to be processed
		if($row_count > 0)
		{	
			while ($row_data = $this->sql->Fetch_Object_Row ($result))
			{	
				$data[] = unserialize($row_data->data);
			}
			
			// Unlock the table so stats doesn't throw a fit
			$query = "UNLOCK TABLES";
			$this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
			
			$new_data = $this->fcc->Csv_Integrity_Check($data);
			$clean_data = $this->fcc->Normalize_Data($new_data);
			
			foreach($clean_data as $key=>$value) 
			{
				$this->fcc->Process_Form_Data($value);
				
				// This is an upload, so hit both visitor and prequal for each, FALSE is set so it forces a hit incremented by 1
				$result = Config_3::Get_Site_Config (LICENSE_KEY, $value->promo_id, $value->promo_sub_code);
				$setup = Set_Stat_1::Setup_Stats ($result->site_id, $result->vendor_id, $result->page_id, $value->promo_id, $value->promo_sub_code, $this->sql, $result->stat_base, $result->promo_status);
				Set_Stat_1::Set_Stat ($setup->block_id, $setup->tablename, $this->sql, $result->stat_base, 'visitor', 1);
				Set_Stat_1::Set_Stat ($setup->block_id, $setup->tablename, $this->sql, $result->stat_base, 'prequal', 1);
			}
			
			// Lock the tables again so we can mass update
			$query = "
				LOCK TABLES
				upload_que WRITE";
			$this->sql->Query (FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
			
			// Update the que so we know which records we processed already
			$query = "
				UPDATE
					`upload_que`
				SET
					processed = 1";
			$this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
			
			$query = "UNLOCK TABLES";
			$this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		}
		else
		{
			// There were no records to process, unlock the table and continue
			$query = "UNLOCK TABLES";
			$this->sql->Query(FCC_CARD_DB, $query, "\t".__FILE__."->".__LINE__."\n");
		}
		return TRUE;
	}
}
?>