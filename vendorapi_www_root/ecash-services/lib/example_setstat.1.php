<?php
	// Site ID
	define ("PROPERTY_NAME", "Pinion");
	define ("SITE_NAME", "mbcash.com");
	define ("PAGE_NAME", "/");

	// Stat to hit
	$promo_id = 10000;
	$promo_sub_code = "";
	$column = 'approved';
	$value = 1;

	// server configuration
	require_once ("/virtualhosts/site_config/server.cfg.php");

	$lib_path = Library_1::Get_Library ("config", 2, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("setstat", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$config = Config_2::Get_Site_Config (PROPERTY_NAME, SITE_NAME, PAGE_NAME, $promo_id);
	$stats = Set_Stat_1::Setup_Stats ($config->site_id, $config->vendor_id, $config->page_id, $config->promo_id, $promo_sub_code, $sql, $config->stat_base, $promo_status);
	Set_Stat_1::Set_Stat ($stats->block_id, $stats->tablename, $sql, $config->stat_base, $column, $value);
	
?>
