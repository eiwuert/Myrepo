<?php

/**
 * Loads suppression lists from the database
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_SuppressionList_DBLoader implements VendorAPI_SuppressionList_ILoader
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}
	
	/**
	 * Returns an array of suppression lists by the given name and type.
	 *
	 * @param string $name
	 * @param string $type
	 * @return VendorAPI_SuppressionList_Wrapper
	 */
	public function getByName($name, $type = NULL)
	{
		$criteria = array( "name" => $name );
		if (!is_null($type))
		{
			$criteria['type'] = $type;
		}

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
			$values = $model_collection["SuppressionListValues"]->loadAllValuesForListID($model_collection['SuppressionLists']->list_id);
			return new VendorAPI_SuppressionList_Wrapper(
				new TSS_SuppressionList_2($this->getListValues($values)),
				$model_collection['SuppressionLists']->name,
				$model_collection['SuppressionLists']->loan_action,
				$model_collection['SuppressionLists']->field_name,
				$model_collection['SuppressionLists']->list_id
			);
		}

		return NULL;
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
	 * @param array $value_list
	 * @return array
	 */
	protected function getListValues($value_list)
	{
		$values = array();
		foreach($value_list as $value)
		{
			$values[$value->value_id] = $value->value;

		}
		return $values;
	}
}

?>