<?php

	// The purpose of this tester is test the Is_ABA_Number method.
	
	include_once( 'dlhdebug.php' );
	include_once( 'galileo_client.php' );

	$submitButton  = getHttpVar('submitButton');
	$submitClear   = getHttpVar('submitClear');
	$aba_number    = getHttpVar('aba_number');


	if ( $submitButton != '' ) {
		$galileo = new Galileo_Client();
		$result = $galileo->Is_ABA_Number( $aba_number ) ? 'TRUE - this is a galileo ABA number' : 'FALSE - this number is NOT a galileo ABA number';
	}
	else if ( $submitClear != '' ) {
		$aba_number = '';
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
		<title>Galileo Client Tester 04</title>
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
				<h2>Galileo Client Tester 04</h2>
				<h4>Test Galileo Client Is_ABA_Number() Method</h4>
			</center>
	
			<center>
				<input type="submit" name="submitButton" value="Submit"></input>
				<input type="submit" name="submitClear" value="Clear Screen"></input>
			</center>
	
			<br clear="all">
	
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td align="center" colspan="2" class="head">
						<input type="text" name="aba_number" value="<? echo $aba_number ?>" size="40"></input>
					</td>
				</tr>
			</table>
			
			<br clear="all">
	
			<? if ( $submitButton != '' ) { ?>
			
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td><? echo $result ?></td>
				</tr>
			</table>
	
			<? } else { ?>
	
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td class="hi">Paste your ABA number in the box above and click the "Submit" button to run the test.</td>
				</tr>
			</table>
	
			<? } ?>
	
			<br clear="all">
	
			<center>
				<input type="submit" name="submitButton" value="Submit"></input>
				<input type="submit" name="submitClear" value="Clear Screen"></input>
			</center>
	
	
	
		</form>
	
	</body>

</html>
