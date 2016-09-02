<?php

/**
 *
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_Actions_GetPage extends VendorAPI_Actions_Base
{
	/**
	 *
	 * @var VendorAPI_CFE_IRulesetFactory
	 */
	protected $rule_factory;

	/**
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;

	/**
	 *
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_CFE_IRulesetFactory $rule_factory
	 * @param VendorAPI_IApplicationFactory $application_factory
	 * @return void
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_CFE_IRulesetFactory $rule_factory,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		$this->rule_factory = $rule_factory;
		$this->application_factory = $application_factory;
		parent::__construct($driver);
	}

	/**
	 * Execute
	 * @param Array $data
	 * @param String $state
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $ecash_sign_docs, $campaign_info, $state = NULL)
	{
		// add this for logging, etc.
		$this->call_context->setApplicationId($application_id);

		if ($state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state);
		$application = $this->application_factory->getApplication($application_id, $persistor, $state);
		$ruleset = $this->rule_factory->getRuleset($this->getPageFlowConfig());

		if (is_array($campaign_info)) $campaign_info = (Object)$campaign_info;
		if (!empty($campaign_info) && !empty($campaign_info->license_key) && !empty($campaign_info->promo_id))
		{
			$application->addCampaignInfo(
				$this->getCallContext(),
				$campaign_info->license_key,
				$site_config->site_name,
				$campaign_info->promo_id,
				$campaign_info->promo_sub_code,
				$application->getCampaign()
			);
			$this->saveState($state);
		}

		$space_key = $application->getSpaceKey($this->driver, $this->driver->getStatProClient());

		$engine = $this->getCfeEngine(
			$application->getCfeContext($this->call_context),
			$ruleset
		);
		$page_data = new ArrayObject();
		$results = $engine->executeEvent(
			'getPage',
			array(
				'track_key' => $application->getTrackId(),
				'space_key' => $space_key,
				'application_id' => $application_id,
				'driver' => $this->getDriver(),
				'page' => $page,
				'page_data' => $page_data,
				'ecash_sign_docs' => !empty($ecash_sign_docs),
			)
		);

		// display should have updated qualify info -- AMG (and possibly
		// others) have the fund amount on the "agree" page

                $extra = array(
			'olp_process'		=> $application->olp_process,
			'loan_amount_desired'   => $application->fund_qualified
		);
															 
		$qi = $application->calculateQualifyInfo(FALSE,$application->fund_qualified, $extra);

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array(
				'documents' => $page_data['unsigned_documents'],
				'fund_amounts' => $application->getAmountIncrements(),
				'qualify_info' => $qi->asArray(),
				'required_data' => $page_data['required_data'],
				'page_name' => $results['page_name'],
				'tokens' => $page_data['tokens'],
				'status' => $application->getApplicationStatus(),
				'paydate' => $page_data['paydate'],
				'exit_strategy_flags' => $page_data['exit_strategy_flags']
			)
		);
	}

	protected function getPageflowConfig()
	{
		return $this->driver->getPageflowConfig();
	}

	/**
	 *
	 * @param ECash_CFE_IContext $context
	 * @return ECash_CFE_Engine
	 */
	protected function getCfeEngine(ECash_CFE_IContext $context, array $ruleset)
	{
		$engine = ECash_CFE_Engine::getInstance($context);
		$engine->setRuleset($ruleset);
		return $engine;
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;

	}
}
