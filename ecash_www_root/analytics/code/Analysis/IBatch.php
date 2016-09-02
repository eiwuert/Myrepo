<?php

/**
 * Provides a common interface for analysis processing functionality.
 */
interface Analysis_IBatch
{
	/**
	 * @param string $company_name_short
	 * @param DB_IConnection_1 $ecash_db
	 * @param Analysis $a
	 * @param DB_IConnection_1 $legacy_db
	 */
	public function __construct($company_name_short, DB_IConnection_1 $ecash_db, Analysis $a, DB_IConnection_1 $legacy_db=NULL);

	/**
	 * @param NULL|integer $effective_date
	 */
	public function execute($effective_date=NULL);

	/**
	 * @param string $mode
	 * @return DB_IDatabaseConfig_1
	 */
	public static function getAnalysisDb($mode);

	/**
	 * @param string $mode
	 * @param string $company
	 * @return DB_IDatabaseConfig_1
	 */
	public static function getECashDb($company, $mode);
}

?>
