<?php
require_once("/virtualhosts/lib/mysql.3.php");
require_once("/virtualhosts/lib/error.2.php");	

error_reporting(E_ALL - E_NOTICE);

// Connect to the db
$select_date = date("YmdHis", time()-(3600*24*2));
$db = new MySQL_3 (); 
$db->Connect ("BOTH", "selsds001", "sellingsource", 'password');
echo 'connect';

if(!$db) {
	echo "Could not connect to database\n";
	return false;
}

// Pull all data from datran table and populate the 
// datran_company_xref with sent, denied, date sent, and ref_company
$query = "
		SELECT id, datran.sent, datran.denied, datran.date_sent
	 	FROM datran left outer join
	 			datran_company_xref ON
	 					ref_datran = datran.id
	 	WHERE 
	 		ref_datran is null	 
";
$db_hnd = $db->query('oledirect2', $query);

while ($row = $db->fetch_array_row($db_hnd)) {
	$res = $db->query('oledirect2', "	
		INSERT INTO datran_company_xref
			(ref_datran, ref_company, sent, denied, date_sent)
		VALUES
			({$row[id]}, 1, {$row[sent]}, {$row[denied]}, '{$row[date_sent]}')
		"
	);
	echo $row['date_sent'] . "\n";
	echo $row['id'] . "\n";
}
		
# or run
# insert into datran_company_xref (ref_datran, ref_company, sent, denied, date_sent) select id, 1, sent, denied, date_sent from datran;
?>
