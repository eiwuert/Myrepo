<?php

/**
 * Adapts DB_IStatement_1 to MySQLi_Result_1
 *
 * Do not use unless absolutely necessary! Also note that some features
 * are disabled (seeking, getting an array of fields, etc.)
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 *
 */
class ECash_Legacy_MySQLiResultAdapter extends MySQLi_Result_1
{
	/**
	 * @var DB_IStatement_1
	 */
	protected $st;

	public function __construct(DB_IStatement_1 $st)
	{
		$this->st = $st;
	}

	public function Fetch_Row()
	{
		return $this->st->fetch(DB_IStatement_1::FETCH_ROW);
	}

	public function Fetch_Array_Row($result_type = NULL)
	{
		switch ($result_type)
		{
			case MYSQLI_ASSOC:
				return $this->st->fetch(DB_IStatement_1::FETCH_ASSOC);
			case MYSQLI_NUM:
				return $this->st->fetch(DB_IStatement_1::FETCH_ROW);
		}

		return $this->st->fetch(DB_IStatement_1::FETCH_BOTH);
	}

	public function Row_Count()
	{
		return $this->st->rowCount();
	}

	public function Fetch_Object_Row()
	{
		return $this->st->fetch(DB_IStatement_1::FETCH_OBJ);
	}

	public function Seek_Row($row_num)
	{
		throw new BadMethodCallException('Adapter cannot seek');
	}

	public function Field_Count()
	{
		throw new BadMethodCallException('Adapter cannot return field count');
	}

	public function Get_Fields()
	{
		throw new BadMethodCallException('Adapter cannot return field list');
	}

	public function Close()
	{
		return TRUE;
	}
}

?>