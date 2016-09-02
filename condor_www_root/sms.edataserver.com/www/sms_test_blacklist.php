<?

include_once 'sms.config.php';
include_once 'prpc/client.php';

$server = "prpc://sms.ds57.tss:8080/sms_prpc.php";

$mode = MODE;

$mode_radio = isset($_POST['mode_radio']) ? trim($_POST['mode_radio']) : $mode;
$mode_radio_LOCAL = $mode_radio == 'LOCAL' ? ' CHECKED ' : '' ;
$mode_radio_RC    = $mode_radio == 'RC'    ? ' CHECKED ' : '' ;
$mode_radio_LIVE  = $mode_radio == 'LIVE'  ? ' CHECKED ' : '' ;

$mode = $mode_radio == '' ? $mode : $mode_radio;

switch($mode)
{
	default:
	case 'LOCAL':
		preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $_SERVER['SERVER_NAME'], $matched);
		$local_name = isset($matched[1]) ? $matched[1] : 'ds57';
		$server = "prpc://sms.$local_name.tss:8080/sms_prpc.php";
		break;
	
	case 'RC':
		$server = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
		break;

	case 'LIVE':
		$server = 'prpc://sms.edataserver.com/sms_prpc.php';
		break;
}
		
$sms_obj = new PRPC_Client($server);
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$response = '';

if ( isset($_POST['submitCheck']) )
{
	$response = $sms_obj->Check_Blacklist($_POST['phone_number']);
}
else if ( isset($_POST['submitUpdate']) )
{
	$response = $sms_obj->Add_To_Blacklist($_POST['phone_number']);
}
else if ( isset($_POST['submitRemove']) )
{
	$response = $sms_obj->Remove_From_Blacklist($_POST['phone_number']);
}
?>

<html>
<head>
<title>SMS Blacklist Test</title>
<style type="text/css">
<!--
td { background-color: #dddddd; }

.head {
	font-family: sans-serif;
	font-weight: bold;
	font-size: 1.6em;
}
-->
</style>
</head>

<body>
<form method=post>
<table align="center" border="0" cellpadding="5" cellspacing="2" bordercolor="#CCCCCC">

  <tr><td align="center" colspan="2"><span class="head">SMS Blacklist Test</span><br>server: <?= $server ?></td></tr>

  <tr>
    <td align="center" colspan="2">phone number:&nbsp; <input name="phone_number" type="text" value="<?= $phone_number ?>" ></td>
  </tr>
  
  <tr>
    <td align="center" colspan="2">
      <input type="radio" name="mode_radio" value="LOCAL" <?= $mode_radio_LOCAL ?> >Local
      <input type="radio" name="mode_radio" value="RC" <?= $mode_radio_RC ?> >RC
      <input type="radio" name="mode_radio" value="LIVE" <?= $mode_radio_LIVE ?> >LIVE
    </td>
  </tr>
  

  <tr>
    <td align="center" colspan="2">
      <input type="submit" name="submitCheck" value="Check">
      <input type="submit" name="submitUpdate" value="Add">
      <input type="submit" name="submitRemove" value="Remove">
    </td>
  </tr>
  
</table>
</form>

<table align="center" border="1" cellpadding="5" cellspacing="0" bordercolor="#990000">
  <tr>
    <td>Response:<br><pre><? var_dump($response); ?></pre></td>
  </tr>
  <tr>
    <td>Request Fields:<br><pre><? print_r($_POST); ?></pre></td>
  </tr>
</table>

</body>
</html>

