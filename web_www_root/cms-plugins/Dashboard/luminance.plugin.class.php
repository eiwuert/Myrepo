<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginDashboard
{
	private $lumRegistry;
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceDashboardModel($registry->db); // model needs database access
	}
	
	// used when display the roles tool
	public function getPermissionTypes()
	{
		// none here, everyone can see the Dashboard!
		return array(
		);

	}	
	
	// ===== RPC Methods ===== //
}

class LuminanceDashboardModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."dashboard";
		$this->_key = "dash_id";
		$this->_localized = true;
	}
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'string_code',
			'string_id',
			'text'=>LOCALIZED,
			'lang_code'=>LOCALIZED
		));
		
		$new = $params['new'];
		
		if ($new)
			$success = parent::insert($params);
		else
			$success = parent::update($params);
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	public function get($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'string_id'
		));
		return parent::get($params);
	}
	
	public function getLocalized($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'string_id'
		));
		return parent::getLocalized($params);
	}	

	public function getByCode($params)
	{
		parent::setRequiredParams(array
		(
			'string_code',
			'lang_code'
		));

		if (!$this->_db)
			return $this->setError("Invalid database handle");	
			
		
		$sql = "select * from ".$this->_table." c left join ".$this->_table."_localized l on l.string_id = c.string_id where l.lang_code = ? and c.string_code = ?";
		
		$value_array = array($params['lang_code'], $params['string_code']);
		
		$row = $this->_db->getRow($sql, $value_array);
		
		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		return $row->text;
	}		
	
	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'string_id'
		));
		
		// we're going to remove this plugin
		
		return parent::delete($params);
	}		
	
	public function getList($params, $table = null)
	{
		$params['filters'] = null;
		$params['values'] = array();
		if (isset($params['lang_code']))
		{
			$params['filters'] .= ' lang_code = ? ';
			$params['values'][] = $params['lang_code'];
		}
		if (isset($params['query']) && $params['query'] != '')
		{
			$params['where'] = "(string_code like '%%".addslashes($params['query'])."%%' or text like '%%".addslashes($params['query'])."%%')";
		}
		
		return parent::getList($params);
	}
	
	public function load($lang_code)
	{
		$lang_path = LANG_CACHE_PATH.$lang_code;
		$lang_file = "lang.php";
				
		if (is_file($lang_path.'/'.$lang_file))
		{
			$strings = file_get_contents($lang_path."/".$lang_file);
			return unserialize($strings);
		}
		else 
		{
			return $this->build($lang_path, $lang_file, $lang_code);
		}		
	}
	
	private function build($path, $file, $lang_code)
	{
 		list($string_list, $count) = $this->getList(array('lang_code'=>$lang_code));
		
		$strings = array();
		foreach ($string_list as $string)
		{
			$strings[$string['string_code']] = $string['text'];
		}

		$this->save($path, $file, $strings);
		
		return $strings;
	}
	
	private function save($path, $file, $strings)
	{
		$strings = serialize($strings);
	
		if (!is_dir($path))
		{
			mkdir($path, 0775);
		}
		
		$fp = fopen($path.'/'.$file,"w");
		if ($fp)
		{
			if (flock($fp, LOCK_EX+LOCK_NB)) {
				fputs($fp, $strings);
				flock($fp, LOCK_UN);
			}		    
			fclose($fp);
		}  
		chmod($path.'/'.$file, 0644);
	}
}

?>
