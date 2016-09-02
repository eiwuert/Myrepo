<?php

	class Validation_EmailAddress_1 extends Validation_Regex_1
	{
		protected static $email_regex = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';

		public function __construct()
		{
			parent::__construct(self::$email_regex, 'must be a valid e-mail address.');
		}
	}
?>