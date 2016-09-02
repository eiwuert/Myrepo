<?php

$cli_args = new Pass_Args ();
$cli_args->Setup_Arg_Switch ("-live");
$cli_args->Setup_Arg_Switch ("-ftp");
$cli_args->Setup_Arg_Switch ("-db");
$cli_args->Setup_Arg_Switch ("-confirm");
$cli_args->Setup_Arg_Switch ("-notify_admin");
$cli_args->Setup_Arg_Switch ("-v");
$passed_args = $cli_args->Get_Args ($argv);

// display generic argument errors
if (count ($cli_args->error_array) > 0)
{
	print_r ($cli_args->error_array);
}

// set default vars
$test_mode = true;
$test_prefix = "tst_";
$show_success = false;
$ftp->action = false;
$ftp->confirm = false;
$ftp->notify_admin = false;

foreach ($passed_args as $args)
{
	switch ($args->arg)
	{
		case "-live":
			$test_mode = false;
			$test_prefix = "";
			$ftp->confirm_email		= $confirm_email   .','.$ftp->confirm_email;
			$ftp->confirm_email_cc	= $confirm_email_cc.','.$ftp->confirm_email_cc;
			break;
		case "-ftp":
			$ftp->action = true;
			break;
		case "-db":
			$db->db_insert = true;
			break;
		case "-confirm":
			$ftp->confirm = true;
			break;
		case "-notify_admin":
			$ftp->notify_admin = true;
			break;
		case "-v";
			$show_success = true;
			break;
		default:
			break;
	}
}

?>
