<?php

/**
 * Basic Tribal rule
 */
class VendorAPI_Blackbox_Rule_Tribal extends VendorAPI_Blackbox_Rule
{
	const EVENT_ON_CALL = "onCall";

	/**
	 * @var TSS_Tribal_Call
	 */
	protected $call;

	/**
	 * @var array VendorAPI_Blackbox_Tribal_ICallObserver[]
	 */
	protected $observers = array();

	/**
	 * @var bool
	 */
	protected $rework;

	/**
	 * Fail the rule when an error
	 * occurs.
	 *
	 * @var string
	 */
	protected $fail_on_error = FALSE;

	/**
	 * @var TSS_Tribal_IResponse
	 */
	protected $response;

	/**
	 * @var int
	 */
	protected $application_type;

	/**
	 * @var bool
	 */
	protected $cache_decision;

	/**
	 * @param TSS_Tribal_Call $call
	 * @param bool $rework
	 * @return void
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, TSS_Tribal_Call $call, $rework = FALSE, $cache_decision = TRUE)
	{
		parent::__construct($log);
		$this->call = $call;
		$this->rework = $rework;
		$this->cache_decision = $cache_decision;
	}

	/**
	 * @param Integer $app_type
	 * @return void;
	 */
	public function setApplicationType($app_type)
	{
		$this->application_type = $app_type;
	}

	/**
	 * Set whether the rule is to fail on error
	 * or not.
	 *
	 * @param boolean $bool
	 * @return void
	 */
	public function setFailOnError($bool)
	{
		$this->fail_on_error = (bool)$bool;
	}

	/**
	 * Sets the name used for event logging
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#getEventName()
	 */
	protected function getEventName()
	{
		return 'TRIBAL SERVER CALL';
	}

	/**
	 * Sets the failure reason stored in the state object when invalid
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#getFailureReason()
	 */
	protected function getFailureReason()
	{
		return new VendorAPI_Blackbox_FailureReason("TRIBAL SERVER CALL", "TRIBAL SERVER CALL FAILED!");
	}

	/**
	 * Attach a new observer to this object
	 *
	 * @param VendorAPI_Blackbox_Tribal_ICallObserver $observer
	 * @return void
	 */
	public function attachObserver(VendorAPI_Blackbox_Tribal_ICallObserver $observer)
	{
		$this->observers[] = $observer;
	}

	/**
	 * Detach an observer from this object
	 *
	 * @param VendorAPI_Blackbox_Tribal_ICallObserver $observer
	 * @return void
	 */
	public function detachObserver(VendorAPI_Blackbox_Tribal_ICallObserver $observer)
	{
		$index = array_search($observer, $this->observers, TRUE);
		if ($index !== FALSE)
		{
			unset($this->observers[$index]);
		}
	}

	/**
	 * Notify all observers of a new event in the
	 * call.
	 *
	 * @param string $event
	 * @param TSS_Tribal_Result $result
	 * @param Blackbox_IStateData $state
	 * @param Blackbox_Data $data
	 * @return void
	 */
	public function notifyObservers($event, TSS_Tribal_Result $result, Blackbox_IStateData $state, VendorAPI_Blackbox_Data $data)
	{
		foreach ($this->observers as $observer)
		{
			$observer->$event($this, $result, $state, $data);
		}
	}

	/**
	 * Overrides the base isValid to add onTransportError
	 *
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#isValid()
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		try
		{
			return parent::isValid($data, $state);
		}
		// Blackbox_Exceptions are caught in isValid.. ECash exceptions won't be
		catch (TSS_Tribal_TransportException $e)
		{
			return ($this->onTransportError($e, $data, $state) === TRUE);
		}
		catch (TSS_Tribal_ProviderException $e)
		{
			// provider exceptions are always fails
			return FALSE;
		}
	}

	/**
	 * Checks whether the rule has enough data to run
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#canRun()
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Fired when the rule is invalid
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#onInvalid()
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);
	}

	/**
	 * Returns how this failure should be reported to lead sources.
	 * @return int
	 */
	protected function determineFailType()
	{
		return VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE;
	}

	/**
	 * Fired when a transport-level error occurs
	 * @param Exception $e
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function onTransportError(Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($event = $this->getEventName())
		{
			$this->logEvent($event, 'ERROR');
		}

		if (!isset($state_data->loan_actions)
			|| !($state_data->loan_actions instanceof VendorAPI_Blackbox_LoanActions))
		{
			$state_data->loan_actions = new VendorAPI_Blackbox_LoanActions();
		}
		$state_data->loan_actions->addLoanAction('TRIBAL_PERF');
		return !$this->fail_on_error;
	}

	/**
	 * Runs the rule
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		/* tribe is not a uw call
		$state->uw_provider = 'tribal';
		// avoid calling onInvalid 
 		if ($this->cache_decision
			&& isset($state->uw_decision))
		{
			return (bool)$state->uw_decision;
		}*/

		$request = $this->getRequestData($data);

		if ($this->application_type == 0 && $state->is_react)
		{
			$this->setApplicationType(1);
		}
		if (is_numeric($this->application_type))
		{
			$request['application_type'] = $this->application_type;
		}

		$result = $this->call->execute($request);

		$this->response = $result->getResponse();

		/*
		if ($this->response->hasError())
		{
			$valid_experian_errors = array(5, 8, 57);

			if (preg_match('#^E-(\d{3})#', $this->response->getErrorMsg(), $m)
				&& !in_array($m[1], $valid_experian_errors))
			{
				throw new TSS_Tribal_ProviderException(
					$this->response->getErrorMsg(),
					$this->response->getErrorCode(),
					$this->response
				);
			}
			else
			{
				throw new TSS_Tribal_TransportException(
					$this->response->getErrorMsg(),
					(int)$this->response->getErrorCode(),
					$this->response
				);
			}
		}
		*/
		$valid = $result->isValid();
		
		/* tribe is not a uw call
		$state->uw_decision = $valid;
		*/

		//$this->notifyObservers(self::EVENT_ON_CALL, $result, $state, $data);
		
		$ssn = $request['ssn'];
		$decision = $result->getResponse()->getDecision();
		$code = $result->getResponse()->getCode();
		$message = $result->getResponse()->getMessage();
		
		$model = ECash::getFactory()->getModel('TribalResponse');
		$model->date_created = date("Y-m-d H:i:s", time());
		$model->ssn = $ssn;
		$model->decision = $decision;
		$model->code = $code;
		$model->message = $message;
		$model->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
		$model->insert();

		return $valid;
	}

	/**
	 * Builds the array for the Tribal request
	 * @param Blackbox_Data $data
	 * @return array
	 */
	protected function getRequestData(VendorAPI_Blackbox_Data $data)
	{
		$request = $data->toArray();

		$request['direct_deposit'] = ($request['direct_deposit'] ? 1 : 0);
		if(!in_array($request['income_direct_deposit'],array(1,0)))
		{
			$request['income_direct_deposit'] = (strtolower($request['income_direct_deposit']) == 'yes') ? 1 : 0;
		}

		if ($data->application_id > 0)
		{
			$request['application_id'] = $data->application_id;
		}
		elseif  ($data->external_id > 0)
		{
			$request['application_id'] = $data->external_id;
		}
		else
		{
			$request['application_id'] = 0;
		}

		return $request;
	}
}

?>
