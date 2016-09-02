<?php

/**
 * Provider for Agean -- they don't include recovered apps as paid
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Agean_ECashProvider extends ECash_HistoryProvider
{
	/**
	 * @param DB_IConnection_1 $db Database connection
	 * @param array $companies
	 * @param bool $expire
	 * @param bool $preact
	 */
	public function __construct(DB_IConnection_1 $db, array $companies, $expire = FALSE, $preact = FALSE)
	{
		unset($this->status_map['*root::external_collections::recovered']);
		parent::__construct($db, $companies, $expire, $preact);
	}
}

?>