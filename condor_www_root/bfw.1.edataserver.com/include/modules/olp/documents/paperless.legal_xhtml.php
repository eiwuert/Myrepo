<?php
	// ************************************************************
	// FILENAME: paperless.legal_xhtml.php
	// This file creates the XHTML version of the legal verbage.
	// ************************************************************

	// figure out the email address to show
	if( $_SESSION["config"]->property_short == "PCL" )
	{
		$email_address = "customerservice@oneclickcash.com";
	}
	elseif( $_SESSION["config"]->property_short == "UCL" )
	{
		$email_address = "customerservice@unitedcashloans.com";
	}
	elseif( $_SESSION["config"]->property_short == "CA" )
	{
		$email_address = "customerservice@paydaycentral.com";
	}
	elseif( $_SESSION["config"]->property_short == "UFC" )
	{
		$email_address = "customerservice@usfastcash.com";
	}
	elseif( $_SESSION["config"]->property_short == "D1" )
	{
		$email_address = "customerservice@500fastcash.com";
	}
	
	// initialize
	$document = '';

	// DOCUMENT CONTAINER & FORM [OPEN]
	// **********************************
	$document .=
	"\n<div id=\"wf-legal-section\" class=\"legal-section\">".
	"\n<form method=\"post\" action=\""
	.(defined('CLIENT_URL_ROOT') ? CLIENT_URL_ROOT : '')
	."\" onsubmit=\"exit=false;legal_agree.type=button;legal_deny.type=button;\">".
	"\n<input type=\"hidden\" name=\"page\" value=\"".(($hidden_post_page != NULL) ? $hidden_post_page : "legal")."\">";

	/*  commenting out for front end to handle
	// BLOCK: ERROR DISPLAY
	// **********************************
	if (isset($result["errors"]) && $result['errors'])
	{
		$ERROR_TEXT = "";																// Initialize $ERROR_TEXT
		foreach ($result["errors"] as $key => $value)									// Loop through $eds_errors
		{
			$ERROR_TEXT .= "\t\t* ".$value."<br />\n";									// Write $ERROR_TEXT using the available error code
		}
		$document .=
		"\n\n\n<!-- BLOCK: EDS ERRORS [begin] -->".
		"\n<br />\n<div id=\"wf-trunk-errors-container\">".
		"\n<div id=\"wf-trunk-errors-header\">ERRORS</div>".
		"\n<div id=\"wf-trunk-error-body\">\n".$ERROR_TEXT."\n</div>".
		"\n<div id=\"wf-trunk-errors-footer\">To continue, please correct the error(s) below.</div>".
		"\n</div>\n<br />".
		"\n<!-- BLOCK: EDS ERRORS [end]-->\n\n";
	}*/
	
	// BLOCK: LOAN ACCEPTANCE
	// **********************************
	$document .=
	"\n<div class=\"wf-legal-block\">".
	"\n<div class=\"wf-legal-title\">LOAN ACCEPTANCE &amp; eSIGNATURE</div>".
	"\n\n<table border=\"0\" cellspacing=\"15\">".

	// Step 01
	// **********************************
	"\n<tr>".
	// "http://".$_SERVER["SERVER_NAME"]."/imgdir/"
	"\n<td style=\"vertical-align: top;\"><img src=\"".((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_01.gif\" alt=\"Step 1'\" border=\"0\" /></td>".
	"\n<td class=\"wf-legal-copy\">".
	"\n<p>".
	"\nThe terms of your loan are described in the <strong><a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'loan_note_and_disclosure');\">LOAN NOTE AND DISCLOSURE</a></strong> found below. Please review and accept the following related documents.".
	"\n</p>".

	"\n<ul>".

	"\n<li><strong>YES</strong></li>".

	"\n<li>".
	"\n<input type=\"checkbox\" name=\"legal_approve_docs_1\" id=\"legal_approve_docs_1\" value=\"TRUE\" ".(isset($_SESSION["data"]["legal_approve_docs_1"]) && $_SESSION["data"]["legal_approve_docs_1"] ? " checked" : "")."   @@legal_approve_docs_1@@ tabindex=\"1\" />".
	"\n<label for=\"legal_approve_docs_1\"> I have read and accept the terms of the</label> <a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'application');\">Application</a>.".
	"\n</li>".

	"\n<li>".
	"\n<input type=\"checkbox\" name=\"legal_approve_docs_2\" id=\"legal_approve_docs_2\" value=\"TRUE\"  ".(isset($_SESSION["data"]["legal_approve_docs_2"]) && $_SESSION["data"]["legal_approve_docs_2"] ? " checked" : "")."   @@legal_approve_docs_2@@ tabindex=\"2\" />".
	"\n<label for=\"legal_approve_docs_2\"> I have read and accept the terms of the</label> <a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'privacy_policy');\">Privacy Policy</a>.".
	"\n</li>".

	"\n<li>".
	"\n<input type=\"checkbox\" name=\"legal_approve_docs_3\" id=\"legal_approve_docs_3\" value=\"TRUE\"  ".(isset($_SESSION["data"]["legal_approve_docs_3"]) && $_SESSION["data"]["legal_approve_docs_3"] ? " checked" : "")."   @@legal_approve_docs_3@@ tabindex=\"3\" />".
	"\n<label for=\"legal_approve_docs_3\"> I have read and accept the terms of the</label> <a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'auth_agreement');\">Authorization Agreement</a>.".
	"\n</li>".
	"\n<li>".
	"\n<input type=\"checkbox\" name=\"legal_approve_docs_4\" id=\"legal_approve_docs_4\" value=\"TRUE\"  ".(isset($_SESSION["data"]["legal_approve_docs_4"]) && $_SESSION["data"]["legal_approve_docs_4"] ? " checked" : "")."   @@legal_approve_docs_4@@ tabindex=\"4\" />".
	"\n<label for=\"legal_approve_docs_4\"> I have read and accept the terms of the</label> <a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'loan_note_and_disclosure');\">Loan Note and Disclosure</a>.".
	"\n</li>".

	"\n</ul>".
	"\n</td>".
	"\n</tr>".

	// Step 02
	// **********************************
	"\n<tr>".
	// "http://".$_SERVER["SERVER_NAME"]."/imgdir/"
	"\n<td style=\"vertical-align: top;\"><img src=\"".((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_02.gif\" alt=\"Step 2'\" border=\"0\" /></td>".
	"\n<td class=\"wf-legal-copy\">".
	"\n<p>".
	"\n To accept the terms of the <strong><a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'loan_note_and_disclosure');\">LOAN NOTE AND DISCLOSURE</a></strong>, provide your <strong>Electronic Signature</strong> by typing your full name below and click the <strong>\"I AGREE - Send Me My Cash\"</strong> button.".
	"\n</p>".
	"<p><center><b>We are saving your IP Address [".$_SESSION['data']['client_ip_address']."] as a means of tracking this transaction.</b></center></p>".
	"\n</div>".
	"\n<br />".
	"\n\n<table class=\"wf-legal-table\" border=\"0\">".
	"\n<tr>".
	"\n<td class=\"wf-legal-table-cell\"><h3><label for=\"esignature\">eSignature</label></h3></td>".
	"\n<td class=\"wf-legal-table-cell\">".
	"\n<input type=\"text\" name=\"esignature\" id=\"esignature\" style=\"width: 275px;\" maxlength=\"250\" value=\"\" class=\"text sh-form-text-long\" tabindex=\"4\" />".
	"\n</td>".
	"\n</tr>".
	"\n<tr>".
	"\n<td class=\"wf-legal-table-cell\">".
	"\n</td>".
	"\n<td class=\"wf-legal-table-cell\">".
	"\n<div align=\"center\">".
	"\n<small>Type your full name (<label for=\"esignature\">".ucfirst(strtolower($_SESSION["data"]["name_first"]))." ".ucfirst(strtolower($_SESSION["data"]["name_last"]))."</label>) in the box above.</small>".
	"\n<br /><br />".
	"\n<a name=\"approve\"></a><input type=\"submit\" style=\"width: 275px;\" class=\"button\" name=\"legal_agree\" value=\"I AGREE - Send Me My Cash\" tabindex=\"5\" onclick=\"value='Processing...';legal_deny.disabled=true\" />".
	"\n</div>".
	"\n</td>".
	"\n</tr>".
	"\n</table>".
	"\n</td>".
	"\n</tr>".

	// Step 03
	// **********************************
	"\n<tr>".
	// "http://".$_SERVER["SERVER_NAME"]."/imgdir/"
	"\n<td style=\"vertical-align: top;\"><img src=\"".((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_03.gif\" alt=\"Step 3'\" border=\"0\" /></td>".
	"\n<td class=\"wf-legal-copy\">";

    switch($_SESSION["data"]["page"])
    {
        case "ent_confirm_legal":
        case "ent_reapply_legal":
        case "ent_reapply":
            // confirm or reapply trip
            $document .= "\n<p>Your loan is in progress.  At this time, no further action is required on your part in order to process your loan and get your cash to you.".
            "\nTo Monitor the status of your loan, please log in to Customer Service with your username and password.</p>";
            break;
        default:
            if ( isset($_SESSION["ent_list"][$_SESSION["config"]->property_short]) )
            {
            	$email_address = "customerservice@".$_SESSION["ent_list"][$_SESSION["config"]->property_short];
                $document .= "\n<p>In order to complete your loan and get your cash to you, you MUST confirm your loan details.  ".
                "\nYou will receive an email shortly with your Customer Service username and password.  ".
                "\nIf you do not receive an email, please contact us at <a href=\"mailto:$email_address\">$email_address</a> ".
                "\nor call us at " . preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "\\1-\\2-\\3", $_SESSION['config']->support_phone) . "</p>";
            }
            else
            {
            	$document .= "\n<p>".
            	"\nOne of our representatives will then contact you at work or by email to confirm the terms of your loan. If approved, loan proceeds will be deposited into the Bank Account provided in your application.".
            	"\n</p>".
            	"\n<p>".
            	"\n<strong>Note:</strong> In some cases, you may be required to fax in supporting documents.".
            	"\n</p>";
            }
    }

	$document .= "\n</td>".
	"\n</tr>".

	// I DO NOT AGREE
	// **********************************
	"\n<tr>".
	"\n<td style=\"vertical-align: top;\"></td>".
	"\n<td align=\"center\">".
	"\n<hr style=\"color: black; background-color: grey: redwidth: 90%; padding: 0; margin: 15px;\">".
	"\n<input type=\"submit\" style=\"width: 275px;\" class=\"button\" name=\"legal_deny\" value=\"I DO NOT AGREE - Don't Send Any Cash\" tabindex=\"6\" onclick=\"value='Processing...';legal_agree.disabled=true;\"/>".
	"\n</td>".
	"\n</tr>".
	"\n</table>".

	"\n<br /><br />";

	// BLOCK: NOTE AND DISCLOSURE
	// **********************************
	include_once("legal_content.php");


	// DOCUMENT CONTAINER & FORM [CLOSE]
	// **********************************
	$document .= "\n</div>\n</div>\n</div>";
?>
)
