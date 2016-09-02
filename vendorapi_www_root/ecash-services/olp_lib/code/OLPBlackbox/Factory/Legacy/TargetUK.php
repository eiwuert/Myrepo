<?php
/**
 * Legacy factory for UK targets.
 * 
 * ** This file is no longer in use. TargetUK uses the generic target now. **
 *
 * @author Matt Piper <matt.piper@sellingsource.com> 
 */
class OLPBlackbox_Factory_Legacy_TargetUK
{
	/**
	 * Return ITarget object, instantiated with UK specific things.
	 *
	 * @param array $target_row An array with the target information
	 *
	 * @return ITarget object
	 */
	public static function getTarget($target_row,$target_model)
	{
		// the target we will return
		$target = new OLPBlackbox_Target($target_model->property_short, $target_model->target_id);

		$property_short = strtolower($target_model->property_short);
		
		// Some 'UK' properties have specific rules they must run in addition to the
		// normal rules.  Instead of setting up the specific rules here, just add the rules
		// into the target_row array.  Once this array is ran through the getRuleCollection
		// it will turn them into the actual rules and add them to the rule collection.  The
		// reason it is being done like this is so when the day comes that all the rules are
		// configured in the database, this factory could really disappear - the rules
		// would already be in the array of rules coming from the db, and the rule collection
		// factory will already be able to handle them.
		if ($property_short == 'bi_uk')
		{
			$target_row['nin_required'] = TRUE;
		}
		elseif ($property_short == 'cg_uk' || $property_short == 'cg_uk2')
		{	
			$target_row['residence_type_required'] = TRUE;
			$target_row['employer_phone_required'] = TRUE;
			$target_row['best_call_time'] = array('ANY','MORNING','EVENING','AFTERNOON');
		}
		elseif ($property_short == 'mem_uk')
		{
			$target_row['nin_required'] = TRUE;
		}
		elseif ($property_short == 'xml_uk')
		{
			$target_row['bank_name'] = TRUE;
		}

		// Grab the basic rules with the addition of the new ones we added.
		$rule_collection = new OLPBlackbox_RuleCollection();
		$legacy_rule_collection = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance();

		$rule_collection->addRule($legacy_rule_collection->getRuleCollection($target_model));
		$rule_collection->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_model));

		foreach ($rule_collection as $rule)
		{
			if ($rule instanceof OLPBlackbox_Rule_MinimumRecur_SSN)
			{
				$rule->setSkippable();
			}
		}
		
		$target->setRules($rule_collection);
		
		return $target;
	}
}

?>
