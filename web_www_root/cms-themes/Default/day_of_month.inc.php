<?php
	$days = array(
		      array('id'=>"1" ,'text'=>"1st"),
		      array('id'=>"2" ,'text'=>"2nd"),
		      array('id'=>"3" ,'text'=>"3rd"),
		      array('id'=>"4" ,'text'=>"4th"),
		      array('id'=>"5" ,'text'=>"5th"),
		      array('id'=>"6" ,'text'=>"6th"),
		      array('id'=>"7" ,'text'=>"7th"),
		      array('id'=>"8" ,'text'=>"8th"),
		      array('id'=>"9" ,'text'=>"9th"),
		      array('id'=>"10" ,'text'=>"10th"),
		      array('id'=>"11" ,'text'=>"11th"),
		      array('id'=>"12" ,'text'=>"12th"),
		      array('id'=>"13" ,'text'=>"13th"),
		      array('id'=>"14" ,'text'=>"14th"),
		      array('id'=>"15" ,'text'=>"15th"),
		      array('id'=>"16" ,'text'=>"16th"),
		      array('id'=>"17" ,'text'=>"17th"),
		      array('id'=>"18" ,'text'=>"18th"),
		      array('id'=>"19" ,'text'=>"19th"),
		      array('id'=>"20" ,'text'=>"20th"),
		      array('id'=>"21" ,'text'=>"21st"),
		      array('id'=>"22" ,'text'=>"22nd"),
		      array('id'=>"23" ,'text'=>"23rd"),
		      array('id'=>"24" ,'text'=>"24th"),
		      array('id'=>"25" ,'text'=>"25th"),
		      array('id'=>"26" ,'text'=>"26th"),
		      array('id'=>"27" ,'text'=>"27th"),
		      array('id'=>"28" ,'text'=>"28th"),
		      array('id'=>"29" ,'text'=>"29th"),
		      array('id'=>"30" ,'text'=>"30th"),
		      array('id'=>"31" ,'text'=>"31st"));

	echo lum_buildSelectOptions($days, 'id', 'text', (isset($_POST[$select_option]) ? $_POST[$select_option] : ''));

?>