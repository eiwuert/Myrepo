<?php
/*
Template: Newsletter Form
Description: The contact us template
*/
	$error = null;

	if (isset($_POST['email']) && lum_validEmail($_POST['email']) && lum_verifyFormHash('NEWSLETTER1'))
	{
		$_POST['new'] = 1;
		$_POST['verify_key'] = md5(uniqid(rand(), true));
		$_POST['request_id'] = '';
		list($msg, $ret) = lum_call('Newsletter', 'update', $_POST);
		
		if ($ret == WEB_SERVICE_ERROR)
		{
			$error = $msg;
			if (strpos(strtolower($error), 'duplicate') !== false)
			{
				$error = "You are already signed up for our newsletter";
			}
		}
		if (!$error)
		{
			// send a confirmation if it's a valid email

			if (!isset($_SESSION['newsletter_verify_sent']))
			{
			    lum_setString('{BECAUSE}', '[EMAIL_NEWSLETTER_BECAUSE]');
			    lum_setString('{MESSAGE}', sprintf(lum_getString('[EMAIL_NEWSLETTER_REPLY]', lum_getCurrentLanguage()), '<a href="'.REAL_BASE_URL.'verify-email?'.$_POST['verify_key'].'">'.REAL_BASE_URL.'verify-email?'.$_POST['verify_key'].'</a>'));
			  
			    ob_start();
			    include(TEMPLATES_PATH.'email_template.inc.php');
			    $email = ob_get_clean();
			    $email = lum_replaceStrings($email);
			    $email = lum_replaceStrings($email);
			    
			    $bcc = unserialize(BCC_ORDER_EMAILS);
			    $subject = lum_getString('[NEWSLETTER_SUBJECT]', lum_getCurrentLanguage());
			    $subject = lum_prepareInputForEmail(lum_replaceStrings($subject));
			    $_SESSION['newsletter_verify_sent'] = lum_sendEmail(SITE_EMAIL, SITE_IDENTITY, $_POST['email'], '', $email, $subject, $bcc, true);
			}
			lum_redirect('/thank-you-newsletter');
		}
	}
	else
	{
		unset($_SESSION['newsletter_verify_sent']);
	}
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
<?php include(TEMPLATES_PATH.'header.php'); ?>
	<h1>[PAGE_TITLE]</h1>
	<?php if ($error) : ?>
		<p class="error"><?php echo $error;?></p>
	<?php endif; ?>
	[cmsrichtext Content]
    <form id="inquire_form" action="/<?=lum_getCurrentLanguage()?>/about-us/newsletter" method="post">
	 <?=lum_getFormHash()?>
        [MY_EMAIL]: <input type="text" name="email" id="Email" class="validate[custom[email]] text-input"/>  <input type="submit" value="[SUBMIT]" class="lum-green_button lum-sleek-button"/>
    </form>
<?php include(TEMPLATES_PATH.'footer.php'); ?>

