<?php

/**
* Writes a record to the Authentication table.
*
* @author Kevin Kragenbrink
* @todo Would be nice if Insert_Record could take fewer arguments, perhaps
		by passing some things in as part of an array.
* @version 1.0.0
*/

require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');


class Authentication
{
	
	private $sql;
	private $database;
	private $applog;
	
	const DATAX_IDV_PREQUAL = 0;
	const DATAX_IDV_CLK = 1;
	const DATAX_PERF = 2;
	const DATAX_IDV_PW = 3;
	const DATAX_IDV_REWORK = 4;
	const DATAX_IDVE_IMPACT = 5; // Change IC to use impact-idve - GForge 5576 [DW]
	const DATAX_PDX_REWORK = 6;
	const DATAX_CCRT = 8;
	const DATAX_AGEAN_PERF = 9;
	const DATAX_AGEAN_TITLE = 10;
	const DATAX_IDVE_IFS = 12;
	const DATAX_IDVE_IPDL = 13;
	const DATAX_IDVE_ICF = 14;
	
	/**
	* @param 	object		$sql:		The MySQL connection object, passed by reference.
	* @return	void
	*/
	public function __construct( &$sql, $database, &$applog )
	{
		$this->sql			= &$sql;
		$this->database		= $database;
		$this->applog		= &$applog;

		$crypt_config 		= Crypt_Config::Get_Config(BFW_MODE);
		$this->crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);

		return;
	}

	public function Insert_Record($application_id, $authentication_source_id, $sent_package, $received_package, $decision, $reason, $elapsed_time, $score = null)
	{
		
		//Encrypted Data
		$sent_package_encrypted = $this->crypt_object->encrypt($sent_package);
		$received_package_encrypted = $this->crypt_object->encrypt($received_package);	
			
		$query = "
			INSERT INTO
				authentication
			(
				date_modified,
				date_created,
				application_id,
				authentication_source_id,
				sent_package,
				received_package,
				decision,
				reason,
				score,
				timer,
				encrypted
			)
			VALUES
			(
				CURRENT_TIMESTAMP(),
				CURRENT_TIMESTAMP(),
				" . $application_id . ",
				" . $authentication_source_id . ",
				COMPRESS('" . mysql_escape_string($sent_package_encrypted) . "'),
				COMPRESS('" . mysql_escape_string($received_package_encrypted) . "'),
				'" . ($decision == 'Y' ? 'PASS' : 'FAIL') . "',
				'" . mysql_escape_string($reason) . "',
				'" . $score . "',
				'" . $elapsed_time . "',
				1
			)";

		try
		{
			
			// run the query
			$result = $this->sql->Query( $this->database, $query );
			
			// get our ID
			$return = $this->sql->Insert_Id($result);
			if (!is_numeric($return)) $return = FALSE;
			
		}
		catch( MySQL_Exception $e )
		{
			DB_Exception_Handler::Def( $this->applog, $e, "working with the authentication table in Authentication::Insert_Record()." );
			$return = FALSE;
		}
		
		return $return;
		
		
	}
	
	/**
		
		Pull records from the authentication table.
		NOTE: This is used when we're inserting into eCash.
		
	*/
	public function Get_Records( $application_id, $source_id = NULL )
	{
		
		$query = "
			SELECT
				authentication_source_id,
				UNCOMPRESS(sent_package) AS sent_package,
				UNCOMPRESS(received_package) AS received_package,
				score,
				encrypted
			FROM
				authentication
			WHERE
				application_id = " . $application_id . "
			";
		
		if (is_numeric($source_id))
		{
			$query .= "AND authentication_source_id = ".$source_id;
		}
		elseif (is_array($source_id))
		{
			$query .= "AND authentication_source_id IN (".implode(', ', $source_id).')';
		}
		
		try
		{
			$result = $this->sql->Query($this->database, $query);
		}
		catch( MySQL_Exception $e )
		{
			DB_Exception_Handler::Def($this->applog, $e, "working with the authentication table in Authentication::Get_Records().");
		}
		
		if ($result !== FALSE)
		{
			
			$response = array();
			
			while ($rec = $this->sql->Fetch_Array_Row($result))
			{
				
				$type = NULL;
				

				//Decrypt
				if($rec['encrypted'] == 1)
				{
					$rec['received_package'] = $this->crypt_object->decrypt($rec['received_package']);
					$rec['sent_package'] = $this->crypt_object->decrypt($rec['sent_package']);
				}
				
				// for backwards compatibility, ensure that this is valid
				// XML: CAN'T have whitespace before the XML declaration
				$rec['received_package'] = preg_replace('/^[\s\r\n]*(?=<\?xml)/', '', $rec['received_package']);

				switch ($rec['authentication_source_id'])
				{
					case self::DATAX_IDV_CLK:
					case self::DATAX_IDV_PREQUAL:
					case self::DATAX_IDVE_IMPACT: // Change IC to use impact-idve - GForge 5576 [DW]
					case self::DATAX_CCRT:
					case self::DATAX_AGEAN_PERF:
					case self::DATAX_AGEAN_TITLE:
					case self::DATAX_IDVE_IFS:
					case self::DATAX_IDVE_IPDL:
					case self::DATAX_IDVE_ICF:
					case self::DATAX_IDV_PW;
						$type = 'IDV';
						break;
					case self::DATAX_PERF:
						$type = 'PERFORMANCE';
						break;
					case self::DATAX_IDV_REWORK:
					case self::DATAX_PDX_REWORK:
						$type = 'REWORK';
				}
				
				if ($type)
				{
					// save it in our response
					$response['DATAX_'.$type] = $rec;
				}
				
			}
			
		}
		else
		{
			$response = FALSE;
		}
		
		return($response);
		
	}
	
}


?>
