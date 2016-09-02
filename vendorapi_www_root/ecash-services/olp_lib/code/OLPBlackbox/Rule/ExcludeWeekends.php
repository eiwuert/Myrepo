<?php

/**
 * Rule which excludes weekends.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_ExcludeWeekends extends OLPBlackbox_Rule_ExcludeDayOfWeek 
{
	/**
	 * Setup the rule, expecting a boolean from the database.
	 * @param array $params This function only looks at the PARAM_VALUE item to
	 * test for non-empty expression.
	 * @return void
	 */
	public function setupRule(array $params)
	{
		if (!empty($params[self::PARAM_VALUE]))
		{
			$params[self::PARAM_VALUE] = array('sat', 'sun');
		}
		else 
		{
			$params[self::PARAM_VALUE] = array();
		}
		
		return parent::setupRule($params);
	}
}

?>
