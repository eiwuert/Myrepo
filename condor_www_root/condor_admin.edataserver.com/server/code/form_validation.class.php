<?php

	/**
	 * Condor form verification
	 *
	 * Revision History:
	 *	bszerdy - 02/10/2009 - Added a constructor and the Find_Simialar_Tokens function
	 *
	 * @package condor
	 */
	class Form_Validation
	{
		private $tokens;
		private $server;

		public function __construct(Server $server)
		{
			$this->server	= $server;
			$ctq			= new Condor_Template_Query($server);
			$this->tokens	= $ctq->Fetch_Tokens();
		}

		/**
		 * Returns an array for close matching tokens.
		 * 
		 * @param string $for_token 
		 * @return array
		 */
		public function Find_Similar_Tokens($for_token)
		{
			$retval = array();
			$matching_percentage = 70.0;

			// strip out the delimiters
			$for_token = preg_replace('/%/', '', $for_token);

			foreach ($this->tokens as $token)
			{
				$match_chars = similar_text(
					strtolower($for_token),
					strtolower(preg_replace('/%/', '', $token->token)),
					$percent
				);

				// find the percentage of matching characters
				$len = strlen($for_token);
				$pec = ceil(($match_chars / $len) * 100);

				if (($percent >= (int) $matching_percent)
					&& ($pec >= $matching_percentage))
				{
					$retval[] = $token;
				}
			}
			
			return $retval;
		}

		/**
		 * When using highlighted search in Firefox, if you save while the highlght
		 *  is still on, it will save the highlight with the file. This function
		 *	removes the highlighting.
		 *
		 * @example <span class="__mozilla-findbar-search" style="padding: 0pt; background-color: yellow;
		 *			color: black; display: inline; font-size: inherit;">TOKEN</span>
		 * 
		 * @param String $data
		 * @return string
		 */
		public function Strip_Firefox_Highlights($data)
		{
			$find_me = "__mozilla-findbar-search";

			// doesn't exist
			if (stristr($data, $find_me) === FALSE)
			{
				return ($data);
			}
			else
			{
				// split the data into an array to make it faster and easier to deal with
				$data_array = explode('%%%', $data);
				
				for ($i = 1; $i < count($data_array); $i++)
				{
					if (stristr($data_array[$i], $find_me) !== FALSE)
					{
						$str = str_ireplace("</span>", "", $data_array[$i]);
						$temp = array_reverse(str_split($str));
						for ($j = 0; $j < count($temp); $j++)
						{
							if ($temp[$j] == ">")
							{
								$data_array[$i] = implode(array_reverse(array_slice($temp, 0, $j)));
							}
						}
					}
				}
				$data = implode('%%%', $data_array);
			}
			return $data;
		}

		public function Normalize_Request_Fields($field_data, &$request)
		{
			if (is_array($field_data))
			{
				foreach ($field_data as $field_name)
				{
					if (isset($request->{$field_name}))
						$request->{$field_name} = trim($request->{$field_name});
				}
			}
			else if (is_string($field_data))
			{
				if (isset($request->{$field_data}))
					$request->{$field_data} = trim($request->{$field_data});
			}
		}
		
		public function Validate_Request_Fields($field_data, &$request)
		{
			$error = array();
	
			if (is_array($field_data))
			{
				foreach ($field_data as $field_name => $field_mode)
				{
					if ((!isset($request->{$field_name}) && is_string($field_mode)) || (is_string($field_mode) && !strlen($request->{$field_name}) && $field_mode == 'REQUIRED'))
					{
						$error[$field_name] = 'REQUIRED';
					}
					else if (is_array($field_mode))
					{
						list($required, $mode) = $field_mode;
						if (!strlen($request->{$field_name}) && $required)
						{
							$error[$field_name] = 'REQUIRED';
						}
						else if (strlen($request->{$field_name}))
						{
							switch (strtolower($mode))
							{
								case 'date_mm/dd/yyyy':
									if (!ereg("([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})", $request->{$field_name}, $match))
									{
										$error[$field_name] = 'INVALID';
									}
									break;
								case 'number':
									if (!is_numeric($request->{$field_name}))
									{
										$error[$field_name] = 'INVALID';
									}
									break;
									
								case 'email':
									if(!eregi("^[a-zA-Z0-9]+[_a-zA-Z0-9-]*(\.[_a-z0-9-]+)*@[a-z??????0-9]+(-[a-z??????0-9]+)*(\.[a-z??????0-9-]+)*(\.[a-z]{2,4})$", $request->{$field_name}))
									{
										$error[$field_name] = 'INVALID';
									}
									break;
								case 'phone':
									if (strlen($request->{$field_name}) < 10 || strlen($request->{$field_name}) > 11 || !is_numeric($request->{$field_name}))
									{
										$error[$field_name] = 'INVALID';
									}
									break;
								case 'string':
								default:
									break;
							}
						}
							
					}
				}
			}
				
			return $error;
		}
	}
?>