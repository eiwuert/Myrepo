<?php
/**
 * webservice factory
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 * 
 */
abstract class WebServices_Factory 
{
	/**
	 * Webservice buffer of calls
	 *
	 * @var WebServices_Buffer
	 */
	protected $buffer;

	/**
	 * Webservice function cache
	 *
	 * @var array
	 */
	protected $cache = NULL;
	/**
	 * Webservice config
	 *
	 * @var stdobj
	 */
	protected $config;
	
	/**
	 * returns the current WebServices_Buffer
	 *
	 * @return WebServices_Buffer
	 */
	protected function getBuffer()
	{
		if (empty($this->buffer))
		{
			$this->buffer = new WebServices_Buffer($this->getConfigSettings()->aggregate_log);
		}
		return $this->buffer;
	}

	abstract protected function getConfigSettings();

	public function getAppLog()
	{
		return $this->getConfigSettings()->app_log;
	}

	/**
	 * returns the current WebServices_ICache
	 *
	 * @return WebServices_ICache
	 */
	public function getCache()
	{
		if (is_null($this->cache))
		{
			$this->cache = new WebServices_Cache($this->getAppLog());
		}

		return $this->cache;
	}
	/**
	 * 
	 *
	 * @return void
	 */
	public function replaceCache(WebServices_ICache $cache)
	{
		$this->cache = $cache;
	}
	/**
	 * returns the webservice client requested
	 *
	 * @param String $service_name
	 * @param array $class_map
	 * @return mixed
	 */
	public function getWebService($service_name, $class_map = NULL, $options = NULL)
	{
		$config = $this->getConfigSettings();
		switch (strtolower($service_name))
		{
			case 'application':
				return new ECash_WebService_AppClient( 
					$config->app_log, 
					new ECash_BufferedWebService(
							$config->app_log, 
							$config->app_url,
							$config->user, 
							$config->pass,
							$service_name, 
							$config->aggregate_url,
							$this->getBuffer(),
							$class_map
						),
					$this->getCache()
				);
			break;
			case 'loanaction':
				return new ECash_WebService_LoanActionClient($config->app_log, 
								new ECash_BufferedWebService(
												$config->app_log, 
												$config->loanaction_url, 													$config->user, 
												$config->pass,
												$service_name, 
												$config->aggregate_url,
												$this->getBuffer(),
												$class_map
												),
												$this->getCache()
											);
			break;		
			case 'inquiry':
				return new ECash_WebService_InquiryClient($config->inquiry_log, 
								new ECash_BufferedWebService(
												$config->inquiry_log, 
												$config->inquiry_url, 													$config->user, 
												$config->pass,
												$service_name, 
												$config->aggregate_url, 												$this->getBuffer(),
												$class_map
												)
											);
			break;
			case 'document':
				return new ECash_WebService_DocumentClient(
					$config->document_log,
					new ECash_BufferedWebService(
						$config->document_log, 
						$config->document_url,
						$config->user,
						$config->pass,
						$service_name, 
						$config->aggregate_url,
						$this->getBuffer(),
						$class_map
					)
				);
			break;
			case 'query':
				return new ECash_WebService_QueryClient($config->app_log, 
								new ECash_BufferedWebService(
												$config->app_log, 
												$config->queryservice_url, 													
												$config->user, 
												$config->pass,
												$service_name, 
												$config->aggregate_url,
												$this->getBuffer(),
												$class_map,
												$options === NULL ? array('features' => SOAP_SINGLE_ELEMENT_ARRAYS) : $options
												),
												$this->getCache()
											);
			break;			
			default:
				throw new Exception("Web Service $service_name does not exist");		
		}
	}
}

?>
