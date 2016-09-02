<?php
/*
Template: Contact Us Emailer
Description: Sends emails for the inquire form
*/
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
<?php include(TEMPLATES_PATH.'header.php'); ?>
	<h1>[PAGE_TITLE]</h1>

<?php
   
	if (!isset($_SESSION['email_receipt_sent']) && lum_validEmail($_POST['inq_email']) && lum_verifyFormHash())
	{
	    lum_setString('{NAME}', $_POST['inq_name']);
	    lum_setString('{EMAIL}', $_POST['inq_email']);
	    lum_setString('{PHONE}', $_POST['inq_phone']);
	    lum_setString('{COMMENTS}', $_POST['inq_comments']);

	    ob_start();
	    include(TEMPLATES_PATH.'contact_details.inc.php');
	    $contact_details = ob_get_clean();

	    lum_setString('{BECAUSE}', '[EMAIL_BECAUSE_CONTACT]');
	    lum_setString('{MESSAGE}', '[EMAIL_MESSAGE_CONTACT]'.$contact_details);
	  
	    ob_start();
	    include(TEMPLATES_PATH.'email_template.inc.php');
	    $email = ob_get_clean();
	    $email = lum_replaceStrings($email);
	    $email = lum_replaceStrings($email);
	    

	    $bcc = unserialize(BCC_ORDER_EMAILS);
	    $subject = lum_getString('[EMAIL_SUBJECT_CONTACT]', lum_getCurrentLanguage());
	    $subject = lum_replaceStrings($subject);
	    $_SESSION['email_receipt_sent'] = lum_sendEmail(SITE_EMAIL, SITE_IDENTITY, $_POST['inq_email'], $_POST['inq_name'], $email, $subject, $bcc, true);
	    
	    $ret = lum_call('Requests', 'update', array(
		'request_id'=>'',
		'name'=>$_POST['inq_name'],
		'email'=>$_POST['inq_email'],
		'phone'=>$_POST['inq_phone'],
		'property_id'=>'',
		'arrival_date'=>'',
		'departure_date'=>'',
		'comments'=>$_POST['inq_comments'],
		'new'=>1,
		'whole_id'=>WHOLE_ID
	    ));
	}    


    ?>
    [EMAIL_MESSAGE_CONTACT]
<?php include(TEMPLATES_PATH.'footer.php'); ?>
