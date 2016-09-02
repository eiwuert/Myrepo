<?php

if (USE_STAT_LIBOLUTION)
{
	include_once 'stats.2.php';
}
else
{
	include_once 'stats.1b.php';
}

/** A collection of OLPStats. Specially used to hit stats with different space
 * keys.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPStats_Spaces
{
	static protected $instance; /**< @var OLPStats_Spaces */
	
	/**
	 * Grab an instance with the correct space information setup
	 *
	 * @param string $mode The current BFW Mode (LIVE, RC, LOCAL)
	 * @param string $target_id The target's ID
	 * @param string $blackbox_mode The mode blackbox is called via
	 * @param int $page_id Site type (Page ID in statpro)
	 * @param int $promo_id Promo ID
	 * @param string $promo_sub_code The promo subcode
	 * @return OLPStats
	 */
	public static function getInstance($mode, $target_id, $blackbox_mode, $page_id, $promo_id, $promo_sub_code)
	{
		if (!self::$instance) self::$instance = new self($mode);
		
		if (STAT_SYSTEM_2)
		{
			self::$instance->setSpace($mode, $target_id, $blackbox_mode, $page_id, $promo_id, $promo_sub_code);
			return self::$instance->stats;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * @var OLPStats
	 */
	protected $stats;

	/**
	 * @var array
	 */
	protected $space_cache = array();

	/**
	 * @param string $mode
	 */
	protected function __construct($mode)
	{
		$this->stats = new OLPStats($mode);
		$this->stats->setProperty(-889275714);
		$this->stats->setGlobal($_SESSION['statpro']['global_key']);
		$this->stats->setTrack($_SESSION['statpro']['track_key']);
	}

	/**
	 * Returns an instance of OLPStats setup with the correct space key
	 *
	 * @param string $mode The current BFW Mode (LIVE, RC, LOCAL)
	 * @param string $target_id The target's ID
	 * @param string $blackbox_mode The mode blackbox is called via
	 * @param int $page_id Site type (Page ID in statpro)
	 * @param int $promo_id Promo ID
	 * @param string $promo_sub_code The promo subcode
	 * @return OLPStats
	 */
	protected function setSpace($mode, $target_id, $blackbox_mode, $page_id, $promo_id, $promo_sub_code)
	{
		$hash = $this->hash($target_id, $blackbox_mode, $page_id, $promo_id, $promo_sub_code);
		
		if (!isset($this->space_cache[$hash]))
		{
			$space = $this->stats->getStatPro()->getSpaceKey(
					array(
						'target_id' => $target_id,
						'bb_mode' => $blackbox_mode,
						'page_id' => $page_id,
						'promo_id' => $promo_id,
						'promo_sub_code' => $promo_sub_code,
					)
			);
			
			$this->space_cache[$hash] = $space;
		}
		
		$this->stats->setSpace($this->space_cache[$hash]);
	}
	
	/** Create a unique hash for this space, so we don't need to loop to find the correct space.
	 *
	 * @param string $target_id The target's ID
	 * @param string $blackbox_mode The mode blackbox is called via
	 * @param int $page_id Site type (Page ID in statpro)
	 * @param int $promo_id Promo ID
	 * @param string $promo_sub_code The promo subcode
	 * @return string
	 */
	protected function hash($target_id, $blackbox_mode, $page_id, $promo_id, $promo_sub_code)
	{
		return md5($target_id . ':' . $blackbox_mode . ':' . $page_id . ':' . $promo_id . ':' . $promo_sub_code);
	}
}

?>
