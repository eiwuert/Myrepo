<?php

	require_once('logsimple.php');
	
	$telephone  = getHttpVar('telephone');
	$message_id = getHttpVar('message_id');  if ( $message_id == '' ) $message_id = '1';
	$message_in = getHttpVar('message');
	$message    = urlencode($message_in);
	$testmodem  = getHttpVar('testmodem');

	$data_array = array(

		/*
		"Andrew's Machine" => array(
			'url'       => 'http://10.1.38.2',
			'adminPort' => ':13000',
			'sendPort'  => ':13013',
			'password'  => 'password',
			'modems'    => array(
				array('modem_0454', 'test')
			)
		),
		*/

		'RC' => array(
			'notes'     => 'Only modem_3639 is configured for receiving messages!',
			'url'       => 'http://kannel.edataserver.com',
			'adminPort' => ':13000',
			'sendPort'  => ':13013',
			'password'  => 'password',
			'modems'    => array(
				 array('modem_3525', 'test', '8014943525')
				// ,array('modem_3639', 'test', '7024034175')     
				// ,array('modem_4287', 'test', '7024032654')
				// ,array('modem_4552', 'test', '7024034415')
				,array('modem_8821', 'test', '7024034273')
				// ,array('modem_9981', 'test', '7023279981')     // this modem is at my desk
			)
		),
		
		'LIVE!!!' => array(
			'notes'     => '',
			'url'       => 'http://kannel2.edataserver.com',
			'adminPort' => ':13000',
			'sendPort'  => ':13013',
			'password'  => 'password',
			'modems'    => array(
				 array('modem_2952', 'test2', '7024034136')
				,array('modem_0454', 'test2', '7024034298')
				,array('modem_6515', 'test2', '7023582396')
				,array('modem_8958', 'test2', '7024034354')
				,array('modem_3924', 'test2', '7024034236')
			)
		)
		
	);


	// This will send a test message between ALL of the configured modems for a particular group.
	// It's a handy way to make sure all modems are working and to find out their actual phone numbers.
	if ( $testmodem != '' )
	{
		$modem_data = $data_array[$testmodem];
		$url = $modem_data['url'] . $modem_data['sendPort'] . '/cgi-bin/sendsms?message_id=1&username={FROM_NAME}&password={FROM_PASSWORD}&to={TO_NUMBER}&text={TEXT}';
		$modem_array = $modem_data['modems'];
		foreach($modem_array as $from_modem)
		{
			$from_name = $from_modem[0];
			$from_password = $from_modem[1];
		
			foreach($modem_array as $to_modem)
			{
				$to_name = $to_modem[0];
				$to_number = $to_modem[2];
			
				if ( $to_name != $from_name )
				{
					$search_array  = array( '{FROM_NAME}', '{FROM_PASSWORD}', '{TO_NUMBER}', '{TEXT}' );
					$replace_array = array( $from_name, $from_password, $to_number, urlencode("test from $from_name to $to_name") );
					$urlsend = str_replace( $search_array, $replace_array, $url );

					$curl = curl_init($urlsend);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					$response = curl_exec($curl);
					logsimplewrite("curl response=" . logsimpledump($response) . ", urlsend=$urlsend");
				}
			}
			
		}
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
		<title>Modem and Bearerbox Control</title>
		<style>
			.result { border: 3px solid red; }
			.head, tr.head td { background: #99cccc; font-weight: bold; font-size: 1.4em; }
			.hi { background: #ccccff; }
			.data { font-family: monospace; font-size: .9em; }
			.notes { color: #aa0000; font-size: .7em; font-weight: 100; }
			td { background: #cccccc; }
			div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
			a { text-decoration: none; color: #3366ff }
			a:hover { text-decoration: underline; background-color: yellow; }
		</style>
	</head>
	<body>
    
		<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
	
			<center>
				<h2>Modem and Bearerbox Control</h2>
			</center>
	
			<table align="left" cellpadding="2" cellspacing="2">
				<tr><td colspan="2">telephone: <input type="text" name="telephone" value="<?= $telephone ?>" size="12">&nbsp; message_id: <input type="text" name="message_id" value="<?= $message_id ?>" size="12">&nbsp; message: <input type="text" name="message" value="<?= $message_in ?>" size="60">&nbsp; <input type="submit" name="submitButton" value="Regenerate"></td></tr>
			</table>
				
			<br clear="all">&nbsp;<br>

			<?php foreach( $data_array as $name => $url_data_array ) { ?>
				<table align="left" cellpadding="2" cellspacing="2">
					<tr><td colspan="2" class="head"><?=$name?>&nbsp;&nbsp;&nbsp;<span class="notes"><?=$url_data_array['notes']?></span></td><tr>
					<tr><td class="hi"><?=$name?> STOP: </td><td class="hi"><a href="<?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/shutdown?password=<?=$url_data_array['password']?>"><?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/shutdown?password=<?=$url_data_array['password']?></a></td><tr>
					<tr>
						<td class="hi"><?=$name?> STATUS: </td>
						<td class="data">
							<a href="<?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/status?password=<?=$url_data_array['password']?>"><?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/status?password=<?=$url_data_array['password']?></a>
							<br>
							<a href="<?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/status.xml?password=<?=$url_data_array['password']?>"><?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/status.xml?password=<?=$url_data_array['password']?></a>
						</td>
					<tr>
					<tr><td class="hi">Test:</td><td class="hi">Send test message between all modems:&nbsp;&nbsp; <a href="?testmodem=<?=$name?>">?testmodem=<?=$name?></a></td><tr>
						
					<? foreach( $url_data_array['modems'] as $modem_array ) { ?>
					<tr>
						<td><?= $modem_array[0] ?>: </td>
						<td class="data">
							<a href="<?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/stop-smsc?password=<?=$url_data_array['password']?>&smsc=<?=$modem_array[0]?>"><?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/stop-smsc?password=<?=$url_data_array['password']?>&smsc=<?=$modem_array[0]?></a>
							<br>
							<a href="<?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/start-smsc?password=<?=$url_data_array['password']?>&smsc=<?=$modem_array[0]?>"><?=$url_data_array['url']?><?=$url_data_array['adminPort']?>/start-smsc?password=<?=$url_data_array['password']?>&smsc=<?=$modem_array[0]?></a>
							<br>
							<a href="<?=$url_data_array['url']?><?=$url_data_array['sendPort']?>/cgi-bin/sendsms?message_id=<?=$message_id?>&username=<?=$modem_array[0]?>&password=<?=$modem_array[1]?>&to=<?=$telephone?>&text=<?=$message?>"><?=$url_data_array['url']?><?=$url_data_array['sendPort']?>/cgi-bin/sendsms?message_id=<?=$message_id?>&username=<?=$modem_array[0]?>&password=<?=$modem_array[1]?>&to=<?=$telephone?>&text=<?=$message?></a>
						</td>
					<tr>
					<? } ?>
				</table>
				<br clear="all">&nbsp;<br>
			<?php } ?>
			
		</form>
  
	</body>

</html>
 
 
