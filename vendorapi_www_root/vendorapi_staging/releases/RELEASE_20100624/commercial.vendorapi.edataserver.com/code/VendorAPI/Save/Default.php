<?php

/**
 *
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_Save_Default implements VendorAPI_Save_ITableHandler
{
		/**
	 * @var ECash_VendorAPI_Driver
	 */
	protected $driver;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	public function __construct(VendorAPI_IDriver $driver, $table, DB_IConnection_1 $db)
	{
		$this->driver = $driver;
		$this->table = $table;
		$this->db = $db;
	}

	public function saveTo(array $data, DB_Models_Batch_1 $batch)
	{
		$model = $this->getModel();

		foreach ($data as $field=>$value)
		{
			if ($value instanceof VendorAPI_ReferenceColumn_Locator)
			{
				$value->setDatabase($this->db);
				$value = $data[$field] = $value->resolveReference();
			}

			// we'll override the actual value below,
			// but this marks the field as modified
			$model->{$field} = $value;
		}

		// hack to set the data because we store it in
		// the state object in the database format (how it's
		// returned from getColumnData()) to transparently
		// take advantage of encryption, etc.
		$model->setModelData(
			array_merge($model->getColumnData(), $data)
		);
		$batch->save($model);
	}

	public function getModel()
	{
		return  $this->driver->getDataModelByTable($this->table, $this->db);
	}

}
