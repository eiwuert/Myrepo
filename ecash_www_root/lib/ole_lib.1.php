<?php
    // Version 1.0.0
    // The ole_add function (used by both ole and opt-in co-reg)

include_once "/virtualhosts/lib/mysql.2.php";

function ole_add($email, $first, $last, $site_id, $list_id, $IPaddress)
{
 
	$email_arr = explode ("@", $email);

	$test_domains = array ("sellingsource.com","nowhere.com","test.test");

	// [development test-URL filter]
	if (!in_array ($email_arr[1], $test_domains))
	{

		// Setup DB Access

		define ('SQL_READ_HOST', 'selsds001'); //ds001.ibm.tss
		define ('SQL_WRITE_HOST', 'selsds001'); //ds001.ibm.tss
		define ('SQL_USER', 'sellingsource');
		define ('SQL_PASS', 'password');
		define ('SQL_PORT', '3306');
		//define ('DB_EPM', 'olenextrel');
		define ('DB_EPM', 'oledirect2');

		$sql = new MySQL_2 (SQL_READ_HOST, SQL_WRITE_HOST, SQL_USER, SQL_PASS, SQL_PORT);
		$link_read = $sql->Read_Connect ("\t".__FILE__."->".__LINE__."\n");
		Error_1::Error_Test ($link_read);
		$link_write = $sql->Write_Connect ("\t".__FILE__."->".__LINE__."\n");
		Error_1::Error_Test ($link_write);

	
		// see if this email is in OLE at all
		$query = "Select ID, lists from personindex where email = '". $email ."'";
		$pi_object = $sql->Query (DB_EPM, $query, "\t".__FILE__."->".__LINE__."\n");
		Error_1::Error_Test ($pi_object);

		if ( ! $sql->Row_Count($pi_object))
		{
			// they are not in the system at all, add personindex then list_### row
			$query = "INSERT INTO personindex SET email = '".$email."', name = '".$first." ".$last."', last = '".$last."', first = '".$first."', lists = '".$list_id."'";
			$object = $sql->Query (DB_EPM, $query, "\t".__FILE__."->".__LINE__."\n");
			$personindex_id = $sql->Insert_Id();
			Error_1::Error_Test ($object);
		
			list($username,$domain) = preg_split("/@/",$email);
			$the_text = $list_id . $email;
			$md5_text = md5($the_text);

			$query = "INSERT INTO list_".$list_id." SET piID = '".$personindex_id."', sID = '".$site_id."', email = '".$email."', name = '".$first." ".$last."', last = '".$last."', first = '".$first."', secret_code = '".$md5_text."', domain = '".$domain."', added = NOW(), addedtime = CURTIME(), IPaddress = '".$IPaddress."' ";
			$object = $sql->Query (DB_EPM, $query, "\t".__FILE__."->".__LINE__."\n");
			$person_id = $sql->Insert_Id();
			Error_1::Error_Test ($object);		
		}
		else 
		{
			// they are in OLE, see if already on this list
			$lists = array();
			$lists = explode(",", $person_index->lists);
			if (array_search($list_id, explode(",", $lists)) === FALSE)
			{
				list($username,$domain) = preg_split("/@/",$email);
				$the_text = $list_id . $email;
				$md5_text = md5($the_text);
				// they are NOT on the list, add them to the list+_### and update the personindex
				$query = "INSERT IGNORE INTO list_".$list_id." SET piID = '".$pi_object->ID."', sID = '".$site_id."', email = '".$email."', name = '".$first." ".$last."', last = '".$last."', first = '".$first."', secret_code = '".$md5_text."', domain = '".$domain."', added = NOW(), addedtime = CURTIME(), IPaddress = '".$IPaddress."' ";
				$object = $sql->Query (DB_EPM, $query, "\t".__FILE__."->".__LINE__."\n");
				$person_id = $sql->Insert_Id();
				Error_1::Error_Test ($object);		
			
				array_push($lists, $list_id);
				$lists_field = implode($lists, ","); // put it back into id,id,id format
				
				$person_indexquery = "UPDATE personindex SET lists = \"".$lists_field."\" WHERE ID = \"".$person_index->ID."\"";
				$person_indexresult = mysql_query($person_indexquery);
			}
		}
	}

	// [development test-URL conditional block]
}

?>
