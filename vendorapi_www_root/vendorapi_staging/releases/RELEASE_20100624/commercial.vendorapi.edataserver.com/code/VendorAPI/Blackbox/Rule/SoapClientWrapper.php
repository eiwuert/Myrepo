<?php
/**
 * Wraps a simple soap client
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_Blackbox_Rule_SoapClientWrapper 
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
	 * Construct a soap wrapper?
	 * @param string $url
	 * @param array $options
	 */
	public function __construct($url, array $options = array()) 
	{
		$this->url = $url;
		$this->options = $options;
	}
	
	/**
	 * Set an option to be passed to the client
	 * on creation of the client
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
	 * defined in this thing. 
	 * @return SoapClient
	 */
	protected function getClient() 
	{
		if (!$this->client instanceof SoapClient)
		{
			if(isset($this->options['login']) && isset($this->options['password']))
			{
				$context = stream_context_create(array(
				    'http' => array('timeout' => 1, 
			        'header'  => "Authorization: Basic " . base64_encode($this->options['login'] .":" . $this->options['password'])
				    )
				));
			}
			else
			{
				$context = stream_context_create(array());
			}
                //attempting to connect to the WSDL beforehand because SOAP throws a fatal error
                //if it can not connection which is retarted because obvisously if a remote system is
                //down we should bring down our system.
                    if(file_get_contents($this->url, false, $context))
                    {

                        $this->client = new SoapClient($this->url, $this->options);
                    }
                    else
                    {
                        throw new Exception("ERROR:SOAP URL {$this->url} can not be reached");
                    }

		}
		return $this->client;
	}
	
	/**
	 * Grabs a client and invokes the method on it and returns
	 * back whatever  response.
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$client = $this->getClient();

		$timeout = ini_get("default_socket_timeout");
		ini_set("default_socket_timeout",5);
		try
		{
			$response = call_user_func_array(array($client, $method), $args);
			ini_set("default_socket_timeout",$timeout);
		}
		catch (Exception $e)
		{
			ini_set("default_socket_timeout",$timeout);
			throw $e;
		}

		return $response;
	}
}
