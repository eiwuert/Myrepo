<?
/**
 * @desc Cron will run peroidicically sending applications to epm_collect from list_mgmt_buffer. 
 * Will also maintain the global nosell list, list_mgmt_nosell.
 * 
 * @author Vinh Trinh 4/13/2007
 */


define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

define('NO_SELL_EXPIRE',30); // Days before emails are removed from the global nosell list
define('BUFFER_TIME',10); //Time in minutes that applications sit inside list_mgmt_buffer before being sent to epm_collect

require_once('/virtualhosts/lib/olp_list_manager.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');

class List_Mgmt
{
	private $database;
	private $sql;
	private $mode;
	private $debug;
	
	public function __construct($mode,$debug = false)
	{
		$this->debug = $debug;
		$this->mode = $mode;
		$this->sql = Setup_DB::Get_Instance('blackbox',$mode);
		$this->database = $this->sql->db_info['db'];
	}
	
	// Send out applications that are older than $mysql_timestamp
	public function Serve_Epm_Collect($mysql_timestamp = null,$datax_ok2send_list = array('LIVE'))
	{
		if(empty($mysql_timestamp))
		{
			$mysql_timestamp = date('YmdHis',time()-BUFFER_TIME*60);
		}
		
		$query = 
		"
			SELECT
				lmb.email,
				lmb.first_name,
				lmb.last_name,
				lmb.ole_site_id,
				lmb.ole_list_id,
				lmb.group_id,
				ci.ip_address,
				lmb.license_key,
				lmb.address_1,
				lmb.apartment,
				lmb.city,
				lmb.state,
				lmb.zip,
				lmb.url,
				lmb.phone_home,
				lmb.phone_cell,
				lmb.date_of_birth,
				lmb.promo_id,
				lmb.bb_vendor_bypass,
				lmb.tier
			FROM
				list_mgmt_buffer lmb
				JOIN campaign_info ci using (application_id)
			WHERE
				date_created <= '$mysql_timestamp'
				
		";
		
		try 
		{
			$result = $this->sql->Query($this->database,$query);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		
		$count = $this->sql->Row_Count($result);	
		while($rows = $this->sql->Fetch_Array_Row($result))
		{
			$email = $rows['email'];
			$first_name = $rows['first_name'];
			$last_name = $rows['last_name'];
			$ole_site_id = $rows['ole_site_id'];
			$ole_list_id = $rows['ole_list_id'];
			$ip_address = $rows['ip_address'];
			$group_id = $rows['group_id'];
			$license_key = $rows['license_key'];
			$address_1 = $rows['address_1'];
			$apartment = $rows['apartment'];
			$city = $rows['city'];
			$state = $rows['state'];
			$zip = $rows['zip'];
			$url = $rows['url'];
			$phone = $rows['phone_home'];
			$phone_cell = $rows['phone_cell'];
			$date_of_birth = $rows['date_of_birth'];
			$promo_id = $rows['promo_id'];
			$bb_vendor_bypass = $rows['bb_vendor_bypass'];
			$tier = $rows['tier'];

			if(in_array($this->mode,$datax_ok2send_list))
			{
			$olm = new olp_list_manager(
				$email, 
				$first_name, 
				$last_name, 
				$ole_site_id, 
				$ole_list_id, 
				$ip_address,
				0, // DEBUG 
				$group_id,
				$license_key,
				$address_1.' '.$apartment,
				$city,
				$state,
				$zip,
				$url,
				$phone,
				$phone_cell,
				$date_of_birth,
				$promo_id,
				$bb_vendor_bypass,
				$tier
				);				
			}
		}
		
		if($this->debug)
		echo "$count Emails sent to epm_collect.\n";
		
		$query = "
			DELETE FROM
				list_mgmt_buffer
			WHERE
				date_created <= '$mysql_timestamp'	
		";
		
		try 
		{
			$result = $this->sql->Query($this->database,$query);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		
		$count = $this->sql->Affected_Row_Count($result);	
		
		if($this->debug)
		echo "$count Emails purged from list_mgmt_buffer.\n";
	}
	
	// Remove expired email address from global nosell table.
	public function Purge_List_Mgmt_Nosell($mysql_timestamp = null)
	{
		if(empty($mysql_timestamp))
		{
			$mysql_timestamp = date('YmdHis',time()-NO_SELL_EXPIRE*24*60*60);
		}
		
		$query = "
			DELETE FROM
				list_mgmt_nosell
			WHERE
				date_created <= '$mysql_timestamp'	
		";
		
		try 
		{
			$result = $this->sql->Query($this->database,$query);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		
		$count = $this->sql->Affected_Row_Count($result);	
		
		if($this->debug)
		echo "$count Emails Purged from list_mgmt_nosell.\n";
	}
		
}






/* MAIN*/

// Enable outputs?
$debug = false;

// This array specifies which environments will allow leads to be sent off to DATAX's LIVE servers. 
// *** Warning *** The List_MGMT_BUFFER table may be HEAVILY populated with test data in the LOCAL and RC
// Environments/ PLEASE ensure that these tables have been purged properly before attempting to
// write out to DATAX's live database as you may fill their database with test data.
$datax_ok2send_list = array(
//	'LOCAL',
//	'RC',
	'LIVE',
);	

if(isset($argv[1]))
{
	$mode = $argv[1];
}
else
{
	echo "You must pass the mode (RC,LOCAL,LIVE)\n";
	exit(1);
}

$unix_timestamp = time();

$mysql_timestamp = date('YmdHis',$unix_timestamp-BUFFER_TIME*60);
$output_time = date('H:i:s m-d-Y',$unix_timestamp-BUFFER_TIME*60);

if($debug)
echo "\nFinding all records in list_mgmt_buffer on or before $output_time\n";

$list_mgmt_obj = new List_Mgmt($mode,$debug);
$list_mgmt_obj->Serve_Epm_Collect($myql_timestamp,$datax_ok2send_list);

$mysql_timestamp = date('YmdHis',$unix_timestamp-NO_SELL_EXPIRE*24*60*60);
$output_time = date('H:i:s m-d-Y',$unix_timestamp-NO_SELL_EXPIRE*24*60*60);

if($debug)
echo "\nFinding all records in lst_mgmt_nosell on or before $output_time\n";

$list_mgmt_obj->Purge_List_Mgmt_Nosell($mysql_timestamp);

if($debug)
echo "Finished\n"; 
?>