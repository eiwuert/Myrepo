<?php
require_once(BFW_CODE_DIR.'/Ajax_Response.php');
require_once(BFW_CODE_DIR.'/Ajax_Request.php');

class Ajax_Handler
{
		protected $collected_data;
		protected $applog;
		protected $olp_db;
		protected $config;
		
		protected $ajax_dir;
		
		/**
		 * Sets up the Ajax_Handler stuff so that it's ready to handle Ajax.
		 *
		 * @param mixed $applog
		 * @param mixed $olp_db
		 * @param mixed $config
		 * @param mixed $ajax_dir
		 */
		public function __construct($applog = NULL, $olp_db = NULL, $config = NULL, $ajax_dir = NULL)
		{
			$this->config = is_object($config) ? $config : new stdClass();
			if(is_null($ajax_dir) || $this->setAjaxDir($ajax_dir) === false)
			{
				$this->ajax_dir = dirname(__FILE__).'/';
			}
			if($applog instanceof Applog)
			{
				$this->applog = $applog;
			}
			else
			{
				$this->applog = OLP_Applog_Singleton::Get_Instance(
					APPLOG_SUBDIRECTORY, 
					APPLOG_SIZE_LIMIT, 
					APPLOG_FILE_LIMIT, 
					isset($this->config->site_name) ? $this->config->site_name : 'Unknown Site', 
					APPLOG_ROTATE, 
					APPLOG_UMASK);
			}
					
			if($olp_db instanceof MySQL_Wrapper)
			{
				$this->olp_db = $olp_db;
			}
			else
			{
				$this->olp_db = Setup_DB::Get_Instance('blackbox', 
					isset($this->config->mode) ? $this->config->mode : BFW_MODE);
			}
		}
		/**
		 * Sets the directory we'll look for Ajax_Request
		 * classes in.
		 *
		 * @param string $dir
		 * @return boolean
		 */
		public function setAjaxDir($dir)
		{
			if(is_dir($dir))
			{
				$this->ajax_dir = $dir;
				return true;
			}
			return false;
			
		}
		
		/**
		 * Logs a string to the applog
		 *
		 * @param string $str
		 */
		private function Log($str)
		{
			$this->applog->Write($str);
		}
		
		/**
		 * Tries to load the request class file 
		 * if the request class doesn't already exist.
		 *
		 * @param string $request
		 */
		private function loadRequestFile($request)
		{
			if(!class_exists($request))
			{
				$file = $this->ajax_dir.strtolower($request).'.php';
				if(file_exists($file))
				{
					require($file);
				}
				else 
				{
					$this->Log("Ajax_Handler::loadRequestFile could not load file for request $request.");
				}
			}
		}
		
		/**
		 * Attempts to create an object of
		 * $class type. Expects $class to be 
		 * a subclass of Ajax_Request. Returns
		 * either the object or false if there was
		 * an error.
		 *
		 * @param string $request
		 * @return Ajax_Request
		 */
		private function getRequestObject($request)
		{
			$return = false;
			if(!empty($request))
			{
				$this->loadRequestFile($request);
			}
			if(class_exists($request))
			{
				try 
				{
					$cr = new ReflectionClass($request);
					if($cr->isSubclassOf('Ajax_Request'))
					{
						$return = new $request(
							$this->collected_data, 
							$this->olp_db, 
							$this->config, 
							$this->applog);
					}
					else 
					{
						$this->Log("Non-Ajax_Request class($class) requested through Ajax_Handler\n");
					}
				}
				catch (Exception $e)
				{
					$this->Log("Exception: ".$e->getMessage());	
					$return = false;
				}
			}
			return $return;
		}
		
		/**
		 * Creates a request object, and gets a response. Then returns
		 * the response in the 'expected' format. Will return false if 
		 * an error occurs.
		 *
		 * @param array $collected_data
		 * @return mixed
		 */
		public function Handle_Request($collected_data)
		{
			$return = $response = false;
			$this->collected_data = $collected_data;
			$request = $this->getRequestObject($this->collected_data['request']);
			if(is_object($request))
			{
				$expected = isset($this->collected_data['expected']) ? strtolower($this->collected_data['expected']) : 'json';
				try 
				{
					$response = $request->Generate_Response();
				}
				catch (Exception $e)
				{
					$this->Log("Exception in Ajax_Handler: ".$e->getMessage());
					$response = FALSE;
				}	
			}

			$ret_obj = new stdClass();
			if($response instanceof Ajax_Response)
			{
				switch($expected)
				{
					case 'xml': 
						$ret_obj->return = $response->Get_As_XML(); 
						$ret_obj->content_type = 'text/xml';
						break;
					default: 
						case 'json': 
							$ret_obj->return = $response->Get_As_JSON(); 
							$ret_obj->content_type = 'text/plain';
						break;
				}
			}
			else 
			{
				$ret_obj->return = $ret_obj;
			}
			return $ret_obj;
		}
	}