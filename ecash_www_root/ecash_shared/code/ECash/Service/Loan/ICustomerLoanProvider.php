<?php

interface ECash_Service_Loan_ICustomerLoanProvider
{
	/**
	 * @param $username
	 * @param $password
	 * @return array Loans
	 */
	public function findLoansForCustomer($username, $password);
}