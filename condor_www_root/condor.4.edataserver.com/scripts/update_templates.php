#!/usr/bin/php
<?php

/**
* update_templates.php
* A CLI script to perform a string replace on templates in the condor database.
*
* @author Josef Norgan
* @copyright Copyright 2006 The Selling Source, Inc.
*
*/

//Condor specific script, so just manually set these values and allow users to select DB from command line

define ("DB_HOST", 'db101.clkonline.com');
//define ("DB_NAME", 'condor_admin_demo');
define ("DB_USER", 'condor');
define ("DB_PASS", 'andean');
define ("DB_PORT", '3313');


if($argv[1] && $argv[2])
{
if(!$argv[3])
{                                   // Make sure unspecified second string is empty
$argv[3] = '';
}
$dbname = $argv[1];
$search = $argv[2];
$replace = $argv[3];

	$total = 0;
	$mysql = mysql_connect(DB_HOST.":".DB_PORT,DB_USER,DB_PASS);
	$db = mysql_select_db($dbname);
	$query = "SELECT * FROM template";
	$result = mysql_query($query);
    
	while ($x = mysql_fetch_array($result))
	{
		$data = $x['data'];
		$count = substr_count($data,$search);
		$total += $count;
	    
        if ($new_data = str_replace($search, $replace, $data))
		{
		echo $count ." occurences of " . $search . " found in template " . $x['template_id'] . ".\n";
			
            if ($count >0)
            {
	            echo "Successful replacement\n";
			    // Do Update
			    $update_query = "Update template set `data`='". $new_data . "' WHERE `template_id`='".$x['template_id']."'";
			
				if ($upd_result = mysql_query($update_query))
                {
				echo "Successful Update\n";
				}
                else
                {
				echo "Failed to Update.\n";
				}
			}
	    }
		else
		{
			echo "failed to replace.\n";
		}
	}
	echo $total . " total replacements.\n";
	mysql_close($mysql);
}
else
{
	echo "\r\nSyntax incorrect. \n\n". $argv[0] . " <dbname> <search string> <replace string>\n\n";
}

?>

