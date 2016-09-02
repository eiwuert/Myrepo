<?php

/* Cron-able file loader for CG
 * File should be available after 2AM CST
 *
 * [#3395] BBx - Check Giant - Dup Check
 *
 * @author Tym Feindel
 * @desc grabs the bloom file from the remote CG site, copies locally and syncs to olp servers
 */

$path_to_write="/virtualhosts/bloom_files/";
$vh_rel_path="bloom_files"; //path from inside /virtualhosts for the sync-olp call

$url_checkgiant="https://partnerweekly:OcsEgPed4@dupecheck.cashnetusa.com/";

$nowdate=date("Ymd");
$fallback_curl=true;
$seconds_timeout=65;

//set the timeout for the following file_get_contents. Normal ini restored after this script exits
ini_set('default_socket_timeout', $seconds_timeout);

$new_file = file_get_contents($url_checkgiant . $nowdate . ".bloom", "r");

if(($new_file===false) || strlen($new_file)<50000){
	//@todo: write the error properly to log
	//try it with curl?
	if($fallback_curl){
		
		$ch = curl_init();
		$timeout = 65;
		curl_setopt ($ch, CURLOPT_URL, $url_checkgiant . $nowdate . ".bloom");
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$new_file = curl_exec($ch);
		curl_close($ch);

		if(($new_file===false) || strlen($new_file)<50000){
			//@todo: write the error properly to log
			die("Loading Check Giant file failed via both file_get and CURL");
		}
		else {
			die("Loading Check Giant file failed on file_get");
		}
	}
}

$fullpath=$path_to_write . "working.bloom"; //'/virtualhosts/bloom_files/working.bloom'

if(!file_put_contents($fullpath,$new_file,LOCK_EX)){
	//@todo: write the error properly to log
	die("Writing Check Giant file failed");
}

exec("sync-olp $vh_rel_path");

?>