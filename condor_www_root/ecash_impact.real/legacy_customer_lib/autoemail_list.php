<?php

function Get_AutoEmail_Doc(Server $server, $doc, $loan_type = null)
{
	$autoemail_list = array('ACCOUNT_SUMMARY'					=> 'Account Summary',
							'APPROVAL_FUND' 					=> "Approval-Fund Letter",
							'APPROVAL_TERMS' 					=> "Approval-Terms Letter",
							'ARRANGEMENTS_MADE' 				=> "Arrangements Made",
							'ARRANGEMENTS_MISSED' 				=> "Arrangements Missed",
							'RETURN_LETTER_1_SPECIFIC_REASON' 	=> "Return Letter 1-Specific Reason",
							//'RETURN_LETTER_2_SECOND_ATTEMPT' 	=> "Return Letter 2-Second Attempt",
							//'RETURN_LETTER_3_OVERDUE_ACCOUNT' 	=> "Return Letter 3-Overdue Account",
							'RETURN_LETTER_4_FINAL_NOTICE' 		=> "Return Letter 4-Final Notice",
							'WITHDRAWN_LETTER' 					=> "Withdrawn Letter",
							'DENIAL_LETTER_GENERIC' 			=> "Denial Letter-Generic",
							'DENIAL_LETTER_TELETRACK' 			=> "Denial Letter - Teletrack",
							'LOAN_NOTE_AND_DISCLOSURE' 			=> "Loan Document",
							'CUSTOMER_SERVICE_REDESIGN' 		=> "Customer Service Redesign",
							'LOGIN_AND_PASSWORD' 				=> "Login And Password",
							'NEW_USERNAME_PASSWORD' 			=> "New Username Password",
							'CONFIRMATION_LETTER'				=> "Unsigned App Request"
							);
							
	switch ($doc) {
		case "REACT_OFFER":
			return eCash_Document::Get_Document_List($server, "packaged", " AND p.document_package_name = 'Reactivation Packet' ");
		
		default:
			if(isset($autoemail_list[$doc])) {
				return $autoemail_list[$doc];
			}
			
	}
	
}
