#!/usr/bin/php
<?php

/**
* cp_template
* A CLI script to copy templates from one company to another.
*   
* @author Josef Norgan
* @copyright Copyright 2006 The Selling Source, Inc.
*
* Note: This could be handled with a single query, but this provides a more verbose method.
* (eg. "INSERT INTO template SELECT NULL, name, <to> ... WHERE company_id='<from>'")
*/
    
$errors = "";

if(in_array("-d",$argv))
{
	$db_pos = array_search("-d",$argv) + 1;
	$db = $argv[$db_pos];
    $db_admin = $db . "_admin";
}else{
	$db = "condor";
    $db_admin = "condor_admin";
}
if(in_array("-h",$argv))
{
	$host_pos = array_search("-h",$argv) + 1;
	$host = $argv[$host_pos];
}else{
	$errors .= "No Host Specified.\n";
}
if(in_array("-P",$argv))
{
	$port_pos = array_search("-P",$argv) + 1;
	$port = $argv[$port_pos];
}else{
	$port = "3306";
}
if(in_array("-u",$argv))
{
	$user_pos = array_search("-u",$argv) + 1;
	$user = $argv[$user_pos];
}else{
	$errors  .= "No Username Specified.\n";
}
if(in_array("-p",$argv))
{
        $pass_pos = array_search("-p",$argv) + 1;
	$pass = $argv[$pass_pos];
}else{
        $erros .= "No Password Specified";
}
if(in_array("-f",$argv))
{
        $from_pos = array_search("-f",$argv) + 1;
        $from = $argv[$from_pos];
}else{
        $errors .= "No 'From' ID Specified.\n";
}
if(in_array("-t",$argv))
{
        $to_pos = array_search("-t",$argv) + 1;
        $to = $argv[$to_pos];
}else{
        $errors .= "No 'To' ID Specified.\n";
}

			

if(!$errors)	//Check for command line arguments
{
	if(!$mysql = mysql_connect($host.":".$port,$user,$pass)){die("Could Not Connect To Host ($host)\n");}
	    $db = mysql_select_db($db);
	    $query = "SELECT * FROM `template` WHERE `company_id`='".$from."' AND `status`='ACTIVE'"; 
	    //Grab active templates for from_id
	    $result = mysql_query($query);
	    
        //Copying Templates one at a time
        while($temp = mysql_fetch_array($result))
	    {
		    // Create and execute insert query
		    $ins_query = "
		    INSERT INTO `template` 
		    (
    		`name`,
    		`company_id`,
    		`user_id`,
    		`date_created`,
    		`date_modified`,
    		`subject`,
    		`data`,
    		`content_type`,
    		`status`,
    		`type`
    		)
    		Values
    		(
    		'{$temp['name']}',
    		'{$to}',
    		'{$temp['user_id']}',
    		'{$temp['date_created']}',
    		'{$temp['date_modified']}',
    		'{$temp['subject']}',
    		'{$temp['data']}',
    		'{$temp['content_type']}',
    		'{$temp['status']}',
    		'{$temp['type']}'
    		)"; 
    		
            echo "Copying Template ".$temp['template_id']."...\n";	
    	
            if($rs = mysql_query($ins_query)){
    			$temp_id = mysql_insert_id();
    			echo "Copied to ". $temp_id. ".\n";
	    	}else{
		    	echo "Failed To Copy!\n";
    		}
	        
            //Copying Template Attachments
            $attachment_query = "INSERT INTO `template_attachment` 
	    	SELECT NULL,
    		'{$temp_id}',
    		`date_created`,
    		`content_type`,
    		`type`,
    		`attachment_id` 
    		from `template_attachment` WHERE `template_id`='{$temp['template_id']}'";
    		
            echo "Copying Template Attachments...\n";
    		
            if($rs = mysql_query($attachment_query)){
	            echo "Copied.\n";
	        }else{
                echo "Failed To Copy!\n";
	        }
	}
    
    //Copying Tokens
    $db2 = mysql_select_db($db_admin);
    $token_query = "INSERT INTO `tokens`
    SELECT `token`,
    `date_created`,
    '{$to}',
    `description`
    from `tokens` WHERE `company_id`='{$from}'";
    echo "\n\nCopying Tokens...\n";
    if($rs = mysql_query($token_query)){
    echo "Copied.\n";
    }else{
    echo "Failed To Copy!\n";
    }
	
    mysql_close($mysql);
}
else
{
    echo "\nInvalid Syntax: \n" . $errors . "\n";
	echo "Correct Syntax:\n $argv[0] -h <host> -P <port> -u <username> -p <password> -f <from company id> -t <to company id>\n\n";
}
?>

