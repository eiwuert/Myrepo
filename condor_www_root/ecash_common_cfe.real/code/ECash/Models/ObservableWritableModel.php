<?php

abstract class ECash_Models_ObservableWritableModel extends DB_Models_ObservableWritableModel_1
{
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
			parent::__set($name, $value);
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
