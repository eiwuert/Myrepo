<?php
	/**
	 * @package TSS
	 */

	/**
	 * A Webadmin 1 site config object; the Libolution equivalent to lib/config.*.php
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class TSS_SiteConfig_1
	{

		/**
		 * Fetchs a site config by license key, promo ID, and sub code.
		 * The database config object should point to an instance containing
		 * the management database.
		 *
		 * @param DB_DatabaseConfig_1 $config
		 * @param string $license_key
		 * @param int $promo_id
		 * @param string $sub_code
		 * @return TSS_SiteConfig
		 * @throws Exception
		 */
		public static function getBy(DB_IDatabaseConfig_1 $config, $license_key, $promo_id, $sub_code)
		{
			$db = $config->getConnection();

			// unlike a missing promo_id, a missing license key is a no-show
			if (($license_info = self::getLicense($db, $license_key)) === FALSE)
			{
				throw new Exception('Invalid license key');
			}

			// respect promo ID forceation
			if ($license_info['force_promo_id'])
			{
				$promo_id = $license_info['force_promo_id'];
			}

			$promo_info = self::getPromo($db, $promo_id);

			// a promo is considered "valid" if it's being used on the correct page
			$valid = ($promo_info && ($promo_info['page_id'] == $license_info['page_id']));

			$config = new self();
			$config->promo_id = $promo_id;
			$config->promo_sub_code = $sub_code;
			$config->vendor_id = $promo_info['vendor_id'];
			$config->promo_status = ($valid ? 'valid' : 'invalid');
			$config->exit_strategy = NULL;

			// we don't want to merge this crap in
			$r = $license_info['run_state'];
			unset($license_info['run_state']);

			// promo takes precedence over site (license)
			$config->mergeInto($license_info);
			$config->mergeInto((array)$r);

			if ($promo_info)
			{
				$config->cost_action = $promo_info['cost_action'];
				$config->mergeInto((array)$promo_info['run_state']);
			}

			return $config;
		}

		/**
		 * Fetchs license information
		 *
		 * @param DB_Database_1 $db
		 * @param string $license_key
		 * @return array
		 */
		protected static function getLicense($db, $license_key)
		{
			$query = "
				SELECT
					license_map.license,
					license_map.mode,
					license_map.page_id,
					license_map.site_id,
					license_map.property_id,
					license_map.page_name,
					license_map.site_name,
					license_map.property_name,
					license_map.force_promo_id,
					license_map.run_state,
					license_map.site_category,
					property_map.qualify,
					property_map.legal_entity,
					property_map.property_short
				FROM
					license_map
					LEFT JOIN property_map ON (license_map.property_id = property_map.property_id)
				WHERE
					license = ?
			";
			$license = DB_Util_1::querySingleRow($db, $query, array($license_key));

			if ($license)
			{
				$license['run_state'] = unserialize($license['run_state']);
			}

			return $license;
		}

		/**
		 * Fetches promo information
		 *
		 * @param DB_Database_1 $db
		 * @param int $promo_id
		 * @return string
		 */
		protected static function getPromo($db, $promo_id)
		{
			$query = "
				SELECT
					promo_id,
					run_state,
					cost_action
				FROM promo_data_map
				WHERE promo_id = ?
			";
			$promo = DB_Util_1::querySingleRow($db, $query, array($promo_id));

			if ($promo)
			{
				$promo['run_state'] = unserialize($promo['run_state']);
			}

			return $promo;
		}

		// standard values -- the rest
		// will be stored in $this->extra
		public $license;
		public $mode;
		public $page_id;
		public $site_id;
		public $property_id;
		public $page_name;
		public $site_name;
		public $property_name;
		public $stat_server;
		public $stat_base;
		public $site_server;
		public $site_base;
		public $site_category;
		public $force_promo_id;
		public $legal_entity;
		public $property_short;
		public $promo_id;
		public $promo_sub_code;
		public $promo_status;
		public $cost_action;
		public $exit_strategy;
		public $event_pixel = array();

		protected $extra = array();

		/**
		 * Return the pixels for a given event
		 *
		 * @param string $event
		 * @return array
		 */
		public function getPixelsForEvent($event)
		{
			if (isset($this->event_pixel[$event])
				&& is_array($this->event_pixel[$event]))
			{
				$pixels = array();

				foreach($this->event_pixel[$event] as $pixel)
				{
					if (!isset($pixel['sub_code'])
						|| ($pixel['sub_code'] == $this->promo_sub_code))
					{
						$pixels[] = $pixel['tracking_pixel'];
					}
				}

				return $pixels;
			}

			return array();
		}

		/**
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name)
		{
			return $this->extra[$name];
		}

		/**
		 * @param string $name
		 * @return bool
		 */
		public function __isset($name)
		{
			return isset($this->extra[$name]);
		}

		/**
		 * @param string $name
		 * @param mixed $value
		 */
		protected function __set($name, $value)
		{
			$this->extra[$name] = $value;
		}

		/**
		 * @param string $name
		 */
		protected function __unset($name)
		{
			unset($this->extra[$name]);
		}

		/**
		 * Merges an array of data into this object
		 *
		 * @param array $data
		 */
		protected function mergeInto(array $data)
		{
			foreach ($data as $key=>$value)
			{
				$this->{$key} = $value;
			}
		}
	}

?>
