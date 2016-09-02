<?php
/*
Template: (Include) Confirmation Header
Description: The header for the web site
*/
?>
	<input id="user_language" name="user_language" type="hidden" value="<?php getCurrentLanguage();?>"/>
	<div id="header">
		<div class="wrapper clearfix">
			<div class="lang">
				<?php include(TEMPLATES_PATH.'lang.inc.php');?>
			</div>
			<a href="/<?=getCurrentLanguage();?>" class="logo"><img src="<?php lum_getThemeImageUrl();?>/logo.png" alt="MultiLoan Source" /></a>
		</div>
	</div>