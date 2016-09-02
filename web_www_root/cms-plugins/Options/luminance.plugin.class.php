<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginOptions
{
	private $lumRegistry;
	private $string_table = array();
	private $custom_string_table = array();
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceOptionsModel($registry->db); // model needs database access
	}

	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Options\All',
			'Options\View',
			'Options\Edit',
			'Options\Delete'
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
		if (!lum_requirePermission('Options\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->get($params);
	}

	public function getByName($params)
	{
		return $this->model->getByName($params);
	}	
	
	public function update($params)
	{
		if (!lum_requirePermission('Options\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		lum_clearPageCache();
		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		if (!lum_requirePermission('Options\View', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Options\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		lum_clearPageCache();
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $option_id)
			{
				$params['option_id'] = $option_id;
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

class LuminanceOptionsModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."options";
		$this->_key = "option_id";
	}
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'name',
			'option_id',
			'value',
			'type'
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
			'option_id'
		));
		return parent::get($params);
	}

	public function getByName($params)
	{
		parent::setRequiredParams(array
		(
			'name'
		));

		if (!$this->_db)
			return $this->setError("Invalid database handle");	
			
		
		$sql = "select * from ".$this->_table." where name = ?";

		$value_array = array($params['name']);

		$row = $this->_db->getRow($sql, $value_array);

		if ($row === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $this->_sql, $value_array);
			return false;
		}
		if (isset($row->value))
			return $row->value;
	
		return false;
	}		
	
	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'option_id'
		));
		
		// we're going to remove this plugin
		
		return parent::delete($params);
	}		
	
	public function getList($params, $table = null)
	{
		$params['filters'] = null;
		$params['values'] = array();
		if (isset($params['query']) && $params['query'] != '')
		{
			$params['where'] = "(name like '%%".addslashes($params['query'])."%%' or value like '%%".addslashes($params['query'])."%%')";
		}
		
		return parent::getList($params);
	}
}

?>
