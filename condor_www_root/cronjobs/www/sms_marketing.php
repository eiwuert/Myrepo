<?php
/**
	@publicsection
	@public
	@brief
		SMS Marketing
	
	This process with check for availble account via
	prpc call to accvalid to get a data dump of Accounts. 
	Based on the status of these accounts a call will be 
	made to smscom to submit loan1 and loan2 campaigns
	
	@version
			
	@todo
			
		
*/
set_time_limit(0);
require_once("prpc/client.php");
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
require_once('mysql.4.php');
require_once('mysqli.1.php');
require_once('config.6.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once(BFW_CODE_DIR.'Cache_Config.php');
require_once('statpro_client.php');
require_once(BFW_CODE_DIR.'Enterprise_Data.php');

// pull this in as a library - RSK
require_once("/virtualhosts/bfw.1.edataserver.com/www/accvalid_prpc.php");

define('STATPRO_KEY','clk');
define('STATPRO_PASS','dfbb7d578d6ca1c136304c845');
define('MODE','LIVE');
define('SHOW_OUTPUT',false);

function getCompanyData($property_short)
{
	//Override any Data
	static $company_override = array(	
		'pcl' => array( 
			'promo_id' => 26182
		),
		'ucl' => array(
			'promo_id' => 26183
		),
		'ca' => array(
			"promo_id" => 26184
		),
		'd1' => array(
			"promo_id" => 26181
		),
		'ufc' => array(
			'promo_id' => 26185
		),
		'ic' => array( 
			'promo_id' => 28155
		),
		'ifs' => array( 
			"promo_id" => 28155
		),
	);
	$company_data = Enterprise_Data::getEnterpriseData($property_short);
	$property_short = strtolower($property_short);
	
	//Override any data we need to 
	if(is_array($company_override[$property_short]))
	{
		foreach($company_override[$property_short] as $k => $v)
		{
			$company_data[$k] = $v;
		}
	}
	return $company_data;
}

function getSiteConfig($property_short)
{
	$company_data = getCompanyData($property_short);
	$license = $company_data['license'][MODE];
	$promo_id = $company_data['promo_id'];
	$db = Setup_DB::Get_Instance('MANAGEMENT',MODE,$property_short);
	$cache_config = new Cache_Config($db);
	$config = $cache_config->Get_Site_Config($license, $promo_id);
	return $config;
}

function display($str)
{
	if(defined('SHOW_OUTPUT') && SHOW_OUTPUT === true)
	{
		echo $str;
	}
}

function StatHitter($hitapp,$actions_details)
{
	$mode = (strcasecmp(MODE,'live') == 0) ? 'live' : 'test';
	$bin = '/opt/statpro/bin/spc_'.STATPRO_KEY.'_'.$mode;
	// OLP Database
	$i = 0;
	display("------------------------------------------------------\n");
	$statpro = new StatPro_Client($bin, NULL, STATPRO_KEY, STATPRO_PASS);
	foreach($hitapp as $app)
	{
		if(strlen(trim($app[0]['track_id'])) > 2)
		{
				$run = true;
				try
				{
					
					$config = getSiteConfig($app[0]['company']);
					$space_key = $statpro->Get_Space_Key($config->page_id, $config->promo_id, $config->promo_sub_code);
					$statpro->Space_Key($space_key);
					$statpro->Track_Key($app[0]['track_id']);
					$statpro->Record_Event($actions_details[1]);
					$run = false;
					display("Hitting: ".$app[0]['company']." Track: ".$app[0]['track_id']." Details:".$actions_details[1]."\n");
				}
				catch (Exception $e)
				{
					echo "EXCEPTION: ".$e->getMessage();
				}
				$i++;
		}
		else 
		{
			print_r($app);
		}
	}
	display("------------------------------------------------------\n");
	display("Hit total: $i ");
	
	display("Total Apps:".count($hitapp)."\n");
}		
			
function Loan1()
{
	$days_to_run = 30;
	
	$accounts = array();
	$ssn_check = array();
	$rules_arr = array(	
						"Loan_Limt",		
						"In_Process",								
						"Good_Standing",					
					);		
	// OLP Loan 1
	display("Starting Loan1 Campaign:\n");
	for($i = 7; $i < $days_to_run + 1; $i++)
	{
		display("Day: $i");
		$starter = $i + 1;
		$end = $i;
		$start_ds = strtotime("-$starter days");
		$end_ds = strtotime("-$end days");
		$actions_details = array('OLP', 'sms_market_send_loan1', $start_ds, $end_ds);
		$run = true;
		while($run)
		{
			try
			{
				//$acc = new Prpc_Client($server, FALSE, 32);
				$acc = new Account_Validate_2();
				$accounts = $acc->Validate(
					$actions_details[2],
					$actions_details[3],
					$rules_arr,
					$actions_details[0],
					MODE
				);			
		  		$run = false;
	   	 	}
	   	 	catch (Exception $e) 
			{
				echo("Exception : ".$e->getMessage()); sleep(10);
			}
	   	}
		foreach($accounts as $ssn => $app)
		{
			if(!in_array($ssn,$ssn_check))
			{
				// Add to SSN's not to use
				$ssn_check[] = $ssn;
				// Save App Data
				if(in_array($app[0]['company'],array("ufc","ucl","pcl","d1","ca")))
					$hitapp[$ssn] = $app;
				display("+");
			}
			else
			{
				// We found a dupe so drop the ssn
				if(isset($hitapp[$ssn]))
				{
					unset($hitapp[$ssn]);
					display("-");
				}
				else 
				{
					// We already got rid of this one
					display(".");
				}
			}
			
		}
		display("\n");
	}
	StatHitter($hitapp, $actions_details);
}

function Loan2()
{
	global $prpc_server_base;
	global $mode;
	
	$accounts = array();
	$ssn_check = array();
	$rules_arr = array(	
						"Loan_Limt",					
						"SMS_Number",
						"In_Process",								
						"Good_Standing",					
						);
	// OLP Loan 2

	display("Starting Loan2 Campaign:\n");
	//for($i=90; $i<91; $i = $i + 1)
	for($i=7; $i<91; $i = $i + 1)
	{
		$starter = $i + 1;
		$end = $i;
		display("Day: $i");
		$start_ds = strtotime("-$starter days");
		$end_ds = strtotime("-$end days");
		$actions_details = array("PARTIALS","sms_market_send_loan2",$start_ds, $end_ds);
		$run = true;
		while($run)
		{
			try{
				//$acc = new Prpc_Client($server, FALSE, 32);
				$acc = new Account_Validate_2();
				$accounts = $acc->Validate($actions_details[2],$actions_details[3],$rules_arr,$actions_details[0],MODE);
		  		$run = false;
	   	 	} catch (Exception $e) 
			{ 
				echo("Exception : ".$e->getMessage()); sleep(10);
			}
	   	}
		foreach($accounts as $ssn => $app)
		{
			if(!in_array($ssn,$ssn_check))
			{
				// Add to SSN's not to use
				$ssn_check[] = $ssn;
				// Save App Data
				if(in_array($app[0]['company'],array("ufc","ucl","pcl","d1","ca")))
					$hitapp[$ssn] = $app;
				display("+");
			}
			else
			{
				// We found a dupe so drop the ssn
				if(isset($hitapp[$ssn]))
				{
					unset($hitapp[$ssn]);
					display("-");
				}
				else 
				{
					// We already got rid of this one
					display(".");
				}
			}
			
		}
		print("\n");

	}
	StatHitter($hitapp,$actions_details);
}
// OLP
Loan1();
// Partials
//Loan2();


?>
