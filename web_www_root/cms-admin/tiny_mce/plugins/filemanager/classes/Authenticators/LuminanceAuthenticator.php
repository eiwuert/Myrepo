<?php
/**
 * $Id: LuminanceAuthenticator.php 10 2007-05-27 10:55:12Z spocke $
 *
 * @package LuminanceAuthenticator
 * @author Moxiecode
 * @copyright Copyright © 2007, Moxiecode Systems AB, All rights reserved.
 */

/**
 * This class handles MCImageManager LuminanceAuthenticator stuff.
 *
 * @package LuminanceAuthenticator
 */
class Moxiecode_LuminanceAuthenticator extends Moxiecode_ManagerPlugin {
	/**#@+
	 * @access public
	 */

	/**
	 * ..
	 */
	function Moxiecode_LuminanceAuthenticator() {
	}

	/**
	 * ..
	 */
	function onAuthenticate(&$man) {
		return lum_call('Users', 'isSignedIn');
	}
}

// Add plugin to MCManager
$man->registerPlugin("LuminanceAuthenticator", new Moxiecode_LuminanceAuthenticator());
?>