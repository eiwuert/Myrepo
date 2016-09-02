<?php

class ECash_VendorAPI_Blackbox_Clarity_BureauInquiryObserver implements VendorAPI_Blackbox_Clarity_ICallObserver
{
	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var VendorAPI_CallContext
	 */
	protected $context;

	/**
	 * @var VendorAPI_IModelPersistor
	 */
	protected $persistor;
	
	/**
	 * @var InquiryClient
	 */
	protected $inquiry_client;

	/**
	 * @var array
	 */
	protected $skip_trace_fields = array(
		'phone_home' => 'home_phone',
		'phone_cell' => 'cell_phone',
		'phone_work' => 'work_phone',
		'phone_work_ext' => 'work_ext',
		'phone_fax' => 'fax_phone',
		'email' => 'email',
	);

	public function __construct(
		ECash_Factory $factory,
		VendorAPI_CallContext $context,
		VendorAPI_IModelPersistor $persistor,
		ECash_WebService_InquiryClient $inquiry_client
	)
	{
		$this->factory = $factory;
		$this->context = $context;
		$this->persistor = $persistor;
		$this->inquiry_client = $inquiry_client;
	}
	
	public function onCall(VendorAPI_Blackbox_Rule_Clarity $caller, Clarity_UW_Result $result, $state, VendorAPI_Blackbox_Data $data)
	{
		$is_valid = $result->isValid();

		if ($is_valid)
		{
			$model = $this->factory->getModel('BureauInquiry');
		}
		else
		{
			$model = $this->factory->getModel('BureauInquiryFailed');
			$model->ssn = $data->ssn;
		}

		/* @var $result Clarity_UW_Result */
		$bureau_list = $this->factory->getReferenceList('Bureau');
		$clarity_bureau_id = $bureau_list->toId('clarity');

		$model->date_created = time();
		$model->company_id = $this->context->getCompanyId();
		$model->application_id = $data->application_id;
		$model->bureau_id = $clarity_bureau_id;
		$model->inquiry_type = $result->getCallType();
		$model->sent_package = $result->getRequestXML();
		$model->received_package = $result->getResponseXML();
		$model->outcome = $result->getResponse()->getScore();
		$model->trace_info = $result->getResponse()->getTrackHash();
		$model->decision = ($is_valid ? 'PASS' : 'FAIL');
		$model->reason = implode(',', $this->getReasons($result));
		$model->timer = round($result->getRequestLength(), 5);
		$this->persistor->save($model);

		if (USE_WEB_SERVICES)
		{
			$contact_info = array();
			$contact = array();

			$ret = $this->inquiry_client->recordSkipTrace(
				$data->ssn,
				$data->external_id,
				$this->context->getApiAgentName(),
				$result->getCallType(),
				implode(",", $this->getReasons($result)),
				$result->isValid() ? 1 : 0,
				$contact_info
			);
        	//	$response = $result->getResponse();
        	//	$response->update_bureau_xml_fields($this->factory->getDB(),false,$data->ssn,false);
		}
	}

	protected function getReasons(Clarity_UW_Result $result)
	{
		$reason_arr = array();
		foreach ($result->getResponse()->getDecisionBuckets() as $vendor=>$reason)
		{
			$reason_arr[] = $reason;
		}
		return $reason_arr;
	}
}
