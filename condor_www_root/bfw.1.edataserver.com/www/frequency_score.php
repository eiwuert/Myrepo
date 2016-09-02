<?php

include_once BFW_CODE_DIR . 'accept_ratio_singleton.class.php';

/**
 * @author Demin Yin <Demin.Yin@SellingSource.com> 
 * @see    GForge #3838 - NEW PROJECT - API for Frequency Score
 * @since  Mon 28 Jan 2008 12:07:48 PM PST
 */
class FrequencyScore extends OLPWebService
{	
	/**
	 * Maximum length of promo sub code.
	 * @var int 
	 */
	const PROMO_SUB_CODE_LENGTH = 250; // the column length is 250.
	

	/**
	 * This is a web service for 3rd parties in which they can ping us with an email address, and we pass back frequency score for the given email address: How many times we've attempted to send a lend (submitted by the applicant who owns that email address) to any lender that passed all business rules. The return value is an array of 3 items indicating how many attempts (frequency) in 1) Last 1 hour 2) Last 24 hrs 3) Last 7 days. With the 5th request argument 'return_type', you can specified in which format data should be returned: CSV format (return_type = 1), Json format (return_type = 2), or PHP serialized format (return_type = 3).
	 * 
	 * @param string $email Email address.
	 * @param string $license_key License key.
	 * @param string $promo_id Promo ID.
	 * @param string $promo_sub_code This is an optional parameter.
	 * @param int $return_type Return type. See OLPWebService::$return_types for details.
	 * @return string If input data are valid, return a string containing frequency score information; otherwise, return a string starting with 'ERROR' and followed by description of the error.
	 */
	public function getFrequencyScore($email, $license_key, $promo_id, $promo_sub_code = '', $return_type = NULL)
	{
		$status = $this->hasValidEmail($email);
		if ($status !== TRUE)
		{
			return $status;
		}
		
		$status = $this->hasAccessPermission($license_key, $promo_id, $promo_sub_code);
		if ($status !== TRUE)
		{
			return $status;
		}
		
		$this->setConnection();
		
		// $current_scores = array(
		//     (intval)SCORE_OF_RECENT_1_HOUR,
		//     (intval)SCORE_OF_RECENT_1_DAY,
		//     (intval)SCORE_OF_RECENT_1_WEEK,
		// );
		$freq_object = Accept_Ratio_Singleton::getInstance($this->sql);
		$current_scores = $freq_object->getPeriodicDeclines($email); // return value is an array

		$this->insertQueryToDB($email, $promo_id, $promo_sub_code);
		
		return $this->getReturnValue($current_scores, $return_type);
	}
	
	/**
	 * Insert query to table 'freq_query_log'.
	 *
	 * @param string $email Email address.
	 * @param string $promo_id Promo ID.
	 * @param string $promo_sub_code Promo sub code.
	 * @return boolean True if inserted properly; otherwise false.
	 */
	private function insertQueryToDB($email, $promo_id, $promo_sub_code)
	{
		$email = mysql_real_escape_string($email, $this->sql->Connect());
		
		$promo_sub_code = substr(trim($promo_sub_code), 0, self::PROMO_SUB_CODE_LENGTH);
		
		if ($promo_sub_code)
		{
			$promo_sub_code = '\'' . mysql_real_escape_string($promo_sub_code, $this->sql->Connect()) . '\'';
		}
		else
		{
			$promo_sub_code = 'NULL';
		}
		
		$query = "
            INSERT INTO `freq_query_log` (
                `query_id`, 
                `email`,
                `promo_id`,
                `promo_sub_code`,
                `created_date`)
            VALUES (
                '',
                '{$email}',
                '{$promo_id}',
                {$promo_sub_code},
                NOW())
        ";
		
		$this->sql->Query($this->database, $query);
		
		return ($this->sql->Affected_Row_Count() > 0) ? TRUE : FALSE;
	}
}
