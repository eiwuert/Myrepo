<?php
require_once(dirname(__FILE__)."/../../www/config.php");

class IDV_CLI_Wrapper
{
	public $argc;
	public $argv;
	private $log;
	private $company;
	private $application_id;
	private $db;
	private $mode;

	public function __construct()
	{
		//this is the default
		$this->mode = 'LOCAL';
	}

	public function Usage($app_name)
	{
		$php = trim(`which php`);
		print <<<END_USAGE
Usage: {$php} {$app_name} MODE company_short application_id
   eg: {$php} {$app_name} RC ucl 440419
   eg: {$php} {$app_name} LIVE d1 900918

Notes: PHP interpreter must be PHP5


END_USAGE;
		exit(1);
}

private function Set_Defines()
{
	//get LOCAL, LIVE or RC
	$this->mode = $this->CLI_Get_Parm(1, "called without environment parm. DataX Fund call lost.");

	switch($this->mode)
	{
		case 'LIVE':
		case 'RC':
		case 'LOCAL':
		$_BATCH_XEQ_MODE = $this->mode;
		break;
		default:
		die("\n" . __FILE__ . " called with invalid environment parm ('{$this->mode}'). DataX Fund call lost. \n\n");
	}
	echo __FILE__ . "\n";
	require_once("applog.1.php");
	require_once("datax.2.php");
	require_once("minixml/minixml.inc.php");
	require_once(LIB_DIR . "common_functions.php");
	require_once(SQL_LIB_DIR.'util.func.php');

	//get UFC, UCL, etc.
	$this->company = $this->CLI_Get_Parm(2, "called without required Company parm. DataX Fund call lost.");
	$this->log = get_log();

	//get the application ID
	$this->application_id = $this->CLI_Get_Parm(3, "called without required Application ID parm. DataX Fund call lost.");

	$this->log->Write(__FILE__ . " called in {$this->mode} environment for application_id {$this->application_id}", LOG_INFO);

	$this->db = ECash_Config::getMasterDbConnection();
}

public function main($argc, $argv)
{
	if($argc != 4)
	{
		IDV_CLI_Wrapper::Usage($argv[0]);
	}

	//get down to business
	$idv = new IDV_CLI_Wrapper();
	$idv->argc = $argc;
	$idv->argv = $argv;
	$idv->Set_Defines();
	echo date("d-M-Y H:i:s ") . ": datax fund forked OK.\n";
	$idv->DataX_Call($idv->Get_IDV_Info());

}

//for gathering command line parameters
private function CLI_Get_Parm($arg_no, $message)
{
	if ($this->argc < ($arg_no + 1) || strlen($this->argv[$arg_no]) == 0)
	{
		if($this->log != NULL)
		{
			$this->log->Write(__FILE__ . " {$message}", LOG_CRIT);
		}
		else
		{
			die("\n" . __FILE__ . " {$message} \n\n");
		}
	}
	return strtoupper($this->argv[$arg_no]);
}


public function Get_IDV_Info()
{
	//get some info including authentication record

	$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT
			(
				CASE
					WHEN
						a.fund_actual > 0
					THEN
						a.fund_actual 
					ELSE 
						a.fund_qualified 
				END
			) as fund_amount,
			 a.date_first_payment,
			(
				CASE 
					WHEN uncompress(bi.received_package) IS NOT NULL 
					THEN uncompress(bi.received_package) 
					ELSE bi.received_package 
				END
				) as received_package,
				a.date_fund_actual funddate,
				a.name_first namefirst,
				a.name_last namelast,
				a.street as street1,
				a.unit as street2,
				a.city,
				a.state,
				a.zip_code zip,
				a.phone_home homephone,
				a.phone_cell cellphone,
				a.email,
				a.legal_id_number legalid,
				a.legal_id_state legalstate,
				YEAR(a.dob) dobyear,
				MONTH(a.dob) dobmonth,
				DAY(a.dob) dobday,
				a.ssn,
				a.name_middle namemiddle,
				a.bank_name bankname,
				a.bank_account bankacct,
				a.bank_aba bankaba,
				a.income_frequency payperiod,
				a.ip_address ipaddress,
				a.phone_work phonework,
				a.employer_name employername,
				site.name source,
				ci.promo_id promo,
				a.application_id,
				a.company_id
		FROM
				application a
				LEFT JOIN bureau_inquiry bi ON (
					bi.application_id = a.application_id
					AND LENGTH(TRIM(bi.received_package)) > 0
				)
				LEFT JOIN bureau b ON (
					 b.bureau_id = bi.bureau_id
					 AND b.name_short = 'datax'
				)
				JOIN (
					SELECT *
					FROM
						campaign_info
					WHERE
						application_id = {$this->application_id}
					ORDER BY
						date_created ASC, campaign_info_id ASC
					LIMIT 1
				) ci ON ci.application_id = a.application_id
				LEFT JOIN site ON site.site_id = ci.site_id
		WHERE
			 a.application_id = {$this->application_id}
		AND
			( bi.inquiry_type NOT LIKE '%fundupd%' OR bi.inquiry_type IS NULL )
		ORDER BY
			bi.date_modified DESC
		LIMIT 1
		";

	try
	{
		$q_obj = $this->db->query($sql);
	}
	catch(Exception $e)
	{
		$this->log->Write(__FILE__ . " Error in bureau_inquiry query", LOG_CRIT);
		throw $e;
	}
	$info = $q_obj->fetch(PDO::FETCH_OBJ);

	return $info;
}

public function DataX_Call($row)
{
	if($row)
	{
		$fund_amount = $row->fund_amount;
		$due_date = date("Y/m/d", strtotime($row->date_first_payment));
		if(! empty($row->funddate))
		{
			$fund_date = date("Y/m/d", strtotime($row->funddate));
		}
		else
		{
			$fund_date = date("Y/m/d");
		}
	
		if(!empty($row->received_package))
		{
			$xml_doc = new SimpleXMLElement($row->received_package);
			$track_hash = (string)$xml_doc->TrackHash;
		}
		else
		{
			$track_hash = '';
		}

		$fields = array(
		       'namefirst',
		       'namelast',
		       'street1',
		       'city',
		       'state',
		       'zip',
		       'homephone',
		       'cellphone',
		       'email',
		       'legalid',
		       'legalstate',
		       'dobyear',
		       'dobmonth',
		       'dobday',
		       'ssn',
		       'namemiddle',
		       'street2',
		       'bankname',
		       'bankacct',
		       'bankaba',
		       'payperiod',
		       'ipaddress',
		       'phonework',
		       'employername',
		       'source',
		       'promo',
		       'funddate'
		);

		$data = array_intersect_key((array)$row, array_flip($fields));

		$data["trackid"] = $this->application_id;
		$data["trackhash"] = $track_hash;
		$data["fundamount"] = $fund_amount;
		$data["fundfee"] = "0.00";
		$data["duedate"] = $due_date;
		$data["funddate"] = $fund_date;

		$datax = new Data_X();
		
		$business_rules = new ECash_BusinessRulesCache(eCash_Config::getMasterDbConnection());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($this->application_id);
		$rule_sets = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		
		/**
		 * The default fund_update call to DataX is 'fundupd-l1' but for 
		 * companies like Agean that require a company specific call, this
		 * will be defined in the config file.
		 */
		if(!empty($rule_sets['FUNDUPD_CALL']))
		{
			$datax_fund_update_call =  $rule_sets['FUNDUPD_CALL'];
		}
		else
		{
			$datax_fund_update_call = 'fundupd-l1';
		}
		
		$response = $datax->Datax_Call($datax_fund_update_call, $data, $this->mode, $this->company);

		/**
		 * I wanted to log this in bureau_inquiry so it'd be easier to review [BR]
		 */
		$bureau = ECash::getFactory()->getModel('Bureau');
		if($bureau->loadBy(array('name_short' => 'datax')))
		{
			$bi_record = ECash::getFactory()->getModel('BureauInquiry');
			$bi_record->company_id = $row->company_id;
			$bi_record->application_id = $row->application_id;
			$bi_record->bureau_id = $bureau->bureau_id;
			$bi_record->inquiry_type = $datax_fund_update_call;
			$bi_record->sent_package = $datax->Get_Sent_Packet();
			$bi_record->received_package = $datax->Get_Received_Packet();
			$bi_record->date_created = time();
			$bi_record->save();
		}
		
		if(empty($response))
		{
			$this->log->Write(__FILE__ . ",  Call to DataX failed. DataX Fund call lost.", LOG_CRIT);
			exit;
		}
		elseif(!empty($response['Response']['ErrorCode']))
		{
			$this->log->Write(__FILE__ . ",  Call to DataX failed: ({$response['Response']['ErrorCode']}) {$response['Response']['ErrorMsg']}. DataX Fund call lost.", LOG_CRIT);
			exit;
		}
		else
		{
			$complete = $response['Response']['Data']['Complete'];
			$this->log->Write(__FILE__ . ", Got funding response for application_id {$this->application_id} from Datax: {$complete}");
		}
		
		/**
		 * GForge #16875 - DataX will now return a Transaction Code for funded applications 
		 * if the customer is using TeleTrack.  This code should be used if we ever make any 
		 * status updates back to TeleTrack. [BR]
		 */
		if(! empty($response['Response']['Data']['TransactionCode']))
		{
			$transaction_code = $response['Response']['Data']['TransactionCode'];
			$this->log->Write(__FILE__ . ", Got TeleTrack transaction code back: $transaction_code");
			
			$at_model = ECash::getFactory()->getModel('ApplicationTeletrack');
			$at_model->loadBy(array('application_id' => $row->application_id));
			//check if entry already exists
			if(empty($at_model->transaction_code))
			{
				$at_model->application_id = $row->application_id;
				$at_model->transaction_code = $transaction_code;
				$at_model->date_created = time();
				$at_model->save();
			}
		}
	}
	else
	{
		$this->log->Write(__FILE__ . ", No DataX received packages found for application_id {$this->application_id}. DataX Fund call lost.");
	}
}
}

IDV_CLI_Wrapper::main($argc, $argv);

?>
