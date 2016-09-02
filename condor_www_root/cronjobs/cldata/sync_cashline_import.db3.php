#!/usr/lib/php5/bin/php
<?php
#######################################
## Global Functions
########################################
function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime() );
   return ((float)$usec + (float)$sec);
}

$timer = array();

function timer_start( $name )
{
	global $timer;
	$timer[strtoupper( $name )]['START'] = microtime_float();
	return;
}

function timer_stop( $name )
{
	global $timer;
	$timer[strtoupper( $name )]['STOP'] = microtime_float();
	$timer[strtoupper( $name )]['ELAPSED'] = $timer[strtoupper( $name )]['STOP'] - $timer[strtoupper( $name )]['START'];
	return;
}

function timer_show( $name )
{
	global $timer;
	return $timer[strtoupper( $name )]['ELAPSED'];
}

// Begin Script Timer
timer_start( 'SCRIPT' );

#######################################
## Library Calls
########################################
require_once("mysql.3.php");

########################################
## Object Creation
########################################
$sql = new MySQL_3();

########################################
## Initializing Variables
########################################
$mysql_host      = 'writer.ecashclk.ept.tss:3308';
$mysql_user      = 'olp';
$mysql_pass      = 'dicr9dJA';
$local_directory = '/virtualhosts/cronjobs/cldata/';
$file            = date("Y-m-d") . ".csv";
$prefix          = "";
//$prefix          = "rc_";

$data            = array(
                    "ca" => array(
                        "file" => $local_directory . "ca/" . $file,
                        "db" => $prefix . "sync_cashline_ca",
                        "table" => "cashline_customer_list",
                        "continue" => TRUE
                        ),
                    );

########################################
## Move our files over
########################################
timer_start( 'GRAB_ALL' );
foreach($data as $property => $values)
{
	if($property == 'ca' && date('w')===0) continue; //Skip Ameriloan on Sunday
	
	timer_start( 'GRAB_' . $property );
    $day_count = 0;
    $file = date("Y-m-d") . ".csv";
    if(!(file_exists($data["$property"]["file"])))
    {
        /*system($local_directory . "csv_grabber $property $file");
        if(!file_exists($data["$property"]["file"]) && date("N")!=1)
        {
        	//Send Email
        	mail("jason.gabriele@sellingsource.com","","Cashline file: " . $data["$property"]["file"] . " does not exist");
        }*/
    }
    else
    {
        $data["$property"]["continue"] = FALSE;
    }

    while(!(file_exists($data["$property"]["file"])))
    {
        $day_count++;
        $file = date("Y-m-d", strtotime("-$day_count day")) . '.csv';
        $data[$property]["file"] = $local_directory . $property . "/" . $file;
        
        if(!(file_exists($local_directory . $property . "/" . $file)))
        {
            /*system($local_directory . "csv_grabber $property $file");
            if(!file_exists($local_directory . $property . "/" . $file) && date("N", strtotime("-$day_count day"))!=1)
            {
            	//Send Email
            	mail("jason.gabriele@sellingsource.com","","Cashline file: " . $local_directory . $property . "/" . $file . " does not exist");
            }*/
        }
        else
        {
//			$data["$property"]["continue"] = FALSE;
//			unset( $data[$property] );
//			echo $property . " will not be run.  We already have the latest file.\n";
//			break;
        }
    }
    timer_stop( 'GRAB_' . $property );
    echo "Time elapsed for [GRAB_" . strtoupper( $property ). "]: " . timer_show( 'GRAB_' . $property ) . " secs.\n";
}
timer_stop( 'GRAB_ALL' );
echo "Time elapsed for [GRAB_ALL]: " . timer_show( 'GRAB_ALL' ) . " secs.\n";

########################################
## Establish Database Connection
########################################
Error_2::Error_Test($sql->Connect(NULL, $mysql_host, $mysql_user, $mysql_pass, Debug_1::Trace_Code(__FILE__, __LINE__)), TRUE);

########################################
## Main
########################################
timer_start( 'MYSQL_ALL' );
foreach($data as $property => $values)
{
	if($property == 'ca' && date('w')===0) continue; //Skip Ameriloan on Sunday
	
	timer_start( 'MYSQL_' . $property );
    // If we don't have a file, let's not touch this one.
    if((strlen($values["file"]) > 0))
    {
        // Open the file for reading.
        if(($ofile = fopen($values["file"], "r")))
        {
        	// Don't delete unless we need to. [BrianF]
//			$query = "DELETE FROM {$values['table']}";
//			$results = Error_2::Error_Test($sql->Query($values["db"], $query));
            
            while(!feof($ofile))
            {
                $record = explode(",", fgets($ofile, 4096));
                $record[0] = str_replace("-", "", $record[0]);
                $record[0] = substr($record[0], 1, 9);
				$record[6] = preg_replace("/[^0-9a-zA-Z_\.-@]/", "", trim($record[6],"\n"));
				
				// Remove hyphens, spaces, and parenthesis from phone numbers
				$record[9] = str_replace(array('-', '(', ')', ' '), '', $record[9]);

				foreach($record as $key => $value)
                {
                    $record[$key] = str_replace(array('"', '\'', '\\'), '', $value);
                }
                
                // Make sure we've got valid data, then push it into the database.
                if(is_numeric($record[0]))
                {
                    $query = "
                        REPLACE INTO
                            `{$values['table']}`
                        SET
                        	social_security_number = '{$record[0]}',
                        	status = '{$record[1]}',
                        	last_payoff_date = '{$record[2]}',
                        	date_customer_added = '{$record[3]}',
                        	current_service_charge_amount = '{$record[4]}',
                        	current_due_date = '{$record[5]}',
                        	email_address = '{$record[6]}',
                        	routing_number = '{$record[7]}',
                        	account_number = '{$record[8]}',
                        	home_phone = '{$record[9]}',
                        	drivers_license_number = '" . mysql_escape_string($record[10]) . "'";
                    
                    $results = Error_2::Error_Test($sql->Query($values["db"], $query));
                }
            }
            fclose($ofile);
        }
        else
        {
            // Couldn't access the file.
            echo "There was a problem opening the file for " . $property . ".\n";
        }
    }
    else
    {
        // Couldn't find the thing.
        echo "Couldn't find a file for " . $property . ".\n";
    }
    
    timer_stop( 'MYSQL_' . $property );
    echo "Time elapsed for [MYSQL_" . strtoupper( $property ). "]: " . timer_show( 'MYSQL_' . $property ) . " secs.\n";
}
timer_stop( 'MYSQL_ALL' );
echo "Time elapsed for [MYSQL_ALL]: " . timer_show( 'MYSQL_ALL' ) . " secs.\n";

// End Script Timer
timer_stop( 'SCRIPT' );
echo "Time elapsed for [SCRIPT]: " . timer_show( 'SCRIPT' ) . " secs.\n";
?>
