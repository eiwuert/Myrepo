<?php
	
	include('../config.php');
	
	// classes that autoload won't resolve properly
	require(BASE_DIR.'/include/mysql.php');
	require(BASE_DIR.'/include/display.jpgraph.php');
	require(BASE_DIR.'/include/display.jpgraph.bar_group.php');

	
	// remember that all relative start times are interpreted relative to the end
	// time, which is interpreted relative to the current time -- if we have an end
	// time of "tomorrow 12:00:00AM" and a start time of "today 12:00:00AM", they're
	// actually reference the _SAME_ time, since "today" is interpreted in the context
	// of "tomorrow"
	$report = new Interval_Report('Sent Documents', '2006-04-28', '2006-04-29', '1 hour', 'gA');
	
	$range = $report->Default_Range();
	$range2 = $report->Default_Range();
	
	$source = Load_Source('sample');
	$range->Source($source);
	$report->Add_Source($range, 'Email');
	
	$source2 = Load_Source('sample2');
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
	$display->Width(800);
	$display->Height(300);
	$display->Colors($colors);
	$display->Scale_Type('textint');
	$display->Use_Gradient();
	$display->Render($report);
	
?>