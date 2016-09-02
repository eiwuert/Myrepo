<?php

/**
 * A generic customer decider
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_Decider implements VendorAPI_Blackbox_ICustomerHistoryDecider
{
	/**
	 * @var int
	 */
	protected $active_threshold;

	/**
	 * @var string
	 */
	protected $denied_time_threshold;

	/**
	 * @var int
	 */
	protected $disagreed_threshold;

	/**
	 * @var string
	 */
	protected $disagreed_time_threshold;

	/**
	 * Strtotime compatible number of days in the past to check for withdrawn apps (ex. '-1 day')
	 *
 	 * @var string
 	 */
	protected $withdrawn_threshold;

	/**
	 * Company we're reacting with (or NULL)
	 *
	 * @var string
	 */
	protected $company;

	/**
	 * Second Tier Allowed Status Usage
	 * @var bool 
	 */
	protected $use_allowed_status;
	
	/**
	 * Strtotime compatible number of days in the past to check for withdrawn apps for allowed status sites (ex. '-1 day')
	 *
 	 * @var string
 	 */
	protected $allowed_withdrawn_threshold;	
	
	/**
	 * @param int $active_threshold Number of active loans the customer can have and still be active
	 * @param string $denied_time_threshold Time period within which a denied loan results in denials
	 * @param int $disagreed_threshold Number of disagreed or confirmed_disagreed apps the customer can have and still be active
	 * @param string $disagreed_time_threshold Time period within which a disagreed app results in denials
	 * @param string $withdrawn_threshold Number of days in the past to check for withdrawn apps
	 * @param string $company Company we're reacting with, if any
	 */
	public function __construct($active_threshold, $denied_time_threshold, $disagreed_threshold, $disagreed_time_threshold, $withdrawn_threshold = NULL, $company)
	{
		$this->active_threshold = $active_threshold;
		$this->denied_time_threshold = $denied_time_threshold;
		$this->disagreed_threshold = $disagreed_threshold;
		$this->disagreed_time_threshold = $disagreed_time_threshold;
		$this->withdrawn_threshold = $withdrawn_threshold;
		$this->company = $company;
		$this->use_allowed_status  = false;
	}

	/**
	 * Second Tier Allowed Status Usage
	 * @param bool $use_allowed_status
	 * @param int $withdrawn_threshold  
	 */	
	public function setUseAllowedStatus($use_allowed_status, $withdrawn_threshold = 3)
	{
		$this->use_allowed_status = $use_allowed_status;
		$this->allowed_withdrawn_threshold = $withdrawn_threshold;
	}
	
	/**
	 * Returns a customer classification based on their history
	 *
	 * @param ECash_CustomerHistory $history
	 * @return VendorAPI_Blackbox_Generic_Decision
	 */
	public function getDecision(ECash_CustomerHistory $history)
	{

		$active = ($history->getCountActive() + $history->getCountPending());
		$bad = $history->getCountBad();
		$paid = $history->getCountPaid() + $history->getCountSettled();
		$allowed = $history->getCountAllowed();
		
		// Withdrawn Threshold for Allows Status Sites and Allowed Status
		$this->withdrawn_threshold = ($this->isAllowedStatusUsed() && $allowed) ? $this->allowed_withdrawn_threshold : $this->withdrawn_threshold;
		
		$decision = NULL;

		if ($this->isDNL($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DONOTLOAN;
		}
		elseif ($this->isActiveWithCompany($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE;
		}
		elseif ($bad || (!$this->isAllowedStatusUsed() && $allowed))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD;
		}
		elseif ($this->isDenied($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED;
		}
		elseif ($this->isWithdrawn($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_WITHDRAWN;
		}
		elseif ($this->isAllowedStatusUsed() && $active > 1)
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE;
		}		
		elseif ($active > $this->active_threshold)
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE;
		}
		elseif ($this->isDisagreed($history))
		{
			// Check if customer exceeds disagreed_threshold
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DISAGREED;
		}
		elseif ($active)
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_UNDERACTIVE;
		}
		elseif ($paid || ($this->isAllowedStatusUsed() && $allowed))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT;		
		}
		else
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW;
		}

		return new VendorAPI_Blackbox_Generic_Decision($decision, "Application History: ".$history);
	}

	/**
	 * Checks to see if the react company is in the DNL list
	 *
	 * @param unknown_type $history
	 * @return bool
	 */
	protected function isDNL($history)
	{
		return ($this->company !== NULL
			&& $history->getIsDoNotLoan($this->company));
	}

	/**
	 * Checks to see if the react company has an active or pending loan
	 *
	 * @param unknown_type $history
	 * @return bool
	 */
	protected function isActiveWithCompany($history)
	{
		if ($this->company)
		{
			return (in_array(strtolower($this->company), $history->getActiveCompanies())
				|| in_array(strtolower($this->company), $history->getPendingCompanies()));
		}
		return FALSE;
	}

	/**
	 * Determines whether the customer status is denied
	 *
	 * @param ECash_CustomerHistory $history
	 * @return bool
	 */
	protected function isDenied($history)
	{
		// Allow for a null $denied_time_threshold - GForge #8062 [DW]
		$denied_time_threshold = is_string($this->denied_time_threshold)
			? strtotime($this->denied_time_threshold)
			: $this->denied_time_threshold;

		return ($denied_time_threshold !== NULL
			&& ($history->getNewestLoanDateInStatus(ECash_CustomerHistory::STATUS_DENIED) > $denied_time_threshold));
	}

	/**
	 * Determines whether the customer status is withdrawn
	 *
	 * @param ECash_CustomerHistory $history
	 * @return bool
	 */
	protected function isWithdrawn($history)
	{
		$withdrawn_threshold = is_string($this->withdrawn_threshold)
			? strtotime($this->withdrawn_threshold)
			: $this->withdrawn_threshold;

		return ($withdrawn_threshold !== NULL
			&& $history->getNewestLoanDateInStatus(ECash_CustomerHistory::STATUS_WITHDRAWN) > $withdrawn_threshold);
	}
	
	/**
	 * Determines whether the customer status is disagreed
	 *
	 * @param ECash_CustomerHistory $history
	 * @return bool
	 */
	protected function isDisagreed($history)
	{
		$disagreed_time_threshold = is_string($this->disagreed_time_threshold)
			? strtotime($this->disagreed_time_threshold)
			: $this->disagreed_time_threshold;		
		
		$disagreed = ($history->getCountDisagreed($disagreed_time_threshold) + $history->getCountConfirmedDisagreed($disagreed_time_threshold));
				
		return ($disagreed > $this->disagreed_threshold);
		
	}
		
	
	/**
	 * Checks to see Second Tier Allowed Status Usage
	 * 
	 * @return bool
	 */
	protected function isAllowedStatusUsed()
	{
		return $this->use_allowed_status;
	}
}

?>
