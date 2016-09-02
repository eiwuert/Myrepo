<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class handles admin site users
 * 
 **/

class LuminancePluginUsers
{
	private $lumRegistry;
	private $model;
	
	function __construct(&$registry)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$this->lumRegistry = $registry;
		$this->model = new LuminanceUsersModel($registry->db); // model needs database access
		
	}
	
	public function hasPermission($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->hasPermission($params['permission'], (isset($params['permissions']) ? $params['permissions'] : null));
	}
	
	// used when display the roles tool
	public function getPermissionTypes()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return array(
			'Users\Super User', // special permission. Only another super user can set it. Prevents users from doing things only a super user should be able to do.
			'Users\Accounts\All',
			'Users\Accounts\View',
			'Users\Accounts\Edit',
			'Users\Accounts\Delete',
			'Users\Accounts\Change Status',
			'Users\Roles\All',
			'Users\Roles\Edit',
			'Users\Roles\Delete'
		);
		
		/*
		  
			will store permissions in the database like this
	
			$perms = array('Users\Accounts\Super User',
						   'Users\Accounts\View',
						   'Users\Roles\Add');
			
			$perms_enc = base64_encode(serialize($perms));
		
		*/
	}
	
	public function isSignedIn()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->isSignedIn();
	}
	
	public function authenticate($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->authenticate($params);
	}
	
	public function signOut($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->signOut($params);
	}		
	
	public function forgotPassword($params)
	{
///file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$params['user'] = $this->model->getByEmail($params);
		
		if ($params['user'])
		{
			$params['user_password'] = $this->model->createTemporaryPassword($params);
			return $this->sendNewPassword($params);
		}
		return false;
	}		
	
	public function changePassword($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (isset($params['username']) && isset($params['user_password']) && isset($params['verify_password']))
		{
			return $this->model->changePassword($params);
		}
		return false;
	}		
	
	private function sendNewPassword($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$body = "You recently filled out the forgot password form on the ".GENERAL_MAIL_SITE_NAME." web site. A new password as been created for you.
		
		Username: ".$params['user']['username']."
		Password: ".$params['user_password']."
		
		To sign in to your account, simply click on the following link:
		
		".BASE_URL."sign-in\r\n";
			
		return lum_sendEmail(GENERAL_MAIL_FROM, GENERAL_MAIL_FROM_NAME, $params['user_email'], '', $body, 'Password Reset', false);	
	}
	
	public function getStatus()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->getStatus();
	}

	public function getChangePassword()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->getChangePassword();
	}	
	
	// ===== RPC Methods ===== //
	public function updateSignature($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->updateSignature($params);
	}
	
	public function getRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->model->getRole($params);
	}
	
	public function updateRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Roles\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		return $this->model->updateRole($params);
	}	
	
	public function getRoleList($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Roles\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		

		return $this->model->getRoleList($params);
	}
	
	public function get($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		

		return $this->model->get($params);
	}
	
	public function update($params, $table = null, $key = null)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		

		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $user_id)
			{
				$params['user_id'] = $user_id;
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
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Change Status', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 0;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $user_id)
			{
				$params['user_id'] = $user_id;
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
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Accounts\Change Status', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 1;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $user_id)
			{
				$params['user_id'] = $user_id;
				
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
	
	public function deleteRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!lum_requirePermission('Users\Roles\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $role_id)
			{
				$params['role_id'] = $role_id;
				if (!$this->model->deleteRole($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->deleteRole($params);
		}
	}	
}

class LuminanceUsersModel extends LuminanceModel
{
	private $_cookie_name;
	private $_expire_seconds;
	private $_roles_table;
	private $_roles_key;
	private $_permissions;
	private $_status;
	private $_change_password;
	
	function __construct($db)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$this->_db = $db;
		$this->_table = "lum_users";
		$this->_roles_table = "lum_roles";
		$this->_key = "user_id";
		$this->_roles_key = "role_id";
		$this->_cookie_name = 'lum_admin';
		$this->_expire_seconds = 10800;
		$this->_permissions = 0;
	}
	
	public function update($params, $table = null, $key = null)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'username',
			'user_password',
			'user_email',
			'last_name',
			'first_name',
			'phone',
			'must_change_password',
			'status',
			'role_id',
			'comments',
			'is_group',
			'signature',
			'user_id'
		));
		
		$new = $params['new'];
		
		if ($params['user_password'] != '')
		{
			$params['user_password'] = $this->createHash($params['user_password']);
		}
		else
		{
			parent::removeRequiredParam('user_password');
		}
		
		if ($new)
		{
			$params['registered_date'] = date('Y-m-d H:i:s');
			$params['last_logged_in'] = date('Y-m-d H:i:s');
			$success = parent::insert($params);
		}
		else
		{
			$success = parent::update($params);
		}
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	public function updateRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'name',
			'permissions',
			'require_superuser',
			'role_id'
		));
		
		$new = $params['new'];

		if (isset($params['permissions[]']))
		{
			if (!is_array($params['permissions[]']))
				$params['permissions[]'] = array($params['permissions[]']);
				
			$params['permissions'] = base64_encode(serialize($params['permissions[]']));
			unset($params['permissions[]']);
		}

		if ($new)
		{
			$success = parent::insert($params, true, $this->_roles_table);
		}
		else
		{
			$success = parent::update($params, $this->_roles_table, $this->_roles_key);
		}
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	public function updateSignature($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'signature',
			'user_id'
		));
		
		$params['signature'] = base64_encode($params['signature']);
		
		$sql = "update lum_users set signature = ? where user_id = ?";
		$value_array = array($params['signature'], $params['user_id']);
		if ($this->_db->doQuery($sql, $value_array) === false)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	public function get($params, $table = NULL, $key = NULL)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'user_id'
		));
		
		$params['join'] = ' left join '.$this->_roles_table.' on '.$this->_roles_table.'.role_id = c.role_id ';
		$params['select'] = 'c.*, '. $this->_roles_table.'.permissions';		
		
		$row = parent::get($params);
		if ($row)
		{
			$row['signature'] = base64_decode($row['signature']);
		}
		return $row;
	}	
	
	public function getRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'role_id'
		));
		return parent::get($params, $this->_roles_table, $this->_roles_key);
	}	
		
	
	public function getByEmail($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!isset($params['user_email']))
			return false;
		
		$sql = "SELECT * from $this->_table where user_email = ?";
		$value_array = array($params['user_email']);
		$row = $this->_db->getRow($sql, $value_array, true);
		if ($row)
		{
			$row['signature'] = base64_decode($row['signature']);
		}
		return $row;		
		
		
	}		
	
	public function delete($params, $table = NULL, $key = NULL)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'user_id'
		));
		return parent::delete($params);
	}		
	
	public function changeStatus($params, $table = NULL, $key = NULL)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'user_id',
			'status'
		));
		return parent::changeStatus($params);
	}		
	
	public function deleteRole($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		parent::setRequiredParams(array
		(
			'role_id'
		));
		return parent::delete($params, $this->_roles_table, $this->_roles_key);
	}		
	
	public function getList($params, $table = NULL)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$params['filters'] = null;
		$params['values'] = array();
		$params['join'] = ' left join '.$this->_roles_table.' on '.$this->_roles_table.'.role_id = t.role_id ';
		$params['select'] = 't.*, '. $this->_roles_table.'.name as role';
		
		if (isset($params['region']) && $params['region'] != '')
		{
			$params['filters'] = ' region = ?';
			$params['values'][] = $params['region'];
		}
		
		if (isset($params['query']) && $params['query'] != '')
		{
			$params['where'] = "(username like '%%".addslashes($params['query'])."%%' or user_email like '%%".addslashes($params['query'])."%%' or first_name like '%%".addslashes($params['query'])."%%' or last_name like '%%".addslashes($params['query'])."%%')";
		}
		return parent::getList($params);
	}
	
	public function getRoleList($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$params['filters'] = null;
		$params['values'] = array();
		return parent::getList($params, $this->_roles_table, $this->_roles_key);
	}	
	
	public function getStatus()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->_status;
	}

	public function setStatus($bool)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$this->_status = $bool;
	}

	public function getChangePassword()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		return $this->_change_password;
	}

	public function setChangePassword($bool)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$this->_change_password = $bool;
	}

	public function authenticate($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$this->eraseFingerprint();
			
		$sql = "SELECT * from $this->_table u where username = ?";
		$value_array = array($params['username']);
		$row = $this->_db->getRow($sql, $value_array);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($row,true)."\n",FILE_APPEND);
		if ($row)
		{
//file_put_contents(LOGS_PATH."tmp.out.txt","Row is good\n",FILE_APPEND);
			$row->signature = base64_decode($row->signature);
			if (!$this->checkHash($params['user_password'], $row->user_password))
			{
//file_put_contents(LOGS_PATH."tmp.out.txt","password failed\n",FILE_APPEND);
				return array('authenticated'=>false, 'status'=>false, 'change_password'=>false);
			}

			if ($row->status == 0)
			{
//file_put_contents(LOGS_PATH."tmp.out.txt","bad status\n",FILE_APPEND);
				return array('authenticated'=>false, 'status'=>true, 'change_password'=>false);
			}

			session_regenerate_id(true);
			
			if ($row->must_change_password)
			{
//file_put_contents(LOGS_PATH."tmp.out.txt","change password\n",FILE_APPEND);
				return array('authenticated'=>false, 'status'=>false, 'change_password'=>true);
			}

			$authenticated = $this->updateFingerprint($row->user_id, $row->is_group);
			
			if ($authenticated)
				$this->saveSession($row);
//file_put_contents(LOGS_PATH."tmp.out.txt","authenticated\n",FILE_APPEND);
			return  array('authenticated'=>$authenticated, 'status'=>false, 'change_password'=>false);;
		}
		
		return array('authenticated'=>false, 'status'=>false, 'change_password'=>false);
	}
	
	private function updateFingerprint($user_id, $is_group)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$fingerprint = $this->createFingerprint(($is_group ? $user_id : null));
		
		$sql = "update $this->_table set user_session = ?, last_logged_in = now() where user_id = ?";
		$value_array = array($fingerprint, $user_id);



		if ($this->_db->doQuery($sql, $value_array) === false)
		{
//file_put_contents(LOGS_PATH."tmp.out.txt","cookies failed- bad query\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",$query."\n",FILE_APPEND);

			return false;
		}
		
		
//file_put_contents(LOGS_PATH."tmp.out.txt","cookies generated\n",FILE_APPEND);
		
		setcookie($this->_cookie_name."_fingerprint",$fingerprint,time()+$this->_expire_seconds, '/', COOKIE_DOMAIN);
		setcookie($this->_cookie_name."_user_id",$user_id,time()+$this->_expire_seconds, '/', COOKIE_DOMAIN);
		return true;	
	}
	
	public function changePassword($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (strcmp($params['verify_password'], $params['new_password']) != 0)
		{
		   return array('changed'=>false, 'notfound'=>false, 'mismatch'=>true);
		}
		
		$sql = "select user_id, user_password from $this->_table where username = ?";
		$value_array = array($params['username']);
		$row = $this->_db->getRow($sql, $value_array);
		
		if (!$row)
		{
			return array('changed'=>false, 'notfound'=>true, 'mismatch'=>false);
		}
		
		if (!$this->checkHash($params['user_password'], $row->user_password))
		{
			return array('changed'=>false, 'notfound'=>true, 'mismatch'=>false);
		}
		
		$sql = "update $this->_table set user_password = ?, must_change_password = 0 where user_id = ?";
		$value_array = array($this->createHash($params['new_password']), $row->user_id);
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			return array('changed'=>false, 'notfound'=>false, 'mismatch'=>false);
		}
		
		return array('changed'=>true, 'notfound'=>false, 'mismatch'=>false);
	}	
	
	public function createTemporaryPassword($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$password = $this->createRandomPassword();
		$sql = "update $this->_table set user_password = ? where user_email = ?";
		$value_array = array($this->createHash($password), $params['user_email']);
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			return false;
		}
		return $password;	
	}	
	
	private function createRandomPassword()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$chars = "abcdefghijkmnopqrstuvwxyz023456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$password = '' ;
		while ($i <= 7) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$password = $password . $tmp;
			$i++;
		}
		return $password;
	}
	
	public function signOut()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		// destroy all cookies!
		if (isset($_SERVER['HTTP_COOKIE'])) {
			$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			foreach($cookies as $cookie) {
				$parts = explode('=', $cookie);
				$name = trim($parts[0]);
				$test = setcookie($name, '', time()-1000, '/', COOKIE_DOMAIN);
			}
		}

		if (session_id())
			session_destroy();
			
		unset($_SESSION);
	}	
	
	public function isSignedIn()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($_COOKIE,true)."\n",FILE_APPEND);

		if (!isset($_COOKIE[$this->_cookie_name."_fingerprint"]) || !isset($_COOKIE[$this->_cookie_name."_user_id"]) ||
			empty($_COOKIE[$this->_cookie_name."_fingerprint"]) || empty($_COOKIE[$this->_cookie_name."_user_id"]))
		{
			lum_logMe("no cookie!");
			// we need to have a cookie to login
			return false;
		}

		// let's look up the user by their fingerprint
		$sql = "select u.*, r.permissions from $this->_table u left join $this->_roles_table r on r.role_id = u.role_id where u.user_session = ? and u.user_id = ?";
		$value_array = array($_COOKIE[$this->_cookie_name."_fingerprint"], intval($_COOKIE[$this->_cookie_name."_user_id"]));
		$row = $this->_db->getRow($sql, $value_array);
		
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($sql,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($value_array,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($row,true)."\n",FILE_APPEND);


		if ($row)
		{
			$row->signature = base64_decode($row->signature);
			// we found a user! Let's make sure the cookie matches the fingerprint we created for this user
			if (!$this->verifyFingerprint($_COOKIE[$this->_cookie_name."_fingerprint"], ($row->is_group ? $row->user_id : null)))
			{
				return false;
			}

			if ($row->must_change_password)
			{
				$this->eraseFingerprint();
				$this->change_password = true;
				return false;
			}						
				
			$this->saveSession($row);
		}
		return $row;
	}
	
	private function saveSession($row)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__FILE__."\n",FILE_APPEND);

		$_SESSION['lum_user'] = array(
			'user_id'=>$row->user_id,
			'username'=>$row->username,
			'user_email'=>$row->user_email,
			'permissions'=>unserialize(base64_decode($row->permissions))
		);
		
		$fingerprint = $_COOKIE[$this->_cookie_name."_fingerprint"];

		$user_id = $row->user_id;
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($fingerprint,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($user_id,true)."\n",FILE_APPEND);
		
		if ($fingerprint)
		{
			setcookie($this->_cookie_name."_fingerprint",$fingerprint,time()+$this->_expire_seconds, '/', COOKIE_DOMAIN);
			setcookie($this->_cookie_name."_user_id",$user_id,time()+$this->_expire_seconds, '/', COOKIE_DOMAIN);
		}
	}
	
	private function getSession($param)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (isset($_SESSION['lum_user'][$param]))
			return $_SESSION['lum_user'][$param];
			
		return null;
	}
	
	private function verifyFingerprint($fingerprint, $user_id = null)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$str = '';
		if ($user_id)
		{
			$str = $user_id.'0url1ttleSecr3t';
		}
		else
		{
			$str .= $_SERVER['HTTP_USER_AGENT'];
			$str .= $_SERVER['REMOTE_ADDR'];
		}
	
		
		return $this->checkHash($str, $fingerprint);
	}
	
	private function createFingerprint($is_group = null)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		$string = '';
		if ($is_group)
		{
			$string = $is_group.'0url1ttleSecr3t';
		}
		else
		{
			$string = $_SERVER['HTTP_USER_AGENT'];
			$string .= $_SERVER['REMOTE_ADDR'];
		}
		
	
		return $this->createHash($string, null, $is_group);
	}
	
	private function createHash($str, $salt = null, $is_group = false)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!$salt)
		{
			if ($is_group)
			{
				$salt = substr(base64_encode(sha1('0url1ttleSecr3t')), 4, 16);
			}
			else
			{
				$salt = substr(base64_encode(sha1(uniqid('LUM'))), 4, 16);
			}
		}
		

		$hash = '$LUM$'. $salt . base64_encode(sha1($str . $salt));
		return $hash;
		//return '$LUM$'. $salt . base64_encode(lum_pbkdf2($str, $salt, 1000, 32));
	}

	function eraseFingerprint()
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		setcookie($this->_cookie_name."_user_id",'',time()-$this->_expire_seconds, '/', COOKIE_DOMAIN);
		setcookie($this->_cookie_name."_fingerprint",'',time()-$this->_expire_seconds, '/', COOKIE_DOMAIN);
		unset($_COOKIE[$this->_cookie_name.'_user_id']);
		unset($_COOKIE[$this->_cookie_name.'_fingerprint']);
	}
	
	function hasPermission($perm, $permissions = null /* so we can check a permission string other than the signed in user*/)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (!$permissions)
			$permissions = $this->getSession('permissions');
		
		// super user can do anything!
		if (in_array('Users\Super User', $permissions))
			return true;
		
		// doe this user have the 'All' permission for this permission group
		$temp = explode('\\', $perm);
		if ($temp)
		{
			// remove the last permission element
			array_pop($temp);
			if (in_array(implode('\\', $temp).'\All', $permissions))
				return true;
		}

		if (in_array($perm, $permissions))
			return true;
		
		return false;		
		
		
		
		
		if (!$permissions)
			$permissions = $this->getSession('permissions');
		
		// super user can do anything!
		if (in_array('Users\Super User', $permissions))
			return true;

		// doe this user have the 'All' permission for this permission group
		$temp = explode('\\', $perm);
		if ($temp)
		{
			// remove the last permission element
			array_pop($temp);
			if (in_array(implode('\\', $temp).'\All', $permissions))
				return true;
		}

		if (in_array($perm, $permissions))
			return true;
		
		return false;
	}
	
	private function checkHash($str, $hash)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
		if (substr($hash, 0, 5) != '$LUM$')
			return false;
		
		$salt = substr($hash, 5, 16);

		$new_hash = $this->createHash($str, $salt);

		if ($new_hash != $hash)
			return false;
		
		return true;
	}
}

?>
