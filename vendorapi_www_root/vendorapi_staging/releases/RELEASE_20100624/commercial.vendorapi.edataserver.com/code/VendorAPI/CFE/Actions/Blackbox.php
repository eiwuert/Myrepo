<?php

class VendorAPI_CFE_Actions_Blackbox implements ECash_CFE_IExpression
{
	protected $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function evaluate(ECash_CFE_IContext $context)
	{
		$history = new ECash_CustomerHistory($context->getAttribute('customer_history'));
		$datax_history = new VendorAPI_Blackbox_DataX_CallHistory();
		$ft_history = new VendorAPI_Blackbox_FactorTrust_CallHistory();
		$cl_history = new VendorAPI_Blackbox_Clarity_CallHistory();
        $uw_history = array($datax_history,$ft_history,$cl_history);
        
		$state_object = $context->getAttribute('state_object');
		$driver = $context->getAttribute('driver');

		$state = new VendorAPI_Blackbox_StateData(array(
			'customer_history' => $history,
			'uw_call_history' => $uw_history,
			'lead_cost' => (isset($state_object->lead_cost) ? $state_object->lead_cost : NULL),
			'uw_track_hash' => (isset($state_object->uw_track_hash) ? $state_object->uw_track_hash : NULL),
			'uw_decision' => (isset($state_object->uw_decision) ? (bool)$state_object->uw_decision : NULL),
			'uw_provider' => (isset($state_object->uw_provider) ? (bool)$state_object->uw_provider : NULL),
			'adverse_action' => $state_object->adverse_action,
			'name' => $driver->getCompany(),
			'fail_type' => $context->getAttribute('fail_type'),
			'loan_actions' => new VendorAPI_Blackbox_LoanActions(),
		));

		// set existing loan actions from post or submitPage
		// calls, this run will merge into this list
		if (is_array($state_object->loan_actions)) {
			$state->loan_actions->addLoanActions($state_object->loan_actions);
		}
		
		$context->setAttribute('blackbox_state', $state);
		$context->setAttribute('customer_history', $history);

		/* @var $bbx_config VendorAPI_Blackbox_Config */
		$bbx_config = $context->getAttribute('blackbox_config');
		foreach ($this->config as $name=>$value)
		{
			if ($value instanceof ECash_CFE_IExpression)
			{
				$value = $value->evaluate($context);
			}
			unset($bbx_config->{$name});
			$bbx_config->{$name} = $value;
		}

		/* @var $factory VendorAPI_Blackbox_Factory */
		$factory = $driver->getBlackboxFactory(
			$bbx_config,
			$app->loan_type_id
		);
		$rework = $context->getAttribute('rework');
		$bbx = $factory->getBlackbox($rework, $state);

		$data = $this->getBlackboxData($context);

		if ($data->external_id == 0)
		{
			$driver->getLog()->write("Data-X track id is 0");
		}

		try
		{
			/* @var $bbx Blackbox_Root */
			$winner = $bbx->pickWinner($data);
		}
		catch (VendorAPI_Blackbox_DataX_ReworkException $e)
		{
			$context->setAttribute('rework_result', true);
			$winner = FALSE;
		}

		if ($winner)
		{
			/* @var $winner VendorAPI_Blackbox_Winner */
			$context->setAttribute(
				'customer_history',
				$winner->getCustomerHistory()
			);
		}
		$context->setAttribute(
			'idv_increase_eligible',
			$this->isIDVIncreaseEligible($winner)
		);

		return $winner;
	}

	/**
	 * @return VendorAPI_Blackbox_Data
	 */
	protected function getBlackboxData(ECash_CFE_IContext $context)
	{
		/* @var $app VendorAPI_IApplication */
		$app = $context->getAttribute('application');
		$qi = $app->calculateQualifyInfo(FALSE,NULL,array('react_type'=>$this->config['react_type']));

		$data = new VendorAPI_Blackbox_Data();
		$data->loadFrom($app->getData());
		$data->external_id = $context->getAttribute('state_object')->external_id;
		$data->requested_amount = $qi->getLoanAmount();
		$data->date_first_payment = date('Y-m-d', $qi->getFirstPaymentDate());
		$data->military = $context->getAttribute('military');
		$vendor_customer_id = $context->getAttribute('vendor_customer_id');
		if(!empty($vendor_customer_id))
		{
			$data->vendor_customer_id = $vendor_customer_id;
		}
		$data->paydates = $qi->getPaydates();
		$data->client_url_root = preg_replace('#^https?://#', '', $context->getAttribute('client_url_root'));
		$data->ioBlackBox = $context->getAttribute('ioBlackBox');
		$data->react_type = $context->getAttribute('blackbox_config')->react_type;

		return $data;
	}

	/**
	 * Determines if can use loan amount increase. If the customer has had
	 * any settled loans, they are not eligible.
	 *
	 * @param mixed $winner
	 * @return bool
	 */
	protected function isIDVIncreaseEligible($winner)
	{
		$idv_increase_eligible = FALSE;

		if ($winner instanceof Blackbox_IWinner)
		{
			$state_data = $winner->getStateData();

			if($state_data->loan_amount_decision
			   && !$state_data->customer_history->getCountSettled())
			{
				$idv_increase_eligible =  $state_data->loan_amount_decision;
			}
		}

		return $idv_increase_eligible;
	}

}

?>
