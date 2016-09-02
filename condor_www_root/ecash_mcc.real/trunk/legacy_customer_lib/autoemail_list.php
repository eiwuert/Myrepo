<?php

/**
 * This trigger/document map is a copy of the Impact document list.
 * 
 * Not all triggers in eCash may be listed here, but references to existing
 * documents in the table have been mapped to the appropriate triggers.
 */
function Get_AutoEmail_Doc(Server $server, $doc, $loan_type = null)
{
	// this is horrible
	$autoemail_list = array(
			'ACCOUNT_SUMMARY'					=> 'Account Summary - FAX',
			'APPROVAL_FUND' 					=> "Approval-Fund Letter",
			'APPROVAL_TERMS' 					=> "Approval-Terms Letter",
			'ARRANGEMENTS_MADE' 				=> "Arrangements Made",
			'ARRANGEMENTS_MISSED' 				=> "Arrangements Missed",
			'RETURN_LETTER_1_SPECIFIC_REASON' 	=> "Return Letter 1-Specific Reason",
			'RETURN_LETTER_2_SECOND_ATTEMPT' 	=> "Return Letter 2-Second Attempt",
			'RETURN_LETTER_3_OVERDUE_ACCOUNT' 	=> "Return Letter 3-Overdue Account",
			'RETURN_LETTER_4_FINAL_NOTICE' 		=> "Return Letter 4-Final Notice",
			'RETURN_LETTER_EARLY_PAYMENT'		=> "Return Letter 5-CSO Manual Payment Failed",
			'WITHDRAWN_LETTER' 					=> "Withdrawn Letter",
			'DENIAL_LETTER_GENERIC' 			=> "Denial Letter-Generic",
			'LOAN_NOTE_AND_DISCLOSURE' 			=> "Loan Document",
			'CUSTOMER_SERVICE_REDESIGN' 		=> "Customer Service Redesign",
			'LOGIN_AND_PASSWORD' 				=> "Login And Password",
			'NEW_USERNAME_PASSWORD' 			=> "New Username Password",
			'REACT_OFFER'						=> "React Offer",
			'ZERO_BALANCE_LETTER'				=> "Zero Balance Letter",
			//'DENIAL_LETTER_TELETRACK' 			=> "Denial Letter Teletrack",
			'ONLINE_NOTE'                       => "Online Note",
			'CREDIT_SERVICES_AGREEMENT'         => "Credit Services Agreement"
			);

	if(isset($autoemail_list[$doc])) 
	{
		return $autoemail_list[$doc];
	}
}
