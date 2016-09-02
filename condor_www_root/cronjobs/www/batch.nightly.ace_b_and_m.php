<?php
/**
 * This is a ftp cronjob file for the ACE brick and mortor campaign
 *
 * @author August Malson <august.malson@sellingsource.com>
 */


require_once 'prpc/client.php';
require_once '/virtualhosts/bfw.1.edataserver.com/include/code/server.php';
require_once('mysql.4.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt_config.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt.singleton.class.php');

$mode = 'LOCAL';
if ($argv[1] != '')
{
	$mode = $argv[1];
}

//Use sftp to send results to ace
/*
$ftp_host="drop1.sellingsource.com";
$ftp_port=21;
$ftp_user="ace";
$ftp_pass="chESwe5R";
*/
$ftp_host='ftp3.acecashexpress.com';
$ftp_port=21;
$ftp_user='partnerweekly';
$ftp_pass='ACeca$H';

$my_obj = new GenReport($mode, $ftp_user, $ftp_pass,$ftp_host, $ftp_port);

if ($my_obj->runMe())
{
	$filename = $my_obj->getReportName();
	$ftp_file=$filename;

		echo 'done: ' . $ftp_file . "\n"; 
}
else 
{
	echo 'Something bad happened' . "\n";
}
// End of the file run!

/** 
 * GenReport
 *
 * @author August Malson <august.malson@sellingsource.com>
 */
class GenReport
{
	/** 
	 * @var array
	 */
	private $output_data;

	/** 
	 * @var string
	 */
	private $filename;


	/** 
	 * @var string
	 */
	private $username;

	/** 
	 * @var string
	 */
	private $pass;
	
	/** 
	 * @var string
	 */
	private $host;
	
	/** 
	 * @var string
	 */
	private $port;

	/** 
	 * @var string
	 */
	private $mode;

	/**
	 * standard constructor function
	 * 
	 * @param string $mode
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @param string $port
	 * 
	 * @return void
	 **/
	public function __construct($mode, $user, $pass, $host, $port)
	{
		// initialize variables
		$this->output_data = '';
		$this->filename = '';

		$this->mode = strtoupper($mode);

		$this->username = $user;
		$this->pass = $pass;
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * get report name
	 *
	 * @return string
	 */
	public function getReportName()
	{
		return $this->filename;
	}
	
	/**
	 * get leads for the day
	 *
	 * @return void
	 */
	private function getLeadsForDay()
	{

		try
		{
			$server_mode = $this->mode;
			switch ($this->mode)
			{
				case 'LIVE':
					$server_mode = 'SLAVE';
				break;
				case 'LOCAL':
					$server_mode = 'MONSTER';
				break;
			} 

			$server = Server::Get_Server($server_mode,'BLACKBOX');

			$crypt_config 	= Crypt_Config::Get_Config($this->mode);
			$crypt_singleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);


			$sql = new MySQL_4($server['host'],$server['user'],$server['password'],FALSE);

			$sql->Connect();

			$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
			$date2 = date("Y-m-d 00:00:00", $yesterday);
			$date1 = date("Y-m-d 23:59:59", $yesterday);
			// Prepare Query
			$query = "
				SELECT 
					a.application_id,
					p.first_name,
					'' middle_name,
					p.last_name,
					r.address_1,
					r.city,
					r.state,
					r.zip,
					p.home_phone,
					e.work_phone work_phone,
					p.email,
					p.date_of_birth,
					e.employer employer,
					i.monthly_net monthly_income,
					i.pay_frequency how_often_paid,
					if(bi.direct_deposit is TRUE, 'Direct Deposit', 'Paper Check') how_receive_pay,
					i.pay_date_1 next_pay_date1,
					i.pay_date_2 next_pay_date2,
					'' us_citizen_18_years_old,
					'' employed_at_least_3_months,
					e.date_of_hire date_of_hire,
					p.social_security_number social_security_number,
					p.drivers_license_state drivers_license,
					e.income_type main_source_of_income,
					bi.bank_name bank_name,
					bi.routing_number bank_aba,
					bi.account_number bank_account_number,
					bi.bank_account_type bank_account_type,
					'' ref1_name,
					'' ref1_rel,
					'' ref1_phone,
					'' ref2_name,
					'' ref2_rel,
					'' ref2_phone,
					date_format(a.created_date,'%m/%d/%y %k:%i:%s') as date,
					as1.store_id ace_store_id
				FROM 
					application a 
					INNER JOIN income i USING (application_id)
					INNER JOIN bank_info_encrypted bi USING (application_id)
					INNER JOIN employment e USING (application_id)
					INNER JOIN personal_encrypted p USING (application_id)
					INNER JOIN residence r USING (application_id)
					INNER JOIN blackbox_post bp USING (application_id)
					INNER JOIN ace_stores as1 on r.zip = as1.zip_code
						and as1.property_short = 'ace'
				WHERE
					a.created_date BETWEEN '{$date2}' and '{$date1}'
					AND bp.winner = 'ace' and success = 'TRUE'";

			// Gather Data
			$results = $sql->Query($server['db'],$query);
			$csv = "First Name,Middle Name,Last Name,Address,City,State,Zip,Home Phone,"
				. "Work Phone,Email,Date of Birth,Employer,Monthly Income,"
				. "How Often Paid,How Receive Pay,Next Pay Date1, Next Pay Date2,"
				. "18 Years Old, Employed At Least 3 months, SSN, Drivers License,"
				. "Main Source Of Income, Bank Name, Bank ABA, Bank Account Number,Bank Account Type,"
				. "Reference 1 Name, Reference 1 Relationship, Reference 1 Phone, "
				. "Reference 2 Name, Reference 2 Relationship, Reference 2 Phone, Date Received, ACE Store ID\n";
			while ($row = $sql->Fetch_Array_Row($results))
			{
				$query = "SELECT 
							full_name,
							phone,
							relationship
						from personal_contact
						where application_id = {$row['application_id']}";
				$results2 = $sql->Query($server['db'], $query);

				unset($row['application_id']);
				$count = 1;
				while($row2 = $sql->Fetch_Array_Row($results2))
				{
					$row["ref{$count}_name"] = $row2['full_name'];
					$row["ref{$count}_phone"] = $row2['phone'];
					$row["ref{$count}_rel"] = $row2['relationship'];
					$count++;
				}

				if (in_array($mode, array('LIVE','LOCAL')))
				{
					$row['date_of_birth'] = $crypt_singleton->decrypt($row['date_of_birth']);
					$row['social_security_number'] = $crypt_singleton->decrypt($row['social_security_number']);
					$row['bank_aba'] = $crypt_singleton->decrypt($row['bank_aba']);
					$row['bank_account_number'] = $crypt_singleton->decrypt($row['bank_account_number']);
				} 
				else 
				{
					$row['date_of_birth'] = $this->decryptData($row['date_of_birth']);
					$row['social_security_number'] =  $this->decryptData($row['social_security_number']);
					$row['bank_aba'] = $this->decryptData($row['bank_aba']);
					$row['bank_account_number'] =$crypt_singleton->decrypt($row['bank_account_number']);
				}

				$ageToCompare  = mktime(0, 0, 0, date("m")  , date("d"), date("Y")-18);
				$employedDate = mktime(0, 0, 0, date("m")-3  , date("d"), date("Y"));

				$row['us_citizen_18_years_old'] =  ($row['date_of_birth'] < $ageToCompare) ? 'TRUE' : 'FALSE';
				$row['employed_at_least_3_months'] =($row['date_of_hire'] < $employedDate) ? 'TRUE' : 'FALSE';
				unset($row['date_of_hire'])	;

				$mycount = 0;
				foreach ($row as $key => $value)
				{
					if ($mycount++ > 0)
					{
						$csv .= ',';
					}
					
					$csv .= "$value";
				}
				$csv .= "\n";
			}
			// store $csv in output data;
			$this->output_data = $csv;
		} 
		catch (Exception $e)
		{
			echo 'Line: ' . __LINE__ . ' Exception: ' . print_r($e, TRUE) . "\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Run this function to execute the class
	 *
	 * @return boolean
	 */
	public function runMe()
	{
		try
		{
			$yesterday = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
			$this->filename =  'ace-' . date("Y-m-d", $yesterday) . '.csv';
			
			//********************************************* 
			// executing the functions in this manner
			// ensures that they are executed in the
			// correct order.  I would hate for some
			// optimizer come through and the order of
			// AND symbols are executed in reverse
			//********************************************* 
			// temp variables for booleans
			$leads 		= $this->getLeadsForDay();
			$filewrite 	= $this->writeFile();
			$ftpfile 	= $this->ftpFile();
			$removefile	= $this->removeFile();
			

			return $leads && $filewrite && $ftpfile && $removefile;
		} 
		catch (Exception $e)
		{
			echo 'Line: ' . __LINE__ . ' Exception: ' . print_r($e, TRUE) . "\n";
			return FALSE;
		}
	}

	/**
	 * This is where we remove the temp file from  our server
	 *
	 * @return boolean
	 */
	private function removeFile()
	{
		try
		{
			// erase the file on the server
			unlink('/tmp/' . $this->filename);
		}
		catch (Exception $e)
		{
			echo 'Line: ' . __LINE__ . ' Exception: ' . print_r($e, TRUE) . "\n";
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * We FTP the files to the client's server
	 *
	 * @return boolean
	 */
	private function ftpFile()
	{
			
		try
		{
			
			//********************************************* 
			// let's write the file to the server
			//********************************************* 
			
			// set up basic ssl connection
			$conn_id = ftp_ssl_connect($this->host);

			// Login with username and password
			$login_result = ftp_login($conn_id, $this->username, $this->pass);

			//********************************************* 
			// ftp did not work until I added this 
			// you may have to turn it off on the server
			//********************************************* 
			ftp_pasv($conn_id, TRUE);


			ftp_chdir($conn_id, '/toace');
			//echo ftp_pwd($conn_id) . "\n" ;

			if (ftp_put($conn_id, $this->filename, '/tmp/' . $this->filename, FTP_ASCII))
			{
				echo 'success! ' . "\n";
			} 
			else
			{
				echo 'There was a problem uploading ' . $this->filename . "\n";	
			}

			// close the ssl connection
			ftp_close($conn_id);

		}
		catch (Exception $e)
		{
			echo 'Line: ' . __LINE__ . ' Exception: ' . print_r($e, TRUE) . "\n";
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Write the file contents to a temp file on the server 
	 * until we can upload it somewhere else
	 *
	 * @return boolean
	 */
	private function writeFile()
	{

		try
		{
			
			$fp = fopen('/tmp/' . $this->filename, 'w');
			fwrite($fp, $this->output_data);
			fclose($fp);


		}
		catch (Exception $e)
		{
			echo 'Line: ' . __LINE__ . ' Exception: ' . print_r($e, TRUE) . "\n";
			return FALSE;
		}

		return TRUE;
	}

	private function decryptData ($input) 
	{
		switch ( strtoupper($this->mode) )
		{
			case "LOCAL":
					$olp_enc_api = "prpc://callcenter:test321@bfw.1.edataserver.com.ds82.tss:8080/olp_encryption_prpc.php";
					break;
			case "RC":
					$olp_enc_api = "prpc://callcenter:test321@rc.bfw.1.edataserver.com/olp_encryption_prpc.php";
					break;
			case 'REPORT':
			case 'SLAVE':
			case "LIVE": 
					$olp_enc_api = "prpc://callcenter:4w#8_G@bfw.1.edataserver.com/olp_encryption_prpc.php";
					break;
		}

        $crypt = new Prpc_Client($olp_enc_api, FALSE, 32);
        $decrypted = $crypt->decrypt($input);

		return $decrypted;
	}

}
?>
