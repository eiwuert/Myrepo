<?php

/**
 * Provider for Agean -- they don't include recovered apps as paid
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_ECashProvider extends OLPBlackbox_Enterprise_Generic_ECashProvider
{
	/**
	 * @param array $companies
	 * @param bool $expire
	 * @param bool $preact
	 */
	public function __construct(array $companies, $expire = FALSE, $preact = FALSE)
	{
		unset($this->status_map['*root::external_collections::recovered']);
		parent::__construct($companies, $expire, $preact);
	}
}

?>