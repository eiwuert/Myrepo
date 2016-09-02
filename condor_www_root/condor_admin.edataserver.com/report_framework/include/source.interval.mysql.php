<?php
	
	class MySQL_Interval_Source implements iInterval_Source, iCacheable
	{
		
		protected $sql;
		
		protected $host;
		protected $user;
		protected $pass;
		protected $db;
		protected $port;
		
		protected $table;
		protected $field_date;
		protected $field_y;
		protected $where;
		
		protected $now;
		
		public function __construct()
		{
		}
		
		public function Host($user = NULL)
		{
			if ($user !== NULL) $this->host = $user;
			return $this->host;
		}
		
		public function Port($port = NULL)
		{
			if ($port !== NULL) $this->port = $port;;
			return $this->port;
		}
		
		public function Username($user = NULL)
		{
			if ($user !== NULL) $this->user = $user;
			return $this->user;
		}
		
		public function Password($pass = NULL)
		{
			if ($pass !== NULL) $this->pass = $pass;
			return $this->pass;
		}
		
		public function Database($db = NULL)
		{
			if ($db !== NULL) $this->db = $db;
			return $this->db;
		}
		
		public function Table($table = NULL)
		{
			if ($table !== NULL) $this->table = $table;
			return $this->table;
		}
		
		public function Field_Date($field = NULL)
		{
			if ($field !== NULL) $this->field_date = $field;
			return $this->field_date;
		}
		
		public function Field_Value($field = NULL)
		{
			if ($field !== NULL) $this->field_y = $field;
			return $this->field_y;
		}
		
		public function Where($where = NULL)
		{
			if (is_array($where)) $this->where = $where;
			return $this->where;
		}
		
		public function Prepare($now)
		{
			
			if (!$this->sql)
			{
				// get a sql connection
				$this->sql = &MySQL_Pool::Connect($this->host, $this->user, $this->pass, $this->db, $this->port);
			}
			else
			{
				$this->sql->Select($this->db);
			}
			
			$this->now = $now;
			
			return;
			
		}
		
		public function Finalize()
		{
			
			// release our reference
			//$this->sql = NULL;
			
			return;
			
		}
		
		public function Hash($x)
		{
			
			$hash = implode(':', $x);
			return $hash;
			
		}
		
		public function Fetch($x)
		{
			
			$value = NULL;
			
			if ($this->sql)
			{
				
				// extract the interval
				list($start, $end) = $x;
				
				// don't try and predict the future
				if ($start < $this->now)
				{
					
					if ($end > $this->now) $end = $this->now;
					
					// hack for the event log: this should go in a derived class,
					// but, hey, it's here for now -- this is a quick and dirty job
					$table = $this->table;
					$table = str_replace('`event_log`', '`event_log_'.date('Ym', $start).'` AS event_log', $table);
					
					$query = "SELECT {$this->field_y} AS y
						FROM {$table}
						WHERE
							{$this->field_date} >= ".date('YmdHis', $start)." AND
							{$this->field_date} < ".date('YmdHis', $end)."
						";
					if (is_array($this->where))
					{
						$query .= " AND ".implode(' AND ', $this->where);
					}
					
					// run the query
					$result = $this->sql->Query($query);
					
					if ($result && ($rec = $result->Next()))
					{
						$value = $rec['y'];
						if ($value === NULL) $value = 0;
					}
					
				}
				
			}
			else
			{
				throw new Source_Not_Prepared();
			}
			
			return $value;
			
		}
		
		public function Fill($intervals, &$data_y)
		{
			
			if ($this->sql)
			{
				
				reset($intervals);
				
				while (list($key, $interval) = each($intervals))
				{
					
					if (is_array($interval))
					{
						$value = $this->Fetch($interval);
						if ($value !== NULL) $data_y[$key] = $value;
					}
					
				}
				
			}
			else
			{
				throw new Source_Not_Prepared();
			}
			
			return;
			
		}
		
		protected function Split_Interval($start, $end, $cache)
		{
			
			// get our difference in fuzzy-intervals
			$diff = Date_Diff($start, $end);
			
		}
		
	}
	
?>
