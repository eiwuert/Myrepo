<?php
	
	/**
	 *
	 * A "stack" cache: unlike the Data_Cache, this cache can handle multiple
	 * pieces of information ("objects"), and each object that is added has it's
	 * own expiration date.
	 *
	 * @author Andrew Minerd
	 *
	 */
	class Object_Cache
	{
		
		protected $cache_dir;
		protected $cache_name;
		protected $compression;
		protected $ttl;
		
		protected $added = array();
		protected $objects = array();
		
		public function Directory($dir = NULL)
		{
			
			if ($dir !== NULL) $this->cache_dir = $dir;
			return $this->cache_dir;
			
		}
		
		public function Name($name = NULL)
		{
			
			if ($name !== NULL) $this->cache_name = $name;
			return $this->cache_name;
			
		}
		
		public function Compression($compression = NULL)
		{
			
			if ($compression !== NULL) $this->compression = $compression;
			return $this->compression;
			
		}
		
		public function TTL($ttl = NULL)
		{
			
			if (($ttl !== NULL) && (is_numeric($ttl) || (@strtotime('-'.$ttl) !== FALSE)))
			{
				$this->ttl = $ttl;
			}
			
			return $this->ttl;
			
		}
		
		public function Put($key, $object)
		{
			
			// add to our cache
			$this->added[$key] = time();
			$this->objects[$key] = $object;
			
			return;
			
		}
		
		public function Fetch($key)
		{
			
			$object = NULL;
			
			if (isset($this->objects[$key]) && (!$this->Expired($key)))
			{
				$object = $this->objects[$key];
			}
			
			return $object;
			
		}
		
		public function Touch($key)
		{
			
			if (isset($this->added[$key]))
			{
				unset($this->added[$key]);
				$this->added[$key] = time();
			}
			
			return;
			
		}
		
		public function Delete($key)
		{
			
			if (isset($this->added[$key]))
			{
				unset($this->added[$key]);
				unset($this->objects[$key]);
			}
			
			return;
			
		}
		
		public function Exists($key)
		{
			$exists = isset($this->objects[$key]);
			return $exists;
		}
		
		public function Expired($key)
		{
			
			if ($this->ttl !== NULL)
			{
				
				// figure out our expiration time
				$expire_at = (is_numeric($this->ttl) ? (time() - ($this->ttl * 60)) : strtotime('-'.$this->ttl));
				$expired = (isset($this->added[$key]) && ($this->added[$key] <= $expire_at));
				
			}
			else
			{
				$expired = FALSE;
			}
			
			return $expired;
			
		}
		
		public function Data()
		{
			
			$data = array();
			
			foreach ($this->objects as $key=>$object)
			{
				
				$data[$key] = array(
					'object' => $object,
					'added' => $this->added[$key],
				);
				
			}
			
			return $data;
			
		}
		
		public function Clean_Up()
		{
			
			if ($this->ttl !== NULL)
			{
				
				// figure out our expiration time
				$expire_at = (is_numeric($this->ttl) ? (time() - ($this->ttl * 60)) : strtotime('-'.$this->ttl));
				
				while (list($key, $time) = each($this->added))
				{
					
					if ($time <= $expire_at)
					{
						unset($this->added[$key]);
						unset($this->objects[$key]);
					}
					
				}
				
			}
			
			return;
			
		}
		
		public function Read()
		{
			
			// set up our cache object
			$cache = new Data_Cache();
			$cache->Cache_Dir($this->cache_dir);
			$cache->Version($this->cache_name);
			
			// assume we fail
			$read = FALSE;
			
			if (is_array($data = $cache->Read()) && isset($data['added']) && isset($data['objects']))
			{
				
				if (is_array($data['added']) && is_array($data['objects']))
				{
					
					// read our data
					$this->added = $data['added'];
					$this->objects = $data['objects'];
					
					// get rid of expired stuff
					$this->Clean_Up();
					unset($data);
					
					$read = TRUE;
					
				}
				
			}
			
			unset($cache);
			
			return $read;
			
		}
		
		public function Write($clean = TRUE)
		{
			
			// set up our cache object
			$cache = new Data_Cache();
			$cache->Cache_Dir($this->cache_dir);
			$cache->Version($this->cache_name);
			$cache->Compression($this->compression);
			
			// the cache can expire when all of the data in our cache will also
			// have expired (important for automatic garabage collection!)
			if ($this->ttl !== NULL)
			{
				$expire_at = (is_numeric($this->ttl) ? (time() + ($this->ttl * 60)) : strtotime('+'.$this->ttl));
				$cache->Expire_At($expire_at);
			}
			
			// avoid writing expired objects: that
			// just makes our job harder
			if ($clean) $this->Clean_Up();
			
			$data = array(
				'added' => $this->added,
				'objects' => $this->objects,
			);
			
			// update our cache
			$written = $cache->Write($data);
			return $written;
			
		}
		
	}
	
?>