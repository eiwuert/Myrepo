<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 15, 2006
 *
 * @version $Revision$
 */

if (!defined('eCash_Document_DIR')) require_once LIB_DIR . "/Document/Document.class.php";

class eCash_Document_AutoEmail {

	static public function Send(Server $server, $application_id, $doc_id, $transaction_id = NULL)
	{
		$doc = "";
		require_once eCash_Document_DIR . "/ApplicationData.class.php";
		// The data returned by this was never even used anywhere!
		//$destination = eCash_Document_ApplicationData::Get_Email($server, $application_id);
		
		
		$info = eCash_Document_ApplicationData::Get_Recipient_Data($server, $application_id);

		require_once CUSTOMER_LIB . "/autoemail_list.php";
		$doc = Get_AutoEmail_Doc($server, $doc_id, $info->loan_type);

		eCash_Document::Log()->write("Sending document $doc to application_id $application_id", LOG_WARNING);
		
		if ($doc) 
		{
			$res = eCash_Document::singleton($server,NULL)->Send_Document($application_id, $doc, 'email', NULL, $transaction_id);
			eCash_Document::Log()->write("Send Result: " . var_export($res[$doc_id],true));
		}

	}
	
	/**
	 * Adds a document to the document queue for later sending.
	 *
	 * @param Server $server
	 * @param int $application_id
	 * @param string $doc_id
	 * @param int $transaction_register_id
	 */
	static public function Queue_For_Send(Server $server, $application_id, $doc_id, $transaction_register_id = NULL) {
		settype($application_id, 'int');
		if (!isset($transaction_register_id)) 
		{
			$transaction_register_id = 'NULL';
		}
		else
		{
			settype($transaction_register_id, 'int');
		}
		
		$db = ECash_Config::getMasterDbConnection();
		$query = "
			INSERT INTO document_queue
			  (
			  	date_created, 
			  	company_id, 
			  	application_id, 
			  	document_name, 
			  	transaction_register_id
			  ) VALUES (
			  	NOW(), 
			  	?,
			  	?,
			  	?, 
			  	?
			  )";
		
		$stmt = $db->Prepare($query);
		$stmt->bindParam(1, $server->company_id, PDO::PARAM_INT);
		$stmt->bindParam(2, $application_id, PDO::PARAM_INT);
		$stmt->bindParam(3, $doc_id, PDO::PARAM_INT);
		$stmt->bindParam(4, $transaction_register_id, PDO::PARAM_INT);
		$stmt->execute();
		
	}
	
	/**
	 * Sends all or a number of queued documents.
	 * 
	 * To send all documents that are queued call this function with only 1 
	 * parameter. To send a certain number of documents, pass that number as 
	 * the second parameter. Returns true if the documents sent.
	 *
	 * @param Server $server
	 * @param int $number_to_send
	 * @return bool
	 */
	static public function Send_Queued_Documents(Server $server, $number_to_send = -1) {
		settype($number_to_send, 'int');
		$company_id = $server->company_id;
		try 
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					document_queue_id,
					company_id,
					application_id,
					transaction_register_id,
					document_name
				  FROM
				  	document_queue
				  WHERE
				  	company_id = {$company_id}
				  ORDER BY
				  	date_created ASC
				  FOR UPDATE
			";
			
			if ($number_to_send > 0) 
			{
				$query .= "LIMIT {$number_to_send}";
			}
			
			$db = ECash_Config::getMasterDbConnection();
			$result = $db->query($query);
			
			while ($row = $result->fetch(PDO::FETCH_OBJ))
			{
				eCash_Document_AutoEmail::Send($server, $row->application_id, $row->document_name, $row->transaction_register_id);
				eCash_Document_AutoEmail::Remove_Queued_Document($server, $row->document_queue_id);
			}
			
		} 
		catch (Exception $e) 
		{
			get_log('main')->Write("There was an error in sending queued documents. Halting the process");
			return false;
		}
		return true;
	}
	
	/**
	 * Removes a document queue entry by id.
	 *
	 * @param Server $server
	 * @param int $document_queue_id
	 */
	static protected function Remove_Queued_Document(Server $server, $document_queue_id) {
		settype($document_queue_id, 'int');
		
		$db = ECash_Config::getMasterDbConnection();
		$query = "
			DELETE FROM
			  	document_queue
			  WHERE
			  	document_queue_id = {$document_queue_id};
		";
		
		$db->exec($query);
	}
}

?>
