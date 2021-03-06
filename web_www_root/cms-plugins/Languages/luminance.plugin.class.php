<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginLanguages
{
	private $lumRegistry;
	private $lang_code;
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceLanguageModel($registry->db); // model needs database access
	}

	public function setLanguage($bits)
	{
		if (is_array($bits) && count($bits) > 0)
		{
			list($lang_code, $default) = $this->model->getByCode(array('lang_code'=>$bits[0]));
			if ($lang_code)
			{
				// yes it was a lang code!
				$this->lumRegistry->language = (object)array('lang_code'=>$lang_code, 'is_default'=>(bool)$default, 'default'=>$this->model->getDefaultLanguage());
				$bits = array_slice($bits, 1);
				return $bits;
			}

			// the bit was not a language code so let's find the default
			if ($this->setDefaultLanguage())
				return $bits;
			
			// could not find the default language so we'll just say it's English
			$this->lumRegistry->language = (object)array('lang_code'=>'en', 'is_default'=>true);
		}
		return $bits;
	}
	
	public function setDefaultLanguage()
	{
		$lang_code = $this->model->getDefaultLanguage();
		if ($lang_code)
		{
			$this->lumRegistry->language = (object)array('lang_code'=>$lang_code, 'is_default'=>true, 'default'=>$lang_code);
			return true;
		}
		return false;
	}
	
	public function getLanguageByCode($params)
	{
		return $this->model->getLanguageByCode($params);
	}	
	
	public function getDefaultLanguage()
	{
		return $this->model->getDefaultLanguage();
	}	
	
	public function getLangCode()
	{
		return $this->lang_code;
	}

	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Languages\All',
			'Languages\View',
			'Languages\Add',
			'Languages\Edit',
			'Languages\Delete',
			'Languages\Change Status'
		);
		
		/*
		  
			will store permissions in the database like this
	
			$perms = array('Users\Accounts\Super User',
						   'Users\Accounts\View',
						   'Users\Roles\Add');
			
			$perms_enc = base64_encode(serialize($perms));
		
		*/
	}
	
	// RPC Methods
	public function get($params)
	{
		if (!lum_requirePermission('Languages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		

		return $this->model->get($params);
	}
	
	public function update($params)
	{
		if (!lum_requirePermission('Languages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		

		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Languages\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $lang_id)
			{
				$params['lang_id'] = $lang_id;
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
	
	public function deactivate($params)
	{
		if (!lum_requirePermission('Languages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 0;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $lang_id)
			{
				$params['lang_id'] = $lang_id;
				if (!$this->model->changeStatus($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->changeStatus($params);
		}
	}	
	
	public function activate($params)
	{
		if (!lum_requirePermission('Languages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 1;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $lang_id)
			{
				$params['lang_id'] = $lang_id;
				
				if (!$this->model->changeStatus($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->changeStatus($params);
		}
	}
	
	public function setDefault($params)
	{
		if (!lum_requirePermission('Languages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->setDefault($params);
	}
	
}

class LuminanceLanguageModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."languages";
		$this->_key = "lang_id";		
	}

	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'lang_code',
			'language',
			'def',
			'status',
			'lang_id'
		));
		
		$new = $params['new'];
		
		if ($new)
		{
			$success = parent::insert($params);
			if ($success)
				$params['lang_id'] = $success;
		}
		else
		{
			$success = parent::update($params);
		}
		
		if ($params['def'] == 1)
			$this->setDefault($params);
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	public function get($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'lang_id'
		));
		return parent::get($params);
	}	

	public function getLanguageByCode($params)
	{
		parent::setRequiredParams(array
		(
			'lang_code'
		));

		if (!$this->_db)
			return $this->setError("Invalid database handle");	
			
		$sql = "select * from ".$this->_table." where lang_code = ?";
		
		$value_array = array($params['lang_code']);
		$row = $this->_db->getRow($sql, $value_array);
		
		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		
		if (count($row) == 0)
			return false;
	
		return $row->language;
	}

	public function getByCode($params)
	{
		parent::setRequiredParams(array
		(
			'lang_code'
		));

		if (!$this->_db)
			return $this->setError("Invalid database handle");	
			
		$sql = "select * from ".$this->_table." where lang_code = ?";
		
		$value_array = array($params['lang_code']);
		$row = $this->_db->getRow($sql, $value_array);
		
		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		
		if (count($row) == 0)
			return false;
	
		return array($row->lang_code, $row->def);
	}		
	
	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'lang_id'
		));
		return parent::delete($params);
	}		
	
	public function getList($params = array(), $table = null)
	{
		parent::setRequiredParams(array());
		return parent::getList($params);
	}	
	
	public function getDefaultLanguage()
	{
		if (!$this->_db)
			return $this->setError("Invalid database handle");	
			
		$this->_sql = "select lang_code from ".$this->_table." where def = 1";
		$row = $this->_db->getRow($this->_sql, null);
		if (!$row)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		return $row->lang_code;
	}
	
	public function setDefault($params)
	{
		if (!$this->_db)
			return $this->setError("Invalid database handle");		
			
		parent::setRequiredParams(array
		(
			'lang_id'
		));

		$this->_sql = "update ".$this->_table." set def = 0 where lang_id <> ?";
		$value_array = array($params['lang_id']);
		if ($this->_db->doQuery($this->_sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		
		$this->_sql = "update ".$this->_table." set def = 1 where lang_id = ?";
		$value_array = array($params['lang_id']);
		if ($this->_db->doQuery($this->_sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		
		return true;
	}	

	public function getLanguagesInUse($params)
	{
		if (!$this->_db)
			return $this->setError("Invalid database handle");		
			
		parent::setRequiredParams(array
		(
			'lang_id'
		));

		$this->_sql = "select * from ".$this->_table." where status = 1 order by language";
		$value_array = null;
		$rows = $this->_db->getRows($this->_sql, $value_array, true);
		
		if ($rows === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		
		return $rows;
	}	
		
	public function changeStatus($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'lang_id',
			'status'
		));
		return parent::changeStatus($params);
	}	
	
}

?>
