<?php
/**
	@publicsection
	@public
	@brief
		Account Status validation
	
	This process with check if a given account(s) fom ECash
	are verified or in a declined status from
	a data dump of Accounts. Used for SMS Marketing cron.
	
	@version
		0.0.4 2005-08-26 - Raymond Lopez
			- [feature] Optimized SQL Query
			- [feature] Added additonal string checks
			- [bug] Removed SSN spliter for performace reasons (SSN's should be numeric)
		0.0.3 2005-08-25 - Raymond Lopez
			- [feature] GetEmailArray() will return an array of emails
			- [feature] GetSSNArray() will return an array of SSNs
			- [feature] Validate will not return a value (must use get functions)
			
		0.0.2 2005-08-19 - Raymond Lopez
			- [bug] Changed name from Cashline_Validate to Account_Validate
			- [bug] Changed Cashline related vaiables to account
			- [feature] Added Email checking
			- [feature] Added Batch validation
			
		0.0.1 2005-08-19 - Raymond Lopez
			- Application framework creation.
			
	@todo
			
		
*/
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt_config.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt.singleton.class.php');
require_once ("mysql.4.php");
require_once ("account_validate.1.php");
require_once ('prpc/client.php');

//class Account_Validate_2 extends Prpc_Server - RSK
class Account_Validate_2
{
	
	public $account_status;
	public $mode;	
	public $server;
	public $mysql;		
	public $start_ts;
	public $end_ts;
	public $accounts;
	public $badssn;
	public $act_validate;
	public $bb_partial_db;
	public $error_log;
	public $validation_rules;
	private $crypt_config; 
	private $cryptSingleton; 	
	/**
		@publicsection
		@public
		@brief
			Construct class
			
		Set acctount status to false.
		Create an array of sync_cashline_* extentions.
		Create an array based connection mode.
		
		@param $mode string Connection Mode (LOCAL,RC,LIVE).
			
	*/
			
	public function init($mode = "LOCAL") 
	{
		$this->mode = $mode;
		$this->accounts = array();
		$this->bad_ssn = array();
		
		$this->crypt_config 	= Crypt_Config::Get_Config($this->mode);
		$this->cryptSingleton 	= Crypt_Singleton::Get_Instance
        (
            $this->crypt_config['KEY'], $this->crypt_config['IV']
        );	
		
				
		$this->validation_rules = array(
								"Good_Standing",
								"Loan_Limt",
								"In_Process"							
								);

		
		switch( $mode )
		{
			case "LOCAL":// Set MySQL conn vars
				$this->server = array("db" => "olp",
								"host" => "monster.tss:3310",
								"user" => "olp",
								"password" => "hochimin");
				$this->bb_partial_db = "olp_bb_partial";
								
			break;
					
			case "RC":
				$this->server = array("db" => "rc_olp",
								"host" => "db101.clkonline.com",
								"user" => "sellingsource",
								"password" => "%selling\$_db");
				$this->bb_partial_db = "rc_olp_bb_partial";								
			break;
			
			case "LIVE":
				$this->server = array("db" => "olp",
								"host" => "reader.olp.ept.tss:3307",
								"user" => "sellingsource",
								"password" => "%selling\$_db");
				$this->bb_partial_db = "olp_bb_partial";								
			break;
			
		}
		try {
			$this->mysql = new MySQL_4($this->server["host"], $this->server["user"], $this->server["password"]);		
			$this->mysql->Connect();	
		} catch (Exception $e) {  $this->error_log = $e;}	
		
	}	
	
	public function Validate($start_ts,$end_ts,$rules_arr,$database,$mode)
	{
			$this->init($mode);
			$this->start_ts 	= date("Ymd000000",$start_ts);
			$this->end_ts 		= date("Ymd125959",$end_ts);
			$this->accounts		= array();
			// Make sure this is an area
			if(!is_array($rules_arr))
				$rules_arr = array($rules_arr);
				
			
			switch($database)
			{
				case "OLP":
					$this->GetAccounts();
					break;
				case "PARTIALS":
					$this->GetPartials();
					break;
			}		
			
			if(empty($this->accounts)) return array();
			
			$run = true;
			while($run)
			{
				try {
			        foreach($this->validation_rules as $check)
			        {
			        	
			        	// Check to make sure we specified this rule
			        	if(in_array($check,$rules_arr))
				        	$this->{$check}();
			        }
			        $run = false;
		        } catch (Exception $e) {  $this->error_log = $e;}
			}
        			
		
		//return $this->error_log;
        return $this->RunFilter();
	}
	
	/*
		Remove The badd ssn's
	*/
	private function RunFilter()
	{
		for($i=0; $i<count($this->bad_ssn); $i++)
			unset($this->accounts[$this->bad_ssn[$i]]);
		
		return $this->accounts;
	}
	
	private function GetAccounts()
	{
		try 
		{
			$this->accounts = array();					
			$query = "SELECT
					   p.social_security_number,
					   p.cell_phone,
					   a.track_id,
					   t.property_short AS company
					  FROM
					   application as a use index(idx_csr),
					   target as t,
					   personal_encrypted as p
					  WHERE a.created_date >= '{$this->start_ts}'
						AND a.created_date <= '{$this->end_ts}'
						AND a.application_id = p.application_id
						AND a.target_id = t.target_id 
						AND a.application_type IN ('COMPLETED','CONFIRMED','AGREED')
						AND p.cell_phone != ''";

			$result = $this->mysql->Query($this->server["db"],$query);
			while($account =  $this->mysql->Fetch_Array_Row($result))
			{ 
				$account['social_secrurity_number'] = $this->cryptSingleton->decrypt($account['social_secrurity_number'] );
				
				$this->accounts[$account["social_security_number"]][] = $account;
			}	
	
		} catch (Exception $e) {  $this->error_log = $e; return; }				
	}
	
	private function GetPartials()
	{
		$run = true;
		while($run)
		{	
			try 
			{
				$this->accounts = array();		
				$query = "
				SELECT
			                p.social_security_number,
			                p.cell_phone,
			                a.track_id,
			                target.property_short AS company
			                FROM
			                	olp.application a use index(idx_csr), personal_encrypted p
			                JOIN 
			                	olp.target ON target.target_id = a.target_id
			                WHERE
			                	a.application_id = p.application_id
			                AND
		                	a.created_date between '{$this->start_ts}' AND '{$this->end_ts}'
						AND p.cell_phone != ''";
				
				$result = $this->mysql->Query($this->bb_partial_db,$query);
				while($account =  $this->mysql->Fetch_Array_Row($result)) 
				{
					$account['social_secrurity_number'] = $this->cryptSingleton->decrypt($account['social_secrurity_number'] );
				
					$this->accounts[$account["social_security_number"]][] = $account;
				}
				$run = false;
			} catch (Exception $e) {  $this->error_log = $e;}				
		}
	}
	
	/*Validation Rules */
	
	/* Rule Check: Make sure that the loans are in good standing */
	private function Good_Standing()
	{
		global $mode;
		$run = true;
		while($run)
		{
			try {
				$ssn_cache = array_keys($this->accounts);
				$this->act_validate = new Account_Validate($mode);
				$this->act_validate->Validate($ssn_cache, null);
				$ssn_array = $this->act_validate->GetSSNArray();
				for($i=0; $i<count($ssn_array); $i++)
				{
					if(!in_array($ssn_array[$i],$this->bad_ssn))
						$this->bad_ssn[] = $ssn_array[$i];
				}
				$run = false;
			} catch (Exception $e) {  $this->error_log = $e;}
		}
	}
	
	/* Rule Check: Make sure there is only 1 loan */
	private function Loan_Limt()
	{
		// Check and make sure they online 
		foreach($this->accounts as $ssn => $app)
		{	
			$count = count($app);
			
			if($count > 1 && !in_array($ssn,$this->bad_ssn))
				$this->bad_ssn[] = $ssn;
		}
	}
	
	/**
	 * In Process
	 * 
	 * Removes customers with apps in process within the past three days
	 */
	private function In_Process()
	{
		try {
			$start_proc = date("Ymd000000",strtotime("-3 days"));
			$end_proc 	= date("Ymd125959");
			$ssn_array = implode("','",array_keys($this->accounts));
			
			foreach($this->accounts as $socials)
			{
				$ssn_encrypted = $this->cryptSingleton->encrypt($socials[0]["social_security_number"]);
				
				$ssn = mysql_escape_string($socials[0]["social_security_number"]);
				
				if(is_numeric($ssn))
				{
					$query = "SELECT
								p.social_security_number as social_security_number
								FROM
								    application a,
								    personal_encrypted p
								WHERE
								    a.application_id = p.application_id
								AND
									a.created_date between $start_proc and $end_proc
								AND
									p.social_security_number = '{$ssn_encrypted}'";
					
					$this->error_log = $query;
					$result = $this->mysql->Query($this->server["db"],$query);
					while($account =  $this->mysql->Fetch_Array_Row($result))
					{
						$account['social_secrurity_number'] = $this->cryptSingleton->decrypt($account['social_secrurity_number'] );
				
						$ssn = $account["social_security_number"];
						if(!in_array($ssn,$this->bad_ssn))
						$this->bad_ssn[] = $ssn;
					}
				}
			}
			
		} catch (Exception $e) {  $this->error_log = $e;}
	}
	
	/* Make sure there is a SMS Number to Use */
	/*
	private function SMS_Number()
	{
		// Check and make sure there is a cell phone
		foreach($this->accounts as $ssn => $app)
		{	
			foreach($app as $key => $data)
				if($data['cell_phone'] == "")
					$this->bad_ssn[] = $ssn;	
		}		
		
	}
    */
}
?>
