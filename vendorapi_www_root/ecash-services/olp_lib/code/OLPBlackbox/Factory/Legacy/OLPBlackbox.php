<?php
/**
 * Defines the OLPBlackbox_Factory_OLPBlackbox class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory to return blackbox object created from "legacy" OLP database structure.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 *
 * @todo Proper handling if no target data was returned
 * @todo Accept/Reject Level
 */
class OLPBlackbox_Factory_Legacy_OLPBlackbox
{
	/**
	 * Data contained within OLPBlackbox_Config
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * The debuging flags to use.
	 *
	 * @var OLPBlackbox_DebugConf
	 */
	protected $debug = NULL;

	/**
	 * The Blackbox config object.
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $config;

	/**
	 * Construct a OLPBlackbox legacy factory.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$this->debug = $this->config->debug;
		
		// log event to denote that this is "new" blackbox running
		$this->config->event_log->Log_Event('BLACKBOX_VERSION', '3');
	}

	/**
	 * Gets the blackbox object.
	 *
	 * @return Blackbox
	 */
	public function getBlackbox()
	{
		// We are going to need a root collection, so get that ready.
		$root_collection = new OLPBlackbox_OrderedCollection('root');

		// collection of rules to be run by all OLPBlackbox configurations
		$root_rules = new Blackbox_RuleCollection();

		// Legacy BBx had the "Bad ABA" rule right on the top level
		// to prevent leads with bad bank accounts from going anywhere.
		$root_rules->addRule($this->getAbaRule());
		
		// Legacy BBx also checked to make sure that applications from certain
		// states could not be sold
		$this->addLegacyStateCheckRules($root_rules);
		$root_collection->setRules($root_rules);

		// Fetch all of the tier/target/rule info were going to need.
		$this->data = $this->getBlackboxData();
		
		// Check if we have any sequential targets
		if (!empty($this->data['sequential']))
		{
			// HACK, once we get away from submitlevel stats we can do away with this
			// They shouldn't be mixing UK and non-UK targets, but I'd rather change it to tier 0
			// than leave it as tier 1. Therefore, we break after finding one UK vendor.
			foreach ($this->data['sequential'] as $target_row)
			{
				if (preg_match('/\_uk\d*$/i', $target_row['property_short']))
				{
					$sequential_state_data = new OLPBlackbox_TierStateData(array('tier_number' => 0));
					break;
				}
				else
				{
					$sequential_state_data = new OLPBlackbox_TierStateData(array('tier_number' => 1));
				}
			}
			
			$sequential_collection = new OLPBlackbox_OrderedCollection('sequential', $sequential_state_data);
			
			if ($this->addTargets($sequential_collection, $this->data['sequential']))
			{
				// TODO: This can all be removed except the last line once campaigns aren't required
				$sequential_collection_campaign = new OLPBlackbox_Campaign('sequential_campaign', 0, 1);
				$sequential_collection_campaign->setTarget($sequential_collection);
				$sequential_collection_campaign->setRules(new Blackbox_RuleCollection());
				$root_collection->addTarget($sequential_collection_campaign);
			}
		}


		// Set up CLK/CashNet tier if we need it
		if (!empty($this->data['preferred']))
		{
			$state_data = new OLPBlackbox_TierStateData(array('tier_number' => 1));
			$collection = new OLPBlackbox_PreferredCollection(OLPBlackbox_Config::getInstance()->preferred_tier, $state_data);

			// When we added GRV to the super-tier, they changed the model from a
			// percentage-based system, to an ordered system where CN gets all 1st
			// looks, GRV gets all 2nd, and CLK gets all 3rd.  But they claim they'll
			// want to move back to percentage at some point, so we'll keep it here.
			if (OLPBlackbox_Config::getInstance()->preferred_tier == 'preferred_ordered')
			{
				//$collection = new OLPBlackbox_OrderedCollection(OLPBlackbox_Config::getInstance()->preferred_tier, $state_data);
				$collection->setPicker(new OLPBlackbox_OrderedPicker());
				
				// Grab all the target data, then switch it so the index is
				// the order in which they should be in
				$targets = array_flip(SpecialTier::getInstance(OLPBlackbox_Config::getInstance()->preferred_tier)->getTargetData());
				
				// Then we'll go through the available targets and drop in the
				// target row data for the target, so that when we create the collection,
				// they're all in order.
				$target_data = array();
				foreach ($this->data['preferred'] as $target_row)
				{
					$target_data[array_search(strtoupper($target_row['property_short']), $targets)] = $target_row;
				}

				// Make sure they're in order
				ksort($target_data);
			}
			else
			{					
				$collection->setPicker(new OLPBlackbox_PercentPicker());
				
				// Change all the weighting to percentage and use whatever is
				// defined in the database
				foreach ($this->data['preferred'] as $key => $target)
				{
					$this->data['preferred'][$key]['weight_type'] = 'PERCENT';
					$this->data['preferred'][$key]['percentage']
						= SpecialTier::getInstance(OLPBlackbox_Config::getInstance()->preferred_tier)->getWeight($target['property_short']);
				}
				
				$target_data = $this->data['preferred'];
			}
			
			if ($this->addTargets($collection, $target_data))
			{
				$preferred_campaign = new OLPBlackbox_Campaign('preferred_campaign', 0, 1);
				$preferred_campaign->setTarget($collection);
				$preferred_campaign->setRules(new Blackbox_RuleCollection());
				$root_collection->addTarget($preferred_campaign);
			}
		}

		if (!empty($this->data))
		{
			foreach ($this->data['tiers'] as $tier_id => $tier_row)
			{
				$tier = OLPBlackbox_Factory_Legacy_Tier::getTier($tier_row);
	
				if ($this->addTargets($tier, $this->data['targets'][$tier_id]))
				{
					$tier_campaign = new OLPBlackbox_Campaign(
						$tier_row['name'], 0, 1
					);
					$tier_campaign->setTarget($tier);
					$tier_campaign->setRules(new Blackbox_RuleCollection());
					$root_collection->addTarget($tier_campaign);
				}
			}
		}

		$init_data = array();
		if ($this->config->blackbox_mode == OLPBlackbox_Config::MODE_ECASH_REACT)
		{
			$init_data['failure_reasons'] = new OLPBlackbox_FailureReasonList();
		}
		$state_data = new OLPBlackbox_StateData($init_data);

		$blackbox = new OLPBlackbox($state_data);
		
		// In BROKER mode, we might be using the "vetting tier" from
		// gforge 9922
		if ($this->config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
			&& $this->config->use_vetting_tier)
		{
			$vetting_factory = new OLPBlackbox_Vetting_Factory_Collection();
			$vetting_collection = $vetting_factory->getCollection();
			
			// the vetting collection 
			$top_collection = new OLPBlackbox_OrderedCollection('supertier');
			$top_collection->addTarget($vetting_collection);
			$top_collection->addTarget($root_collection);
			$blackbox->setRootCollection($top_collection);
		}
		else 
		{
			$blackbox->setRootCollection($root_collection);
		}

		return $blackbox;
	}
	
	/**
	 * Add some hard coded rules that Legacy BBx ran before any targets.
	 *
	 * @param Blackbox_RuleCollection $rules Rule collection to add to
	 * @return NULL
	 */
	protected function addLegacyStateCheckRules(Blackbox_RuleCollection $rules)
	{
		static $excluded_states = array(
			'VA', 'WV', 'GA',
		);
		
		$bb_mode = OLPBlackbox_Config::getInstance()->blackbox_mode;
		if (in_array($bb_mode, array(OLPBlackbox_Config::MODE_BROKER, OLPBlackbox_Config::MODE_ECASH_REACT))
			&& empty(OLPBlackbox_Config::getInstance()->bypass_state_exclusion))
		{
			foreach ($excluded_states as $state)
			{
				$rule = new OLPBlackbox_Rule_LegacyStateExclude($state);
				// If we're not in broker mode, make us skippable
				if ($bb_mode != OLPBlackbox_Config::MODE_BROKER)
				{
					$rule->setSkippable(TRUE);
				}
				$rules->addRule($rule);
			}
		}
	}

	/**
	 * Prevent leads with bad ABA numbers from going anywhere.
	 *
	 * @return Blackbox_IRule object
	 */
	protected function getAbaRule()
	{
		// We don't want to run this rule for UK apps
		if ($this->debug->flagFalse(OLPBlackbox_DebugConf::ABA)
			|| $this->config->app_flags->flagExists(OLP_ApplicationFlag::UK_APP))
		{
			$rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			// @todo figure out how to determine if its an agean site and add the no ssn checks rule here!
			// @todo need to make sure that it checks to see if debug flags are checked
			$rule = new OLPBlackbox_Rule_AbaCheck();
		}
		if (OLPBlackbox_Config::getInstance()->blackbox_mode != OLPBlackbox_Config::MODE_BROKER)
		{
			$rule->setSkippable(TRUE);
		}
		$rule->setEventName(OLPBlackbox_Config::EVENT_ABA_BAD);
		$rule->setStatName(strtoupper(OLPBlackbox_Config::EVENT_ABA_BAD));

		return $rule;
	}

	/**
	 * Gets the blackbox data, including tiers, targets, and rules.
	 * 
	 * For bb_sequential_preferred, functionality was changed so that if you 
	 * specify a child target of a parent target, it will not honor that input. 
	 * For instance, if you specify PCL in the bb_sequential_preferred list, it 
	 * will not be put into the list because it is a child of CLK. This could be 
	 * changed later, but as CLK was supposed to be the one specified in 
	 * existing sites at the time, to prevent issue where it's children would 
	 * get all the leads, I left the functionality as specified above. Note that 
	 * the order of the conditionals below is important when adding targets to 
	 * the array. Do not change that order without understanding exactly what 
	 * you're changing. If the bb_sequential_preferred targets are above the 
	 * children, it will add the child to the sequential array and not run any 
	 * of the parent rules. For further explanation, see Brian Feaver.
	 *
	 * @return array
	 */
	protected function getBlackboxData()
	{
		$config = OLPBlackbox_Config::getInstance();

		// determine if we're forcing particular tiers
		$tier_clause = '';

		// Grab tiers we're using and the tiers we're excluding
		$use_tiers = $this->debug->getFlag(OLPBlackbox_DebugConf::USE_TIER);
		$exclude_tiers = $this->debug->getFlag(OLPBlackbox_DebugConf::EXCLUDE_TIER);

		// Find our common tiers
		$common_tiers = array_intersect($use_tiers, $exclude_tiers);
		$tiers = array_diff($use_tiers, $common_tiers);
		
		// Grab the bb_sequential_preferred targets, sequential only needs to run on BROKER mode
		$sequential_preferred_targets = array();
		if (isset($config->bb_sequential_preferred)
			&& $config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			$sequential_preferred_targets = explode(',', $config->bb_sequential_preferred);
			$sequential_preferred_targets = array_map('trim', $sequential_preferred_targets);
			$sequential_preferred_targets = array_map('strtoupper', $sequential_preferred_targets);
		}

		/**
		 * This is used for the CLK/CashNet preferred tier.
		 */
		$preferred_tier_targets = array();
		if ($config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
			&& $config->disable_preferred_tier !== TRUE)
		{
			$preferred_tier_targets = SpecialTier::getInstance(OLPBlackbox_Config::getInstance()->preferred_tier)->getTargets();
		}

		// preferred targets should be an array if present.
		$preferred_targets = $config->preferred_targets;

		if ($preferred_targets)
		{
			if (!is_array($preferred_targets))
			{
				$preferred_targets = array($preferred_targets);
			}
			$preferred_targets = array_map('strtoupper', $preferred_targets);
		}

		if (!empty($tiers))
		{
			$tier_clause = "AND tier.tier_number IN (".implode(', ', $tiers).")";
		}
		else
		{
			$tier_clause = "AND 0";
		}
		
		$force_winner = $this->config->bb_force_winner;
		if (!is_array($force_winner))
		{
			$force_winner = array_map('trim', explode(',', $force_winner));
		}
		$query = self::getCampaignQuery($tier_clause, $force_winner);
		
		$key = sprintf('olpblackbox/factory/legacy/query/%s', md5($query));
		$target_list = Cache_OLPMemcache::getInstance()->get($key);
		
		if (!$target_list)
		{
			try
			{
				$target_list = array();
				$result = $config->olp_db->Query(
					$config->olp_db->db_info['db'], $query
				);
				
				while ($row = $config->olp_db->Fetch_Array_Row($result))
				{
					$target_list[] = $row;
				}
				
				Cache_OLPMemcache::getInstance()->add($key, $target_list);
			} 
			catch (Exception $e)
			{
				throw new Blackbox_Exception($e->getMessage());
			}
		}
		
		$data = array();
		$data['sequential'] = array();
		$data['preferred'] = array();

		
		foreach ($target_list as $row)
		{
			// preferred targets will modify these two items.
			$tier_index = $row['tier_id'];
			$tier_name = $row['tier_name'];

			if ($preferred_targets 
				&& in_array($row['property_short'], $preferred_targets))
			{
				// artificially put this target at "tier 0"
				$tier_name = 'Tier 0';
				$tier_index = 0;
			}

			// Create an array of all of the tiers.
			if (!isset($data['tiers'][$tier_index]))
			{
				$data['tiers'][$tier_index] = array(
					'name' => $tier_name,
					'weight_type' => $row['weight_type'],
					'tier_number' => $row['tier_number']
				);
			}

			// Create a children array to store all of the child->parent relationships/
			if ($row['is_parent'] && !isset($data['children'][$row['property_short']]))
			{
				$data['children'][$row['property_short']] = array();
			}

			// Find the index for the sequential target, used below if found
			$location = array_search(
				$row['property_short'], $sequential_preferred_targets
			);
			
			$preferred_location = array_search(
				$row['property_short'], $preferred_tier_targets
			);

			// Add the target to the appropriate targets [parent] or children array.
			// *****
			// DO NOT CHANGE THE ORDER OF THESE CONDITION STATEMENTS WITHOUT 
			// READING THE FUNCTION COMMENT ABOVE
			// *****
			if (isset($data['children'][$row['parent_name']]))
			{
				// this is, basically, CLK (at this time). CLK companies
				// will not be set as preferred targets (i.e. tier_index=0)
				$data['children'][$row['parent_name']][$row['property_short']] = $row;
			}
			elseif (!empty($sequential_preferred_targets) && $location !== FALSE)
			{
				// We're going to add a section to $data to hold sequential targets
				// This needs to be done second, so that we don't add children to the
				// sequential list.
				// Sequential targets will not be added to the normal target list.
				$data['sequential'][$location] = $row;
			}
			elseif (!empty($preferred_tier_targets) 
				&& $preferred_location !== FALSE
				&& ((date('N') < 6) || (strcasecmp($row['property_short'], 'CLK') !== 0))) // #15681 [DY]
			{
				$data['preferred'][$preferred_location] = $row;
			}
			else
			{
				$data['targets'][$tier_index][$row['property_short']] = $row;
			}
		}

		// tier 0 may get added out of order, resort
		ksort($data['tiers']);
		ksort($data['targets']);
		ksort($data['sequential']);
		ksort($data['preferred']);

		$data = $this->restrictForceWinners($data);
		$data = $this->restrictAndExclude($data);
		
		// Call removeInactive on each piece of the data array - GForge #17569 [DW]
		$tiers = array_keys($data['targets']);
		foreach ($tiers as $tier)
		{
			$this->removeInactive($data['targets'][$tier]);
		}
		$parents = array_keys($data['children']);
		foreach ($parents as $parent)
		{
			$this->removeInactive($data['children'][$parent]);
		}
		$this->removeInactive($data['sequential']);
		$this->removeInactive($data['preferred']);
		
		return $data;
	}

	/**
	 * Remove inactive targets from the peices of the data array.
	 * 
	 * Because of GForge #16537, it's possible to have targets listed in the
	 * data array that are inactive. So, we unset them here.
	 *
	 * Found from GForge #17569, there are actually several peices (targets, 
	 * children, sequential, and preferred) to data that need the inactive 
	 * targets removed from. So this now is just takes each piece and removes 
	 * the inactive data. This function is called on each piece in 
	 * $this->getBlackboxData()
	 * 
	 * @param array $data Factory data for setting up targets.
	 * @return void (modifications are done in place)
	 */
	protected function removeInactive(array &$data)
	{
		$targets = array_keys($data);
		foreach ($targets as $target)
		{
			if ($data[$target]['target_status'] == 'INACTIVE'
				|| $data[$target]['campaign_status'] == 'INACTIVE')
			{
				unset($data[$target]);
			}
		}
	}
	
	/**
	 * The main query that fuels the collection of rules for targets.
	 * 
	 * This was moved out so that {@see OLPBlackbox_Vetting_Factory_Collection}
	 * could use this query as well.
	 *
	 * @param string $clause Must begin with 'AND', newline will be suffixed.
	 * @return string full query.
	 */
	public static function getCampaignQuery($clause, array $force_targets = array())
	{
		if (empty($force_targets))
		{
			$force_targets[] = -1;
		}
		$return = "
			SELECT
				DISTINCT(target.target_id),
				UCASE( property_short ) AS property_short,
				tier.tier_number,
				tier.name as tier_name,
				tier.tier_id,
				target.name AS target_name,
				target.status AS target_status,
				parent_target_id,
				weight_type,
				non_dates,
				military,
				weekends,
				bank_account_type,
				minimum_income,
				income_direct_deposit,
				excluded_states,
				income_frequency,
				state_id_required,
				state_issued_id_required,
				minimum_recur AS minimum_recur_ssn,
				minimum_recur AS minimum_recur_email,
				minimum_recur_withheld_ssn,
				minimum_recur_withheld_email,
				income_recur,
				pay_date_recur,
				identical_phone_numbers,
				identical_work_cell_numbers,
				suppression_lists,
				required_references,
				restricted_states,
				excluded_zips,
				force_promo_id,
				force_site_id,
				minimum_recur,
				dd_check,
				minimum_age,
				paydate_minimum,
				withheld_targets,
				operating_hours,
				income_type,
				income_source,
				datax_idv,
				vendor_qualify_post,
				verify_post_type,
				run_fraud,
				frequency_decline,
				max_loan_amount_requested,
				min_loan_amount_requested,
				residence_length,
				employer_length,
				residence_type,
				list_mgmt_nosell,
				campaign.`limit`,
				campaign.daily_limit,
				campaign.hourly_limit,
				campaign.limit_mult,
				campaign.lead_amount,
				campaign.percentage,
				campaign.dd_ratio,
				campaign.max_deviation,
				campaign.priority,
				campaign.overflow,
				campaign.status AS campaign_status,
				(
					SELECT parent_target_id
					FROM target tp
					WHERE tp.parent_target_id = target.target_id
						AND tp.status = 'ACTIVE'
						AND tp.deleted = 'FALSE'
					LIMIT 1
				) AS is_parent,
				(
					SELECT UCASE(property_short)
					FROM target tp
					WHERE tp.target_id = target.parent_target_id
						AND tp.status = 'ACTIVE'
						AND tp.deleted = 'FALSE'
					LIMIT 1
				) AS parent_name
			FROM
				target
				INNER JOIN tier
					ON tier.tier_id = target.tier_id
				INNER JOIN rules
					ON rules.target_id = target.target_id
				INNER JOIN campaign
					ON campaign.target_id = target.target_id
			WHERE
				(target.status = 'ACTIVE' 
				 OR
				 target.property_short IN ('".implode("', '", $force_targets)."'))

				AND target.deleted = 'FALSE'
				AND tier.status = 'ACTIVE'
				{$clause}
				AND rules.status = 'ACTIVE'
				AND campaign.type = 'ONGOING'
				AND campaign.status = 'ACTIVE'
			ORDER BY tier_number ASC, parent_target_id ASC
		";
		return $return;
	}

	/**
	 * This function will run restrict and exclude rules.  There
	 * are a few things we have to consider when doing this, namely
	 * that restrict will override anything else.  The biggest
	 * problem appears when you introduce parent-child relationships.
	 *
	 * Consider the situation where we ssforce='clk'.  In this case,
	 * we're restricting to CLK, which is a parent target.  So as a
	 * result, we're also going to have to restrict to all of its children
	 * as well, so we need to make sure we don't remove them.
	 *
	 * In another situation where we run ssforce='d1', since D1 is a
	 * child, we need to ensure that we don't remove his parent.  So in
	 * this case, we'd need to make sure the CLK parent target stays
	 * around as well.
	 *
	 * @param array $data The target data from getBlackboxData()
	 * @return array A new data array based on restrictions and exclusions.
	 */
	protected function restrictAndExclude(array $data)
	{
		$restricted_targets = $this->debug->getFlag(OLPBlackbox_DebugConf::TARGETS_RESTRICT);
		$excluded_targets = $this->debug->getFlag(OLPBlackbox_DebugConf::TARGETS_EXCLUDE);

		if (!empty($restricted_targets) || !empty($excluded_targets))
		{
			$final_data = array();
			$final_data['targets'] = array();
			$final_data['children'] = array();

			foreach ($data['targets'] as $tier_id => $tier)
			{
				foreach ($tier as $prop_short => $target)
				{
					// Checking for restricted targets overrides
					// checking for excluded targets.
					if (!empty($restricted_targets))
					{
						// We want to check against the current target, and
						// if it's a parent, its children, as well.
						$names = array($prop_short);
						if (isset($data['children'][$prop_short]))
						{
							$names = array_merge($names, array_keys($data['children'][$prop_short]));
						}

						// See if we find any matches in the restricted targets.
						$found = array_intersect($names, $restricted_targets);
						if (!empty($found))
						{
							// If we do have a match, the parent will ALWAYS be included
							// because either it was specified directly, or one of its
							// children was, which means it still has to be there, like
							// the loving parent that it is.
							$final_data['targets'][$tier_id][$prop_short] = $target;

							// We only found the parent in the restriction, which means
							// we need to include ALL of its children in the restriction.
							if (count($found) == 1 && in_array($prop_short, $found)
								&& isset($data['children'][$prop_short]))
							{
								$final_data['children'][$prop_short] = $data['children'][$prop_short];
							}
							else
							{
								// Otherwise, we just figure out which ones were
								// specified and include them in the restriction.
								foreach ($found as $restricted_child)
								{
									if ($restricted_child != $prop_short)
									{
										$final_data['children'][$prop_short][$restricted_child] = $data['children'][$prop_short][$restricted_child];
									}
								}
							}
						}
					}
					// Otherwise, we check and see if the current target is
					// NOT an excluded target.  If they are, we just won't add
					// them to the final_data array since they've been excluded.
					elseif (!$this->isExcludedTarget($prop_short))
					{
						$final_data['targets'][$tier_id][$prop_short] = $target;

						// We'll also check all of its children here.
						if (isset($data['children'][$prop_short]))
						{
							foreach (array_keys($data['children'][$prop_short]) as $child_short)
							{
								// And assuming they're not excluded, we'll keep them
								// all together as one big happy family.
								if (!$this->isExcludedTarget($child_short))
								{
									$final_data['children'][$prop_short][$child_short] = $data['children'][$prop_short][$child_short];
								}
							}
						}
					}
				}
			}
			
			// Handle the targets in our sequential collection
			if (!empty($data['sequential']))
			{
				$final_data = $this->sequentialRestrictAndExclude($data, $final_data, 'sequential');
			}
			if (!empty($data['preferred']))
			{
				$final_data = $this->sequentialRestrictAndExclude($data, $final_data, 'preferred');
			}

			// Overwrite all the keys (except sequential) with the new data
			$data = array_merge($data, $final_data);
		}

		return $data;
	}

	/**
	 * Restricts the force winners.
	 *
	 * This function does the logic for bb_force_winner. If bb_force_winner is set, it will only
	 * restrict the target on the tier that the target exists on. All other tiers that don't have
	 * a bb_force_winner specified, will remain intact.
	 *
	 * @param array $data the target data from getBlackboxData
	 * @return array
	 */
	protected function restrictForceWinners(array $data)
	{
		$force_winner_targets = $this->config->force_winner;

		$final_data = array();
		$final_data['children'] = array();

		if (!empty($force_winner_targets))
		{
			foreach ($data['targets'] as $tier_id => $tier)
			{
				/**
				 * Flatten out the tier so we can see if any of the targets that may be children
				 * are part of the force winner list.
				 */
				$tier_props = array_keys($tier);
				$tier_children = array();
				foreach ($tier_props as $prop_short)
				{
					if (isset($data['children'][$prop_short]))
					{
						$tier_props = array_merge($tier_props, array_keys($data['children'][$prop_short]));
						$tier_children[$prop_short] = $data['children'][$prop_short];
					}
				}

				$found = array_intersect($force_winner_targets, $tier_props);
				if (!$found)
				{
					// We didn't find any of the force_winners, so leave this tier alone
					$final_data['targets'][$tier_id] = $tier;
					if (!empty($tier_children))
					{
						$final_data['children'] = array_merge($final_data['children'], $tier_children);
					}
					continue;
				}

				// Run through the tier, adding only the targets that we have in force_winners
				foreach ($tier as $prop_short => $target)
				{
					// We want to check against the current target, and
					// if it's a parent, its children, as well.
					$names = array($prop_short);
					if (isset($data['children'][$prop_short]))
					{
						$names = array_merge($names, array_keys($data['children'][$prop_short]));
					}

					// See if we find any matches in the force winner targets.
					$found = array_intersect($names, $force_winner_targets);
					if (!empty($found))
					{
						// If we do have a match, the parent will ALWAYS be included
						// because either it was specified directly, or one of its
						// children was, which means it still has to be there.
						$final_data['targets'][$tier_id][$prop_short] = $target;

						// We only found the parent in the restriction, which means
						// we need to include ALL of its children in the restriction.
						if (count($found) == 1 && in_array($prop_short, $found)
							&& isset($data['children'][$prop_short]))
						{
							$final_data['children'][$prop_short] = $data['children'][$prop_short];
						}
						else
						{
							// Otherwise, we just figure out which ones were
							// specified and include them in the restriction.
							foreach ($found as $restricted_child)
							{
								if ($restricted_child != $prop_short)
								{
									$final_data['children'][$prop_short][$restricted_child] = $data['children'][$prop_short][$restricted_child];
								}
							}
						}
					}
				}
			}
			
			// Check our sequential list for children
			if (!empty($data['sequential']) || !empty($data['preferred']))
			{
				$targets = array_merge($data['sequential'], $data['preferred']);
				foreach ($targets as $target)
				{
					$prop_short = $target['property_short'];
					if (isset($data['children'][$prop_short]))
					{
						// If our sequential list has children, add them back in
						$final_data['children'][$prop_short] = $data['children'][$prop_short];
					}
				}
			}

			$data = array_merge($data, $final_data);
		}

		return $data;
	}
	
	/**
	 * Checks the restricted and excluded targets against the sequential list.
	 *
	 * @param array $data the data obtained from getBlackboxData()
	 * @param array $final_data the final data from restrictAndExclude()
	 * @param string $type the type of restrict we're doing (sequential, preferred)
	 * @return unknown
	 */
	protected function sequentialRestrictAndExclude(array $data, array $final_data, $type = 'sequential')
	{
		$restricted_targets = $this->debug->getFlag(
			OLPBlackbox_DebugConf::TARGETS_RESTRICT
		);
		
		$final_data[$type] = array();

		foreach ($data[$type] as $target)
		{
			$prop_short = $target['property_short'];
			
			if (!empty($restricted_targets))
			{
				$found = in_array($prop_short, $restricted_targets);

				if (!empty($found))
				{
					$final_data[$type][] = $target;
					
					// Check for children of this target
					// We don't do the same restrictions as above, because of how sequential works.
					// Sequential will only ever have the parent target. If a child target is specified
					// it'll be ignored in the sequential list.
					if (isset($data['children'][$prop_short]))
					{
						$final_data['children'][$prop_short] = $data['children'][$prop_short];
					}
				}
			}
			elseif (!$this->isExcludedTarget($prop_short))
			{
				$final_data[$type][] = $target;
				
				// We'll also check all of its children here.
				if (isset($data['children'][$prop_short]))
				{
					foreach (array_keys($data['children'][$prop_short]) as $child_short)
					{
						// And assuming they're not excluded, we'll keep them
						// all together as one big happy family.
						if (!$this->isExcludedTarget($child_short))
						{
							$final_data['children'][$prop_short][$child_short] = $data['children'][$prop_short][$child_short];
						}
					}
				}
			}
		}

		return $final_data;
	}

	/**
	 * Helper function to determine is a target has been excluded
	 *
	 * @param string $name target name (property short)
	 *
	 * @return bool Whether or not this target was marked as excluded or not
	 */
	protected function isExcludedTarget($name)
	{
		return in_array(
			strtoupper($name), 
			$this->debug->getFlag(OLPBlackbox_DebugConf::TARGETS_EXCLUDE)
		);
	}

	/**
	 * Adds all of the targets to the tiers target collection.
	 *
	 * @param Blackbox_TargetCollection $parent_target_collection Target Collection
	 * @param array $targets_to_add Array of targets to add to the parent target collection
	 *
	 * @return bool TRUE if any targets were added to the collection.
	 */
	private function addTargets($parent_target_collection, $targets_to_add)
	{
		if (empty($targets_to_add))
		{
			return FALSE;
		}

		foreach ($targets_to_add as $target_row)
		{
			// Try to add any children for this target, really - only CLK will do this.
			if ($target_row['is_parent'])
			{
				// Get the TargetCollection we need to use for this parent.
				$target = OLPBlackbox_Factory_Legacy_TargetCollection::getTargetCollection($target_row);

				// Now we need to add the targets for this "tier" collection.
				$this->addTargets($target, $this->data['children'][$target_row['property_short']]);
			}
			else
			{
				// Create the target.
				$target = OLPBlackbox_Factory_Legacy_Target::getTarget($target_row);

				// This determines whether a target submits leads to the list management feed
				$target->getStateData()->list_mgmt_nosell = (strcasecmp($target_row['list_mgmt_nosell'], 'TRUE')===0);
				$target->getStateData()->look_percentages = array();
				
				// Set up look percentages if we have them.
				if (strlen($target_row['frequency_decline']) > 3)
				{
					$frequency_limits = unserialize($target_row['frequency_decline']);
					
					// Indexes 0-5 are for frequency score min/max
					// Indexes 6/7/8 are 1st/2nd/3rd look percentages
					if (count($frequency_limits) >= 9)
					{
						// We only want the look percentages.
						$target->getStateData()->look_percentages = array_slice($frequency_limits, 6); 
					}
				}
			}

			$weight = (int)$target_row['priority'];
			if (strcasecmp($parent_target_collection->getStateData()->weight_type, 'PERCENT') == 0
				&& isset($target_row['percentage']))
			{
				
				$weight = (int)$target_row['percentage'];
			}

			$target_campaign = new OLPBlackbox_Campaign($target_row['property_short'], $target_row['target_id'], $weight);
			// $target could be either a TargetCollection or Target depending
			//   on the code above.
			$target_campaign->setTarget($target);
			$target_campaign->setRules(new Blackbox_RuleCollection());

			$parent_target_collection->addTarget($target_campaign);
		}

		return TRUE;
	}

}
?>
