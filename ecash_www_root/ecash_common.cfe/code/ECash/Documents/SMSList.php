<?php

class SMSList {

	protected static $MESSAGES = array();
	protected $config;
	
	public function __construct() {
		$this->config = ECash::getConfig();
		
		self::$MESSAGES['ACH_RETURN'] =
			'Unfortunately, your agreed %%%COMPANY_NAME%%% payment failed. Please contact us ASAP at %%%COMPANY_PHONE_SHORT%%% or %%%COMPANY_SUPPORT_EMAIL%%%. Thanks.';
		self::$MESSAGES['REACT_OFFER'] =
			'%%%COMPANY_NAME%%%\'s Previous Customer Reward pre-approved loan! More $$$ AND less interest! Call %%%COMPANY_PHONE_SHORT%%%! Reply OPTOUT to stop msgs';
		self::$MESSAGES['LEAD_ACCEPTED'] =
			'%%%COMPANY_NAME%%% has received your application. Please call to confirm now at %%%COMPANY_PHONE_SHORT%%%. Reply OPTOUT to stop msgs.';
		self::$MESSAGES['UNSIGNED_APP_REQUEST'] =
			'%%%COMPANY_NAME%%% accepted your payday loan. Please confirm the amount online. Call %%%COMPANY_PHONE_SHORT%%% for details. Reply OPTOUT to stop msgs.';
	}
			
	public function getMessage($key) {
		if ( isset( self::$MESSAGES[$key] ) ) {
			if ( strpos( self::$MESSAGES[$key], '%%%' ) !== FALSE )
				return preg_replace_callback("/%%%(.*?)%%%/", array($this, 'replaceTokens'), self::$MESSAGES[$key]);
			return self::$MESSAGES[$key];
		}

		return '';
	}

	protected function replaceTokens($matches) {
		$var = $matches[1];

		if( isset($this->config->{$var}) )
			return $this->config->{$var};
		return '';
	}

	
}