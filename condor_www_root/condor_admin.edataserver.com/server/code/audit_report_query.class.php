<?php

	class Audit_Report_Query
	{
		private $server;
		private $mysqli;
		
		public function __construct(Server $server)
		{
			$this->server = $server;
			$this->mysqli = $server->MySQLi();
		}
		
		/**
		 * returns an array of failed audits for a given execution_id
		 * 
		 * @param int $execution_id report id to fetch failures for
		 * @return array
		 */
		public function Fetch_Failed_Document_Audits($execution_id)
		{
			$query = "
				select
					doc.document_id,
					doc.application_id,
					part.file_name,
					pa0.part_audit_id,
					unix_timestamp(pa0.date_audit) date_audit,
					pa0.part_id,
					pa0.audit_status
				from part_audit pa0
				join part on part.part_id = pa0.part_id
				join document_part dp on dp.part_id = pa0.part_id
				join document doc on doc.document_id = dp.document_id				
				where pa0.execution_id = $execution_id
				and pa0.audit_status in ('MODIFIED','MISSING')";
			
			$result = $this->mysqli->Query($query, CONDOR_DB_NAME);
			
			$audits = array();
			
			while ($audit = $result->Fetch_Object_Row())
			{
				$audits[$audit->document_id][] = $audit;
			}
			
			return $audits;	
		}
		
		public function Fetch_Failed_FS_Audits($execution_id)
		{
			$query = "
				select
					fsa.file_path,
					fsa.file_size,
					fsa.file_hash,
					fsa.filesystem_audit_id,
					unix_timestamp(fsa.date_audit) date_audit,
					fsa.status
				from filesystem_audit fsa
				where fsa.execution_id = $execution_id
				and fsa.status in ('UNLINKED')";
			
			$result = $this->mysqli->Query($query, CONDOR_DB_NAME);
			
			$audits = array();
			
			while ($audit = $result->Fetch_Object_Row())
			{
				$audits[] = $audit;
			}
			
			return $audits;				
		}
		
		public function Fetch_FS_Report_List()
		{
			$query = "
				select
					fsa.execution_id,
					unix_timestamp(cast(fsa.date_audit as date)) date_audit,
					status,
					count(*) count
				from filesystem_audit fsa
				group by fsa.execution_id, status
				order by fsa.execution_id desc";
			
			$result = $this->mysqli->Query($query, CONDOR_DB_NAME);
			
			$reports = array();
			
			while ($report = $result->Fetch_Object_Row())
			{
				if (!isset($reports[$report->execution_id]))
				{
					$reports[$report->execution_id] = $report;
					$reports[$report->execution_id]->count_linked = 0;
					$reports[$report->execution_id]->count_unlinked = 0;
				}
				
				// I'd just like to come out and say that I wrote the line below because I didn't think php was gay enough to let me do it
				// but apparently it is, and so the line will live in infamy.
				$reports[$report->execution_id]->{"count_".strtolower($report->status)} = $report->count;
			}
			
			return $reports;
		}
		
		/**
		 * returns an array of audit report summaries
		 *
		 * @return array
		 */
		public function Fetch_Report_List()
		{
			$query = "
				select
					pa0.execution_id,
					unix_timestamp(cast(pa0.date_audit as date)) date_audit,
					audit_status,
					count(*) count
				from part_audit pa0
				where
					pa0.execution_id is not null
				group by pa0.execution_id, audit_status
				order by pa0.execution_id desc";
			
			$result = $this->mysqli->Query($query, CONDOR_DB_NAME);
			
			$reports = array();
			
			while ($report = $result->Fetch_Object_Row())
			{
				if (!isset($reports[$report->execution_id]))
					$reports[$report->execution_id] = $report;
				
				// I'd just like to come out and say that I wrote the line below because I didn't think php was gay enough to let me do it
				// but apparently it is, and so the line will live in infamy.
				$reports[$report->execution_id]->{"count_".strtolower($report->audit_status)} = $report->count;
			}
			
			return $reports;
		}
	}

?>