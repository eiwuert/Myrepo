<?php
/*
Template: Customer Logout
Description: Page that submits the customer logout request
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php');
	killSession();
	lum_redirect("/");
?>
