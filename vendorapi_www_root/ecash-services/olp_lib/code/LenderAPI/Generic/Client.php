<?php

/**
 * Client to post to Blackbox lenders.
 *
 * Usage:
 *
 * $lender = LenderAPI_Factory_Client::getClient('LOCAL');
 * $response = $lender->postLead('grv_t1', $data_sources);
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Rodric Glaser <Rodric.Glaser@SellingSource.com>
 * @package LenderAPI
 */
class LenderAPI_Generic_Client implements LenderAPI_IClient
{
	/**
	 * The environment (mode) to run in.
	 * @var string
	 */
	protected $environment;

	/**
	 * @var DB_Database_1
	 */
	protected $db;

	/**
	 * @var LenderAPI_Response;
	 */
	protected $response;
	
	/**
	 * @var LenderAPI_XslTransformer
	 */
	protected $request_xsl;
	
	/**
	 * @var LenderAPI_XslTransformer
	 */
	protected $response_xsl;
	
	/**
	 * Type of lender post to use
	 *
	 * @var string
	 */
	protected $post_type;
	
	/**
	 * Holds a fake response string.
	 * @var string
	 */
	protected $fake_response;
	
	/**
	 * Constants for the different post types
	 *
	 */
	const POST_TYPE_STANDARD = 'post';
	const POST_TYPE_VERIFY = 'verify_post';
	
	/**
	 * Returns the response object this client will/does populate.
	 *
	 * @return LenderAPI_Response
	 */
	public function getResponse() 
	{ 
		return $this->response; 
	}

	/**
	 * Create a LenderAPI Client for a specific environment.
	 *
	 * @param string $environment What environment to run in (LOCAL, RC, LIVE)
	 * @param DB_Database_1 $db A connection to the Blackbox Admin database.
	 * @param string $post_type What type of post to run (post, verify_post)
	 * @return void
	 */
	public function __construct($environment = 'LOCAL', DB_Database_1 $db, $post_type)
	{
		$this->environment = $environment;
		$this->db = $db;
		$this->post_type = $post_type;
	}
	
	/**
	 * Returns a lender API response object, prepped for use.
	 *
	 * @return LenderAPI_Response
	 */
	protected function getResponseObject()
	{
		return new LenderAPI_Response();
	}
	
	/**
	 * Post a lead to a regular Blackbox vendor.
	 *
	 * @param string $property_short Campaign to post to.
	 * @param mixed $data_sources A list of data sources which will be transformed
	 * into XML and used for the outgoing transfer.
	 * @param array $override_config An optional configuration array which will
	 * be merged (and override) the configuration pulled for the target specified
	 * by $property_short
	 * @return LenderAPI_Response
	 * @throws LenderAPI_ConfigurationException
	 * @see LenderAPI_IClient::postLead()
	 */
	public function postLead($property_short, $data_sources, array $override_config = array())
	{
		$this->response = $this->getResponseObject();
		
		$config_object = new LenderAPI_TargetConfig($this->db, $property_short);
		if ($override_config)
		{
			$config_object->setRuntimeOverride($override_config);
		}
		$post_config = $config_object->getConfig();
		
		$this->addCampaignConstantsTo($data_sources, $config_object);
		$this->initRequestXsl($post_config, $data_sources);
		$this->initResponseXsl($post_config, $data_sources);
		
		$this->sendRequest($post_config);
				
		return $this->response;
	}
	
	/**
	 * Extract the correct post method from a config array or throw an execption.
	 *
	 * @throws LenderAPI_ConfigurationException
	 * @param array $config The configuration to rummage through.
	 * @return string
	 */
	protected function getPostMethod(array $config)
	{
		$key = $this->getTargetDataKeyName('vendor_api_method', TRUE);
		
		if ($config[$key])
		{
			return $config[$key];
		}
		else
		{
			throw new LenderAPI_ConfigurationException(
				'unable to find valid post method in lender post configuration'
			);
		}
	}
	
	/**
	 * Extract the correct post url from a config array or throw an execption.
	 *
	 * @throws LenderAPI_ConfigurationException
	 * @param array $config The configuration to rummage through.
	 * @return string
	 */
	protected function getPostUrl(array $config)
	{
		$key = $this->getTargetDataKeyName('vendor_api_url', TRUE);
		return empty($config[$key]) ? NULL : $config[$key];
	}
	
	/**
	 * Extract the correct time out from a config array or throw an execption.
	 *
	 * @param array $config The configuration to rummage through.
	 * @return string|NULL
	 */
	protected function getTimeout(array $config)
	{
		$key = $this->getTargetDataKeyName('vendor_api_timeout', TRUE);
		return empty($config[$key]) ? NULL : $config[$key];
	}

	/**
	 * Initialize the outgoing XSL transoform object which will translate the
	 * data sources passed in into request XML using the post configuration.
	 *
	 * @param array $post_config The list of configuration items for posting
	 * to the lender this client is sending to. Has Xsl and urls, etc.
	 * @param Traversable|array $data_sources The list of data sources which the
	 * transformer transforms (after translating it into XML).
	 * @return null
	 */
	protected function initRequestXsl($post_config, $data_sources)
	{
		$this->request_xsl = new LenderAPI_XslTransformer();
		$key = $this->getTargetDataKeyName('outgoing_xsl');

		if (array_key_exists($key, $post_config))
		{
			$this->req_xsl = $post_config[$key];
			try
			{
				$this->request_xsl->setXsl($post_config[$key]);
			}
			catch (LenderAPI_XMLParseException $e)
			{
				$e->operation = 'Request XSL';
				throw $e;
			}

			foreach ($data_sources as $xpath => $data_source)
			{
				// TODO: Shouldn't we ask the data source what the path is? It has a key...
				$this->request_xsl->addDataSource($data_source, $xpath);
			}
		}
	}
	
	/**
	 * Initialize the response xsl transformer.
	 *
	 * @param array $post_config The Lender post data which holds the information about
	 * how to post to the lenders (which transforms to use, etc.)
	 * @param Traversable|array $data_sources The list of data sources which get added
	 * as paramaters to the xsl
	 * @return void
	 */
	protected function initResponseXsl($post_config, $data_sources)
	{
		$key = $this->getTargetDataKeyName('incoming_xsl');
		if (empty($post_config[$key]))
		{
			throw new LenderAPI_XMLParseException(
				"incoming_xsl for must be set", 'Response XSL'
			);
		}

		$this->res_xsl = $post_config[$key];

		$this->response_xsl = new LenderAPI_XslTransformer();

		try
		{
			$this->response_xsl->setXsl($post_config[$key]);
		}
		catch (LenderAPI_XMLParseException $e)
		{
			$e->operation = 'Response XSL';
			throw $e;
		}

		// Add constants, application data, and the campaign property short as parameters to the response xsl
		if (isset($data_sources['constant']))
		{
			foreach ($data_sources['constant'] as $name => $value)
			{
				$this->response_xsl->addParameter($name, $value);
			}
		}
		if (isset($data_sources['application']))
		{
			foreach ($data_sources['application'] as $name => $value)
			{
				$this->response_xsl->addParameter('app_'.$name, $value);
			}
		}
		if (isset($data_sources['brick_and_mortar_store']))
		{
			foreach ($data_sources['brick_and_mortar_store'] as $name => $value)
			{
				$this->response_xsl->addParameter('brick_and_mortar_store_'.$name, $value);
			}
		}
		if (isset($data_sources['campaign']))
		{
			foreach ($data_sources['campaign'] as $campaign_property_short)
			{
				$this->response_xsl->addParameter('campaign_property_short', $campaign_property_short);
			}
		}
		
		$this->response->setTransform($this->response_xsl);
	}
	
	/**
	 * Uses a specialized iterator to read header values out of the post config.
	 *
	 * @param array|Traversable $post_config A dictionary of items to configure
	 * how to post to this lender. The headers will be extracted from this.
	 * @return array
	 */
	protected function getHeadersFromConfig($post_config)
	{
		$headers = array();
		try
		{
			foreach (new LenderAPI_HttpHeadersIterator($post_config, $this->post_type) as $header_key => $header_value)
			{
				$headers[$header_key] = $header_value;
			}
		}
		catch (InvalidArgumentException $e)
		{
			// pass, no custom headers
		}
		return $headers;
	}
	
	/**
	 * Use a transport object and transform object to send a request to a lender.
	 *
	 * @param array $post_config The configuration for posting to this lender
	 * which will contain the header information we need to retrieve.
	 * @return void
	 */
	protected function sendRequest($post_config)
	{
		$transport = $this->getTransportObject(
			$this->getPostMethod($post_config), 
			$this->getPostUrl($post_config),
			$this->getTimeout($post_config)
		);
		$transport->setHeaders($this->getHeadersFromConfig($post_config));

		try
		{
			$this->req_xml = $this->request_xsl->getSourceDocument();
			$this->req_xsl = $this->request_xsl->getXsl();

			$transport->send(
				$this->request_xsl->transform()
			);
		}
		catch (LenderAPI_XMLParseException $e)
		{
			if (empty($e->operation)) $e->operation = 'Transform Send';
			throw $e;
		}
		catch (Exception $e)
		{
			$transport->response->exception = $e->__toString();
		}
	}

	/**
	 * Add a constant data source with target specific items.
	 *
	 * These are items that can be entered in blackbox admin such as:
	 * username => sellingsource1
	 *
	 * They are set per campaign/target but they never change. We'll wrap them
	 * in a data source that knows how to extract them from a traversable object.
	 *
	 * @param ArrayObject|array &$data_sources The data sources list to add the
	 * new data source to.
	 * @param LenderAPI_TargetConfig $target_config The configuration for the
	 * campaign we're posting to.
	 * @return void This function works by reference on argument $data_sources
	 */
	protected function addCampaignConstantsTo(&$data_sources, LenderAPI_TargetConfig $target_config)
	{
		if ($target_config->getConstants())
		{
			$data_sources['constant'] = new ArrayObject($target_config->getConstants());
		}
	}

	/**
	 * Returns the transport object, assumedly for mocking.
	 * @param string $method The method to use, such as GET or POST_SOAP, to use
	 * to post to the lender.
	 * @param string $url the URL to post to.
	 * @param int|NULL $timeout Number of seconds before request time out.
	 * @return LenderAPI_Transport
	 */
	protected function getTransportObject($method, $url = '', $timeout = NULL)
	{
		if (strcasecmp($method, 'LOOPBACK') == 0)
		{
			return new LenderAPI_Transport_Loopback($this->response, $this->response_xsl);
		}
		if (strcasecmp($method, 'EMAIL') == 0)
		{
			return new LenderAPI_Transport_Email($this->response, $this->request_xsl, $this->response_xsl);
		}
		elseif (strcasecmp($method, 'FAKE_RESPONSE') == 0)
		{
			return new LenderAPI_Transport_FakeResponse($this->fake_response, $url, $method, $timeout, $this->response);
		}
		else
		{
			return new LenderAPI_Transport($url, $method, $timeout, $this->response);
		}
	}
	
	/**
	 * gets the key name to use to get the data from target data
	 *
	 * @param string $name
	 * @param bool $include_environment
	 * @return string
	 */
	protected function getTargetDataKeyName($name, $include_environment = FALSE)
	{
		$key = $name;
		$key .= ($this->post_type == LenderAPI_Generic_Client::POST_TYPE_VERIFY) ? '_verify' : '';
		$key .= $include_environment ? '_'.$this->environment : '';
		return $key;
	}
	
	/**
	 * Set the fake response
	 * @param string $res
	 * @return unknown_type
	 */
	public function setFakeResponse($res) 
	{
		$this->fake_response = $res;
	}
}
?>
