<?php
/**
 *	FILE: blackbox.references.php
 *
 *	ABSTRACT
 * Reference handling
 *
 * Reference handling class for deciding to pass dummy data, blank data or real data
 
 * @author Thomas Phipps <thomas.phipps@sellingsource.com>
 */
class References {
	protected $references = array();
	protected $sql;
	
/**
 *	main construct
 *
 *	@param object $sql the sql object in case of needed to get the dummy data
 *	@return none
 */
	public function __construct($sql) {
		$this->sql =$sql;
	}
/**
 * the array of one of the references
 *
 * @param int $reference which reference you want
 * @return array the array of the reference
 */

	public function getReference($reference) {
		return $this->references[$reference];
	}

/**
 * add the Lead Data to the object and parse info to pick
 * what to return for this call
 *
 * @param mixed $lead_data the lead data to be parsed
 * @param string $target the property short of the current target
 * @return none
*/
	public function addLeadData($lead_data,$target) {
		if(isset($lead_data['data']['ref_01_name_full']))
		{
			$this->references[1]['full_name'] = $lead_data['data']['ref_01_name_full'];
			$tmp = explode(" ",$this->references[1]['full_name']);
			$this->references[1]['first_name'] = $tmp[0];
			$this->references[1]['last_name'] = $tmp[1];
			$this->references[1]['phone_number'] = $lead_data['data']['ref_01_phone_home'];
			$this->references[1]['relationship'] = $lead_data['data']['ref_01_relationship'];
			$this->references[2]['full_name'] = $lead_data['data']['ref_02_name_full'];
			$tmp = explode(" ",$this->references[2]['full_name']);
			$this->references[2]['first_name'] = $tmp[0];
			$this->references[2]['last_name'] = $tmp[1];
			$this->references[2]['phone_number'] = $lead_data['data']['ref_02_phone_home'];
			$this->references[2]['relationship'] = $lead_data['data']['ref_02_relationship'];
		}
		else {
			$reference_data = $this->getReferenceData($target);
			$this->references[1]['full_name'] = $reference_data[1]['fn'] . " " . $reference_data['1']['ln'];
			$this->references[1]['first_name'] = $reference_data[1]['fn'];
			$this->references[1]['last_name'] = $reference_data[1]['ln'];
			$this->references[1]['phone_number'] = $reference_data[1]['phone'];
			$this->references[1]['relationship'] = $reference_data[1]['relation'];
			$this->references[2]['full_name'] = $reference_data[2]['fn'] . " " . $reference_data[2]['ln'];
			$this->references[2]['first_name'] = $reference_data[2]['fn'];
			$this->references[2]['last_name'] = $reference_data[2]['ln'];
			$this->references[2]['phone_number'] = $reference_data[2]['phone'];
			$this->references[2]['relationship'] = $reference_data[2]['relation'];
		}
	}
	
	
	/**
	 * grab the reference data from the database
	 *
	 * @param string $target the property short of the current target
	 * @return array the reference data array
	 *
	 */
	protected function getReferenceData($target) {
		$query = "
		SELECT
			reference_data
		FROM
			rules as r
		JOIN
			target as t
			ON (t.target_id = r.target_id)
		WHERE
			t.property_short = '" . strtoupper($target) . "'
			AND r.status = 'ACTIVE'";
			$result = $this->sql->Query(NULL,$query);
			$tmp = $this->sql->Fetch_Column($result,'reference_data');
			return unserialize($tmp);
	}
}
?>