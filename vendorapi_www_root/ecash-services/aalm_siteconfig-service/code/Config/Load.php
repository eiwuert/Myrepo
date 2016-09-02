<?php
define('BASE_DIR', realpath(dirname(__FILE__).'/../../') . '/');

class Config_Load
{
	private $config;
	private $db;
	private $property_name = 'SomeLoanCompany';
	private $property_info;
	
	public function __construct()
	{
		$this->loadConfig();
		$this->connectDb();
		$this->loadProperty();
	}

	public function copyConfigs()
	{
		$base_dir = $this->config['config_dir'];
		if ($base_dir[0] != '/') {
			$base_dir = BASE_DIR . $base_dir;
		}
		$site_service = new Config_Service($base_dir);
			
		$licenses = $this->getLicenses();
		foreach ( $licenses as $license )
		{
			$site_service->saveConfig("license/{$license['license']}", $license);
		}
		
		$promos = $this->getPromos();
		foreach ( $promos as $promo )
		{
			$site_service->saveConfig("promo/{$promo['promo_id']}", $promo);
		}
	}

	private function loadConfig()
	{
		$this->config = parse_ini_file( BASE_DIR . 'config/config.ini');
		$override_file = BASE_DIR . 'config/override.ini';
		if (file_exists($override_file))
		{
			$override = parse_ini_file($override_file);
			$this->config = array_merge($config, $override);
		}
	}

	private function connectDb()
	{
		$dsn = "mysql:host={$this->config['management_host']};dbname=management";
		$this->db = new PDO( $dsn, $this->config['management_user'], $this->config['management_pass'] );		
	}

	private function loadProperty()
	{
		$query = "select * from name_id_map n where property_name = ?";
		$st = $this->db->prepare($query);
		$st->execute(array($this->property_name));
		$result = $st->fetchAll(PDO::FETCH_ASSOC);
		if ( !count( $result ) )
			throw new Exception( "Could not find Property {$this->property_name}" );

		$this->property_info = current($result);
	}

	private function getLicenses()
	{
		$query = "select * from license_map n where property_id = ?";
		$st = $this->db->prepare($query);
		$st->execute(array($this->property_info['property_id']));
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}
	
	private function getPromos()
	{
		$query = "select * from promo_data_map n where property_id = ?";
		$st = $this->db->prepare($query);
		$st->execute(array($this->property_info['property_id']));
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}

}


require 'AutoLoad.1.php';

AutoLoad_1::addSearchPath(BASE_DIR.'/code/');

$load = new Config_Load();
$load->copyConfigs();