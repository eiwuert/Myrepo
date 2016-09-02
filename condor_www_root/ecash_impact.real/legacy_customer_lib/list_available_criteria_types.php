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
		'name_last' => 'Last Name',
		'name_first' => 'First Name',
		'social_security_number' => 'Social Security #',
		'phone'                  => 'Phone Number'
	);
}

?>
