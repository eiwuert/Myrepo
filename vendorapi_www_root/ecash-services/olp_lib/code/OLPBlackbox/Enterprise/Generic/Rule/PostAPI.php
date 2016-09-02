<?php
/**
 * Rule for the ECash Vendor API Post call.
 *
 * This is heavily based on the original Qualify API call.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PostAPI extends OLPBlackbox_Rule implements OLPBlackbox_ISellRule
{
	/**
	 * Blackbox configuration object
	 *
	 * @var Blackbox_Config
	 */
	protected $config;

	/**
	 * eCash Vendor API object
	 *
	 * @var OLPECash_VendorAPI
	 */
	protected $api;

	/**
	 * Database model for the application_value table.
	 *
	 * @var DB_Models_Decorator_ReferencedWritableModel_1
	 */
	protected $application_value_model;

	/**
	 * OLP's application manager
	 *
	 * @var App_Campaign_Manager
	 */
	protected $app_campaign_manager;

	/**
	 * Whether to expire applications
	 *
	 * @var bool
	 */
	protected $should_expire = FALSE;

	/**
	 * Blackbox DebugConf object
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $debug;

	/**
	 * Throw a fail exception on DataX failure
	 * @var bool
	 */
	protected $datax_exception;

	/**
	 * Throw the rework exception?
	 *
	 * @var OLPBlackbox_ReworkException
	 */
	protected $rework_exception;

	/**
	 * Constructor
	 *
	 * @param Blackbox_Config $config
	 * @param OLPBlackbox_DebugConf $debug
	 * @param OLPECash_VendorAPI $api
	 * @param DB_Models_Decorator_ReferencedWritableModel_1 $application_value_model
	 * @param App_Campaign_Manager $app_campaign_manager
	 * @param bool $should_expire
	 * @param bool $datax_exception
	 */
	public function __construct(
		Blackbox_Config $config,
		OLPBlackbox_DebugConf $debug,
		OLPECash_VendorAPI $api,
		DB_Models_Decorator_ReferencedWritableModel_1 $application_value_model,
		App_Campaign_Manager $app_campaign_manager,
		$should_expire,
		$datax_exception = FALSE
	)
	{
		$this->config = $config;
		$this->debug = $debug;
		$this->api = $api;
		$this->application_value_model = $application_value_model;
		$this->app_campaign_manager = $app_campaign_manager;
		$this->should_expire = (bool)$should_expire;
		$this->datax_exception = $datax_exception;

		parent::__construct();
	}

	/**
	 * Runs the eCash Vendor API Post action
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$result = array();
		$ecash_data = $this->buildECashData($data, $state_data);

		try
		{
			$response = $this->api->post($ecash_data);
		}
		catch (Exception $e)
		{
			$exception_message = $e->getMessage();

			if (preg_match('/The requested URL returned error: 401$/', $exception_message))
			{
				$generallog_message = sprintf(
					'Authentication error connecting to: %s',
					$state_data->name
				);

				$olp_db = DB_Connection::getInstance('GENERAL_LOG', $this->config->mode);
				GeneralLog_Client::createEntry(
					$olp_db,
					'vendor_api_401',
					$generallog_message,
					$data->session_id,
					$data->application_id
				);
			}

			throw new Blackbox_Exception("eCash API ({$state_data->name}) call threw exception: {$exception_message}");
		}

		if (FALSE == $response['outcome'])
		{
			if (isset($response['result']) && isset($response['result']['exception']))
			{
				throw new Blackbox_Exception(sprintf(
					'eCash API (%s) call threw exception: %s',
					$state_data->name,
					$response['result']['exception']
				));
			}

			throw new Blackbox_Exception(sprintf('eCash API (%s) returned an unknown error', $state_data->name));
		}

		if ($response['outcome'] && isset($response['result']))
		{
			$result = $response['result'];
		}

		if (isset($result['rework']) && $result['rework'] === TRUE)
		{
			$info = array(
				'company' => $state_data->campaign_name,
				'tier'    => $state_data->tier_number
			);
			$this->rework_exception = new OLPBlackbox_ReworkException("DataX for {$state_data->campaign_name} failed.", $info);
		}

		// Hit the fail event first so that we know that something happened
		if (isset($result['fail']))
		{
			$this->hitEvent(
				'ECASH_API_' . $result['fail']['short'],
				OLPBLackbox_Config::EVENT_RESULT_FAIL,
				$data->application_id,
				$state_data->campaign_name,
				$this->config->blackbox_mode
			);

			// deferred actions are executed after blackbox is finished
			if (isset($result['fail_callback'])
				&& $result['fail_callback'])
			{
				// the fail call takes an array of data as the first parameter; we don't have it
				$action = Delegate_1::fromMethod($this->api, 'fail', array(NULL));
				$state_data->deferred->add($action, $state_data->name);
			}

			// CLK throws an exception to close the collection on DataX failure
			if ($this->datax_exception
				&& $result['fail']['short'] == 'DATAX'
				&& !$this->rework_exception instanceof OLPBlackbox_ReworkException)
			{
				$this->rework_exception = new OLPBlackbox_FailException('Failed DataX');
			}
		}

		$state_data->qualified_loan_amount = isset($result['amount']) ? $result['amount'] : 0;

		if (isset($result['is_react'])
			&& $result['is_react'])
		{
			$state_data->is_react = TRUE;
			$state_data->react_app_id = $result['react_application_id'];
		}

		// Save our loan type to OLP
		if (isset($result['loan_type_short'])) $this->saveLoanType($data->application_id, $result['loan_type_short']);

		if (isset($result['loan_actions']) && is_array($result['loan_actions']) && !empty($result['loan_actions']))
		{
			if (!$state_data->loan_actions instanceof OLPBlackbox_Enterprise_LoanActions)
			{
				$state_data->loan_actions = new OLPBlackbox_Enterprise_LoanActions();
			}
			$state_data->loan_actions->addData($result['loan_actions']);
		}

		$valid = (isset($result['qualified'])
			&& $result['qualified']);

		if ($valid)
		{
			// remove any queued fail callback for this target
			$state_data->deferred->remove($state_data->name);
		}

		return $valid;
	}

	/**
	 * Basically make sure we throw the rework exception
	 * if it's set
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = parent::isValid($data, $state_data);
		if ($this->rework_exception instanceof OLPBlackbox_ReworkException
			|| $this->rework_exception instanceof OLPBlackbox_FailException)
		{
			$ex = $this->rework_exception;
			unset($this->rework_exception);
			throw $ex;
		}
		return $valid;
	}

	/**
	 * Builds the array of data to pass to the eCash Vendor API
	 *
	 * @param OLPBlackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return array
	 */
	protected function buildECashData(OLPBlackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$ecash_data = $data->toECashArray();

		// Set page ID
		// The API will determine if it needs to use this page_id or the page_id of the enterprise site.
		$ecash_data['page_id'] = $this->config->page_id;

		$ecash_data['call_center'] = (isset($this->config->call_center) && $this->config->call_center == TRUE);

		// Build campaign info
		$campaign_info = $this->app_campaign_manager->Get_Campaign_Info($data->application_id);
		$ecash_data['campaign_info'] = array();

		foreach ($campaign_info as $info)
		{
			$ecash_data['campaign_info'][] = array(
				'promo_id' => $info['promo_id'],
				'promo_sub_code' => $info['promo_sub_code'],
				'reservation_id' => $info['reservation_id'],
				'license_key' => $info['license_key'],
				'campaign_name' => $state_data->campaign_name,
				'name' => $info['url']
			);
		}

		// check for debug flags that we should pass on to the API
		$ecash_data['debug'] = array(
			'DATAX' => $this->debug->getFlag(OLPBlackbox_DebugConf::DATAX_PERF),
			'PREV_CUSTOMER' => $this->debug->getFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER),
			'USED_INFO' => $this->debug->getFlag(OLPBlackbox_DebugConf::USED_INFO),
			'SUPPRESSION_LIST' => $this->debug->getFlag(OLPBlackbox_DebugConf::RULES),
		);

		$ecash_data['is_react'] = (bool)$this->config->react_company;
		$ecash_data['olp_process'] = $this->app_campaign_manager->Get_Olp_Process($data->application_id);
		$ecash_data['campaign'] = $state_data->campaign_name;
		$ecash_data['is_enterprise'] = $this->config->is_enterprise;
		$ecash_data['react_type'] = $this->config->react_type;

		// Pass loan type for CLK - this will just be overwritten for Commercial
		$ecash_data['loan_type'] = TRUE == $data->card_loan ? 'card' : 'standard';

		// Go hard coded values!
		$ecash_data['legal_id_type'] = 'dl';

		// For Agean... doesn't hurt to pass it to everyone
		$ecash_data['is_title_loan'] = (bool)$this->config->title_loan;

		// rework is triggered by the site config
		$ecash_data['rework'] = ($this->config->allow_datax_rework
			&& !$this->config->do_datax_rework);

		$ecash_data['price_point'] = $state_data->price_point;

		return $ecash_data;
	}

	/**
	 * Saves the loan type short we get from the result to OLP's database.
	 *
	 * @param int $application_id
	 * @param string $loan_type_short
	 * @return void
	 */
	protected function saveLoanType($application_id, $loan_type_short)
	{
		$saved = $this->application_value_model->loadBy(array(
			'application_id' => $application_id,
			'name' => 'loan_type_short'
		));

		if (!$saved)
		{
			$this->application_value_model->application_id = $application_id;
			$this->application_value_model->name = 'loan_type_short';
		}
		$this->application_value_model->value = $loan_type_short;

		$this->application_value_model->save();
	}

	/**
	 * Defined by Blackbox_Target.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
}
