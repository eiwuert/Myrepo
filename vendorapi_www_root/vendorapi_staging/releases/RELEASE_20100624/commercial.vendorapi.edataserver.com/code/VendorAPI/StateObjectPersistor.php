<?php

class VendorAPI_StateObjectPersistor implements VendorAPI_IModelPersistor
{
	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * @var int
	 */
	protected $cur_version;

	/**
	 * @var array
	 */
	protected $cache = array();

	/**
	 * @var array
	 */
	protected $map = array();

	/**
	 * @var array
	 */
	protected $mapped_models = array();


	public function __construct(VendorAPI_StateObject $state)
	{
		$this->state = $state;
	}

	/**
	 * Set the current version of this object to use
	 * @param Integer $version
	 * @return void
	 */
	public function setVersion($version)
	{
		$this->cur_version = $version;
	}

	/**
	 * Set the current version of this object to use
	 * @return Integer $version
	 */
	public function getVersion()
	{
		return $this->cur_version;
	}

	public function loadBy(DB_Models_IWritableModel_1 $model, array $where)
	{
		$found_model = FALSE;
		$table = $model->getTableName();

		if ($this->state->isPart($table))
		{
			$data = $this->state->{$table}->getData();
			$versions = $this->state->{$table}->getTableDataSince($this->cur_version);

			// support single parts for legacy state objects... all new
			// parts will be created as multiparts
			if (!$this->state->isMultiPart($table))
			{
				$versions = array($versions);
				$data = array($data);
			}

			foreach ($data as $index=>$row)
			{
				$hash = $this->getHash($model, $row, $index);

				if (isset($this->cache[$hash])
					&& $this->matchRow($this->cache[$hash]->getColumnData(), $where))
				{
					$found_model = $this->cache[$hash];
					break;
				}

				$row_model = clone $model;
				if ($this->hasFullKey($model, $row, FALSE))
				{
					$row_model->loadBy($this->getKey($model, $row));
				}

				$data = $row_model->getColumnData();
				if (isset($versions[$index]))
				{
					$data = array_merge($data, $versions[$index]);
					$row_model->setModelData($data);
					$row_model->setDataSynched();
				}

				if ($this->matchRow($data, $where))
				{
					$found_model = $row_model;
					$this->map[spl_object_hash($found_model)] = $index;
					$this->mapped_models[spl_object_hash($found_model)] = $found_model;
					$this->cache[$hash] = $found_model;
					break;
				}
			}
		}

		if (!$found_model)
		{
			$row_model = clone $model;

			// @todo this could potentially return a row that was modified in the state
			// object not to match the given conditions; this would be weeded out above,
			// but there's no way to prevent it from showing up again here... it also
			// wouldn't get updated with the state object changes... basically, this sucks
			if ($row_model->loadBy($where))
			{
				$hash = $this->getHash($model, $row);
				if (isset($this->cache[$hash]))
				{
					$found_model = $this->cache[$hash];
				}
				else
				{
					$found_model = $row_model;
					$this->cache[$hash] = $found_model;
				}
			}
		}

		return $found_model;
	}

	public function loadAllBy(DB_Models_IWritableModel_1 $model, array $where, $check_db = TRUE)
	{
		$matches = array();
		$found = array();

		// first, look for models in the database
		if ($check_db)
		{
			$list = $model->loadAllBy($where);
			foreach ($list as $row_model)
			{
				$hash = $this->getHash($row_model);

				// if we already have an instance of the same row, return
				// the same instance, as it's already up to date
				if (isset($this->cache[$hash]))
				{
					$matches[] = $this->cache[$hash];
				}
				else
				{
					$found[$hash] = $row_model;
				}
			}
		}

		$table = $model->getTableName();

		// second, merge in rows that exist in the state object -- if we have a
		// row that we also found in the database, we'll update it with unwritten
		// data from the state object and ensure that it still matches; this will
		// also pull in rows that only exist in the state object
		if ($this->state->isPart($table))
		{
			$data = $this->state->{$table}->getData();
			$versions = $this->state->{$table}->getTableDataSince($this->cur_version);

			// support single parts for legacy state objects... all new
			// parts will be created as multiparts
			if (!$this->state->isMultiPart($table))
			{
				$versions = array($versions);
				$data = array($data);
			}

			foreach ($data as $index=>$row)
			{
				$hash = $this->getHash($model, $row, $index);

				if (isset($found[$hash]))
				{
					$row_model = $found[$hash];
					unset($found[$hash]);
				}
				else
				{
					// this will pull in models that only exist in the state object,
					// or models that have been changed in the state object to match
					$row_model = clone $model;
					if ($this->hasFullKey($model, $row, FALSE))
					{
						$row_model->loadBy($this->getKey($model, $row));
					}
				}

				// bring the model up to date with state object changes
				$data = $row_model->getColumnData();
				if (isset($versions[$index]))
				{
					$data = array_merge($data, $row);
					$row_model->setModelData($data);
					$row_model->setDataSynched();
				}

				// models that matched in the database might have been modified
				// here, so we have to disqualify them
				if ($this->matchRow($data, $where))
				{
					$matches[] = $row_model;

					$this->map[spl_object_hash($row_model)] = $index;
					$this->mapped_models[spl_object_hash($row_model)] = $row_model;
					$this->cache[$hash] = $row_model;
				}
			}
		}

		// last, merge in any rows found in the database that didn't
		// have unwritten changes in the state object
		if (!empty($found))
		{
			foreach ($found as $hash=>$row_model)
			{
				$matches[] = $row_model;
				$this->cache[$hash] = $row_model;
			}
		}

		return $matches;
	}

	public function save(DB_Models_IWritableModel_1 $model)
	{
		if (!$this->hasFullKey($model))
		{
			throw new Exception('Cannot save model without a full key ' . print_r($model, TRUE));
		}

		$table = $model->getTableName();
		$data = $this->processReferenceData($model->getAlteredColumnData());

		if (isset($this->map[spl_object_hash($model)]))
		{
			// support non-multiparts for legacy state objects;
			// everything new will be created as a multipart
			if (!$this->state->isMultiPart($table))
			{
				foreach ($data as $key=>$value)
				{
					$this->state->{$table}->{$key} = $value;
				}
			}
			else
			{
				$index = $this->map[spl_object_hash($model)];
				$this->state->{$table}[$index] = $data;
			}
		}
		else
		{
			if (!$this->state->isPart($table))
			{
				$this->state->createMultiPart($table);
			}

			// have to include the primary key so we can load
			// this model from the database later...
			$data = array_merge($data, $this->getKey($model));
			$this->state->{$table}->append($data);

			// record where we're putting this, so we can save it again
			$index = $this->state->{$table}->highestIndex();
			$this->map[spl_object_hash($model)] = $index;
			$this->mapped_models[spl_object_hash($model)] = $model;

			$hash = $this->getHash($model, NULL, $index);
			$this->cache[$hash] = $model;
		}

		$this->state->updateVersion(TRUE);
		$model->setDataSynched();
	}

	protected function getHash(DB_Models_IWritableModel_1 $model, array $row = NULL, $index = NULL)
	{
		$hash = $model->getTableName()
			.':'.serialize($this->getKey($model, $row));

		if (!$this->hasFullKey($model, $row, FALSE))
		{
			$hash .= ':' . $index;
		}

		return sha1($hash);
	}

	protected function matchRow(array $row, array $where)
	{
		foreach ($where as $col=>$value)
		{
			if (!isset($row[$col])
				|| $row[$col] != $value)
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	protected function hasFullKey(DB_Models_IWritableModel_1 $model, array $row = NULL, $exclude_auto_increment = TRUE)
	{
		if (!$row)
		{
			$row = $model->getColumnData();
		}

		$ai = $model->getAutoIncrement();

		foreach ($model->getPrimaryKey() as $col)
		{
			if (empty($row[$col])
				&& ($col != $ai || !$exclude_auto_increment))
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	protected function getKey(DB_Models_IWritableModel_1 $model, array $row = NULL)
	{
		if (!$row)
		{
			$row = $model->getColumnData();
		}

		$ai = $model->getAutoIncrement();

		$key = array();
		foreach ($model->getPrimaryKey() as $col)
		{
			if ($col != $ai || !empty($row[$col]))
			{
				$key[$col] = $row[$col];
			}
		}
		return $key;
	}

	/**
	 * Handles any reference data parts..
	 */
	protected function processReferenceData(array $data)
	{
		foreach ($data as $key => $value)
		{
			if ($value instanceof VendorAPI_ReferenceColumn_Locator)
			{
				if (($v = $value->resolveReference()) !== FALSE)
				{
					$data[$key] = $v;
				}
				else
				{
					$m  = $value->getModel();
					$this->state->addReferencePart($m->getTableName(), $m->getColumnData());
				}
			}
		}
		return $data;
	}
}

?>
