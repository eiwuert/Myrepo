<?php
    /**
     * Cronjob that spawns processes for each outgoing condor mail account 
     * so that they can send emails
     *
     * Randy Klepetko 2012/07/12  Added logging to system
     */
    define('CONDOR_DIR',realpath(dirname(__FILE__).'/../').'/');
    require_once('mysqli.1.php');
    require_once(CONDOR_DIR.'lib/config.php');
    define('EXECUTION_MODE',MODE_LIVE);
    require_once(CONDOR_DIR.'lib/condor_exception.php');
    define('LOCK_FILE','/tmp/send_mail.lock');
    define('PHP_BIN','/usr/bin/php');
    
    require_once 'Console/Getopt.php';
    require_once(CONDOR_DIR.'lib/logging.php');
    
    //Parse command line options
    $args = Console_Getopt::readPHPArgv(); 
    $short_opts = "l::";
    $long_opts = array('log');
    $options = Console_Getopt::getOpt($args,$short_opts,$long_opts); 
    
    // Check the options are valid
    if(PEAR::isError($options)) {
       echo $options->getMessage()."\n";
       exit(1);
    }
    
    // Gather the options into an array
    $parsed_opts = array();
    foreach($options[0] as $opt) {
        $name = substr(trim($opt[0],"-"),0,1);
        if(is_null($opt[1])) {
            $parsed_opts[$name] = true;
        } else {
            $parsed_opts[$name] = $opt[1];
        }
    }
    
    // check for log mode
    $LOG_MODE = false;
    if($parsed_opts['l']) {
        $LOG_MODE = true;
        $my_pid = getmypid();
        
        // Logging class initialization
        $log = new Logging();
        // set path and name of log file (optional)
        $log->lfile('/send_mails/send_mails_2_'.date('Y-m-d_H-i-s-T').'_'.$my_pid.'.log');
        
        $log->lwrite("Log File send_mails_2_".date('Y-m-d_H-i-s-T').'_'.$my_pid." Started");
    }

    function Lock() {
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Touching ".LOCK_FILE." Lock File");
        touch(LOCK_FILE);
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - register_shutdown_function('UnLock')");
        register_shutdown_function('UnLock');
    }
    
    function isLocked() {
        return file_exists(LOCK_FILE);
    }
    function UnLock() {
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Unlock ".LOCK_FILE." Lock File?");
        if(isLocked()) {
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Unlocking\n");
            unlink(LOCK_FILE);
        } else {
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Already unlocked\n");
        }
    }
    
    function start_process($account_id) {
        $cmd = PHP_BIN.' '.CONDOR_DIR.'scripts/send_mail_for_account.php '.EXECUTION_MODE." $account_id > /dev/null &";
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Execute command (".$cmd.")\n");
        shell_exec($cmd);
        echo $cmd;
        return true;
    }
    function is_running($account_id) {
        $exec = 'ps axo pid,command | grep -E "send_mail_for_account.php '.EXECUTION_MODE.' '.$account_id.'" | grep -v grep | wc -l';
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Check if sending mail (send_mail_for_account.php) is running for account (".$account_id.")");
        $cnt = `$exec`;
        if($cnt > 0) {
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - still running\n");
            return true;
        } 
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - its done\n");
        return false;
    }
    function Get_Status_Ids($db = NULL) {
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite("  Getting dispatch status ids");
        if(!$db instanceof MySQLi_1) {
            $db = MySQL_Pool::Connect('condor_'.EXECUTION_MODE);
        }
        $query = '
            SELECT
                dispatch_status_id
            FROM
                dispatch_status
            WHERE
                type NOT IN (\'FAIL\',\'SENT\')
        ';
        $return = array();
        try  {
            $res = $db->Query($query);
            while($row = $res->Fetch_Object_Row()) {
                $return[] = $row->dispatch_status_id;
            }
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite("  - found ". count($return)." dispatch status ids.");
            
            if ($GLOBALS["LOG_MODE"]) {
                $str = "  - ".implode(',',$return)." \n";
                $GLOBALS["log"]->lwrite($str);
            }
        }
        catch (Exception $e) {
            
        }
        return $return;
    }
    
    function Gather_Account_Ids($account_ids) {
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Gathering account ids");
        if ($GLOBALS["LOG_MODE"]) {
            $str = " - Not these ->".implode(',',$account_ids)." \n";
            $GLOBALS["log"]->lwrite($str);
        }
        $return = array();
        $date = date('YmdHis',(time() - 86400));
        $db = MySQL_Pool::Connect('condor_'.EXECUTION_MODE);
        $status_ids = Get_Status_Ids($db);
        if(count($status_ids) > 0) {
            $query = "
                SELECT
                    DISTINCT(account_id)
                FROM
                    mail_queue
                WHERE
                    date_modified >= '$date'
                AND
                    status_id IN (".join(',',$status_ids).")
            ";
            if(count($account_ids) > 0) {
                $query .= "	AND	account_id NOT IN (".join(',',$account_ids).")";
            }
        
            try  {
                $res = $db->Query($query);
                while($row = $res->Fetch_Object_Row())
                {
                    $return[] = $row->account_id;
                }
            }
            catch (Exception $e) {
                
            }
        }
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Returned ".count($return)." accounts.");
        if ($GLOBALS["LOG_MODE"]) {
            $str = " - ".implode(',',$return)." \n";
            $GLOBALS["log"]->lwrite($str);
        }
        return $return;
    }
    
    if(!isLocked()) {
        try {
            Lock();
            ini_set('max_execution_time',0);
            ini_set('memory_limit',"512M");
            
            $procs = array( );
            $pipes = array();
            //really just wait until they're all gone before exiting out.
            while(1) {
                //This will find all the accounts that are NOT running
                //and have mail to send
                $accounts = Gather_Account_Ids(array_keys($procs));
                //Loop through those accounts and start their process
                foreach($accounts as $account_id) {
                    if(!isset($procs[$account_id]) || !is_running($procs[$account_id]))
                    {
                        $procs[$account_id] = start_process($account_id);
                    }
                }
                if(count($procs) > 0) {
                    //Loop through every running process 
                    //and check the status
                    foreach($procs as $account=>$proc)
                    {
                        //$status = proc_get_status($proc);
                        //It finished so lets unset it
                        if(!is_running($account))
                        {
                            unset($procs[$account]);
                        }
                        
                    }
                }
                //Incase they've all finished by this point
                if(count($procs) < 1) {
                    if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" No more processes running - BYE.\n");
                    //We have NO processes so we're done.
                    break;
                }
                
                //If we still have processes, sleep a bit before we
                //check again.
                sleep(12);
            }
            UnLock();
        }
        catch (Exception $e) {	
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Exception crash".$e->getMessage);
            $c = new CondorException($e->getMessage,CondorException::ERROR_EMAIL);
        }
    //echo 'blah';
    } else {
    //echo 'huh';
        //If it's locked for a long time ,update it
        if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" Update the lock file?");
        if(time() - filemtime(LOCK_FILE) > 14400)
        {
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Lock file ".LOCK_FILE."(Mode: ".EXECUTION_MODE.") for send mail has been in place for 4 hours.");
            $x = new CondorException("Lock file ".LOCK_FILE."(Mode: ".EXECUTION_MODE.") for send mail has been in place for 4 hours.",CondorException::ERROR_EMAIL);
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Refreshing lock file.\n");
            touch(LOCK_FILE);
        } else {
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Lock file ".LOCK_FILE."(Mode: ".EXECUTION_MODE.") for send mail is not that old (<4hrs).");
            if ($GLOBALS["LOG_MODE"]) $GLOBALS["log"]->lwrite(" - Do nothing.\n");
        }
    }
