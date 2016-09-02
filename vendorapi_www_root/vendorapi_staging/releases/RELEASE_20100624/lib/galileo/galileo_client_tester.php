<?php
	
	include_once( 'dlhdebug.php' );
	include_once( 'galileo_client.php' );

	$submitButton  = getHttpVar('submitButton');
	$htmlRows      = 15;
	$htmlCols      = 50;
	$DEBUG         = true;
	$url           = '';


	$transactionId = '1';  // This is a value we maintain.  I will increment it manually.
	
	// $account_01    = '4870930100000402';
	// $account_02    = '5185300500030952';
	// $account_03    = '018104274463';        // should give same results as 01; this is prn rather than pan (don't know what that means)
	// $account_04    = '001100030491';        // should give same results as 02; this is prn rather than pan (don't know what that means)

	// Just received new account info from Ryan Berringer
	$account_01    = '5185300504480252';
	$account_01b   = '001104448492';        // should give same results as 01; this is prn rather than pan (don't know what that means) 
	$account_02    = '5185300500030952';        
	$account_02b   = '001100030491';        // should give same results as 02; this is prn rather than pan (don't know what that means)
	

	if ( $submitButton != '' ) {
		
		$galileo = new Galileo_Client();

		// If we had a test vs. live url, this is how we would set the run mode to test (It currently defaults to LIVE).
		// -------------------------------------------------------------------------------------------------------------
		// $galileo->Set_Operating_Mode(Galileo_Client::RUN_MODE_TEST);

		$url = $galileo->Get_Url();

		$tn = 1;
		${"name$tn"}      = 'GetLoadHistory';
		${"result{$tn}A"} = $galileo->GetLoadHistory(  $transactionId++, $account_01 );                      // raw response
		${"result{$tn}D"} = dlhvardump($galileo->Get_Call_Data(), false);                                    // call data sent to galileo
		${"result{$tn}R"} = $galileo->Get_Raw_Response();                                                    // raw response (same as "A")
		${"result{$tn}F"} = dlhvardump($galileo->Parse_GetLoadHistory_Xml( ${"result{$tn}A"} ), false);      // array of loadhistory nodes meeting criteria

		$tn = 2;
		${"name$tn"}      = 'GetLoadHistory';
		${"result{$tn}A"} = $galileo->GetLoadHistory(  $transactionId++, $account_01b );   
		${"result{$tn}D"} = dlhvardump($galileo->Get_Call_Data(), false);
		${"result{$tn}R"} = $galileo->Get_Raw_Response();                           
		${"result{$tn}F"} = dlhvardump($galileo->Parse_GetLoadHistory_Xml( ${"result{$tn}A"} ), false);      // array of loadhistory nodes meeting criteria

		$tn = 3;
		${"name$tn"}      = 'GetLoadHistory';
		${"result{$tn}A"} = $galileo->GetLoadHistory(  $transactionId++, $account_02 );   
		${"result{$tn}D"} = dlhvardump($galileo->Get_Call_Data(), false);
		${"result{$tn}R"} = $galileo->Get_Raw_Response();                            
		${"result{$tn}F"} = dlhvardump($galileo->Parse_GetLoadHistory_Xml( ${"result{$tn}A"} ), false);      // array of loadhistory nodes meeting criteria

		$tn = 4;
		${"name$tn"}      = 'GetLoadHistory';
		${"result{$tn}A"} = $galileo->GetLoadHistory(  $transactionId++, $account_02b );   
		${"result{$tn}D"} = dlhvardump($galileo->Get_Call_Data(), false);
		${"result{$tn}R"} = $galileo->Get_Raw_Response();
		${"result{$tn}F"} = dlhvardump($galileo->Parse_GetLoadHistory_Xml( ${"result{$tn}A"} ), false);      // array of loadhistory nodes meeting criteria

		$testStart    = 1;
		$testEnd      = 4;
	
	}


	function makeNotNull( $arr, $val, $defaultIfNull='' ) {

		$result = $defaultIfNull;
		
		if ( isset($arr) && is_array($arr) ) {
			$result = trim(isset($arr[$val]) ? $arr[$val] : $defaultIfNull);
			return $result;
		}
		else if ( isset($val) ) {
			$result =trim($val);
			return $result;
		}
		else {
			return $result;
		}
	}
	
	
	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? $_POST[$var] : $default)
			: (isset($_GET[$var]) ? $_GET[$var] : $default);
	}
	
	
	
?>

<html>
	<head>
		<title>Galileo Client Tester</title>
		<style>
			.result { border: 3px solid red; }
			.head, tr.head td { background: #99cccc; font-weight: bold; }
			.hi { background: #ccccff; }
			td { background: #cccccc; }
			div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
		</style>
	
	</head>
<body>

	<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">

		<center>
			<h2>Galileo Client Tester</h2>
			<h3><? echo $url ?></h3>
		</center>

		<center><input type="submit" name="submitButton" value="Submit"></input></center>

		<br clear="all">

		<? if ( $submitButton != '' ) { ?>

		<table align="center" cellpadding="5" cellspacing="5" border="0">
			<tr class="head">
				<td>Transaction Name</td>
				<td>Raw Response from Galileo</td>
				<td>Data Sent to Galileo</td>
				<td>Raw Response from Galileo (double check)</td>
				<td>Filtered loadhistory Nodes</td>
			</tr>

			
			
			<? for ( $i = $testStart; $i <= $testEnd; $i++ ) { ?>
			<tr>
				<td><? echo ${"name$i"} ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result{$i}R"} ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result{$i}D"} ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result{$i}A"} ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result{$i}F"} ?></textarea></td>
			</tr>
			<? } ?>



		</table>

		<? } else { ?>

		<table align="center" cellpadding="5" cellspacing="5" border="0">
			<tr>
				<td class="hi">Click the "Submit" button to run the tests.</td>
			</tr>
		</table>

		<? } ?>

		<br clear="all">

		<center><input type="submit" name="submitButton" value="Submit"></input></center>


	</form>

</body>

</html>
