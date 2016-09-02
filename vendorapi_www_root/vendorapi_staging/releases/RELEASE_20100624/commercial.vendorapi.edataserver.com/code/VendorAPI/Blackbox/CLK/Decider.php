<?php 

/**
 * AMG customer decider
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_CLK_Decider extends VendorAPI_Blackbox_Generic_Decider
{

	/**
	 * Determines whether the customer status is withdrawn
	 *
	 * @param ECash_CustomerHistory $history
	 * @return bool
	 */
	protected function isWithdrawn($history)
	{
		
		$company_withdrawn_threshold = '-240 hours';
		
		$company_history = $history->getCompanyHistory($this->company);		
		$company_withdrawn = $company_history->getNewestLoanDateInStatus(ECash_CustomerHistory::STATUS_WITHDRAWN);
		
		$withdrawn_threshold = is_string($this->withdrawn_threshold)
			? strtotime($this->withdrawn_threshold)
			: $this->withdrawn_threshold;
		$withdrawn = $history->getNewestLoanDateInStatus(ECash_CustomerHistory::STATUS_WITHDRAWN);

		return ($withdrawn_threshold !== NULL && ($withdrawn > $withdrawn_threshold || $company_withdrawn > strtotime($company_withdrawn_threshold)));
	}
	
	
	/**
	 * Determines whether the customer status is disagreed
	 *
	 * @param ECash_CustomerHistory $history
	 * @return bool
	 */
	protected function isDisagreed($history)
	{
		$company_disagree_time_threshold = 0;
		$company_disagree_threshold = '-240 hours';
		
		$company_history = $history->getCompanyHistory($this->company);		
		$company_disagree =($company_history->getCountDisagreed(strtotime($company_disagree_threshold)) + $company_history->getCountConfirmedDisagreed(strtotime($company_disagree_threshold))); 
		
		$disagreed_time_threshold = is_string($this->disagreed_time_threshold)
			? strtotime($this->disagreed_time_threshold)
			: $this->disagreed_time_threshold;		
		
		$disagreed = ($history->getCountDisagreed($disagreed_time_threshold) + $history->getCountConfirmedDisagreed($disagreed_time_threshold));
				
		return ($disagreed > $this->disagreed_threshold || $company_disagree > $company_disagree_time_threshold);
		
	}

	/**
	 * Checks to see if the react company has an active or pending loan
	 *
         * Dirth Hack Alert:
         *  AMG is reuqesting that there is a limit to the number of Reacts a customer can do in a month. This hack
         *  will treat those previews applications (even though Inactive Paid) as Active Applications
         *  [#26492] New Checks For Reacts
         *  [#53453] React check - no more than 2 reacts in 30 day period
         *
	 * @param unknown_type $history
	 * @return bool
	 */
	protected function isActiveWithCompany($history)
	{
                /*
                 * Dirth Hack Usage: How many Inactive React applications within the time threshold are allowed
                 */
                $counter = 0;
                $react_threashold = 2;
                $react_time_threashold = "-30 days";
                $paid_array = array('paid::customer::*root');
                
		if ($this->company)
		{
                    $active_apps = $history->getActiveCompanies();
                    $pending_apps = $history->getPendingCompanies();

                    // Checks to see if the react company has an active or pending loan
                    if(in_array(strtolower($this->company), $active_apps) || in_array(strtolower($this->company), $pending_apps))
                    {
                       return TRUE;
                    }
                    else
                    {
                        /*
                         * Dirth Hack Usage: [#26492] [#53453]
                         * We are going to go through the Inactive Paid applications to check to see
                         * if they were created in the past {$react_time_threashold} days.
                         * If they are more than {$react_threashold} we will consider the
                         * applicant to be Active aka OVERACTIVE.
                         */

                        $company_apps = $history->getLoans();
                        foreach($company_apps as $application)
                        {
                            $purchased_date = $application["purchase_date"];
                            $app_status = $application["additional_info"]["application_status"];

                            if($purchased_date >= strtotime($react_time_threashold) && in_array($app_status, $paid_array))
                            {
                                $counter++;
                            }
                        }
                        return ($react_threashold <= $counter);

                    }
		}
		return FALSE;
	}
}
?>