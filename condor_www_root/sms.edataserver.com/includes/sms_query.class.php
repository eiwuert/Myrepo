<?

/**
 * sms.query.class.php
 * 
 * @desc 
 * 		Query class for SMS Web Service
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @version 
 * 		1.0
 * @todo
 * 		comments
 */

class SMS_Query
{
	private $mysql;
	private $database;
	public  $message_status_array;   // array( message_status.status => message_status.message_status_id );
	
	function __construct( &$mysql, $database )
	{
		$this->mysql = $mysql;
		$this->database = $database;

		$this->message_status_array = array();
		$sql = " SELECT status, message_status_id FROM message_status ";
		$result = $this->mysql->Query($this->database, $sql);
		while ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$this->message_status_array[$row['status']] = $row['message_status_id'];
		}
	}
	
	
	public function Get_Modem_Data( $company_id = false, $modem_id = false, $result_key = 'modem_id')
	{
		$query = "
			SELECT 
				modem.*, 
				company.email,
				company.company_name,
				company.company_short
			FROM 
				modem left join company on (modem.company_id = company.company_id) 
			";
		if ($modem_id || $company_id)
		{
			$query .= " WHERE ";
			$query .= ($modem_id) ? " modem.modem_id = {$modem_id} " : "";
			$query .= ($company_id) ? " company.company_id = {$company_id} " : "";
		}
		
		try 
		{
			$result = $this->mysql->Query($this->database, $query);
			
			$modem_count = $this->mysql->Row_Count($result);
			switch(true)
			{
				case $modem_count == 1:
					return $row = $this->mysql->Fetch_Array_Row($result);
					break;
				case $modem_count > 1:
					while($row = $this->mysql->Fetch_Array_Row($result))
					{
						$modems[$row[$result_key]] = $row;
					}
					return $modems;
					break;				
			}
		}
		catch( MySQL_Exception $e )
		{
			throw($e);
		}
		
		return false;
	}


	public function Get_Campaign_Data( $company_id, $message )
	{
		$company_id = isset($company_id) ? $company_id : '-2';
		$message = isset($message) ? strtolower(trim($message)) : '';
		if ( $company_id < 1 || $message == '' ) return false;
		$sql = " SELECT date_modified, date_created, campaign_id, company_id, campaign, default_message FROM campaign WHERE company_id = '$company_id' ";
		$result = $this->mysql->Query($this->database, $sql);
		while ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$campaign = strtolower(trim($row['campaign']));
			// if ( preg_match("/$campaign/", $message) )    // regular expression is much less efficient than simple string search
			if ( $campaign != '' && strpos($message, $campaign) !== false )
			{
				return $row;
			}
		}
		return false;
	}
	
	
	public function Add_Message($campaign, $phone_number, $message, $time_zone_abbrev, $time_zone_chk, $date_scheduled, $company_id, $track_key, $space_key, $priority, $message_type = 'outgoing', $modem_name = false, $status = 'queued')
	{
				
		// check date_scheduled
		$date_scheduled = (!$date_scheduled) ? 'NOW()' : "'$date_scheduled'";  // add single quotes for mysql safety if date_scheduled is populated

		// tasks if message type is outgoing
		if ($message_type == 'outgoing')
		{
			// get / create campaign info
			$campaign_info = $this->Get_Campaign_Info($campaign, $company_id, $message);
			
			// store message if it exists and does no = the default message
			$message = (($message && ($message != $campaign_info['default_message']))) ? $message : '';
		
			// get modem_data
			$modem_data = $this->Get_Modem_Data($company_id);
		}
		else 
		{
			if ($modem_name)
			{
				// get modem data 
				$modems = $this->Get_Modem_Data( null, null, 'modem_name');
				$modem_data = $modems[$modem_name];
			}
		}
			
		
		
		// prep query
		$query = "
			INSERT INTO message
			SET
				date_created = NOW(),
				date_scheduled = $date_scheduled,
				company_id = ".$company_id.",
				modem_id = '".$modem_data['modem_id']."',
				campaign_id = '".$campaign_info['campaign_id']."',
				phone_number = '".$phone_number."',
				message_status_id = (SELECT message_status_id FROM message_status WHERE status='$status'),
				message = '".mysql_escape_string($message)."',
				type = '".$message_type."',
				priority = '".$priority."',
				time_zone_id = (SELECT time_zone_id from time_zone WHERE abbrev ='{$time_zone_abbrev}'),
				time_zone_restrict = '".(($time_zone_chk) ? 'yes' : 'no')."',
				track_key = '".$track_key."',
				space_key = '".$space_key."'
		";

		try
		{
			$result = $this->mysql->Query($this->database, $query);
			$insert_id = $this->mysql->Insert_ID();
		}
		catch(MySQL_Exception $e)
		{
			throw $e;
		}
		
		return $insert_id;
	}
	
	public function Get_Campaign_Info($campaign, $company_id, $message = false)
	{
		$campaign = mysql_escape_string(strtolower($campaign));
		
		$query = "SELECT * FROM campaign WHERE company_id = {$company_id} and campaign = '{$campaign}' ";
		
		$result = $this->mysql->Query($this->database, $query);
		
		if ($row = $this->mysql->Fetch_Array_Row($result))
		{
			return $row;
		}
		else 
		{
			$query = "
			INSERT INTO campaign
			SET 
				date_created = NOW(),
				company_id = '".$company_id."',
				campaign = '".$campaign."',
				default_message = '".mysql_escape_string($message)."'
				";
			try 
			{
				$this->mysql->Query($this->database, $query);
				$campaign_id = $this->mysql->Insert_ID();
			}
			catch (MySQL_Exception $e)
			{
				throw $e;	
			}
			
			$return = array(
				'campaign_id' => $campaign_id,
				'company_id' => $company_id,
				'default_message' => $default_message,
				'campaign' => $campaign
			);
			
			return $return;
		}
	}
	
	
	public function Get_Company_ID($company_short)
	{
		$company_short = strtolower($company_short);	
	
		$query = "select company_id from company where company_short = '{$company_short}'";
		$result = $this->mysql->Query($this->database, $query);
		
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			return $row['company_id'];
		}
		else
		{
			return '';
		}

		
	}
	
	// 2006.02.16, modified to let the database calculate the new date_scheduled rather than
	//             calculating it in PHP.  The clock for the database and for the php server are
	//             not in sync but the bigger problem is when the php date_scheduled is calculated as
	//             a 12 hour time but the database is operating in 24 hour mode.
	//             Letting the database handle the time makes everything simple.
	// public function Update_Message_Status($message_id, $status, $date_scheduled = false)
	public function Update_Message_Status($message_id, $status, $minutes_from_now = false)
	{
		// $deliver_date = ($date_scheduled) ? ', date_scheduled='.$date_scheduled.' ' : false;
		$deliver_date = ($minutes_from_now) ? ", date_scheduled=now() + interval $minutes_from_now minute " : false;

		$status_id = isset($this->message_status_array[$status]) ? $this->message_status_array[$status] : 99;
		
		$query = "
			update message 
			set 
				message_status_id = '$status_id'
				{$deliver_date}
			where message_id={$message_id}";
		
		try 
		{
			$this->mysql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			throw $e;
		}
		return true;
	}
	

	public function Update_Message_Status_Campaign_Track_Space( $message_id, $status, $campaign_id, $track_key, $space_key )
	{
		$status_id = isset($this->message_status_array[$status]) ? $this->message_status_array[$status] : 98;
		
		$query = "
			update message 
			set 
				message_status_id = '$status_id'
				, campaign_id = '$campaign_id'
				, track_key = '$track_key'
				, space_key = '$space_key'
			where message_id={$message_id}";
		
		try 
		{
			$this->mysql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			throw $e;
		}
		return true;
	}
	

	public function Register_Attempt($message_id)
	{
		$query = "UPDATE message SET attempts = attempts+1 WHERE message_id={$message_id}";
		try 
		{
			$this->mysql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			throw $e;
		}
		
		$query = " SELECT attempts FROM message WHERE message_id = '$message_id' ";
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			return $row['attempts'];
		}
		return -1;
	}
	
	public function Get_Message_Data($message_id)
	{
		// $query = "SELECT m.*, ms.status as message_status FROM message m join message_status ms on (ms.message_status_id=m.message_status_id) WHERE m.message_id={$message_id}";
		
		$query = "
			SELECT message.*, message_status.status as message_status, company_short
			FROM   message, message_status, company
			WHERE  message.message_status_id = message_status.message_status_id
			AND    message.company_id = company.company_id
			AND    message.message_id={$message_id}
		";

		$result = $this->mysql->Query($this->database, $query);
		$row = $this->mysql->Fetch_Array_Row($result);
		return $row;
	}
	
	public function Get_Company_Data($company_id)
	{
		
		$query = "SELECT * FROM company WHERE company_id='$company_id' ";
		$result = $this->mysql->Query($this->database, $query);
		$row = $this->mysql->Fetch_Array_Row($result);
		return $row;
	}
	
	public function Check_Message_Status($message_id, $status)
	{
		
		$query = "SELECT * FROM message WHERE message_id={$message_id} AND message_status_id=(select message_status_id from message_status where status='{$status}')";
		$result = $this->mysql->Query($this->database, $query);
		$row = $this->mysql->Fetch_Array_Row($result);
		return is_array($row);
	}
	
	public function Get_Scheduled_Messages($type = 'outgoing')
	{
		/*
		$query = "
			SELECT m.*, t.*, c.*  
			FROM message m join campaign c on (c.campaign_id=m.campaign_id) left join time_zone t on (m.time_zone_id=t.time_zone_id)
			WHERE 
				date_scheduled <= NOW()
			AND message_status_id = (SELECT message_status_id FROM message_status WHERE status = 'queued') 
			AND type = '{$type}'
			ORDER BY priority
			LIMIT 0,500
		";
		*/
		
		$query = "
			SELECT message.*, time_zone.*, campaign.*, company.company_short
			FROM   message
			LEFT JOIN time_zone on (message.time_zone_id = time_zone.time_zone_id),
			       campaign, company
			WHERE  message.campaign_id = campaign.campaign_id
			AND    message.company_id = company.company_id
			AND    date_scheduled <= NOW()
			AND    message_status_id = (SELECT message_status_id FROM message_status WHERE status = 'queued')
			AND    type = '{$type}'
			ORDER BY priority, message.message_id
			LIMIT 0,50
		";

		$result = $this->mysql->Query($this->database, $query);
		
		while($row = $this->mysql->Fetch_Array_Row($result))
		{
			$row['message'] = (!$row['message']) ? $row['default_message'] : $row['message'];
			$messages[] = $row;
		}
		
		return $messages;
	}


	public function Catch_Messages_Stuck_In_Processing()
	{
		$status_id_queued = isset($this->message_status_array['queued']) ? $this->message_status_array['queued'] : 97;
		$status_id_processing = isset($this->message_status_array['processing']) ? $this->message_status_array['processing'] : 96;
		
		$query = "
			update message
			set message_status_id = '$status_id_queued'
			where message_status_id = '$status_id_processing'
			and   date_modified < date_sub(now(), interval 1 hour)
		";

		$this->mysql->Query($this->database, $query);

		return $this->mysql->Affected_Row_Count();
	}


	public function Check_Blacklist($phone_number)
	{
		$query = "SELECT sms_blacklist_id FROM sms_blacklist WHERE cell_number = '$phone_number' AND status = 'ACTIVE' ";
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			return true;
		}
		return false;
	}
	
	public function Update_Blacklist($phone_number)
	{
		$query = "SELECT sms_blacklist_id FROM sms_blacklist WHERE cell_number = '$phone_number' ";
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$query = "UPDATE sms_blacklist SET date_modified = now(), status = 'ACTIVE' WHERE cell_number = '$phone_number' ";
			$this->mysql->Query($this->database, $query);
		}
		else
		{
			$query = "INSERT INTO sms_blacklist SET cell_number = '$phone_number', date_modified = now(), date_created = now(), status = 'ACTIVE' ";
			$this->mysql->Query($this->database, $query);
		}
	}
	
	public function Remove_Blacklist($phone_number)
	{
		$query = "SELECT sms_blacklist_id FROM sms_blacklist WHERE cell_number = '$phone_number' ";
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$query = "UPDATE sms_blacklist SET date_modified = now(), status = 'INACTIVE' WHERE cell_number = '$phone_number' ";
			$this->mysql->Query($this->database, $query);
		}
	}
	
	
	public function Get_Last_Track_And_Space_Keys(&$stat_stuff, $phone_number, $modem_name, $campaign_id = 0)
	{
		$modem_id = 0;
	
		if ($modem_name)
		{
			// get modem data
			$modems = $this->Get_Modem_Data( null, null, 'modem_name');
			if ( $modems )
			{
				$modem_id = isset($modems[$modem_name]) ? $modems[$modem_name]['modem_id'] : 0;
			}
		}

		$campaign_sql = $campaign_id > 0 ? " AND campaign_id = '$campaign_id' " : '';

		$query = "SELECT
					message_id
					, track_key
					, space_key
				  FROM message
				  WHERE
				  	phone_number = '$phone_number'
				  	AND   modem_id = '$modem_id'
				  	AND   type = 'outgoing'
				  	$campaign_sql
				  	ORDER BY message_id desc LIMIT 1";
				  	
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$stat_stuff['track_key'] = $row['track_key'];
			$stat_stuff['space_key'] = $row['space_key'];
			return;
		}
			
		// the $stat_stuff array coming in should already have the fields set to
		// defaults (blank) so no need to do anything if the row is not found.
	}


	public function Count_Outgoing_Queued()
	{
		$status_id_queued = isset($this->message_status_array['queued']) ? $this->message_status_array['queued'] : 95;
	
		$query = "SELECT count(*) as count from message where type = 'outgoing' and message_status_id = '$status_id_queued' ";
		$result = $this->mysql->Query($this->database, $query);
		if ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			return $row['count'];
		}
		return false;
	}

	
	public function Get_Unsuccessful_Modems( $last_n_hours = 3 )
	{
		$answer = array();
		
		$status_id_delivered = isset($this->message_status_array['delivered']) ? $this->message_status_array['delivered'] : 95;
		$status_id_delivered_stathit = isset($this->message_status_array['delivered_stathit']) ? $this->message_status_array['delivered_stathit'] : 95;
		
		$query = "
			select modem.modem_name, modem.modem_id
			from modem
			where not exists
			(
				select message.message_id
				from message
				where message.modem_id = modem.modem_id
				and (
						message.message_status_id = '$status_id_delivered'
						or
						message.message_status_id = '$status_id_delivered_stathit'
					)
				and (
						message.date_created >= date_sub(now(), interval $last_n_hours hour)
						or
						message.date_scheduled >= date_sub(now(), interval $last_n_hours hour)
					)
			)
		";
		
		$result = $this->mysql->Query($this->database, $query);
		while ( $row = $this->mysql->Fetch_Array_Row($result) )
		{
			$answer[] = $row;
		}
		
		return $answer;
	}

}
