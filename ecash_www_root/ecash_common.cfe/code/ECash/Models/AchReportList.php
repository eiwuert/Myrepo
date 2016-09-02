<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_AchReportList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_AchReport';
	}
	
	public function getTableName()
	{
		return 'ach_report';
	}
	
	public function loadHistory($start_date, $end_date, $company_id)
	{
		$query = "
			SELECT 	date_modified, date_created, date_request,
					company_id, ach_report_id, ach_report_request,
					report_status, report_type, delivery_method,
					content_hash, ach_provider_id
			FROM ach_report 
			WHERE date_request >= ? AND date_request <= ?
			AND company_id = ?
			ORDER BY ach_report_id DESC	"  ;
		$this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				array($start_date, $end_date, $company_id)
		);
		
	}
	function __sleep()
	{
	}
}

?>
