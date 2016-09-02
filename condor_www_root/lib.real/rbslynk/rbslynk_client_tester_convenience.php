<?php

	include_once( 'rbslynk_client.php' );
	
	$submitButton = getHttpVar('submitButton');

	$htmlRows     = 4;
	$htmlCols     = 50;
	
	$DEBUG        = true;
	$error_text   = '';
	$result       = '';
	$amount       = '1.23';
	$amount2      = '4.56';
	$expdate      = '12/2005';
	$mc           = '5499974444444445';
	$disc         = '6011000993069248';
	$visa         = '4446661234567892';
	$amex         = '373235387881007';
	$SellerId     = '12345';
	$Password     = 'myPassword';
	$ApprovalCode = 'someApprovalCode';


	
	$rbslynk = new Rbslynk_Client();
	$rbslynk->Set_Operating_Mode( Rbslynk_Client::RUN_MODE_TEST );
	$url = $rbslynk->Get_Url();

	$name1        = '#1:<br> Moto_Sale';
	$result1A     = $rbslynk->Moto_Sale( '', '', '', '', '', '', '', '', '', '', '', '', $visa, $expdate, '', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $amount );
	$ApprovalCode = makeNotNull($result1A, 'ApprovalCode', $ApprovalCode);
	$result1      = vardump($result1A);
	$pop1         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name2        = '#2:<br> Regular_Sale';
	$result2A     = $rbslynk->Regular_Sale( '', 'David', '', '', '', '123 Easy Street', 'Las Vegas', 'Nevada', '85015', 'USA', '', '', $amex, $expdate, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $amount2 );
	$ApprovalCode = makeNotNull($result2A, 'ApprovalCode', $ApprovalCode);
	$result2      = vardump($result2A);
	$pop2         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name3        = '#3:<br> Regular_Verify_Reserve';
	$result3A	  = $rbslynk->Regular_Verify_Reserve( '', 'David', '', '', '', '123 Easy Street', 'Phoenix', 'Arizona', '85015', 'USA', '', '', $mc, $expdate, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $amount );
	$ApprovalCode = makeNotNull($result3A, 'ApprovalCode', $ApprovalCode);
	$OrderId3     = $result3A['OrderId'];
	$result3      = vardump($result3A);
	$pop3         = vardump($rbslynk->Get_Populated_Fields_Array());

	// Sometimes this works successfully and sometimes it
	// fails with the same error as in number 5.
	$name4        = '#4:<br> Regular_Settlement';
	$result4A     = $rbslynk->Regular_Settlement( $OrderId3, $amount, $SellerId, $Password, '', '', '', '', '' );
	$ApprovalCode = makeNotNull($result4A, 'ApprovalCode', $ApprovalCode);
	$result4      = vardump($result4A);
	$pop4         = vardump($rbslynk->Get_Populated_Fields_Array());

	// This one fails: [ErrorCode] => ERROR_INVALID_LOGIN [ErrorMsg] => Error - A wrong UserId or Password was entered
	// I don't know what to put in for password.
	$name5        = '#5:<br> Regular_Force_Capture';
	$result5A     = $rbslynk->Regular_Force_Capture( '' , 'David' , '' , 'Mars' , '' , '123 Easy Street' , 'Las Vegas' , 'NV' , '89123' , 'US' , '' , '' , $amex , $expdate , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , $amount2 , $SellerId , $Password , $ApprovalCode );
	$ApprovalCode = makeNotNull($result5A, 'ApprovalCode', $ApprovalCode);
	$result5      = vardump($result5A);
	$pop5         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name6        = '#6:<br> Regular_Refund_Request Using_Previous_Order';
	$result6A     = $rbslynk->Regular_Refund_Request_Using_Previous_Order( $OrderId3, $amount, $SellerId, $Password );
	$ApprovalCode = makeNotNull($result6A, 'ApprovalCode', $ApprovalCode);
	$result6      = vardump($result6A);
	$pop6         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name7        = '#7:<br> Regular_Refund_Request Using_Card_Number';
	$result7A     = $rbslynk->Regular_Refund_Request_Using_Card_Number( '' , 'David' , '' , '' , '' , '123 Easy Street' , 'Las Vegas' , 'Nevada' , '89123' , 'USA' , '' , '' , 'visa' , $expdate , '' , '' , 33.33 , $SellerId , $Password );
	$ApprovalCode = makeNotNull($result7A, 'ApprovalCode', $ApprovalCode);
	$result7      = vardump($result7A);
	$pop7         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name8        = '#8:<br> Moto_Verify_Reserve';
	$result8A     = $rbslynk->Moto_Verify_Reserve( '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , $visa , $expdate , '' , '1' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , '' , $amount2 );
	$OrderId8     = $result8A['OrderId'];
	$ApprovalCode = makeNotNull($result8A, 'ApprovalCode', $ApprovalCode);
	$result8      = vardump($result8A);
	$pop8         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name9        = '#9:<br> Moto_Settlement_Request';
	$result9A     = $rbslynk->Moto_Settlement_Request( $OrderId8, $amount2, $SellerId, $Password, '', '', '', '', '' );
	$ApprovalCode = makeNotNull($result9A, 'ApprovalCode', $ApprovalCode);
	$result9      = vardump($result9A);
	$pop9         = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name10       = '#10:<br> Moto_Force_Capture Using_Voice_Authorization';
	$result10A    = $rbslynk->Moto_Force_Capture_Using_Voice_Authorization( '', '', '', '', '', '', '', '', '', '', '', '', $mc, $expdate, '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $amount2, $SellerId, $Password, $ApprovalCode );
	$ApprovalCode = makeNotNull($result10A, 'ApprovalCode', $ApprovalCode);
	$result10     = vardump($result10A);
	$pop10        = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name11       = '#11:<br> Moto_Refund_Request Using_Previous_Order';
	$result11A    = $rbslynk->Moto_Refund_Request_Using_Previous_Order( $OrderId8, $SellerId, $Password, $amount2 );
	$ApprovalCode = makeNotNull($result11A, 'ApprovalCode', $ApprovalCode);
	$result11     = vardump($result11A);
	$pop11        = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$name12       = '#12:<br> Moto_Refund_Request Using_Card_Number';
	$result12A    = $rbslynk->Moto_Refund_Request_Using_Card_Number( '', '', '', '', '', '', '', '', '', '', '', '', $amex, $expdate, '1', '', $amount2, $SellerId, $Password );
	$ApprovalCode = makeNotNull($result12A, 'ApprovalCode', $ApprovalCode);
	$result12     = vardump($result12A);
	$pop12        = vardump($rbslynk->Get_Populated_Fields_Array());
	
	$testStart    = 1;
	$testEnd      = 12;


	function makeNotNull( $arr, $val, $defaultIfNull='' ) {

		$result = $defaultIfNull;
		
		if ( isset($arr) && is_array($arr) ) {
			$result = trim(isset($arr[$val]) ? $arr[$val] : $defaultIfNull);
			// dlhlog( "makeNotNull: returning result=$result" );
			return $result;
		}
		else if ( isset($val) ) {
			$result =trim($val);
			// dlhlog( "makeNotNull: returning result=$result" );
			return $result;
		}
		else {
			// dlhlog( "makeNotNull: returning result=$result" );
			return $result;
		}
	}
	
	
	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? $_POST[$var] : $default)
			: (isset($_GET[$var]) ? $_GET[$var] : $default);
	}
	
	
	function dlhlog( $msg, $filename='/_log/rbslynk_client_tester_convenience.log' ) {
		global $DEBUG;
		if ( !$DEBUG ) return;
		$fp = fopen( $filename, 'a+' );
		fwrite($fp, date('Y-m-d H:i:s: ') . $msg . "\r\n");
		fclose($fp);
	}
	
	
	function vardump( $var, $stripnewlines=false ) {
		ob_start();
		print_r( $var );
		$output = ob_get_contents();
		ob_end_clean();
		if ( $stripnewlines ) {
			$output = strtr( $output, "\r\n", '  ' );
			$output = preg_replace( '/(\s)+/', ' ', $output );
		}
		return $output;
	}

?>

<html>
	
	<head>
		<title>RBSLynk Client Tester Convenience Methods</title>
		<style>
		.result { border: 1px solid red; }
		.head, tr.head td { background: #99cccc; font-weight: bold; }
		.hi { background: #ccccff; }
		td { background: #cccccc; }
		div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
		</style>
		
	</head>

	<body>
		
		<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
	
			<input type="hidden" name="screenInitialCall" value="<? echo $screenInitialCall ?>"></input>
		
			<center><h1>RBSLynk Client Tester Convenience Methods</h1></center>
			
			<center><input type="submit" name="submitButton" value="Submit"></input></center>
			
			<br clear="all">
		
			<? if ( $submitButton != '' ) { ?>
			
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr class="head">
					<td>Transaction Name</td>
					<td>Result from API Call<br><? echo $url ?></td>
					<td>Populated Fields</td>
				</tr>

				
				<? for ( $i = $testStart; $i <= $testEnd; $i++ ) { ?>
				<tr>
					<td><? echo ${"name$i"} ?>:</td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"result$i"} ?></textarea></td>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo ${"pop$i"} ?></textarea></td>
				</tr>
				<? } ?>

				
			</table>

			<? } else { ?>

			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td class="head">Click the "Submit" button to run the tests.</td>
				</tr>
			</table>

			<? } ?>
			
			<br clear="all">
		
			<center><input type="submit" name="submitButton" value="Submit"></input></center>
			
		</form>
	
	</body>
	
</html>
