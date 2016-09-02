<?php
	
	/**
	 *
	 * A simple cache class. You can cache a single piece of data (although
	 * that "single piece" could be an array). The entire cache is expired
	 * at once.
	 *
	 * @author Andrew Minerd
	 *
	 */
	class Data_Cache
	{
		
		// our version
		const VERSION = 2.0;
		
		// compression constants
		const COMPRESS_GZ = 'GZIP';
		
		protected $cache_dir;
		protected $cache_file;
		protected $expire_at;
		protected $ttl;
		protected $refreshed;
		protected $version;
		protected $compression;
		
		public function Cache_Dir($dir = NULL)
		{
			
			if ($dir !== NULL)
			{
				
				// get rid of unwanted characters
				$dir = trim($dir);
				if (substr($dir, -1) === '/') $dir = substr($dir, 0, -1);
				
				$this->cache_dir = $dir;
				
			}
			
			return $this->cache_dir;
			
		}
		
		public function Cache_File($file = NULL)
		{
			
			if ($file !== NULL)
			{
				
				if (strpos('/', $file) === FALSE)
				{
					$path = $this->cache_dir.'/'.$file;
				}
				else
				{
					
					// if possible, work with a real filename
					if (($temp = realpath($file)) !== FALSE) $file = $temp;
					
					$path = $file;
					$this->cache_dir = dirname($file);
					$this->cache_file = basename($file);
					
				}
				
				// try to get info from it
				$this->Read_Info($path);
				
			}
			
			return $this->cache_file;
			
		}
		
		public function Version($version = NULL)
		{
			
			if (!is_null($version))
			{
				
				// save this
				$this->version = $version;
				
				// try to get our information
				$file = $this->File_Name($version);
				$this->Read_Info($file);
				
			}
			
			return $this->version;
			
		}
		
		public function Expire_At($timestamp = NULL)
		{
			
			if ($timestamp === FALSE)
			{
				$this->ttl = FALSE;
				$this->expire_at = FALSE;
			}
			elseif (($timestamp !== NULL) && (is_numeric($timestamp) || (($timestamp = @strtotime($timestamp)) !== FALSE)))
			{
				
				// save
				$this->expire_at = $timestamp;
				$this->ttl = NULL;
				
			}
			
			return $this->expire_at;
			
		}
		
		public function TTL($minutes = NULL)
		{
			
			if ($minutes === FALSE)
			{
				$this->ttl = FALSE;
				$this->expire_at = FALSE;
			}
			elseif (is_numeric($minutes))
			{
				
				// save
				$this->ttl = $minutes;
				$this->expire_at = NULL;
				
			}
			
			return $this->ttl;
			
		}
		
		public function Refreshed()
		{
			return $this->refreshed;
		}
		
		public function Expired()
		{
			
			$expired = FALSE;
			
			if (($this->expire_at !== NULL) && ($this->expire_at !== FALSE))
			{
				$expired = ($this->expire_at < time());
			}
			elseif (($this->ttl !== NULL) && ($this->ttl !== FALSE))
			{
				$ttl = ($this->ttl * 60);
				$expired = (($this->refreshed + $ttl) < time());
			}
			
			return $expired;
			
		}
		
		public function Compression($compression = NULL)
		{
			
			switch ($compression)
			{
				
				case self::COMPRESS_GZ:
					$this->compression = $compression;
					break;
				
			}
			
			return $this->compression;
			
		}
		
		public function Read($version = NULL)
		{
			
			$file = $this->File_Name($version);
			
			$content = FALSE;
			
			if (($file !== FALSE) && file_exists($file) && is_readable($file))
			{
				
				if (($fp = @fopen($file, 'r')) !== FALSE)
				{
					
					// read the header
					$header = trim(fgets($fp, 1024));
					
					// make sure it's a valid file
					if ($this->Decode_Header($header) && (!$this->Expired()))
					{
						
						// read the rest of the data
						$content = @stream_get_contents($fp);
						
						// unpack the data
						if ($this->compression) $content = $this->Decompress($content);
						$content = @unserialize($content);
						
					}
					
					// close it up
					fclose($fp);
					
				}
				
				
			}
			
			
			return $content;
			
		}
		
		public function Write($content, $version = NULL)
		{
			
			// decide which file we're using
			$file = $this->File_Name($version);
			$written = FALSE;
			
			if ($file !== FALSE)
			{
				
				// we just refreshed it!
				$this->refreshed = time();
				
				// "zip" this stuff up
				$header = $this->Encode_Header();
				$content = serialize($content);
				if ($this->compression) $content = $this->Compress($content);
				
				// save it to the file
				$content = $header."\n".$content;
				$written = (@file_put_contents($file, $content) == strlen($content));
				
			}
			
			return $written;
			
		}
		
		public function File_Name($version = NULL)
		{
			
			$filename = FALSE;
			
			// decide which filename to use
			if (is_null($version) && (!is_null($this->cache_file)))
			{
				$filename = $this->cache_file;
			}
			elseif ((!is_null($version)) || (!is_null($version = $this->version)))
			{
				$filename = $this->cache_dir.'/'.$version.'.cache';
			}
			
			return($filename);
			
		}
		
		protected function Read_Info($file)
		{
			
			$read = FALSE;
			
			if (is_file($file) && is_readable($file))
			{
				
				// open the file
				if (($fh = @fopen($file, 'r')) !== FALSE)
				{
					
					// read the header line
					$data = trim(@fgets($fh, 1024));
					$read = $this->Decode_Header($data);
					
					// done with it
					fclose($fh);
					
				}
				
			}
			
			return $read;
			
		}
		
		protected function Decode_Header($data)
		{
			
			$decoded = FALSE;
			
			// attempt to unserialize it as needed
			if (!is_array($data)) $data = @unserialize($data);
			
			// check to see if this is a valid header
			if (is_array($data) && isset($data['data_cache_version']) && ($data['data_cache_version'] >= self::VERSION))
			{
				
				// read the data
				$expire_at = ((isset($data['expire_at']) && (is_numeric($data['expire_at']) || ($data['expire_at'] === FALSE))) ? $data['expire_at'] : NULL);
				$ttl = ((isset($data['ttl']) && (is_numeric($data['ttl']) || ($data['ttl'] === FALSE))) ? $data['ttl'] : NULL);
				$refreshed = ((isset($data['refreshed']) && is_numeric($data['refreshed'])) ? $data['refreshed'] : NULL);
				$compression = (isset($data['compression']) ? $data['compression'] : NULL);
				
				// is it a valid header?
				if ($refreshed !== NULL) //((($expire_at !== NULL) || ($ttl !== NULL)) && ($refreshed !== NULL))
				{
					
					// save this locally
					$this->expire_at = $expire_at;
					$this->ttl = $ttl;
					$this->refreshed = $refreshed;
					$this->compression = $compression;
					
					// we did it!
					$decoded = TRUE;
					
				}
				
			}
			
			return $decoded;
			
		}
		
		protected function Encode_Header()
		{
			
			$header = array();
			$header['data_cache_version'] = self::VERSION;
			$header['refreshed'] = $this->refreshed;
			
			if ($this->compression) $header['compression'] = $this->compression;
			if ($this->expire_at !== NULL) $header['expire_at'] = $this->expire_at;
			if ($this->ttl !== NULL) $header['ttl'] = $this->ttl;
			
			$header = serialize($header);
			return $header;
			
		}
		
		protected function Compress($content)
		{
			
			switch ($this->compression)
			{
				
				case self::COMPRESS_GZ:
					$content = (function_exists('gzcompress') || @dl('zlib')) ? gzcompress($content) : FALSE;
					break;
					
				// no compression
				case NULL:
					break;
					
				// unsupported compression type
				default:
					$content = FALSE;
					break;
					
			}
			
			return $content;
			
		}
		
		protected function Decompress($content)
		{
			
			switch ($this->compression)
			{
				
				case self::COMPRESS_GZ:
					$content = (function_exists('gzuncompress') || @dl('zlib')) ? gzuncompress($content) : FALSE;
					break;
					
				// no compression
				case NULL:
					break;
					
				// unsupported compression type
				default:
					$content = FALSE;
					break;
				
			}
			
			return $content;
			
		}
		
	}
	
	/*	class Data_Cache
	{
		
		protected $cache_dir;
		protected $cache_file;
		protected $expire_at;
		protected $ttl;
		protected $refreshed;
		protected $version;
		
		public function Cache_Dir($directory = NULL)
		{
			
			if (is_string($directory) && is_dir($directory))
			{
				
				if (substr($directory, -1) === '/')
				{
					$directory = substr($directory, 0, -1);
				}
				
				$this->cache_dir = $directory;
				
			}
			
			return($this->cache_dir);
			
		}
		
		public function Cache_File($file = NULL)
		{
			
			if (strpos('/', $file) === FALSE)
			{
				$directory = $this->cache_dir;
				$path = $directory.'/'.$file;
			}
			else
			{
				
				$path = $file;
				
				$directory = dirname($file);
				$file = basename($file);
				
			}
			
			if (file_exists($path))
			{
				
				$this->cache_dir = $directory;
				
				if (preg_match('/^(.+/?)\.cache$/', $file. $m))
				{
					$this->version = $m[1];
					$this->cache_file = NULL;
				}
				else
				{
					$this->cache_file = $file;
					$this->version = NULL;
				}
				
				// update info about this file
				$this->File_Info($file);
				
			}
			else
			{
				$path = FALSE;
			}
			
			return $path;
			
		}
		
		public function TTL($ttl = NULL)
		{
			
			if (is_numeric($ttl))
			{
				$this->expire_at = strtotime("+{$ttl} minutes");
				$this->ttl = $ttl;
			}
			elseif ($ttl === FALSE)
			{
				$this->expire_at = $ttl;
				$this->ttl = $ttl;
			}
			elseif ($this->expire_at)
			{
				$ttl = (int)round(($this->expire_at - time()) / 60, 0);
			}
			
			return($ttl);
			
		}
		
		public function Expire_At($timestamp = NULL)
		{
			
			if (is_numeric($timestamp) || ($timestamp === FALSE))
			{
				$this->expire_at = $timestamp;
				$this->ttl = NULL;
			}
			
			return($this->expire_at);
			
		}
		
		public function Expired()
		{
			
			$expired = FALSE;
			
			if (is_numeric($this->ttl))
			{
				$expired = (($this->refreshed + ($this->ttl * 60)) < time());
			}
			elseif (is_numeric($this->expire_at))
			{
				$expired = ($this->expire_at < time());
			}
			elseif ($this->expire_at === FALSE)
			{
				$expired = $this->expire_at;
			}
			
			return $expired;
			
		}
		
		public function Refreshed()
		{
			return($this->refreshed);
		}
		
		public function Version($version = NULL)
		{
			
			if (!is_null($version))
			{
				
				$filename = $this->File_Name($version);
				
				// update local variables
				$this->version = $version;
				$this->cache_file = $filename;
				
				// update info about this file
				$this->File_Info($filename);
				
			}
			
			return($version);
			
		}
		
		public function File_Name($version = NULL)
		{
			
			$filename = FALSE;
			
			// decide which filename to use
			if (is_null($version) && (!is_null($this->cache_file)))
			{
				$filename = $this->cache_file;
			}
			elseif ((!is_null($version)) || (!is_null($version = $this->version)))
			{
				$filename = $this->cache_dir.'/'.$version.'.cache';
			}
			
			return($filename);
			
		}
		
		public function Read($version = NULL)
		{
			
			// decide which filename to use
			$filename = $this->File_Name($version);
			
			$content = FALSE;
			
			if (($filename !== FALSE) && file_exists($filename) && is_readable($filename))
			{
				
				if (($this->expire_at === FALSE) || (filemtime($filename) > time()))
				{
					$content = @file_get_contents($filename);
				}
				
				if ($content !== FALSE)
				{
					
					// pull out our variables
					$content = @unserialize($content);
					
					// update local variables
					if (!is_null($version))
					{
						$this->cache_file = $filename;
						$this->version = $version;
					}
					
					$this->File_Info($filename);
					
				}
				
			}
			
			return($content);
			
		}
		
		public function Write($content, $version = NULL)
		{
			
			$filename = $this->File_Name($version);
			$written = FALSE;
			
			if (($filename !== FALSE) && (is_writable($filename) || is_writable($this->cache_dir)))
			{
				
				// write the cache
				$content = serialize($content);
				$written = (@file_put_contents($filename, $content) > 0);
				
				if ($written)
				{
					
					if ($this->expire_at !== FALSE)
					{
						// set the expiration time
						touch($filename, $this->expire_at);
					}
					
					// update local variables
					$this->version = $version;
					$this->cache_file = $filename;
					$this->refreshed = filectime($filename);
					
				}
				
			}
			
			return($written);
			
		}
		
		protected function File_Info($filename)
		{
			
			if (file_exists($filename))
			{
				
				$this->refreshed = @filectime($filename);
				
				if ($this->expire_at !== FALSE)
				{
					$this->expire_at = @filemtime($filename);
					$this->ttl = round(($this->expire_at - $this->refreshed) / 60);
				}
				
			}
			
			return;
			
		}
		
	}*/
	
?>