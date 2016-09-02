<?php

// for language aware plugins
define ('INVARIANT', 0);
define ('LOCALIZED', 1);

class LuminanceModel
{
	protected $_table;
	protected $_key;
	protected $_db;
	protected $_requiredParams = array();
	private $_error;
	protected $_localized = false;
	public $sql_query = '';
	
	public function __construct()
	{
	}
	
	protected function setRequiredParams($required)
	{
		$this->_requiredParams = $required;
	}
	
	protected function addRequiredParam($param)
	{
		$this->_requiredParams[] = $param;
	}
	
	protected function removeRequiredParam($param)
	{
		$index = array_search($param, $this->_requiredParams);
		if ($index !== false)
		{
			unset($this->_requiredParams[$index]);
		}
	}	
	
	protected function setError($value, $func, $sql = 'NO SQL', $value_array = 'NO VALUE ARRAY')
	{
		$this->_error = 'Error in Class: '.__CLASS__.' Function: '.$func.' Error: '.$value;
		lum_logMe($sql);
		lum_logVar($value_array);
	}
	
	public function getError()
	{
		return $this->_error;
	}

	// *** CRUD FUNCTIONS *** //
	protected function insert($params, $use_identity = true, $table = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
	
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}
			
		if (!$table)
			$table = $this->_table;

		$sql = 'insert into '.$table.' ';
		$fields = '';
		$ques = '';
		$c = 0;
		$value_array = array();

		$localize_arr = array();

		foreach ($this->_requiredParams as $param=>$localize)
		{
			if (gettype($param) == 'integer')
			{
				$param = $localize;
				$localize = false;
			}
				
			if ($localize)
			{
				$localize_arr[] = $param;
			}
			else
			{
				if ($c > 0)
				{
					$fields .= ',';
					$ques .= ',';
				}
				$fields .= "`$param`";
				$ques .= '?';
				$value_array[] = $params[$param];
				$c++;
			}
			
		}
		
		$sql .= '('.$fields.') VALUES ('.$ques.')';

		if ($use_identity)
		{
			$id = $this->_db->doInsert($sql, $value_array);
			if (!$id)
			{
				$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
				return false;
			}
			$params[$this->_key] = $id;
			
		}
		else
		{
			if ($this->_db->doQuery($sql, $value_array) === false)
			{
				$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
				return false;
			}
		}
		
		// we can only do localized content when using identity
		if ($this->_localized)
		{
			$localize_arr[] = $this->_key;
			$this->doLocalized($localize_arr, $params, $table);
		}
			
		return $params[$this->_key];
		
		
	}


	// we parse out the localized content then pass it into insertLocalized
	protected function doLocalized($localized_arr, $params, $table)
	{
		$local_params = array();
		
		// first the default language
		foreach ($params as $param=>$value)
		{
			if (in_array($param, $localized_arr))
			{
				$local_params[$param] = $value;
			}
		}
		
		if (count($local_params) == count($localized_arr))
		{
			$this->updateLocalized($local_params, $params, $table);
		}

		// now the rest of the languages
		$last_lang = null;
		$local_params = array();
		foreach ($params as $param=>$value)
		{
			if (strpos($param, '-') !== false)
			{
				list($lang_code, $field) = explode('-', $param);
				
				if ($lang_code != $last_lang)
				{
					if (count($local_params) == count($localized_arr))
					{
						
						$this->updateLocalized($local_params, $params, $table);
					}
					$local_params = array();
					$local_params['lang_code'] = $lang_code;
					$local_params[$this->_key] = $params[$this->_key];
					$last_lang = $lang_code;
				}
				$local_params[$field] = $value;
			}
		}
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($local_params,true)."\n",FILE_APPEND);
 		
		if (count($local_params) == count($localized_arr))
		{
			$this->updateLocalized($local_params, $params, $table);
		}	
	}

	protected function insertLocalized($params, $table)
	{
		$sql = 'insert into '.$table.'_localized ';
		$fields = '';
		$ques = '';
		$c = 0;
		$value_array = array();
		
		foreach ($params as $param=>$value)
		{
			if ($c > 0)
			{
				$fields .= ',';
				$ques .= ',';
			}
			$fields .= "`$param`";
			$ques .= '?';
			$value_array[] = $params[$param];
			$c++;
		}
		
		$sql .= '('.$fields.') VALUES ('.$ques.')';

//var_dump($sql);
//var_dump($value_array);

		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		return true;
	}

	protected function localizedExists($params, $table)
	{
		$sql = 'select '.$this->_key.' from '.$table.'_localized where '.$this->_key.' = ? and lang_code = ?';
		$value_array = array($params[$this->_key], $params['lang_code']);
		
		$test = $this->_db->getRow($sql, $value_array);
		if (!$test || count($test) == 0)
			return false;
		
		return true;
	}

	protected function updateLocalized($local_params, $params, $table)
	{
		
		if (!$this->localizedExists($local_params, $table))
			$this->insertLocalized($local_params, $table);
		
		$sql = 'update '.$table.'_localized set';
		$c = 0;
		$value_array = array();
		
		foreach ($local_params as $param=>$value)
		{
			if ($param == $this->_key || $param == 'lang_code')
				continue;
				
			if ($c > 0)
			{
				$sql .= ',';
			}
			$sql .= "`$param` = ?";
			$value_array[] = $value;
			$c++;
		}
		
		$sql .= ' where '.$this->_key.' = \''.$params[$this->_key].'\' and lang_code = ?';
		$value_array[] = $local_params['lang_code'];
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($sql,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($value_array,true)."\n",FILE_APPEND);
		
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		return true;
	}

	protected function update($params, $table = null, $key = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		
		// make sure the key is required
		if (!$key)
			$key = $this->_key;
			
		$this->addRequiredParam($key);
		
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}
			
		if (!$table)
			$table = $this->_table;			

		//==================================			
		$sql = 'update '.$table.' set ';
		$c = 0;
		$value_array = array();
		
		$localize_arr = array();

		foreach ($this->_requiredParams as $param=>$localize)
		{
			if (gettype($param) == 'integer')
			{
				$param = $localize;
				$localize = false;
			}
				
			if ($localize)
			{
				$localize_arr[] = $param;
			}
			else
			{
				if ($param == $key)
					continue;
					
				if ($c > 0)
				{
					$sql .= ',';
				}
				$sql .= "`$param` = ?";
				$value_array[] = $params[$param];
				$c++;
			}
		}			
		//==================

		$sql .= ' where '.$key.' = \''.$params[$key].'\'';
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($sql,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($value_array,true)."\n",FILE_APPEND);
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		$localize_arr[] = $key;
		$this->doLocalized($localize_arr, $params, $table);
		return true;
	}
		
	protected function delete($params, $table = null, $key = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		
		// make sure the key is required
		if (!$key)
			$key = $this->_key;
			
		$this->addRequiredParam($key);
		
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}

		if (!$table)
			$table = $this->_table;
			
		$sql = 'delete from '.$table.' where '.$key.' = ?';
		$value_array = array($params[$key]);
		
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		return true;
	}

	protected function get($params, $table = null, $key = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		
		// make sure the key is required
		if (!$key)
			$key = $this->_key;
			
		$this->addRequiredParam($key);
		
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}

		if (!$table)
			$table = $this->_table;
		
		$value_array = array();
		$where = '';
		$join = '';
		if ($this->_localized && isset($params['lang_code']))
		{
			// we need to join the localized table
			$join = 'left join '.$table.'_localized on '.$table.'_localized.'.$key.' = c.'.$key.' and '.$table.'_localized.lang_code = ?';
			$value_array[] = $params['lang_code'];
		}
		
		// if you're going to pass these in make sure to reference the main plugin table as 'c'
		if (isset($params['join']))
		{
			$join .= ' '.$params['join'];
		}		
		
		$select = '*';
		if (isset($params['select']))
		{
			$select = $params['select'];
		}
					
			
		$sql = 'select '.$select.' from '.$table.' c '.$join.' where c.'.$key.' = ?';
		
		$value_array[] = $params[$key];
		$row = '';
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",$sql."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($value_array,true)."\n",FILE_APPEND);

		$row = $this->_db->getRow($sql, $value_array, true);

		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}

		return $row;
	}
	
	protected function getLocalized($params, $table = null, $key = null)
	{
		if (!$this->_localized)
			return array();
			
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		
		// make sure the key is required
		if (!$key)
			$key = $this->_key;
			
		$this->addRequiredParam($key);
		
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}

		if (!$table)
			$table = $this->_table;
		
		$value_array = array();
		$where = '';
		$join = '';
			
		$sql = 'select * from '.$table.'_localized c '.$join.' where c.'.$key.' = ?';
		
		$value_array[] = $params[$key];
		$row = '';
		$row = $this->_db->getRows($sql, $value_array, true);
		
		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		return $row;
	}	
	
	protected function changeStatus($params, $table = null, $key = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		
		if (!$key)
			$key = $this->_key;
			
		$this->addRequiredParam($key);
		
		$retVal = $this->_checkParams($params);
		if ($retVal)
		{
			$this->setError($retVal, __FUNCTION__);
			return false;
		}
		
		if (!$table)
			$table = $this->_table;		
		
		$sql = 'update '.$table.' set status = ? where '.$key.' = ?';
		$value_array = array($params['status'], $params[$key]);
		
		$this->sql_query = $sql;
		
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		return true;
	}
	
	protected function getList($params, $table = null)
	{
		if (!$this->_db)
		{
			$this->setError("Invalid database handle");
			return false;
		}
		$value_array = null;
		$limit = "";
		
		if (isset($params['start']) && isset($params['limit']))
			$limit = " limit ".$params['start']. ", ".$params['limit'];
			
		$dir = "";
		if (isset($params['dir']))
		{
			if (strtoupper($params['dir']) == "ASC")
				$dir = "ASC";
			else 
				$dir = "DESC";
		}
		
		$order = "";
		if (isset($params['sort']))
		{
			$order = "order by ".addslashes($params['sort']);
		}

		$join = '';
		$where = "";
		if (isset($params['status']) && $params['status'] != '')
		{
			$where = "where t.status = ".addslashes($params['status']);
		}
		
		if (isset($params['where']) && $params['where'] != '')
		{
			$where .= ($where == '' ? ' where ' : ' and ');
			$where .= $params['where'];
		}		
		
		if (isset($params['filters']))
		{
			$where .= ($where == '' ? ' where ' : ' and ');
			$where .= $params['filters'];
				
			if (isset($params['values']))
			{
				if ($value_array == null)
				{
					$value_array = $params['values'];
				}
				else
				{
					foreach ($params['values'] as $value)
					{
						$value_array[] = $value;
					}
				}
			}
		}
		
		if (!$table)
			$table = $this->_table;		
		
		$join = '';
		if ($this->_localized)
		{
			// we need to join the localized table
			$join = 'left join '.$table.'_localized on '.$table.'_localized.'.$this->_key.' = t.'.$this->_key;
		}		
		
		if (isset($params['join']))
		{
			$join .=  ' '.$params['join'];
		}		
		
		$select = '*';
		if (isset($params['select']))
		{
			$select = $params['select'];
		}
		
		$sql = 'SELECT SQL_CALC_FOUND_ROWS '.$select.' from '.$table.' t '.$join.' '.$where.' '.$order.' '.$dir.' '.$limit;
		
		
		$this->sql_query = $sql;
		
//echo "<!-- $sql -->";
		$rows = $this->_db->getRows($sql, $value_array, true);
		if (!$rows)
		{
			$this->setError($this->_db->getError());
			return array(null, 0);
		}
		
		$sql = "SELECT FOUND_ROWS() as num_rows";
		$num = $this->_db->getRow($sql, null, true);	
		
		return array($rows, $num['num_rows']);			
	}

	protected function _checkParams($params)
	{
		$bOk = true;
		if (!is_array($params))
			return "Parameters are invalid";
			
		foreach ($this->_requiredParams as $param=>$localize)
		{
			if (gettype($param) == 'integer')
			{
				$param = $localize;
				$localize = false;
			}

			if (array_key_exists($param, $params))
			{
				continue;
			}
			else 
			{
				$bOk = false;
				break;
			}
		}
		if (!$bOk)
		{
			return "Parameter: $param is invalid";
		}
		else 
		{
			return null;
		}
	}	
}

?>
