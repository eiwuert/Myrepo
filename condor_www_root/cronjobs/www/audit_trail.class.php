<?PHP
class Audit_Trail
	{
		var $sql;
		var $database;

		/**
		 * @return boolean
		 * @param sql
		 * @param database
		 * @desc Instantiate Audit_Trail class
		 */
		function Audit_Trail($sql,$database)
		{
			$this->sql = $sql;
			$this->database = $database;
			$this->date = date("m-d-Y");
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param $sec_id
		 * @convert security id to a name
		 */
		function Sec_Info($sec_id)
		{
			$query = "SELECT full_name FROM `employee` WHERE employee_id = '".$sec_id."'";
			$result = $this->sql->Query('egc_agent', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$sec_data = $this->sql->Fetch_Object_Row ($result);
			
			return $sec_data->full_name;
		}
		
		/**
		 * @return boolean
		 * @param $trans_obj
		 * @desc Insert Into Audit Table
		 */
		function Insert_Comment($comment_obj)
		{
			$fields = "cc_number, modified_date, created_date, created_time, employee_id, comment, follow_up_date";
			$values = "'".$comment_obj->cc_number."', NOW(), '".date('Y-m-d')."', '".date('H:i:s')."', '".$comment_obj->employee."', ";
			$values .= "'".$comment_obj->comment."', '".$comment_obj->follow_up."'";
			$query = "INSERT INTO comments (".$fields.") VALUES(".$values.")";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			return TRUE;
		}
		
		function Update_Comment($comment_obj)
		{
			$query = "UPDATE comments SET comment='".$comment_obj->comment."', follow_up_date = '".$comment_obj->follow_up."', employee_id = '".$comment_obj->security."' WHERE comment_id = '".$comment_obj->comment_id."'";	
			
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			return TRUE;
		}
		
		/**
		 * @return boolean
		 * @param $trans_obj
		 * @desc Insert Into Audit Table
		 */
		function Insert_Audit($trans_obj)
		{
			switch(strtoupper($trans_obj->transaction_type))
			{
				case "ORDER":
				$comment = "[".strtoupper($trans_obj->transaction_type)."][".$this->trans_id."]: Order transaction entered manually.";
				break;
				
				case "ENROLLMENT":
				$comment = "[".strtoupper($trans_obj->transaction_type)."][".$this->trans_id."]: Enrollment transaction entered manually."; 
				break;
				
				case "CC REFUND":
				$comment = "[".$trans_obj->transaction_type."][".$trans_obj->trans_id."]: Refund request entered for $".$trans_obj->ach_amount."." ;
				break;
				
				case "ACH REFUND":
				$comment = "[".$trans_obj->transaction_type."][".$trans_obj->trans_id."]: Refund request entered for $".$trans_obj->ach_amount."." ;
				break;
				
				case "LINE VOID":
				$comment = "[".$trans_obj->transaction_type."][".$trans_obj->trans_id."]: Line item has been voided";
				break;
				
				case "GC":
				$comment = "[".$trans_obj->transaction_type."][".$trans_obj->trans_id."]: Gift certificate applied manually";
				break;
				
				default:
				NULL;
			}
			
			$fields = "cc_number, modified_date, origination_date, employee_id, action_description, transaction_id";
			$values = "'".$trans_obj->cc_number."', NOW(), NOW(), '".$trans_obj->employee."', '".$comment."', '".$trans_obj->transaction_id."'";
			$query = "INSERT INTO audit_trail (".$fields.") VALUES(".$values.")";
			$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			return TRUE;
		}
		
		function Get_Comments($cc_number)
		{
			$comment_html = new stdClass();
			
			
			$query = "SELECT *, DATE_FORMAT(created_date, '%m-%d-%y') AS created_date, DATE_FORMAT(follow_up_date, '%m-%d-%y') AS follow_up_date FROM comments WHERE cc_number = '".$cc_number."' ORDER BY comment_id DESC LIMIT 20";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
			{
				$trans = $row_data->comment_id;
				$comment_data->{$trans}=$row_data;
			}
			
			
			
			if($comment_data)
			{
					foreach($comment_data AS $comment=>$record)
					{
						$record_count++;
						if ($colorcount == 0)
						{
							$bgcolor = "";
							$colorcount++;
						}
						else
						{
							$bgcolor = " bgcolor=\"#E7E7E7\"";
							$colorcount--;
						}
						
						if($record->follow_up_date == "00-00-00")
						{
							$record->follow_up_date = "N/A";	
						}
						
						$comment_len = strlen($record->comment);
						$record->comment_small = $record->comment;
								
						if(trim($comment_len) > 82)
						{
							$len = $comment_len-82;
							$record->comment = trim(substr($record->comment, 0, -$len));
							$record->comment .= "...&nbsp; <img src=\"".IMAGE_URL."arrow.gif\">";	
						}
						
						if(trim($comment_len) >43)
						{
							$len = $comment_len-43;
							$record->comment_small = trim(substr($record->comment_small, 0, -$len));
							$record->comment_small .= "...&nbsp; <img src=\"".IMAGE_URL."arrow.gif\">";
						}
						
						$comment_html->full_html .= "
						<tr class=\"mainsm\">
						<td width=\"13%\" ".$bgcolor.">".$record->created_date."</td>
						<td width=\"14%\"".$bgcolor.">".$record->follow_up_date."</td>
						<td width=\"50%\"".$bgcolor.">".$record->comment."</td>
						<td width=\"15%\"".$bgcolor." align=\"center\"><a href=\"javascript:Open_Comment(".$record->comment_id.",'VIEW');\"><img src=\"".IMAGE_URL."ico_ppl_orange_check.gif\" onMouseover=\"show_text(18,'div2')\" onMouseout=\"reset_div('div2')\" border=\"0\"></a>&nbsp;&nbsp;<a href=\"javascript:Open_Comment(".$record->comment_id.",'EDIT');\"><img src=\"".IMAGE_URL."ico_ppl_orange_x.gif\" onMouseover=\"show_text(19,'div2')\" onMouseout=\"reset_div('div2')\" border=\"0\"></a></td>
						</tr>";	
						
						if($record_count<15)
						{
							if ($colorcount2 == 0)
							{
								$bgcolor = "";
								$bgcolor = " bgcolor=\"#E7E7E7\"";
								$colorcount2++;
							}
							else
							{
								$bgcolor = "";
								$colorcount2--;
							}
							
							$comment_html->limit_html .= "
							<tr class=\"mainsm\">
							<td ".$bgcolor." width=\"5%\">".$record->created_date."</td>
							<td ".$bgcolor." width=\"90%\">".$record->comment_small."</td>
							</tr>
							";
						}
					}
			}
			else 
			{
				$comment_html->full_html .= "
				<tr class=\"mainsm\">
				<td colspan=\"4\" align=\"center\">There are no comments to display for this account.</td>
				</tr>";	
				
				$comment_html->limit_html .= "
				<tr class=\"mainsm\">
				<td colspan=\"2\" align=\"center\">There are no comments to display.</td>
				</tr>";
				
			}
			return $comment_html;
		}
		
		/**
		 * @return comment_info
		 * @param $id
		 * @returns an object of the comment information
		 */
		function Get_Comment_Record($id)
		{
			$query = "SELECT *, DATE_FORMAT(created_date, '%m-%d-%y') AS created_date, DATE_FORMAT(follow_up_date, '%m-%d-%y') AS follow_up_date FROM comments WHERE comment_id = '".$_REQUEST['id']."'";
			$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$comment_info = $this->sql->Fetch_Object_Row ($result);
			
			if($comment_info->follow_up_date == "00-00-00")
			{
				$comment_info->follow_up_date = "N/A";	
			}
			
			return $comment_info;
		}
	}
?>