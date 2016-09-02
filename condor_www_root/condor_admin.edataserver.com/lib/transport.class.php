<?php

class Transport
{
	public $company;
	public $company_id;
	public $agent_id;
	public $login;
	public $action;
	public $user_acl;
	public $acl_sorted;
	public $acl_unsorted;
	public $section_views;

	private $page_array;
	private $data;
	private $current_level;
	private $errors;

	public function __construct()
	{
		$this->page_array = array();
		$this->user_acl = array();
		$this->acl = array();
		$this->current_level = 0;
		$this->errors = array();
	}

	public function Set_Levels()
	{
		$this->page_array = array();
		$count = func_num_args();
		for ($i = 0; $i < $count; $i++)
		{
			$arg = func_get_arg($i);
			$this->page_array[] = $arg;
		}
		
	}

	public function Get_Next_Level()
	{
		return ( isset($this->page_array[$this->current_level]) ? $this->page_array[$this->current_level++] : NULL);
	}

	public function Get_Current_Level()
	{
		return ( isset($this->page_array[$this->current_level]) ? $this->page_array[$this->current_level] : NULL);
	}

	public function Add_Levels()
	{
		$count = func_num_args();
		for ($i = 0; $i < $count; $i++)
		{
			$arg = func_get_arg($i);
			$this->page_array[] = $arg;
		}
	}

	public function Add_Error($error_message, $field = NULL)
	{
		if( is_null($field) )
		{
			$this->errors[] = $error_message;
		}
		else
		{
			$this->errors[$field] = $error_message;
		}
	}
	
	public function Add_Errors($errors)
	{
		if (count($errors))
		{
			foreach($errors as $field => $message)
			{
				$this->errors[$field] = $message;
			}
		}
	}

	public function Get_Errors()
	{
		return $this->errors;
	}
	
	
	
	
	public function Add_Notice($message, $field = NULL)
	{
		if( is_null($field) )
		{
			$this->notices[] = $message;
		}
		else
		{
			$this->notices[$field] = $message;
		}
	}
	
	public function Add_Notices($notices)
	{
		if (count($notices))
		{
			foreach($notices as $field => $message)
			{
				$this->notices[$field] = $message;
			}
		}
	}

	public function Get_Notices()
	{
		return $this->notices;
	}
	
	
	
	
	
	public function Add_Success($message, $field = NULL)
	{
		if( is_null($field) )
		{
			$this->success[] = $message;
		}
		else
		{
			$this->success[$field] = $message;
		}
	}
	
	/*
	successes (s?k-se(s')es pronunciation
	n.
		1. A word made up to match the rest of the methods
	*/
	public function Add_Successes($messages)
	{
		if (count($messages))
		{
			foreach($messages as $field => $message)
			{
				$this->success[$field] = $message;
			}
		}
	}

	public function Get_Success()
	{
		return $this->success;
	}
	
	
	
	public function Set_Action($action)
	{
		$this->action = $action;
	}

	public function Set_Data($data)
	{
		if( is_object($data) && is_object($this->data) )
		{
			$this->data = (object) array_merge( (array) $this->data, (array) $data );
		}
		elseif( is_array($data) && is_array($this->data) )
		{
			$this->data = array_merge($this->data, $data);
		}
		else
		{
			$this->data = $data;
		}
	}

	public function Get_Data()
	{
		return $this->data;
	}

	public function Get_Section_Views()
	{
		return $this->section_views;
	}
	
	public function __toString()
	{
		$string = "<pre>";
		$string .= "page_array: " . To_String($this->page_array);
		$string .= "data: " . To_String($this->data);
		$string .= "user_acl: " . To_String($this->user_acl);
		$string .= "company_id: {$this->company_id}";
		$string .= "</pre>";
		return $string;
	}

	public function __clone()
	{
		if(isset($this->data) && is_object($this->data))
		$this->data = clone $this->data;
	}
}

?>
