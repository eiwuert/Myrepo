<?php

require_once(COMMON_LIB_DIR . "data_format.1.php");
require_once(CLIENT_CODE_DIR . "error.strings.class.php");

abstract class Display_Parent
{
	protected $transport;
	protected $data;
	protected $module_name;
	protected $mode;
	protected $data_format;

	public function __construct(Transport $transport, $module_name, $mode)
	{
		$this->transport = $transport;
		$this->module_name = $module_name;		
		$this->mode = $mode;
		$this->data = $transport->Get_Data();
		$this->data_format = new Data_Format_1();
	}

	// Variable replacement callback function.  This got a little complicated, complain to Chris.  Fix later.
	protected function Replace($matches)
	{
		// Is it an edit layer?
		if( strpos($matches[0], "_edit%%%") )
		{
			$matches[1] = substr($matches[1], 0, -5);

			if( !empty($this->data->saved_error_data) && array_key_exists($matches[1], (array)$this->data->saved_error_data) )
			{
				$return_value = $this->data->saved_error_data->{$matches[1]};

			}
			elseif(array_key_exists($matches[1], (array)$this->data))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		else // Non edit replacement.
		{
			if(array_key_exists($matches[1], (array)$this->data))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		return $return_value;
	}
	
	
	public function Get_Error_Block()
	{
		$error_list = '';
		if (count($errors = $this->transport->Get_Errors()))
		{
			foreach ($errors as $field => $message)
			{
				$error_msg = ($err_return = Error_Strings::Get_Error($field)) ? $err_return : $message;
				
				$error_list .= $error_msg."\n<br>";
			}
		}

		if ($error_list)
			return $this->Message_Block_Prep( $error_list, 'error');
		else 
			return false;

	}
	
	public function Get_Message_Block($messages, $type)
	{
		$message_list = '';
		if ($messages)
		{
			foreach ($messages as $field => $message)
			{	
				$msg_list .= $message."\n<br>";
			}
		}
		
		if ($msg_list)
			return $this->Message_Block_Prep( $msg_list, $type);
		else 
			return false;
	}
	
	private function Message_Block_Prep($list, $type)
	{
		$block_html = file_get_contents(CLIENT_VIEW_DIR . $type . '_block.html');
		
		return $html = preg_replace('/%%%'.$type.'_list%%%/', $list, $block_html);
		
	}
	
	// HACK for admin section since it is in different code structure than the rest of the modules	
	public function Get_Success_Block()
	{
		return $this->Get_Message_Block($this->transport->Get_Success(), 'success');
	}
	
	public function Get_Notice_Block()
	{
		return $this->Get_Message_Block($this->transport->Get_Notices(), 'notice');
	}
	
}

?>