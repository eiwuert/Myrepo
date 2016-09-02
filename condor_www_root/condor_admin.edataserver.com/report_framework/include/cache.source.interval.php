<?php
	
	class Interval_Source_Cache implements iInterval_Source
	{
		
		protected $source;
		protected $cache;
		
		protected $cache_dir;
		protected $cache_name;
		protected $ttl;
		protected $overlap;
		protected $compression;
		
		protected $now;
		
		public function __construct($source = NULL)
		{
			
			if ($source instanceof iCacheable) $this->source = $source;
			return;
			
		}
		
		public function Source(iCacheable $source)
		{
			
			$this->source = $source;
			return;
			
		}
		
		public function Cache_Dir($dir = NULL)
		{
			
			if ($dir !== NULL)
			{
				if (substr($dir, -1) !== '/') $dir .= '/';
				$this->cache_dir = $dir;
			}
			
			return $this->cache_dir;
			
		}
		
		public function Cache_Name($name = NULL)
		{
			
			if ($name !== NULL)
			{
				$name = str_replace('/', '', $name);
				$this->cache_name = $name;
			}
			
			return $this->cache_name;
			
		}
		
		public function TTL($ttl = NULL)
		{
			
			if (is_numeric($ttl) || (($ttl !== NULL) && (@strtotime('-'.$ttl) !== FALSE)))
			{
				$this->ttl = $ttl;
			}
			
			return $this->ttl;
			
		}
		
		public function Overlap($overlap = NULL)
		{
			
			if (($overlap !== NULL) && (is_numeric($overlap) || (@strtotime('-'.$overlap) !== FALSE)))
			{
				$this->overlap = $overlap;
			}
			
			return $this->overlap;
			
		}
		
		public function Prepare($now)
		{
			
			if ($this->source)
			{
				
				$this->now = $now;
				
				if (!$this->cache)
				{
					
					// get a cache object
					$this->cache = new Object_Cache();
					$this->cache->Directory($this->cache_dir);
					$this->cache->Name($this->cache_name);
					
					if ($this->ttl !== NULL)
					{
						$this->cache->TTL($this->ttl);
					}
					
					// read from our cache
					$this->cache->Read();
					
				}
				
				$this->source->Prepare($now);
				
			}
			else
			{
				throw new Exception('No source.');
			}
			
			return;
			
		}
		
		public function Finalize()
		{
			
			if ($this->cache)
			{
				// write our cache
				$this->cache->Write();
			}
			
			$this->source->Finalize();
			
			return;
			
		}
		
		public function Fill($intervals, &$data_y)
		{
			
			if ($this->cache)
			{
				
				$overlap = $this->now;
				if ($this->overlap) $overlap = Move_Interval($overlap, $this->overlap, -1);
				
				reset($intervals);
				
				while (list($key, $interval) = each($intervals))
				{
					
					if (is_array($interval) && (list($start, $end) = $interval))
					{
						$value = $this->Fetch($interval);
						if ($value !== NULL) $data_y[$key] = $value;
					}
					
				}
				
			}
			
			return;
			
		}
		
		// fetch a single interval
		protected function Fetch($interval)
		{
			
			if ($this->cache)
			{
				
				list($start, $end) = $interval;
				
				// get the time past which intervals will need refreshing
				$overlap = (($this->overlap) ? $this->now : Move_Interval($this->now, $this->overlap, -1));
				
				$hash = $this->source->Hash($interval);
				
				// have to refresh this interval if it overlaps the current time
				$force = ($end > $overlap);
				$value = $this->cache->Fetch($hash);
				
				if ($this->source && ($force || ($value === NULL)))
				{
					
					// fetch the real value from our source
					$value = $this->source->Fetch($interval);
					
					// don't write intervals inside our overlap time to the cache,
					// otherwise, we may have stale values build up
					if (($value !== NULL) && !$force)
					{
						$this->cache->Put($hash, $value);
					}
					
				}
				elseif ($value !== NULL)
				{
					// keep this interval from expiring
					$this->cache->Touch($hash);
				}
				
			}
			else
			{
				throw new Source_Not_Prepared();
			}
			
			return $value;
			
		}
		
	}
	
?>