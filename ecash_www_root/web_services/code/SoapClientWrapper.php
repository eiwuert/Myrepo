<?php
/**
 * Wraps a simple soap client
 * 
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 */
class SoapClientWrapper 
{
	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var SoapClient
	 */
	protected $client;

	/**
	 *
	 * @var Applog
	 */
	protected $log;

	/**
	 * Construct a soap wrapper?
	 * 
	 * @param string $url
	 * @param array $options
	 * @param Applog $log
	 */
	public function __construct($url, $options = array(), $log)
	{
		$this->url = $url;
		$this->options = $options;
		$this->log = $log;
	}

	/**
	 * Set an option to be passed to the client
	 * on creation of the client
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setOption($key, $value) 
	{
		$this->options[$key] = $value;
	}

	/**
	 * Returns a soap client initialized with the url/options
	 * defined in this thing
	 * 
	 * @return SoapClient
	 */
	protected function getClient() 
	{
		if (!$this->client instanceof SoapClient)
		{
			/*
			 * There is a bug when using SoapClient with xdebug enabled that causes the SoapClient
			 * constructor to throw a fatal error if a wsdl is unreachable. This will NOT occur if
			 * XDebug is not loaded or is disabled. This will emit warnings on production but that
			 * is all.
			 */
			try
			{
				$this->client = new SoapClient($this->url, $this->options);
			}
			catch (Exception $e)
			{
				throw new Exception("ERROR:SOAP URL {$this->url} can not be reached [{$e->getMessage()}]");
			}
		}

		return $this->client;
	}

	/**
	 * Grabs a client and invokes the method on it and returns
	 * back whatever response
	 * 
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$client = $this->getClient();

		/**
		 * The application search function can be time consuming.
		 * I set this initially at 10 seconds, to allow for a 4 digit
		 * application 'starts_with' search to complete, results may vary...
		 */
		if ($method === 'applicationSearch')
		{
			$new_timeout = 90;
		}
		else
		{
			$new_timeout = 60;
		}
		$prev_timeout = ini_get("default_socket_timeout");
		ini_set("default_socket_timeout", $new_timeout);

		
		$tries = 0;
		do
		{
			try
			{
				$e = NULL;
				$response = call_user_func_array(array($client, $method), $args);
				ini_set("default_socket_timeout", $prev_timeout);
			}
			catch (Exception $e)
			{
				ini_set("default_socket_timeout", $prev_timeout);

				$this->log->Write($client->__getLastRequest());
				$this->log->Write($client->__getLastResponse());
				$this->log->Write($e->getMessage() . ' ' . $e->getTraceAsString());

				if ($tries++ < 3 &&
					$e->getMessage() == "Error Fetching http headers")
				{
					sleep(1);
				}
				else
				{
					throw $e;
				}
			}
		} while (!is_null($e));

		if (LOG_SERVICE_REQUEST)
		{
			$this->log->Write($client->__getLastRequest());
		}

		if (LOG_SERVICE_RESPONSE)
		{
			$this->log->Write($client->__getLastResponse());
		}
		
		return $response;
	}
}
