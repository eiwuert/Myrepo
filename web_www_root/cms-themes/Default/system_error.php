<?php
/*
Template: System Error
Description: Error page with headers, and cleared history;
*/
?>
<?php header_remove(); ?>
<?php if(!isset($_SESSION)) session_start(); ?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
[cmsinclude Header]
	<div class="wrapper">
		<div class="interior">
			<h1>[PAGE_TITLE]</h1>
			[cmsrichtext Content]
			<p style="text-align: center;"><?= $_SESSION['error_code'];?></p>
		</div>
	</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
