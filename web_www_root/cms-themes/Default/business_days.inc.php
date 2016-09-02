<?php
/*
Template: Business Days
Description: Subform for language management
*/

	$days = array(
		array('id'=>'MON', 'text'=>'[cmstext Monday]'),
		array('id'=>'TUE', 'text'=>'[cmstext Tuesday]'),
		array('id'=>'WED', 'text'=>'[cmstext Wenesday]'),
		array('id'=>'THU', 'text'=>'[cmstext Thursday]'),
		array('id'=>'FRI', 'text'=>'[cmstext Friday]')
	);

	echo lum_buildSelectOptions($days, 'id', 'text', strtoupper(isset($_SESSION['select_option']) ? $_SESSION['select_option'] : ''));

?>
