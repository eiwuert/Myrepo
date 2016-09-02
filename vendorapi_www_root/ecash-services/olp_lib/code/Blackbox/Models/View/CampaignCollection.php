<?php

/**
 * Gets information about collections that campaigns belong to
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models
 */
class Blackbox_Models_View_CampaignCollection extends Blackbox_Models_View_Base
{
	/**
	 * Pulls info about collections for a property_short
	 *
	 * @param int $property_short The property short of a target.
	 * @return DB_Models_DefaultIterativeModel_1
	 */
	public function getCollections($property_short)
	{
		$query = "
			SELECT
				t.target_id,
				t.property_short,
				t.active
			FROM target_relation tr
			INNER JOIN target t ON tr.target_id = t.target_id
			INNER JOIN target c ON tr.child_id = c.target_id
			INNER JOIN blackbox_type bt ON bt.blackbox_type_id = c.blackbox_type_id
			WHERE
				c.property_short = ?
				AND bt.name = 'CAMPAIGN'";

		$db = $this->getDatabaseInstance();
		$st = DB_Util_1::queryPrepared($db, $query, array($property_short));

		return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
	}

	/**
	 * @return array
	 * @see Blackbox_Models_View_Base::getColumns()
	 */
	public function getColumns()
	{
		static $columns = array(
			'target_id',
			'property_short',
			'active',
		);
		return $columns;
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
