<?php

require_once '/virtualhosts/lib/mysql.3.php';
require_once '/virtualhosts/lib/session.4.php';

// Setup Database Object
$db = new stdClass ();
$db->sql_host = SQL_HOST;
$db->sql_user = SQL_USER;
$db->sql_pass = SQL_PASS;
$db->db_insert = false;

$sql = new MySQL_3 ();
$result = $sql->Connect (NULL, SQL_HOST, SQL_USER, SQL_PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

// Setup FTP Object with GE info
$ftp = new stdClass ();
$ftp->server = 'ftp.gefanet.com';
$ftp->server = 'ftp.gepmg.com';
$ftp->user_name = 'ssourpmg';
$ftp->user_password = 'password';
$ftp->file = TMP_FILE;

// email to send confirmation to for live and test
$ftp->confirm_email = 'david.bryant@thesellingsource.com';
$ftp->confirm_email_cc = 'david.bryant@thesellingsource.com';

// email to send confirmation to for live
$confirm_email = 'cd.esp@ge.com';
$confirm_email_cc = 'jerry.lamparski@ge.com';

// email for GE to reply to confirmation to
$ftp->confirm_reply_email = 'john.hawkins@thesellingsource.com';
$ftp->confirm_reply_email = 'david.bryant@thesellingsource.com';

// debug stuff
$confirm_email = '';
$confirm_email_cc = '';
?>
