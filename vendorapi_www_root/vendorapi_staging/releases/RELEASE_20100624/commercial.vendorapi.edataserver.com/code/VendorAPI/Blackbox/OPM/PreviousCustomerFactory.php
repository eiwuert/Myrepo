<?php

/**
 * The previous customer check factory for OPM
 * OPM wants a 60 day disagreed threshold
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_OPM_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $disagreed_time_threshold = '-60 days';

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $denied_time_threshold = '-30 days';

	/**
	 * List of organic campaigns for OPM
	 * @var array
	 */
	protected $organic_campaign_list = array('opm_bsc', 'opm_bsc_ex', 'opm_bsc_nc');

	/**
	 * OPM only wants to run ssn checks against non organic campaigns.
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		if(in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			parent::addCriteria($container);
		}
		else
		{
			$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_Ssn($this->getCustomerHistoryStatusMap()));
		}
	}

	/**
	 * OPM has different decision rules for organic vs non organic campaigns.
	 * 
	 * @return VendorAPI_Blackbox_OPM_Decider
	 */
	protected function getDecider()
	{
		if($this->config->is_react || in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			return parent::getDecider();
		}
		else
		{
			return new VendorAPI_Blackbox_OPM_Decider(
				$this->active_threshold,
				$this->denied_time_threshold,
				$this->disagreed_threshold,
				$this->disagreed_time_threshold,
				$this->withdrawn_threshold,
				$this->company
			);
		}
	}
}

?>
