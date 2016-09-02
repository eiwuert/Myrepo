<?php

/**
 * Class to handle 'special' tiers that have different weighting, like the CLK/CashNet tier.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class SpecialTier
{
	/**
	 * Instances per-tier
	 *
	 * @var array
	 */
	private static $instances;

	/**
	 * OLP DB Connection 
	 *
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * The available tiers.  The index here will
	 * be the same as the tier_id in the table.
	 *
	 * @var array
	 */
	protected $tiers = array(
		1 => 'preferred',			// Percent-based preferred tier for CLK/CashNet
		2 => 'second_loan_sites',	// Percent-based restrictions on showing second loan offer for certain sites
		3 => 'mpt',					// Force certain companies to get first looks on second loans
		4 => 'preferred_ordered',	// Ordered preferred tier for CLK/CashNet/GRV
		5 => 'mpt_prev',			// Force first looks for second loans with previous apps
		6 => 'mpt_lr',				// Force first looks for second loans during reacts
		7 => 'mpt_offers',			// Percent weighting for second loan offers
		8 => 'mpt_prev_offers',		// Percent weighting for second loan offers
		9 => 'mpt_lr_offers',		// Percent weighting for second loan offers
		10 => 'second_loan_bbx',	// Send second loan through blackbox
	);
	
	/**
	 * The current tier
	 *
	 * @var int
	 */
	protected $tier;
	
	/**
	 * The current target list.
	 *
	 * @var array
	 */
	protected $targets = array();
	
	/**
	 * Returns an instance of this class.
	 *
	 * @param string $tier Name of the tier to retrieve.
	 * @return SpecialTier
	 */
	public static function getInstance($tier)
	{
		if (!isset(self::$instances[$tier]))
		{
			self::$instances[$tier] = new self($tier);
		}
		
		return self::$instances[$tier];
	}
	
	/**
	 * Constructor
	 *
	 * @param string $tier The name of the tier.
	 */
	public function __construct($tier)
	{
		$this->tier = array_search($tier, $this->tiers);
		
		if ($this->tier === FALSE)
		{
			throw new Exception('Invalid special tier requested.');
		}
		
		$this->init();
	}
	
	/**
	 * Initializes all the targets.
	 *
	 * @return NULL
	 */
	protected function init()
	{
		$query =
			"SELECT
				UPPER(property_short) AS property_short,
				weight
			FROM special_tier
			WHERE tier_id = {$this->tier}";
		
		try
		{
			$this->db = DB_Connection::getInstance('blackbox', BFW_MODE);
			$st = $this->db->query($query);
			
			while ($row = $st->fetch())
			{
				$this->targets[$row['property_short']] = $row['weight'];
			}
		}
		catch (Exception $e)
		{
			// @todo Move OLP_Applog_Singleton into olp_lib
			OLP_Applog_Singleton::quickWrite("Could not get target for special tier {$this->tier}");
		}
	}
	
	
	/**
	 * Returns all target data for the tier.
	 *
	 * @return array
	 */
	public function getTargetData()
	{
		return $this->targets;
	}
	
	/**
	 * Returns the property shorts for the tier.
	 *
	 * @return array
	 */
	public function getTargets()
	{
		return array_keys($this->targets);
	}

	/**
	 * Returns whether or not the specified property short
	 * is in the special tier.
	 *
	 * @param string $property_short Property short
	 * @return bool TRUE if it's found.
	 */
	public function isTarget($property_short)
	{
		return isset($this->targets[strtoupper($property_short)]);
	}
	
	/**
	 * Returns the weight for a specified target.
	 *
	 * @param string $property_short Property short for the target.
	 * @return int The weight value
	 */
	public function getWeight($property_short)
	{
		$weight = NULL;
		
		if ($this->isTarget($property_short))
		{
			$weight = $this->targets[strtoupper($property_short)];
		}
		
		return $weight;
	}
	
	/**
	 * Picks a random target based on weight
	 *
	 * @return string Property short of the target
	 */
	public function pickRandom()
	{
		$winner = NULL;

		// Only need to pick a winner if there's something to pick
		if (!empty($this->targets))
		{
			$random = mt_rand(1, 100);
	
			$current_weight = 0;
			foreach ($this->targets as $target => $weight)
			{
				$current_weight += $weight;
				if ($random <= $current_weight)
				{
					$winner = $target;
					break;
				}
			}
		}

		return $winner;
	}
	
	/**
	 * Picks a random target
	 *
	 * @param bool $increment TRUE if it should automatically increment the stat limit when a winner is chosen
	 * 
	 * @return string Property short of the winner
	 */
	public function pickRandomByWeight($increment = TRUE)
	{
		$winner = NULL;
		
		// Only need to pick a winner if there's something to pick
		if (!empty($this->targets))
		{
			$stats_limits = new Stats_Limits($this->db);
			
			$difference = array();
			$targets = $this->targets;
			// If we have less than 100% total for all the targets, we need
			// to add a dummy target to take up the slack
			if (array_sum($targets) < 100)
			{
				$targets['NONE'] = 100 - array_sum($this->targets);
			}
			
			foreach ($targets as $target => $weight)
			{
				$difference[$target] = $stats_limits->count($this->tiers[$this->tier] . '_' . $target);
			}

			$total = array_sum($difference);
			foreach ($difference as $target => $weight)
			{
				if ($total = 0)
				{
					$difference[$target] = 0;
				}
				else
				{
					$difference[$target] = (($difference[$target] / $total) * 100) - $targets[$target];
				}
			}

			// Whoever is the lowest is furthest from their percentage, so return them.
			asort($difference);
			$winner = array_shift(array_keys($difference));
			
			// Increment the stat limit if we want to
			if ($increment)
			{
				$stats_limits->increment($this->tiers[$this->tier] . '_' . $winner);
			}
			
			// Blank out the winner before we send it back.
			if (strcasecmp($winner, 'none') == 0)
			{
				$winner = NULL;
			}
		}
		
		return $winner;
	}
}

?>
