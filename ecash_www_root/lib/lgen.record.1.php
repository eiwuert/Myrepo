<?php

	/***
		lgen.record.1.php
		--
		this class is used to keep track of who the hell we send our data to
		there is a lot of data going out the door.. this should provide a universal
		interface for recording all that information for statistical purposes as well
		as making sure we don't double-sell leads..
		
		its a work in progress.  There's a lot of ways data leaves our little world.. 
		blackbox is one major way... This should either be handled in realtime or I'll
		setup a batch to migrate that information on a nightly basis..
		
		There should be a unified way for handling this all at some point.. and this is
		geared toward eventually having that..
		
		currently there is a function for each possible lead purchaser..
		
			"Record" functions return TRUE if it was inserted, 
			FALSE if it alredy there and Error_2 if there was a problem.
			
			
			"Check" functions return TRUE if the record exists, FALSE if it does not
			Error_2 if there was a problem			
		
		
			BMG
			---
			Record_BMG(MySQL_3,application_id, campaign_id, firstname, lastname, homephone, email)
			Check_BMG(MySQL_3,email)
			
			Vendor Promotions
			-----------------
			Record_VP(MySQL_3,application_id, firstname, lastname, homephone, email)
			Check_VP(MySQL_3,email,phone)
			
			Direct Synergy
			-----------------
			Record_DS(MySQL_3,application_id, media_id, firstname, lastname, homephone, email)
			Check_DS(MySQL_3,email)
			
			NATIONAL DO NOT CALL LIST
			-------------------------
			This one is special. its read only...
			Check_DNC(MySQL_3,phone)
			
			
			Ex:
				if ( Leadgen_Record::Check_BMG($sql, "bob@bob.com") )
				{
					print "bob@bob.com has been sent to BMG!";
				}
				else
				{
					print "omg! bob@bob.com has not been sent to BMG!";
				}
	***/
	
	define('LGENDB', 'lead_generation');
	define('SCRUBBERDB', 'scrubber');

	require_once('mysql.3.php');
	require_once('error.2.php');
	require_once('debug.1.php');
	
	class Leadgen_Record
	{
		// ============================================
		// constructor
		function Leadgen_Record()
		// ============================================
		{
			// hoody hoo!
		}
		
		// ============================================
		function Record_BMG(&$sql,$application_id, $campaign_id, $first_name, $last_name, $home_phone, $email)
		// ============================================
		{
			$query = "
					INSERT IGNORE INTO
						bmg_sent
					SET
						application_id='$application_id',
						created_date=NOW(),
						campaign_id='".Leadgen_Record::__norm($campaign_id)."',
						first_name='".Leadgen_Record::__norm($first_name)."',
						last_name='".Leadgen_Record::__norm($last_name)."',
						phone_number='".Leadgen_Record::__norm($home_phone)."',
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
				return TRUE;
			
			return FALSE;			
		}
		
		// ============================================
		function Check_BMG(&$sql,$email)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						bmg_sent
					WHERE
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			return ( $sql->Row_Count($rs) > 0 );
		}
		
		// ============================================
		function Record_DS(&$sql,$application_id, $media_id, $first_name, $last_name, $home_phone, $email)
		// ============================================
		{
			$query = "
					INSERT IGNORE INTO
						ds_sent
					SET
						application_id='$application_id',
						created_date=NOW(),
						media_id='".Leadgen_Record::__norm($media_id)."',
						first_name='".Leadgen_Record::__norm($first_name)."',
						last_name='".Leadgen_Record::__norm($last_name)."',
						phone_number='".Leadgen_Record::__norm($home_phone)."',
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
				return TRUE;
			
			return FALSE;			
		}
		
		// ============================================
		function Check_DS(&$sql,$email)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						ds_sent
					WHERE
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			return ( $sql->Row_Count($rs) > 0 );
		}
		// ============================================
		function Record_VP(&$sql,$application_id, $first_name, $last_name, $home_phone, $email)
		// ============================================
		{
			$query = "
					INSERT IGNORE INTO
						vp_sent
					SET
						application_id='$application_id',
						created_date=NOW(),
						first_name='".Leadgen_Record::__norm($first_name)."',
						last_name='".Leadgen_Record::__norm($last_name)."',
						phone_number='".Leadgen_Record::__norm($home_phone)."',
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
				return TRUE;
			
			return FALSE;			
		}
		
		// ============================================
		function Check_VP(&$sql,$email,$phone)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						vp_sent
					WHERE
						email_address='".Leadgen_Record::__norm($email)."'
					AND phone_number='".Leadgen_Record::__norm($phone)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			return ( $sql->Row_Count($rs) > 0 );
		}
		// ============================================
		function Record_Vendare(&$sql,$application_id, $media_id, $first_name, $last_name, $home_phone, $email)
		// ============================================
		{
			$query = "
					INSERT IGNORE INTO
						vendare_sent
					SET
						application_id='$application_id',
						created_date=NOW(),
						campaign_id='".Leadgen_Record::__norm($media_id)."',
						first_name='".Leadgen_Record::__norm($first_name)."',
						last_name='".Leadgen_Record::__norm($last_name)."',
						phone_number='".Leadgen_Record::__norm($home_phone)."',
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
				return TRUE;
			
			return FALSE;			
		}
		
		// ============================================
		function Check_Vendare(&$sql,$email)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						vendare_sent
					WHERE
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			return ( $sql->Row_Count($rs) > 0 );
		}		
		// ============================================
		function Record_CT(&$sql,$application_id,$media_id, $first_name, $last_name, $home_phone, $email)
		// ============================================
		{
			$query = "
					INSERT IGNORE INTO
						ct_sent
					SET
						application_id='$application_id',
						created_date=NOW(),
						campaign_id='".Leadgen_Record::__norm($media_id)."',
						first_name='".Leadgen_Record::__norm($first_name)."',
						last_name='".Leadgen_Record::__norm($last_name)."',
						phone_number='".Leadgen_Record::__norm($home_phone)."',
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
				return TRUE;
			
			return FALSE;			
		}
		
		// ============================================
		function Check_CT(&$sql,$email)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						ct_sent
					WHERE
						email_address='".Leadgen_Record::__norm($email)."'";
			$rs = $sql -> Query(LGENDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;
			
			return ( $sql->Row_Count($rs) > 0 );
		}		
		
		// ============================================
		function Check_DNC(&$sql,$phone)
		// ============================================
		{
			$query = "
					SELECT
						*
					FROM
						natl_donotcall
					WHERE
						dnc_phone='".Leadgen_Record::__norm($phone)."'";
			$rs = $sql -> Query(SCRUBBERDB, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( Error_2::Check($rs) )
				return $rs;			
			
			return ( $sql->Row_Count($rs) > 0 );
		}
		
		//=============================================
		//normalize/clean data
		function __norm($f)
		//=============================================
		{
			return mysql_escape_string(trim($f));
		}
	}
?>