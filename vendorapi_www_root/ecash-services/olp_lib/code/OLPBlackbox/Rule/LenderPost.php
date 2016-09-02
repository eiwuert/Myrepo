<?php

/**
 * Rule which posts information to blackbox lenders (non-enterprise).
 *
 * @package OLPBlackbox
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_LenderPost extends OLPBlackbox_Rule implements OLPBlackbox_ISellRule, OLPBlackbox_Rule_IObservable
{
	
	/**
	 * Whether this lender post is a verify post
	 *
	 * @var string
	 */
	protected $post_type = LenderAPI_Generic_Client::POST_TYPE_STANDARD;
	
	/**
	 * @var array
	 */
	protected $observers = array();
	
	/**
	 * 
	 * @var LenderAPI_IClient
	 */
	protected $client;
	
	const EVENT_RECEIVED_RESPONSE = 1;
	
	/**
	 * Construct a LenderPost rule (constructor sets up stat names/event names)
	 * 
	 * @param LenderAPI_IClient $client
	 * @return void
	 */
	public function __construct(LenderAPI_IClient $client)
	{
		$this->event_name = 'POST';
		$this->client = $client;
	}
	
	/**
	 * Override parent canRun so that this rule always runs (there is no data
	 * to check for).
	 * 
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data.
	 * @return bool TRUE if the rule should be run.
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Post to a blackbox lender. (non-enterprise)
	 * 
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data 
	 * @return bool 
	 * @see Blackbox_Rule::runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$data_sources = $this->getDataSources($data, $state_data);
				
		$this->hitLeadSentStats($state_data->campaign_name);
		
		$exception = NULL;
		
		try
		{
			/* @var $client LenderAPI_Generic_Client */
			$this->client->postLead($state_data->campaign_name, $data_sources);
		}
		catch (LenderAPI_XMLParseException $e)
		{
			$exception = new Blackbox_Exception(
				'Error posting to lender with operation '.$e->operation
			);
		}
		catch (Exception $e)
		{
			$exception = new Blackbox_Exception(
				'unable to post to vendor: '.$e->getMessage()
			);
		}
		
		$response = $this->client->getResponse();
		$this->notifyOfResponse($response, $data, $state_data);
		
		if ($response->getTimeoutExceeded())
		{
			$this->postTimeoutExceeded($state_data->campaign_name);
		}
		elseif (!$response->getDataReceived())
		{
			$this->emptyResponse($state_data->campaign_name);
		}
		else 
		{
			$this->responseReceived($state_data->campaign_name);
		}
		
		
		$valid = FALSE;
		
		if ($response->Is_Success())
		{
			$state_data->lender_post_result = $response;
			$valid = TRUE;
		}
		
		// save persistent data from response
		if (!is_array($state_data->lender_post_persistent_data)) 
		{
			$state_data->lender_post_persistent_data = array();
		}
		if (is_array($response->getPersistentData()))
		{
			$state_data->lender_post_persistent_data = $this->mergePersistentData($state_data->lender_post_persistent_data, $response->getPersistentData());
		}
		
		if ($exception instanceof Exception)
		{
			throw $exception;
		}
		
		return $valid;
	}
	
	/**
	 * Errors should also be recorded to the blackbox_post_exception table.
	 * @param Blackbox_Exception $e Exception that happened during isValid()
	 * @param Blackbox_Data $data Info about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 *
	 * @return bool
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_StateData $state_data)
	{
		try
		{
			$this->logPostError($e->getMessage(), $data, $state_data);
		}
		catch (Blackbox_Exception $e)
		{
			$this->getConfig()->applog->Write(
				'could not log exception ' . $e->getMessage() 
				. ' to blackbox_post_exception table'
			);
		}
		return parent::onError($e, $data, $state_data);
	}
	
	/**
	 * Log an error in the blackbox_log table.
	 * 
	 * @param string $error The error that occurred.
	 * @param Blackbox_Data $data The data about the application.
	 * @param Blackbox_IStateData $state_data Data about the target we're being
	 * run for.
	 * @return void 
	 */
	protected function logPostError($error, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$query = sprintf("INSERT INTO blackbox_post_exception
				(application_id, winner, date_modified, date_created, exception)
			VALUES ('%s', '%s', NOW(), NOW(), '%s')",
			$data->application_id,
			$state_data->campaign_name,
			$this->getConfig()->olp_db->escape($error)
		);
		
		try
		{
			$this->getConfig()->olp_db->Query(
				$this->getConfig()->olp_db->db_info['db'], $query
			);
		} 
		catch (MySQL_Exception $e)
		{
			throw new Blackbox_Exception('unable to log post error: ' . $e->getMessage());
		}
	}
	
	/**
	 * Records stats when an empty response is received.
	 * 
	 * @param string $property_short The name of the campaign.
	 * @return void
	 */
	protected function emptyResponse($property_short)
	{
		Stats_OLP_Client::getInstance()->hitStat("blank_response_{$property_short}");
	}
	
	/**
	 * Saves data from the persistent tag in the response xml. Does a recursive merge overwriting any existing keys
	 * 
	 * @param array $existing_data
	 * @param array $new_data
	 * @return array
	 */
	protected function mergePersistentData($existing_data, $new_data)
	{
		foreach ($new_data as $key => $value)
		{
			if (is_array($value) && isset($existing_data[$key]))
			{
				$existing_data[$key] = $this->mergePersistentData($existing_data[$key], $value);
			}
			else
			{
				$existing_data[$key] = $value;
			}
		}
		
		return $existing_data;
	}
	
	/**
	 * Add a particular stat for fail status.
	 * @param Blackbox_Data $data Info about the application being processed.
	 * @param Blackbox_IStateData $state_data Information about blackbox run.
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		Stats_OLP_Client::getInstance()->hitStat("reject_{$state_data->campaign_name}");
		parent::onInvalid($data, $state_data);
	}
	
	/**
	 * Record stats for when a post to a lender times out.
	 * 
	 * @param string $campaign_name The name of the campaign name of the lender.
	 * @return void
	 */
	protected function postTimeoutExceeded($campaign_name)
	{
		$helper = new Stats_Helper(Stats_OLP_Client::getInstance());
		$helper->hitVendorPostTimeoutStats($campaign_name);
	}
	
	/**
	 * Hits stats and performs actions when a lendor post response was received.
	 * 
	 * @param string $campaign_name The campaign name of the lender.
	 * @return void
	 */
	protected function responseReceived($campaign_name)
	{
		Stats_OLP_Client::getInstance()->hitStat("response_received_$campaign_name");
	}
	
	/**
	 * Hit stats that need to be recorded when a lead is posted to a lender.
	 * @param string $property_short campaign name
	 * @return void
	 */
	protected function hitLeadSentStats($property_short)
	{
		Stats_OLP_Client::getInstance()->hitStat("lead_sent_{$property_short}");
	}
	
	
	/**
	 * Returns the data sources to pass to the LenderAPI client.
	 * @param Blackbox_Data $data Data about the application.
	 * @param Blackbox_IStateData $state_data The current state of the Blackbox call stack.
	 * @return array List of iterable LenderAPI_BlackboxDataSource objects. 
	 */
	protected function getDataSources(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// in addition to the data sources added below, the client will add
		// target-specific data sources which have campaign constants
		// @see LenderAPI_Generic_Client
		$data_sources = array(
			'config' => new ArrayObject(array('mode' => OLPBlackbox_Config::getInstance()->mode)), 	
		);
		$data_sources['application'] = new LenderAPI_BlackboxDataSource(
			$data, 'application', $this->getConfig()->memcache
		);
		$data_sources['campaign'] = new LenderAPI_BlackboxDataSource(
			$state_data, 'campaign', $this->getConfig()->memcache
		);
		
		if (!is_array($state_data->lender_post_persistent_data))
		{
			$state_data->lender_post_persistent_data = array();
		}
		$data_sources['persistent'] = new ArrayObject($state_data->lender_post_persistent_data);
		
		if (isset($state_data->brick_and_mortar_store))
		{
			$data_sources['brick_and_mortar_store'] = new ArrayObject($state_data->brick_and_mortar_store);
		}
		
		return $data_sources;
	}
	
	/**
	 * Sets the post type for the rule
	 *
	 * @param string $post_type
	 * @return void
	 */
	public function setPostType($post_type)
	{
		$this->post_type = $post_type;
	}

	/**
	 * Override to be more descriptive.
	 * @return string
	 */
	public function __toString()
	{
		return 'Rule: ' . get_class($this) . ' [' . $this->event_name . "]\n";
	}
	
	
	/**
	 * Attach a new observer to this rule
	 * @param OLPBlackbox_Rule_IObserver $observer
	 * @return void
	 */
	public function attach(OLPBlackbox_Rule_IObserver $observer)
	{
		if (!in_array($observer, $this->observers))
		{
			$this->observers[] = $observer;
		}
	}

	/**
	 * Detach an observer from this rule
	 * @param OLPBlackbox_Rule_IObserver $observer
	 * @return void
	 */
	public function detach(OLPBlackbox_Rule_IObserver $observer)
	{
		$key = array_search($observer, $this->observers);
		if ($key !== FALSE)
		{
			unset($this->observers[$key]);
		}
	}
	
	/**
	 * Notify all observers that a response was received
	 * @param LenderAPI_IResponse $response
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function notifyOfResponse(LenderAPI_IResponse $response, 
		Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_data = new stdClass();
		$event_data->response = $response;
		$event_data->application_id = $data->application_id;
		$event_data->campaign_name = $state_data->campaign_name;
		$event_data->target_name = $state_data->name;
		$event_data->post_type = $data->post_type;
		$this->notify(self::EVENT_RECEIVED_RESPONSE, $event_data);
	}
	
	/**
	 * Notify all observers of an event with data
	 * @param string $event
	 * @param mixed $data
	 * @return void
	 */
	protected function notify($event, $data)
	{
		foreach ($this->observers as $observer)
		{
			$observer->onNotification($this, $event, $data);
		}
	}
}
?>
