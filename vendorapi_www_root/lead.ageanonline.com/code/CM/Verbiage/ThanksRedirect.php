<?php

/**
 * The default verbiage section for an application that has been bought
 * and must redirect to finish the loan.
 * @author Andrew Minerd
 */
class CM_Verbiage_ThanksRedirect {
	public static function build(CM_ResponseBuilder $builder, $redirect_url) {
		$text = <<<PAGE
	<br/>
	<p>Thank you for your application. You have been pre-approved with one of
	our lending partners. Please click <b><a href="{$redirect_url}">here</a></b>
	to complete your application.</p>
PAGE;
		$builder->createVerbiageSection($text);
	}
}