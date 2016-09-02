<?php

	/**
	*   WSDL invocation tool to automatically generate interactive PHP/HTML screens from a SOAP WSDL
	*   and simultaneously implement HTTP GET and POST handling for a PHP SOAP server.
	*
	*   This tool takes a SOAP WSDL and automatically generates interactive PHP/HTML
	*   screens for interacting with the SOAP server that implements the WSDL.
	*   In addition, this tool provides an interface layer so that once you create
	*   your PHP class to handle SOAP requests, by using this tool your class
	*   automatically can handle GET and POST requests as well with no extra effort.
	*
	*   The calls to the SOAP server class can be through SOAP or direct.
	*
	*   This is useful for testing your SOAP server, for interactively exercising your
	*   SOAP server and for helping your users to understand and interact with your
	*   SOAP server.
	*
	*   This tool requires PHP version 5 and above.
	*
	*   @author    Don Adriano and David Hickman
	*   @link      http://SellingSource.com
	*   @since     2005.12.27
	*   @version   1.0
	*/

	if ( basename(__FILE__) ==  basename($_SERVER['SCRIPT_FILENAME']) )
	{
		// If this script is being executed directly, display an entry field for
		// an arbitrary wsdl URL and invoke methods via soap rather than direct.
			
		$wsdl_url = Soap_Screen::Get_Http_Var('wsdl_url');
		$soap_screen = new Soap_Screen($wsdl_url);
		$soap_screen->handle();
	}


	class Soap_Screen
	{
		const OPCODE_NAME                    = 'op';
		const DEBUG_QUERY_VARIABLE_NAME      = 'debug';
		const VIEW_ONLY_QUERY_VARIABLE_NAME  = 'viewonly';
		const SOAP_SCREEN_METHOD_RETURN_TYPE = 'SOAP_SCREEN_METHOD_RETURN_TYPE';
		const LB                             = "\r\n";

		private $wsdl_url;                    // the URL for the WSDL.
		private $debug;                       // boolean debug flag, true or false.
		private $debug_msg;                   // concatenated list of debugging messages appended to HTML if debug is true.
		private $client;                      // the SoapClient built in to PHP5.
		private $functions;                   // holds result of calling SoapClient->__getFunctions().
		private $types;                       // holds result of calling SoapClient->__getTypes().
		private $function_list;               // parsed list of functions: array(functionName => array(paramName => paramType)).
		private $function_list_duplicates;    // array of duplicate function names for info only.  Happens when WSDL is too complicated for this tool and has more than one definition of a function.
		private $complex_types;               // parameter types fully resolved down to simple string, boolean, int, float.
		private $class_name;                  // holds class_name from setClass() method for instantiating a server class.
		private $class_params;                // holds parameters to be passed on class instantiation.  Obtained in setClass() method.
		private $standard_types;              // defines standard parameter types that do not need resolving: string, boolean, int, float.
		private $extra_path_info;             // extra path info in the URL that is between the script URL and the query string.  See: Get_Extra_Path_Info().
		private $method_result;               // holds the result of invoking a method on a class.
		private $template;                    // some kind of template engine.
		private $token_array;                 // array of tokens used in template engine.
		private $mode;                        // set to 1 if setClass() is called, otherwise 2  (1 => direct calls to object, 2 => soap calls).
		private $view_only;                   // boolean flag, true => only view functions and types output by built-in PHP processing, false => normal processing.


		public function __construct( $wsdl_url = '', $debug = false, $template_path = '', &$template_object = NULL )
		{ 
			$this->wsdl_url         = $wsdl_url;
			$this->debug            = $debug;
			$this->debug_msg        = '';
			$this->standard_types   = array('string', 'boolean', 'int', 'float');
			$wsdl_debug_array       = array('trace' => 1, 'exceptions' => 1);
			$this->function_list    = array();
			$this->complex_types    = array();
			$this->token_array      = array();
			$this->mode             = 1;  // 1 => directly invoke methods, 2 => invoke methods via soap

			if ( $template_object == NULL )
			{
				require_once('soap_screen/soap_screen_html.php');
				$this->template = new Soap_Screen_Html($template_path);
			}
			else
			{
				$this->template = $template_object;
			}

			$debug_http_var = strtolower($this->Get_Http_Var(self::DEBUG_QUERY_VARIABLE_NAME));
			$view_only_http_var = strtolower($this->Get_Http_Var(self::VIEW_ONLY_QUERY_VARIABLE_NAME));

			$yes_indicators = array('yes','on','true','1');

			$this->view_only = in_array( $view_only_http_var, $yes_indicators ) ? true : false;
			if ( in_array( $debug_http_var, $yes_indicators ) ) $this->debug = true;

			$this->function_list_duplicates = array();

			if ( $this->wsdl_url != '' )
			{
				try
				{
					$this->client     = $this->debug ? new SoapClient($this->wsdl_url, $wsdl_debug_array) : new SoapClient($this->wsdl_url);
					$this->functions  = $this->client->__getFunctions();
					$this->types      = $this->client->__getTypes();
					$this->Parse_Functions();
					$this->Parse_Types();
				}
				catch ( SoapFault $e )
				{
					$msg = __METHOD__ . ': received SoapFault: msg=' . $e->getMessage();
					$this->logger($msg);
				}
				catch ( Exception $e )
				{
					$msg = __METHOD__ . ': received Exception: msg=' . $e->getMessage();
					$this->logger($msg);
				}
			}
			
			if ( $this->debug )
			{
				$this->logger(__METHOD__ . ": wsdl_url=" . $this->wsdl_url);
				$this->logger(__METHOD__ . ": duplicate functions=" . (count($this->function_list_duplicates) > 0 ? $this->Get_Print_Var($this->function_list_duplicates, false) : ' none ') );
			}
		
			$this->token_array['debug'] = ($this->debug ? true : false);
			$this->token_array['main_menu_url'] = $this->Build_Link( $_SERVER['SCRIPT_NAME'], ($this->debug ? 'debug=yes' : '') );
			$this->token_array['debug_hidden_field'] = ($this->debug ? '<input type="hidden" name="debug" value="yes">' : '');
			$this->token_array['form_target'] = 'target="_blank"';
			$this->token_array['form_url'] = $this->Build_Link( $_SERVER['SCRIPT_NAME'], ($this->debug ? 'debug=yes' : '') );
			$this->token_array['wsdl_url'] = $wsdl_url;
			$this->token_array['wsdl_input_field'] = '';
			$this->token_array['wsdl_url_hidden_field'] = $this->wsdl_url;

		}


		public function __destruct()
		{
			// $this->logger(__METHOD__ . ": entering method");
		}


		public function setClass( $class_name )
		{
			// This function must take a variable number of arguments just like SoapServer->setClass()
			// See: http://www.php.net/manual/en/function.soap-soapserver-setclass.php

			// This function is optional if making SOAP connections to the server.
			// It is REQUIRED if making direct connections.

			$this->class_name = $class_name;
			$this->class_params = func_get_args();  // $this->class_params[0] ==> class name
			array_shift($this->class_params);
		
			if ( $this->debug )
			{
				$this->logger(__METHOD__ . ": class_name=" . $this->class_name);
				$this->logger(__METHOD__ . ": class_params=" . $this->Get_Print_Var($this->class_params) );
			}
		}


		// This function will display a set of interactive screens
		public function handle()
		{
			if ( $this->view_only )
			{
				$main_menu_link = '<a href="' .
					$this->Build_Link( $_SERVER['SCRIPT_NAME'], ($this->debug ? 'debug=yes' : ''), 'wsdl_url=' . urlencode($this->wsdl_url) )
						. '">Back to Main Menu</a><br clear="all">&nbsp;<br>';
				$this->token_array['color'] = 'green';
				$this->token_array['color-legend'] = 'blue';
				$this->token_array['soap_screen_msg'] = $main_menu_link . 'Functions:';
				$this->token_array['invocation_msg'] = htmlspecialchars($this->Get_Print_Var($this->functions, false));
				$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);

				$this->token_array['soap_screen_msg'] = 'Types:';
				$this->token_array['invocation_msg'] = htmlspecialchars($this->Get_Print_Var($this->types, false));
				$this->token_array['main_data_body'] .= $this->template->Get_Msg_Html($this->token_array);

				$this->Display_Html( $this->template->Get_Main_Html($this->token_array) );
				return;
			}
		
			$this->extra_path_info = $this->Get_Extra_Path_Info( $_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'], $_SERVER['QUERY_STRING'] );
			$opcode = $this->Get_Http_Var(self::OPCODE_NAME);
			
			if ( !isset($this->class_name) || $this->class_name == '' )
			{
				$this->mode = 2;
				$this->token_array['main_menu_url'] = $this->Build_Link( $_SERVER['SCRIPT_NAME'], ($this->debug ? 'debug=yes' : ''), 'wsdl_url=' . urlencode($this->wsdl_url) );
			}

			if ( $this->debug )
			{
				$this->logger(__METHOD__ . ": extra_path_info=" . $this->extra_path_info);
				$this->logger(__METHOD__ . ": opcode=$opcode");
				$this->logger(__METHOD__ . ": mode=" . $this->mode . ($this->mode == 1 ? ':direct method calls' : ':soap method calls') );
			}

			if ( !isset($this->class_name) || $this->class_name == '' ) $this->mode = 2;

			switch ( true )
			{
				case ( $this->extra_path_info != '' ):
					$this->Execute_Method();
					break;

				case ( $opcode != '' ):
					if ( $this->mode == 2 ) $this->token_array['wsdl_input_field'] = $this->Get_Wsdl_Input_Field( true );
					$this->Display_Function_Form($opcode);
					break;

				default:
					$this->token_array['form_target'] = '';
					if ( $this->mode == 2 ) $this->token_array['wsdl_input_field'] = $this->Get_Wsdl_Input_Field( false );
					$this->Display_Main_Menu();
			}

		}


		protected function Get_Wsdl_Input_Field( $read_only = true )
		{
			$this->logger( __METHOD__ . ': sample wsdl: http://ds57.tss:8080/wsdl_parser/lib/soap_screen_sample.php?wsdl');
		
			if ( $read_only )
			{
				return '<table class="data">
							<tr>
								<td valign="bottom">
									WSDL URL:&nbsp;&nbsp;<span style="color:green;">' . $this->wsdl_url . '</span>
								</td>
							</tr>
						</table>
				';
			}
			else
			{
				return '<table class="data">
							<tr>
								<td valign="bottom" nowrap>
									WSDL URL:&nbsp;&nbsp;
									<input type="text" size="80" name="wsdl_url" value="' . $this->wsdl_url . '">
									&nbsp;&nbsp;<input type="submit" name="goButton" value="Submit">
									&nbsp;&nbsp;<input type="checkbox" name="viewonly" value="yes">
									functions and types only
								</td>
							</tr>
						</table>
				';
			}
			
		
		}

				
		protected function Display_Main_Menu()
		{
			if ( count($this->function_list) < 1 )
			{
				$this->token_array['form_target'] = '';
				if ( $this->wsdl_url == '' && $this->mode == 2 )
				{
					$this->token_array['soap_screen_msg'] = 'Please enter a WSDL URL';
				}
				else
				{
					$this->token_array['soap_screen_msg'] = 'This WSDL is either invalid or else has no functions';
				}
				$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);
			}
			else
			{
				$debugqs = $this->debug ? 'debug=yes' : '';
				$wsdlqs  = $this->mode == 2 ? 'wsdl_url=' . urlencode($this->wsdl_url) : '';

				$this->token_array['list_data'] = '';
				
				foreach( $this->function_list as $method => $parameters )
				{
					$link = $this->Build_Link( $_SERVER['SCRIPT_NAME'], self::OPCODE_NAME . "=$method", $debugqs, $wsdlqs );
					$this->token_array['list_data'] .= self::LB . "<li><a href=\"$link\">$method</li>";
				}

				$this->token_array['main_data_body'] = $this->template->Main_Function_List_Html($this->token_array);
			}

			$this->Display_Html( $this->template->Get_Main_Html($this->token_array) );
		}


		protected function Display_Function_Form($method)
		{
			$this->token_array['form_url'] = $_SERVER['PHP_SELF'] . '/' . $method;
			$this->token_array['method'] = $method;
			$this->token_array['form_row_data'] = '';
			$this->token_array['data'] = '';
		
			if ( isset($this->function_list[$method]) )
			{
				$row_num = 0;
	
				$array_of_input_fields = $this->Get_Fields_For_Method($method);
	
				if ( count($array_of_input_fields) > 0 )
				{
					foreach( $array_of_input_fields as $field_name )
					{
						$this->token_array['row_num'] = $row_num++;
						$this->token_array['field_name'] = $field_name;
						$this->token_array['form_row_data'] .= $this->template->Get_Form_Data_Row_Html($this->token_array);
					}

					$this->token_array['data'] = $this->template->Function_Form_Data($this->token_array);
					$this->token_array['main_data_body'] = $this->template->Function_Form_Html($this->token_array);
				}
				else
				{
					$this->token_array['soap_screen_msg'] = "Sorry, this method ($method) does not have any input parameters";
					$this->token_array['data'] = $this->template->Get_Msg_Html($this->token_array);
					$this->token_array['main_data_body'] = $this->template->Function_Form_Html($this->token_array);
				}
			}
			else
			{
				$this->token_array['soap_screen_msg'] = "Sorry, this is not one of the methods in the WSDL: $method";
				$this->token_array['data'] = $this->template->Get_Msg_Html($this->token_array);
				$this->token_array['main_data_body'] = $this->template->Function_Form_Html($this->token_array);
			}

			$this->Display_Html( $this->template->Get_Main_Html($this->token_array) );
		}


		protected function Execute_Method()
		{
			$method = $this->extra_path_info;
			$method = ltrim($method, '/');
			$this->token_array['form_url'] = $_SERVER['PHP_SELF'] . '/' . $method;  // is this used at all?

			$array_of_input_fields = $this->Get_Fields_For_Method($method);

			$this->logger( __METHOD__ . ": method=$method" );
		
			if ( isset($this->function_list[$method]) )
			{
				try
				{
					if ( $this->mode == 1 )
					{
						$this->logger( __METHOD__ . ": mode = Direct Execution" );
						$obj = call_user_func_array( array( new ReflectionClass($this->class_name), 'newInstance' ), $this->class_params );
					}
					else
					{
						// This is mode 2 which means we need to make a soap call and NOT a direct execution.
						$this->logger( __METHOD__ . ": mode = SOAP Call" );
						$debug_control = array('trace' => 1, 'exceptions' => 1);
						$obj = $this->debug ? new SoapClient($this->wsdl_url, $debug_control) : new SoapClient($this->wsdl_url);
					}
					
					$param_values_array = array();

					// need to get the parameters passed from our form and put them into an array.
					foreach( $array_of_input_fields as $field_name )
					{
						$val = $this->Get_Http_Var($field_name);
						$param_values_array[] = $val;
					}

					$object_method_array = array( $obj, $method );

					$valid_method_name = is_callable( $object_method_array );

					if ( $valid_method_name )
					{
						$result = call_user_func_array( $object_method_array, $param_values_array );
						$this->method_result = isset($result) ? htmlspecialchars($this->Get_Print_Var($result, false)) : 'no return value';
						$this->token_array['color'] = 'green';
						$this->token_array['color-legend'] = 'blue';
						$this->token_array['soap_screen_msg'] = 'Result:';
						$this->token_array['invocation_msg'] = $this->method_result;
						$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);
						
						if ( $this->debug && $this->mode == 2 )
						{
							$this->token_array['soap_screen_msg'] = 'SOAP REQUEST:';
							$this->token_array['invocation_msg'] = htmlspecialchars($obj->__getLastRequest());
							$this->token_array['main_data_body'] .= $this->template->Get_Msg_Html($this->token_array);
							
							$this->token_array['soap_screen_msg'] = 'SOAP RESPONSE:';
							$this->token_array['invocation_msg'] = htmlspecialchars($obj->__getLastResponse());
							$this->token_array['main_data_body'] .= $this->template->Get_Msg_Html($this->token_array);
						}

					}
					else
					{
						$this->token_array['soap_screen_msg'] = "Sorry, $method is not a callable method in " . $this->class_name;
						$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);
					}
				}
				catch( Exception $e )
				{
					$this->token_array['soap_screen_msg'] = 'Sorry, we experienced an exception trying to invoke: ' . $this->class_name . '::' . $method;
					$this->token_array['invocation_msg'] = $e->getMessage();
					$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);

					if ( $this->debug && $this->mode == 2 )
					{
						$this->token_array['soap_screen_msg'] = 'SOAP REQUEST:';
						$this->token_array['invocation_msg'] = htmlspecialchars($obj->__getLastRequest());
						$this->token_array['main_data_body'] .= $this->template->Get_Msg_Html($this->token_array);

						$this->token_array['soap_screen_msg'] = 'SOAP RESPONSE:';
						$this->token_array['invocation_msg'] = htmlspecialchars($obj->__getLastResponse());
						$this->token_array['main_data_body'] .= $this->template->Get_Msg_Html($this->token_array);
					}
				}
			}
			else
			{
				$this->token_array['soap_screen_msg'] = "Sorry, this is not one of the methods in the WSDL: $method";
				$this->token_array['main_data_body'] = $this->template->Get_Msg_Html($this->token_array);
			}

			$this->Display_Html( $this->template->Get_Main_Html($this->token_array) );
		}


		// This function returns an array of fully resolved input field names for any method.
		// For this version of code, I am ignoring the field type since we don't make anykind of
		// special input field depending on type.  In the future, we might want to automatically
		// display a radio button for a boolean field and things like that but for now we always
		// just display a text entry field.
		
		protected function Get_Fields_For_Method( $method )
		{
			$result = array();
			
			if ( isset($this->function_list[$method]) )
			{
				$param_list = $this->function_list[$method];
				
				foreach( $param_list as $subscript => $field_and_type )
				{
					if ( $subscript === self::SOAP_SCREEN_METHOD_RETURN_TYPE )
					{
						// this is the return type
					}
					else
					{
						list($field_name, $field_type) = $field_and_type;
						if ( in_array($field_type, $this->standard_types) )
						{
							$result[] = $field_name;  // THROWING AWAY THE $FIELD_TYPE AT THIS POINT !!!
						}
						else
						{
							if ( isset($this->complex_types[$field_type]) )
							{
								foreach($this->complex_types[$field_type] as $subscript => $param_array)
								{
									list( $param_is_simple, $param_is_array, $param_name, $param_type ) = $param_array;
									$result[] = $param_name;  // THROWING AWAY THE $FIELD_TYPE AT THIS POINT !!!
								}
							}
						}
					}
				}
			}

			return $result;
		}


		protected function Parse_Functions()
		{
			if ( is_array($this->functions) )
			{
				$token_delimiter = ' ,$()';
			
				foreach( $this->functions as $subscript => $function_definition )
				{
					$return_type = strtok( $function_definition, $token_delimiter );
					$function_name = strtok( $token_delimiter );
					$param_array = array();
					
					do
					{
						$param_type = strtok( $token_delimiter );
						$param_name = strtok( $token_delimiter );
						
						if ( $param_type !== false && $param_name !== false )
						{
							$param_array[] = array( $param_name, $param_type );
						}
					}
					while ( $param_type !== false && $param_name !== false );

					$param_array[self::SOAP_SCREEN_METHOD_RETURN_TYPE] = $return_type;

					if ( isset($this->function_list[$function_name]) )
					{
						// keep a count of duplicate function names.
						if ( !isset($this->function_list_duplicates[$function_name]) ) $this->function_list_duplicates[$function_name] = 0;
						$this->function_list_duplicates[$function_name]++;
					}
					else
					{
						// if this function name has already beed defined, keep the first definition.
						$this->function_list[$function_name] = $param_array;
					}
				}
			}
			else
			{
				// we don't have a valid array of functions
			}

		}


		protected function Parse_Types()
		{
			if ( is_array($this->types) )
			{
				$token_delimiter = " ;{}\r\n";
			
				foreach( $this->types as $subscript => $type_definition )
				{
					$struct_keyword = strtok( $type_definition, $token_delimiter );
					$type_name = strtok( $token_delimiter );

					if ( !isset($this->complex_types[$type_name]) ) 
					{
						$param_array = array();
						
						do
						{
							$param_type = strtok( $token_delimiter );
							$param_name = strtok( $token_delimiter );

							$param_is_array  = $this->String_Begins_With('ArrayOf', $param_type, false) ? 1 : 0;
							$param_is_simple = in_array($param_type, $this->standard_types) ? 1 : 0;

							if ( $param_type !== false && $param_name !== false )
							{
								$param_array[] = array( $param_is_simple, $param_is_array, $param_name, $param_type );
							}
						}
						while ( $param_type !== false && $param_name !== false );
	
						$this->complex_types[$type_name] = $param_array;
					}
				}
			}
			else
			{
				// we don't have a valid array of types
			}

			// Now it's time for pass 2 to fully resolve all types

			$complex_types_resolved = array();

			foreach( $this->complex_types as $type_name => $array_of_param_arrays )
			{
				foreach( $array_of_param_arrays as $subscript => $param_array )
				{
					list( $param_is_simple, $param_is_array, $param_name, $param_type ) = $param_array;
					
					if ( $param_is_simple == 0 )
					{
						if ( ! isset($complex_types_resolved[$param_type]) )
						{
							// This is a complex types that needs to be fully resolved.
							$previously_encountered_types = array();
							$complex_types_resolved[$param_type] = array();
							$this->Resolve_Complex_Type($param_type, $complex_types_resolved[$param_type], $previously_encountered_types);
						}
						unset( $this->complex_types[$type_name][$subscript] );  // throwing away info that this is an array!!

						foreach( $complex_types_resolved[$param_type] as $sub => $simple_params_array )
						{
							$this->complex_types[$type_name][] = $simple_params_array;
						}
					}
				}				
			}
		}


		protected function Resolve_Complex_Type( $param_type, &$data_array, &$previously_encountered_types )
		{
			if ( in_array( $param_type, $previously_encountered_types ) )
			{
				// If this param_type has been encountered before then we would be in an infinite
				// loop if we continued to process it normally.  We must stop the infinite loop.
				// I don't think this should ever happen for a valid WSDL.

				$this->logger( __METHOD__ . ": Infinite loop on param_type=$param_type, previously_encountered_types=" . $this->Get_Print_Var($previously_encountered_types) );

				return;
			}

			$previously_encountered_types[] = $param_type;
		
			if ( !isset($this->complex_types[$param_type]) )
			{
				$data_array[] = array( 1, 0, "UnknownType_$param_type", 'string' );  // default to string type
				return;
			}

			$array_of_param_arrays = $this->complex_types[$param_type];

			foreach( $array_of_param_arrays as $subscript => $param_array )
			{
				list( $param_is_simple, $param_is_array, $param_name, $param_type ) = $param_array;
				if ( $param_is_simple == 1 )
				{
					$data_array[] = $param_array;
				}
				else
				{
					// Use recursion to further resolve this complex type.
					$this->Resolve_Complex_Type( $param_type, $data_array, $previously_encountered_types );
				}
			}
		}


		// This function builds a link using a base url along with a variable number of arguments.
		// Some of the arguments can be empty strings and the ? and & will be place appropriately.
		protected function Build_Link( $base_url )
		{
			$sep = '?';
			$result = $base_url;

			$arg_count = func_num_args();
			for ( $i = 1; $i < $arg_count; $i++ )
			{
				$arg = func_get_arg($i);
				if ( $arg != '' )
				{
					$result .= $sep . $arg;
					$sep = '&';
				}
			}
			
			return $result;
		}


		protected function Get_Extra_Path_Info( $request_uri, $script_name, $query_string )
		{
			// This function returns the extra path info which is the stuff remaining from
			// the URL after stripping off the query string and stripping off the script name.
			// example:
			//    URL:              /somepath/somefunction.php/how/now/brown/cow/?x=5&y=6
			//    script name:      /somepath/somefunction.php
			//    query string:     x=5&y=6
			//    extra path info:  /how/now/brown/cow/
		
			$result = $request_uri;
			$result = $this->Chop_String_Right( $result, $query_string, true );
			$result = $this->Chop_String_Left( $result, $script_name, true );
			$result = rtrim($result, '/?');
			return $result;
		}


		protected function Chop_String_Right( $str_in, $str_to_remove, $case_sensitive = false )
		{
			$str_in = $this->Make_Not_Null($str_in);
			$str_to_remove = $this->Make_Not_Null($str_to_remove);
	
			$result = $str_in;
	
			if ( $this->String_Ends_With( $str_to_remove, $str_in, $case_sensitive ) ) {
				$result = substr( $result, 0, - strlen($str_to_remove) );
			}
	
			return $result;
		}
	
	
		protected function Chop_String_Left( $str_in, $str_to_remove, $case_sensitive = false )
		{
			$str_in = $this->Make_Not_Null($str_in);
			$str_to_remove = $this->Make_Not_Null($str_to_remove);
	
			$result = $str_in;
	
			if ( $this->String_Begins_With( $str_to_remove, $str_in, $case_sensitive ) ) {
				$result = substr( $result, strlen($str_to_remove) );
			}
	
			return $result;
		}
	
	
		protected function String_Begins_With( $needle, $haystack, $case_sensitive = false )
		{
			if ( $needle == '' || $haystack == '' ) return false;
	
			if ( $case_sensitive ) {
				if ( $needle == substr($haystack, 0, strlen($needle)) ) return true;
			}
			else {
				if ( strtolower($needle) == strtolower(substr($haystack, 0, strlen($needle))) ) return true;
			}
	
			return false;
		}
	
	
		protected function String_Ends_With( $needle, $haystack, $case_sensitive = false )
		{
			if ( $needle == '' || $haystack == '' ) return false;
	
			if ( $case_sensitive ) {
				if ( $needle == substr($haystack, strlen($haystack) - strlen($needle)) ) return true;
			}
			else {
				if ( strtolower($needle) == strtolower(substr($haystack, strlen($haystack) - strlen($needle))) ) return true;
			}
	
			return false;
		}

		
		public static function Get_Http_Var( $var, $default='' )
		{
			return
			'POST' == $_SERVER['REQUEST_METHOD']
				? (isset($_POST[$var]) ? trim($_POST[$var]) : $default)
				: (isset($_GET[$var])  ? trim($_GET[$var])  : $default);
		}
		
	
		protected function Get_Print_Var( $var, $stripnewlines=true )
		{
			ob_start();
			print_r( $var );
			$output = ob_get_contents();
			ob_end_clean();
			if ( $stripnewlines ) $output = preg_replace( '/(\s)+/', ' ', $output );
			return $output;
		}
	
	
		protected function Make_Not_Null($s, $default='')
		{
			return isset($s) ? trim($s) : $default;
		}


		protected function logger( $s = '' )
		{
			if ( $s != '' ) $this->debug_msg .= self::LB . $s . '<p/>';
			$this->token_array['debug_data'] = $this->debug_msg;
			return $this->debug_msg;
		}


		protected function Display_Html( $html )
		{
			print $html;
		}

	}

?>