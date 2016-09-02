<?php
#######################################
## Library Calls
########################################
require_once("/virtualhosts/lib/db2.1.php");

########################################
## Initializing Variables
########################################
$local_directory = "/virtualhosts/cronjobs/cldata/";
$property        = $argv[1];
$counter         = 0;
$file            = date("Y-m-d") . ".csv";
$day_count       = 0;

########################################
## Object Creation
########################################
$db2 = new Db2_1("OLP", "web_" . $property, $property . "_web");

########################################
## Establish Database Connection
########################################
Error_2::Error_Test ($db2->Connect(), TRUE);

########################################
## Prepare Database for Updates
########################################
$prepare = $db2->Autocommit(FALSE);
Error_2::Error_Test($prepare, FALSE);

$update_query = "
    UPDATE
        cashline_customer_list
    SET
        date_created = CURRENT TIMESTAMP,
		status = ?,
        last_payoff_date = ?,
        date_customer_added = ?,
        current_service_charge_amount = ?,
        current_due_date = ?
    WHERE
        social_security_number = ?
    ";

$select_query = "SELECT count(*) as row_count FROM cashline_customer_list WHERE social_security_number = ? for read only";
$insert_query = "INSERT INTO cashline_customer_list VALUES (CURRENT TIMESTAMP, ?, ?, ?, ?, ?, ?)";

$update = $db2->Query($update_query);
$select = $db2->Query($select_query);
$insert = $db2->Query($insert_query);
Error_2::Error_Test($update_query, TRUE);
Error_2::Error_Test($select_query, TRUE);
Error_2::Error_Test($insert_query, TRUE);
########################################
## Checking Filename
########################################
while(!(file_exists($local_directory . $property . "/" . $file)))
{
    $day_count++;
    $file = date("Y-m-d", (time() - (86400 * $day_count))) . ".csv";
}

########################################
## Main
########################################
if($ofile = fopen($local_directory . $property . "/" . $file, "r"))
{
    while(!feof($ofile))
    {
        $counter++;

        $record = explode(",", trim(fgets($ofile, 4096)));
        $record[0] = str_replace("-", "", $record[0]);
        $record[0] = substr($record[0], 1, 9);

        foreach($record as $key => $value)
        {
			$record[$key] = str_replace('"','', $value);
		}

        $result = $update->Execute($record[1], $record[2], $record[3], $record[4], $record[5], $record[0]);
                   
        $result = $select->Execute($record[0]);
        Error_2::Error_Test($result, FALSE);
        
        $result = $select->Fetch_Array();
        
		if(!($result['ROW_COUNT']))
        {
			$result = $insert->Execute($record[0], $record[1], $record[2], $record[3], $record[4], $record[5]);
            Error_2::Error_Test($result, FALSE);
        }

        if($counter == 1000)
        {
	        $db2->Commit();
            $counter = 0;
        }
    }
}
fclose($ofile);
$db2->Commit();
?>
