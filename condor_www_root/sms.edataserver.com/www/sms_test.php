<?


if ($_POST['submit'])
{
	require_once('sms.config.php');
	include_once 'prpc/client.php';
	
	$server = $_SERVER['SERVER_NAME'];
	
	switch(MODE)
	{
		default      :
		case 'LOCAL' : $url = 'prpc://sms.' . LOCAL_NAME . '.tss:8080/sms_prpc.php'; break;
		case 'RC'    : $url = 'prpc://rc.sms.edataserver.com/sms_prpc.php'; break;
		case 'LIVE'  : $url = 'prpc://sms.edataserver.com/sms_prpc.php'; break;
	}

	$sms_obj = new PRPC_Client($url);
						
	$response = $sms_obj->Send_SMS($_POST['phone_number'], 
										$_POST['message'], 
										(isset($_POST['campaign']) && trim($_POST['campaign']) != '') ? trim($_POST['campaign']) : basename(__FILE__),  // reveal where this campaign came from rather than inserting a blank campaign.
										$_POST['state'],
										$_POST['delivery_date'], 
										$_POST['company_short'], 
										$_POST['track_key'], 
										$_POST['space_key'], 
										$_POST['time_zone_chk'],
										$_POST['message_id']);
	
}
?>

<html>
<head>
<title>SMS Test Interface</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
<!--
.head {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
}
.formText {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 9px;
}
-->
</style>
</head>

<body>
<table width="459" border="1" cellpadding="0" cellspacing="1" bordercolor="#CCCCCC">
<form method=post>
  <tr align="center"> 
    <td height="23" colspan="2" class="head"><font size="2">SMS Test</font></td>
  </tr>
  <tr> 
    <td width="132" align="center" class="formText"><font size="2">phone number</font></td>
    <td width="318"><font size="2"> 
      <input name="phone_number" type="text" id="phone_number" value="<?=$_POST['phone_number']?>">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">campaign</font></td>
    <td><font size="2"> 
      <input name="campaign" type="text" id="campaign" value="<?=$_POST['campaign']?>">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">state</font></td>
    <td><font size="2"> 
      <input name="state" type="text" id="state" value="<?=$_POST['state']?>" size="5">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">delivery date</font></td>
    <td><font size="2"> 
      <input name="delivery_date" type="text" id="delivery_date" value="<?=$_POST['delivery_date']?>">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">company short</font></td>
    <td> <font size="2"> 
      <select name="company_short">
<?
	foreach (array('PCL','UCL','CA','D1','UFC','IC') as $company)
	{
		$selected = ($_POST['company_short'] == $company) ? 'selected' : '';
		echo "<option value={$company}  {$selected}>{$company}\n";
	}
?>
      </select>
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">track key</font></td>
    <td><font size="2"> 
      <input name="track_key" type="text" value="<?=$_POST['track_key']?>">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">space key</font></td>
    <td><font size="2"> 
      <input name="space_key" type="text" value="<?=$_POST['space_key']?>">
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">time zone check?</font></td>
    <td><font size="2"> 
      <input name="time_zone_chk" type="checkbox" id="time_zone_chk" value="1" <?=(($_POST['time_zone_chk']) ? 'checked' : '')?>>
      </font></td>
  </tr>
  <tr> 
    <td align="center" class="formText"><font size="2">message id</font></td>
    <td><font size="2"> 
      <input name="message_id" type="text" id="message_id" value="<?=$_POST['message_id']?>">
      </font></td>
  </tr>
   <tr> 
    <td align="center" class="formText"><font size="2">message </font></td>
    <td><font size="2"> 
      <textarea name=message><?=$_POST['message']?></textarea>
      </font></td>
  </tr>
  <tr> 
    <td align="center"><font size="2">&nbsp;</font></td>
    <td><font size="2"> 
      <input type="submit" name="submit" value="Send">
      </font></td>
  </tr>
</form>
</table>
<?
	if ($response)
	{
?>
<table width="458" border="1" cellpadding="0" cellspacing="1" bordercolor="#990000">
  <tr>
    <td width="458" class="formText">Response:<br><pre><?var_dump($response);?></pre>
    </td>
  </tr>
</table>
<?
	}
?>
<pre><?print_r($_POST);?></pre>
</body>
</html>

