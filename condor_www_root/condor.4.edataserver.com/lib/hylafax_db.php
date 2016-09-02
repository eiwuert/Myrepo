<?php
/**
 * This class handles setting up the HylaFAX database
 *
 */
class HylaFax_DB
{
		const TABLE_USER = "
			CREATE TABLE user
			(
				login VARCHAR(16) NOT NULL,
				password VARCHAR(40) NOT NULL,
				incoming_url TEXT NOT NULL,
				PRIMARY KEY (login)
			)
		";
		
		const TABLE_USER_ROUTING = "
			CREATE TABLE user_routing
			(
				login VARCHAR(16) NOT NULL,
				number VARCHAR(10) NOT NULL,
				direction INT(1) NOT NULL,
				PRIMARY KEY (number, direction)
			)
		";
		
		const TABLE_NUMBER_ROUTING = "
			CREATE TABLE number_routing
			(
				number VARCHAR(10) NOT NULL,
				direction INT(1) NOT NULL,
				modem VARCHAR(255) NOT NULL,
				status INT(1) NOT NULL DEFAULT 1,
				priority INT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (number, direction, modem)
			)
		";

		/**
		 * Table to route Incoming faxes based on DID.
		 *
		 */
		const TABLE_DID_ROUTING = "
			CREATE TABLE did_routing
			(
				did_number VARCHAR(10) NOT NULL,
				fax_number VARCHAR(10) NOT NULL,
				user VARCHAR(16) NOT NULL,
				PRIMARY KEY (did_number)
			)
		";
		
		const TABLE_JOB_CONTROL = 'CREATE TABLE job_control
			(
				job_id int(11) NOT NULL,
				from_string varchar(255) NOT NULL DEFAULT NULL,
				company_id int(5) NOT NULL DEFAULT 0,
				PRIMARY KEY (job_id)
			)';
		
		protected static $db;
		
		public static function Get_DB($mode = 'LIVE')
		{
			if(!isset(self::$db))
			{
				self::Setup_DB($mode);
			}
			return self::$db;
		}
		
		//not that it matters the mode since it's all the same thing
		protected static function Setup_DB($mode = 'LIVE')
		{
			switch ($mode)
			{
				case MODE_DEV:
				case MODE_RC:
				case MODE_LIVE:
					$file = '/var/spool/fax/hylafax.sqlite';
					//$file = '/tmp/hylafax.sqlite';
					break;
					
			}
			self::$db = self::Database($file);
				
			if (self::$db !== FALSE)
			{
				// make sure we have all our required tables
				self::Check_Table(self::$db, 'user', self::TABLE_USER);
				self::Check_Table(self::$db, 'user_routing', self::TABLE_USER_ROUTING);
				self::Check_Table(self::$db, 'number_routing', self::TABLE_NUMBER_ROUTING);
				self::Check_Table(self::$db, 'did_routing', self::TABLE_DID_ROUTING);
				self::Check_Table(self::$db, 'job_control', self::TABLE_JOB_CONTROL);
			}
			else 
			{
				unset(self::$db);
			}
		}
				
		
		protected static function Check_Table($db, $name, $sql_create = NULL)
		{
			
			$exists = FALSE;
			
			// see if we have the proper table
			$query = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='{$name}'";
			$exists = $db->singleQuery($query);
			
			// create the table if it doesn't exist
			if ((!$exists) && ($sql_create !== NULL))
			{
				$exists = ($db->queryExec($sql_create) !== FALSE);
			}
			
			return $exists;
			
		}
		
		protected static function Database($file)
		{
			
			// open the database
			$db = sqlite_factory($file);
			
			if ($db === FALSE)
			{
				throw new Exception('Could not open routing database.');
			}
			
			return $db;
			
		}
}