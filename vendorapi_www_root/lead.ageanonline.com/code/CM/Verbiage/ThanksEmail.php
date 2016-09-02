<?php

/**
 * The default verbiage section for an application that has been bought
 * and will receive further instructions in an email.
 * @author Andrew Minerd
 */
class CM_Verbiage_ThanksEmail {
	public static function build(CM_ResponseBuilder $builder, $name_first) {
			$text = <<<HTML
<div align=left>
	<h3 style="font-family: Arial, Helvetica, sans-serif">Welcome ' . $name_first . ' Thank You for your Application!</h3>
	<p style="font-family: Arial, Helvetica, sans-serif">Your information and application have been successfully submitted.</p>
</div>
HTML;
			$builder->createVerbiageSection($text);

			$text = <<<HTML
<div align=left>
	<h3 style="font-family: Arial, Helvetica, sans-serif"><strong>THE FOLLOWING IS EXTREMELY IMPORTANT!</strong></h3>
	<p style="font-family: Arial, Helvetica, sans-serif"><strong>You will receive an e-mail from us momentarily.</strong></p>
	<ul>
	  <li style="font-family: Arial, Helvetica, sans-serif"> PLEASE CHECK YOUR INBOX AND ANY SPAM FOLDERS FOR YOUR CONFIRMATION EMAIL!<br>
	  </li>
	  <li style="font-family: Arial, Helvetica, sans-serif"> You <strong>MUST</strong> follow the directions and confirm your
	  	details provided in your e-mail in order for us to process your loan and get your cash to you!<br>
	  </li>
	  <li style="font-family: Arial, Helvetica, sans-serif">  Due to increasing e-mail restrictions, this email-may
	  	accidentally be marked as spam and sent to your Bulk Mail (Yahoo!), Junk Mail (MSN Hotmail) or Spam (AOL) folder.</li>
	</ul>
</div>
HTML;
		$builder->createVerbiageSection($text);
	}
}