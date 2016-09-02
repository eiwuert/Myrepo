<?php 

/**
 * Returns an array containing the names of the customer's external collection 
 * companies.
 * 
The format of the array is as follows:
 * 
 * array(
 *   <company_short_name> => <company_name>,
 *   ...
 * );
 *
 * @return array
 */
function list_available_collection_companies() {
	
	return array(
		
		'other' => 'Other'
	);
}

?>
