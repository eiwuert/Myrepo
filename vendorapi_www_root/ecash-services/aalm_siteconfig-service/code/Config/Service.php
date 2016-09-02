<?php

class Config_Service
{
	private $base_dir;

	public function __construct($base_dir)
	{
		$this->base_dir = $base_dir;
	}

	/**
	 * Loads a site config by license key, promo ID, and promo sub code.
	 *
	 * The site config is constructed heirarchically from a default configuration,
	 * a configuration by license key, and a configuration by promo ID, in that order.
	 *
	 * @param $license String Site license key
	 * @param $promo String Promo ID
	 * @param $sub String Promo Sub Code
	 * @return Object
	 */
	public function getConfig($license, $promo_id, $sub)
	{
		$config = new stdClass();

		$def = $this->load('default');
		if ($def !== false) {
			$this->extend($config, $def);
		}

		$license_cfg = $this->load('license/'.$license);
		if ($license_cfg !== false) {
			$this->extend($config, $license_cfg);
		}

		if (isset($config->force_promo_id)) {
			$promo_id = $config->force_promo_id;
			unset($config->force_promo_id);
		} else {
			// ensure all digits
			$promo_id = preg_replace('/[^\d]/', '', $promo_id);
		}

		// check for default promo ID if we're using 10000, which is the historical
		// default and sent from the front-end if no promo ID is specified
		if ((!$promo_id || $promo_id == '10000') && isset($config->default_promo_id)) {
			$promo_id = $site->default_promo_id;
		} else if (!$promo_id) {
			$promo_id = '10000';
		}

		$promo_cfg = $this->load('promo/'.$promo_id);
		if ($promo_cfg !== false) {
			$this->extend($config, $promo_cfg);
		}

		$config->promo_id = $promo_id;
		$config->promo_sub_code = $sub;
		return json_encode($config);
	}

	public function saveConfig($file, $data) {
		$config = new stdClass();

		foreach ( $data as $name => $value ) {
			if($name == 'run_state') {
				$config->run_state = unserialize( $value );
			} else {
				$config->{$name} = $value;
			}
		}

		$this->save($file, $config);
	}

	private function extend($obj1, $obj2) {
		foreach ($obj2 as $name=>$value) {
			$obj1->{$name} = $value;
		}
		return $obj1;
	}

	private function load($value) {
		$path = realpath($this->base_dir.DIRECTORY_SEPARATOR.$value);
		if (!$path || strncmp($this->base_dir, $path, strlen($this->base_dir)) != 0 || !is_readable($path)) {
			return false;
		}

		$config = json_decode(file_get_contents($path));
		if (!$config || !is_object($config)) {
			return false;
		}
		return $config;
	}

	private function save($file, $config) {
		file_put_contents(
			$this->base_dir.DIRECTORY_SEPARATOR.$file,
			json_encode($config)
		);
	}
}
