#!/usr/bin/php

<?php

$per_day = 300;
$days = 1;

require_once("/virtualhosts/lib/mysql.3.php");
require_once("/virtualhosts/lib/error.2.php");

$sql = new MySQL_3();

// Basic database configuration.
$host   = "localhost";
$user   = "root";
$pass   = "sellingsource";
$nmsdb  = "scrubber";
// This needs to include all of the databases we're pulling from.
$dblist = array("olp_pcl_visitor","olp_ucl_visitor");

Error_2::Error_Test ($sql->Connect(NULL, $host, $user, $pass, Debug_1::Trace_Code(__FILE__, __LINE__)), TRUE);

// Scroll through the databases and make an array of all new entries.
foreach($dblist as $db)
{
    $query = "SELECT
                $db.personal.application_id,
                $db.personal.first_name,
                $db.personal.last_name,
                $db.personal.email,
                $db.application.application_id,
                $db.application.status
              FROM
                $db.personal,$db.application
              LEFT JOIN $nmsdb.nms_funded ON $db.personal.email = $nmsdb.nms_funded.email
              WHERE
                $db.application.application_id = $db.personal.application_id
                    AND
                $db.application.status = 'APPROVED'
                    AND
                $nmsdb.nms_funded.email IS NULL";

    $results = $sql->Query($db, $query);
    Error_2::Error_Test($results, TRUE);
    
    while($row = $sql->Fetch_Array_Row($results))
    {
        $indata[strtoupper($row["email"])] = $row;
    }
}

// Populate scrubber.
if($indata)
{
    foreach($indata as $array)
    {
        $query = "INSERT INTO nms_funded
                    (name_first,name_last,email)
                VALUES (
                    '".mysql_escape_string($array["first_name"])."',
                    '".mysql_escape_string($array["last_name"])."',
                    '".mysql_escape_string($array["email"])."')";
        $results = $sql->Query($nmsdb, $query);
        Error_2::Error_Test($results, TRUE);
    }
}

?>