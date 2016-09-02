<?php
	require_once('../virtualhosts/verihub.com/serenity/live/code/cingular.php');
	
	$ds = disk_free_space(".");

	$gb = $ds /1024 /1024 /1024;

	echo $gb;

	if ($gb < 5.00)
{
		cingular("serenity diskspace low. (" . number_format($gb, 2) . " GB FREE)");
        mail("7024234683@vtext.com", "SERENITY_ERROR", "Serenity disk space is low (" . number_format($gb, 2) . " GB free) (" . date("Y-m-d H:i:s") . ")", "From: Administrator@serenity.verihub.com");
}


?>
