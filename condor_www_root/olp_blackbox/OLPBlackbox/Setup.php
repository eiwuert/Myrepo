<?php

/**
 * Does setup for OLPBlackbox to make sure the proper classes are actually available.
 * 
 * See comment for doSetup()
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Setup
{
	/**
	 * Set up various defines and includes for OLPBlackbox
	 *
	 * OLPBlackbox interfaces with some legacy classes out of bfw.1.edataserver.com, 
	 * so we define this static setup class to make sure all the individual "require_once"
	 * stuff that got pulled out for proper unit testing is included on live sites.
	 * olp should use this file prior to utilizing blackbox.
	 * 
	 * @return void
	 */
	public static function doSetup()
	{
		// include things required by OLPBlackbox
		
		// required by OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount
		// TODO: When this rule is changed to do an API call, remove these.
		require_once(ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php');
		require_once('qualify.2.php');
		require_once(OUT_PHP_DIR . 'OLP_Qualify_2.php');
		require_once('business_rules.class.php');
	}
}

?>
