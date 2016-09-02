<?php
	/**
	 * @package Stats.StatPro
	 *
	 */

	/**
	 * Class to hit stats locally.
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Stats_StatPro_Client_1 extends Stats_StatPro_ClientBase_1
	{
		/**
		 * @var TSS_IKeyGenerator
		 */
		private $track_key_generator;
		
		/**
		 * @param string $statpro_key
		 * @param string $auth_user
		 * @param string $auth_password
		 * @param string $statpro_base_dir
		 */
		public function __construct($statpro_key, $auth_user, $auth_password, $statpro_base_dir = self::STATPRO_BASE_DIR)
		{
			parent::__construct($statpro_key, $auth_user, $auth_password, $statpro_base_dir);
			$this->track_key_generator = new Stats_StatPro_TrackKeyGenerator();
		}
		
		/**
		 * Records a statpro event
		 *
		 * @param string $track_key
		 * @param string $space_key
		 * @param string $event_type_key
		 * @param int $date_occurred
		 * @param int $event_amount
		 */
		public function recordEvent($track_key, $space_key, $event_type_key, $date_occurred = NULL, $event_amount = NULL)
		{
			// Prevent track keys such as '3x2vTiFti6adzhic2Nf8dIaLV0dâ=ADBRITE'
			foreach(array('track_key', 'space_key') as $k)
			{
		//		if(! preg_match('/^[a-z0-9,-]{27}$/i', $$k))
		//		{
		//			throw new Exception("Invalid {$k} ({$$k})");
		//		}
			}

			if ($date_occurred === NULL)
			{
				$date_occurred = time();
			}

			$this->insert(
				self::ROW_RECORD_EVENT,
				array(
					$track_key,
					$space_key,
					strtolower($event_type_key),
					$date_occurred,
					$event_amount
				)
			);
		}

		/**
		 * Creates a track key
		 *
		 * @return string
		 */
		public function createTrackKey()
		{
			return $this->track_key_generator->generate();
		}

		/**
		 * Generates and records a space key for the given definition
		 *
		 * @param array $space_definition
		 * @param int $action_date
		 * @return string
		 */
		public function getSpaceKey(array $space_definition, $action_date = NULL)
		{
			ksort($space_definition);
			$space_values = '';
			foreach ($space_definition as $item)
			{
				$space_values .= trim($item).'|';
			}
			$space_key = $this->hash($space_values);

			$this->insert(
				self::ROW_CREATE_SPACE,
				array($space_key, serialize($space_definition)),
				$action_date
			);

			return $space_key;
		}

	}

?>
