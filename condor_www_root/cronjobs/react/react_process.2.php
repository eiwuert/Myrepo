<?php

/**
	@publicsection
	@public
	@brief
		Create React Loan

	Gathers Inactive/Paid loans withing a set time period
	and sends emails to the Loan owner which enables them
	to React.

	@version

		0.0.1 2005-08-19 - Raymond Lopez
			- Application framework creation.

	@todo


*/

define('LOG_DIR', '/virtualhosts/cronjobs/react/log');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

if (!is_writable(LOG_DIR))
{ die("Unable to write to log directory\n"); }

require_once("mysqli.1.php");
require_once("prpc/client.php");
require_once("library.1.php");
require_once("error.2.php");
require_once("prpc/client.php");
require_once ('statpro_client.php');
require_once ('config.5.php');	
//Load correct Config
require_once('config.6.php');
require_once(BFW_CODE_DIR . 'server.php');
require_once(BFW_CODE_DIR . 'setup_db.php');
		
				
if (
// mode
!in_array(strtoupper(trim($_SERVER['argv'][1])), array('LOCAL', 'RC', 'LIVE'))
	||
// company (default=clk)
(strlen($_SERVER['argv'][2]) && !in_array($_SERVER['argv'][2],array('clk', 'impact', 'agean','lcs'))))
{
	die("Usage: ".$_SERVER['SCRIPT_NAME']." LOCAL|RC|LIVE clk|impact|agean|lcs\n");
}



define('REACT_GEN_LOG', LOG_DIR.'/react_process2_'.date("Ymd").'_'.strtolower(trim($_SERVER['argv'][1])).'_'.strtolower(trim($_SERVER['argv'][2])).'.log');
$fh = fopen(REACT_GEN_LOG, "a");

class Create_Reacts
{
	public $mode;
	public $db;
	public $reacts;
	public $mysql;
	public $dateFrom;
	public $dateTo;
	public $config;
	public $props;
	public $company;
	public $fh;
	public $license;
	public $statpro_key;
	public $statpro_pass;
	public $react_count;
	public $master_remove;


	public function __construct() {

		global $fh;

		$this->fh = $fh;
		if(!in_array(strtolower($_SERVER['argv'][2]), array('impact', 'clk', 'agean','lcs')))
		{
			$this->company = 'impact';
		}
		else
		{
			$this->company = strtolower($_SERVER['argv'][2]);
		}
		$this->master_remove = new Prpc_Client('prpc://cpanel.partnerweekly.com/service/unsub.php');
		
		switch ($this->company)
		{
			case 'clk':
				// Enable each company one by one
				//$this->props = array ("ca","d1","pcl","ucl","ufc");
				$this->props = array('ufc', 'pcl', 'ucl', 'd1', 'ca');
				$this->statpro_key = 'clk';
				$this->statpro_pass = 'dfbb7d578d6ca1c136304c845';
				break;
			case 'impact':
				$this->props = array("ic");
				$this->statpro_key = 'imp';
				$this->statpro_pass = 'h0l3iny0urp4nts';
				break;
			case 'agean':
				$this->props = array('cbnk', 'jiffy');
				$this->statpro_key = 'agean';
				$this->statpro_pass = 'ohChua3t';
				break;
			case 'lcs':
				$this->props = array('lcs');
				$this->statpro_key = 'lcs';
				$this->statpro_pass = 'F7eu5Kr1';
				break;
		}

		$this->mode = strtoupper(trim($_SERVER['argv'][1]));
		define('DEBUG',FALSE); //Added to remove notice from Setup_DB call
		$sql = Setup_DB::Get_Instance('management', $this->mode);
		$this->config_obj = new Config_6($sql);
		
	}

	//Setup the database/sms server based on the property_short
	//passed
	function Setup_React_DB($prop_short)
	{
		
		$this->mysql = Setup_DB::Get_Instance('mysql', $this->mode, $prop_short);

	}
		
	public function GatherReacts($dateFrom, $dateTo)
	{

		$this->dateFrom 	= $dateFrom;
		$this->dateTo		= $dateTo;
		
		//Loop through each prop_short in this company
		//and process each one seperately basically
		$hashes = array();
		foreach($this->props as $property)
		{
			fwrite($this->fh, "[".date("dmYHIs")."] React Processing Beginning (".substr($this->dateFrom,0,8)." - ".substr($this->dateTo,0,8).") For property_short $property\n");	// Start Log Entry
			$this->Setup_React_DB($property);

			//Make a hash based on the database info so 
			//that we don't query the same database 
			//twice.
			$hash = md5(serialize($this->mysql->db_info));
			if(in_array($hash,$hashes))
			{
				fwrite($this->fh, "[".date("dmYHIs")."] Database for $property has already been checked.\n");
				continue;
			}
			else 
			{
				$hashes[] = $hash;
			}
			
			$query = "SELECT DISTINCT
					app.application_id,
					com.name_short AS property_short,
					email,
					phone_cell,
					track_id,
					IF(app.date_created > '2007-08-09', 'TRUE', 'FALSE') AS send_wap,
					site.license_key,
					campaign_info.promo_id,
					campaign_info.promo_sub_code,
					site.site_id
				FROM application AS app
				INNER JOIN company AS com USING (company_id)
				INNER JOIN application_status AS app_stat ON app.application_status_id = app_stat.application_status_id 
				INNER JOIN status_history AS sh ON app.application_id = sh.application_id
				INNER JOIN campaign_info ON campaign_info.campaign_info_id = (
						SELECT MAX(ci2.campaign_info_id)
						FROM campaign_info ci2
						WHERE ci2.application_id = app.application_id
					)
				INNER JOIN site ON campaign_info.site_id = site.site_id    		
				WHERE app_stat.name_short = 'paid'
					AND com.name_short IN ('".implode("','",$this->props)."')
					AND sh.date_created BETWEEN '$this->dateFrom' AND '$this->dateTo'			
					AND	(
							SELECT COUNT(application_id)
							FROM application_column AS ac
							INNER JOIN application AS appcol USING(application_id)
							WHERE appcol.company_id = com.company_id
                    			AND appcol.ssn = app.ssn
                    			AND ac.table_name = 'application'
                    			AND ac.do_not_loan = 'on'
                    	) = 0";
			if ($this->mode!='LIVE') $query .= " LIMIT 25";
			try
			{

				$mysqli_results = $this->mysql->Query($query);
				$this->react_count += $this->mysql->Affected_Row_Count();
				while($row =  $mysqli_results->Fetch_Array_Row())
				{
					$react = $row;
					$react["reckey"] = urlencode(base64_encode($row["application_id"]));

					if($this->CheckUnsubList($react['email']))
					{
						continue;
					}
					$react["property_short"] = $row["property_short"]; 
					switch ($row["property_short"])
					{
						case "ca":
							$react["datalink"] = "ameriloan.com";
							$react["promo_id"] = 26184;
							$react["wap_promo_id"] = 30232;
							$react["service_phone"] = "1-800-362-9090";
							break;
						case "d1":
							$react["datalink"] = "500fastcash.com";
							$react["promo_id"] = 26181;
							$react["wap_promo_id"] = 30233;
							$react["service_phone"] = "1-888-919-6669";
							break;
						case "pcl":
							$react["datalink"] = "oneclickcash.com";
							$react["promo_id"] = 26182;
							$react["wap_promo_id"] = 30234;
							$react["service_phone"] = "1-800-230-3266";
							break;
						case "ucl":
							$react["datalink"] = "unitedcashloans.com";
							$react["promo_id"] = 26183;
							$react["wap_promo_id"] = 30235;
							$react["service_phone"] = "1-800-279-8511";
							break;
						case "ufc":
							$react["datalink"] = "usfastcash.com";
							$react["promo_id"] = 26185;
							$react["wap_promo_id"] = 30236;
							$react["service_phone"] = "1-800-640-1295";
							break;
						case "ic":
							$react["datalink"] = "impactcashusa.com";
							$react["promo_id"] = 28155;
							$react["service_phone"] = "1-800-707-0102";
							break;
						case 'cbnk':
							$react['datalink'] = 'cashbanc.com';
							$react['promo_id'] = 99999;
							$react['service_phone'] = '1-800-979-0823';
							break;
						case 'jiffy':
							$react['datalink'] = 'jiffycash.com';
							$react['promo_id'] = 99999;
							$react['service_phone'] = '1-800-979-4808';
							break;
						case 'lcs':
							$react['datalink'] = 'lendingcashsource.com';
							$react['promo_id'] = 32402;
							$react['service_phone'] = '1-888-501-2698';
							break;
					}
					$this->reacts[] = $react;
					fwrite($this->fh, "Adding To React Process Queue: ".$react['email'].". \n");
				}
			}
			catch (Exception $e)
			{
				print_r($e);
			}
		}
	}


	public function CheckUnsubList($email, $attempt = 1)
	{
		$return = false;
		
		try
		{
			if($this->master_remove->queryUnsubEmail($email))
			{
				fwrite($this->fh, $email . " is in master remove email\n");
				$return = true;
			}
		}
		catch(Exception $e)
		{
			if($attempt !== 1)
			{
				//If we got an exception twice in a row, just skip them.
				$return = true;
				$text = "Failed to successfully call queryUnsubEmail($email) twice.\n";
				
				fwrite($this->fh, $text);
				echo $text;
			}
			else
			{
				$return = $this->CheckUnsubList($email, 2);
			}
		}
		
		return $return;
	}

	private function HitStat($react, $stat_name, $promo_id)
	{
		$lic_key = $react['license_key'];
		$promo_sub_code = $react['promo_sub_code'];
		try
		{
			$this->config = $this->config_obj->Get_Site_Config($lic_key, $promo_id, $promo_sub_code);
		}
		catch(Exception $e)
		{
			fwrite($this->fh, $e->getMessage() . " license key: {$lic_key}\n");
		}
		
		$this->statpro->Space_key($this->statpro->Get_Space_Key($this->config->page_id, $promo_id, $promo_sub_code));
		$this->statpro->Track_Key($react['track_id']);
		$this->statpro->Record_Event($stat_name);
	}
	
	//Function to hit the 'react_start' stat
	public function StartReactProcess()
	{
		$bin = '/opt/statpro/bin/spc_'.$this->statpro_key.'_'.strtolower($this->mode);
		$this->statpro = new StatPro_Client($bin, NULL, $this->statpro_key, $this->statpro_pass);
		
		if(!empty($this->reacts))
		{
			foreach($this->reacts as $react)
			{
				$this->HitStat($react, 'react_start', $react['promo_id']);
	
				//send the people with new docs (8/9/07 and after) wap messages if they have a mobile [JustinF]
				if($react['phone_cell'] && $react['send_wap'] == 'TRUE' && isset($react['wap_promo_id']))
				{
					fwrite($this->fh, "SMS WAP React Stat Track ID: {$react['track_id']}\n");
					$this->HitStat($react, 'react_wap_send', $react['wap_promo_id']);
				}
			}
		}
	}

}

// Lets Begin



$reactor = new Create_Reacts();
if(in_array($reactor->company, array('impact', 'agean','lcs')))
{
    // We want data for the past day
	$fromdate 	= date("Ymd", strtotime("-1 days"))."000000";
	$todate 	= date("Ymd")."000001";

}
elseif($reactor->company == 'clk')
{
        // We want data for the past 4 days not earlier
        $fromdate       = date("Ymd", strtotime("-4 days"))."000000";
        $todate         = date("Ymd", strtotime("-3 days"))."000001";
}

$reactor->GatherReacts($fromdate ,$todate);
$reactor->StartReactProcess();

fwrite($fh, "[".date("dmYHIs")."] React Processing Complete. ({$reactor->react_count} reacts)\n\n");		// End Log Entry
// echo "[".date("dmYHIs")."] React Processing Complete. ({$reactor->react_count} reacts)\n\n";

fclose($fh);

?>
