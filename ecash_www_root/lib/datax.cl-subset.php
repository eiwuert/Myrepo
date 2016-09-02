<?php



	/***
	
		datax.cl-subset
		--
	
	***/
	
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	
	require_once('diag.1.php');
	
	class Cashline_Subset
	{
		var $__subsetid;
		var $__sql;
		var $__userdata;
		
		function Create_Subset()
		{
			if (!$this->__sql)
				$this->__initsql();
				
			$this->__subsetid = "dx" . sprintf("%06x",mt_rand(0,0xFFFFFF));

			$this->__create();
			
			return $this->__subsetid;
		}
		
		function Import_Using_Loan_ID($arr,$data_a=FALSE,$data_b=FALSE)
		{
			if (!$this->__sql)
				$this->__initsql();
			
			$dbname = $this->__subsetid;
			
			foreach($arr as $set)
			{
				$p       = $set[0];
				$loan_id = $set[1];
				
				if ( $data_a ) $data_a = "'" . mysql_escape_string($set[2]) . "'";
				else $data_a = 'NULL';
				if ( $data_b ) $data_b = "'" . mysql_escape_string($set[3]) . "'";
				else $data_b = 'NULL';
				
				$rs = $this->__sql->query("datax", "
					INSERT INTO $dbname.{$p}_loan
					SELECT
						{$p}_loan.*,$data_a,$data_b
					FROM {$p}_loan
					WHERE {$p}_loan.loan_id='$loan_id'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);


				$rs = $this->__sql->query("datax","
					INSERT INTO $dbname.{$p}_customer
					SELECT
						{$p}_customer.*,$data_a,$data_b
					FROM {$p}_customer
					INNER JOIN {$p}_loan ON ({$p}_loan.custnum={$p}_customer.custnum)
					WHERE {$p}_loan.loan_id='$loan_id'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);


				$rs = $this->__sql->query("datax","
					INSERT INTO $dbname.{$p}_raw_transact
					SELECT
						{$p}_raw_transact.*,$data_a,$data_b
					FROM {$p}_raw_transact
					WHERE {$p}_raw_transact.loan_id='$loan_id'",Debug_1::Trace_Code(__FILE__,__LINE__));
				Error_2::Error_Test($rs, TRUE);

				$rs = $this->__sql->query("datax","
					INSERT INTO $dbname.{$p}_newest_loan
					SELECT
						{$p}_newest_loan.*,$data_a,$data_b
					FROM {$p}_newest_loan
					WHERE {$p}_newest_loan.loan_id='$loan_id'",Debug_1::Trace_Code(__FILE__,__LINE__));
				Error_2::Error_Test($rs, TRUE);

			}
		}
		
		function Import_Using_SSN($arr)
		{		
			if (!$this->__sql)
				$this->__initsql();
			
			$dbname = $this->__subsetid;
			
			foreach($arr as $set)
			{
				$p    = $set[0];
				$ssn  = $set[1];
				
				if ( $data_a ) $data_a = "'" . mysql_escape_string($set[2]) . "'";
				else $data_a = 'NULL';
				if ( $data_b ) $data_b = "'" . mysql_escape_string($set[3]) . "'";
				else $data_b = 'NULL';
				
				// loan table
				$rs = $this->__sql->query("datax", "
					INSERT INTO $dbname.{$p}_loan 
					SELECT 
						{$p}_loan.*,$data_a,$data_b
					FROM {$p}_loan
					INNER JOIN {$p}_customer ON ({$p}_customer.custnum={$p}_loan.custnum)
					WHERE {$p}_customer.ssn='$ssn'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);

				// customer table					
				$rs = $this->__sql->query("datax", "
					INSERT INTO $dbname.{$p}_customer
					SELECT
						{$p}_customer.*
					FROM {$p}_customer
					WHERE {$p}_customer.ssn='$ssn'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);

				// raw transaction data
				$rs = $this->__sql->query("datax", "
					INSERT INTO $dbname.{$p}_raw_transact
					SELECT
						{$p}_raw_transact.*
					FROM {$p}_raw_transact
					INNER JOIN {$p}_customer ON ({$p}_customer.custnum={$p}_raw_transact.custnum)
					WHERE {$p}_customer.ssn='$ssn'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);

				// newest loan info
				$rs = $this->__sql->query("datax", "
					INSERT INTO $dbname.{$p}_newest_loan
					SELECT
						{$p}_newest_loan.*
					FROM {$p}_newest_loan
					INNER JOIN {$p}_customer ON ({$p}_customer.custnum={$p}_newest_loan.custnum)
					WHERE {$p}_customer.ssn='$ssn'",Debug_1::Trace_Code(__FILE__,__LINE__));

				Error_2::Error_Test($rs, TRUE);

			}
		}
		
		function Destroy_Subset()
		{
			$this->__destroy();
		}
		function __initsql()
		{
			$this->__sql = new MySQL_3();
			$result = $this->__sql->connect(NULL, "serenity.x", "serenity", "firefly", Debug_1::Trace_Code(__FILE__,__LINE__));
			
			Error_2::Error_Test($result, TRUE);
		}
		function __destroy()
		{
			Error_2::Error_Test($this->__sql->query("datax", "DROP DATABASE {$this->__subsetid};"),TRUE);
			
		}
		function __create()
		{
			Error_2::Error_Test($this->__sql->query("datax", "CREATE DATABASE {$this->__subsetid};"),TRUE);
			
			$dir = dirname(__FILE__);
			$exec = "mysql -hserenity.x -userenity -pfirefly {$this->__subsetid} < {$dir}\\datax.cl-subset.structure.sql";
			exec($exec);
			
			Error_2::Error_Test($this->__sql->query($this->__subsetid,"ALTER TABLE ufc_loan add user_data_a varchar(200), add user_data_b varchar(200)"),TRUE);
			Error_2::Error_Test($this->__sql->query($this->__subsetid,"ALTER TABLE ucl_loan add user_data_a varchar(200), add user_data_b varchar(200)"),TRUE);
			Error_2::Error_Test($this->__sql->query($this->__subsetid,"ALTER TABLE pcl_loan add user_data_a varchar(200), add user_data_b varchar(200)"),TRUE);
			Error_2::Error_Test($this->__sql->query($this->__subsetid,"ALTER TABLE d1_loan add user_data_a varchar(200), add user_data_b varchar(200)"),TRUE);
			Error_2::Error_Test($this->__sql->query($this->__subsetid,"ALTER TABLE ca_loan add user_data_a varchar(200), add user_data_b varchar(200)"),TRUE);
		}
	}
	
	
	// Test
	
	
		
/*
	Diag::Enable();
	
	// Create our cashline subset data class
	$clss = new Cashline_Subset();
	
	// Obtain a subset ID. this must be called before running any imports!
	print $subsetid = $clss->Create_Subset();
	
	
	
	
	// Import from our base cashline data based on an SSN population
	//$clss->Import_Using_SSN($arr);
	
	// Use the data.
	
	// This isn't necessary, but it's recommended. Destroys the subset DB.
	// This will completely wipeout the subset data.
	//$clss->Destroy_Subset();*/

	
?>