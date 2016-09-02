<?php

	define('CACHE_DIR','/data/graph_cache/');
	if($_GET['date'])
	{
		$start_timestamp = strtotime($_GET['date']);
		$start_date = date('Y-m-d', $start_timestamp);
		$end_date = date('Y-m-d', strtotime('+1 day', $start_timestamp));
		$company_id = intval($_GET['company_id']);
	}
	else
	{
		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime('tomorrow'));
	}
	if(isset($_GET['no_display']) && $_GET['no_display'])
	{
		$no_display = TRUE;
	}
	else 
	{
		$no_display = FALSE;
	}
	if(isset($_GET['no_cache']) && $_GET['no_cache'])
	{
		$caching = FALSE;
	}
	else 
	{
		$caching = TRUE;
	}
	$file_name = CACHE_DIR."sent_{$start_date}_{$end_date}_{$company_id}.png";
	clearstatcache();
	if(file_exists($file_name) && $caching === TRUE)
	{
		if(date('Y-m-d') == $start_date)
		{
			if(filemtime($file_name) > (time() - 1800))
			{
				if($no_display === FALSE)
				{
					header('Content-Type: image/png');
					readfile($file_name);
				}
				exit;
			}
			else 
			{
				unlink($file_name);
			}
		}
		else 
		{
			if($no_display === FALSE)
			{
				header('Content-Type: image/png');
				readfile($file_name);
			}
			exit;
		}
	}
	include('../report_framework/config.php');
	
	// classes that autoload won't resolve properly
	require(BASE_DIR.'/include/mysql.php');
	require(BASE_DIR.'/include/display.jpgraph.php');
	require(BASE_DIR.'/include/display.jpgraph.bar_group.php');

	
	// remember that all relative start times are interpreted relative to the end
	// time, which is interpreted relative to the current time -- if we have an end
	// time of "tomorrow 12:00:00AM" and a start time of "today 12:00:00AM", they're
	// actually reference the _SAME_ time, since "today" is interpreted in the context
	// of "tomorrow"
	$report = new Interval_Report('Sent Documents', $start_date, $end_date, '1 hour', 'gA');
	
	$range = $report->Default_Range();
	$range2 = $report->Default_Range();
	
	$source = Load_Source('email_sent');
	$range->Source($source);
	$report->Add_Source($range, 'Email');
	
	$source2 = Load_Source('fax_sent');
	$range2->Source($source2);
	$report->Add_Source($range2, 'Fax');
	
	// again, start times are interpreted relative to the end time, so this
	// actually starts at -7 days from last Sunday, not from now
//	$range2 = new Average_Interval_Range('-7 days', 'last Sunday', '1 day', '1 hour', $source);
//	$range2->Precision(0);
//	
//	$report->Add_Source($range, 'Today');
//	$report->Add_Source($range2, 'Last Week, Average');
	$report->Prepare(time());
	
	$colors = array(
		'red', '#9bbeff', '#77bb11'
	);
	
	$display = new Display_JPGraph_Bar_Group();
	$display->Display_Generate_Date('Y-m-d H:i:s');
	$display->Width(800);
	$display->Height(300);
	$display->Colors($colors);
	$display->Scale_Type('textint');
	$display->Use_Gradient();
	$display->Render($report,$file_name);
	if($no_display === FALSE)
	{
		header('Content-Type: image/png');
		readfile($file_name);
	}
	
?>
