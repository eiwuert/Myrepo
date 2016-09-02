<?php

class VendorAPI_PreviousCustomer_CustomerHistoryStatusMap
{
	/**
	 * Map of status names to simple names (i.e., 'bad', 'active')
	 * @var array
	 */
	protected $status_map = array(
		// bad
		'*root::customer::collections::collections_rework' => 'bad',
		'*root::applicant::fraud::confirmed' => 'bad',
		'*root::customer::collections::arrangements::amortization' => 'bad',
		'*root::customer::collections::arrangements::arrangements_failed' => 'bad',
		'*root::customer::collections::arrangements::current' => 'bad',
		'*root::customer::collections::arrangements::hold' => 'bad',
		'*root::customer::collections::bankruptcy::dequeued' => 'bad',
		'*root::customer::collections::bankruptcy::queued' => 'bad',
		'*root::customer::collections::bankruptcy::unverified' => 'bad',
		'*root::customer::collections::bankruptcy::verified' => 'bad',
		'*root::customer::collections::chargeoff' => 'bad',
		'*root::customer::collections::contact::dequeued' => 'bad',
		'*root::customer::collections::contact::follow_up' => 'bad',
		'*root::customer::collections::contact::queued' => 'bad',
		'*root::customer::collections::deceased::unverified' => 'bad',
		'*root::customer::collections::deceased::verified' => 'bad',
		'*root::customer::collections::indef_dequeue' => 'bad',
		'*root::customer::collections::new' => 'bad',
		'*root::customer::collections::quickcheck::arrangements' => 'bad',
		'*root::customer::collections::quickcheck::ready' => 'bad',
		'*root::customer::collections::quickcheck::pending' => 'bad',
		'*root::customer::collections::quickcheck::return' => 'bad',
		'*root::customer::collections::quickcheck::sent' => 'bad',
		'*root::customer::collections::skip_trace' => 'bad',
		'*root::customer::servicing::past_due' => 'bad',
		'*root::customer::settled' => 'bad',
		'*root::customer::write_off' => 'bad',
		'*root::external_collections::pending' => 'bad',
		'*root::external_collections::sent' => 'bad',
		'*root::external_collections::recovered' => 'bad',

		// denied
		'*root::applicant::denied' => 'denied',

		// disagreed
		'*root::prospect::disagree' => 'disagreed',

		// confirmed_disagreed
		'*root::prospect::confirm_declined' => 'confirmed_disagreed',

		// withdrawn
		'*root::applicant::withdrawn'=>'withdrawn',

		// cancel is withdrawn
		//'*root::customer::servicing::canceled'=>'withdrawn',
                '*root::applicant::canceled'=>'withdrawn',

		// paid
		'*root::customer::paid' => 'paid',

		// settled
		'*root::customer::settled' => ECash_CustomerHistory::STATUS_SETTLED,

		// active
		'*root::customer::servicing::active' => 'active',
        
        // cccs is active
		'*root::customer::collections::cccs' => 'active',

		// pending is active
		'*root::applicant::fraud::dequeued' => 'pending',
		'*root::applicant::fraud::follow_up' => 'pending',
		'*root::applicant::fraud' => 'pending',
		'*root::applicant::fraud::queued' => 'pending',
		'*root::applicant::high_risk::dequeued' => 'pending',
		'*root::applicant::high_risk::follow_up' => 'pending',
		'*root::applicant::high_risk' => 'pending',
		'*root::applicant::high_risk::queued' => 'pending',
		'*root::applicant::underwriting::dequeued' => 'pending',
		'*root::applicant::underwriting::follow_up' => 'pending',
		'*root::applicant::underwriting::preact' => 'pending',
		'*root::applicant::underwriting::queued' => 'pending',
		'*root::applicant::verification::addl' => 'pending',
		'*root::applicant::verification::dequeued' => 'pending',
		'*root::applicant::verification::follow_up' => 'pending',
		'*root::applicant::verification::queued' => 'pending',
		'*root::customer::servicing::approved' => 'pending',
		'*root::customer::servicing::funding_failed' => 'pending',
		'*root::customer::servicing::hold' => 'pending',
		'*root::prospect::agree' => 'pending',
		'*root::prospect::confirmed' => 'pending',
		'*root::prospect::in_process' => 'pending',
		'*root::prospect::pending' => 'pending',
		'*root::prospect::preact_confirmed' => 'pending',
	
		// Allowind - sort of paid
		'*root::external_collections::allowed' => 'allowed',
	
	);

	protected $expirable_statuses = array(
		'*root::prospect::confirmed',
		'*root::prospect::pending',
		'*root::prospect::preact_confirmed',
	);
	
	/**
	 * Returns the Customer History status based on eCash Status
	 *
	 * @param string $ecash_status
	 * @return string
	 */
	public function getStatus($ecash_status)
	{
		$ecash_status = implode('::', array_reverse(explode('::', $ecash_status)));
		return isset($this->status_map[$ecash_status]) ? $this->status_map[$ecash_status] : NULL;
	}

	/**
	 * Returns whether or not an app status is expirable.
	 * @param string $ecash_status
	 * @return bool
	 */
	public function isStatusExpirable($ecash_status)
	{
		$ecash_status = implode('::', array_reverse(explode('::', $ecash_status)));
		return in_array($ecash_status, $this->expirable_statuses);
	}
}

?>
