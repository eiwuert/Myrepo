<?php
	class LuminanceCache
	{
		var $start;
		var $end;
		var $log = true;
		var $path = '';
		var $sig = null;
		var $debug = false;
		
		function LuminanceCache($path, $debug = false)
		{
			$this->path = $path;
			$this->debug = $debug;
		}
		
		private function create_signature($filename)
		{
			$this->sig = md5($filename);
			$this->debug("Creating Signature for $filename:". $this->sig);					
		}
		
		private function create_path()
		{
			$first2 = substr($this->sig, 0,1);
			$path = $this->path."/".$first2;

			$this->debug("Creating path: $path");		
			
	    	if (!is_dir($path))
	    	{
	    		if (!mkdir($path))
	    		{
	    			$this->debug("Failed to create path: $path");					
	    			return false;
	    		}
	    			
	    		chmod($path, 0775);
	    	}
	    		
	    	$this->debug("Path created: $path");	
	    	return $path;
		}	
		
		private function get_path()
		{
			$first2 = substr($this->sig, 0,1);
			$path = $this->path."/".$first2;
			
			$this->debug("returning path: $path");		
			
			return $path;
		}	
		
		public function store($filename, $data, $max_age = null, $header = "200")
		{
			if (!_USE_CACHING)
				return true;
				
			$this->debug("Storing: $file", $data);			
			
			$this->create_signature($filename);
			$path = $this->create_path();
			if (!$path)
				return false;
			
			$cache_info = null;
			if (is_numeric($max_age))
			{
				$cache_info = $path."/".$this->sig.".info";
			}

			// we're caching a missing page
			// if we don't the server could get killed by 
			// 404 errors
			$cache_404 = $path."/".$this->sig.".404";
			if ($header == "404")
			{
			    if (!is_file($cache_404))
			    {
				    $fp = fopen($cache_404,"w");
				    if ($fp)
				    {
						if (flock($fp, LOCK_EX+LOCK_NB)) {
						    fputs($fp, "404");
						    flock($fp, LOCK_UN);
						}		    
					    fclose($fp);
				    }
			    }
		    }
			else 
			{		    
			    if (is_file($cache_404))
			    {
				    unlink($cache_404);
				    unset($cache_404);
			    }		
			}
					    			
	    	$cache = $path."/".$this->sig;
	    	
	        $OUTPUT = serialize($data);
		    $fp = fopen($cache,"w");
		    if ($fp)
		    {
				$this->debug("Cache file is open");
				
				if (flock($fp, LOCK_EX+LOCK_NB)) 
				{
					$this->debug("Writing cache data");
				    fputs($fp, $OUTPUT);
				    flock($fp, LOCK_UN);
				    
				}
				else
				{
					$this->debug("Unable to flock");
					return false;
				}
			    fclose($fp);	
			    
			    if ($cache_info)
			    {
				    if (is_file($cache))
				    {
					    $fp = fopen($cache_info,"w");
					    if ($fp)
					    {
							if (flock($fp, LOCK_EX+LOCK_NB)) {
							    fputs($fp, $max_age);
							    flock($fp, LOCK_UN);
							}		    
						    fclose($fp);
					    }
				    }		
			    }
			    else 
			    {
			    	$cache_info = $path."/".$this->sig.".info";
				    if (is_file($cache_info))
				    {
					    unlink($cache_info);
					    unset($cache_info);
				    }	
			    }
		    }
		    else 
		    {
		    	$this->debug("Unable to open cache file");
		    	return false;
		    }
		    
		    $this->debug("Cache successfully stored!");
		    return true;
		}

		public function store_html($path, $data)
		{
			if (!_USE_CACHING)
				return true;
				
			//$this->debug("Storing HTML: $path", $data);			
			$data .= "<!-- page cached at ".date("Y-m-d H:i:s")." -->";
			$file = $path."/index.html";

		    $fp = fopen($file,"w");
		    if ($fp)
		    {
				$this->debug("Cache html file is open");
				
				if (flock($fp, LOCK_EX+LOCK_NB)) 
				{
					$this->debug("Writing cache html data");
				    fputs($fp, $data);
				    flock($fp, LOCK_UN);
				    
				}
				else
				{
					$this->debug("Unable to flock");
					// we couldn't write to the file!
					// we need to remove the folder so we 
					// don't end up with trouble.
							
					if ($path != ROOT_PATH && strpos($path, ROOT_PATH) !== false)
					{
						if (system ('rm -Rf '.$path.'*'))
						{
							/*if (_USE_DEBUG)
							{		
								lum_logMe('Path Destroyed');
							}*/
							return true;
						}					
					}					
					return false;
				}
			    fclose($fp);	
		    }
		    else 
		    {
		    	$this->debug("Unable to open cache file");
		    	return false;
		    }
		    
		    $this->debug("Cache successfully stored!");
		    return true;
		}
		
		
		public function delete($filename)
		{
			$this->create_signature($filename);
			$path = $this->create_path();
			if (!$path)
				return false;

	    	$cache = $path."/".$this->sig;				
			$cache_info = $path."/".$this->sig.".info";
			$cache_404 = $path."/".$this->sig.".404";
		    if (is_file($cache_404))
		    {
			    unlink($cache_404);
		    }	
		    	
			if (is_file($cache_info))
		    {
			    unlink($cache_info);
		    }	

			if (is_file($cache))
		    {
			    unlink($cache);
		    }	
		}
		
		
		private function debug($msg, $var = null)
		{
			if ($this->debug)
			{
				$caller = $this->getCaller();
				//lum_logMe($caller);
				//lum_logMe($msg);
				//if ($var)
					//lum_logVar($var);
			}	
		}
		
	    public function get($filename)
	    {
	    	$this->debug("About to get cache for $filename");
	    	$this->create_signature($filename);
	    	$path = $this->get_path();
	    	
	    	$cache = $path."/".$this->sig;	  
	    	$cache_info = $path."/".$this->sig.".info";	  
	    	$cache_404 = $path."/".$this->sig.".404";	  
	    	$this->debug("Cache file is $cache");
	    	
 	
	    	// do we have an info file?
	    	// an info files stores the max age in seconds
	    	// for the data. If we have one we should check how
	    	// old it is.
	    	if (is_file($cache_info))
	    	{
	    		$this->debug("Cache file has an info!");
	    		$info = file_get_contents($cache_info);
		    	$max_age = intval($info);
		    	$this->debug("Max age is $max_age");
				$stat = @stat($cache_info);
				if($stat) {
					$age = time() - $stat['mtime'];
					$this->debug("Age is $max_age");
					if($age > $max_age) { // The cached file is old!
						$this->debug("We have stale content! Skip the cache!");
						return false;
					}
				}		    	
	    	}
		    	
	    	$header = "200";
	    	if (is_file($cache_404))
	    		$header = "404";
	    	
	    	// we are free to use the cache, if it exists
	    	if (is_file($cache))
	    	{
		    	$cache = file_get_contents($cache);
		    	if ($cache)
		    	{
			    	$this->debug("Found cache data");
			    	return (object)array("header"=>$header, "result"=>unserialize($cache));
		    	}
	    	}
	    	
	    	$this->debug("Did not find cached data");
	    	return false;
	    }
	    
		private function getCaller()
		{
			$backtrace = debug_backtrace(); 

			$script = explode("/", $_SERVER['SCRIPT_FILENAME']);
			$script = $script[count($script)-1];

			$pid = getmypid();

			$caller = "{ " .  $pid . "::" . $script . " ";

			// only get the class if one exists!
			if (isset($backtrace[1]['class']) && $backtrace[1]['class'] != "")
				$caller .= $backtrace[1]['class'] ."::";

			// only get the function if one exists!
			if (isset($backtrace[1]))
				$caller .= $backtrace[1]['function'] . " } ";
			else
				$caller .= " } ";
				
			return $caller;
		}	

		public function clear_path($path)
		{
			$root = lum_getSitePath();
			if($path == '' || strpos($path, $root) === false)
			{
//				lum_logMe('Path is null or invalid');
				return false;
			}
			
//			if (_USE_DEBUG)
	//		{		
		//		lum_logMe('Destroying path '.$path);
			//}
				
			if (system ('rm -Rf '.$path.'*'))
			{
				//if (_USE_DEBUG)
				//{		
//					lum_logMe('Path Destroyed');
	//			}
				return true;
			}
		
			return false;
		}
	}
?>
