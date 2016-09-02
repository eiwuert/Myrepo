<?php

/**
 * Basic DataX rule
 *
 * This does not implement adverse actions... Check the adverse action
 * observers (@see VendorAPI_Blackbox_DataX_AdverseActionObserver and
 * children) for those.
 *
 * This rule caches its result in state_data->uw_decision, which,
 * in turn, is stored in the state object and persisted across calls. This
 * prevents making multiple DataX calls for companies that have multiple
 * blackbox campaigns (like NSC). Likewise, it stores the DataX track hash
 * in state_data->uw_track_hash and, eventually, the state object.
 *
 * The exception to the caching rule, however, is rework. If rework is
 * triggered, the cached decision and track hash MUST be cleared. If the
 * decision is cached, this rule will never make a second call to DataX. If
 * the track  hash is reused, DataX will NOT rerun the IDV portion of
 * the performance call and will immediately fail the application.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_DataX extends VendorAPI_Blackbox_Rule
{
	const EVENT_ON_CALL = "onCall";

	/**
	 * @var TSS_DataX_Call
	 */
	protected $call;

	/**
	 * @var array VendorAPI_Blackbox_DataX_ICallObserver[]
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
	 * @var TSS_DataX_IResponse
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
	 * @param TSS_DataX_Call $call
	 * @param VendorAPI_Blackbox_DataX_CallHistory $history
	 * @param bool $rework
	 * @return void
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, TSS_DataX_Call $call, $rework = FALSE, $cache_decision = TRUE)
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
		return 'DATAX UW';
	}

	/**
	 * Sets the failure reason stored in the state object when invalid
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#getFailureReason()
	 */
	protected function getFailureReason()
	{
		return new VendorAPI_Blackbox_FailureReason("DATAX UW", "DATAX UW FAILED!");
	}

	/**
	 * Attach a new observer to this object
	 *
	 * @param VendorAPI_Blackbox_DataX_ICallObserver $observer
	 * @return void
	 */
	public function attachObserver(VendorAPI_Blackbox_DataX_ICallObserver $observer)
	{
		$this->observers[] = $observer;
	}

	/**
	 * Detach an observer from this object
	 *
	 * @param VendorAPI_Blackbox_DataX_ICallObserver $observer
	 * @return void
	 */
	public function detachObserver(VendorAPI_Blackbox_DataX_ICallObserver $observer)
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
	 * @param TSS_DataX_Result $result
	 * @param Blackbox_IStateData $state
	 * @param Blackbox_Data $data
	 * @return void
	 */
	public function notifyObservers($event, TSS_DataX_Result $result, Blackbox_IStateData $state, VendorAPI_Blackbox_Data $data)
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
		catch (TSS_DataX_TransportException $e)
		{
			return ($this->onTransportError($e, $data, $state) === TRUE);
		}
		catch (TSS_DataX_ProviderException $e)
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
		// at this point, we're always in broker mode,
		// so we should always have enough dataz to run
		return TRUE;
	}

	/**
	 * Fired when the rule is invalid
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#onInvalid()
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		if ($this->rework
			&& $this->response
			&& $this->response->isIDVFailure())
		{
			// reset the track hash to force DataX to rerun IDV
            $state_data->uw_provider = 'datax';
			$state_data->uw_track_hash = NULL;
			$state_data->uw_decision = NULL;

			throw new VendorAPI_Blackbox_DataX_ReworkException(
				'IDV call failed',
				$this->response
			);
		}

		/**
		 * If it's a real IDV failure, we want to fail the enterprise.  If it's due
		 * to price point, it's just a campaign failure.  We'll return the price
		 * point to OLP, and they will retry if it's not sold by the time the
		 * lead cost is reduced to that price point.
		 */
		if(isset($state_data->fail_type) && $state_data->fail_type instanceof VendorAPI_Blackbox_FailType)
		{
			$state_data->fail_type->setFail($this->determineFailType());
		}
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
		$state_data->loan_actions->addLoanAction('DATAX_PERF');
		return !$this->fail_on_error;
	}

	/**
	 * Runs the rule
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		$state->uw_provider = 'datax';
		/* avoid calling onInvalid */
 		if ($this->cache_decision
			&& isset($state->uw_decision))
		{
			return (bool)$state->uw_decision;
		}

		$request = $this->getRequestData($data);

		// reuse track hash if we have one...
		if (isset($state->uw_track_hash))
		{
			$request['track_hash'] = $state->uw_track_hash;
		}
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

		if (isset($state->uw_call_history) && (isset($state->uw_call_history[0])))
		{
			$hist = $state->uw_call_history[0];
			$hist->addResult($result);
		}
		if ($this->response->hasError())
		{
			$valid_experian_errors = array(5, 8, 57);

			if (preg_match('#^E-(\d{3})#', $this->response->getErrorMsg(), $m)
				&& !in_array($m[1], $valid_experian_errors))
			{
				throw new TSS_DataX_ProviderException(
					$this->response->getErrorMsg(),
					$this->response->getErrorCode(),
					$this->response
				);
			}
			else
			{
				throw new TSS_DataX_TransportException(
					$this->response->getErrorMsg(),
					(int)$this->response->getErrorCode(),
					$this->response
				);
			}
		}

		$valid = $result->isValid();
		$state->uw_decision = $valid;

		// HMS/RRV checks to see if DataX says they can get a loan amount increase
		if ($this->response instanceof TSS_DataX_ILoanAmountResponse)
		{
			$state->loan_amount_decision = $this->response->getLoanAmountDecision();
		}
		if ($this->response instanceof TSS_DataX_IAutoFundResponse)
		{
			$state->auto_fund = $this->response->getAutoFundDecision();
			if($state->auto_fund)
			{
				if (!isset($state->loan_actions)
					|| !($state->loan_actions instanceof VendorAPI_Blackbox_LoanActions))
				{
					$state->loan_actions = new VendorAPI_Blackbox_LoanActions();
				}
				$state->loan_actions->addLoanAction('AUTOFUND_APPROVED');				
			}
		}

		$this->notifyObservers(self::EVENT_ON_CALL, $result, $state, $data);

		// save the track hash if we got one
		if ($hash = $this->response->getTrackHash())
		{
			$state->uw_track_hash = $hash;
		}
		return $valid;
	}

	/**
	 * Builds the array for the DataX request
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
