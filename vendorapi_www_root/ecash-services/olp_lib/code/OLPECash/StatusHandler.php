<?php

/**
 * Handles non-application-specific OLP-eCash status interactions.
 * 
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 *
 */
class OLPECash_StatusHandler
{
	/**
	 * LDB connection
	 *
	 * @var DB_Database_1
	 */
	protected $ldb;
	
	/**
	 * Holds all the eCash statuses
	 *
	 * @var array
	 */
	protected $status_map = array();
	
	/**
	 * Holds a status map by nested levels with an application_status_id
	 * at the end of each chain, like so:
	 * 
	 * 	Array
	 * 	(
	 *   	[applicant] => Array
	 *       (
	 *           [underwriting] => Array
	 *            (
	 *                [dequeued] => 11
	 *                [queued] => 10
	 *                [preact] => 141
	 *                [follow_up] => 14
	 *            )
	 *
	 *           [verification] => Array
	 *            (
	 *                [dequeued] => 8
	 *                [queued] => 9
	 *                [follow_up] => 13
	 *            )
	 *
	 *           [denied] => 18
	 *           [duplicate] => 12
	 *		)
	 *  )
	 *
	 * @var array
	 */
	protected $level_map = array();
	
	/**
	 * Constructor
	 *
	 * @param DB_Database_1 $ldb LDB connection
	 */
	public function __construct(DB_Database_1 $ldb)
	{
		$this->ldb = $ldb;
		$this->fetchStatusMap();
	}
	
	/**
	 * Fetches all of the active statuses and sets an
	 * associative array with statuses by id and named
	 * 'chains' such as 'active::servicing::customer::*root'
	 *
	 * @return array Associative array of statuses
	 */
	private function fetchStatusMap()
	{
		if (empty($this->status_map))
		{
			$statuses = array();
	
			$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
				SELECT  ast.application_status_id,
					ast.name,
					ast.name_short,
					asf.level0, asf.level1, asf.level2, asf.level3, asf.level4
				FROM application_status AS ast
				LEFT JOIN application_status_flat AS asf USING (application_status_id)
				WHERE ast.application_status_id NOT IN (
					SELECT application_status_parent_id
					FROM application_status
					WHERE active_status = 'active'
					AND application_status_parent_id IS NOT NULL
				)
				AND ast.active_status = 'active'
				ORDER BY name";
	
			$statement = $this->ldb->query($query);
			while ($statement !== FALSE && ($row = $statement->fetch(PDO::FETCH_OBJ)))
			{
				$chain = $row->level0;
				if (!empty($row->level1)) $chain .= '::' . $row->level1;
				if (!empty($row->level2)) $chain .= '::' . $row->level2;
				if (!empty($row->level3)) $chain .= '::' . $row->level3;
				if (!empty($row->level4)) $chain .= '::' . $row->level4;
	
				$statuses[$row->application_status_id]['id'] = $row->application_status_id;
				$statuses[$row->application_status_id]['name_short'] = $row->name_short;
				$statuses[$row->application_status_id]['name'] = $row->name;
				$statuses[$row->application_status_id]['chain'] = $chain;
				
				$this->buildLevelMap($chain, $row->application_status_id);
			}
			
			$this->status_map = $statuses;
		}
		
		return $this->status_map;
	}
	
	/**
	 * Returns a status chain based on the application_status_id.
	 *
	 * @param int $application_status_id The app status ID
	 * @return string
	 */
	public function getStatusChain($application_status_id)
	{
		$chain = NULL;
		
		if (!empty($this->status_map[$application_status_id]))
		{
			$chain = $this->status_map[$application_status_id]['chain'];
		}
		
		return $chain;
	}
	
	/**
	 * Returns the full status chain for common statuses that are
	 * used inside of OLP.
	 *
	 * @param string $status_name The short name of the status
	 * @param bool $is_preact Set to TRUE if app is a preact
	 * @return string The full status chain
	 */
	public static function getStatusChainByName($status_name, $is_preact = FALSE)
	{
		switch ($status_name)
		{
			case 'addl':
				$path = "{$status_name}::verification::applicant";
				break;
			
			case 'verification':
			case 'underwriting':
			case 'fraud':
			case 'high_risk':
				if ($is_preact)
				{
					$status_name = 'preact';
				}
				
				$path = "queued::{$status_name}::applicant";
				break;

			case 'withdrawn':
			case 'denied':
				$path = "{$status_name}::applicant";
				break;

			default:
			case 'pending':
			case 'agree':
			case 'confirmed':
			case 'confirm_declined':
			case 'disagree':
			case 'declined':
			case 'soft_fax':
				if ($is_preact)
				{
					$status_name = 'preact_' . $status_name;
				}
				
				$path = "{$status_name}::prospect";
				break;
		}

		return $path . '::*root';
	}
	
	/**
	 * Search the status map for an application_status_id using the status chain
	 *
	 * @param string $chain Status chain (example: 'active::servicing::customer::*root')
	 * @return int
	 */
	public function getApplicationStatusID($chain) 
	{
		foreach ($this->status_map as $id => $info)
		{
			if ($info['chain'] == $chain)
			{
				return $id;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Gets an array of application_status_ids
	 *
	 * @param array $chains The chains of the statuses we're looking for
	 * @return array
	 */
	public function getApplicationStatuses(array $chains)
	{
		$statuses = array();
		
		foreach ($this->status_map as $id => $info)
		{
			if (in_array($info['chain'], $chains))
			{
				$statuses[] = $id;
			}
		}
		
		return $statuses;
	}
	
	/**
	 * Adds the current chain to the level map
	 *
	 * @param string $chain Status chain to add
	 * @param int $application_status_id The app status ID for the chain
	 * @return NULL
	 */
	private function buildLevelMap($chain, $application_status_id)
	{
		// Get the indivdual statuses and reverse them
		$levels = array_reverse(explode('::', $chain));
		// Get rid of *root
		array_shift($levels);
		
		// Make gratuitous use of references
		$current_level = &$this->level_map;
		for ($i = 0; $i < count($levels); $i++)
		{
			$level = $levels[$i];

			// If this level isn't set up, let's do it.
			if (!isset($current_level[$level]))
			{
				$current_level[$level] = array();
			}
			
			// If we're on the last level, we'll just store
			// the full chain.
			if ($i == count($levels) - 1)
			{
				$current_level[$level] = $application_status_id;
			}
			// Otherwise, get ready to add a new level
			// by changing the current level to this level.
			else
			{
				$current_level = &$current_level[$level];
			}
		}
	}
	
	/**
	 * Rework of the old function from OLP_LDB.  Gets rid of all that leaf and children
	 * nonsense that didn't really make much sense in the first place.  Still uses all
	 * the same syntax and whatnot, though, so as to be backwards compatible.
	 * 
	 * This will grab all the statuses out of the level_map that's generated when
	 * the statuses are queried from LDB.
	 *
	 * Examples:
	 *
	 * 	/applicant/denied == *root=>applicant=>denied
	 * 	/customer/collections/ == all children of *root=>customer=>collections
	 * 	/customer/collections/> == all children that are leaf nodes
	 *	/prospect> == nothing, prospect is a branch, not a leaf
	 * 	/prospect/pending:/prospect/agree == *root=>prospect=>pending, *root=>prospect=>agree
	 *
	 * @param string $path
	 * @return array
	 */
	public function statusGlob($path)
	{
		$found_statuses = array();
		$statuses = explode(':', $path);
		foreach ($statuses as $status)
		{
			$status = trim($status, '/>');
			$levels = explode('/', $status);
			
			$current_level = $this->level_map;
			for ($i = 0; $i < count($levels); $i++)
			{
				$level = $levels[$i];
				if ($i == count($levels) - 1 && isset($current_level[$level]))
				{
					$found_statuses[] = $current_level[$level];
				}
				else
				{
					$current_level = $current_level[$level];
				}
			}
		}
		
		return $this->flattenArray($found_statuses);
	}
	

	/**
	 * Flattens an array
	 *
	 * @param array $array Array to flatten
	 * @return array
	 */
	private function flattenArray($array)
	{
		$flat = array();
		
		foreach ($array as $index => $value)
		{
			if (is_array($value))
			{
				$flat = array_merge($flat, $this->flattenArray($value));
			}
			else
			{
				$flat[] = $value;
			}
		}
		
		return $flat;
	}
}

?>
