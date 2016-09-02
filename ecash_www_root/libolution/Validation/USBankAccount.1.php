<?php

/**
 * @package Validation
 */

/**
 * Validate that a value is a valid Bank Account Number fo the United States
 *
 * @author Adam Carnine <adam.carnine@amgsrv.com>
 */
class Validation_USBankAccount_1 extends Validation_RegEx_1
{
	public function __construct($message = 'must be a valid US Bank Account, alphanumeric, dashes and spaces.')
	{
		parent::__construct('/^[ \-0-9a-zA-Z]{3,17}$/', $message);
	}
}
?>
