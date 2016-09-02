<?php

require_once 'Customer.php';

interface ECash_Models_ICustomerFriend
{
	public function setCustomerData(ECash_Models_Customer $customer);
}

?>