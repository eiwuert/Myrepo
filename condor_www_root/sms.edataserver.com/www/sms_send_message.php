<?
	require_once 'sms.php';
	require_once 'prpc/client.php';

	$response = '';

	$submitButton  = getHttpVar('submitButton');
	$phone_number  = getHttpVar('phone_number');
	$message       = getHttpVar('message');
	$company_short = getHttpVar('company_short');
	
	if ($submitButton && $phone_number && $message)
	{
		$mode = MODE;
		// $mode = 'RC'; // FOR TESTING!!!
		$sms_obj = Get_Server($mode);

		// concatenate company_short to campaign to make sure each company_id gets its own campaign.
		// i think when a messages is sent the modem is actually chosen based on the company_id from
		// the campaign table.  this could potentially be a problem if several companies have the
		// same message.  i haven't verified this.
		$result_array = $sms_obj->Send_SMS($phone_number, $message, $company_short . ':sms_send_message.php', 'NV', '', $company_short);
		
		if ( $result_array['flag'] )
		{
			$response = $result_array['msg'];
		}
		else
		{
			$response = '<span class="resultError">' . $result_array['msg'] . '</span>';
		}
	}


	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? trim($_POST[$var]) : $default)
			: (isset($_GET[$var])  ? trim($_GET[$var])  : $default);
	}


	function printVar( $var, $stripnewlines=true ) {
		ob_start();
		print_r( $var );
		$output = ob_get_contents();
		ob_end_clean();
		if ( $stripnewlines ) $output = preg_replace( '/(\s)+/', ' ', $output );
		return $output;
	}


	function Get_Server($mode)
	{
		switch(strtoupper($mode))
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
		$sms_server = new PRPC_Client($server);
		return $sms_server;
	}
	

?>

<html>
	<head>
		<title>SMS Send Message</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<style type="text/css">
		<!--
			table.entryTable td { background-color: #d9d9d9; }
		
			input, select { }

			select { color: red; }
			
			.head {	font-family: sans-serif; font-size: 1.4em; font-weight: bold; }

			.legend { padding-right: 15px; }

			table.entryTable .entryField {  padding-right: 15px; background-color: #ececed; }

			.result { }

			.resultError { color: red; }
		-->
		</style>
	</head>
	
	<body>
	
		<form method=post>
			<table align="center" border="0" cellpadding="2" cellspacing="1" class="entryTable">
				<tr align="center">
					<td colspan="2" class="head">SMS Send Message</td>
				</tr>
				<tr>
					<td align="left" class="legend">phone number: </td>
					<td class="entryField"><input name="phone_number" type="text" id="phone_number" value="<?= $phone_number ?>" size="12" maxlength="12"></td>
				</tr>
				<tr>
					<td align="left" class="legend">company short: </td>
					<td class="entryField">
						<select name="company_short">
							<?
								foreach (array('PCL','UCL','CA','D1','UFC','IC') as $company)
								{
									$selected = ($company_short == $company) ? 'selected' : '';
									echo "<option value={$company} {$selected}>{$company}\n";
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td align="left" class="legend">message: </td>
					<td class="entryField"><textarea name="message" rows="10" cols="80" onkeyup="this.value = this.value.slice(0, 160)"><?= $message ?></textarea></td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="submit" name="submitButton" value="Send Message"></td>
				</tr>
			</table>
		</form>
		

		<table align="center" border="0" cellpadding="5" cellspacing="5">
			<tr>
				<td class="result"><?= $response ?></td>
			</tr>
		</table>
		
	</body>
	
</html>

