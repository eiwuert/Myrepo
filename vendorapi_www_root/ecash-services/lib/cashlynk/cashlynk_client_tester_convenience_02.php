<?php
	
	include_once( 'dlhdebug.php' );
	include_once( 'cashlynk_client.php' );

	// The convenience methods in Cashlynk_Client have become more convenient.
	// This tester tests the new convenience methods in the simplest way possible.
	// Hopefully, this will also serve as a good example of how to use Cashlynk_Client.
	// Also, hopefully, by displaying the contents of the array that is returned
	// after parsing XML responses, it will give a clear example of exactly what
	// is in those arrays.
	//
	// This test will run in the LIVE environment using Natalie's LIVE card that
	// she supplied us with via email on: Monday 2005.09.26
	//
	// Natalie's Live CashLynk Card Data
	// Card PAN:            5151260000000063
	// Cardholder ID:       999887776
	// Card Account:        00000000600000016
	// PIN:                 3236
	// Funding program id:  3853      (now set automatically in Cashlynk_Client for LIVE vs TEST environment)
		
	$submitButton           = getHttpVar('submitButton');
	$htmlRows               = 10;
	$htmlCols               = 100;
	
	$cardnumber             = '5151260000000063';  
	$ssn_or_id              = '999887776';  
	$cardaccountnumber      = '00000000600000016'; 
	$pin                    = '3236';
	
	$shipname               = 'David Hickman';
	$shipaddress            = '11603 Sweet Nokia Street';
	$shipcity               = 'Las Vegas';
	$shipstate              = 'NV';
	$shipzip                = '89123';
	$expmonth               = '12';
	$expyear                = '2005';


	if ( $submitButton != '' ) {
		$cashlynk = new Cashlynk_Client();
		$cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_LIVE );

		// 007 Deposit to card
		$result_007_desc = '007 - Depositing $5 to card';
		$result_007      = $cashlynk->Deposit_To_Card_Account( $cardaccountnumber, '5.00', 'deposit' );
		$result_007_dump = dlhvardump( $result_007, false );
		$result_007_raw  = $cashlynk->Get_Raw_Response();
		$result_007_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 009 Get Card Details
		$result_009_desc = '009 - Get Card Details (xml response)';
		$result_009      = $cashlynk->View_Card_Details( $cardnumber, '9/1/2005', '9/30/2005' );
		$result_009_dump = dlhvardump( $result_009, false );
		$result_009_raw  = $cashlynk->Get_Raw_Response();
		$result_009_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 010 Get Card Accounts For Card Number
		$result_010_desc = '010 - Get Card Accounts For Card Number (xml response)';
		$result_010      = $cashlynk->Get_Card_Accounts_For_Card_Number( $cardnumber );
		$result_010_dump = dlhvardump( $result_010, false );
		$result_010_raw  = $cashlynk->Get_Raw_Response();
		$result_010_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();

		// 020 Get Card Transaction Detail
		$result_020_desc = '020 - Get Card Transaction Detail (xml response)';
		$result_020      = $cashlynk->Get_Card_Transaction_Detail( $cardnumber, '9/1/2005', '9/30/2005' );
		$result_020_dump = dlhvardump( $result_020, false );
		$result_020_raw  = $cashlynk->Get_Raw_Response();
		$result_020_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 018 Get Short Summary For Card
		$result_018_desc = '018 - Get Short Summary For Card';
		$result_018      = $cashlynk->Get_Short_Summary_For_Card( $cardnumber );
		$result_018_dump = dlhvardump( $result_018, false );
		$result_018_raw  = $cashlynk->Get_Raw_Response();
		$result_018_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 019 Get Card Account Balance
		$result_019_desc = '019 - Get Card Account Balance';
		$result_019      = $cashlynk->Get_Card_Account_Balance( $cardaccountnumber );
		$result_019_dump = dlhvardump( $result_019, false );
		$result_019_raw  = $cashlynk->Get_Raw_Response();
		$result_019_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 015 Edit Cardholder
		$result_015_desc = '015 - Edit Cardholder';
		$result_015      = $cashlynk->Edit_Cardholder( $ssn_or_id, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Note added for testing', '' );
		$result_015_dump = dlhvardump( $result_015, false );
		$result_015_raw  = $cashlynk->Get_Raw_Response();
		$result_015_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 004 Change Card Status
		$result_004_desc = '004 - Change Card Status to HOLD';
		$result_004      = $cashlynk->Change_Card_Status(  $cardnumber, 'hold' );
		$result_004_dump = dlhvardump( $result_004, false );
		$result_004_raw  = $cashlynk->Get_Raw_Response();
		$result_004_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 004 Change Card Status
		$result_004b_desc = '004 - Change Card Status back to ENABLED';
		$result_004b      = $cashlynk->Change_Card_Status(  $cardnumber, 'enabled' );
		$result_004b_dump = dlhvardump( $result_004b, false );
		$result_004b_raw  = $cashlynk->Get_Raw_Response();
		$result_004b_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 005 Change Pin
		$result_005_desc = '005 - Change Pin from 3236 to 1234';
		$result_005      = $cashlynk->Change_Pin(  $cardnumber, '3236', '1234' );
		$result_005_dump = dlhvardump( $result_005, false );
		$result_005_raw  = $cashlynk->Get_Raw_Response();
		$result_005_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
		// 005 Change Pin
		$result_005b_desc = '005 - Change Pin back to 3236 from 1234';
		$result_005b      = $cashlynk->Change_Pin(  $cardnumber, '1234', '3236' );
		$result_005b_dump = dlhvardump( $result_005b, false );
		$result_005b_raw  = $cashlynk->Get_Raw_Response();
		$result_005b_sent = 'msg_id=' . $cashlynk->Get_Msg_Id() . "\r\n" . $cashlynk->Get_Raw_Data_Sent();
		
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
		<title>CashLynk Client Tester Convenience Methods 02</title>
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
			<h2>CashLynk Client Tester Convenience Methods 02</h2>
		</center>

		<center><input type="submit" name="submitButton" value="Submit"></input></center>

		<br clear="all">

		<? if ( $submitButton != '' ) { ?>

		<table align="center" cellpadding="5" cellspacing="5" border="0">
			<tr class="head">
				<td>Transaction Description</td>
				<td>Parsed Response</td>
				<td>Raw Response</td>
				<td>Raw Data Sent</td>
			</tr>
			
			<tr>
				<td><? echo $result_007_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_007_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_007_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_007_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_009_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_009_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_009_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_009_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_010_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_010_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_010_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_010_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_020_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_020_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_020_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_020_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_018_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_018_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_018_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_018_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_019_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_019_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_019_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_019_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_015_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_015_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_015_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_015_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_004_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_004b_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004b_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004b_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_004b_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_005_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005_sent ?></textarea></td>
			</tr>

			<tr>
				<td><? echo $result_005b_desc ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005b_dump ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005b_raw ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo $result_005b_sent ?></textarea></td>
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
