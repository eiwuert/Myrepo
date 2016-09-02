<?php
/**
 * Implements storage and retrieval of billing rates in local memory, memcache, and database
 *
 * @author Eric Johney <eric.johney@ericjohney.com>
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class BillingRates
{
	/**
	 * stores reference to singleton instance of self
	 *
	 * @var BillingRates
	 */
	private static $instance;

	/**
	 * OLP database connection
	 *
	 * @var DB_Database_1
	 */
	protected $olp_db;

	/**
	 * stores memcache connection object
	 *
	 * @var Cache_Memcache
	 */
	protected $memcache;

	/**
	 * stores billing rate values in local memory
	 *
	 * @var array
	 */
	protected $billing_rates = array();

	/**
	 * private so can only be instantiated from the getInstance method
	 *
	 * @param DB_Database_1 $db
	 */
	private function __construct(DB_Database_1 $db)
	{
		$this->init($db);
	}

	/**
	 * returns a singleton instance of BillingRates
	 *
	 * @param DB_Database_1 $db
	 * @return BillingRates
	 */
	public function getInstance(DB_Database_1 $db)
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self($db);
		}

		return self::$instance;
	}

	/**
	 * sets database and memcache connections
	 *
	 * @param DB_Database_1 $db
	 * @return void
	 */
	protected function init(DB_Database_1 $db)
	{
		$this->olp_db = $db;
		$this->memcache = Cache_Memcache::getInstance();
	}

	/**
	 * Checks to see if the combination promo_id, property_short, and price exist in the database and memcache
	 * and adds them if they don't
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @param float $new_price
	 * @return void
	 */
	public function saveNewRate($promo_id, $property_short, $new_price)
	{
		$property_short = strtolower($property_short);

		// checks that there is not a value in the database already with this promo_id, property_short and new_price
		if ($this->getRateFromDatabase($promo_id, $property_short) != $new_price)
		{
			$this->saveToDatabase($promo_id, $property_short, $new_price);
		}
		
		if ($this->getRateFromMemcache($promo_id, $property_short) != $new_price)
		{
			$this->saveToMemcache($promo_id, $property_short, $new_price);
		}

		$this->saveToLocal($promo_id, $property_short, $new_price);
	}

	/**
	 * stores a promo_id, property_short, and price in local memory
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @param float $new_price
	 * @return void
	 */
	protected function saveToLocal($promo_id, $property_short, $new_price = NULL)
	{
		if (!isset($this->billing_rates[$property_short]) || !is_array($this->billing_rates[$property_short]))
		{
			$this->billing_rates[$property_short] = array();
		}

		$this->billing_rates[$property_short][$promo_id] = $new_price;
	}

	/**
	 * Returns the appropriate memcache key
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @return string
	 */
	public function getCacheKey($promo_id, $property_short)
	{
		return 'BillingRate/' . strtolower($property_short) . '/' . $promo_id;
	}
	
	/**
	 * stores a promo_id, property_short, and price in memcache
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @param float $new_price
	 * @return void
	 */
	public function saveToMemcache($promo_id, $property_short, $new_price = NULL)
	{
		// Store the record for an hour.
		$this->memcache->set($this->getCacheKey($promo_id, $property_short), $new_price, 3600);
	}

	/**
	 * stores a promo_id, property_short, and price in database
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @param float $new_price
	 * @return void
	 */
	public function saveToDatabase($promo_id, $property_short, $new_price = NULL)
	{
		DB_Util_1::queryPrepared(
			$this->olp_db,
			'INSERT INTO cpanel_pricepoint (promo_id, property_short, price, date_created) VALUES (?, ?, ?, NOW())',
			array($promo_id, $property_short, $new_price)
		);
	}

	/**
	 * User callable function that will return a billing rate for a promo_id and property_short
	 * This function tries local memory first, then memcache data, then database values
	 *
	 * returns FALSE if billing rate not found
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @return float|bool
	 */
	public function getBillingRate($promo_id, $property_short)
	{
		// initialize return value
		$rate = FALSE;

		// try to find billing rate in local memory first
		if (isset($this->billing_rates[$property_short][$promo_id]))
		{
			$rate = $this->billing_rates[$property_short][$promo_id];
		}
		// if billing rate hasn't been found in local memory, try getting it from memcache
		else
		{
			$rate = $this->getRateFromMemcache($promo_id, $property_short);

			// if the billing rate wasn't found in memcache, check the database
			if (!$this->rateIsValid($rate))
			{
				$rate = $this->getRateFromDatabase($promo_id, $property_short);

				// if the billing rate was found in the database, save to memcache and local memory for future use
				if ($this->rateIsValid($rate))
				{
					$this->saveToMemcache($promo_id, $property_short, $rate);
				}
			}
			
			// if the billing rate was found in memcache, store it in local memory for future use
			if ($this->rateIsValid($rate))
			{
				$this->saveToLocal($promo_id, $property_short, $rate);
			}
		}

		return $rate;
	}

	/**
	 * searches memcache for a billing rate with a given promo_id and property_short
	 *
	 * returns FALSE if billing rate not found
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @return mixed
	 */
	protected function getRateFromMemcache($promo_id, $property_short)
	{
		return $this->memcache->get($this->getCacheKey($promo_id, $property_short));
	}

	/**
	 * searches olp database for a billing rate with a given promo_id and property_short
	 *
	 * returns FALSE if billing rate not found
	 *
	 * @param int $promo_id
	 * @param string $property_short
	 * @return float|bool
	 */
	protected function getRateFromDatabase($promo_id, $property_short)
	{
		$rate = FALSE;
		
		try
		{
			$rate = DB_Util_1::querySingleValue(
				$this->olp_db,
				'SELECT price FROM cpanel_pricepoint WHERE promo_id = ? AND property_short = ? ORDER BY date_created DESC LIMIT 1',
				array($promo_id, $property_short)
			);
		}
		catch (Exception $e)
		{
			$rate = FALSE;
		}

		return $rate;
	}
	
	/**
	 * Determines whether a rate is valid
	 * 
	 * @param float $rate
	 * @return bool
	 */
	protected function rateIsValid($rate)
	{
		return ($rate !== FALSE && $rate !== NULL);
	}

	/**
	 * Reload memcache from the database.
	 * 
	 * @return void
	 */
	public function refreshFromDatabase()
	{
		$query = "SELECT cp.promo_id, cp.property_short, cp.price
			FROM cpanel_pricepoint cp
			LEFT JOIN cpanel_pricepoint cp2 ON (
				cp2.promo_id = cp.promo_id
				AND cp2.property_short = cp.property_short
				AND cp2.date_created > cp.date_created
			)
			WHERE cp2.cpanel_pricepoint_id IS NULL";

		$statement = $this->olp_db->query($query);
		
		while ($row = $statement->fetch(PDO::FETCH_OBJ))
		{
			$this->saveToMemcache($row->promo_id, $row->property_short, $row->price);
		}
	}
}
?>
