<?php
//require_once '../ApplicationService/Queries.php';

/**
 * ECash Commercial Inquiry Sevice Query Class
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_Service_InquiryService_Queries extends ECash_Service_ApplicationService_Queries
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	public function __construct() {
		$this->db = ECash::getAppSvcDB();
	}

	/**
	 * Finds all of the inquiries for an application by aalm_key_id (application_id.) 
	 *
	 * @returns query result set object
	 */
	public function findInquiriesByApplicationQuery($application_id) {

	$query = 'SELECT bi.bureau_inquiry_id AS bureau_inquiry_id,
			bi.application_id AS application_id,
			bi.bureau_id AS bureau_id,
			bu.name_short AS bureau,
			bi.company_id AS company_id,
			bi.date_created AS date_created,
			bi.date_modified AS date_modified,
			bi.application_id AS external_id,
			bi.inquiry_type AS inquiry_type,
			bi.outcome AS outcome,
			bi.payrate AS payrate,
			bi.reason AS reason,
			bi.score AS score,
			bi.timer AS timer,
			bi.trace_info AS trace_info,
			bi.decision AS decision,
			bi.error_condition AS error_condition,
			bi.received_package AS receive_package,
			bi.sent_package AS sent_package
		FROM bureau_inquiry AS bi
			JOIN bureau AS bu USING (bureau_id)
			WHERE bi.application_id = '.$application_id.';';

		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Finds an inquiry by id
	 *
	 * @returns query result set object
	 */
	public function findInquiryQuery($inquiry_id) {

	$query = 'SELECT bi.bureau_inquiry_id AS bureau_inquiry_id,
			bi.application_id AS application_id,
			bi.bureau_id AS bureau_id,
			bu.name_short AS bureau,
			bi.company_id AS company_id,
			bi.date_created AS date_created,
			bi.date_modified AS date_modified,
			bi.application_id AS external_id,
			bi.inquiry_type AS inquiry_type,
			bi.outcome AS outcome,
			bi.payrate AS payrate,
			bi.reason AS reason,
			bi.score AS score,
			bi.timer AS timer,
			bi.trace_info AS trace_info,
			bi.decision AS decision,
			bi.error_condition AS error_condition,
                        bi.received_package AS receive_package,
		        bi.sent_package AS sent_package
		FROM bureau_inquiry AS bi
			JOIN bureau AS bu USING (bureau_id)
			WHERE bi.bureau_inquiry_id = '.$inquiry_id.';';

		$result = $this->db->query($query);
		return($result->fetch(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Finds if a ssn has a 
	 *
	 * @returns query result set object
	 */
	public function findSkipTraceBySsnQuery($ssn) {

		$query = 'SELECT st.skip_trace_id,
				st.date_created AS date_created,
				st.date_created AS date,
				st.date_modified AS date_modified,
				st.calltype AS calltype,
				st.calltype AS call_type,
				st.reason AS reason,
				st.pass AS pass,
				st.application_source AS application_source,
				st.external_id AS external_id,
				st.external_id AS application_id
			FROM skip_trace AS st
				WHERE st.ssn = '.$ssn.' AND pass = 0;';

		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Finds an duplicate inquiry by application id, bureau and time (within 10 minutes)
	 *
	 * @returns query result set object
	 */
	public function findDupInquiryQuery($inquiry) {
		$delay = 5*60;
		$delayed_date = date("Y-m-d G:i:s",time()-$delay);
		$date = date("Y-m-d G:i:s",time()+$delay);
	
		$query = 'SELECT bi.bureau_inquiry_id AS bureau_inquiry_id,
			bi.application_id AS application_id,
			bi.bureau_id AS bureau_id,
			bu.name_short AS bureau,
			bi.company_id AS company_id,
			bi.date_created AS date_created,
			bi.date_modified AS date_modified,
			bi.application_id AS external_id,
			bi.inquiry_type AS inquiry_type,
			bi.outcome AS outcome,
			bi.payrate AS payrate,
			bi.reason AS reason,
			bi.score AS score,
			bi.timer AS timer,
			bi.trace_info AS trace_info,
			bi.decision AS decision,
			bi.error_condition AS error_condition,
                        bi.received_package AS receive_package,
		        bi.sent_package AS sent_package
			FROM bureau_inquiry AS bi
			JOIN bureau AS bu USING (bureau_id)
			WHERE bi.application_id = '.$inquiry->application_id.'
			AND bu.name_short = "'.$inquiry->bureau.'"
			AND bi.date_created BETWEEN "'.$delayed_date.'" AND "'.$date.'";';

		$result = $this->db->query($query);
		return($result->fetch(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Inserts a brueau inquiry record.
	 *
	 * @returns the brueau_inquiry_id key geneterated by the database auto number
	 */
	public function recordInquiryQuery($inquiry){
		$date = date("Y-m-d G:i:s");
		
		$query = 'INSERT INTO bureau_inquiry (
				application_id,
				bureau_id,
				company_id,
				date_created,
				date_modified,
				inquiry_type,
				outcome,
				payrate,
				reason,
				score,
				timer,
				trace_info,
				decision,
				error_condition,
				received_package,
				sent_package
			) VALUES (
				'.$inquiry->application_id.',
				(SELECT bureau_id FROM bureau WHERE LOWER(name_short) = "'.strtolower($inquiry->bureau).'" LIMIT 1),
				'.$inquiry->company_id.',
				"'.$date.'",
				"'.$date.'",
				"'.$inquiry->inquiry_type.'",
				"'.$inquiry->outcome.'",
				'.$inquiry->payrate.',
				"'.$inquiry->reason.'",
				"'.$inquiry->score.'",
				'.$inquiry->timer.',
				"'.$inquiry->trace_info.'",
				"'.strtolower($inquiry->decision).'",
				"'.strtolower($inquiry->error_condition).'",
				COMPRESS('."'".utf8_encode(str_replace("'","`",$inquiry->receive_package))."'),
				COMPRESS('".utf8_encode(str_replace("'","`",$inquiry->sent_package))."')
			);";

		$result = $this->db->query($query);
		return($this->db->lastInsertId());
	}

	/**
	 * Inserts a skip trace record.
	 *
	 * @returns the skip_trace_id key geneterated by the database auto number
	 */
	public function recordSkipTraceQuery($ssn, $external_id, $source, $call_type, $reason, $status){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO skip_trace (
				date_created,
				date_modified,
				calltype,
				ssn,
				reason,
				pass,
				application_source,
				external_id
			) VALUES (
				"'.$date.'",
				"'.$date.'",
				"'.$call_type.'",
				'.$ssn.',
				"'.$reason.'",
				"'.$status.'",
				"'.$source.'",
				'.$external_id.'
			);';

		$result = $this->db->query($query);
		return($this->db->lastInsertId());
	}
}
