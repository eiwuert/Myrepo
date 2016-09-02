<?php

require_once ("config.php");

// Should we really be doing this? [RayL]
//require_once (LIB_DIR . "popup_check_login.php");
require_once (LIB_DIR . "common_functions.php");

// Connect to the database
$db = ECash_Config::getMasterDbConnection();

$query = "
			SELECT	app.ssn,
					app.application_id,
					app.date_created,
					status.name as status,
					app.name_first, 
					app.name_last,
					app.street,
					app.unit, 
					app.city, 
					app.state
			FROM
				application app,
				application_status status
			WHERE
				app.company_id = " . $db->quote($_REQUEST['cid']) . "
			and app.application_status_id = status.application_status_id
			and app.bank_aba		= " . $db->quote($_REQUEST['bank_aba']) ."
			AND app.bank_account	= " . $db->quote($_REQUEST['bank_account']) . "
			ORDER BY app.ssn, app.application_id
		 ";

$result = $db->query($query);

echo "<html>
<head>
<title>Duplicate Bank Accounts</title>
</head>
<body onload=\"self.focus();\">
	 ";
		
echo "<h3>Transactions with ABA " . 
		$_REQUEST["bank_aba"] . " and Bank Account Number " . $_REQUEST["bank_account"] . 
	 "</h3>
	 <br>";

echo "
      <table width='100%' border='1' cellspacing='0' cellpadding='2' bgcolor='#F7F7F7'>
		<tr bgcolor='#CCCCCC'>
		<th>SSN</th>
		<th>AppID</th>
		<th>Name</th>
		<th>Address</th>
		<th>City</th>
		<th>State</th>
		<th>Date</th>
		<th>Status</th>
		</tr>
	";

$row_count = 0;
$ssn_count = 0;
$ssn_break_prev = "";
while ($row = $result->fetch(PDO::FETCH_ASSOC))
{
	$row_count++;

	$ssn_display = $row['ssn'];
	if ($row['ssn'] != $ssn_break_prev)
	{
		$ssn_count++;
	}
	$ssn_break_prev = $row['ssn'];

	if ($row['date_created'])
	{
 	    $cdate = substr($row['date_created'], 5, 2) . "/" .
		  	     substr($row['date_created'], 8, 2) . "/" .
			     substr($row['date_created'], 0, 4) . " " .
			     substr($row['date_created'],11, 2) . ":" .
			     substr($row['date_created'],14, 2) . ":" .
			     substr($row['date_created'],17, 2);
	}
	else
	{
	    $cdate = "";
	}

	if (strlen($row['unit']) > 0)
	{
		$address_line_1 = $row['street'] . ' #' . $row['unit'];
	}
	else
	{
		$address_line_1 = $row['street'];
	}
	
	echo "<tr>";
	echo "<td>" . $ssn_display							. "</td>";
	echo "<td>" . ucwords(strtolower($row['application_id']))	. "</td>";
  	echo "<td>" . ucwords(strtolower($row['name_first']))		. " " . ucwords(strtolower($row['name_last'])) . "</td>";
	echo "<td>" . ucwords(strtolower($address_line_1))		. "</td>";
	echo "<td>" . ucwords(strtolower($row['city']))			. "</td>";
	echo "<td>" . strtoupper($row['state'])					. "</td>";
	echo "<td>" . $cdate								. "</td>";
	echo "<td>" . $row['status']							. "</td>";
	echo "</tr>\n";
}
echo "
	</table>
	 ";

$summary_text = $row_count	? "Number of transactions with this ABA/Bank Account : &nbsp;&nbsp; <b>$row_count</b>" 
							: "[ No transactions with this ABA/Bank Account were found. ]";
echo "<br>$summary_text\n";
if ($row_count > 0)
{
	$summary_text = "Number of different SSN's associated with this ABA/Bank Account : &nbsp;&nbsp; <b>$ssn_count</b>";
	echo "<br>$summary_text\n";
}

echo "
</body>
</html>
	 ";

?>
