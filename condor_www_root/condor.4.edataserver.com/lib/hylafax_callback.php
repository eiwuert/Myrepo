<?php
	
	require('callback.1.php');
	
	/**
	 *
	 * Extends the base Callback class and implements HylaFax
	 * specific functionality (i.e., callback storage, etc.).
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 *
	 */
	class HylaFax_Callback extends Callback
	{
		
		// SQLite database for callbacks
		const DB_FILE = '/var/spool/fax/hylafax.sqlite';
		protected static $db;
		
		protected $id;
		protected $job_id;
		
		public function ID()
		{
			return $this->id;
		}
		
		public function Job_ID()
		{
			return $this->job_id;
		}
		
		public static function Request()
		{
			
			$id = md5(microtime(TRUE));
			return $id;
			
		}
		
		public static function Register($id, $url, $job_id)
		{
			
			$result = FALSE;
			
			$db = self::Get_DB();
			
			if ($db !== FALSE)
			{
				
				$query = "
					INSERT INTO
						callback
						(
							id,
							url,
							job_id
							)
					VALUES
						(
							'{$id}',
							'{$url}',
							'{$job_id}'
						)
				";
				$result = $db->queryExec($query);
				
				if ($result !== FALSE)
				{
					
					$result = new HylaFax_Callback();
					$result->id = $id;
					$result->url = $url;
					$result->job_id = $job_id;
					
				}
				
			}
			
			return $result;
			
		}
		
		public static function Find_By_ID($id)
		{
			
			$callback = FALSE;
			
			$db = self::Get_DB();
			
			if ($db !== FALSE)
			{
				
				$query = "SELECT * FROM callback WHERE id='{$id}' LIMIT 1";
				$rec = $db->arrayQuery($query);
				
				if (is_array($rec))
				{
					
					// get the first record
					$rec = reset($rec);
					
					$callback = new HylaFax_Callback($rec['url']);
					$callback->id = $id;
					$callback->job_id = $rec['job_id'];
					
				}
				
			}
			
			return $callback;
			
		}
		
		public function Delete()
		{
			
			$result = FALSE;
			
			$db = self::Get_DB();
			
			if ($db !== FALSE)
			{
				
				$query = "DELETE FROM callback WHERE id='{$this->id}'";
				$result = $db->queryExec($query);
				
			}
			
			return $result;
			
		}
		
		public function Process(HylaFax_Job $job, $reason)
		{
			
			// build our array of tokens
			$tokens = $job->To_Array();
			$tokens['status'] = strtolower($reason);
			
			$result = parent::Process($tokens);
			
			$db = self::Get_DB();
			
			if ($db !== FALSE)
			{
				
				$query = "
					UPDATE
						callback
					SET
						last_status = '".sqlite_escape_string($reason)."'
					WHERE
						id = '{$this->id}'
				";
				$db->queryExec($query);
				
				unset($db);
				
			}
			
			return $result;
			
		}
		
		protected static function Get_DB()
		{
			
			if (!self::$db)
			{
				
				// open the database
				$db = sqlite_factory(self::DB_FILE);
				
				if ($db !== FALSE)
				{
					
					// see if we have the proper table
					$query = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='callback'";
					$exists = $db->singleQuery($query);
					
					// create the table if it doesn't exist
					if (!$exists)
					{
						
						$query = "
							CREATE TABLE callback
							(
								id VARCHAR(32) NOT NULL DEFAULT '',
								url VARCHAR(255) NOT NULL DEFAULT '',
								job_id INT(11),
								last_status VARCHAR(255) DEFAULT NULL,
								PRIMARY KEY (id)
							)
						";
						$result = $db->queryExec($query);
						
						// if we couldn't create the table return FALSE
						if ($result === FALSE)
						{
							$db = FALSE;
						}
						
					}
					
				}
				
			}
			else
			{
				$db = self::$db;
			}
			
			return $db;
			
		}
		
	}
	
?>
