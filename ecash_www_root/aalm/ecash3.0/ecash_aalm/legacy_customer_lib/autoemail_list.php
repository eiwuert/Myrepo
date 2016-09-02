<?php

/**
 * This trigger/document map is a copy of the Impact document list.
 * 
 * Not all triggers in eCash may be listed here, but references to existing
 * documents in the table have been mapped to the appropriate triggers.
 */
function Get_AutoEmail_Doc(Server $server, $doc, $loan_type = null)
{

	$autoemail_list = array(
			'ACCOUNT_SUMMARY'					=> "Account Summary",
			'APPROVAL_FUND' 					=> "Approval-Fund Letter",
			'APPROVAL_TERMS' 					=> "Approval-Terms Letter",
			'ARRANGEMENTS_MADE' 				=> "Arrangements Made",
			'ARRANGEMENTS_MISSED' 				=> "Arrangements Missed",
			'RETURN_LETTER_1_SPECIFIC_REASON' 	=> "Payment Failed",
			'RETURN_LETTER_2_SECOND_ATTEMPT' 	=> "Payment Failed",
			'RETURN_LETTER_3_OVERDUE_ACCOUNT' 	=> "Payment Failed",
			'RETURN_LETTER_4_FINAL_NOTICE' 		=> "Payment Failed",
			'WITHDRAWN_LETTER' 					=> "Withdrawn Letter",
			'DENIAL_LETTER_GENERIC' 			=> "Denial Letter-Generic",
			'LOAN_NOTE_AND_DISCLOSURE' 			=> "Loan Document",
			'CUSTOMER_SERVICE_REDESIGN' 		=> "Customer Service Redesign",
			'LOGIN_AND_PASSWORD' 				=> "Login And Password",
			'NEW_USERNAME_PASSWORD' 			=> "New Username Password",
			'REACT_OFFER'						=> "React Offer",
			'PAYMENT_REMINDER'					=> "Payment Reminder",
			'ZERO_BALANCE_LETTER'				=> "Zero Balance",
			//'DENIAL_LETTER_TELETRACK' 			=> "Denial Letter Teletrack",
			//nirvana emails moved into condor for AALM migration
			'UNSIGNED_APP_REQUEST'              => "Unsigned App Request",
			'REACT_OFFER'                       => "React Offer",
			'DENIAL_LETTER_DATAX'   			=> "Denial Letter-DataX",
			'LEAD_ACCEPTED'                     => "Lead Accepted",
			'ACH_RETURN'                        => "Payment Failed",
			'REFI_OFFER'                        => "Refinance Offer",
			'PAYMENT_FAILED'                    => "Payment Failed",
			'PAYMENT_RECEIPT'                    => "Payment Receipt",
			'CIF_OFFER'                         => "Collection SIF Offer"
	);

	if(isset($autoemail_list[$doc])) {
		return $autoemail_list[$doc];
	}
}
