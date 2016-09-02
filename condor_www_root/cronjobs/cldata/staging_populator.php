<?php
require_once("/virtualhosts/lib/mysql.3.php");

$sql = new MySQL_3();

$mysql_host      = "selsds001";
$mysql_user      = "sellingsource";
$mysql_pass      = 'password';
$db              = "sync_cashline_staging";
$file            = "/virtualhosts/cronjobs/cldata/ufc/2005-01-21.csv";
$table           = "sync_ufc";

Error_2::Error_Test($sql->Connect(NULL, $mysql_host, $mysql_user, $mysql_pass, Debug_1::Trace_Code(__FILE__, __LINE__)), TRUE);

if($ofile = fopen($file, "r"))
{
    while(!feof($ofile))
    {
        $record = explode(",", fgets($ofile, 4096));
        $record[0] = str_replace("-", "", $record[0]);
        $record[0] = substr($record[0], 1, 9);

		foreach($record as $key => $value)
        {
            $record[$key] = str_replace('"','', $value);
        }
        
        // Make sure we've got valid data, then push it into the database.
        if(is_numeric($record[0])) // strlen($record[0]) == 11 && 
        {
            $query = "
                REPLACE INTO
                    `" . $table . "`
                VALUES
                (
                    '$record[0]',
                    '$record[1]',
                    '$record[2]',
                    '$record[3]',
                    '$record[4]',
                    '$record[5]'
                );";
            
            $results = Error_2::Error_Test($sql->Query($db, $query));
        }
    }
    fclose($ofile);
}
?>