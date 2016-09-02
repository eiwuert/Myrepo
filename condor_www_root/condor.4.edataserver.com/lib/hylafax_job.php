<?php
	
	/**
	 *
	 * Reads HylaFax queue files.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 *
	 */
	class HylaFax_Job
	{
	
		
		protected static $queue_dir = '/var/spool/fax';
		
		protected static $queues = array(
			'docq',
			'sendq',
			'pollq',
			'doneq',
			'recvq',
		);
		
		protected $queue_file;
		protected $info;
		
		public static function Find($job_id)
		{
			
			$exists = FALSE;
			
			// try all the files
			while (($exists === FALSE) && ($queue = next(self::$queues)))
			{
				$exists = realpath(self::$queue_dir.'/'.$queue.'/q'.$job_id);
			}
			
			return $exists;
			
		}
		
		public function __construct($queue_file)
		{
			
			// load our info
			$this->info = $this->Load($queue_file);
			$this->queue_file = $queue_file;
			
			return;
			
		}
		
		public function __get($name)
		{
			
			if (isset($this->info[$name]))
			{
				$value = $this->info[$name];
			}
			else
			{
				$value = NULL;
			}
			
			return $value;
			
		}
		
		public function __set($name, $value)
		{
			return;
		}
		
		public function __isset($name)
		{
			return isset($this->info[$name]);
		}
		
		public function __unset($name)
		{
			return;
		}
		
		public function To_Array()
		{
			return $this->info;
		}
		
		protected function Load($queue_file)
		{
			
			$job = FALSE;
			
			if (is_readable($queue_file) && ($contents = file_get_contents($queue_file)))
			{
				
				if (preg_match_all('/([^:]*):(.*?)\n/', $contents, $matches, PREG_PATTERN_ORDER))
				{
					$job = array_combine($matches[1], $matches[2]);
				}
				else
				{
					throw new Exception('Invalid queue file.');
				}
				
			}
			else
			{
				throw new Exception('File not found.');
			}
			
			return $job;
			
		}
		
	}
	
?>