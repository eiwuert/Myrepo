<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginStrings
{
	private $lumRegistry;
	private $string_table = array();
	private $custom_string_table = array();
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceStringsModel($registry->db); // model needs database access
	}

	public function add($strings)
	{
		if (is_array($strings) && count($strings) > 0)
		{
			$this->string_table = array_merge($this->string_table, $strings);
		}
	}
	
	public function addCustom($strings)
	{
		if (is_array($strings) && count($strings) > 0)
		{
			$this->custom_string_table = array_merge($this->custom_string_table, $strings);
		}
	}	
	
	public function getStringTable()
	{
		return $this->string_table;
	}
	
	public function getCustomStringTable()
	{
		return $this->custom_string_table;
	}	
	
	public function load()
	{
		$this->string_table = $this->model->load($this->lumRegistry->language->lang_code);
	}
	
	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Strings\All',
			'Strings\View',
			'Strings\Edit',
			'Strings\Delete'
		);
		
		/*
		  
			will store permissions in the database like this
	
			$perms = array('Users\Accounts\Super User',
						   'Users\Accounts\View',
						   'Users\Roles\Add');
			
			$perms_enc = base64_encode(serialize($perms));
		
		*/
	}	
	
	// ===== RPC Methods ===== //
	public function get($params)
	{
		if (!lum_requirePermission('Strings\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->get($params);
	}
	
	public function getLocalized($params)
	{
		if (!lum_requirePermission('Strings\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getLocalized($params);
	}	
	
	public function getByCode($params)
	{
		return $this->model->getByCode($params);
	}	
	
	public function update($params)
	{
		if (!lum_requirePermission('Strings\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		lum_clearPageCache();
		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		if (!lum_requirePermission('Strings\View', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Strings\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		lum_clearPageCache();
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $string_id)
			{
				$params['string_id'] = $string_id;
				if (!$this->model->delete($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->delete($params);
		}
	}	
}

class LuminanceStringsModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."strings";
		$this->_key = "string_id";
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
		
		$params['text'] = lum_htmlEncode(stripslashes($params['text']), false);
		$params = $this->getLocalizedStrings($params);
		
		if ($new)
			$success = parent::insert($params);
		else
			$success = parent::update($params);
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}
	
	private function getLocalizedStrings($params)
	{
		list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
		foreach ($langs as $lang)
		{
			if ($lang['def'])
				continue;
			
			$params['use_lang'] = $lang['lang_code'];
			$params[$lang['lang_code'].'-text'] = lum_htmlEncode(stripslashes($params[$lang['lang_code'].'-text']), false);
		}
		return $params;
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
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",$path."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",$file."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",getcwd()."\n",FILE_APPEND);
		if (!is_dir($path))
		{
			mkdir($path, 0775);
		}
		chmod($path, 0775);
		$fp = fopen($path.'/'.$file,"w");
		if ($fp)
		{
			if (flock($fp, LOCK_EX+LOCK_NB)) {
				fputs($fp, $strings);
				flock($fp, LOCK_UN);
			}		    
			fclose($fp);
		}  
		chmod($path.'/'.$file, 0664);
	}
}

?>
