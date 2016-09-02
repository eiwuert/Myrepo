<?php
/**
	@publicsection
	@public
	@brief
		Account Status validation
	
	This process with check if a given account(s) fom ECash
	are verified or in a declined status from
	a data dump of Accounts.
	
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

require_once ("mysql.4.php");

class Account_Validate 
{
	
	public $invalid_accounts;
	public $valid_accounts;
	public $total_accounts;
	private $account_status;
	private $ssn;
	private $ssn_array;
	private $email_address;
	private $email_array;
	private $mode;	
	private $account_db_ext;
	private $account_invalid_status;
	private $db;
	private $mysql;		
	private $tmp_where_arr;
	private $tmp_where;
	private $e;
	private $valid_result;
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
	public function  __construct($mode = "LOCAL") 
	{
		define("CASHLINE_DB","sync_cashline_");
		define("CUSTOMER_TABLE","cashline_customer_list");
		define("SSN_COL","social_security_number");
		define("EMAIL_COL","email_address");	
		define("STAT_COL", "status");
		$this->invalid_accounts = array();	
		$this->account_status = false;		
		$this->account_db_ext = array ("d1", "ufc", "pcl", "ucl", "ca");		
		$this->account_invalid_status = array (
										"COLLECTION", 
										"HOLD",
										"SCANNED",
										"BANKRUPTCY",
										"WITHDRAWN",
										"INCOMPLETE",
										"DENIED");
		switch ($mode) 
		{
			case "LIVE":	
		    	$this->db = array (
		    				"host" => "writer.olp.ept.tss",
		    				"user" => "sellingsource",
		    				"password" => "password");
		    	break;
		    case "LOCAL":
		    	$this->db = array (
		    				"host" => "beast.tss",
		    				"user" => "root",
		    				"password" => "");
		    	break;		    

		}
	}	
	
    /**
		@publicsection
		@public
		@brief 
			Validate Account is in good standing
			
    	@param $ssn string or array Acount SSN 
    	@param $email_address string or array Account Email Address
    	@return $invalid_accounts array Array object of invalid accounts
    */
	public function Validate($ssn = null, $email_address = null) {
		$this->invalid_accounts = null;
		if (is_array($this->db) &! (is_null($ssn) && is_null($email_address)))
		{
			$this->ssn_array = $this->ManageSSNArray($ssn);
			$this->email_array = $this->ManageEmailArray($email_address);
			if(is_array($this->ssn_array) || is_array($this->email_array)) {
				try 
				{					
					$sql_query = $this->CompileSQLUnionQuery($this->ssn_array,$this->email_array);								
					$this->mysql = new MySQL_4($this->db["host"], $this->db["user"], $this->db["password"]);				
					$this->mysql->Connect();
					for($i=0; $i<count($sql_query); $i++) 
					{
						$result = $this->mysql->Query(CASHLINE_DB.$this->account_db_ext[0], $sql_query[$i]);
						$this->AccountResultCheck($result);
						$result = null;
					}
	
					$this->mysql->Close_Connection();
				} 
				catch (MySQL_Exception $e)
				{
					$this->invalid_accounts = null;
				} 
			} 
		}		
		return $this->invalid_accounts;
	}	
	
    /**
		@publicsection
		@public
		@brief 
			 Constructs and array and combination SSN formats
			 (not used for performance reasons)
			 
    	@param $ssn string SSN number
    	@return ssn string array
    */	
	private function GetSSNStringArray($ssn = null)
	{
		if (strlen($ssn) == 9)
		{			
			$ssn_arr[] = $ssn;
			$ssn_string = $ssn[0].$ssn[1].$ssn[2]."-";
			$ssn_string .= $ssn[3].$ssn[4]."-";
			$ssn_string .= $ssn[5].$ssn[6].$ssn[7].$ssn[8];
			$ssn_arr[] = $ssn_string;
		} else if (strstr($ssn,"-") && (count(strlen($ssn)) == 11)) 
		{
			$ssn_arr[] = str_replace("-","",$ssn);
			$ssn_arr[] = $ssn;
		} 
		else if(is_null($ssn) || (trim($ssn) == "")) 		
		{
			$ssn_arr = null;
		} 
		else
		{
			$ssn_arr[] = $ssn;	
		}
		$ssn_arr[] = $ssn;
		return $ssn_arr;
	}

    /**
		@publicsection
		@public
		@brief  
			Constructs a complext SQL query bases on SSN and email array
			
    	@param $ssn string array SSN Array
    	@param $email_arr array Email Arrays
    	@return sqlarray
    */	
	private function CompileSQLUnionQuery($ssn_arr = null,$email_arr = null) {
		$tmp_where_ssn = null;
		$tmp_where_email = null; 
		if(count($ssn_arr) > 0) 
			$tmp_where_ssn  = "(".SSN_COL." IN ('".implode("','",$ssn_arr)."'))";
		if(count($email_arr) > 0) 
			$tmp_where_email  = "(".EMAIL_COL." IN ('".implode("','",$email_arr)."'))";
		for ($i=0; $i<count($this->account_db_ext); $i++) 
		{
			$db_ext_item = $this->account_db_ext[$i];
			$tmp_cashline_col = CASHLINE_DB.$db_ext_item.".".CUSTOMER_TABLE;
			if($tmp_where_ssn) 
				$sql_query_array[] = "select ".SSN_COL.", ".EMAIL_COL.", ".STAT_COL." from $tmp_cashline_col where $tmp_where_ssn";
			if($tmp_where_email) 
				$sql_query_array[] = "select ".SSN_COL.", ".EMAIL_COL.", ".STAT_COL." from $tmp_cashline_col where $tmp_where_email";				
			
		}	
		return $sql_query_array;		
	}
	
    /**
		@publicsection
		@public
		@brief 
			Creates SSN Number arrays
			
    	@param $ssn string or array
    	@return ssn string array
    */		
	private function ManageSSNArray($ssn = null) {
		$ssn_array = array();
		/*
		Commented out performance reasons
		if(is_string($ssn) || is_numeric($ssn)) 
		{
			$ssn_array = $this->GetSSNStringArray($ssn);
		}
		else if(is_array($ssn)) 
		{
			for($i=0; $i<count($ssn); $i++) 
			{				
				$ssn_item = $this->GetSSNStringArray($ssn[$i]);
				if (count($ssn_item) > 0) 
					$ssn_array = array_merge($ssn_array,$ssn_item);
			}
		} 
		*/
		if (is_array($ssn)) 
		{
			$ssn_array = $ssn;
		} 
		else if(!is_null($ssn))
		{
			$ssn_array[] = $ssn;
		}

		return $ssn_array;
	}
	
    /**
		@publicsection
		@public
		@brief 
			Creates Email address array
			
    	@param $ssn string SSN number
    	@return ssn string array
    */		
	private function ManageEmailArray($email_address = null)
	{ 
		$email_array =array();
		if(is_array($email_address)) 
		{
			$email_array = $email_address;
		} 
		else if(!is_null($email_address))
		{
			$email_array[] = $email_address;
		}
		return $email_array;
	}
	
    /**
		@publicsection
		@public
		@brief 
			Parse through MySQL result for bad accounts
			
    	@param $result resource MySQL result
    	@return void
    */	
	private function AccountResultCheck($result) 
	{		
		if($this->mysql->Row_Count($result) == 1)
		{
			$asso_arr = $this->mysql->Fetch_Array_Row($result);
			if (in_array($asso_arr[STAT_COL],$this->account_invalid_status))
			{
				$this->invalid_accounts[] = $asso_arr;
			}
			else
			{
				$this->valid_accounts[] = $asso_arr;	
			}	
			$this->total_accounts[] = $asso_arr;		
		} 
		else if($this->mysql->Row_Count($result) > 0) 
		{	
			while($act_item =  $this->mysql->Fetch_Array_Row($result)) 
			{
				if (in_array($act_item[STAT_COL],$this->account_invalid_status))
				{
					$this->invalid_accounts[] = $act_item;
				}
				else
				{
					$this->valid_accounts[] = $act_item;	
				}
				$this->total_accounts[] = $act_item;
			}
		}
		return;		
	}
	
	/**
		@privatesection
		@private
		@brief
			ResultArrayParse
			
		Parses through invalid_acounts and returns request types
		
		@param $col string EMAIL_COL or SSN_COL
		@return $object array
			
	*/	
	private function ResultArrayParse($col, $accounts) 
	{		
		$return_array = array();
		$dupe = 0;
		
		for($i=0; $i<count($accounts); $i++) {
			$item = $accounts[$i];
			$item_sel = $item[$col];
			if(!in_array($item_sel,$return_array))
			{
				$return_array[] = $item_sel;
			}
			else
			{
				$dupe++;	
			}
		} 
		return 	$return_array;
	}
	
	/**
		@publicesection
		@public
		@brief
			GetEmailArray
			
		Returns an array of emails found;
		
		@return $emails array
			
	*/		
	public function GetEmailArray() 
	{
		return $this->ResultArrayParse(EMAIL_COL,$this->invalid_accounts);
	}
	
	/**
		@publicesection
		@public
		@brief
			GetSSNArray
			
		Returns an array of SSNs found;
		
		@return $ssn array
			
	*/		
	public function GetSSNArray() 
	{
		return $this->ResultArrayParse(SSN_COL,$this->invalid_accounts);
	}
	
	/**
		@publicesection
		@public
		@brief
			GetEmailArray
			
		Returns an array of emails found;
		
		@return $emails array
			
	*/		
	public function GetValidEmailArray() 
	{
		return $this->ResultArrayParse(EMAIL_COL,$this->valid_accounts);
	}
	
	/**
		@publicesection
		@public
		@brief
			GetSSNArray
			
		Returns an array of SSNs found;
		
		@return $ssn array
			
	*/		
	
	public function GetValidSSNArray() 
	{
		return $this->ResultArrayParse(SSN_COL,$this->valid_accounts);
	}	
	
	/**
		@publicesection
		@public
		@brief
			GetEmailArray
			
		Returns an array of emails found;
		
		@return $emails array
			
	*/		
	public function GetTotalEmailArray() 
	{
		return $this->ResultArrayParse(EMAIL_COL,$this->total_accounts);
	}
	
	/**
		@publicesection
		@public
		@brief
			GetSSNArray
			
		Returns an array of SSNs found;
		
		@return $ssn array
			
	*/		
	public function GetTotalSSNArray() 
	{
		return $this->ResultArrayParse(SSN_COL,$this->total_accounts);
	}		
}
?>