<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginRequests
{
	private $lumRegistry;
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceRequestsModel($registry->eDB ? $registry->eDB : $registry->db); // model needs database access
	}

	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Requests\All',
			'Requests\View',
			'Requests\Archive',
			'Requests\Delete'
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
		return $this->model->getCount(array('status'=>1));
	}
	
	// ===== RPC Methods ===== //
	public function addReply($params)
	{
		return $this->model->addReply($params);
	}
	
	public function get($params)
	{
		if (!lum_requirePermission('Requests\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->get($params);
	}
	
	public function update($params)
	{
		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		if (!lum_requirePermission('Requests\View', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Requests\Delete', false))
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
		if (!lum_requirePermission('Requests\Edit', false))
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
		if (!lum_requirePermission('Requests\Edit', false))
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
	

	public function claim($params)
	{
		if (!$this->model->claim(array('request_id'=>$params['request_id'], 'user_id'=>$params['signed_in']['user_id'])))
		{
			return lum_showError('Database Error: Unable to claim request');   
		}
		return lum_showSuccess();
	}
		
}

class LuminanceRequestsModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."form_requests";
		$this->_key = "request_id";
	}
	
	public function addReply($params)
	{
		parent::setRequiredParams(array
		(
			'request_id',
			'reply',
			'reply_subject'
		));
		
		$sql = "update lum_form_requests set replied = 1, reply = ?, reply_subject = ?, replied_on = now() where request_id = ?";
		$value_array = array($params['reply'], $params['reply_subject'], $params['request_id']);
		return $this->_db->doQuery($sql, $value_array);
		return false;		
	}	
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'request_id',
			'name',
			'email',
			'phone',
			'property_id',
			'arrival_date',
			'departure_date',
			'comments',
			'other_data', // if we have site specific data that needs to into the database
			'whole_id'
		));
		
		$new = $params['new'];
		
		$params['comments'] = base64_encode($params['comments']);
		
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

	public function claim($params)
	{
		parent::setRequiredParams(array
		(
			'request_id',
			'user_id'
		));
		
		$sql = "update lum_form_requests set agent_id = ? where request_id = ? and agent_id = 0";
		$value_array = array($params['user_id'], $params['request_id']);
		return $this->_db->doQuery($sql, $value_array);
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
			$params['where'] = "(email like '%%".addslashes($params['query'])."%%' or name like '%%".addslashes($params['query'])."%%' or property_id like '%%".addslashes($params['query'])."%%')";
		}
		
		if (isset($params['replied']) && $params['replied'] != '')
		{
			$params['filters'] .= (!$params['filters'] ? '' : ' and ');
			
			if ($params['replied'] == '1')
			{
				$params['filters'] .= ' t.replied  = 1 ';
			}
			
			if ($params['replied'] == '0')
			{
				$params['filters'] .= ' t.replied = 0 ';
			}
		}		
		
		$params['select'] = ' t.*, lq.quote_id ';
		$params['join'] = ' left join lum_quotes lq on lq.request_id = t.request_id ';
		
		if (isset($_COOKIE['lum_loadSiteKey']))
		{
			$params['select'] .= ', mlu.username as agent ';
			$params['join'] .= ' left join master_site.lum_users mlu on mlu.user_id = t.agent_id ';
		}
		
		$params['select'] .= ', lw.company ';
		$params['join'] .= ' left join lum_wholesalers lw on lw.whole_id = t.whole_id ';
		
		
		$row = parent::getList($params);
		//echo $this->sql_query;
		return $row;
	}
	
	public function getCount($params)
	{
		$sql = "select count(request_id) as num from lum_form_requests where status = ?";
		$value_array = array($params['status']);
		$row = $this->_db->getRow($sql, $value_array);
		if ($row)
			return $row->num;
		
		return 0;
	}
}

?>
