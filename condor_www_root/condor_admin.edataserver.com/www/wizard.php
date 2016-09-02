<html>
<head>
<link rel="stylesheet" href="css/style.css">
<title>Paydate Wizard</title>
</head>
<body class="bg" onload="self.focus();">
<form method="post" action="/" class="no_padding">
<table width="450"><tr><td class="align_left" valign="top" height="310">
<?php
require_once("config.php");



# build querystring
$qs = array();
if (isset($_GET['paydate']))
{
	// Check the data format to work with the widget
	if( isset($_GET['paydate']['biweekly_date']) )
	{
		$temp  = explode( " ", $_GET['paydate']['biweekly_date'] );
		$date  = explode( "-", $temp[0] );
		$stamp = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );

		// Forward the date in the database to either this week or last week
		while( strtotime(date("d-M-Y", $stamp)) < strtotime("-2 weeks") )
		{
			$stamp = strtotime( "+2 weeks", $stamp );
		}

		$_GET['paydate']['biweekly_date'] = date( "m/d/Y", $stamp );
	}
	// Some should be upper case, some lower...
	isset($_GET['paydate']['biweekly_day'])      && $_GET['paydate']['biweekly_day']      = strtoupper($_GET['paydate']['biweekly_day']);
	isset($_GET['paydate']['twicemonthly_type']) && $_GET['paydate']['twicemonthly_type'] = strtolower($_GET['paydate']['twicemonthly_type']);
	isset($_GET['paydate']['monthly_type'])      && $_GET['paydate']['monthly_type']      = strtolower($_GET['paydate']['monthly_type']);

	foreach( $_GET['paydate'] as $k => $v )
	{
		$qs[] = urlencode("paydate[" . $k . "]") . "=" . urlencode($v);
	}
	
}

include(URL_PAYDATE_WIDGET . "?" . join("&", $qs));
        
?>
</td></tr>
<tr><td>
<input type="hidden" name="action" value="save_wizard">
<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
<input type="submit" name="submit" value="Save" class="button">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td></tr></table>
</form>
</body>
</html>
