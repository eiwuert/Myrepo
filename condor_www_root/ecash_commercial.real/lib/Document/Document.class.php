<?php
/**
 * Document
 * Document management module for ecash
 *
 * @package Documents
 * @category Document_Management
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

require_once SQL_LIB_DIR ."/application.func.php";

define("eCash_Document_DIR", dirname(realpath(__FILE__)));

// require_once eCash_Document_DIR . "/XMLFormat.class.php";

class eCash_Document {

	private $server;
	private $request;

	static private $log_context;
	
	static private $instance = array();

	static public $package_list_fields = array('name','name_short','document_list_id','active_status');
	static public $document_list_fields = array('name', 'name_short', 'active_status', 'required', 'esig_capable','send_method','document_api','only_receivable');
	
	static public $message;
	
	static public function Factory(Server $server, $request)
	{
		return new eCash_Document($server, $request);
	}

	/**
	 * returns a singleton
	 *
	 * @param Server $server
	 * @param unknown_type $request
	 * @return eCash_Document
	 */
	static public function Singleton(Server $server, $request)
	{
		$key = md5(serialize(array($server,$request)));

		if(!isset(self::$instance[$key]) || !(self::$instance[$key] instanceof eCash_Document)) 
		{
			self::$instance[$key] = self::Factory($server, $request);
		}

		return self::$instance[$key];

	}

	public function __construct(Server $server, $request)
	{

		$this->server = $server;
		$this->transport = ECash::getTransport();
		$this->request = $request;

	}

	static public function Get_Document_List(Server $server, $type = NULL,  $addtl_where = NULL, $require_active = TRUE)
	{
		switch(strtolower($type)) 
		{
			case "package-display":
				require_once eCash_Document_DIR . "/Type/Packaged.class.php";
				return eCash_Document_Type_Packaged::Get_Display_List($server,  $addtl_where, $require_active);
				break;

			case "packaged":
				require_once eCash_Document_DIR . "/Type/Packaged.class.php";
				return eCash_Document_Type_Packaged::Get_Document_List($server,  $addtl_where, NULL, $require_active);
				break;

			case "receive":
				require_once eCash_Document_DIR . "/Type/Receive.class.php";
				return eCash_Document_Type_Receive::Get_Document_List($server,  $addtl_where, 'receive', $require_active);
				break;

			case "send":
				require_once eCash_Document_DIR . "/Type/Send.class.php";
				return eCash_Document_Type_Send::Get_Document_List($server,  $addtl_where, 'send', $require_active);
				break;

			case "esig":
				require_once eCash_Document_DIR . "/Type/Esig.class.php";
				return eCash_Document_Type_Esig::Get_Document_List($server,  $addtl_where, NULL, $require_active);
				break;

			default:
				require_once eCash_Document_DIR . "/Type/Send.class.php";
				return eCash_Document_Type::Get_Document_List($server, $addtl_where, NULL, $require_active);
				break;

		}

	}

	/**
	 * replaces document_query::Fetch_Application_Docs
	 */
	static public function Get_Application_History(Server $server, $application_id, $event = NULL)
	{
		require_once eCash_Document_DIR . "/ApplicationData.class.php";

		return eCash_Document_ApplicationData::Get_History($server, $application_id, $event);

	}

	static public function Get_Application_Data(Server $server, $application_id, $transaction_id = NULL)
	{
		require_once eCash_Document_DIR . "/ApplicationData.class.php";

		return eCash_Document_ApplicationData::Get_Data($server, $application_id, $transaction_id);

	}

	public function Get_Document_Id($doc_name, $require_active = FALSE)
	{
		$record  = $this->Get_Documents_By_Name($doc_name, $require_active);

		return ($record && !is_array($record)) ? $record->document_list_id : FALSE ;

	}

	public function Get_Documents_By_Name ($doc_name, $require_active = FALSE)
	{
		
		if(is_array($doc_name)) 
		{
			foreach($doc_name as $t) 
			{
				if (is_numeric(($t))) 
				{
					return $this->Get_Documents($doc_name, $require_active);
				}
			}
		} 
		elseif(is_numeric($doc_name)) 
		{
			return $this->Get_Documents($doc_name, $require_active);
		}

		
		$sql_piece = is_array($doc_name) ? " AND l.name IN ('" . implode("','",$doc_name) . "')" : " AND l.name = '{$doc_name}'" ; //"

		$document_list = self::Get_Document_List($this->server, NULL, $sql_piece, $require_active);
		
		if(!is_array($doc_name) && isset($document_list) && is_array($document_list)) 
		{
			return array_shift($document_list);
		} 
		elseif (!isset($document_list)) 
		{
			return false;
		} 
		else 
		{
			return $document_list;
		}

	}

	public function Get_Documents($id, $require_active = FALSE)
	{

		if(is_array($id)) 
		{
			foreach($id as $t) 
			{
				if (!is_numeric(($t))) 
				{
					return $this->Get_Documents_By_Name($id, $require_active);
				}
			}
		} 
		elseif(!is_numeric($id)) 
		{
			return $this->Get_Documents_By_Name($id, $require_active);
		}

		$sql_piece = is_array($id) ? " AND l.document_list_id IN (" . implode(",",$id) . ")" : " AND l.document_list_id = {$id}" ; //"

		$document_list = self::Get_Document_List($this->server, NULL, $sql_piece, $require_active);

		if(!is_array($id) && is_array($document_list)) 
		{
			return array_shift($document_list);
		} 
		elseif (!$document_list) 
		{
			return false;
		} 
		else 
		{
			return $document_list;
		}

	}

	public function Send_Document($application_id, $document_list, $send_method = "email", $destination_override = NULL, $transaction_id = NULL)
	{
		//$this->server->log->write(__METHOD__ . " Given Documents: " . var_export($document_list,true));
		
		$app = self::Get_Application_Data($this->server, $application_id, $transaction_id);
//throw new Exception();
		if(is_array($document_list) && count($document_list) > 0 && is_object(current($document_list)) && isset(current(current($document_list)->bodyparts)->document_list_id) && is_numeric(current(current($document_list)->bodyparts)->document_list_id)) 
		{
			$docs = $document_list;
		} 
		elseif (is_array($document_list) && count($document_list) > 0 && is_object(current($document_list)) && isset(current($document_list)->document_list_id) && is_numeric(current($document_list)->document_list_id)) 
		{
			$docs = $document_list;
		} 
		elseif (($docs = $this->Get_Documents($document_list, true)) !== false) 
		{
			$docs = (is_array($docs)) ? $docs : array($docs);
		} 
		else 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: Invalid Document");
		}
		//self::Log()->write("List of Documents: " . var_export($docs,true), LOG_DEBUG);		
		$tcondor = array();
		$tcopia = array();
		foreach ($docs as $tdoc) 
		{
			switch (strtolower($tdoc->document_api)) 
			{
				case "condor":
					$tcondor[] = $tdoc;
					break;

				case "copia":
					$tcopia[] = $tdoc;
			}
		}

		$rcondor = array();
		if(count($tcondor)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
			$rcondor = eCash_Document_DeliveryAPI_Condor::Send($this->server, $app, $tcondor, $send_method, $destination_override);
		}

		$rcopia = array();
		if(count($tcopia)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Copia.class.php";
			$rcopia = eCash_Document_DeliveryAPI_Copia::Send($this->server, $app, $tcopia, $send_method, $destination_override);
		}

		$result = array_merge($rcondor, $rcopia);

		return (isset($result)) ? $result : array(0 => array("status" => "failed")) ;

	}

	public function Receive_Document($request)
	{

		$docs = array_keys($request->document_list);
		$document_list = $this->Get_Documents($docs);

		if(!is_array($document_list)) $document_list = array($document_list);

		$tcondor = array();
		$tcopia = array();
		foreach($document_list as $doc) 
		{
			switch (strtolower($doc->document_api)) 
			{
				case "condor":
					$tcondor[] = $doc;
					break;
					
				case "copia":
					$tcopia[] = $doc;
			}
		}

		if(count($tcondor)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
			$success = eCash_Document_DeliveryAPI_Condor::Receive($this->server, $tcondor, $request);
		}

		if(count($tcopia)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Copia.class.php";
			$success = eCash_Document_DeliveryAPI_Copia::Receive($this->server, $tcopia, $request);
		}
		

		if ($success) 
		{
			foreach($document_list as $doc ) 
			{
				// Calls the CFE event, don't know why I have to get the application at this point, but I do.
				$app    = ECash::getApplicationById($_SESSION['current_app']->application_id);
				$engine = ECash::getEngine();
				$engine->executeEvent('DOCUMENT_RECEIVED', array($doc->document_list_id));
				
				if(($process_status_id = self::Get_Status_Trigger($this->server, $_SESSION['current_app']->application_id, $doc->document_list_id)) !== FALSE) 
				{
					Update_Status($this->server, $request->application_id, intval($process_status_id), null, null, TRUE, "Verification (react)");
				}
			}
		}
		
		$_SESSION['current_app']->docs = self::Get_Application_History($this->server, $_SESSION['current_app']->application_id);
		ECash::getTransport()->Set_Data($_SESSION['current_app']);
		ECash::getTransport()->Add_Levels('overview','receive_documents','edit','documents','view');

	}

	public function Preview_Document($application_id, $document_list)
	{
		$app = self::Get_Application_Data($this->server, $application_id);

		if (($docs = $this->Get_Documents($document_list)) !== false) 
		{
			$docs = (is_array($docs)) ? $docs : array($docs);

			$tcondor = array();
			$tcopia = array();
			foreach ($docs as $tdoc) 
			{
				switch (strtolower($tdoc->document_api)) 
				{
					case "condor":
						$tcondor[] = $tdoc;
						break;

					case "copia":
						throw new Exception (__METHOD__ . " Error: Preview_Document not supported for Copia.");
				}
			}

			$rcondor = array();
			if(count($tcondor)) 
			{
				require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
				$rcondor = eCash_Document_DeliveryAPI_Condor::Preview($this->server, $app, $tcondor);
			}

		}

	}

	public function Update_List_Sort($doc_array, $which = 'send')
	{
		if (!is_array($doc_array)) 
		{
			$doc_array = array($doc_array);
		}
		
		$field = ($which == 'receive') ? 'doc_receive_order' : 'doc_send_order';
		
		if (!ctype_digit((string) implode("",array_keys($doc_array))) || !ctype_digit((string) implode("",$doc_array)) ) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: list must be a numerically indexed array of document_list_ids");
		}
		try 
		{
		
			foreach ($doc_array as $key => $did) 
			{
				$doc_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE
						document_list
					SET
						{$field} = {$key}
					WHERE
						document_list_id = {$did}
						";
				$db = ECash_Config::getMasterDbConnection();
				$db->exec($doc_query);
			}
		} 
		catch (Exception $e) 
		{
			get_log('main')->Write("There was an error sorting document list.");
			throw $e;
		}
	}
	
	public function Update_Document_Package($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(!isset($doc->document_package_id) || !$doc->document_package_id) 
		{
			return $this->New_Document_Package($doc);
		}
		
		$values = array_intersect(array_keys((array) $doc), self::$package_list_fields);

		$db = ECash_Config::getMasterDbConnection();
		
		$update = array();
		foreach($values as $field ) {
			$update[] = "{$field} = " . $db->quote($doc->$field);
		}

		try 
		{
			if (is_array($doc->attachments) && count($doc->attachments)) 
			{
							
				$atch_chk_query = "
					SELECT
						document_list_id
					FROM
						document_list_package
					WHERE
						document_package_id = {$doc->document_package_id}
					";
				
				$doc_id_ary = $db->querySingleColumn($atch_chk_query);
			
				$removes = array_diff($doc_id_ary, $doc->attachments);
				$additions = array_diff($doc->attachments, $doc_id_ary);
			
				if (count($removes)) 
				{
					$del_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						DELETE FROM document_list_package
						WHERE
							document_package_id = {$doc->document_package_id}
						AND
							document_list_id IN (" . implode(",", $removes) . ")
					";
					
					$db->exec($del_query);
						
				}
					
				if (count($additions)) 
				{
					$lines = array();
					foreach($additions as $doc_id) 
					{
						$lines[] = "(now(),now(), {$this->server->company_id}, {$doc->document_package_id}, {$doc_id})";
					}
					
					$doc_query = "
						INSERT INTO document_list_package
							(date_modified, date_created, company_id, document_package_id, document_list_id)
						VALUES
							" . implode(",\n",$lines);
					$db->exec($doc_query);
					
				}
			} 
			else 
			{
				$del_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					DELETE FROM document_list_package
					WHERE
						document_package_id = {$doc->document_package_id}
				";
				$db->exec($del_query);
				
			}
			
			if (count($update)) 
			{
				
				$doc_query = "
					UPDATE
						document_package
					SET
						" . implode(", ", $update) . "
					WHERE
						document_package_id = {$doc->document_package_id}
				";
				$db->exec($doc_query);
			
			}				
		} 
		catch (Exception $e) 
		{
			throw $e;
		}	
		
	}
	
	public function New_Document_Package($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(isset($doc->document_package_id) && $doc->document_package_id) 
		{
			return $this->Update_Document_Package($doc);
		}
		
		$missing = array_diff(self::$package_list_fields, array_keys((array) $doc));
		
		if (count($missing)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: the required values are missing: " . implode(", ", $missing));
		}
		
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			$insert = array();
			foreach(self::$package_list_fields as $field) 
			{
				if (!$doc->$field) 
				{
					throw new OutOfRangeException(__METHOD__ . " Error: Required value is missing for field {$field}");
				}
				
				$insert[] = $db->quote($doc->$field);
				
			}
				
			$doc_query = "
				INSERT INTO	document_package
					(date_modified, date_created, company_id, " . implode (",", self::$package_list_fields) . ")
				VALUES
					(now(), now(), {$this->server->company_id}, " . implode(", ", $insert) . ")
			";

			$db->exec($doc_query);		
			$doc->document_package_id = $db->lastInsertId();
			
			if(!$doc->document_package_id) 
			{
				throw new RuntimeException(__METHOD__ . " Error: Unknown MySQL error. No value returned for document_package insert.");
			}
			
			if (is_array($doc->attachments) && count($doc->attachments)) 
			{
							
				$lines = array();

				foreach($doc->attachments as $doc_id) 
				{
					$lines[] = "(now(),now(), {$this->server->company_id}, {$doc->document_package_id}, {$doc_id})";
				}
					
				$doc_query = "
					INSERT INTO document_list_package
						(date_modified, date_created, company_id, document_package_id, document_list_id)
					VALUES
						" . implode(",\n",$lines);
				$db->exec($doc_query);
					
			}
		} 
		catch (Exception $e) 
		{
			throw $e;
		}	
		
		
	}
	
	public function Delete_Document_Package($doc)
	{
		if(!is_numeric($doc)) 
		{
			if(is_object($doc))  
			{
				$doc = $doc->document_package_id;
			} 
			elseif (is_array($doc)) 
			{
				$doc = $doc['document_package_id'];
			} 
			else 
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: {$doc} is an invalid package or package id");
			}
		} 
		$db = ECash_Config::getMasterDbConnection();
		
		try 
		{
			
			$doc_query = "
				DELETE FROM
					document_package
				WHERE
					document_package_id = {$doc}
			";
			
			$db->exec($doc_query);

			$doc_query = "
				DELETE FROM
					document_list_package
				WHERE
					document_package_id = {$doc}
			";
			$db->exec($doc_query);
		} 
		catch (Exception $e) 
		{
			throw $e;
		}
		
		
	}
	
	public function Update_List_Document($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(!isset($doc->document_list_id) || !$doc->document_list_id) 
		{
			return $this->New_List_Document($doc);
		}
				$doc_obj = new stdclass();
		foreach($doc as $key => $value)
		{
			$doc_obj->$key = $value;
		}
		$values = array_intersect(array_keys((array) $doc_obj), self::$document_list_fields);
		$db = ECash_Config::getMasterDbConnection();

		$update = array();
		foreach($values as $field ) 
		{
			if($field == "send_method") 
			{
				$doc->$field = implode(",",$doc->$field);
			}
			$update[] = "{$field} = " . $db->quote($doc->$field);
		}

		try 
		{
			
			
			$doc_query = "
				SELECT
					document_list_body_id,
					send_method
				FROM
					document_list_body
				WHERE
					document_list_id = {$doc->document_list_id}
			";
						
			$current_bodies = array();
			$res = $db->query($doc_query);
			while($row = $res->fetch(PDO::FETCH_OBJ))
			{
				$current_bodies[$row->send_method] = $row->document_list_body_id;
			}
			
			$new_bodies['esig'] = (is_numeric($doc->esig_body)) ? $doc->esig_body : NULL;
			$new_bodies['email'] = (is_numeric($doc->email_body)) ? $doc->email_body : NULL;
			$new_bodies['fax'] = (is_numeric($doc->fax_body)) ? $doc->fax_body : NULL;

			$company_id = ECash::getCompany()->company_id;
			foreach(array_keys($new_bodies) as $mode) 
			{
				$bod_query = NULL;
				
				switch (TRUE) 
				{
					case (isset($new_bodies[$mode]) && isset($current_bodies[$mode]) && $new_bodies[$mode] != $current_bodies[$mode]) :
						$bod_query = "
							UPDATE
								document_list_body
							SET
								document_list_body_id  = {$new_bodies[$mode]}
							WHERE
								document_list_id = {$doc->document_list_id} AND
								send_method = '{$mode}'
						";
						break;
						
					case (isset($new_bodies[$mode]) && !isset($current_bodies[$mode])) :
						$bod_query = "
							INSERT INTO 
								document_list_body
								(date_modified, date_created, company_id, document_list_id, document_list_body_id, send_method)
							VALUES
								(now(), now(), {$company_id}, {$doc->document_list_id}, {$new_bodies[$mode]}, '{$mode}')
						";
						break;						
						
					case (!isset($new_bodies[$mode]) && isset($current_bodies[$mode])) :
						$bod_query = "
							DELETE FROM
								document_list_body
							WHERE
								document_list_id = {$doc->document_list_id} AND
								send_method = '{$mode}'
						";
						break;
						
				}

				if ($bod_query) 
				{
					$db->exec($bod_query);
				}
				
			}
			
			if (count($update)) 
			{
				
				$doc_query = "
					UPDATE
						document_list
					SET
						" . implode(", ", $update) . "
					WHERE
						document_list_id = {$doc->document_list_id}
				";
			
				$db->exec($doc_query);
			
			}			
		} 
		catch (Exception $e) 
		{
			throw $e;
		}		
			
	}
	
	public function New_List_Document($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(isset($doc->document_list_id) && $doc->document_list_id) 
		{
			return $this->Update_List_Document($doc);
		}
		$doc_obj = new stdclass();
		foreach($doc as $key => $value)
		{
			$doc_obj->$key = $value;
		}
		$missing = array_diff(self::$document_list_fields, array_keys((array) $doc_obj));
		
		if (count($missing)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: the required values are missing: " . implode(", ", $missing));
		}
		
		$db = ECash_Config::getMasterDbConnection();
		$company_id = ECash::getCompany()->company_id;
		try 
		{
			$insert = array();
			foreach(self::$document_list_fields as $field) 
			{
				if (!$doc->$field) 
				{
					throw new OutOfRangeException(__METHOD__ . " Error: Required value is missing for field {$field}");
				}
				
				if($field == "send_method") 
				{
					$doc->$field = implode(",",$doc->$field);
				}
				
				$insert[] = $db->quote($doc->$field);			
			}
				
			$doc_query = "
				INSERT INTO
					document_list
					(date_modified, date_created, company_id, system_id, " . implode (",", self::$document_list_fields) . ")
				VALUES
					(now(), now(), {$company_id}, 3, " . implode(", ", $insert) . ")
			";
			
			$db->exec($doc_query);		
			$doc->document_list_id = $db->lastInsertId();
			
			if(!$doc->document_list_id) 
			{
				throw new RuntimeException(__METHOD__ . " Error: Unknown MySQL error. No value returned for document_list insert.");
			}
						
			$new_bodies['esig'] = (is_numeric($doc->esig_body)) ? $doc->esig_body : NULL;
			$new_bodies['email'] = (is_numeric($doc->email_body)) ? $doc->email_body : NULL;
			$new_bodies['fax'] = (is_numeric($doc->fax_body)) ? $doc->fax_body : NULL;
			
			$values = array();
			foreach(array_keys($new_bodies) as $mode) 
			{
				if (isset($new_bodies[$mode])) 
				{
					$values[] = "(now(), now(), {$company_id}, {$doc->document_list_id}, {$new_bodies[$mode]}, '{$mode}')";
				}				
			}
			
			if(count($values)) 
			{
				$bod_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					INSERT INTO 
						document_list_body
						(date_modified, date_created, company_id, document_list_id, document_list_body_id, send_method)
					VALUES
						" . implode(",\n",$values);
				$db->exec($bod_query);
			}
			
		} 
		catch (Exception $e) 
		{
			throw $e;
		}		
	}
	
	static public function Log_Document(Server $server, $document, $result)
	{
		$db = ECash_Config::getMasterDbConnection();

//		$map[''] = () ? : ;
		$map['document_method'] 	= isset($result['method']) 				? "" . $db->quote($result['method']) . "" 				: NULL ;
		$map['sent_to'] 			= isset($result['destination']['destination'])	? "" . $db->quote($result['destination']['destination']) . "" : NULL ;
		$map['name_other'] 			= isset($result['destination']['name']) 	? "" . $db->quote($result['destination']['name']) . "" 	: NULL ;
		$map['signature_status'] 	= isset($result['signature_status']) 	? "" . $db->quote($result['signature_status']) . "" 	: NULL ;
		$map['document_id_ext'] 	= isset($result['document_id_ext']) 		? "" . $db->quote($result['document_id_ext']) . "" 		: NULL ;
		$map['document_event_type'] = isset($result['document_event_type']) 	? "" . $db->quote($result['document_event_type']) . ""  : "'sent'" ;
		$map['archive_id'] 			= isset($result['archive_id']) 			? $result['archive_id'] : NULL ;

		foreach($map as $field => $rawval) 
		{
			if($rawval != NULL) 
			{
				$field_part[] = $field;
				$rawval_part[] = $rawval;
			}
		}

		if(count($field_part) == count($rawval_part) && count($field_part) > 0) 
		{
			$field_sql = "," . implode(",",$field_part);
			$rawval_sql = "," . implode(",",$rawval_part);
		}
		$doc_query = "
					insert into document
						(
						date_created,
						date_modified,
						application_id,
						company_id,
						document_list_id,
						transport_method,
						agent_id
						{$field_sql}
						)
					  values
					  	(
						now(),
						now(),
						{$result['application_id']},
						" . ECash::getCompany()->company_id . ",
						{$document->document_list_id},
						" . $db->quote(strtolower($document->document_api)) . ",
						" . ECash::getAgent()->getAgentId() . "
						{$rawval_sql}
						)"; //"
						
		self::Log()->write($query, LOG_DEBUG);
					
		$q_obj = $db->exec($doc_query);

		return $db->lastInsertId();
	}

	static public function Get_Document_Log(Server $server, $document_id)
	{
		$query = "
					SELECT
						document.document_id,
						document_list.document_list_id,
						document_list.name_short as name,
						document_list.name as description,
						document_list.required,
						document_list.send_method,
						agent.agent_id,
						if(agent.login is null, 'unknown', agent.login) as login,
						document.document_event_type as event_type,
						if(document.document_method is null, document.document_method_legacy,document.document_method) as document_method,
						document.transport_method,
						document.signature_status,
						document.name_other as name_other,
						date_format(document.date_created,'%m-%d-%Y %H:%i') as xfer_date,
						DATE (document.date_modified) as alt_xfer_date,
						document.document_id_ext,
						document_list.document_api,
						document.archive_id,
						document.application_id
					FROM
						document left join agent on document.agent_id = agent.agent_id,
						document_list
					WHERE
						document.document_id = {$document_id}
					and document.document_list_id = document_list.document_list_id limit 1
					"; //"

		//self::Log()->write($query, LOG_DEBUG);

		$db = ECash_Config::getMasterDbConnection();
		
		$q_obj = $db->query($query);

		for ( $app_docs = array(); $row = $q_obj->fetch(PDO::FETCH_OBJ); true )
		{
			return $row;
		}
		
	}
	
	static public function Delete_Archive_Document(Server $server, $document_id)
	{
		if(!isset($document_id) || !is_numeric($document_id)) 
		{
			throw new Exception ("Document ID must be valid.");
		}
		
		$query = "delete from document where document_id = {$document_id}";

		self::Log()->write($query, LOG_DEBUG);

		$db = ECash_Config::getMasterDbConnection();
		
		$db->exec($query);		
	}
	
	static public function Change_Document_Archive_ID(Server $server, $document_id, $archive_id)
	{
		$query = "update document set archive_id = {$archive_id} where document_id = {$document_id}"; //"

		self::Log()->write($query, LOG_DEBUG);
		
		$db = ECash_Config::getMasterDbConnection();
		$db->exec($query);
	}

	static public function Check_Archive_IDs(Server $server, $archive_id)
	{
		require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
		return eCash_Document_DeliveryAPI_Condor::Check_ArchiveIDs($server, $archive_id);
	}
	
	static public function Get_Status_Trigger(Server $server, $application_id, $document_list_id)
	{
		require_once(SQL_LIB_DIR . "loan_actions.func.php");
		require_once(SQL_LIB_DIR."/application.func.php");

		$db = ECash_Config::getMasterDbConnection();

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */

					SELECT
						document_process.application_status_id,
						app.is_react
					FROM
						document_process 
					JOIN 
						application as app on  (document_process.current_application_status_id = app.application_status_id)
					WHERE
						document_list_id = $document_list_id 
					AND
						app.application_id = $application_id
					";
		
		$q_obj = $db->query($query);

		while( $row = $q_obj->fetch(PDO::FETCH_OBJ))
		{
			//This is crap, but we need to do some special processing for react apps
			$status = Fetch_Application_Status($application_id);
			if($status['status_chain'] == 'pending::prospect::*root' && $row->is_react == 'yes')
			{
				if(count(Get_Loan_Actions($application_id)) > 0)
				{
					return Status_Utility::Get_Status_ID_By_Chain('queued::verification::applicant::*root');
				}
				else
				{
					return 	$row->application_status_id;
				}
			}
			return 	$row->application_status_id;
		}
		
		
		
		return false;
		
	}
	
	/**
	 * will return an array of arrays of status triggers
	 *
	 * @return array[current_application_status_id][application_status_id][document_list_id] = true
	 */
	static public function Get_All_Status_Triggers($company_id)
	{
		$db = ECash_Config::getMasterDbConnection();
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT dp.*
					FROM
						document_process AS dp
					INNER JOIN document_list AS dl ON dp.document_list_id=dl.document_list_id
					WHERE dl.company_id={$company_id}
					";
		
		$q_obj = $db->Query($query);

		$retval = array();
		while(  $row = $q_obj->fetch(PDO::FETCH_OBJ) ) 
		{
			if(!is_array($retval[$row->current_application_status_id]))
			{
				$retval[$row->current_application_status_id] = array();
				$retval[$row->current_application_status_id][$row->application_status_id] = array();
			} elseif(!is_array($retval[$row->current_application_status_id][$row->application_status_id])) {
				$retval[$row->current_application_status_id][$row->application_status_id] = array();
			}
			$retval[$row->current_application_status_id][$row->application_status_id][$row->document_list_id] = true;
		}
		return $retval;
	}
	
	static public function Log()
	{
		if (!class_exists('Applog_Singleton')) require_once 'applog.singleton.class.php';
		
		if(!self::$log_context) self::$log_context = ( isset($_SESSION["Server_state"]["company"]) ) ? strtoupper($_SESSION["Server_state"]["company"]) : "";
		
		return Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY."/documents", APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, self::$log_context, 'TRUE');
		
	}
}

?>
