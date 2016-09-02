<?php

abstract class ECash_Models_ObservableWritableModel extends DB_Models_ObservableWritableModel_1
{
	protected $AlterIndifference = false;
	/**
	 * @var bool
	 */
	private $is_readonly = FALSE;

	public function setAlterIndifference($value)
	{
		$this->AlterIndifference = $value;
	}
	public function getAlterIndifference()
	{
		return $this->AlterIndifference;
	}

	public function setDatabaseInstance(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}
	
	public function __set($name, $value)
	{
		$name_short = str_replace('_', '', $name);
		if(method_exists($this, 'set' . $name_short))
		{
			$this->{'set' . $name_short}($value);
		}
		else
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}
			elseif (!in_array($name, $this->getColumns()))
			{
				throw new Exception("'$name' is not a valid column for table '".$this->getTableName()."'.");
			}

			if ($this->column_data[$name] !== $value || $this->AlterIndifference)
			{
				$old = $this->{$name};
				$this->column_data[$name] = $value;
				$this->altered_columns[$name] = $name;

				// update our observers to the change
				$event = new stdClass();
				$event->type = self::EVENT_VALUES;
				$event->column = $name;
				$event->old = $old;
				$event->new = $value;

				$this->notifyObservers($event);
			}
		}
	}

	public function __get($name)
	{
		$name_short = str_replace('_', '', $name);
		if(method_exists($this, 'get' . $name_short))
		{
			return $this->{'get' . $name_short}();
		}
		else
		{
			return parent::__get($name);
		}
	}

	public function __isset($name)
	{
		$name_short = str_replace('_', '', $name);
		if(method_exists($this, 'get' . $name_short))
		{
			//this may be too simplistic
			return TRUE;
		}
		else
		{
			return parent::__isset($name);
		}
	}
}

?>
