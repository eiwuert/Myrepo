<?php

	// Version 1.0.0
	
	/* DESIGN TYPE
		static
	*/

	/* UPDATES
		Features:
			Handles and configures incoming arguments for scripts used at the command line or through cron ONLY.

		Bugs:
	*/

	/* PROTOTYPES
	* required to be called
		* bool Pass_Args ()
		bool Setup_Arg_Switch($arg, $options="", $message="");
		bool Setup_Arg_Param_Opt($arg, $options="", $message="");
		bool Setup_Arg_Param($arg, $param_req=true, $options="", $message="");
		bool Setup_Arg_Param_Array($arg, $param_req=true, $options="", $message="");
		array arg_array , array of registered arguments using "Setup_Args_*" methods
		* array Get_Args ($arg_array)
		array error_array , array of generic errors not specific to each argument
		int arg_count , gives number of passed arguments after Get_Args is called
	*/
	
	/* OPTIONAL CONSTANTS
	*/

	/* SAMPLE USAGE
	Pass_Args is called to instantiate the object, the all necessary arguments are setup with the "Setup_Arg_*"  methods.
	Get_Args is then called to compare the given args to the defined args, then returns an object with the argument list, properties, messages, etc...
	
	This class can be used for command line interfaces or any other argument list that can be send to the class for parsing.
	
	$arg: string - name of the switch or argument with a "-" in front, like "-live" or "-s"
	$param_req: bool - if true, will set error message to object if argument parameters don't exist
	$options: string - not currently used, reserved for future functionality
	$message: string - message to display if argument exists
	$arg_array: array - array of argument and parameters
	
	Example:
		// Example of arguments being passed via command line:
		// "--" is used to separate the arguments from the command so that they pass into the script, not the PHP parser
		#> php my_script.php -- -t -s -email email@mail.com -promo_code 87923
		
  		$cli_args = new Pass_Args ();
  		
  		// Switches are simple boolean options that you can check for with no additional parameters.
  		$cli_args->Setup_Arg_Switch("-t");
  		$cli_args->Setup_Arg_Switch("-s", $options="", $message="Simple mode is activated.");
  		
  		// Param_Opts are simply switches that you can use as a switch, but with an optional parameter passed in.
  		// Very passive: the parameter object property wont be set if it doesn't exist. Should be used when values aren't important, 
  		// but the possibility of a parameter exists. All data before the next argument will be set to a single parameter.
		$cli_args->Setup_Arg_Param_Opt("-email", $options="", $message="Email has been diverted to default email.");
		
		// Same as Param_Opts, but $param_req defaults to true. Expects a single parameter, or none if $param_reg=false. 
		// This allows errors to be set if $param_req is set to true. The parameter object property will get set to "" if none are passed.
		// All data before the next argument will be set to a single parameter.
		$cli_args->Setup_Arg_Param("-promo_code", $param_req=true, $options="", $message="");
		$cli_args->Setup_Arg_Param("-promo_sub_code", $param_req=true, $options="", $message="");
		
		// Param_Arrays can be passed multiple space delimited parameters and are set to a comma delimited string within the returned object. 
		// $param_req is set to true by default.
		$cli_args->Setup_Arg_Param_Array("-color_options", $param_req=true, $options="", $message="");
		
		// Returned data is an array of objects
		$passed_args = $cli_args->Get_Args ($arg_array);
		
		
		// Returned array/object example
		
		Array
		(
   			[0] => stdClass Object
        				(
           				[arg] => -t
            				[type] => switch
       				 )

    			[1] => stdClass Object
        				(
            				[arg] => -s
            				[type] => switch
            				[message] => Simple mode is activated.
       				)

    			[2] => stdClass Object
        				(
            				[arg] => -email
            				[type] => param_opt
            				[param_string] => sample@email.com
            				[message] => Email has been diverted to default email.
        				)

    			[3] => stdClass Object
        				(
            				[arg] => -promo_code
            				[type] => param
            				[param_string] => 99832
        				)

    			[4] => stdClass Object
        				(
            				[arg] => -promo_sub_code
            				[type] => param
            				[errors] => Argument: -promo_sub_code, parameter missing.
            				[param_string] =>
        				)

   			[5] => stdClass Object
        				(
            				[arg] => -color_options
            				[type] => param_array
            				[param_string] => blue,red,purple
        				)

		)

		
		
	*/

class Pass_Args
{
	var $arg_array;
	var $arg_count;
	var $error_array;
	
	function Pass_Args ()
	{
		$this->arg_array[] = new stdClass();
		$this->arg_count = 0;
		$this->error_array = array ();
		
		return true;
	}
	
	function Setup_Arg_Switch ($arg, $options="", $message="")
	{
		$this->arg_array[$this->arg_count]->type = "switch";
		$this->arg_array[$this->arg_count]->arg = $arg;
		if ($message != "")
		{
			$this->arg_array[$this->arg_count]->message = $message;	
		}
					
		$this->arg_count++;
		return true;
	}
	
	function Setup_Arg_Param_Opt ($arg, $req=false, $options="", $message="")
	{
		$this->arg_array[$this->arg_count]->type = "param_opt";
		$this->arg_array[$this->arg_count]->arg = $arg;
		$this->arg_array[$this->arg_count]->req = $req;
		if ($message != "")
		{
			$this->arg_array[$this->arg_count]->message = $message;	
		}
		
		$this->arg_count++;
		return true;
	}
	
	function Setup_Arg_Param ($arg, $req=false, $param_req=true, $options="", $message="")
	{
		$this->arg_array[$this->arg_count]->type = "param";
		$this->arg_array[$this->arg_count]->arg = $arg;
		$this->arg_array[$this->arg_count]->req = $req;
		$this->arg_array[$this->arg_count]->param_req = $param_req;
		if ($message != "")
		{
			$this->arg_array[$this->arg_count]->message = $message;	
		}
		
		$this->arg_count++;
		return true;
	}
	
	function Setup_Arg_Param_Array ($arg, $req=false, $param_req=true, $options="", $message="")
	{
		$this->arg_array[$this->arg_count]->type = "param_array";
		$this->arg_array[$this->arg_count]->arg = $arg;
		$this->arg_array[$this->arg_count]->req = $req;
		$this->arg_array[$this->arg_count]->param_req = $param_req;
		if ($message != "")
		{
			$this->arg_array[$this->arg_count]->message = $message;	
		}
		
		$this->arg_count++;
		return true;
	}
	
	function Get_Args ($incoming_arg_array)
	{
		if ((count ($incoming_arg_array) > 0) && (count ($this->arg_array) > 0))
		{
			$legit_arg_array = array(); 
			foreach ($this->arg_array as $possible_args)
			{
				if (in_array ($possible_args->arg, $incoming_arg_array))	
				{
					$legit_arg_array[] = $possible_args->arg;
				}
				elseif ($possible_args->req)
				{
					$this->error_array =  "Argument: " . $possible_args->arg  . " is missing.";
				}
			}
			
			// set all args to single string
			$full_arg_string = implode (" ", $incoming_arg_array);
			
			//create parsable arg string
			$delimited_arg_string = $full_arg_string;
			foreach ($legit_arg_array as $legit_arg)
			{
				$delimited_arg_string = str_replace ($legit_arg, "|" . $legit_arg . "::", $delimited_arg_string)	;	
			} 
			$delimited_arg_string = substr ($delimited_arg_string, strpos ($delimited_arg_string, "|"));
			
			$args_passed[] = new stdClass();
			$args_passed_count = 0;
			foreach ($this->arg_array as $defined_arg)
			{
				if (in_array ($defined_arg->arg, $legit_arg_array))
				{	
					$args_passed[$args_passed_count]->arg = $defined_arg->arg;
					$args_passed[$args_passed_count]->type = $defined_arg->type;
					// parse params
					if (($defined_arg->type == "param_opt") || ($defined_arg->type == "param") || ($defined_arg->type == "param_array"))
					{
						preg_match ("/(\|" . $defined_arg->arg . ")::([^\|]+)/", $delimited_arg_string, $matches);
						$arg_string = $matches[2];
						
						if ($defined_arg->param_req && (trim ($arg_string) == ""))
						{
							$args_passed[$args_passed_count]->errors = "Argument: " . $defined_arg->arg  . ", parameter missing.\n";	
						}
						switch ($defined_arg->type)
						{
							case "param_opt":
								if (trim ($arg_string) != "")
								{
									$args_passed[$args_passed_count]->param_string = trim ($arg_string);	
								}
								break;
							case "param":
								$args_passed[$args_passed_count]->param_string = trim ($arg_string);
								break;
							case "param_array":
								$args_passed[$args_passed_count]->param_string = preg_replace ("/ /", ",", trim ($arg_string));
								break;
							default:
								break;
						}
					}
					if ($defined_arg->message)
					{
						$args_passed[$args_passed_count]->message = $defined_arg->message;	
					}
				}
				
				$args_passed_count++;
			}
			
			return $args_passed;
		}		
	}	
}

?>