<?php
/**
*	@name Adverse_Action
*	@author Chris Barmonde
*	@version 0.1
*
*	@desc Class to handle Adverse Action functionality.  It
*		will determine if an application has failed for a
*		CLK company.  If it has, and a winner was never
*		chosen for the app, it will select a random CLK
*		company based on the companies that denied the app.
*/


class Adverse_Action
{
	const DENIAL_CLK	= 'aa_denial_clk';
	const DENIAL_DATAX	= 'aa_denial_datax';
	const DENIAL_TT		= 'aa_denial_teletrack';
	const DENIAL_IMPACT	= 'aa_denial_impact';
	const DENIAL_DATAX_IMPACT	= 'aa_denial_datax_impact';

	const APPLOG_NONE		= 0; //Don't write to the applog
	const APPLOG_DEFAULT	= 1; //Write only crucial errors
	const APPLOG_FULL		= 2; //Write a decent amount of info
	
	protected $config;
	protected $applog;		//Applog
	protected $applog_level;	//Applog level
	
	
	protected $application_id;//App ID
	protected $denial_reason;	//Denial reason
	protected $event_table;	//Event Log table used

	protected $query_restriction;
	protected $good_events; //Events that might not or won't have a target_id in the event log

	public function __construct(&$config, $reason)
	{
		$this->config = &$config;
		
		$this->application_id = intval($this->config->application_id);
		$this->denial_reason = $reason;

		$prop_query = implode("','", $config->clk_properties);
		$this->query_restriction = "target.property_short IN ('{$prop_query}')";
		
		$this->good_events = array (
			'USEDINFO_CHECK',
			'CASHLINE_CHECK',
			'ABA_BAD',
			'DATAX_IDV',
			//'DATAX_PDX_IMPACT',
			'DATAX_PERF'
		);
		
		//Get event_log table
		$this->event_table = (isset($_SESSION['event_log_table'])) ? $_SESSION['event_log_table'] : 'event_log_' . date('Ym');

		$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
		$this->applog_level = self::APPLOG_DEFAULT;
	}
	
	
	/**
	*	@publicsection
	*	@public
	*
	*	@fn bool Update_Denial_Winner
	*
	*	@desc Tries to find a target for the application so we can set
	*		the denial_target_id.  First it will check and see if
	*		there was a target assigned at one point, and uses that
	*		if it was a CLK company.  Otherwise, it will grab FAIL
	*		events from the event log and randomly select a CLK
	*		company that failed the application (if more than one
	*		denied it).
	*
	*	@return bool
	*		True if the record was updated successfully or we already
	*		had a denied_target_id, false if it failed somehow.
	*/
	public function Update_Denial_Winner()
	{
		$target_id = 0;

		//Gets the target_id and denied_target_id
		$status = $this->Get_App_Status();

		if(!empty($status))
		{	
			//If we have a denied_target_id, just ignore it for now
			//Should this ever happen???
			if(intval($status['denied_target_id']) > 0)
			{
				$this->Applog_Write("[Adverse Action] denied_target_id already exists!  ID: {$status['denied_target_id']}", self::APPLOG_FULL);
				return TRUE;
			}
			//If we do have a target_id, though, we can use that as the denial id
			elseif(intval($status['target_id']) > 0)
			{
				$target_id = intval($status['target_id']);				
			}
		}
		//Didn't find an app with this app_id??
		else
		{
			$this->Applog_Write("[Adverse Action] Didn't find an app with this app_id??  App_ID: {$this->application_id}", self::APPLOG_FULL);
			return FALSE;
		}
		
		
		//If we already have a target_id, we can try and use it as the denied_target_id		
		if($target_id > 0)
		{
			//If it's not a CLK company, reset the target_id
			if(!$this->Verify_Target($target_id))
			{
				$target_id = 0;
			}
		}
		
		
		//Now we need to find a target_id, so let's do it.
		if($target_id == 0)
		{
			$target_id = $this->Find_Target_ID();
		}
		
		
		//Let's at least make sure it's an int in case of some freak accident.
		$target_id = intval($target_id);

		//One last check to make sure we have a good number
		if($target_id > 0)
		{
			//Finally, let's update the app!
			$query = 
				"UPDATE application
					SET denied_target_id = '{$target_id}'
				WHERE application_id = '{$this->application_id}'
				LIMIT 1";
				
			try
			{
				$result = $this->config->sql->Query($this->config->database, $query);
			}
			catch(MySQL_Exception $e)
			{
				$this->Applog_Write("[Adverse Action] Failed to update application with denied_target_id: {$query}", self::APPLOG_DEFAULT);
			}

			
			if($result)
			{
				//Let's hit some stats
				$this->Hit_Denial_Stat();
				$this->Hit_Mail_Stat($target_id);
			}
			
			
			return ($result) ? TRUE : FALSE;
		}
		
		return FALSE;
	}
	
	
	
	protected function Hit_Denial_Stat()
	{
		Stats::Hit_Stats($this->denial_reason, $this->config->session, $this->config->log, $this->applog, $this->application_id);
	}
	
	
	/**
	*	@privatesection
	*	@private
	*
	*	@fn array Get_App_Status()
	*
	*	@desc Grabs the target_id and denial_target_id from the application, if they exist.
	*
	*	@return array
	*		The target_id and denied_target_id if they actually exist.
	*/
	protected function Get_App_Status()
	{
		$status = array();
		
		//Check and see if we've already found a denial target
		$query = "SELECT target_id, denied_target_id, application_type FROM application WHERE application_id='{$this->application_id}' LIMIT 1";
		try
		{
			$result = $this->config->sql->Query($this->config->database, $query);
			
			if($result && $this->config->sql->Row_Count($result) > 0)
			{
				$status = $this->config->sql->Fetch_Array_Row($result);
			}
		}
		catch (MySQL_Exception $e)
		{
			$this->Applog_Write("[Adverse Action] Error when trying to get app status: {$this->application_id}", self::APPLOG_FULL);
		}
		
		return $status;
	}
	
	
	/**
	*	@privatesection
	*	@private
	*
	*	@fn bool Verify_Target($target_id)
	*
	*	@desc Verifies that the target is a CLK company.  This
	*		is used in cases where a target_id was already
	*		set on the application.  We can only use that id
	*		if it's a CLK company.
	*
	*	@param target_id int
	*		The target_id to verify
	*
	*	@return bool
	*		True if we find the target and it is a tier 1 company.
	*/
	protected function Verify_Target($target_id)
	{
		$verified = FALSE;
		
		//We need to make sure it's a CLK company, first
		$query = "SELECT target_id
			FROM target
			WHERE
				{$this->query_restriction}
				AND target.status  = 'ACTIVE'
				AND target.deleted = 'FALSE'
				AND target_id = '{$target_id}'";
		
		try
		{
			$result = $this->config->sql->Query($this->config->database, $query);
		
			//If we found a row, we have a valid CLK target
			$verified = ($result && $this->config->sql->Row_Count($result) > 0);
		}
		catch (MySQL_Exception $e)
		{
			$this->Applog_Write("[Adverse Action] Failed to verify target: {$query}", self::APPLOG_FULL);
		}
		
		return $verified;
	}
	
	
	/**
	*	@privatesection
	*	@private
	*
	*	@fn array Get_Failed_Events()
	*
	*	@desc Grabs the events from the event log that are
	*		marked with FAIL and are in BROKER mode.
	*
	*	@return array
	*		Array containing the target_id, event, and tier_number
	*		for every failed event.
	*/
	protected function Get_Failed_Events()
	{
		$events = array();

		$query = "SELECT el.target_id, e.event
			FROM {$this->event_table} el
			INNER JOIN events e USING (event_id)
			INNER JOIN event_responses er ON el.response_id = er.response_id
			LEFT JOIN target ON el.target_id = target.target_id
				AND {$this->query_restriction}
			WHERE el.application_id = '{$this->application_id}'
				AND el.mode = 'BROKER'
				AND er.response IN ('FAIL', 'bad', 'BAD', 'OVERACTIVE', 'UNDERACTIVE')";

		try
		{
			$result = $this->config->sql->Query($this->config->database, $query);
			
			if($result && $this->config->sql->Row_Count($result) > 0)
			{
				while($row = $this->config->sql->Fetch_Array_Row($result))
				{
					$events[] = $row;
				}
			}
		}
		catch(MySQL_Exception $e)
		{
			$this->Applog_Write("[Adverse Action] Failed to get events from event log: {$query}", self::APPLOG_DEFAULT);
		}

		return $events;
	}
	
	
	/**
	*	@privatesection
	*	@private
	*
	*	@fn int Find_Target_ID()
	*
	*	@desc Attempts to find a valid CLK company to use for
	*		the app.  It will check against the event log and
	*		the events that occurred to see which, if any, CLK
	*		companies were involved in the denial, then it will
	*		randomly choose from them to pick a 'winner'.
	*
	*	@return int
	*		The target_id.  Will be 0 if something goes wrong.
	*/
	protected function Find_Target_ID()
	{
		$target_id = 0;
		
		$events = $this->Get_Failed_Events();

		
		if(!empty($events))
		{
			$found_targets = array();

			
			//Assume we don't have one of the above events
			$has_event = FALSE;
			$pick_winner_failed = FALSE;
			
			foreach($events as $event)
			{
				//Found a CLK company
				if(!is_null($event['target_id']))
				{
					//Make sure we only get each target once
					$found_targets[$event['target_id']] = $event['target_id'];
				}
				elseif(is_null($event['target_id']) && in_array(strtoupper($event['event']), $this->good_events))
				{
					$has_event = TRUE;
				}
			}
			

			//If there weren't any tier 1 targets, but we have a bad aba or some such,
			//We'll need to get a random CLK company.
			if(empty($found_targets) && $has_event)
			{
				$target_id = $this->Get_Random_Target();
			}
			//Otherwise, if we've found some CLK targets in the event log
			elseif(!empty($found_targets))
			{
				//Get a random winner
				$winner = mt_rand(0, count($found_targets) - 1);
	
				//Grab the winner from the found_targets array
				$keys = array_keys($found_targets);
				$target_id = $keys[$winner];
			}

		}
		
		return $target_id;
	}
	
	

	/**
	*	@privatesection
	*	@private
	*
	*	@fn int Get_Random_Target()
	*
	*	@desc Gets a random CLK company.  Used when no
	*		CLK company was involved in the denial.
	*		(Should only happen with ABA_BAD???)
	*
	*	@return int
	*		The target_id of the CLK company.  0 if something
	*		goes wrong.
	*/
	protected function Get_Random_Target()
	{
		$target_id = 0;
		
		//Get random CLK company
		$query = "SELECT target_id
			FROM target
			WHERE
				{$this->query_restriction}
				AND target.status  = 'ACTIVE'
				AND target.deleted = 'FALSE'
			ORDER BY RAND()
			LIMIT 1";
		
		try
		{
			$result = $this->config->sql->Query($this->config->database, $query);
			
			//Grab the target_id if we've found something
			if($result && $this->config->sql->Row_Count($result) > 0)
			{
				$target_id = $this->config->sql->Fetch_Column($result, 0);					
			}
		}
		catch(MySQL_Exception $e)
		{
			$this->Applog_Write("[Adverse Action] Didn't manage to find any CLK companies for some reason?", self::APPLOG_FULL);
		}

		return $target_id;
	}
	
	
	
	
	
	protected function Get_Property_Short($target_id)
	{
		$winner = '';
		
		$query = "SELECT property_short FROM target WHERE target_id = '{$target_id}'";
		
		try
		{
			$result = $this->config->sql->Query($this->config->database, $query);
			
			if($result && $this->config->sql->Row_Count($result) > 0)
			{
				$winner = $this->config->sql->Fetch_Column($result, 0);
			}
		}
		catch (MySQL_Exception $e)
		{
			$this->Applog_Write('[Adverse Action] Failed to get property short.', self::APPLOG_FULL);
		}
		
		return $winner;
	}
	
	
	
	protected function Hit_Mail_Stat($target_id)
	{
		//Find the property short
		$winner = strtolower($this->Get_Property_Short($target_id));
		
		if(!is_null($this->denial_reason))
		{
			//Get the right stat
			$stat = '';
			switch($this->denial_reason)
			{
				case self::DENIAL_IMPACT:
				case self::DENIAL_DATAX_IMPACT:
				case self::DENIAL_CLK:
				case self::DENIAL_DATAX:$stat = 'aa_mail_generic_'; break;
				case self::DENIAL_TT:	$stat = 'aa_mail_teletrack_'; break;
			}
			
			if(!empty($stat) && !empty($winner))
			{
				$stat .= $winner;
				
				//Let's hit it
				Stats::Hit_Stats($stat, $this->config->session, $this->config->log, $this->applog, $this->application_id);
			}
		}
	}
	
	
	
	
	/**
	*	@privatesection
	*	@private
	*
	*	@fn void Applog_Write($message, $level)
	*
	*	@desc Writes out to the applog depending on the current
	*		log level.  As long as the level passed in the
	*		Applog_Write() call is <= the current applog_level,
	*		it will be written out.
	*
	*	@param message string
	*		The message to write to the applog
	*	@param level int
	*		The applog level of the call
	*/
	protected function Applog_Write($message, $level = self::APPLOG_DEFAULT)
	{
		if($level <= $this->applog_level)
		{
			$this->applog->Write($message);
		}
	}
}






class Adverse_Action_Impact extends Adverse_Action
{
	public function __construct(&$config, $reason)
	{
		parent::__construct($config, $reason);
		
		$prop_query = implode("','", $config->impact_properties);
		$this->query_restriction = "target.property_short IN ('{$prop_query}')";
		
		$this->good_events = array (
			'USEDINFO_CHECK',
			'CASHLINE_CHECK',
			'ABA_BAD',
			'DATAX_PDX_IMPACT'
		);
	}
}

?>