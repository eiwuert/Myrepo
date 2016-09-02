<?php
	
	require_once('logsimple.php');
	require_once('prpc/client.php');
	
	$submitState     = getHttpVar('submitState');
	$submitTimezone  = getHttpVar('submitTimezone');
	$state           = getHttpVar('state');
	$timezone        = getHttpVar('timezone');
	$mode            = getHttpVar('mode', 'LOCAL');

	$localchecked    = $mode == 'LOCAL' ? ' CHECKED ' : '';
	$rcchecked       = $mode == 'RC' ? ' CHECKED ' : '';
	$livechecked     = $mode == 'LIVE' ? ' CHECKED ' : '';

	$result = '';

	// I can't do this mode stuff until I make the timezone methods public and
	// put their proxies into the sms_prpc.php code.
	switch($mode)
	{
		default:
		case 'LOCAL':
			preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $_SERVER['SERVER_NAME'], $matched);
			$local_name = isset($matched[1]) ? $matched[1] : '';
			if ( $local_name == '' )
			{
				$server = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
			}
			else
			{
				$server = "prpc://sms.$local_name.tss:8080/sms_prpc.php";
			}
			break;
		
		case 'RC':
			$server = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
			break;
	
		case 'LIVE':
			$server = 'prpc://sms.edataserver.com/sms_prpc.php';
			break;
	}

	if ( $submitState != '' )
	{
		$sms_obj = new PRPC_Client($server);
		$result = $sms_obj->Get_Time_Zone($state);
	}
	else if ( $submitTimezone != '' )
	{
		$sms_obj = new PRPC_Client($server);
		$result = $sms_obj->Check_Time_Zone($timezone);
	}

	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? trim($_POST[$var]) : $default)
			: (isset($_GET[$var])  ? trim($_GET[$var])  : $default);
	}

	
?>
<html>
	<head>
		<title>Check Timezone</title>
		<style>
			.result { border: 3px solid red; }
			.head, tr.head td { background: #99cccc; font-weight: bold; font-size: 1.4em; }
			.hi { background: #ccccff; }
			.data { font-family: monospace; font-size: .9em; }
			td { background: #cccccc; }
			div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
			a { text-decoration: none; color: #3366ff }
			a:hover { text-decoration: underline; background-color: yellow; }
		</style>
	</head>
	<body>
    
		<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
	
			<center>
				<h2>Check Timezone</h2>
			</center>
	
			<br clear="all">&nbsp;<br>

			<table align="center" cellpadding="5" cellspacing="1" border="0">
				<tr><td>gmdate('Hi', (time() + (-8 * 3600)));</td><td><?= gmdate('Hi', (time() + (-8 * 3600))) ?></td></tr>
				<tr><td>gmdate('Y-m-d H:i', (time() + (-8 * 3600)));</td><td><?= gmdate('Y-m-d H:i', (time() + (-8 * 3600))) ?></td></tr>
				<tr><td>date('Y-m-d H:i', (time() + (-8 * 3600)));</td><td><?= date('Y-m-d H:i', (time() + (-8 * 3600))) ?></td></tr>
				<tr><td>State:</td><td><input type="text" name="state" value="<?=$state?>"></td></tr>
				<tr><td>Timezone Abbreviation:</td><td><input type="text" name="timezone" value="<?=$timezone?>"></td></tr>
				<tr>
					<td colspan="2" align="center">
						<input type="radio" name="mode" value="LOCAL" <?=$localchecked?> >LOCAL
						<input type="radio" name="mode" value="RC" <?=$rcchecked?> >RC
						<input type="radio" name="mode" value="LIVE" <?=$livechecked?> >LIVE
						&nbsp;&nbsp;
						<input type="submit" name="submitButton" value="Submit">
						<input type="submit" name="submitState" value="Check State">
						<input type="submit" name="submitTimezone" value="Check Timezone">
					</td>
				</tr>
			</table>

			<br clear="all">

			<table align="center" cellpadding="10" cellspacing="1" border="0">
				<tr>
					<td><pre><?= logsimpledump($result) ?></pre></td>
				</tr>
			</table>

			<br clear="all">
			
		</form>
  
	</body>

</html>
 
 
 
