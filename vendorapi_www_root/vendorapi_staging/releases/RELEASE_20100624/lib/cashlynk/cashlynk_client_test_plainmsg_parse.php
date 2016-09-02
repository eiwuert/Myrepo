<?php
	
	include_once( 'dlhdebug.php' );
	include_once( 'cashlynk_client.php' );

	$textinput = getHttpVar('textinput');
	if ( $textinput == '' ) $textinput = 'P1=001|Unspecified Error. The specified cardholder already exists.';
	// here's a response from 018 that uncovered a but in my parse routine: P1=000 P2= P3=350.6900 P4=40.0000 P5=0 P6=0 P7=0
	$cashlynk  = new Cashlynk_Client();
	$result    = dlhvardump( $cashlynk->Get_Text_Response_Parsed( $textinput ), false );
	

	
	function getHttpVar( $var, $default='' ) {
		return
			'POST' == $_SERVER['REQUEST_METHOD']
				? (isset($_POST[$var]) ? $_POST[$var] : $default)
				: (isset($_GET[$var]) ? $_GET[$var] : $default);
	}

?>

<html>
	<head>
		<title>Cashlynk Client Test Plain Msg Parse</title>
		<style>
			.head, tr.head td { background: #99cccc; font-weight: bold; }
			.hi { background: #ccccff; }
			td { background: #cccccc; }
			div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
		</style>
	
	</head>
	<body>
	
		<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
	
			<center>
				<h2>Cashlynk Client Test Plain Msg Parse</h2>
			</center>
		
			<table align="center" cellpadding="5" cellspacing="5" border="0">
				<tr>
					<td colspan="2" class="head">
						<input type="text" name="textinput" size="80" maxlength="255" value="<? echo $textinput ?>">
					</td>
				</tr>
				<tr>
					<td>result:</td>
					<td><textarea cols="100" rows="15"><? print $result ?></textarea></td>
				</tr>
			</table>
		
			<br clear="all">

			<center>
				<input type="submit" name="submitButton" value="Submit"></input>
			</center>
	
		</form>
	
	</body>
</html>

