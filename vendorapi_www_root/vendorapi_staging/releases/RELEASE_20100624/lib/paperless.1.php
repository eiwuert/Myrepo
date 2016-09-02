<?php
/*********************
	THIS WAS MOVED TO ECASH 6/22/04 SO IF YOU USE THIS CODE LET KANSAS KNOW
	NOTE PUT HERE 06/22/04 - SW
**********************/
	/*
		DDL Requirements:
			schema: company
			table: transaction
			column                  type        default
			is_paperless            boolean     0
			date_paperless_printed  timestamp   NULL
			date_paperless_purged   timestamp   NULL
	*/
	require_once ("/virtualhosts/lib/db2_table_names.php");
	
	class Paperless_1
	{
		function Paperless_1 ($db2_object)
		{
			$this->db2 = $db2_object;
			/*
				if we limit the amount of recs show should we not also add functionality
				to let them view the rest?? Right now they have a print #recs option, they 
				can clear the printed que to see the rest of the records. Maybe when we move
				to flash we should impliment the previous/next functionality for the list display
			*/
			$this->max_rows = 100;	// How many rows to return if limited
			return TRUE;
		}
		
		function Get_Queue ()
		{
			// retreive paperless queue where status not purged and paperless
			$query = "
				SELECT
					count (*) as total
				FROM
					".COMPANY_TRANSACTION." trans
				WHERE
					trans.is_paperless = 1
					AND trans.date_paperless_purged IS NULL
			";
			
			$result = $this->db2->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			$tmp_total = $result->Fetch_Object ();
			$ret_val->found_count = $tmp_total->TOTAL;		// Set the number of records in queue
			
			// Set the number of records that will be displayed
			$result = $this->_Get_Transaction (NULL, TRUE, FALSE, $this->max_rows);
			Error_2::Error_Test ($result, TRUE);
			$ret_val->display_count = $this->Select_Num_Rows ($result);
		
			// if there are any rows returned, prepare the paperless queue table row return
			if ($ret_val->display_count)
			{
				//used to help with control of counts when printing from a condtioned rec display
				//so when display 50 of 100 and have printed 5 but enter to print next 500 'NEW' the 
				//already printed 5 need to be calculated in for the query limit
				$ret_val->printed_list_cnt = 0;
				while(FALSE !== ($temp = $result->Fetch_Object ()))
				{
					//  prepare status icon
						if (is_null ($temp->DATE_PRINTED))
						{
							$printed_img =  "<b><font color=green> New</font></b>";
						}
						else 
						{
							$printed_img = "<img src='./img/ico_check.gif' border='0' target='_blank'>" ;
							 ++$ret_val->printed_list_cnt; 
						}
					
					$name = trim($temp->NAME_LAST).", ".trim($temp->NAME_FIRST)." ".trim($temp->NAME_MIDDLE);
					$ssn =
						substr($temp->SOCIAL_SECURITY_NUMBER,0,3)
						."-".substr($temp->SOCIAL_SECURITY_NUMBER,3,2)
						."-".substr($temp->SOCIAL_SECURITY_NUMBER,5,4);
						
					$print_link = "
						<img 
							src='./img/ico_transaction.gif' 
							border='0' 
							onClick=\"Javascript: doPrint('&print=1&id=".$temp->TRANSACTION_ID."&printed=TRUE')\"; 
						>
						Print";
						
					//  prepare 
					$ret_val->html .= "
						<tr class=mainxs>
							<td width=10%>".$temp->TRANSACTION_ID."</td>
							<td width=15%>".$temp->DATE_CREATED."</td>
							<td width=30%>".$name."</td>
							<td width=12%>".$ssn."</td>
							<td width=10% align=center>".$print_link."</td>
							<td width=10% align=center>".$printed_img."</td>
							
						</tr>";
				}
			}
			// if there were no rows returned from the query, prepare the no results response
			else
			{
				$ret_val->html = "
				<tr>
					<td colspan=6 class=mainxs align=center>  There are currently no pending paperless applications. </td>
				</tr>
				";	
			}

			return $ret_val;
		}
		
		function Print_Application ($transaction_id = NULL, $printed = FALSE, $pr_rows=0)
		{
			// Grab all the transactions that should be printed
			$result = $this->_Get_Transaction ($transaction_id, $printed, FALSE, $pr_rows);
			
			Error_2::Error_Test ($result, TRUE);
			$ret_val->print_total = $this->Select_Num_Rows ($result);
			while(FALSE !== ($temp = $result->Fetch_Object ()))
			{
				// add queue val to array
				$ret_val->data[] = $temp;
				
				//  update printed status
				$this->_Set_Date ($temp->TRANSACTION_ID, "date_paperless_printed");
			}		
			return $ret_val;
		}
		
		
			
		function Purge ($transaction_id = NULL)
		{
			$trans_id = "";
			
			// retreive paperless queue count where not purged and printed
			if (!is_null ($transaction_id))
			{
				$trans_id = "AND trans.transaction_id = ".$transaction_id;
			}
			
			$query = "
				SELECT
					trans.transaction_id
				FROM
					".COMPANY_TRANSACTION." trans
				WHERE
					trans.is_paperless = 1
					".$trans_id."
					AND trans.date_paperless_purged IS NULL
					AND trans.date_paperless_printed IS NOT NULL";
			$result = $this->db2->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			
			while (FALSE !== ($temp = $result->Fetch_Object ()))
			{
				$this->_Set_Date ($temp->TRANSACTION_ID, "date_paperless_purged");
			}
			
			$ret_val->html = "<b>".$this->Select_Num_Rows ($result)." printed application(s) were successfully removed from the queue.</b>";
			return $ret_val;
		}
		
		function _Set_Date ($transaction_id, $status_name)
		{
			$query = "
				UPDATE
					".COMPANY_TRANSACTION."
				SET
					".$status_name." = CURRENT TIMESTAMP
				WHERE
					transaction_id = ".$transaction_id;
			
			$result = $this->db2->Execute ($query);
			Error_2::Error_Test ($result, TRUE);
			
			return TRUE;
		}
		
		/*
			Parameter Definition:
				transaction_id: If passed will limit to only the transaction_id requested, otherwise all transaction_ids
				with_printed: If TRUE will return transactions that have been printed before
				with_purged: If TRUE will return transactions that have been purged
				num_rows: If 0, return all rows, otherwise only return the number of rows requested
		*/
		function _Get_Transaction ($transaction_id = NULL, $with_printed = TRUE, $with_purged = FALSE, $num_rows = 0)
		{
			// Set some defaults
			$trans_id = "";
			$printed = "";
			$purged = "";
			$row_limit = "";
			
			// Check passed in conditions 
			if (strlen ($transaction_id))
			{
				$trans_id = "AND trans.transaction_id = ".$transaction_id;
			}
			
			if (!$with_printed)
			{
				$printed = "AND trans.date_paperless_printed IS NULL";
			}
			
			if ($with_purged)
			{
				$purged = "AND trans.date_paperless_purged IS NOT NULL";
			}
			else
			{
				$purged = "AND trans.date_paperless_purged IS NULL";
			}
			
			if ($num_rows > 0)
			{
				$row_limit = "FETCH FIRST ".$num_rows." ROWS ONLY";
			}
			
			// Build the query
			$query = "
				SELECT
					cust.name_first,
					cust.name_middle,
					cust.name_last,
					cust.social_security_number,
					DATE (trans.date_created) as date_created,
					DATE (trans.date_paperless_printed) as date_printed,
					DATE (trans.date_paperless_purged) as date_purged,
					trans.transaction_id
				FROM
					".COMPANY_TRANSACTION." trans,
					".CUSTOMER_CUSTOMER." cust
				WHERE
					trans.is_paperless = 1
					".$trans_id."
					".$purged."
					".$printed."
					AND cust.customer_id = trans.customer_id
				ORDER BY 
					trans.date_created, trans.transaction_id ASC
				".$row_limit;
			// Run the query and return results
			return $this->db2->Execute ($query);
		}
		
		function Select_Num_Rows ($passed_result)
		{
			if (!empty ($passed_result->query))
			{
				$query = "
					SELECT
						count (*) AS num_rows
					FROM
						(" . $passed_result->query . ") AS res
				";
				$result = $this->db2->Execute ($query);
				Error_2::Error_Test ($result, TRUE);
				$row = $result->Fetch_Array ();
				$num = $row["NUM_ROWS"];
			}
			else
			{
				$num = -1;	
			}
			
			return $num;
		}
	}
?>
