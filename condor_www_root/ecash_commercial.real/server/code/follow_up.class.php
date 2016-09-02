<?php

require_once(LIB_DIR.'AgentAffiliation.php');

/**
 * @todo Make this a little bit more Object Oriented. There is no reason why 
 * we can't be using static functions to retrieve records and then actually 
 * returning followup data as instances of the follow up object.
 * 
 * -MikeLively
 *
 */
class Follow_Up
{
	private static $types;
	
	/**
	 * Returns a map of follow-up types in an associative array
	 *
	 * @return array $follow_up_types
	 */
	static public function Get_Type_Map()
	{
		if(is_array(self::$types))
		{
			return self::$types;
		}
		
		$query = "
			SELECT 	follow_up_type_id,
					name_short,
					name
			FROM 	follow_up_type
			WHERE 	
					active_status = 'active'";
		
		$db = ECash_Config::getMasterDbConnection();
		$result = $db->query($query);
		
		$types = array();
		
		while(($row = $result->fetch(PDO::FETCH_OBJ)) !== FALSE)
		{
			$types[$row->follow_up_type_id]['id'] 			= $row->follow_up_type_id;
			$types[$row->follow_up_type_id]['name'] 		= $row->name;
			$types[$row->follow_up_type_id]['name_short'] 	= $row->name_short;
		}
		
		self::$types = $types;
		
		return self::$types;
	}

	/**
	 * Find the follow_up_type_id by it's name_short value
	 *
	 * @param string $type_name - the name_short value of the follow_up type
	 * @return integer or false
	 */
	private function Get_Type_Id($type_name) 
	{
		$follow_up_type_map = self::Get_Type_Map();
		
		foreach($follow_up_type_map as $type) 
		{
			if($type['name_short'] == $type_name)
			return $type['id'];
		}
		
		return false;
	}
	
	static public function Create_Follow_Up($application_id, $follow_up_type, $follow_up_time, $agent_id = 0, $company_id = NULL, $comment = NULL, $fire_cfe = true)
	{
		$db = ECash_Config::getMasterDbConnection();
		
		if(!$follow_up_type_id = self::Get_Type_Id($follow_up_type))
		{
			throw new Exception("Unknown follow up type \"$follow_up_type\", $follow_up_type_id");
		}



		if(is_null($company_id))
		{
			$company_id =  ECash::getCompany()->CompanyId;;
		}

		$date_available = $follow_up_time;

		if (!stristr($follow_up_time,'DATE_ADD'))
		{
			$follow_up_time = "'$follow_up_time'";
		}
		
		if(is_null($comment))
		{
		//	throw new Exception("A comment must be supplied with a follow-up!");
			$comment_id = 0;
		}
		else
		{
			$comments = ECash::getApplicationById($application_id)->getComments();
			$comment_id = $comments->add("F: $comment", $agent_id, ECash_Application_Comments::TYPE_FOLLOWUP, ECash_Application_Comments::SOURCE_LOAN_AGENT);
		}
		$query = "
			INSERT INTO follow_up
			( date_created, company_id, application_id, follow_up_type_id, follow_up_time, agent_id, comment_id )
			VALUES
			( NOW(), $company_id, $application_id, $follow_up_type_id, $follow_up_time, $agent_id, $comment_id ); ";
		
		$db->query($query);

		$retval = $db->lastInsertId();
		
		if($fire_cfe)
		{
			ECash::getApplicationById($application_id);
	        $engine = ECash::getEngine();	
	        $engine->executeEvent('FOLLOW_UP', array('date_available' => strtotime($date_available)));
		}
		return $retval;
	}
	
	/**
	 * With the addition of refactor 2.2, "My Queue" is an actual queue now.  
	 * This means that creating a collections followup is now just a matter of inserting 
	 * the application into the agent's queue and leaving a comment on the application when appropriate.
	 * A genuine followup entry in the followup table is no longer created. [W!-09-19-2008][#18250]
	 *
	 * For now we're just going to keep the arguments the same, for consistency's sake.
	 * 
	 * @param int $application_id
	 * @param string $follow_up_time - MySQL timestamp of when the application will be available in an agent's queue.
	 * @param int $agent_id
	 * @param int $company_id
	 * @param string $comment - A comment that will be added to the comment pane of the application.
	 * @param int $date_expiration - The date that the application will no longer be available in an agent's queue.
	 * @param string $affiliation_reason - The reason that the application is getting a follow_up. It must be a value in the agent_affiliation_reason table.
	 */
	static public function createCollectionsFollowUp($application_id, $follow_up_time, $agent_id, $company_id, $comment, $date_expiration, $affiliation_reason, $fire_cfe = true)
	{
		//No longer creating a followup for the application [W!-09-19-2008][#18250]
		
		//$follow_up = new Follow_Up();
		//$follow_up->Create_Follow_Up($application_id, 'collections', $follow_up_time, $agent_id, $company_id, $comment, $fire_cfe);

		
		$application = ECash::getApplicationById($application_id);
		//Add the comment if it exists
		if (!is_null($comment))
		{
			$comments = $application->getComments();
			$comments->add("F: $comment", $agent_id, ECash_Application_Comments::TYPE_FOLLOWUP, ECash_Application_Comments::SOURCE_LOAN_AGENT);
		}
		
		
		//Insert into my queue
		$agent = ECash::getAgentById($agent_id);
		if(!empty($date_expiration) && !is_numeric($date_expiration))
		{
			$date_expiration = strtotime($date_expiration);
		}
		$agent->getQueue()->insertApplication($application, 'collections', $date_expiration, strtotime($follow_up_time));
	}
	
	static public function Get_Follow_Ups_For_Application($application_id)
	{
		$query = "
			SELECT  fup.follow_up_id,
					fup.application_id,
					fup.company_id,
					fup.follow_up_time,
					fup.agent_id,
					fut.name_short as type,
					fut.name
					FROM follow_up AS fup
					JOIN follow_up_type AS fut USING (follow_up_type_id)
					WHERE fup.application_id = {$application_id}
					AND fup.status != 'complete' ";

		$db = ECash_Config::getMasterDbConnection();
		$st = $db->query($query);
				
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	static public function Get_Follow_Ups_For_Agent($agent_id, $show_past, $show_future, $sort_field, $sort_direction, $show_count = FALSE,$restrictions = NULL)
	{
		if($show_future == FALSE && $show_past == FALSE) 
		{
			return array();
		}
		$query = "
            SELECT   fup.application_id    AS  application_id,
                     NULL                  AS  date_expiration,
                     fup.follow_up_time    AS  date_next_contact,
                     fut.name_short        AS  affiliation_area,
                     ap.name_last          AS  name_last,
                     ap.name_first         AS  name_first,
                     ap.ssn                AS  ssn
            FROM     follow_up             AS  fup
            JOIN     follow_up_type        AS  fut USING (follow_up_type_id)
            JOIN     application           AS  ap  ON (ap.application_id = fup.application_id)";
		
		if($restrictions)
		{
			foreach($restrictions['join'] as $join_text)
			{
				$query .= "JOIN " . $join_text;
			}
		}
        $query.="    WHERE    fup.agent_id          = $agent_id ";

		if($show_past === TRUE && $show_future === FALSE) 
		{
			$query .= "
            AND      fup.follow_up_time    <= CURRENT_TIMESTAMP ";
		}
		else if ($show_future === TRUE && $show_past === FALSE)
		{
			$query .= "
            AND      fup.follow_up_time    >= CURRENT_TIMESTAMP 
            AND      fup.status             = 'pending' ";
		}
		else if ($show_count)
		{
			$query .= "
            AND      fup.status             = 'pending' ";
		}
		if($restrictions)
		{
			foreach($restrictions['where'] as $where_text)
			{
				$query .= "
					AND
					{$where_text}";
			}
		}

		if(isset($sort_direction) && isset($sort_field))
		{
			$query .= "
			ORDER BY    $sort_field               $sort_direction ";
		}

		$db = ECash_Config::getMasterDbConnection();
		$st = $db->query($query);

		return $st->fetch(PDO::FETCH_ASSOC);
	}

	static public function Get_Follow_Ups_By_Type($types)
	{
		$company_id = ECash::getCompany()->company_id;
		
		$query = "
			SELECT  fup.follow_up_id,
					fup.application_id,
					fup.company_id,
					fup.follow_up_time,
					fup.agent_id,
					fut.name_short as type,
					fut.name
			FROM follow_up AS fup
			JOIN follow_up_type AS fut USING (follow_up_type_id)
			WHERE fut.name_short IN ('".implode("','", $types)."')
			AND fup.status = 'pending'
			AND fup.follow_up_time <= CURRENT_TIMESTAMP()
			AND fup.company_id = $company_id ";

		$db = ECash_Config::getMasterDbConnection();
		$st = $db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}
	
	static public function Update_Follow_Up_Status($application_id, $follow_up_id = NULL)
	{
		$query = "
			UPDATE follow_up
			SET	status = 'complete'
			WHERE application_id = {$application_id}
			AND follow_up_time  <= CURRENT_TIMESTAMP ";
		
		if(!is_null($follow_up_id) && ctype_digit((string) $follow_up_id)) 
		{
			$query .= "
			AND follow_up_id = {$follow_up_id}";
		}

		$db = ECash_Config::getMasterDbConnection();
		$st = $db->query($query);
		return ($st->rowCount() > 0);
	}
	
	/**
	 * Add a given unit of time to a passed in date/time value
	 * 
	 * $timestamp may be either a string or a unix timestamp
	 * 
	 * This function was originally called emulate_date_add and only supported unix timestamps
	 *
	 * @param string or unix timestamp $timestamp
	 * @param integer $value to add
	 * @param string $unit_string unit value to add (day, hour, minute, etc.)
	 * @return string date string (Y-m-d H:i:s)
	 */
	static public function Add_Time($timestamp, $integer, $unit_string)
	{
		if(! ctype_digit((string) $timestamp)) 
		{
			$unix_timestamp = strtotime($timestamp);
		} 
		else 
		{
			$unix_timestamp = $timestamp;
		}
		
    	switch(strtolower($unit_string))
    	{
	        case "microsecond": $interval = (intval($unix_timestamp + ($integer/1000)));
				break;
	        case "second":      $interval = (intval($unix_timestamp + ($integer)));
				break;
    	    case "minute":      $interval = (intval($unix_timestamp + ($integer * 60)));
				break;
        	case "hour":        $interval = (intval($unix_timestamp + ($integer * 3600)));
				break;
        	case "day":         $interval = (intval($unix_timestamp + ($integer * 86400)));
        		break;
        	case "week":        $interval = (intval($unix_timestamp + ($integer * 604800)));
				break;

        	case "month":
        	case "year":
        	case "second_microsecond":
        	case "minute_microsecond":
        	case "minute_second":
        	case "hour_microsecond":
        	case "hour_second":
        	case "hour_minute":
        	case "day_microsecond":
        	case "day_second":
        	case "day_minute":
        	case "day_hour":
        	case "year_month":
        	default:
	            throw(new Exception("Not yet implemented"));
    	}

    	return date("Y-m-d H:i:s", $interval);
    	
    	// Fallback on resumed exception
    	return(NULL);
	}

	
	/**
	 * Expires all follow-ups for an application
	 *
	 * @param integer $application_id
	 * @return boolean
	 */
	public static function Expire_Follow_Ups($application_id)
	{
		$query = "
			UPDATE follow_up
			SET	status = 'complete'
			WHERE application_id = {$application_id} ";
			
		$db = ECash_Config::getMasterDbConnection();
		$st = $db->query($query);
		return ($st->rowCount() > 0);
	}

}

