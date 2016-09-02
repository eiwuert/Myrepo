<?php
	
	include_once( 'dlhdebug.php' );
	include_once( 'cashlynk_client.php' );

	/*
		**********************************************************************************************
		Natalie's LIVE card data, 2005.09.26
		Natalie's Live CashLynk Card Data
		This data worked with the live url for functions 007, 015, 018, 019, 020, 004, 005, 009.
		Did NOT work for 027 - unspecified error, contact support.
		----------------------------------------------------------------------------------------------
		Card PAN 		    5151260000000063
		Cardholder ID 	    999887776
		Card Account 	    00000000600000016
		PIN 			    3236
		Funding program id: 3853
		**********************************************************************************************

		
		Some info from Don 2005.09.16
		-----------------------------
		Client ID = 306
		Program ID = 3853
		Card Stock ID = 129
		Card BIN = 5151260 (I need to check this one - we got test cards with a different bin 5151810)
	*/

	
	$submitButton           = getHttpVar('submitButton');
	$htmlRows               = 4;
	$htmlCols               = 50;
	$DEBUG                  = true;
	
	$ssn_or_id              = '000000019';          // Last value used in TEST
	// $ssn_or_id              = '000000012';          // Last value used LIVE  (also used '7')

	$cardnumber             = '4446661234567892';   // ???
	$cardaccountnumber      = '12345678901234567';  // ???     
	$cardmembernumber       = '0';                  // (from Natalie, 2005.09.27) I THINK it is zero.  Each card we will do is a primary card.
	                                                // There is a feature that allows us to issue a second card to an account -
	                                                // that they share the bank account behind the card.  We are not planning on using that.
	                                                // The card member number = 0 indicates that it is the primary card.  = 1 would indicate a secondary card.

	$cardstock              = '1';                  // TEST
	// $cardstock              = '129';             // This did NOT work.     
	// $cardstock              = '132';             // Eureka!  This worked (Live)!

	$cardbin                = '6034110';            // new TEST value from Natalie 2005-09-27, this worked with func 026
	// $cardbin                = '5151260';
	// $cardbin                = '5151810';
												    // is this same as economic program id or funding program id ???
	// $programid              = '178';                // TEST
	// $programid              = '3853';            // LIVE
	// $economicprogramid      = $programid;
	// $fundingprogramid       = $programid;
	
	$expmonth               = '12';
	$expyear                = '2005';
	$pin                    = '12345';
	$shipname               = 'David Hickman';
	$shipaddress            = '11603 Sweet Nokia Street';
	$shipcity               = 'Las Vegas';
	$shipstate              = 'NV';
	$shipzip                = '89123';
	


	if ( $submitButton != '' ) {
		$cashlynk = new Cashlynk_Client();
		$cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_TEST );
		// $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_LIVE );
		$url = $cashlynk->Get_Url();

		// ------------------------------------------------------------------------------------------------------------------
		// 2005.09.20, Just learned that function 26 (Create_Prepaid_Mc_With_Cardholder_Validation) was created
		// by CashLynk for us specifically to do in one call what previously required 3 calls (functions 001, 002, 003 -
		// create card holder, create card, and create card account).  We will now use just function 26.
		// ------------------------------------------------------------------------------------------------------------------

/*		
		$tn = 1;
		${"name$tn"}      = 'Create_Card_Holder';
		${"result{$tn}A"} = $cashlynk->Create_Card_Holder( $ssn_or_id );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$ssn_or_id = $result1A['P1'] == '000' ? $result1A['P2'] : $ssn_or_id;

		$tn = 2;
		${"name$tn"}      = 'Create_Card';
		${"result{$tn}A"} = $cashlynk->Create_Card( $ssn_or_id, $cardstock, $cardbin, $expmonth, $expyear, $pin, '', '', '', '', '', '', '', $shipname, $shipaddress, $shipcity, $shipstate, $shipzip, '', '', '', '' );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$cardnumber = $result2A['P1'] == '000' ? $result2A['P2'] : $cardnumber;

		$tn = 3;
		${"name$tn"}      = 'Create_Card_Account';
		${"result{$tn}A"} = $cashlynk->Create_Card_Account( $cardmembernumber, '1', $economicprogramid, $fundingprogramid, $cardnumber, '', '', '', '', '', '' );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$cardaccountnumber = $result3A['P1'] == '000' ? $result3A['P2'] : $cardaccountnumber;
		 
		$tn = 4;
		${"name$tn"}      = 'Change_Card_Status';
		${"result{$tn}A"} = $cashlynk->Change_Card_Status( $cardnumber, $cardmembernumber, '1' );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$tn = 5;
		${"name$tn"}      = 'Change_Pin';
		${"result{$tn}A"} = $cashlynk->Change_Pin( $cardnumber, '1234', '4321' );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$tn = 6;
		${"name$tn"}      = 'Transfer_To_Another Card_Account_Same_Card_Pan';
		${"result{$tn}A"} = $cashlynk->Transfer_To_Another_Card_Account_Same_Card_Pan( $cardnumber, $cardaccountnumber, '12345678901234568', 1.56, $cardmembernumber );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);
*/		

		$tn = 7;
		${"name$tn"}      = 'Create_Prepaid_Mc With_Cardholder_Validation';
		${"result{$tn}A"} = $cashlynk->Create_Prepaid_Mc_With_Cardholder_Validation(  $ssn_or_id, 'David', 'Hickman', '11603 Sweet Nokia Street', '', 'Las Vegas', 'NV', '89123', '1-800-555-1212', '01-01-1969', 'AynRand@yahoo.com', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $programid, $cardbin, $cardstock, $pin, '', '' );
		${"result$tn"}    = dlhvardump(${"result{$tn}A"}, false);
		${"pop$tn"}       = dlhvardump($cashlynk->Get_Populated_Fields_Array(), false);
		${"popP$tn"}      = dlhvardump($cashlynk->Get_Populated_Fields_P_Names_Array(), false);

		$testStart    = 7;
		$testEnd      = 7;
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
		<title>CashLynk Client Tester Convenience Methods</title>
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
			<h2>CashLynk Client Tester Convenience Methods</h2>
		</center>

		<center><input type="submit" name="submitButton" value="Submit"></input></center>

		<br clear="all">

		<? if ( $submitButton != '' ) { ?>

		<table align="center" cellpadding="5" cellspacing="5" border="0">
			<tr class="head">
				<td>Transaction Name</td>
				<td>Result from API Call<br><? echo $url ?></td>
				<td>Populated P-Fields</td>
				<td>Populated Fields</td>
			</tr>

			
			
			<? for ( $i = $testStart; $i <= $testEnd; $i++ ) { ?>
			<tr>
				<td><? echo ${"name$i"} ?>:</td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result$i"} ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"popP$i"} ?></textarea></td>
				<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"pop$i"} ?></textarea></td>
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
