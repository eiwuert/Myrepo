<?php
	
	require_once('mysqli.1.php');
	
	/**
	 *
	 * This file is a hack for Condor's email transport via OLE.
	 * No attempt has been made to disguise the fact that this is,
	 * indeed, a hack.
	 *
	 * Author: Andrew Minerd, 2006-03-24
	 *
	 */
	
	class OLE_Hack
	{
		
		protected $mode;
		protected $sql;
		
		// database connection information
// 		const DB_HOST = 'db100.clkonline.com';
// 		const DB_USER = 'sellingsource';
// 		const DB_PASS = 'password';
// 		const DB_NAME = 'oledirect2';
		
		// constants used for database inserts
		const EMAIL_TYPE = 2; // "Transactional"
		
		public function __construct($mode)
		{
			$this->mode = $mode;
		}
		
		/**
		 *
		 * Creates a dummy event (i.e., an event and a corresponding email
		 * with a single token that will contain the Condor documnet body) for
		 * a Condor template, so that OLE stats will continue to work properly.
		 *
		 * @param $event_name string The event name to create
		 * @param $subject_token string The token used for the document's subject (default is "subject")
		 * @param $body_token string The token used for the document's body (default is "body")
		 * @param $format string The email format (H = HTML / T = Text / M = Multipart-Alternate?)
		 *
		 */
		public function Create_Dummy_Event($property_id, $event_name, $from_token = 'from', $subject_token = 'subject', $body_token = 'body', $format = 'H')
		{
			
			// assume we fail
			$result = FALSE;
			
			// get our MySQL connection
			$sql = $this->Connection();
			
			if ($sql !== FALSE)
			{
				
				try
				{
				
					$query = "
						INSERT INTO events
						(
							property_id,
							name,
							event_type,
							event_owner
						)
						VALUES
						(
							{$property_id},
							'{$event_name}',
							".self::EMAIL_TYPE.",
							''
						)
					";
					$sql->Query($query);
					
					$event_id = $sql->Insert_ID();
					
					$query = "
						INSERT INTO emails
						(
							property_id,
							event_id,
							RE,
							subject,
							text_message,
							html_message,
							created,
							wfrom,
							format
						)
						VALUES
						(
							{$property_id},
							{$event_id},
							'{$event_name}',
							'%%%{$subject_token}%%%',
							'".(($format == 'T') ? "%%%{$body_token}%%%" : '')."',
							'".(($format == 'H') ? "%%%{$body_token}%%%" : '')."',
							NOW(),
							'%%%{$from_token}%%%',
							'H'
						)
					";
					$sql->Query($query);
					
					// done
					$result = TRUE;
					
				}
				catch (Exception $e)
				{
					$result = FALSE;
				}
				
			}
			
			return $result;
			
		}
		
		public function Event_Exists($property_id, $event_name)
		{
			
			$exists = FALSE;
			
			// get our MySQL connection
			$sql = $this->Connection();
			
			$query = "
				SELECT
					name
				FROM
					events
				WHERE
					property_id = {$property_id} AND
					name = '{$event_name}'
			";
			$result = $sql->Query($query);
			
			$exists = ($result && ($rec = $result->Fetch_Array_Row()));
			return $exists;
			
		}
		
		protected function Connection()
		{
			
			if (!$this->sql)
			{
				
				$host = 'olemaster.soapdataserver.com';
				$user = 'condor';
				$pass = '5uIwP2kg';
				$db = 'oledirect2';
				// Setup MySQL connection
				$sql = new MySQLi_1(
					$host,
					$user,
					$pass,
					$db,
					(isset($port) ? $port : NULL)
				);
				
				$this->sql = $sql;
				
			}
			else
			{
				$sql = $this->sql;
			}
			
			return $sql;
			
		}
		
	}
	
?>
