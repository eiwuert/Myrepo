<?
	/**
		@publicsection
		@public
		@brief
			Gets message data into frountend templates
			
		This is a singleton class, Used to add your error, info and coustom messages to the front end without having to put them in the session
		
		@version 
			0.0.1 2005-06-22 - Sam Hennessy
				- Initial version
		@todo
				- Write class
	*/
	class Template_Messages
	{
		private static $instance = NULL;	// The objects instance as returned by Get_Instance
		private $user_message_array;		// An array of message strings that are for the user to read
		private $error_message_array;		// An array or error messages for the user to read
		private $custom_message_array;		// An array of custom message that are not shown by default in any block but can be access in the template by using tokens e.g. @@my_message@@
		
		/**
			@private
			@fn Template_Messages __construct()
			@brief
				initialize object
				
			This is a singleton class so don't new to get an instance, use Get_Instance
				
			@return Template_Messages
				Returns a new Template_Messages object
			@todo
				- 
		*/
		private function __construct()
		{
			//initialize object properties
			$this->user_message_array	= array();
			$this->error_message_array	= array();
			$this->custom_message_array	= array();
		}
		
		/**
			@public
			@fn Template_Messages Get_Instance()
			@brief
				Gives you the reference to the Template_Messages object
			
			This is a singleton class so use this static method to get a reference to the Template_Messages object
		*/
		public static function Get_Instance()
		{
			// If instance not set, get new object
			if(self::$instance == NULL)
			{
				self::$instance = new Template_Messages();
			}
			
			return self::$instance;
		}

		/**
			@public
			@fn void Add_User_Message(string $message)
			@brief
				Adds a message to be show to the user

			@param $message string 
				A message youd like displayed to the user
			@return void 
				This function doesn't return anything
		*/
		public function Add_User_Message($message)
		{
			$this->user_message_array[] = $message;
		}

		/**
			@public
			@fn void Add_Error_Message(string $message)
			@brief
				Adds an error message to be show to the user

			@param $message string 
				An error message youd like displayed to the user
			@return void 
				This function doesn't return anything
		*/
		public function Add_Error_Message($message)
		{
			$this->error_message_array[] = $message;
		}

		/**
			@public
			@fn void Add_Custom_Message(string $message, string $label)
			@brief
				Gives you the reference to the Template_Messages object

			@param $message string 
				A message youd like displayed to the user
			@param $label string 
				Lable you'd like to use as the token name. So if you use the label of 'foo', you would use the token '@@foo@@' in the template to display your message
			@return void 
				This function doesn't return anything
		*/
		public function Add_Custom_Message($message, $label)
		{
			$this->custom_message_array[$label] = $message;
		}

		/**
			@public
			@fn array Get_User_Message_Array()
			@brief
				Will give you an array of user messages

			@return array 
				Returns a zero indexed array or strings
		*/
		public function Get_User_Message_Array()
		{
			return $this->user_message_array;
		}

		/**
			@public
			@fn array Get_Error_Message_Array()
			@brief
				Will give you an array of error messages

			@return array 
				Returns a zero indexed array or strings
		*/
		public function Get_Error_Message_Array()
		{
			return $this->error_message_array;
		}

		/**
			@public
			@fn array Get_Custom_Message_Array()
			@brief
				Will give you an array of custom messages, where the key is the label and the value is the message

			@return array 
				Returns an array with the keys are the labels and the value is the message
		*/
		public function Get_Custom_Message_Array()
		{
			return $this->custom_message_array;
		}
	}
?>