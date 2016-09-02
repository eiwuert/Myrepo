<?php

class Log_Vendor_Post
{
	
	const TYPE_POST = 'POST';
	const TYPE_VERIFY_POST = 'VERIFY_POST';

	public function __construct()
	{
	}
	
	/**
	 * Logs the vendor post result data to blackbox_post.
	 *
	 * @param object $sql
	 * @param string $database
	 * @param string $property_short
	 * @param int $application_id
	 * @param object $vendor_post_result
	 * @param string $type
	 */
	public static function Log_Vendor_Post(&$sql, $database, $property_short, $application_id, &$vendor_post_result, $type = self::TYPE_POST)
	{
		$crypt_object = Crypt_Singleton::Get_Instance();
		$post_result_id = Log_Vendor_Post::Norm_Post_Result($sql, $database, $vendor_post_result->Get_Data_Sent());
		
		// setup the success variable
		$success = $vendor_post_result->Is_Success() ? "TRUE" : "FALSE";
		
		/*$query = "
			INSERT INTO blackbox_post(date_created, date_modified, num_update, data_sent, data_recv, post_result_id, application_id, winner, post_time, success)
			VALUES(sysdate(), sysdate(), 1, '" . mysql_escape_string($vendor_post_result->Get_Data_Sent()) . "', '" . mysql_escape_string($vendor_post_result->Get_Data_Received()) . 
					"', '{$post_result_id}', '{$application_id}', '{$property_short}', {$vendor_post_result->Get_Post_Time()}, '{$success}')
		";*/
		
		$encrypted_data_sent = $crypt_object->encrypt($vendor_post_result->Get_Data_Sent());
		$encrypted_data_recv = $crypt_object->encrypt($vendor_post_result->Get_Data_Received());

		$data_sent = gzcompress($encrypted_data_sent);
		$data_recv = gzcompress($encrypted_data_recv);
		
		$query = "
			UPDATE
				blackbox_post
			SET
				date_modified = NOW(),
				data_sent = '" . mysql_escape_string($data_sent) . "',
				data_recv = '" . mysql_escape_string($data_recv) . "',
				post_result_id = {$post_result_id},
				post_time = ".$vendor_post_result->Get_Post_Time().",
				vendor_decision = '" .mysql_escape_string($vendor_post_result->Get_Vendor_Decision()). "',
				vendor_reason = '" .mysql_escape_string($vendor_post_result->Get_Vendor_Reason()). "',
				success = '{$success}',
				compression = 'GZ',
				encrypted = 1
			WHERE
				application_id = {$application_id}
			AND
				winner = '{$property_short}'
			AND
				type = '$type'
			";
		
		$sql->query( $database, $query );
		
	}
	
	/**
	 * @desc Fetches the vendor post result's unique ID
	 * 	This routine will insert a row into blackbox_post_result if
	 *	one doesn't exist already.
	 */
	public static function Norm_Post_Result(&$sql, $database, $data_sent)
	{
		$post_result = serialize($data_sent);
		$post_md5 = md5($post_result);

		$query = "
			SELECT blackbox_post_result_id 
			FROM blackbox_post_result 
			WHERE hash = '" . mysql_escape_string($post_md5) . "'
			";
		$result = $sql->Query($database, $query);
		$row = $sql->Fetch_Object_Row($result);

		$post_result_id = 0;
		if (isset($row->blackbox_post_result_id))
		{
			$post_result_id = $row->blackbox_post_result_id;
		}
		else
		{
			$query = "
				INSERT INTO blackbox_post_result(hash, data)
				VALUES('" . mysql_escape_string($post_md5) . "', '" . mysql_escape_string($post_result) . "')
				";
			$post_result_id = $sql->Insert_Id();
		}

		return $post_result_id;
	}
	
	/**
	 * Inserts an initial "dummy" record into the blackbox_post table.
	 *
	 * @param object $sql
	 * @param string $database
	 * @param string $winner
	 * @param int $application_id
	 * @param string $type
	 */
	public static function Insert_Dummy_Record( &$sql, $database, $winner, $application_id, $type = self::TYPE_POST )
	{
		if(!is_numeric($application_id)) return;
        
        $query = "
			INSERT INTO
				blackbox_post
			(
				date_created,
				date_modified,
				num_update,
				data_sent,
				data_recv,
				post_result_id,
				application_id,
				winner,
				post_time,
				success,
				type,
				encrypted
			) VALUES (
				NOW(),
				NOW(),
				1,
				'',
				'',
				0,
				" . $application_id . ",
				'" . $winner . "',
				0,
				'PROCESSING',
				'$type',
				1
			)
		";
		
		try 
		{
			$sql->Query( $database, $query );
		} 
		catch(MySQL_Exception $e)
		{
			
		}
		return;
	}
	
	public static function Check_For_Records( &$sql, $database, $application_id )
	{
		
		try
		{
			
			$query = "SELECT count(*) AS count FROM blackbox_post
				WHERE	application_id = " . $application_id . " AND	success IN ('PROCESSING', 'TRUE')";
			$result = $sql->Query($database, $query);
			
			// get the number of records
			$count = $sql->Fetch_Column($result, 'count');
			
			$sql->Free_Result($result);
			
		}
		catch (Exception $e)
		{
			$count = 0;
		}
		
		return($count);
		
	}
	
	
	public static function Get_Data_Received(&$sql, $database, $application_id, $winner, $type = self::TYPE_POST)
	{
		$crypt_object = Crypt_Singleton::Get_Instance();
		try
		{
			
			$query = "
				SELECT
					data_recv,
					compression,
					encrypted
				FROM
					blackbox_post
				WHERE
					application_id = '$application_id'
					AND winner = '$winner'
					AND type = '$type'";
			$result = $sql->Query($database, $query);
			
			if ($result && ($row = $sql->Fetch_Array_Row($result)))
			{
				if($row['encrypted'] == 1)
				{

					// uncompress data
					if (strtoupper($row['compression']) == 'GZ')
					{
						$data_received = gzuncompress($row['data_recv']);
					}
					else 
					{
						$data_receieved = $row['data_recv'];
					}
					
					$data_received = $crypt_object->decrypt($data_received);
				
				}
				else 
				{
					
					// uncompress data
					if (strtoupper($row['compression']) == 'GZ')
					{
						$data_received = gzuncompress($row['data_recv']);
					}
					else 
					{
						$data_receieved = $row['data_recv'];
					}
					
					
				}
				
			}
			
		}
		catch(Exception $e)
		{
			$data_received = '';
		}
		
		return($data_received);
		
	}
	
	/**
	 * Secondary recur SSN check for double posts (generally coming from SOAP Vendors).
	 * Returns TRUE is we pass the rule.
	 *
	 * @param object $sql
	 * @param string $database
	 * @param string $winner
	 * @param int $application_id
	 * @param object $data
	 */
	public static function Secondary_Recur_Check( &$sql, $database, $winner, $application_id, &$data )
	{

		$return = TRUE;
		$query = "
			(
			SELECT
				*
			FROM
				blackbox_post b,
				personal_encrypted p
			WHERE
				b.type != 'VERIFY_POST' AND 
				p.application_id=b.application_id AND
				b.winner='{$winner}' AND
				b.date_created>='".date("Ymd")."000000' AND
				p.social_security_number='{$data['social_security_number_encrypted']}'
			)
		";
		
		try 
		{
			$result = $sql->Query( $database, $query );
			$return = ($result && ($sql->Row_Count($result)>0) ? FALSE : TRUE);
		} 
		catch(MySQL_Exception $e)
		{
			
		}
		return $return;
	}

}
