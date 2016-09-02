<?php
	// ************************************************************
	// FILENAME: paperless.legal_xhtml.php
	// This file creates the XHTML version of the legal verbage.
	// ************************************************************
	
	// initialize
	$document = '';

	// DOCUMENT CONTAINER & FORM [OPEN]
	// **********************************
	$document .=
	"\n<div id=\"wf-legal-section\" class=\"legal-section\">".
	"\n<form method=\"post\" action=\""
	.(defined('CLIENT_URL_ROOT') ? CLIENT_URL_ROOT : '')
	."\" onsubmit=\"exit=false;legal_agree.type=button;legal_deny.type=button;\">".
	"\n<input type=\"hidden\" name=\"page\" value=\"esig\">".
	"\n<input type=\"hidden\" name=\"legal_agree\" value=\"confirm\">";
	
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
	"\n<td style=\"vertical-align: top;\"><img src=\"" . ((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_01.gif\" alt=\"Step 1'\" border=\"0\" /></td>".
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
	"\n<td style=\"vertical-align: top;\"><img src=\"" . ((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_02.gif\" alt=\"Step 2'\" border=\"0\" /></td>".
	"\n<td class=\"wf-legal-copy\">".
	"\n<p>".
	"\n To accept the terms of the <strong><a href=\"javascript:void(0)\" onclick=\"pop_newsite('?page=preview_docs&unique_id=@@unique_id@@', 'loan_note_and_disclosure');\">LOAN NOTE AND DISCLOSURE</a></strong>, provide your <strong>Electronic Signature</strong> by typing your full name below and click the <strong>\"I AGREE - Send Me My Cash\"</strong> button.".
	"\n</p>".
	"<p><center><b>We are saving your IP Address [@@ip_address@@] as a means of tracking this transaction.</b></center></p>".
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
	"\n<a name=\"approve\"></a><input type=\"submit\" style=\"width: 275px;\" class=\"button\" name=\"b_legal_agree\" value=\"I AGREE - Send Me My Cash\" tabindex=\"5\" onclick=\"value='Processing...';legal_deny.disabled=true\" />".
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
	"\n<td style=\"vertical-align: top;\"><img src=\"" . ((isset($FE_CONF->site["SECURE_SITE"]) && $FE_CONF->site["SECURE_SITE"] && $_SERVER['HTTPS']) ? "https://" : "http://") .$_SERVER["SERVER_NAME"]."/imgdir/live/media/image/esig_step_03.gif\" alt=\"Step 3'\" border=\"0\" /></td>".
	"\n<td class=\"wf-legal-copy\">";
	
		$email_address = "customerservice@".$this->ent_prop_list[$this->config->property_short]['site_name'];
    $document .= "\n<p>In order to complete your loan and get your cash to you, you MUST confirm your loan details.  ".
    "\nYou will receive an email shortly with your Customer Service username and password.  ".
    "\nIf you do not receive an email, please contact us at <a href=\"mailto:$email_address\">$email_address</a> ".
    "\nor call us at " . $this->ent_prop_list[$this->config->property_short]['phone'] . "</p>";
    
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
	$document .= "@@esig_doc@@";
	
	// DOCUMENT CONTAINER & FORM [CLOSE]
	// **********************************
	$document .= "\n</div>\n</div>\n</div>";
?>
