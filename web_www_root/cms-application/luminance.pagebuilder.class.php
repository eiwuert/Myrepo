<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class handles all of our page building and output
 * 
 **/

class LuminancePageBuilder
{
	private $lumRegistry;
	private $lumStrings;
	private $url;
	private $bits = array();
	private $plugins;
	private $last_bit_id = 0;
	private $info = null;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->lumStrings = new LuminancePluginStrings($registry);
	}
	
	public function addCustomString($string)
	{
		$this->lumStrings->addCustom($string);
	}
	
	public function loadStringTable()
	{
		$this->lumStrings->load();
	}

	public function servePage()
	{
		$page_data = $this->build();
		
		if ($page_data !== false)
		{
			// good
			$this->displayPage($page_data);
		}
		else 
		{
			// bad
			$this->displayPage($this->getPageNotFound());
		}
	}
	
	private function displayPage($page_data)
	{
		header('Content-Type: text/html; charset=utf-8'); 
		switch($page_data->getHeader())
		{
			case '200':
			{
				header('HTTP/1.1 200 OK');
				break;
			}
			case '503':
			{
				header('HTTP/1.1 503 Service Unavailable');
				break;
			}
			default:
			case '404':
			{
				header('HTTP/1.1 404 Not Found');
				break;
			}
		}
		
		echo $page_data->getResult();
	}
	
	public function showNotAvailable()
	{
		$this->displayPage($this->getPageNotAvailable());
	}
	
	public function show404()
	{
		$this->displayPage($this->getPageNotFound());
	}	

	private function findPluginContent($bit, $is_last)
	{
		// we'll go through each installed plugin starting with
		$classes = lum_getInstalledPlugins();
		
		foreach ($classes as $plugin)
		{
			$potential_plugin = 'LuminancePlugin'.$plugin;

			if (!class_exists($potential_plugin))
			{
				lum_loadPlugin($plugin);
			}
			
			if (method_exists($potential_plugin, 'parseBit'))
			{
				$obj = new $potential_plugin($this->lumRegistry);
				
				if (method_exists($potential_plugin, 'setIncomingInfo'))
				{
					$obj->setIncomingInfo($this->info);
				}				
				
				// let's see if the object knows what this bit is
				$stop = $obj->parseBit($bit, $is_last, $this->last_bit_id);
				
				if ($stop === WEB_SERVICE_ERROR)
					continue;
				
				// see if we're supposed to cache this page - only if caching is turned on
				$cache_page = $obj->cachePage();
				
				// see if we need to add strings to the string table
				$this->lumStrings->addCustom($obj->getStrings());

				// we pass this into each subsequent bit plugin in case it needs it
				// for instance, the Pages plugin uses it to verify parent pages
				$this->last_bit_id = $obj->getLastBitId();
				if (method_exists($potential_plugin, 'getOutgoingInfo'))
				{
					$this->info = ($obj->getOutgoingInfo() ? $obj->getOutgoingInfo() : $this->info);
				}

				
				// we don't need you any more!
				unset($obj);
				
				// return what we've found!
				return array($stop, $cache_page);
			}
		}
	}
	
	public function setLastBitId($id)
	{
		$this->last_bit_id = $id;
	}

	public function getLastBitId($id)
	{
		return $this->last_bit_id;
	}

	
	private function addStringsToTable($strings)
	{
		
		if (is_array($strings) && count($strings) > 0)
		{
			if (isset($GLOBALS['ADD_STRINGS']))
				$GLOBALS['ADD_STRINGS'] = array_merge($GLOBALS['ADD_STRINGS'], $strings);
			else
				$GLOBALS['ADD_STRINGS'] = $strings;
		}
	}
	
	private function getPageNotAvailable()
	{
		$page_data = new LuminancePage();
		$page_data->setHeader('503');
		
		if (is_file(PAGE_NOT_AVAILABLE))
		{
			$page_data->setResult(file_get_contents(PAGE_NOT_AVAILABLE));
		}
		else 
		{
			$page_data->setResult('<p>Page not available</p>');
		}
		return $page_data;
	}	
	
	private function getPageNotFound()
	{
		$page_data = new LuminancePage();
		$page_data->setHeader('404');
		
		if (is_file(PAGE_NOT_FOUND))
		{
			$page_data->setResult($test);
		}
		else 
		{
			$page_data->setResult('<p>Page not found</p>');
		}
		return $page_data;
	}	
	
	private function build()
	{
		// this function stores the entire page so we can
		// do more with it after the page is built
		
		// start the buffer capture
		ob_start();
		
		// Do we need to parse the url. We may have already done it
		$url_bits = $this->getBits();

		// default to the home page if there are no bits
		if ((count($url_bits) == 1 && $url_bits[0] == '') || count($url_bits) == 0)
		{
			$url_bits[0] = DEFAULT_HOME_SEO_NAME;
		}

		// so we know if a bit resulted in any content or not
		$found_content = false;
		
		// so we know if we should cache the page or not
		// fyi, the caching define is used in the caching class
		// this function doesn't care about that right now
		$cache_page = true;
			
		/** 
		 * At this point we need to check our bits against
		 * our plugins to see what kind of page we're supposed
		 * to display.
		 */
			
		for ($i = 0;$i<count($url_bits);$i++)
		{
			$is_last = ($i == (count($url_bits) - 1));
			$bit = $url_bits[$i];
			if ($bit == '')
				continue;
			
			/*lum_logMe("//=====<br/>
			bit: $bit<br/>
			=====<br/><br/>");
			lum_logMe('is last:');
			lum_logVar($is_last);*/
			
			
			/**
			 *
			 * Look for bits by the name in the url
			 * For instance:
			 * 
			 * http://www.yourdomain.com/en/category/page
			 * 
			 * URL bits look like this:
			 * 
			 * $url_bits[0] = 'en';
			 * $url_bits[1] = 'category';
			 * $url_bits[2] = 'page';
			 * 
			 * We'll now iterate through each bit
			 * and look for it in each plugin until 
			 * we find a match.
			 *
			 * If we ultimately cannot find a bit
			 * we'll either display error 404 not found.
			 * 
			 */

			// a system bit is a hard coded bit that's set in defines.inc.php
			if ($this->isAdminPage($bit))
			{
				// we found content!
				$found_content = true;
				
				// don't cache system pages
				$cache_page = false;

				// break out of the loop
				break;
			}
			
			/**
			 *
			 * this will check the bit against all valid
			 * plugins. If there is any content it will be
			 * output to the buffer and captured here.
			 *
			 * if we should stop processing the plugin will
			 * let us know.
			 * 
			 **/
			
			list($stop, $cache_page) = $this->findPluginContent($bit, $is_last);

			if ($stop)
			{
				// we've found our content!
				$found_content = true;
				break;
			}
		}	
	
		if (!$found_content)
		{
			// return an error 404!
			return false;
		}
		
		$page_data = ob_get_contents();
		ob_end_clean();
	
		// now we do our string replacements
		// string replacements based on language!
		// meta title, key words, string table, etc!
		
		$page_data = $this->replaceStrings($page_data);	

		$c = new LuminanceCache(HTML_CACHE_PATH, _USE_DEBUG);

		if ($cache_page == true && _USE_CACHING)
		{
			// this is permanently stored in the cache until the page content 
			// is changed in the admintools. We'll find the page cache on 
			// change and delete it.
			$path = "";
			$cache_path = HTML_CACHE_PATH.CURRENT_DISPLAY_TARGET;
			if (!is_dir($cache_path))
			{
				mkdir($cache_path, 0775);
			}
			
			if (!$this->lumRegistry->language->is_default)
			{
				$cache_path .= '/'.$this->lumRegistry->language->lang_code;
				if (!is_dir($cache_path))
				{
					mkdir($cache_path, 0775);
				}
			}			
			
			for ($i = 0;$i<count($url_bits);$i++)
			{
				$folder = '/'.$url_bits[$i];
				if ($folder == '/'.DEFAULT_HOME_SEO_NAME)
					$folder = '';
					
				$cache_path = $cache_path.$folder;
				if (!is_dir($cache_path))
				{
					mkdir($cache_path, 0775);
				}
				$path = $cache_path;	
			}

			$c->store_html($path, $page_data);
		}

		return new LuminancePage("200", $page_data); // 200 OK Page Header and the data
	}
		
	private function isAdminPage($bit)
	{
		if ($bit == TOOLS_STEP)
		{
			// load the user authentication
			include(ADMIN_PATH.'admin.php');
			return true;
		}
		return false;			
	}
	
	public function replaceStrings($page_data)
	{
		$string_table = $this->lumStrings->getStringTable();
		
		
		
		// these were set dynamically by plugins
		$custom_string_table = $this->lumStrings->getCustomStringTable();
	
		if (isset($custom_string_table) && is_array($custom_string_table))
		{
			$custom_string_table = str_replace('[META_TITLE]', $string_table['[META_TITLE]'], $custom_string_table );
			$custom_string_table = str_replace('[META_DESCRIPTION]', $string_table['[META_DESCRIPTION]'], $custom_string_table );
			$custom_string_table = str_replace('[META_KEYWORDS]', $string_table['[META_KEYWORDS]'], $custom_string_table );			
			$string_table = array_merge($string_table, $custom_string_table);
		}			

		if (isset($string_table) && is_array($string_table))
		{
			// we're doing this twice on purpose to catch any secondary replacements
			$page_data = strtr($page_data, $string_table);
			$page_data = strtr($page_data, $string_table);
		}	
		return $page_data;		
	}
	
	public function parseUrl($url)
	{
		$url_split = explode('#', $url);
		$url = $url_split[0];
		$url_split = explode('?', $url);
		$url = $url_split[0];
		$url = substr($url,1);
		$this->bits = explode("/", $url);
		
		if (count($this->bits) == 1 && $this->bits[0] == '')
		{
			$this->bits[0] = DEFAULT_HOME_SEO_NAME;
		}		
		
		return $this->bits;
	}
	
	public function getBits()
	{
		return $this->bits;
	}

	public function setBits($bits)
	{
		$this->bits = $bits;
	}

}

class LuminancePage
{
	
	private $result = '';
	private $header = ''; // the http header value
	
	function __construct($header = null, $result = null)
	{
		$this->result = $result;
		$this->header = $header;
	}
	
	public function getResult()
	{
		return $this->result;
	}
	
	public function setResult($value)
	{
		$this->result = $value;
	}	

	public function getHeader()
	{
		return $this->header;
	}
	
	public function setHeader($value)
	{
		$this->header = $value;
	}
}

?>
