<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginNewsletter
{
	private $lumRegistry;
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceNewsletterModel($registry->eDB ? $registry->eDB : $registry->db); // model needs database access
	}

	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Newsletter\All',
			'Newsletter\View',
			'Newsletter\Archive',
			'Newsletter\Delete'
		);
		
		/*
		  
			will store permissions in the database like this
	
			$perms = array('Users\Accounts\Super User',
						   'Users\Accounts\View',
						   'Users\Roles\Add');
			
			$perms_enc = base64_encode(serialize($perms));
		
		*/
	}	

	public function getNewCount($params)
	{
		return $this->model->getCount();
	}
	
	public function verify($params)
	{
		return $this->model->verify($params);
	}	
	
	// ===== RPC Methods ===== //
	public function get($params)
	{
		if (!lum_requirePermission('Newsletter\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->get($params);
	}
	
	public function update($params)
	{
		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		if (!lum_requirePermission('Newsletter\View', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Newsletter\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		lum_clearPageCache();
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $request_id)
			{
				$params['request_id'] = $request_id;
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
		if (!lum_requirePermission('Newsletter\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 0;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $request_id)
			{
				$params['request_id'] = $request_id;
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
		if (!lum_requirePermission('Newsletter\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 1;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $request_id)
			{
				$params['request_id'] = $request_id;
				
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
}

class LuminanceNewsletterModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."newsletter_requests";
		$this->_key = "request_id";
	}
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'request_id',
			'email',
			'verify_key'
		));
		
		$new = $params['new'];
		
		if ($new)
		{
			$id = parent::insert($params);
			$success = true;
			if (!$id)
			{
				$success = false;
			}
			
			$params['request_id'] = $id;
		}
		else
		{
			$success = parent::update($params);
		}
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess(array('request_id'=>$params['request_id']));
	}

	public function changeStatus($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'request_id',
			'status'
		));
		return parent::changeStatus($params);
	}

	public function get($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'request_id'
		));
		$row = parent::get($params);
		if ($row)
			$row['comments'] = base64_decode($row['comments']);
			
		return $row;
	}

	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'request_id'
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
			$params['where'] = "(email like '%%".addslashes($params['query'])."%%')";
		}
		
		return parent::getList($params);
	}
	
	public function getCount($params)
	{
		$sql = "select count(request_id) as num from ".$this->_table." where status = 1";
		$row = $this->_db->getRow($sql, null);
		if ($row)
			return $row->num;
		
		return 0;
	}
	
	public function verify($params)
	{
		parent::setRequiredParams(array
		(
			'verify_key'
		));
		
		$sql = 'update '.$this->_table.' set verified = 1 where verify_key = ?';
		$value_array = array($params['verify_key']);
		return $this->_db->doQuery($sql, $value_array);
	}		
}

?>
