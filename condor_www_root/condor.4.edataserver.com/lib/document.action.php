<?php
/**
 * Handles all document actions.
 *
 * @author Brian Feaver
 */
class Document_Action
{
	private static $sql;
	private static $instance;
	
	/**
	 * Private constructor.
	 *
	 * @param object $sql
	 * @param string $db
	 */
	private function __construct(&$sql)
	{
		$this->sql =& $sql;
	}
	
	/**
	 * Creates a singleton version of the class and returns the instance.
	 *
	 * @param object $sql
	 * @param string $db
	 * @return object
	 */
	public static function Singleton(&$sql)
	{
		if(!isset(self::$instance))
		{
			$class = __CLASS__;
			self::$instance = new $class($sql);
		}
		
		return self::$instance;
	}
	/**
	 * Load all rows with a given 'action' for a given 'document_id'
	 * also optionally from a given 'user_id'
	 *
	 * @param string $action
	 * @param int $document_id
	 * @param int $user_id
	 */
	public function Get_Actions($action,$document_id,$user_id=false, $ip_address = null)
	{
		$ret = false;
		try {
			$action_id = $this->Get_Action_Id($action);
			$document_id = $this->sql->Escape_String($document_id);
			$query = "
				SELECT 
					ah.date_created,
					ah.document_hash,
					ah.user_id, 
					aip.ip_address
				FROM 
					action_history ah
				LEFT JOIN
					action_ip_address aip ON (ah.action_history_id = aip.action_history_id)
				WHERE 
					ah.document_action_id='$action_id' 
				AND
					ah.document_id='$document_id'";

			if(is_numeric($user_id))
			{
				$user_id = $this->sql->Escape_String($document_id);
				$query .= " AND ah.user_id='{$user_id}'";
			}
		
			if ($ip_address != NULL)
			{
				$ip_address = $this->sql->Escape_String($ip_address);
				$query .= " AND aip.ip_address = '{$ip_address}'";
			}
			
			$res = $this->sql->Query($query);
			$ret = Array();
			while($row = $res->Fetch_Object_Row())
			{
				$row->action = $action;
				$row->document_id = $document_id;
				$ret[] = $row;
			}
		}
		catch (Exception $e)
		{
			exit($e->GetMessage());
		}
		return $ret;
	}
	/**
	 * Logs a document action. Returns the action history ID.
	 *
	 * @param string $action
	 * @param int $document_id
	 * @param int $user_id
	 * @param string $hash
	 * @param string $ip_address
	 * @return int
	 */
	public function Log_Action($action, $document_id, $user_id, $hash = '', $ip_address = NULL)
	{
		$action_id = $this->Get_Action_Id($action);
			
		$query = "
			INSERT INTO action_history
			(
				document_id,
				document_action_id,
				date_created,
				document_hash,
				user_id
			)
			VALUES
			(
				$document_id,
				$action_id,
				NOW(),
				'$hash',
				$user_id
			)
		";
					
		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
		
		$insert_id = $this->sql->Insert_Id();

		// This is new, associates IP addresses with actions.
		if ($ip_address != NULL)
		{
			$query = "
				INSERT INTO action_ip_address
				(
					action_history_id,
					ip_address
				)
				VALUES
				(
					$insert_id,
					'$ip_address'
				)
			";

			try
			{
				$this->sql->Query($query);
			}
			catch(Exception $e)
			{
				die($e->getMessage());
			}
		}

		return $insert_id;
	}
	
	/**
	 * Returns the action ID for the given action name.
	 *
	 * @param string $action_name
	 * @return int
	 */
	public function Get_Action_Id($action_name)
	{
		$action_id = FALSE;
		
		$query = "
			SELECT
				document_action_id
			FROM
				document_action
			WHERE
				name = '$action_name'";
		
		try
		{
			$result = $this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
		
		if(($row = $result->Fetch_Array_Row()))
		{
			$action_id = $row['document_action_id'];
		}
		else
		{
			// Action doesn't exist, add it
			$action_id = $this->Insert_New_Action($action_name);
		}
		
		return intval($action_id);
	}
	
	/**
	 * Inserts a new document action into the document_action table.
	 *
	 * @param string $action_name
	 * @param string $description
	 */
	private function Insert_New_Action($action_name, $description = '')
	{
		$query = "
			INSERT INTO document_action
			(
				date_created,
				name,
				description
			)
			VALUES
			(
				NOW(),
				'$action_name',
				'$description'
			)
		";
		
		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
		
		return $this->sql->Insert_Id();
	}
}
?>
