<?php

class Condor_Admin
{
	public function Condor_Admin($sql)
	{

		$this->sql = $sql;
		//echo "<pre>"; print_r($this->sql);die;
		$this->request = $_REQUEST;
		
		$this->color1 = '#CAFFDF';
		$this->color2 = '#EEEEEE';
		$this->highlight = '#F1A713';
		
		switch($_REQUEST['show'])
		{
			case "legal_document":
			$this->Show_Legal_Doc();
			break;
			
			case "edit_doc":
			$this->Process_Doc();
			break;
			
			case "daily_report":
			$this->Show_Forms('date');
			break;
			
			case "date_search":
			$this->Show_Daily();
			break;
			
			case "date_report":
			$this->Show_Date_Report();
			break;
			
			case "detail":
			$this->Show_Detail();
			break;
			
			case "transaction":
			$this->Show_Transaction();
			break;
			
			case "audit_trail":
			$this->Show_Forms('audit');
			break;
			
			case "audit_search":
			$this->Gen_Audit_Report();
			break;
			
			case "audit_check":
			$this->Audit_Report_List();
			break;
			
			default:
			Condor_Admin::Show_Menu();
			break;
		}
	}
	
	private function Check_Insert()
	{
		$query = "SELECT COUNT(*) AS COUNT 
				FROM 
					LEGAL_DOCUMENT 
				WHERE
					LEGAL_DOCUMENT_NAME = '".strtolower($this->request[legal_document_name])."'
				AND
					PROPERTY_SHORT = '".strtoupper($this->request[property_short])."'
				FOR READ ONLY";
		$result = $this->db2->Execute($query);
		$data = $result->Fetch_Array();
		return $data[COUNT]>0? FALSE : TRUE;
	}
	
	private function Process_Doc()
	{
		switch($this->request[type])
		{
			case "insert_doc":
			$query = "SELECT 
						LEGAL_DOCUMENT_NAME,
						PROPERTY_SHORT,
						DOCUMENT_PATH,
						DOCUMENT_NAME
					FROM 
						LEGAL_DOCUMENT 
					ORDER BY DATE_MODIFIED DESC 
					FETCH FIRST 1 ROWS ONLY";
			$result = $this->db2->Execute($query);
			$this->Set_Page('header','ADD NEW Document');
			echo "<form method='post'>\n";
			echo "<input type=hidden name='page' value='admin'>\n";
			echo "<input type=hidden name='show' value='edit_doc'>\n";
			echo "<input type=hidden name='type' value='insert_new'>\n";
			$this->Gen_Table($result,'gen_form');
			$this->Set_Page('footer');
			break;
			
			case "insert_new":
			if($this->Check_Insert() === FALSE)
			{
				echo "document already exists!";
				return;
			}
			$query = "INSERT INTO LEGAL_DOCUMENT (
						LEGAL_DOCUMENT_NAME,
						PROPERTY_SHORT,
						DOCUMENT_PATH,
						DOCUMENT_NAME,
						DATE_CREATED,
						DATE_MODIFIED ) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)";
			$prepare = $this->db2->Query($query);
			$result = $prepare->Execute($this->request[legal_document_name],
										strtoupper($this->request[property_short]),
										$this->request[document_path],
										$this->request[document_name]);			
			if($this->db2->Insert_Id()>0)
				$this->Show_Legal_Doc();
			else 
				echo "insert failed";
			break;
			
			case "edit_info":
			$query = "SELECT
						LEGAL_DOCUMENT_NAME,
						PROPERTY_SHORT,
						DOCUMENT_PATH,
						DOCUMENT_NAME
					FROM
						LEGAL_DOCUMENT
					WHERE
						LEGAL_DOCUMENT_ID={$this->request[legal_doc_id]}
					FOR READ ONLY";
			$result = $this->db2->Execute($query);
			$this->Set_Page('header',"Edit Legal Document info of # {$this->request[legal_doc_id]}");
			echo "<form method=post>\n";
			echo "<input type=hidden name='legal_document_id' value='{$this->request[legal_doc_id]}'>\n";
			echo "<input type=hidden name='show' value='edit_doc'>\n";
			echo "<input type=hidden name='type' value='update_info'>\n";
			$this->Gen_Table($result,'gen_form');
			$this->Set_Page('footer');
			break;
			
			case "update_info":
			$query = "UPDATE
						LEGAL_DOCUMENT
					SET
						LEGAL_DOCUMENT_NAME = '{$this->request[legal_document_name]}',
						PROPERTY_SHORT = '".strtoupper($this->request[property_short])."',
						DOCUMENT_PATH = '{$this->request[document_path]}',
						DOCUMENT_NAME = '".strtolower($this->request[document_name])."'
					WHERE
						LEGAL_DOCUMENT_ID = {$this->request[legal_document_id]}
					";
			$this->db2->Execute($query);
			$this->Show_Legal_Doc();
			break;
		}
	}
	
	private function Show_Legal_Doc()
	{
		$this->Set_Page('header',"Condor Legal Document Lists");
		switch($this->request['type'])
		{
			case "per_site":
				$query = "SELECT 
							LEGAL_DOCUMENT_ID,
							LEGAL_DOCUMENT_NAME,
							SITE_ID,
							POSITION,
							PROPERTY_SHORT,
							DOCUMENT_NAME,
							DATE(DATE_MODIFIED) AS DATE_MODIFIED
						FROM
							LEGAL_DOCUMENT
						WHERE
							SITE_ID = '{$this->request['site_id']}'
						ORDER BY DATE_MODIFIED DESC
						FOR READ ONLY";
			$result = $this->db2->Execute($query);
			$this->Gen_Table($result,'legal_list');
			break;
			
			default:
			$query = "SELECT * FROM LEGAL_DOCUMENT";
			$result = $this->db2->Execute($query);
			$this->Gen_Table($result,'legal_per_site');
			break;
		}
		echo "<a href='?page=admin&show=edit_doc&type=insert_doc'>Add new legal document</a>\n";
	}
	
	private function Show_Menu()
	{
		switch($this->request['show'])
		{
			default:
			$this->Set_Page('header',"Condor Admin");
			echo "<div align=left>\n";
			echo "<ul>";
			echo "<li><a href='?page=admin&show=daily_report'>Condor Reports</a>
					<br>Show history/list of legal document requests\n";
			echo "<li><a href='?page=admin&show=legal_document'>Add/Edit Legal Document</a>??\n";
			echo "<br>Edit/Add Legal Document\n";
			echo "</ul>\n";
			echo "</div>\n";
			$this->Set_Page('footer');
			break;
		}
	}
	
	private function Show_Forms($type)
	{
		switch($type)
		{
			case "date":
			$this->Set_Page('header','Condor Report');
			$show = "date_search";
			include_once('form.dates.html');
			$this->Set_Page('footer');
			break;
			
			case "audit":
			$this->Set_Page('header','Audit Trail Generate');
			$show = "audit_search";
			include_once('form.dates.html');
			$this->Set_Page('footer');
		}
	}
	
	private function Get_Date()
	{
		$this->date1 = $this->request['month1']."-".$this->request['day1']."-".$this->request['year1'];
		$this->date2 = $this->request['month2']."-".$this->request['day2']."-".$this->request['year2'];
		return;
	}

	private function Show_Daily()
	{
		$this->Get_Date();
		$this->Set_Page('header','Daily Count');
		$query = "SELECT DATE(DATE_MODIFIED) AS DATE_LISTS,
					COUNT(*) AS TOTAL_COUNT,
					PROPERTY_SHORT
					FROM SIGNATURE 
					WHERE DATE(DATE_MODIFIED) BETWEEN '{$this->date1}' AND '{$this->date2}'
					GROUP BY DATE(DATE_MODIFIED),PROPERTY_SHORT
					ORDER BY DATE_LISTS DESC 
					FOR READ ONLY";
		$result = $this->db2->Execute($query);
		$this->Gen_Table($result,'show_daily');
		$this->Set_Page('footer');
	}
	
	private function Show_Date_Report()
	{
		/**
		$query = "SELECT SIGNATURE.SIGNATURE_ID,
						SIGNATURE.DOCUMENT_ARCHIVE_ID,
						SIGNATURE.APPLICATION_ID,
						SIGNATURE.PROPERTY_SHORT,
						SIGNATURE.SITE_ID,
						SIGNATURE.IP_ADDRESS,
						TIME(SIGNATURE.DATE_MODIFIED) AS TIME,
		
						CUSTOMER.NAME_FIRST,
						CUSTOMER.NAME_LAST,
						TRANSACTION.IS_PAPERLESS

				FROM SIGNATURE, CUSTOMER, TRANSACTION
				WHERE 
					TRANSACTION.TRANSACTION_ID = SIGNATURE.APPLICATION_ID
				AND
					CUSTOMER.CUSTOMER_ID = TRANSACTION.CUSTOMER_ID
				AND
					DATE(SIGNATURE.DATE_MODIFIED) = '{$this->request['date']}'

				ORDER BY SIGNATURE.DATE_MODIFIED DESC
				FOR READ ONLY";
		**/
/*		$query = "SELECT SIGNATURE.SIGNATURE_ID,
						SIGNATURE.DOCUMENT_ARCHIVE_ID,
						SIGNATURE.APPLICATION_ID,
						SIGNATURE.PROPERTY_SHORT,
						SIGNATURE.SITE_ID,
						SIGNATURE.IP_ADDRESS,
						TIME(SIGNATURE.DATE_MODIFIED) AS TIME,
		
						CUSTOMER.NAME_FIRST as First_Name,
						CUSTOMER.NAME_LAST as Last_Name,
						TIME(AUDIT_TRAIL.SIGNATURE_AGREE) AS AGREE,
						TIME(AUDIT_TRAIL.SIGNATURE_DISAGREE) AS DISAGREE
					FROM 
						SIGNATURE, CUSTOMER, TRANS_FOR_SIG, AUDIT_TRAIL
				";
		if($this->request['transaction_id'])
			$query .= " WHERE SIGNATURE.APPLICATION_ID IN (".trim($this->request['transaction_id']).") ";
		else
		{
//			$this->Get_Date();
			$query .= " WHERE DATE(SIGNATURE.DATE_MODIFIED) = '{$this->request[date]}'";
		}
		$query .= " AND TRANS_FOR_SIG.TRANSACTION_ID = SIGNATURE.APPLICATION_ID
					AND CUSTOMER.CUSTOMER_ID = TRANS_FOR_SIG.CUSTOMER_ID 
					AND AUDIT_TRAIL.SIGNATURE_ID = SIGNATURE.SIGNATURE_ID
					";
		
		$query .= 	" AND SIGNATURE.PROPERTY_SHORT='{$this->request[property_short]}'";
		$query .=	" AND TRANS_FOR_SIG.SCHEME = SIGNATURE.PROPERTY_SHORT 
					ORDER BY SIGNATURE.DATE_MODIFIED DESC
					FOR READ ONLY";*/
	
		$query = "SELECT SIGNATURE.SIGNATURE_ID,
						SIGNATURE.DOCUMENT_ARCHIVE_ID,
						SIGNATURE.APPLICATION_ID,
						SIGNATURE.PROPERTY_SHORT,
						SIGNATURE.SITE_ID,
						SIGNATURE.IP_ADDRESS,
						TIME(SIGNATURE.DATE_MODIFIED) AS TIME,
						TIME(AUDIT_TRAIL.SIGNATURE_AGREE) AS AGREE,
						TIME(AUDIT_TRAIL.SIGNATURE_DISAGREE) AS DISAGREE
					FROM 
						SIGNATURE, AUDIT_TRAIL
				";
		if($this->request['transaction_id'])
			$query .= " WHERE SIGNATURE.APPLICATION_ID IN (".trim($this->request['transaction_id']).") ";
		else
		{
//			$this->Get_Date();
			$query .= " WHERE DATE(SIGNATURE.DATE_MODIFIED) = '{$this->request[date]}'";
		}
		$query .= " AND AUDIT_TRAIL.SIGNATURE_ID = SIGNATURE.SIGNATURE_ID
					";
		
		$query .= 	" AND SIGNATURE.PROPERTY_SHORT='{$this->request[property_short]}'";
		$query .=	"ORDER BY SIGNATURE.DATE_MODIFIED DESC
					FOR READ ONLY";
		
		//echo $query; die;
		/**/

		$this->Set_Page('header',"Date report for {$this->request['date']}");
		$result = $this->db2->Execute($query);
		//echo "<pre>"; print_r($result); die;
		$this->Gen_Table($result,'date_report');
		$this->Set_Page('footer');
	}
	
	private function Show_Detail()
	{
		switch($this->request[type])
		{
			case "view_doc":
			// removed header and footer per mike rsk
			//$this->Set_Page('header','Legal Document View');
			$query = "SELECT DOCUMENT FROM DOCUMENT_ARCHIVE WHERE DOCUMENT_ARCHIVE_ID={$this->request[value]}";
			$result = $this->db2->Execute($query);
			$data = $result->Fetch_Array();
			if(!$data[DOCUMENT])
				echo "DOCUMENT NOT EXISTS!";
			else 
			{
				echo gzuncompress($data[DOCUMENT]);
			}
			//$this->Set_Page('footer');
			break;
			
			// this if for ecash app so they can use a link to pull up the application for reprints
			case "reprint_doc":
			
			// security check
			$pass = md5($this->request['application_id'] . 'potato');
			
			if ($this->request['password'] != $pass)
			{
				echo "Legal documents not found";
				break;
			}
			if ($this->request['application_id'] )
			{

				$query = "SELECT document FROM document_archive
				JOIN 
					signature ON (signature.document_archive_id = document_archive.document_archive_id) 
				AND 
					signature.application_id = {$this->request['application_id']}
				ORDER by signature_id DESC 
				LIMIT 1";

				$result = $this->sql->Query (MYSQL_DB, $query);

				// If the query finds a row
				if($this->sql->Row_Count($result) > 0)
				{
					$data_set = $this->sql->Fetch_Object_Row($result);
				}
				if(!$data_set->document)
				{
					echo "Legal documents not found";

				}
				else
				{
					echo gzuncompress($data_set->document);
				}

			// no application_id passed in
			} else {
				echo "Legal documents not found";
			}
			break;
			
			// this if for ecash app so they can use a link to pull up the application for reprints
			case "reprint_rsk":
			
			if ($this->request['application_id'] )
			{

				$query = "SELECT document FROM document_archive
				JOIN 
					signature ON (signature.document_archive_id = document_archive.document_archive_id) 
				AND 
					signature.application_id = {$this->request['application_id']}
				ORDER by signature_id DESC 
				LIMIT 1";


				$result = $this->sql->Query (MYSQL_DB, $query);

				// If the query finds a row
				if($this->sql->Row_Count($result) > 0)
				{
					$data_set = $this->sql->Fetch_Object_Row($result);
				}
				if(!$data_set->document)
				{
					return FALSE;
				}
				else
				{
					echo gzuncompress($data_set->document);
				}

			// no application_id passed in
			} else {
				echo "Legal documents not found";
			}
			
			break;
			
			default:
			break;
		}
	}
	
	private function Show_Transaction()
	{
		$query = "SELECT * FROM AUDIT_TRAIL WHERE SIGNATURE_ID IN
					(SELECT SIGNATURE_ID FROM SIGNATURE WHERE APPLICATION_ID IN({$this->request['value']}))
					ORDER BY DATE_MODIFIED DESC";
		$this->Set_Page('header',"Signature History of {$this->request['value']}");
		$result = $this->db2->Execute($query);
		$this->Gen_Table($result);
		$this->Set_Page('footer');
	}
	
	private function Set_Page($type,$title=FALSE)
	{
		switch($type)
		{
			case "header":
			echo "<html>\n";
			echo "<head>\n";
			echo "<title>$title</title>\n";
			echo "</head>\n";
			echo "<body>\n";
			echo "<table width=900 cellspacing=1 cellpadding=2 style=\"border-width:1px;border-style:solid; font-family:Verdana; font-size:10pt;\">\n";
			echo "<tr bgcolor='#c8c8c8'><td>::<a href='?page=admin'>Main</a><br>".
					"::<a href='?page=admin&show=daily_report'>Condor Report</a><br>".
					"::<a href='?page=admin&show=audit_trail'>Generate Audit Trail Report</a><br>".
					"::<a href='?page=admin&show=legal_document'>Add/Edit Document</a>".
					"</td></tr>\n";
			echo "<tr><td bgcolor='#d8d8d8'><strong>$title</strong></td></tr>\n";
			echo "<tr><td align=center>\n";
			break;
			
			case "footer":
			echo "</td></tr></table>\n";
			echo "</body>\n";
			echo "</html>\n";
		}
	}
	
	private function Gen_Table($result,$type=FALSE,$data=FALSE)
	{
		echo "<table cellspacing=1 cellpadding=2 style=\"border-width:1px;border-style:solid; font-family:Verdana; font-size:10pt;\">\n";
		
		// build input forms
		if($type == 'gen_form')
		{
			while($data = $result->Fetch_Array())
			{
				foreach($data as $key => $val)
				{
					$i++;
					$color = ($i%2)?$this->color1:$this->color2;
					echo "<tr valign='top' bgcolor='$color' align=left onMouseOver=\"this.bgColor='".$this->highlight."';\" onMouseOut=\"this.bgColor='$color';\">\n";
					echo "<td>". ucwords(strtolower(str_replace("_", " " ,$key))) ."</td>";
					if(strlen($val)>100)
						echo "<td><textarea name='".strtolower($key)."' rows=40 cols=120></textarea></td>";
					else
						echo "<td><input type='text' name='".strtolower($key)."' size='".strlen($val)."' value='{$val}'></td>";
					echo "</tr>\n";
				}
				echo "<tr><td colspan=2 align=right><input type=submit value='SUBMIT'></td></tr>\n";
			}
			echo "</form>\n";
			echo "</table>\n";
			return;
		}
		
		while($data = $result->Fetch_Array())
		{
			if($type == 'audit_report')
			{
				$data[PASSED] = $this->Validate_Signature($data[DOCUMENT_ARCHIVE_ID],$data[DOCUMENT_CHECKSUM]);
				if($data[PASSED] > 0)
				{
					$count_passed++;
				}
				else 
				{
					$count_failed++;
					$failed_doc[]= $data[DOCUMENT_ARCHIVE_ID];
				}
			}
			//-- build table header
			if($count == 0)
			{
				echo "<tr bgcolor='#EEEEEE'>";
				foreach ($data as $key=>$val)
				{
					echo "<td>". ucwords(strtolower(str_replace("_", " " ,$key))) ."</td>";
				}
				echo "</tr>";
				$count = 1;
			}
			
			//-- reset the data to the first pointer
			reset($data);
			
			$i++;
			$color = ($i%2)?$this->color1:$this->color2;
			echo "<tr bgcolor='$color' align=right onMouseOver=\"this.bgColor='".$this->highlight."';\" onMouseOut=\"this.bgColor='$color';\">\n";
			foreach($data as $key=>$val)
			{
				switch($type)
				{
					case "date_report":
					if($key == 'DOCUMENT_ARCHIVE_ID')
						// added target=_new rsk
						echo "<td><a href='?page=admin&show=detail&type=view_doc&value=$data[DOCUMENT_ARCHIVE_ID]' target=_new>$data[DOCUMENT_ARCHIVE_ID]</a></td>";
					elseif($key == 'APPLICATION_ID')
						echo "<td><a href='?page=admin&show=transaction&value=$data[APPLICATION_ID]'>$data[APPLICATION_ID]</a></td>";
					else
						echo "<td>$val</td>\n";
					break;
					
					case "show_daily":
					if($key == 'DATE_LISTS')
						echo "<td width=100><a href='?page=admin&show=date_report&date=$data[DATE_LISTS]&property_short=$data[PROPERTY_SHORT]'>$data[DATE_LISTS]</a></td>";
					elseif($key == 'TOTAL_COUNT')
						echo "<td align=right>$data[TOTAL_COUNT]</td>";
					else 
						echo "<td>$val</td>";
					break;
					
					case "legal_per_site":
					if($key == 'LEGAL_DOCUMENT_ID')
						echo "<td><a href='?page=admin&show=edit_doc&type=edit_info&legal_doc_id=$data[LEGAL_DOCUMENT_ID]'>$data[LEGAL_DOCUMENT_ID]</a></td>";
					else
						echo "<td>$val</td>";
					break;
					
					case "audit_list":
					if($key == 'DATE')
						echo "<td><a href='?page=admin&show=audit_check&date=$data[DATE]&property_short=$data[PROPERTY_SHORT]'>$data[DATE]</a></td>";
					else 
						echo "<td>$val</td>\n";
					break;
					
					default:
					echo "<td>$val</td>";
					break;
				}
			}
			echo "</tr>";
		}
		echo "</table>";
		if($type == 'audit_report')
		{
			echo "<b>PASSED: $count_passed</b><br><b>FAILED: $count_failed</b>\n";
			if($failed_doc)
			{
				echo "<br>FAILED DOCUMENT_ARCHIVE_ID:";
				echo "<UL>\n";
				foreach($failed_doc as $key=>$val)
				{
					echo "<li>$val<br>\n";
				}
				echo "</UL>\n";
			}
		}
		return;
	}
	
	private function Gen_Audit_Report()
	{
		if($this->request[transaction_id])
		{
			$this->Audit_Report_List();
			return;
		}
		$this->Get_Date();
		$query = "SELECT 
					COUNT(*) as NUMDOCS,
					SIGNATURE.PROPERTY_SHORT,
					DATE(SIGNATURE.DATE_MODIFIED) as DATE
				FROM 
					SIGNATURE
				WHERE 
					DATE(SIGNATURE.DATE_MODIFIED) BETWEEN '{$this->date1}' AND '{$this->date2}' 
				GROUP BY SIGNATURE.PROPERTY_SHORT, DATE(SIGNATURE.DATE_MODIFIED)
				ORDER BY DATE(SIGNATURE.DATE_MODIFIED) DESC
				FOR READ ONLY";
		$result = $this->db2->Execute($query);
		$this->Set_Page('header','Audit Report List');
		$this->Gen_Table($result,'audit_list');
		$this->Set_Page('footer');
	}

	private function Audit_Report_List()
	{
		$query = "SELECT
					*
				FROM
					SIGNATURE
				WHERE	";
		if($this->request[transaction_id])
		{
			$query .= " DOCUMENT_ARCHIVE_ID IN (".trim($this->request[transaction_id]).") ";
		}
		else 
		{
			$query .= "	DATE(DATE_MODIFIED) = '{$this->request[date]}' ";
			$query .= " AND PROPERTY_SHORT = '{$this->request[property_short]}'";
		}
		$query .= "	FOR READ ONLY";
		$result = $this->db2->Execute($query);
		$this->Set_Page('header',"Audit History for {$this->request[date]}");
		$this->Gen_Table($result,'audit_report');
		$this->Set_Page('footer');
	}
	public function Validate_Signature($document_archive_id,$originating_checksum)
	{
		$query = "SELECT DOCUMENT
					FROM DOCUMENT_ARCHIVE
					WHERE DOCUMENT_ARCHIVE_ID = $document_archive_id";
		$result = $this->db2->Execute($query);
		$data = $result->Fetch_array();
		$checksum = md5(gzuncompress($data[DOCUMENT]));
		return ($originating_checksum == $checksum)? 1: 0;
	}
}
?>
