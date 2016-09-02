<?php

// Setup FTP Object with GE info
$ftp = new stdClass ();
$ftp->server = "ftp.?.com";
$ftp->user_name = "username";
$ftp->user_password = "password";
// email to send confirmation to at GE
$ftp->confirm_email = "david.bryant@thesellingsource.com";
// email for GE to reply to confirmation to
$ftp->confirm_reply_email = "john.hawkins@thesellingsource.com";
// email to send confirmation to if in test mode
$confirm_email = "cd.esp@ge.com";

// Name of file is based on product (given by GE)
// Main $ftp object is from /virtualhosts/cronjobs/www/ge/global.conf.php
//$ftp->file = "/ge_return_ccmem_" . date ("YmdHis") . ".txt,outbound/" . $test_prefix . "SSO_clubenrolls_" . date ("mdY") . ".txt.pgp";
//$ftp->file = "ge_return_ccmem_" . date ("YmdHis") . ".txt,outbound/blah.txt";
$ftp->regex_file = "/^tss(.+)/";

$ftp->file_path = "/virtualhosts/cronjobs/www/ge/return/return_files/";

$ftp->remove_source = false;



// files must be included in this order
//include_once ("/virtualhosts/cronjobs/www/ge/global.conf.php");
include_once ("global.conf.php");
include_once ("/virtualhosts/cronjobs/www/ge/global.setup.php");
include_once ("/virtualhosts/cronjobs/www/ge/return/get_return_file.php");
include_once ("/virtualhosts/cronjobs/includes/pass_args.1.php");
include_once ("/virtualhosts/cronjobs/www/ge/get_args.php");

$return_file = new Get_Return_File;
$return_file->do_Connect ($ftp);
// directory location to retrieve return files from
$return_file->do_Cd ("outbound");
// directory location to put retrieved return files
$return_file->do_Local_Cd ("return_files");
$return_file->process_File ($ftp, $show_success);

?>
