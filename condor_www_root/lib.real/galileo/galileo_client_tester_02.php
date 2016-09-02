<?php
	
	include_once( 'dlhdebug.php' );
	include_once( 'galileo_client.php' );

	$submitButton  = getHttpVar('submitButton');
	$htmlRows      = 15;
	$htmlCols      = 50;
	$url           = '';

	$transactionId = '1';  // This is a value we maintain.  I will increment it manually.
	
	// Just received new account info from Ryan Berringer
	$account_01    = '5185300504480252';
	$account_01b   = '001104448492';        // should give same results as 01; this is prn rather than pan (don't know what that means) 
	$account_02    = '5185300500030952';        
	$account_02b   = '001100030491';        // should give same results as 02; this is prn rather than pan (don't know what that means)
	$account_03    = '4870930100000402';    
	

	if ( $submitButton != '' ) {
		
		$galileo = new Galileo_Client();

		$url = $galileo->Get_Url();

		$result_master_array_01 = $galileo->Get_Payroll_Load_History(  $transactionId++, $account_01 );
		$data_sent_01           = $result_master_array_01['SENT'];
		$data_received_01       = $result_master_array_01['RECEIVED'];
		$filtered_payroll_01    = $result_master_array_01['FILTERED_PAYROLL_ITEMS'];

		$result_master_array_02 = $galileo->Get_Payroll_Load_History(  $transactionId++, $account_02 );
		$data_sent_02           = $result_master_array_01['SENT'];
		$data_received_02       = $result_master_array_01['RECEIVED'];
		$filtered_payroll_02    = $result_master_array_01['FILTERED_PAYROLL_ITEMS'];

		$result_master_array_03 = $galileo->Get_Payroll_Load_History(  $transactionId++, $account_03 );
		$data_sent_03           = $result_master_array_01['SENT'];
		$data_received_03       = $result_master_array_01['RECEIVED'];
		$filtered_payroll_03    = $result_master_array_01['FILTERED_PAYROLL_ITEMS'];

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
		<title>Galileo Client Tester 02</title>
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
				<h2>Galileo Client Tester 02</h2>
				<h4>Test Galileo Client Method: Get_Payroll_Load_History()</h4>
				<h4>URL: <? echo $url ?></h4>
			</center>
	
			<center><input type="submit" name="submitButton" value="Submit"></input></center>
	
			<br clear="all">
	
			<? if ( $submitButton != '' ) { ?>
			
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr class="head">
					<td>Account Number</td>
					<td>Data Sent</td>
					<td>Data Received</td>
					<td>Filtered Payroll Data</td>
				</tr>
	
				<tr>
					<td><? echo $account_01 ?></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($data_sent_01, false) ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $data_received_01 ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($filtered_payroll_01, false) ?></textarea></td>
				</tr>
	
				<tr>
					<td><? echo $account_02 ?></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($data_sent_02, false) ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $data_received_02 ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($filtered_payroll_02, false) ?></textarea></td>
				</tr>
	
				<tr>
					<td><? echo $account_03 ?></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($data_sent_03, false) ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $data_received_03 ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($filtered_payroll_03, false) ?></textarea></td>
				</tr>
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
