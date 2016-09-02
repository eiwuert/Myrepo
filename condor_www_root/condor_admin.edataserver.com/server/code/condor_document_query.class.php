<?php
	
	class Condor_Document_Query
	{
		private $server;
		private $mysqli;
		
		public function __construct(Server $server)
		{
			$this->server = $server;
			$this->mysqli = $server->MySQLi();
		}
		
		public function Fetch_Documents($mode, 
			$date_start = null, 
			$date_end = null, 
			$application_id = null, 
			$document_id = null, 
			$sender = null, 
			$unlinked = false,
			$offset = 0,
			$max = 100)
		{
			if(!is_numeric($offset)) $offset = 0;
			if(!is_numeric($max)) $max = 100;
			$where = array();
			$query = "
				SELECT
					doc.document_id,
					doc.application_id,
					DATE_FORMAT(doc.date_created, '%m/%d/%Y %h:%i:%s %p') AS date_created,
					doc.subject,
					(
						SELECT
							dd0.sender
						FROM 
							".CONDOR_DB_NAME.".document_dispatch dd0
						WHERE
							dd0.document_id = doc.document_id
							AND dd0.transport = 'FAX'
						ORDER BY dd0.document_dispatch_id
						LIMIT 1
					) AS receive_from,
					doc.type
				FROM
					".CONDOR_DB_NAME.".document doc
					JOIN agent a ON a.agent_id = doc.user_id";
			
					
			$where[] = "a.company_id = {$this->server->company_id}";
			if (!is_null($date_start)) $where[] = "doc.date_created >= '$date_start'";
			if (!is_null($date_end)) $where[] = "doc.date_created <= '$date_end'";
			if (!is_null($application_id)) $where[] = "doc.application_id = $application_id";
			if (!is_null($document_id)) $where[] = "doc.document_id = $document_id";
			if (!is_null($sender))
			{
				$where[] = "doc.document_id in 
(
	select document_id
	from ".CONDOR_DB_NAME.".document_dispatch dd1
	where dd1.sender like '%".mysql_escape_string($sender)."%'
)";
			
			}
			if ($unlinked === true) $where[] = "doc.application_id is null";
			
			switch ($mode)
			{
				case 'INCOMING':
					$where[] = "doc.type = 'INCOMING'";
					break;
				case 'OUTGOING': $where[] = "doc.type = 'OUTGOING'"; break;
				case 'ALL':
				default : break;
			}
			
			if (sizeof($where)) $query .= "\nwhere " . implode ("\nand ", $where);
			$count = $this->Get_Number_Of_Docs($where);
							
			$query .= " limit $offset,$max";

			// Really... do we need to log everytime this query is ran?
			// Why do you hate hard drives? [benb]			
//			$this->server->log->Write(__METHOD__ . " : " . $query);
				
			
			$result = $this->mysqli->Query($query);
			
			$documents = array();
			
			while ($document = $result->Fetch_Object_Row())
			{
				$documents[] = $document;
			}
			
			return array($count,$documents);		
		}
		
		private function Get_Number_Of_Docs(&$where)
		{
			$query = 'SELECT 
						count(*) as count
					FROM
						'.CONDOR_DB_NAME.'.document doc
					JOIN agent a ON a.agent_id = doc.user_id';
			if(sizeof($where)) $query .= "\nwhere ".implode("\nand ",$where);
			$cnt = 0;
			try 
			{
				$result = $this->mysqli->Query($query);
				$row = $result->Fetch_Object_Row();
				$cnt = $row->count;
			}
			catch (Exception $e)
			{
				$this->server->log->Write(__METHOD__." : ".$query." \n".$e->getMessage());
			}
			return $cnt;
		}
		
		public function Fetch_Document_All($document_id)
		{
			$query = "
				SELECT 
					doc.document_id,
					DATE_FORMAT(doc.date_created, '%m/%d/%Y %h:%i:%s %p') date_created,
					DATE_FORMAT(doc.date_modified, '%m/%d/%Y %h:%i:%s %p') date_modified,
					doc.template_id,
					doc.root_id,
					doc.type,
					doc.subject,
					doc.user_id,
					doc.application_id,
					doc.space_key,
					doc.track_key,
					DATE_FORMAT(
						(
							SELECT
								max(ah.date_created)
							FROM
								".CONDOR_DB_NAME.".action_history ah
								JOIN ".CONDOR_DB_NAME.".document_action da
									ON da.document_action_id = ah.document_action_id
							WHERE
								da.name = 'SIGNED'
								AND ah.document_id = doc.document_id
						), '%m/%d/%Y %h:%i:%s %p') AS date_esignature
				FROM
					".CONDOR_DB_NAME.".document doc
				WHERE
					doc.document_id = $document_id";
			
			$result = $this->mysqli->Query($query);
			
			$document = $result->Fetch_Object_Row();
			
			$document->events = $this->Fetch_Event_History($document->document_id);
			$document->audit = $this->Fetch_Audit_Status($document->document_id);
			$document->dispatch = $this->Fetch_Dispatch($document->document_id);
			
			return $document;
		}
		
		public function Fetch_Event_History($document_id)
		{
			$query = "
				SELECT
					DATE_FORMAT(ah.date_created, '%m/%d/%Y %h:%i:%s %p') date_created,
					da.name event_type,
					ag.name_last user_name_last,
					ag.name_first user_name_first,
					aip.ip_address ip_address
				FROM 
					".CONDOR_DB_NAME.".action_history ah
					JOIN ".CONDOR_DB_NAME.".document_action da
						ON da.document_action_id = ah.document_action_id
					JOIN agent ag ON ag.agent_id = ah.user_id
					LEFT JOIN ".CONDOR_DB_NAME.".action_ip_address aip ON (aip.action_history_id = ah.action_history_id)
				WHERE
					ah.document_id = $document_id";
			
			
			$result = $this->mysqli->Query($query);
			
			$events = array();
			
			while ($event = $result->Fetch_Object_Row())
			{
				$events[] = $event;
			}
			
			return $events;
					
		}
		
		public function Fetch_Audit_Status($document_id)
		{
			$query = "
				SELECT
					DATE_FORMAT(p.date_created, '%m/%d/%Y %h:%i:%s %p') date_created,
					DATE_FORMAT(p.date_modified, '%m/%d/%Y %h:%i:%s %p') date_modified,
					p.parent_id,
					p.content_type,
					p.uri,
					p.file_name,
					DATE_FORMAT(p.date_audit, '%m/%d/%Y %h:%i:%s %p') date_audit,
					p.audit_status
				FROM
					".CONDOR_DB_NAME.".document_part dp
					JOIN ".CONDOR_DB_NAME.".part p ON (p.part_id = dp.part_id)
				WHERE
					dp.document_id = $document_id";
			
			$result = $this->mysqli->Query($query);
			
			$audit_status = new stdClass();
			$audit_status->status = 'success';
			$audit_status->date = time();
			
			while ($part_audit = $result->Fetch_Object_Row())
			{
				if ($part_audit->audit_status != 'SUCCESS')
				{
					$audit_status->status = 'failed';					
				}
				if ($part_audit->date_audit)
				{
					$audit_status->date = $part_audit->date_audit;
				}
			}
			
			return $audit_status;
		}
		
		/**
		 * Retrieves the document dispatch history for the given document ID.
		 *
		 * @param int $document_id
		 */
		public function Fetch_Dispatch($document_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					dd.document_dispatch_id,
					DATE_FORMAT(dd.date_created, '%m/%d/%Y %h:%i:%s %p') AS date_created,
					dd.transport,
					dd.recipient,
					dd.sender,
					DATE_FORMAT(dh.date_created, '%m/%d/%Y %h:%i:%s %p') AS last_modified,
					ds.name,
					ds.type AS status
				FROM
					".CONDOR_DB_NAME.".document_dispatch AS dd
					LEFT JOIN ".CONDOR_DB_NAME.".dispatch_history AS dh ON dh.dispatch_history_id = (
						SELECT
							MAX(dh2.dispatch_history_id)
						FROM
							".CONDOR_DB_NAME.".dispatch_history AS dh2
						WHERE
							dd.document_dispatch_id = dh2.document_dispatch_id
					)
					LEFT JOIN ".CONDOR_DB_NAME.".dispatch_status AS ds ON dh.dispatch_status_id = ds.dispatch_status_id
				WHERE
					dd.document_id = $document_id
				ORDER BY document_dispatch_id";
			
			$result = $this->mysqli->Query($query);
			
			$dispatch = array();
			
			while(($row = $result->Fetch_Object_Row()))
			{
				$dispatch[] = $row;
			}
			
			return $dispatch;
		}
		
		/**
		 * Returns the dispatch history for a single dispatch.
		 *
		 * @param int $dispatch_id
		 * @return array
		 */
		public function Fetch_Dispatch_History($dispatch_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					DATE_FORMAT(dh.date_created, '%m/%d/%Y %h:%i:%s %p') AS date_created,
					ds.name AS status_name,
					ds.type AS status_type
				FROM
					".CONDOR_DB_NAME.".dispatch_history AS dh
					JOIN ".CONDOR_DB_NAME.".dispatch_status AS ds
						ON dh.dispatch_status_id = ds.dispatch_status_id
				WHERE
					dh.document_dispatch_id = $dispatch_id
				ORDER BY dispatch_history_id";
			
			$result = $this->mysqli->Query($query);
			
			$history = array();
			
			while(($row = $result->Fetch_Object_Row()))
			{
				$history[] = $row;
			}
			
			return $history;
		}
	}
