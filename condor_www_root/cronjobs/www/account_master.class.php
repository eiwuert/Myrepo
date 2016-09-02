<?PHP
class Account_Master
        {
		var $sql;
		var $database;
		var $trans_id;
		var $audit_trail;
				
		/**
		 * @return boolean
		 * @param sql
		 * @param database
		 * @param audit_trail
		 * @desc Instantiate Account Master class
		 */
		function Account_Master($sql,$database,$audit_trail)
		{
			$this->sql = $sql;
			$this->database = $database;
			$this->trans_id = '';
			$this->audit_trail = $audit_trail;
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param trans_obj
		 * @desc Creates a new transaction entry and initial line item
		 */
		function Create_Transaction($trans_obj)
		{
			if(!strlen($trans_obj->transaction_status))
			{
				$trans_obj->transaction_status = 'PENDING';	
			}
			
			switch($trans_obj->transaction_type)
			{
				case "SAT":
				$trans_obj->cc_amount = 0;
				$trans_obj->ach_amount = 0;
				$trans_obj->sat_amount -= $trans_obj->sat_amount*2; // Convert to a negative of itself
				$trans_obj->transaction_total -= $trans_obj->transaction_total*2; // Convert to a negative of itself
				$balance = 0;
				break;
				
				case "MAN PAYMENT":
				$trans_obj->cc_amount -= $trans_obj->cc_amount*2; // Convert to a negative of itself
				$trans_obj->ach_amount = 0;
				$trans_obj->sat_amount = 0;
				$trans_obj->transaction_total -= $trans_obj->transaction_total*2; // Convert to a negative of itself
				$trans_obj->transaction_type = "M-PAYMENT";
				$balance = $trans_obj->transaction_total;				
				break;
				
				case "AUTO PAYMENT":
				$trans_obj->cc_amount -= $trans_obj->cc_amount*2; // Convert to a negative of itself
				$trans_obj->ach_amount = 0;
				$trans_obj->sat_amount = 0;
				$trans_obj->transaction_total -= $trans_obj->transaction_total*2; // Convert to a negative of itself
				$trans_obj->transaction_type = "A-PAYMENT";
				$balance = $trans_obj->transaction_total;	
				break;
				
				case "FEE":
				$trans_obj->sat_amount = 0;	
				$balance = $trans_obj->ach_amount+$trans_obj->cc_amount;
				break;
				
				default:
				$trans_obj->sat_amount = 0;	
				$balance = $trans_obj->ach_amount+$trans_obj->cc_amount;
				
			}
						
			$fields = "modified_date, origination_date, cross_reference_id, cc_number, transaction_status, transaction_type, transaction_source, ";
			$fields .= "ach_total, cc_total, sat_total, transaction_total, transaction_balance, promo_id, promo_sub_code";
			$values = "NOW(),NOW(),'".$trans_obj->cross_ref_id."','".$trans_obj->cc_number."','".$trans_obj->transaction_status."','".strtoupper($trans_obj->transaction_type)."', ";
			$values .= "'".$trans_obj->transaction_source."','".$trans_obj->ach_amount."','".$trans_obj->cc_amount."', '".$trans_obj->sat_amount."', ";
			$values .= "'".$trans_obj->transaction_total."','".$balance."','".$trans_obj->promo_id."','".$trans_obj->promo_sub_code."'";
			$query = "INSERT INTO transaction_0 (".$fields.") VALUES(".$values.")";
			
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		
			$this->trans_id = $this->sql->Insert_Id();
			$this->Create_Line_Item($trans_obj);
			
			return TRUE;
		}
		
		function Create_Sat($trans_obj)
		{
			$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, line_item_status, line_item_comment, modified_date, origination_date";
			$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$trans_obj->msg."','SAT','".$trans_obj->sat_amount."','0','CREDIT', 'APPROVED', '".$trans_obj->msg."', NOW(), NOW())";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$query = "UPDATE transaction_0 SET sat_total = '".$trans_obj->sat_amount."', transaction_balance = transaction_balance+($trans_obj->sat_amount) WHERE transaction_id = '".$trans_obj->msg."'";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$query = "UPDATE transaction_line_item SET line_item_balance = line_item_balance+($trans_obj->sat_amount) WHERE rel_transaction_id = '".$trans_obj->msg."' AND line_item_type = 'CC'";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param trans_obj
		 * @desc Creates a new line item for an existing transaction
		 */
		function Create_Line_Item($trans_obj)
		{
			if(strlen($trans_obj->trans_id))
			{
				$this->trans_id = $trans_obj->trans_id;
			}
					
			switch(strtoupper($trans_obj->transaction_type))
			{
				case "ENROLLMENT":
				
				if(strlen(!$trans_obj->ach_amount)) //This was added because of the the manual transaction entry.
				{
					//$trans_obj->ach_amount = 149.00;
					$trans_obj->ach_amount = 9.95;
				}
				
				$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','ACH','".$trans_obj->ach_amount."','".$trans_obj->ach_amount."','DOWN PAYMENT',NOW(),NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				//If this is a manual entry add comments
				if(strtoupper($trans_obj->transaction_source) == 'AGENT')
				{
					$this->audit_trail->Insert_Audit($trans_obj);
				}
				break;
				
				case "ORDER":
				$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, line_item_status, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','ACH','".$trans_obj->ach_amount."','".$trans_obj->ach_amount."','DOWN PAYMENT', 'PENDING', NOW(), NOW()),('".$this->trans_id."','CC','".$trans_obj->cc_amount."','".$trans_obj->cc_amount."','CHARGE','-',NOW(),NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				//If this is a manual entry add comments
				if(strtoupper($trans_obj->transaction_source) == 'AGENT')
				{
					$this->audit_trail->Insert_Audit($trans_obj);
				}
				break;
				
				case "SAT":
				$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, line_item_status, line_item_comment, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','SAT','".$trans_obj->sat_amount."','0','CREDIT', 'APPROVED', '".$trans_obj->msg."', NOW(), NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$this->Create_Sat($trans_obj);
				
				//If this is a manual entry add comments
				if(strtoupper($trans_obj->transaction_source) == 'AGENT')
				{
					$this->audit_trail->Insert_Audit($trans_obj);
				}				
				break;
				
				case "MAN PAYMENT":
				$fields = "rel_transaction_id, line_item_type, line_item_amount,  line_item_balance, line_item_action, line_item_status, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','PAYMENT','".$trans_obj->cc_amount."','".$trans_obj->cc_amount."','PAYMENT', 'PENDING', NOW(), NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "AUTO PAYMENT":
				$fields = "rel_transaction_id, line_item_type, line_item_amount,  line_item_balance, line_item_action, line_item_status, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','PAYMENT','".$trans_obj->cc_amount."','".$trans_obj->cc_amount."','PAYMENT', 'PENDING', NOW(), NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "CREDIT":
				$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, line_item_status, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','CC','".$trans_obj->cc_amount."','".$trans_obj->cc_amount."', 'CREDIT', '-', NOW(), NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "FEE":
				$fields = "rel_transaction_id, line_item_type, line_item_amount, line_item_balance, line_item_action, modified_date, origination_date";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."','ACH','".$trans_obj->ach_amount."','".$trans_obj->ach_amount."', 'FEE', NOW(), NOW())";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "GC_AUTO":
				$query = "UPDATE `transaction_0` SET cc_total = cc_total-".$trans_obj->amount.", gc_total = gc_total+".$trans_obj->amount.", transaction_balance = transaction_balance-".$trans_obj->amount." 
				WHERE transaction_id = '".$trans_obj->rel_transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE `transaction_line_item` SET line_item_balance = line_item_balance-".$trans_obj->amount." WHERE rel_transaction_id = '".$trans_obj->rel_transaction_id."' AND line_item_type = 'CC'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$fields = "rel_transaction_id, modified_date, origination_date, line_item_type, line_item_amount, line_item_balance, line_item_action, line_item_status, line_item_comment";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."',NOW(),NOW(),'GC','-".$trans_obj->amount."','0','CREDIT','APPROVED','".$trans_obj->desc."')";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "CC REFUND":
				$fields = "rel_transaction_id, modified_date, origination_date, line_item_type, line_item_amount, line_item_balance, line_item_action";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."',NOW(),NOW(),'CC-R','".$trans_obj->cc_amount."','".$trans_obj->cc_amount."','REFUND')";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE `transaction_0` SET cc_total = cc_total+".$trans_obj->cc_amount.", transaction_total = transaction_total+".$trans_obj->cc_amount.", transaction_balance = transaction_balance+".$trans_obj->cc_amount." WHERE transaction_id = '".$this->trans_id ."''";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$this->Insert_Audit($trans_obj);
				break;
										
				case "ACH REFUND":
				$fields = "rel_transaction_id, modified_date, origination_date, line_item_type, line_item_amount, line_item_balance, line_item_action";
				$query = "INSERT INTO transaction_line_item(".$fields.") VALUES('".$this->trans_id."',NOW(),NOW(),'ACH-R','".$trans_obj->ach_amount."','".$trans_obj->ach_amount."','REFUND')";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE `transaction_0` SET ach_total = ach_total+".$trans_obj->ach_amount.", transaction_total = transaction_total+".$trans_obj->ach_amount.", transaction_balance = transaction_balance+".$trans_obj->ach_amount." WHERE transaction_id = '".$this->trans_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$this->audit_trail->Insert_Audit($trans_obj);
				break;
				
				default:
				NULL;
			}
				
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param transaction_id
		 * @param status
		 * @param reason
		 * @desc Adjust transactions and associated line items for approved or denied ach accounts
		 */
		function Ach_Update($transaction_id,$status,$reason='')
		{
			switch($status)
			{
				case "APPROVED":
				$query = "UPDATE `transaction_0` SET transaction_status = 'APPROVED', transaction_balance = transaction_balance-ach_total WHERE transaction_id = '".$transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE transaction_line_item SET line_item_status = 'APPROVED', line_item_balance = '0'  WHERE line_item_type = 'ACH' AND rel_transaction_id = '".$transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "DENIED":
				$query = "UPDATE `transaction_0` SET transaction_status = 'DENIED' WHERE transaction_id = '".$transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE transaction_line_item SET line_item_status = 'DENIED' AND line_item_comment = '".$reason."' WHERE line_item_type = 'ACH' AND rel_transaction_id = '".$transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
			}
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param transaction_id
		 * @desc Voids the transaction and all of it's associated line items 
		 */
		function Void_Transaction($transaction_id)
		{
			$query = "UPDATE `transaction_0` SET transaction_status = 'VOID', transaction_balance = '0.00' WHERE transaction_id = '".$transaction_id."'";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$from = "`transaction_line_item`,`transaction_0`";
			$select = "transaction_line_item.line_item_id,transaction_line_item.line_item_amount, transaction_line_item.line_item_comment, transaction_0.cc_number";
			$where = "transaction_line_item.rel_transaction_id = '".$transaction_id."' AND transaction_line_item.line_item_type = 'GC'";
			$where .= " AND transaction_line_item.line_item_status != 'VOID' AND transaction_line_item.rel_transaction_id = transaction_0.transaction_id";
			
			$query = "SELECT ".$select." FROM ".$from." WHERE ".$where."";
						
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$continue = 0;
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$trans = $row_data->line_item_id;
				$transaction_data->{$trans}=$row_data;
				$continue = 1;
			}
						
			if($continue == 1)
			{
				foreach($transaction_data AS $record)
				{
					$query = "UPDATE `certificates` SET balance = balance-".$record->line_item_amount." WHERE cc_number='".$record->cc_number."' AND description = '".$record->line_item_comment."' LIMIT 1";
					$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				}	
			}
			
			$query = "UPDATE `transaction_line_item` SET line_item_status = 'VOID', line_item_balance = '0.00' WHERE rel_transaction_id = '".$transaction_id."'";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param line_item_id
		 * @desc Voids a specific line item, adjusts transaction totals to match the void
		 */
		function Void_Line_Item($line_item_id,$employee)
		{	
			//Get the current information from the database
			$from = "transaction_line_item,transaction_0 ";
			$where = "transaction_line_item.line_item_id = '".$line_item_id."' AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id";
			$query = "SELECT transaction_0.cc_number, transaction_line_item.line_item_amount, transaction_line_item.line_item_id, transaction_line_item.line_item_comment, ";
			$query .= "transaction_line_item.rel_transaction_id,transaction_line_item.line_item_type FROM ".$from." WHERE ".$where."";
								
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$info = $this->sql->Fetch_Object_Row($result);
			
			//Update the line item to void and set appropriate balance
			$where = "line_item_id = '".$line_item_id."'";
			$query = "UPDATE transaction_line_item SET line_item_status = 'VOID', line_item_balance = '0.00' WHERE ".$where."";
			
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$trans_obj = new stdClass();
			$trans_obj->trans_id = $info->line_item_id;
			$trans_obj->transaction_type = 'LINE VOID';
			$trans_obj->employee = $employee;
			$trans_obj->cc_number = $info->cc_number;
			
			//Add a comment for this void
			$this->audit_trail->Insert_Audit($trans_obj);
			
			switch($info->line_item_type)
			{	
				case "CC-R": // added this to capture refunds
				case "CC":
				$query = "UPDATE `transaction_0` SET cc_total = cc_total-".$info->line_item_amount.", transaction_total = transaction_total-".$info->line_item_amount.", transaction_balance = transaction_balance-".$info->line_item_amount." WHERE transaction_id = '".$info->rel_transaction_id."' AND transaction_status != 'VOID'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "ACH-R": // added this to capture refunds
				case "ACH":
				$query = "UPDATE `transaction_0` SET ach_total = ach_total-".$info->line_item_amount.", transaction_total = transaction_total-".$info->line_item_amount.", transaction_balance = transaction_balance-".$info->line_item_amount." WHERE transaction_id = '".$info->rel_transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "MAN PAYMENT":
				$query = "UPDATE `transaction_0` SET transaction_total = transaction_total-".$info->line_item_amount.", transaction_balance = transaction_balance-".$info->line_item_amount." WHERE transaction_id = '".$info->rel_transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "AUTO PAYMENT":
				$query = "UPDATE `transaction_0` SET transaction_total = transaction_total-".$info->line_item_amount.", transaction_balance = transaction_balance-".$info->line_item_amount." WHERE transaction_id = '".$info->rel_transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				case "GC":
				$reverse = $info->line_item_amount-$info->line_item_amount*2;
				
				$query = "UPDATE `transaction_0` SET transaction_balance = transaction_balance-".$info->line_item_amount.", gc_total = gc_total+".$info->line_item_amount.", cc_total= cc_total-".$info->line_item_amount." WHERE transaction_id = '".$info->rel_transaction_id."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE `certificates` SET balance = balance-".$info->line_item_amount." WHERE cc_number='".$info->cc_number."' AND description='".$info->line_item_comment."' LIMIT 1";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$query = "UPDATE `transaction_line_item` SET line_item_balance = line_item_balance-".$info->line_item_amount." WHERE rel_transaction_id = '$info->rel_transaction_id' AND line_item_type = 'CC'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				break;
				
				default:
				NULL;
			}	
					
			return TRUE;
		}
		
		/**
		 * @return rounded value
		 * @param value
		 * @desc Returns the rounded value of a number in three decimal
		 */
		function Rounder($value)
		{
			$chunk_value = explode('.',$value);
			
			if(strlen($chunk_value[0]) > 2 || substr($chunk_value[0],3,1 == 5))
			{
				
				return substr($value,0,-1)+0.01;	
			}
			else
			{
				return round($value,2);	
			}
		}
		
	
		/**
		 * @return boolean
		 * @param cc_number
		 * @desc Returns session variable with available_credit
		 */
		function Get_Available_Credit($cc_number,$output=null)
		{
			if($output == 1)
			{
				
				$list = "'VOID','DENIED','HOLD'";

				$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.") AND ";
				$where .= "transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND ";
				$where .= "transaction_line_item.line_item_type = 'CC' AND transaction_line_item.line_item_status NOT IN (".$list.")";
				$query = "SELECT SUM(transaction_line_item.line_item_balance) AS credit_total FROM transaction_line_item,transaction_0 WHERE ".$where."";
				//echo $query."\n\n";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$available_credit = $this->sql->Fetch_Object_Row($result);
				
				if($available_credit->credit_total > 7500)
				{
					$available_credit= 7500;
				}
				else
				{
					$available_credit = 7500 - $this->Rounder($available_credit->credit_total);
				}
				//echo $available_credit."\n\n";
				
				return number_format($available_credit, 2, '.', ',');
			}
			else
			{
				if(!isset($_SESSION['available_credit']))
				{
					$list = "'VOID','DENIED','HOLD'";
	
					$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.") AND ";
					$where .= "transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND ";
					$where .= "transaction_line_item.line_item_type = 'CC' AND transaction_line_item.line_item_status NOT IN (".$list.")";
					$query = "SELECT SUM(transaction_line_item.line_item_balance) AS credit_total FROM transaction_line_item,transaction_0 WHERE ".$where."";
					
					$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
					$available_credit = $this->sql->Fetch_Object_Row($result);
					
					if($available_credit->credit_total > 7500)
					{
						$available_credit= 7500;
					}
					else
					{
						$available_credit = 7500 - $available_credit->credit_total;
					}
	
					$_SESSION['available_credit'] = number_format($available_credit, 2, '.', ',');
					
					return TRUE;
				}
			}
		}
		
		/**
		 * @return billing_balance
		 * @param cc_number
		 * @desc Returns billable balance used by bill_master
		 */
		function Get_Billing_Balance($cc_number)
		{
			$list = "'VOID','DENIED','HOLD'";
			$type_list = "'FEE'";
				
			$where = "cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.") AND transaction_type NOT IN (".$type_list.")";
			$query = "SELECT SUM(transaction_balance) AS balance FROM transaction_0 WHERE ".$where."";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$billing_balance = $this->sql->Fetch_Object_Row($result);
				
			return $billing_balance->balance;	
		}
		
		/**
		 * @return billing_balance
		 * @param cc_number
		 * @desc Returns billable balance used by bill_master
		 */
		function Get_Billing_Fee($cc_number)
		{
			$list = "'VOID','DENIED','HOLD'";
			$type_list = "'FEE'";
				
			$where = "cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.") AND transaction_type IN (".$type_list.")";
			$query = "SELECT SUM(transaction_balance) AS balance FROM transaction_0 WHERE ".$where."";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$billing_balance = $this->sql->Fetch_Object_Row($result);
				
			return $billing_balance->balance;	
		}
		
		
		/**
		 * @return boolean
		 * @param cc_number
		 * @desc Returns session variable with account_balance
		 */
		function Get_Account_Balance($cc_number,$output=null)
		{
			if($output == '1')
			{
				$list = "'VOID','DENIED','HOLD'";
				
				$where = "cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.")";
				$query = "SELECT ROUND(SUM(transaction_balance),2) AS balance FROM transaction_0 WHERE ".$where."";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$account_balance = $this->sql->Fetch_Object_Row($result);
				
				return $account_balance->balance;	
			}
			
			if(!isset($_SESSION['account_balance']))
			{
				$list = "'VOID','DENIED','HOLD'";
				
				$where = "cc_number = '".$cc_number."' AND transaction_status NOT IN (".$list.")";
				$query = "SELECT ROUND(SUM(transaction_balance),2) AS balance FROM transaction_0 WHERE ".$where."";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$account_balance = $this->sql->Fetch_Object_Row($result);
				
				$_SESSION['account_balance'] = number_format($account_balance->balance, 2, '.', ',');
				
				return TRUE;	
			}
		}
		
		/**
		 * @return boolean
		 * @param cc_number
		 * @desc Returns session variable with account_balance
		 */
		function Get_Credit_Balance($cc_number,$output=null)
		{
			if($output == '1')
			{
				$list = "'VOID','DENIED','HOLD'";
				
				$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_0.transaction_status NOT IN (".$list.") AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND transaction_line_item.line_item_type IN('CC','CC-R','PAYMENT','GC','SAT')";
				$query = "SELECT ROUND(SUM(transaction_line_item.line_item_balance),2) AS balance FROM `transaction_0`,`transaction_line_item` WHERE ".$where."";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$charge_balance = $this->sql->Fetch_Object_Row($result);
				
				$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_0.transaction_status NOT IN (".$list.") AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND transaction_line_item.line_item_action = 'FEE'";
				$query = "SELECT ROUND(SUM(transaction_line_item.line_item_balance),2) AS fee_balance FROM `transaction_0`,`transaction_line_item` WHERE ".$where."";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$fee_balance = $this->sql->Fetch_Object_Row($result);
		
				$credit_balance->balance = $fee_balance->fee_balance+$charge_balance->balance;
							
				return number_format($credit_balance->balance,2,'.',',');	
			}
			else 
			{
				if(!isset($_SESSION['credit_balance']))
				{
					$list = "'VOID','DENIED','HOLD'";
					
					$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_0.transaction_status NOT IN (".$list.") AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id";
					$query = "SELECT ROUND(SUM(line_item_balance),2) AS balance FROM `transaction_0`,`transaction_line_item` WHERE ".$where."";
					$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
					$credit_balance = $this->sql->Fetch_Object_Row($result);
					
					$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_0.transaction_status NOT IN (".$list.") AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND transaction_line_item.line_item_action = 'FEE'";
					$query = "SELECT ROUND(SUM(transaction_line_item.line_item_balance),2) AS fee_balance FROM `transaction_0`,`transaction_line_item` WHERE ".$where."";
					$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
					$fee_balance = $this->sql->Fetch_Object_Row($result);
				
					$credit_balance->balance = $fee_balance->fee_balance+$charge_balance->balance;
					
					$_SESSION['credit_balance'] = number_format($credit_balance->balance, 2, '.', ',');
					
					return TRUE;	
				}
			}
		}
					
		/**
		 * @return boolean
		 * @param cc_number, &transaction_data
		 * @desc Builds the transaction tab and balance values.
		 */
		function Get_Transaction_Data($cc_number, &$transaction_data)
		{
			// Holds combine total amount
			$transaction_data = new stdClass();
			// Holds just transaction data
			$trans_data = new stdClass();
			// Holds just line item data
			$line_data = new stdClass();
			
			// Build trans_data
			$query = "SELECT *, DATE_FORMAT(origination_date, '%m-%d-%y') AS format_date FROM transaction_0 WHERE cc_number = '".$cc_number."' ORDER BY origination_date ASC";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$trans = $row_data->transaction_id;
				$transaction_data->{$trans}=$row_data;
			}
			
			// Build line_data
			$where = "transaction_0.cc_number = '".$cc_number."' AND transaction_0.transaction_id = transaction_line_item.rel_transaction_id";
			$from = "transaction_line_item, transaction_0";
			$query = "SELECT transaction_line_item.*, DATE_FORMAT(transaction_line_item.origination_date, '%m-%d-%y') AS format_date FROM ".$from." WHERE ".$where." ORDER BY line_item_id ASC";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$line_data->{$row_data->line_item_id} = $row_data;
			}
			
			foreach($line_data as $line_item)
			{
				$trans = $line_item->rel_transaction_id;
				$line = $line_item->line_item_id;
				$transaction_data->$trans->$line= $line_item;
			}
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param cc_number
		 * @desc Upon entry of a new certificate this function auto balances the amount owed of current transactions.
		 */
		function _Adjust_Trans($cc_number,$amount, $cert_id, $desc)
		{
			$from_clause = "`transaction_line_item`,`transaction_0`";
			$where_clause = "transaction_line_item.line_item_type = 'CC' AND 
			transaction_line_item.line_item_action = 'CHARGE' AND 
			transaction_line_item.line_item_balance > 0 AND transaction_line_item.line_item_status = '-' AND 
			transaction_0.transaction_id = transaction_line_item.rel_transaction_id AND transaction_0.cc_number = '".$cc_number."' AND 
			transaction_0.transaction_status IN('PENDING','APPROVED','SENT')";
			
			$query = "SELECT transaction_line_item.* FROM ".$from_clause." WHERE ".$where_clause."";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$loop_count_1 = FALSE;
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$trans_data->{$row_data->line_item_id} = $row_data;
				
				$loop_count_1= TRUE;
			}
						
			// A 'break' inside of a foreach loop and/or within an if statement inside of a foreach  will bring you out of the foreach loop 
			if($loop_count_1)
			{
				foreach($trans_data AS $record)
				{
					if($record->line_item_balance>$amount) // If the line item balance is greater than the certificate amount
					{
						$trans_obj = new stdClass();
						
						$trans_obj->transaction_type = 'GC_AUTO';				
						$trans_obj->amount = $amount;
						$trans_obj->cc_amount = $amount;
						$trans_obj->cc_number = $cc_number;
						$trans_obj->trans_id = $record->rel_transaction_id;
						$trans_obj->rel_transaction_id = $record->rel_transaction_id;
						$trans_obj->desc = $desc;
						
						$this->Create_Line_Item($trans_obj);
						
						$query = "UPDATE `certificates` SET balance = balance-".$amount." WHERE id='".$cert_id."'";
						$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));					
						
						break;
					}
					else
					{
						$trans_obj = new stdClass();
						
						$trans_obj->transaction_type = 'GC_AUTO';				
						$trans_obj->amount = $record->line_item_balance;
						$trans_obj->cc_amount = $record->line_item_balance;
						$trans_obj->cc_number = $cc_number;
						$trans_obj->trans_id = $record->rel_transaction_id;
						$trans_obj->rel_transaction_id = $record->rel_transaction_id;
						
						$this->Create_Line_Item($trans_obj);
						
						$query = "UPDATE `certificates` SET balance = balance-".$record->line_item_balance." WHERE id='".$cert_id."'";
						$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
						
						$amount -= $record->line_item_balance;	
					}
				}
			}
			else
			{
				NULL;	
			}
			
		}
		
		/**
		 * @return boolean
		 * @param certificate_obj
		 * @desc Add a new gift certificate to the database.
		 */
		function Apply_Certificate($certificate_obj)
		{
			$certificate_obj->transaction_type = 'GC_AUTO';				
			$this->Create_Line_Item($certificate_obj);
			
			$query = "UPDATE `certificates` SET balance = balance-$certificate_obj->amount WHERE cc_number = '".$certificate_obj->cc_number."' AND balance >= $certificate_obj->amount";
			
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param certificate_obj
		 * @desc Add a new gift certificate to the database.
		 */
		function Add_Certificate($certificate_obj)
		{
			$query = "INSERT INTO `certificates` (cc_number,amount,balance,description,modified_date,origination_date) VALUES('".$certificate_obj->cc_number."','".$certificate_obj->amount."','".$certificate_obj->amount."','".$certificate_obj->desc."',NOW(),NOW())";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$cert_id = $this->sql->Insert_Id($result);
			
			$this->_Adjust_Trans($certificate_obj->cc_number,$certificate_obj->amount,$cert_id, $certificate_obj->desc);
						
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param cc_number
		 * @param certificate_html
		 * @desc return the html for the certificate data page
		 */
		function Get_Certificates($cc_number, &$certificate_html)
		{
			$query = "SELECT *, DATE_FORMAT(origination_date, '%m-%d-%Y') AS origination_date FROM `certificates` WHERE cc_number = '".$cc_number."'";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$loop_count_2 = FALSE;
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$certificate_data->{$row_data->id} = $row_data;
				
				$loop_count_2= TRUE;
			}
			
			if($loop_count_2)
			{
				foreach($certificate_data AS $record)
				{
					if($bg == "")
					{
						$bg = "bgcolor=#E7E7E7";	
					}
					else 
					{
						$bg = "";	
					}
					
					
					$certificate_html .= "
					<tr class=\"mainsm\">
					<td ".$bg.">".$record->origination_date."</td>
					<td ".$bg.">$".number_format($record->amount, 2, '.', ',')."</td>
					<td ".$bg.">$".number_format($record->balance, 2, '.', ',')."</td>
					<td ".$bg.">$record->description</td>
					</tr>";
				}
			}
			else
			{
				$certificate_html = "<tr class=\"mainsm\"><td colspan=\"4\" align=\"center\" bgcolor=\"#E7E7E7\">There are no certificates for this account.</td></tr>";	
			}
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param cc_number
		 * @desc sets the certificate balance in a session variable
		 */
		function Get_Certificate_Balance($cc_number, $return_val=NULL)
		{
			if(!isset($_SESSION['certificate_balance']))
			{			
				$query = "SELECT SUM(balance) AS balance FROM `certificates` WHERE cc_number = '".$cc_number."'";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$certificate_balance = $this->sql->Fetch_Object_Row ($result);
			}
			
			if(strlen($return_val))
			{
				return $certificate_balance;
			}
			else
			{
				$_SESSION['certificate_balance'] = number_format($certificate_balance->balance, 2, '.', ',');
				return TRUE;	
			}
		}
		/**
		 * @return object
		 * @param cc_number
		 * @desc returns and object of certificate name and available amount
		 */
		function Get_Certificate_Object($cc_number)
		{
			$query = "SELECT * FROM `certificates` WHERE cc_number = '".$cc_number."' AND balance != 0 LIMIT 1";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$cert_data = $this->sql->Fetch_Object_Row ($result);
						
			return $cert_data;
		}
	}
?>
