<?php

/**
 * View for getting information about blackbox target entries.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_TargetCollectionChild extends Blackbox_Models_View_TargetCompany implements Blackbox_Models_IReadableTarget
{
	/**
	 * Builds the base query
	 *
	 * @return string
	 */
	protected function getQueryFields()
	{
		return parent::getQueryFields().",
			t.rule_id,
			t.target_collection_class_id,
			tr.weight,
			t.weight_class_id,
			bt.name AS class_name,
			w.class AS weight_class,
			tcc.class AS target_collection_class,
			r.rule_collection_class_id";
	}
	
	/**
	 * Gets the fields for the query
	 *
	 * @return string
	 */
	protected function getQueryTables()
	{
		return "
			target_relation tr
			INNER JOIN target t
				ON tr.child_id = t.target_id
			LEFT JOIN rule r
				ON t.rule_id = r.rule_id
			INNER JOIN blackbox_type bt
				ON bt.blackbox_type_id = t.blackbox_type_id
			LEFT JOIN company c
				ON c.company_id = t.company_id
			LEFT JOIN weight_class w
				ON w.weight_class_id = t.weight_class_id
			LEFT JOIN target_collection_class tcc
				ON tcc.target_collection_class_id = t.target_collection_class_id";
	}
	
	/**
	 * Returns an map of column names to their respective fields in the query
	 *
	 * @return array
	 */
	protected function getColumnNameMap()
	{
		return array_merge(
			parent::getColumnNameMap(),
			array(
				'rule_id' => 't.rule_id',
				'target_collection_class_id' => 't.target_collection_class_id',
				'weight' => 'tr.weight',
				'weight_class_id' => 't.weight_class_id',
				'class_name' => 'bt.name',
				'weight_class' => 'w.class',
				'target_collection_class' => 'tcc.class',
				'rule_collection_class_id' => 'r.rule_collection_class_id',
			)
		);
	}
	
	/**
	 * WARNING: Do not use this unless you know what you're doing!
	 *
	 * This function pulls TargetCollection children by the id of the child, not
	 * the parent. This is used specifically for a hack in
	 * OLPBlackbox_Factory_OLPBlackbox to make the legacy preferred_targets and
	 * sequential preferred targets collections.
	 *
	 * @param int $property_short The property short of a target.
	 * @param int $type olp_blackbox.blackbox_type.blackbox_type_id of the object to return
	 * @param int $active Whether to get active or inactive targets
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getChild($property_short, $type = self::TYPE_CAMPAIGN, $active = 1)
	{
		$params = array(
			'property_short' => $property_short,
			'type' => $type,
			'active' => $active ? 1 : 0,
		);
		
		return $this->getTargets($params, 'weight');
	}
	
	/**
	 * Gets all the targets/campaigns for a specified collection.
	 *
	 * @param int $target_id
	 * @param bool $active Whether to gather active targets or non-active targets.
	 * @param array $shorts List of property_shorts to gather even if they are
	 * inactive. This was initially implemented for gforge issue #19929
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getCollectionTargets($target_id, $active = 1, $shorts = NULL)
	{
		$active = $active ? 1 : 0;
		
		$query = $this->getBaseQuery() . " WHERE tr.target_id = ? AND (t.active = ? ";
		
		$symbols = array();
		if (is_array($shorts) && count($shorts))
		{
			$symbols = array_pad(array(), count($shorts), '?');
			$query .= " OR t.property_short IN (".implode(',', $symbols).")) ";
		}
		else
		{
			$shorts = array();
			$query .= ") ";
		}
	
		$query .= " ORDER BY tr.weight";
		
		$db = $this->getDatabaseInstance();
		$st = DB_Util_1::queryPrepared(
			$db,
			$query,
			array_merge(array($target_id, $active), $shorts)
		);

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}
	
	/**
	 * @return string
	 * @see Blackbox_Models_View_Base::getTableName()
	 */
	public function getTableName()
	{
		return 'target';
	}
	
	/**
	 * Return the name of the auto increment column in this 'table.'
	 *
	 * This is a useless function for this class, really.
	 *
	 * @return string column
	 */
	public function getAutoIncrement()
	{
		return 'target_id';
	}
}

?>
