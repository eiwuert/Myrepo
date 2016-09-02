<?php 

/**
 * Returns an array of available search criterias that can be searched over.
 * 
 * The format of the array is as follows:
 * 
 * array(
 *   <short_name> => <human_friendly_label>,
 *   ...
 * );
 *
 * @return array
 */
function list_available_criteria_types() {
	
	return array(
		'application_id' => 'Application ID #',
		'customer_id' => 'Customer ID #',
		'name_last' => 'Last Name',
		'name_first' => 'First Name',
		'social_security_number' => 'Social Security #',
		'email' => 'Email',	//mantis:4253
		'ach_id' => 'ACH ID',	//mantis:5500
		//'ecld_id' => 'QuickCheck #',	// Disabled till Phase II
		'phone' => 'Phone #',	//mantis:4313
		'ip_address' => 'IP Address',	//mantis:4313
	);
}

?>
