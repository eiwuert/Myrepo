<?php

/**
 * ECash Commercial Document Sevice Query Class
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_Service_DocumentService_Queries extends ECash_Service_ApplicationService_Queries
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	protected $query = 'SELECT doc.document_id AS document_id,
			doc.date_created AS date_created,
			doc.date_modified AS date_modified,
			doc.company_id AS company_id,
			doc.application_id AS application_id,
			dl.name AS document_list_name,
			doc.document_method AS document_method,
			doc.document_method_legacy AS document_method_legacy,
			doc.document_event_type AS document_event_type,
			dl.name AS name_other,
			doc.document_id_ext AS document_id_ext,
			doc.agent_id AS agent_id,
			doc.signature_status AS signature_status,
			doc.sent_to AS sent_to,
			doc.transport_method AS transport_method,
			doc.archive_id AS archive_id,
			doc.sent_from AS sent_from
		FROM document AS doc
			JOIN document_list AS dl ON (doc.document_list_id = dl.document_list_id) ';
			//doc.name_other AS name_other,
	
	public function __construct() {
		$this->db = ECash::getAppSvcDB();
	}

	/**
	 * Finds all of the documents for an application by application_id (i.e. ecash application id.) 
	 *
	 * @returns query result set object
	 */
	public function findApplicationDocumentsQuery($application_id) {
		$query = $this->query.'
			WHERE doc.application_id = '.$application_id.';';
		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Finds all of the documents for an application by application_id (i.e. ecash application id.) 
	 *
	 * @returns query result set object
	 */
	public function findArchivedDocumentQuery($archive_id) {
		$query = $this->query.'
			WHERE doc.archive_id = '.$archive_id.';';
		$result = $this->db->query($query);
		return($result->fetchAll(DB_IStatement_1::FETCH_OBJ));
	}

	/**
	 * Gets an a document by id  
	 *
	 * @returns single query result row object
	 */
	public function getDocumentQuery($document_id) {
		$query = $this->query.'
			WHERE doc.document_id = '.$document_id.';';

		$result = $this->db->query($query);
		$rows = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}

	/**
	 * Inserts a new document.
	 *
	 * @returns the document_id key geneterated by the database auto number
	 */
	public function insertDocumentQuery($doc){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO document (
				date_created,
				date_modified,
				application_id,
				company_id,
				document_list_id,
				document_method,
				document_method_legacy,
				document_event_type,
				name_other,
				document_id_ext,
				agent_id,
				signature_status,
				sent_to,
				transport_method,
				archive_id,
				sent_from
			) VALUES (
				"'.$date.'",
				"'.$date.'",
				'.$doc->application_id.',
				'.$doc->company_id.',
				(SELECT document_list_id FROM document_list WHERE LOWER(name) = "'.strtolower($doc->document_list_name).'" LIMIT 1),
				"'.$doc->document_method.'",
				"'.$doc->document_method_legacy.'",
				"'.$doc->document_event_type.'",
				"'.$doc->name_other.'",
				"'.$doc->document_id_ext.'",
				'.$doc->agent_id.',
				"'.$doc->signature_status.'",
				"'.$doc->sent_to.'",
				"'.$doc->transport_method.'",
				'.$doc->archive_id.',
				"'.$doc->sent_from.'"
			);';
		$result = $this->db->query($query);

		$rows = $result->execute();
		return($this->db->lastInsertId());
	}//getNextDocumentID

        /**
         * Updates the Document table.
         *
         * @returns boolean
         */
         public function getNextDocumentID(){
 		$query = 'SELECT MAX(document_id) AS ID FROM document;';
                $result = $this->db->query($query);
		$row = $result->fetch(DB_IStatement_1::FETCH_OBJ);
		return ($row->ID+1);
	}
	
	/**
	 * Updates the Document table
	 *
	 * @returns boolean
	 */
	public function updateDocumentQuery($doc){
		$date = date("Y-m-d G:i:s");
		$query = 'UPDATE document
				date_modified = "'.$date.'",
				document_list_id = (SELECT document_list_id FROM document_list WHERE LOWER(name) = "'.strtolower($doc->document_list_name).'" LIMIT 1),
				document_method = "'.$doc->document_method.'",
				document_method_legacy = "'.$doc->document_method_legacy.'",
				document_event_type = '.$doc->document_event_type.',
				name_other = "'.$doc->name_other.'",
				document_id_ext = "'.$doc->document_id_ext.'",
				agent_id = '.$doc->agent_id.',
				signature_status = "'.$doc->signature_status.'",
				sent_to = "'.$doc->sent_to.'",
				transport_method = "'.$doc->transport_method.'",
				archive_id = '.$doc->archive_id.',
				sent_from = "'.$doc->sent_from.'"
			WHERE document_id ='.$doc->document_id.';';

		$result = $this->db->query($query);
		return($result->execute());
	}

}
