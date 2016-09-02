<?php
/**
 * Factory for creating a RuleCollection for SuppressionLists.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_SuppressionList
{
	/**
	 * Static list of suppression lists rules.
	 * 
	 * If we load the same rule with the same type, there's no reason not to use the same object.
	 *
	 * @var array
	 */
	protected static $list_rules = array();
	
	/**
	 * Returns a rule collection of suppression list rules.
	 * 
	 * The array passed to this function is expected to have keys with the ID's 
	 * of the suppressions lists with values of the type of list 
	 * (EXCLUDE, VERIFY, etc...).
	 *
	 * @todo This could eventually be moved into a non-legacy folder and updated
	 * to not be static.
	 * @param array $lists the lists to create
	 * @param array $allowed_fields an array of strings containing fields that 
	 * we'll restrict lists to
	 * @param array $allowed_lists an array of strings containing list names 
	 * that we'll restrict lists to
	 * @return void
	 */
	public function getSuppressionLists(
		array $lists, 
		array $allowed_fields = NULL, 
		array $allowed_lists = NULL)
	{
		$db = OLPBlackbox_Config::getInstance()->olp_db;
		$db_name = OLPBlackbox_Config::getInstance()->olp_db->db_info['bbadmin_db'];
		
		$list_collection = new OLPBlackbox_RuleCollection();
		$list_collection->setEventName(OLPBlackbox_Config::EVENT_SUPPRESSION);
		// Suppression list stats handled inside of OLPBlackbox_Rule_Suppression
		
		// catch rules should not result in failure per brianf [DO]
		$no_fail_collection = new OLPBlackbox_NoFailRuleCollection();
		
		foreach ($lists as $id => $type)
		{
			$list = new Cache_SuppressionList($db, $db_name);
			
			try
			{
				$list->Load($id);
			}
			catch (Exception $e)
			{
				OLPBlackbox_Config::getInstance()->applog->Write(
					sprintf(
						"Failed to load suppression list (ID: %s): (%s) %s.",
						$id,
						get_class($e),
						$e->getMessage()
					)
				);
				continue;
			}
			
			// Check if we're on the allowed list, if we're not, skip us
			if (!empty($allowed_fields) && !in_array($list->Field(), $allowed_fields))
			{
				continue;
			}
			
			// Only allow lists that we specify to run
			if (!empty($allowed_lists) && !in_array($list->Name(), $allowed_lists))
			{
				continue;
			}
			
			// Determine our type and mode (list mode)
			if (is_array($type))
			{
				// Type and list mode
				list($list_type, $list_mode) = $type;
			}
			else
			{
				$list_type = $type;
				$list_mode = 'ALL';
			}
			
			// If we're not in ALL mode or we don't match the current Blackbox mode
			if (strcasecmp($list_mode, 'ALL') != 0
				&& strcasecmp($list_mode, OLPBlackbox_Config::getInstance()->blackbox_mode))
			{
				// We want to skip this suppression list for a mode it's not set for
				continue;
			}
			
			// Check our static list to see if we already have this list
			if (isset(self::$list_rules[$list_type][$id]))
			{
				// If we have it, use that rule and move on
				$list_collection->addRule(self::$list_rules[$list_type][$id]);
				continue;
			}
			
			switch ($list_type)
			{
				case 'EXCLUDE':
					$list_rule = new OLPBlackbox_Rule_Suppression_Exclude($list);
					break;
				case 'CATCH':
					$list_rule = new OLPBlackbox_Rule_Suppression_Catch($list);
					break;
				case 'RESTRICT':
					$list_rule = new OLPBlackbox_Rule_Suppression_Restrict($list);
					break;
				case 'VERIFY':
					// this currently doesn't add any rules, but we don't want to keep seeing the log entry
					break;
				default:
					OLPBlackbox_Config::getInstance()->applog->Write(
						'Invalid suppression list type passed during rule construction.'
					);
					break;
			}
			
			if ($list_rule instanceof OLPBlackbox_Rule_Suppression)
			{
				$list_rule->setupRule(
					array(
						OLPBlackbox_Rule::PARAM_FIELD => $list->Field(),
						OLPBlackbox_Rule::PARAM_VALUE => NULL
					)
				);
				// Store this rule in cache
				self::$list_rules[$list_type][$id] = $list_rule;
				
				if (OLPBlackbox_Config::getInstance()->blackbox_mode != OLPBlackbox_Config::MODE_BROKER
					&& !$list_rule instanceof OLPBlackbox_RuleCollection)
				{
					// in prequal mode, rules can be skipped
					$list_rule->setSkippable(TRUE);
				}
				
				if ($list_rule instanceof OLPBlackbox_Rule_Suppression_Catch)
				{
					$no_fail_collection->addRule($list_rule);
				}
				else 
				{
					$list_collection->addRule($list_rule);
				}
				unset($list_rule);
			}
		}
		
		$list_collection->addRule($no_fail_collection);
		
		return $list_collection;
	}
}
?>
