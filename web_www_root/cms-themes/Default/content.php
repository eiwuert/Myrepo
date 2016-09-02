<?php
/*
Template: Content
Description: Content page for the site
*/
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
[cmsinclude Header]
	<div class="wrapper">
		<div class="interior">
			<h1>[PAGE_TITLE]</h1>
			[cmsrichtext Content]
		</div>
	</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
