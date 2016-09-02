<?php
/**
 * Reference model for the rule_condition_action table
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class Blackbox_Models_RuleConditionSource extends Blackbox_Models_WriteableModel
{
	const SOURCE_RUNTIME_CONFIG = 'runtime config';
	const SOURCE_APPLICATION_DATA = 'application data';
	
	public static function getValueConstants()
	{
		static $sources;
		
		if (!is_array($sources))
		{
			$constants = new ClassConstants(__CLASS__);
			$sources = $constants->keyStartsWith('SOURCE');
		}
		
		return $sources;
	}

	/**
	 * Get columns for the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'rule_condition_source_id', 'source'
		);
		return $columns;
	}

	/**
	 * Get the name of the table the model represents
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'rule_condition_source';
	}
}
