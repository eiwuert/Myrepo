<?php

	// The purpose of this tester is test the xml parsing.
	
	include_once( 'dlhdebug.php' );
	include_once( 'galileo_client.php' );

	$submitButton  = getHttpVar('submitButton');
	$submitClear   = getHttpVar('submitClear');
	$xml           = getHttpVar('xml');
	$htmlRows      = 15;
	$htmlCols      = 100;
	$url           = '';

	if ( $submitButton != '' ) {
		$galileo = new Galileo_Client();
		$result = $galileo->Parse_GetLoadHistory_Xml( $xml );
	}
	else if ( $submitClear != '' ) {
		$xml = '';
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
		<title>Galileo Client Tester 03</title>
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
				<h2>Galileo Client Tester 03</h2>
				<h4>Test Galileo Client XML Parsing</h4>
			</center>
	
			<center>
				<input type="submit" name="submitButton" value="Submit"></input>
				<input type="submit" name="submitClear" value="Clear Screen"></input>
			</center>
	
			<br clear="all">
	
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td align="center" colspan="2" class="head">
						<textarea name="xml" rows="<? echo $htmlRows ?>" cols="<? echo $htmlCols ?>" wrap="soft"><? echo $xml ?></textarea>
					</td>
				</tr>
			</table>
			
			<br clear="all">
	
			<? if ( $submitButton != '' ) { ?>
			
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td><textarea  wrap="soft" cols="<? echo $htmlCols ?>" rows="<? echo $htmlRows ?>"><? echo dlhvardump($result, false) ?></textarea></td>
				</tr>
			</table>
	
			<? } else { ?>
	
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td class="hi">Paste your xml in the box above and click the "Submit" button to run the tests.</td>
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
