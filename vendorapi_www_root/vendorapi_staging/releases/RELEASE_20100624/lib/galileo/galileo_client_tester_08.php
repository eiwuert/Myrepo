<?php

	// Since we seem to be testing the LoadHistory by hand quite a bit, this
	// screen will make it a little easier.  The screen simply takes an account
	// number and runs the LoadHistory report.
	
	include_once( 'dlhdebug.php' );
	include_once( 'galileo_client.php' );

	$submitButton  = getHttpVar('submitButton');
	$transactionId = getHttpVar('transactionId');
	$account       = getHttpVar('account');
	$start_date    = getHttpVar('start_date');
	$end_date      = getHttpVar('end_date');

	$htmlRows      = 15;
	$htmlCols      = 50;
	$url           = '';

	
	$acct_for_andy = '001104374513';  // This account was having problems 2005.09.30.  Tested by hand for Andy.
	
	if ( $transactionId == '' ) $transactionId = '1';  // This is a value we maintain.  It doesn't seem to be terribly significant.
	if ( $account == '' ) $account = $acct_for_andy;

	// The galileo documentation says the dates should be in format YYYY-DD-MM hh:mm:ss
	// but when we run it we get a response saying they should be in format MM/DD/YYYY
	if ( $start_date == '' ) $start_date = date('m/d/Y', time() - 1209600); // go back 2 weeks by default
	if ( $end_date == '' ) $end_date = date('m/d/Y');
	
	// if ( $start_date == '' ) $start_date = date('Y-m-d H:i:s', time() - 1209600); // go back 2 weeks by default
	// if ( $end_date == '' ) $end_date = date('Y-m-d H:i:s'); 
	

	if ( $submitButton != '' ) {
		
		$galileo = new Galileo_Client();

		$url = $galileo->Get_Url();

		$data_received = $galileo->History( $transactionId, $account, $start_date, $end_date );
		$data_sent     = $galileo->Get_Call_Data();
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
		<title>HISTORY: Galileo Client Tester 08</title>
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
				<h2>HISTORY: Galileo Client Tester 08</h2>
				<h4>Test Galileo Client Method: History()</h4>
				<h4>URL: <? echo $url ?></h4>
			</center>
	
			<center><input type="submit" name="submitButton" value="Submit"></input></center>
	
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td>transaction id:</td>
					<td align="left" colspan="2" class="head">
						<input type="text" name="transactionId" value="<? echo $transactionId ?>">
					</td>
				</tr>
				<tr>
					<td>account:</td>
					<td align="left" colspan="2" class="head">
						<input type="text" name="account" value="<? echo $account ?>">
					</td>
				</tr>
				<tr>
					<td>start date:</td>
					<td align="left" colspan="2" class="head">
						<input type="text" name="start_date" value="<? echo $start_date ?>"> (MM/DD/YYYY)
					</td>
				</tr>
				<tr>
					<td>end date:</td>
					<td align="left" colspan="2" class="head">
						<input type="text" name="end_date" value="<? echo $end_date ?>"> (MM/DD/YYYY)
					</td>
				</tr>
			</table>
			
			<br clear="all">
	
			<? if ( $submitButton != '' ) { ?>
			
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr class="head">
					<td>Account Number</td>
					<td>Data Sent</td>
					<td>Data Received</td>
				</tr>
	
				<tr>
					<td><? echo $account ?></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($data_sent, false) ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $data_received ?></textarea></td>
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
