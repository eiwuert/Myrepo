<?php

interface ECash_Service_LoanActionHistoryService_IECashAPIFactory {
	/**
	 * Creates an instance of ECash API 2 for the given application ID.
	 * @param $application_id
	 * @return Ecash_api_2
	 */
	public function createECashApi($application_id);
}