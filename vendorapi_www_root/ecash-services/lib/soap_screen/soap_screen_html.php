<?php

	/**
	*   HTML module for the Soap_Screen WSDL invocation tool.
	*
	*   This HTML module acts as a template of sorts.
	*
	*   @author    Don Adriano and David Hickman
	*   @link      http://SellingSource.com
	*   @since     2005.12.27
	*   @version   1.0
	*/

	class Soap_Screen_Html
	{
		const LB = "\r\n";
		const DEFAULT_TEMPLATE_DIR = 'templates';
		

		// If passing in a path to templates, it should be in the form:  /template/
		function __construct( $template_path = NULL )
		{
			if ( $template_path == NULL )
			{
				$dir = dirname(__FILE__);
				if ( $dir == DIRECTORY_SEPARATOR ) $dir = '';
				$template_path = $dir . DIRECTORY_SEPARATOR . self::DEFAULT_TEMPLATE_DIR . DIRECTORY_SEPARATOR;
			}
			$this->template_path = $template_path;
		}
	
		public function Main_Function_List_Html( &$token_array )
		{
			$html = $this->Get_Template('main_function_list.html');
			return $this->Resolve_Template( $html, $token_array );
		}


		public function Function_Form_Html( &$token_array )
		{
			$html = $this->Get_Template('function_form.html');
			return $this->Resolve_Template( $html, $token_array );
		}

	
		public function Function_Form_Data( &$token_array )
		{
			$html = $this->Get_Template('function_form_data.html');
			return $this->Resolve_Template( $html, $token_array );
		}

	
		public function Get_Form_Data_Row_Html( &$token_array )
		{
			$token_array['row_class'] = ($token_array['row_num'] % 2 == 0 ? 'class="rowOne"' : 'class="rowTwo"');
			$html = $this->Get_Template('form_data_row.html');
			return $this->Resolve_Template( $html, $token_array );
		}


		public function Get_Msg_Html( &$token_array )
		{
			if ( ! isset($token_array['color']) )           $token_array['color']           = 'red';
			if ( ! isset($token_array['color-legend']) )    $token_array['color-legend']    = 'red';
			if ( ! isset($token_array['soap_screen_msg']) ) $token_array['soap_screen_msg'] = '';
			if ( ! isset($token_array['invocation_msg']) )  $token_array['invocation_msg']  = '';
		
			$html = $this->Get_Template('msg.html');
			
			return $this->Resolve_Template( $html, $token_array );
		}


		public function Get_Main_Html( &$token_array )
		{
			$token_array['debug_info_html'] = $this->Get_Debug_Html($token_array);
			$html = $this->Get_Template('main_body.html');
			return $this->Resolve_Template( $html, $token_array );
		}


		public function Get_Debug_Html( &$token_array )
		{
			if ( !isset($token_array['debug']) || ! $token_array['debug'] ) return '';
			$html = $this->Get_Template('debug.html');
			return $this->Resolve_Template( $html, $token_array );
		}


		protected function Get_Template( $template_name )
		{
			$filename = $this->template_path . $template_name;
			if ( file_exists( $filename ) )
			{
				$file_contents = file_get_contents($filename);
			}
			else
			{
				$file_contents = '<br clear="all"><center>Sorry, template: ' . $filename . ' could not be found.</center>';
			}
			return $file_contents;
		}


		protected function Resolve_Template( &$template_string, &$token_array )
		{
			$result = $template_string;
			
			foreach( $token_array as $key => $val )
			{
				$result = str_replace( '%%%' . $key . '%%%', $val, $result );
			}

			return $result;
		}

	}

?>
