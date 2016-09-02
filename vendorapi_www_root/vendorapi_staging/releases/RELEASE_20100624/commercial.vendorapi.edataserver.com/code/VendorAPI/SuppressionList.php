<?php
/**
 * VendorAPI Suppression List
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_SuppressionList 
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var int
	 */
	protected $company_id;

	public function __construct(DB_IConnection_1 $db, $company_id)
	{
		$this->db = $db;
		$this->company_id = $company_id;
	}

	/**
	 *  Loads ALL Active ID's in the database
	 * 	@todo replace
	 */
	public function getListIDs()
	{
		$items = array();
		/**
		* Load Suppression List
		*/
		$list_model = new ECash_Models_SuppressionLists($this->db);
		$rows = $list_model->loadAllBy(array("active" => 1, "company_id" => $this->company_id));
		if($rows)
		{
			foreach($rows as $row_item)
			{
				$items[] = $row_item->list_id;
			}
		}

		return $items;

	}

	/**
	 *  Loads ALL Active Field Names in the database
	 * 	@todo replace
	 */
	public function getListFieldNames()
	{
		$items = array();
		/**
		* Load Suppression List
		*/
		$list_model = new ECash_Models_SuppressionLists($this->db);
		$rows = $list_model->loadAllBy(array("active" => 1, "company_id" => $this->company_id));

		foreach($rows as $row_item)
		{
			$items[] = $row_item->field_name;
		}

		return $items;

	}

	public function getListByName($name, $type = NULL)
	{
		$criteria = array( "name" => $name );
		if (!is_null($type))
		{
			$criteria['type'] = $type;
		}
		return $this->getListUsingCriteria($criteria);
	}

	private function getListUsingCriteria($criteria)
	{
		$list_collections = array();

		/**
		 * Collection of ECash Suppression Models
		 */

		$model_collection = $this->getModelCollection();
		/**
		 * Load Suppression List
		 */
		$criteria["active"] = 1;
		if($model_collection["SuppressionLists"]->loadBy($criteria))
		{
			$list_id = $model_collection["SuppressionLists"]->list_id;

			$list_collections[$list_id] = array();
			$list_collections[$list_id]["Name"] = $model_collection["SuppressionLists"]->name;
			$list_collections[$list_id]["Field"] = $model_collection["SuppressionLists"]->field_name;
			$list_collections[$list_id]["Description"] = $model_collection["SuppressionLists"]->description;
			$list_collections[$list_id]["LoanAction"] = $model_collection["SuppressionLists"]->loan_action;

				/**
				 * Populate List Values
				 */

				$list_collections[$list_id]["Values"] = $this->getListValues($list_id, $model_collection["SuppressionListValues"]);

				/*
				 *  Create Suppression List Object from Values
				 */
				$list_collections[$list_id]["SuppressionList"] = new TSS_SuppressionList_1($list_collections[$list_id]["Values"]);

		}

		return $list_collections;
	}

	protected function getModelCollection()
	{

		$models = array(
			"SuppressionLists",
			"SuppressionListRevisions",
			"SuppressionListRevisionValues",
			"SuppressionListValues"
		);

		$model_item = array();

		foreach($models as $model)
		{
			$class = 'ECash_Models_'.$model;
			$model_item[$model] = new $class($this->db);
		}
		return $model_item;
	}

	/**
	 * getListValues from Revision
	 *
	 * @param unknown_type $list_id
	 * @param ECash_Models_SuppressionListValue $value_list
	 * @return arrau
	 */
	protected function getListValues($list_id, $value_list)
	{
		$values = array();
		foreach($value_list->loadAllValuesForListID($list_id) as $value_list)
		{
			$values[$value_list->value_id] = $value_list->value;

		}
		return $values;
	}

}
?>