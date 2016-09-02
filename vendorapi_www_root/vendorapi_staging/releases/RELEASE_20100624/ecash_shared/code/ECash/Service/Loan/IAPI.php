<?php
/**
 * LoanAPI interface defines the methods required to support the service's WSDL (Loan.wsdl)
 *
 * @package ECash_Loan
 * @author Adam Englander <adam.englander@sellingsurce.com>
 */
interface ECash_Service_Loan_IAPI
{
	/**
	 * Test the service connection
	 *
	 * @return bool
	 */
	public function testConnection();

	/**
	 * @param int $application_id
	 * @param string $login
	 * @param string $old_password
	 * @param string $new_password 
	 */
	public function changeCustomerPassword($application_id, $login, $old_password, $new_password);
	
	/**
	 * Gets the customer login information
	 *
	 * @param int $application_id
	 * @return stdClass
	 */
	public function getCustomerLoginInfo($application_id);

	/**
	 * Get the loan data for the provided application ID including:
	 * 		apr - Loan APR (float)
	 * 		date_received - Date application was received
	 * 		date_confirmed - Date application was confirmed
	 * 		date_approved - Date application was approved
	 * 		date_funded - Date application was funded
	 * 		date_fund_estimated - Estimated date for funding
	 * 		payoff_amount - Loan payoff amount
	 * 		has_active_paid_out_date - Has an active paid out date
	 * 		paid_out_date - Date loan was paid out
	 * 		has_pending_transactions - Has pending transactions
	 * 		is_regulatory_flag - Is the regulatory flag set
	 *
	 * @param int $application_id
	 * @return array
	 */
	public function getLoanData($application_id);

	/**
	 * Get the information regarding the last payment including:
	 * 		date - Payment date
	 * 		amount - Payment amount
	 *
	 * @param int $application_id
	 * @return array
	 */
	public function getLastPayment($application_id);

	/**
	 * Get the information regarding the current balance
	 * 		next_due_date - Payment date
	 * 		amount_due - Payment amount
	 * 		principle_amount_due - Principal portion of total amount due
	 * 		service_charge_amount_due - Service charge portion of total amount due
	 * 		payoff_amount - Payoff amount
	 *
	 * @param unknown_type $application_id
	 * @return array
	 */
	public function getBalance($application_id);

	/**
	 * Retrieves a list of loans for the given customer.
	 *
	 * Each loan is returned with the application ID, current balance, and status.
	 *
	 * @param $username string Customer username
	 * @param $password string Customer password
	 * @return array[] Loan
	 */
	public function getCustomerLoans($username, $password);
	
	/**
	 * @param int $application_id
	 * @return string
	 */
	public function requestPayout($application_id);
	
	/**
	 * @param int $application_id
	 * @param int $amount
	 * @return string
	 */
	public function requestPaydown($application_id, $amount);
	
}
