<?php
/**
 * Target database model
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_Target extends Blackbox_Models_WriteableModel implements Blackbox_Models_IReadableTarget
{
	/**
	 * Loads the target by property short and blackbox type ID
	 *
	 * The blackbox type ID is required since the target model is used for targets,
	 * campaigns, and target collections.
	 *
	 * @param string $property_short
	 * @param int $blackbox_type_id
	 * @return bool
	 */
	public function loadByPropertyShort($property_short, $blackbox_type_id)
	{
		return $this->loadBy(array(
			'property_short' => $property_short,
			'blackbox_type_id' => (int)$blackbox_type_id
		));
	}
	
	/**
	 * Loads a target row model from the campaign ID of a related campaign.
	 *
	 * @param int $campaign_id
	 * @return void
	 */
	public function getCampaignTarget($campaign_id)
	{
		$query = "
			SELECT
				t.*
			FROM
				target_relation tr
				INNER JOIN target t
					ON tr.child_id = t.target_id
			WHERE
				tr.target_id = ?
			LIMIT 1";
		
		if (FALSE !== ($row = DB_Util_1::querySingleRow($this->db, $query, array((int)$campaign_id))))
		{
			$this->fromDbRow($row);
			return TRUE;
		}
		
		return FALSE;
	}
		
	/**
	 * Produces a list of Target models from a subquery string which must include
	 * a target_id in it's field list.
	 *
	 * @param string $subquery
	 * @return Iterator of Blackbox_Models_Target objects.
	 */
	public function fromTargetIdSubquery($subquery)
	{
		$query = "
			SELECT target.* FROM target
			JOIN ($subquery) AS t ON t.target_id = target.target_id
			ORDER BY target.property_short";
		
		return $this->factoryIterativeModel(
			DB_Util_1::queryPrepared($this->db, $query), $this->db
		);
	}
	
	/**
	 * Returns an "AND" queryset, which is .. rule filters are added in AND fashion.
	 *
	 * @return Blackbox_Models_TargetRuleQueryset
	 */
	public function getAndRuleQueryset()
	{
		return $this->getQueryset(OLP_DB_WhereGlue::AND_GLUE);
	}

	/**
	 * Returns an "OR" queryset, which is .. rule filters are added in OR fashion.
	 *
	 * @return Blackbox_Models_TargetRuleQueryset
	 */
	public function getOrRuleQueryset()
	{
		return $this->getQueryset(OLP_DB_WhereGlue::OR_GLUE);
	}
	
	/**
	 * Makes a Blackbox_Models_TargetRuleQueryset.
	 *
	 * @param string $glue OLP_DB_WhereGlue constant representing OR or AND.
	 * @return Blackbox_Models_TargetRuleQueryset
	 */
	protected function getQueryset($glue)
	{
		return new Blackbox_Models_TargetRuleQueryset($this, $glue);
	}

	/**
	 * Returns an array of column names
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'target_id',
			'target_collection_class_id',
			'weight_class_id',
			'property_short',
			'name',
			'lender_id',
			'active',
			'deleted',
			'date_modified',
			'date_created',
			'date_effective',
			'company_id',
			'blackbox_type_id',
			'rule_id',
			'paydate_minimum',
			'list_mgmt_nosell',
			'reference_data',
			'lead_cost'
		);
		return $columns;
	}

	/**
	 * Returns an array of primary keys
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_id');
	}

	/**
	 * Returns the auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_id';
	}

	/**
	 * Returns the table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target';
	}

	/**
	 * Returns an array of table data
	 *
	 * This is used for inserting and updating the table.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
		$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
		$column_data['date_effective'] = date('Y-m-d H:i:s', $column_data['date_effective']);
		return $column_data;
	}

	/**
	 * Returns an array of table data
	 *
	 * This is used for retrieving the data from the database and passing it to the application.
	 *
	 * @param unknown_type $data
	 * @return void
	 */
	protected function setColumnData($data)
	{
		$this->column_data = $data;
		$this->column_data['date_modified'] = strtotime($data['date_modified']);
		$this->column_data['date_created'] = strtotime($data['date_created']);
		$this->column_data['date_effective'] = strtotime($data['date_effective']);
	}
}
?>
