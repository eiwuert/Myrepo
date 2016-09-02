<?php

/**
 * Overridden version of post to add Purchased Leads 
 * operations.
 *
 * @author Brian Ronald <brian.ronald@fitech.com>
 */
class AALM_VendorAPI_Actions_Post extends VendorAPI_Actions_Post
{
	/**
	 * Stub function in Post that allows us to perform
	 * actions after we're successfully purchased a lead
	 * and have saved it into the Application Service.
	 *
	 * For #54795, we're adding the application to the
	 * Purchased Lead Memcache Store.
	 */
	protected function postPurchaseOperations($state, $data)
	{
		parent::postPurchaseOperations($state, $data);

		$application_id = $state->application_id;
		$ssn = $this->application->ssn;
		$company = $this->getCallContext()->getCompany();

		$store = $this->driver->getPurchasedLeadStore();

		/**
		 * It's assumed we've already locked the SSN via
		 * the Purchased Leads rule, so there is no need to
		 * lock it first.
		 */
		$store->addApplication($ssn, $company, $application_id, time());
		$store->unlockSsn($ssn);

	}

	/**
	 * Stub function in Post that allows us to perform
	 * actions after we've determined not to purchase a 
	 * lead.
	 * 
	 * For #54795, we're simply unlocking the SSN.  This
	 * serves as a way of keeping multiple calls for the same
	 * application from going through.
	 */
	protected function postNotPurchasedOperations($state, $data)
	{
		parent::postNotPurchasedOperations($state, $data);

		$ssn = $this->application->ssn;

		$store = $this->driver->getPurchasedLeadStore();

		/**
		 * We've decided not to purchase the lead so we need
		 * to unlock the SSN else a subsequent run of the
		 * Purchased Leads rule will fail because it was
		 * unable to attain a lock.
		 */
		$store->unlockSsn($ssn);

	}
}

?>
