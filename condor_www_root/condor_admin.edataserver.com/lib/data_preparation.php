<?
/**
  	@publicsection
	@public
	@brief
		Prepares/Checks user entered data
	@version
		$Revision: 800 $
	@todo
		
*/
include_once(DIR_LIB . 'data_validation.2.php');

class Data_Preparation
{
	function __construct(&$transport, $site_type_obj)
	{
		$this->data_validation = new Data_Validation(0,0,0,0,0,0);
		$this->site_type_obj = $site_type_obj;
		$this->transport = $transport;
	}

	/**
	 * @param $page string
	 * @desc assembles data which is broken apart in multiple fields
	 **/
	public function Assemble_Data($collected_data, $field, $glue)
	{
		$assembled = array();

		foreach($field as $field_name => $blah)
		{
			if( isset($collected_data[$field[$field_name][0]]) )
			{
				foreach($field[$field_name] as $key => $field_piece)
				{
					if( !isset($assembled[$field_name]) )
					{
						$assembled[$field_name] = '';
					}

					$assembled[$field_name] .=	( isset($glue[$field_name])	&& $key ) ? $glue[$field_name] . $collected_data[$field_piece]	: $collected_data[$field_piece];
				}
			}
		}

		return $assembled;
	}

	/**
	 * @return bool
	 * @desc run validation rules
	 * 	It can be called directly as long as the given arguments are in the same format
	 *	as they would be in an OLP object.
	 *
	 **/
	public function Validate_Data($collected_data, $page)
	{
		$normalized_data = Array();
		$errors = Array();

		// If the site type doesn't exist, default to returning the request data that was sent in.
		if (!$current_site_type = $this->site_type_obj->pages->{$page})
		{
			$return->normalized_data = (object) $collected_data;
			return $return;
		}
			
		$collected_data = (array)$collected_data;
		
		// prevent form post hijacking
		foreach ($collected_data as $k => $v)
		{
			$collected_data[$k] = preg_replace("/\r/", "", $v);
			$collected_data[$k] = preg_replace("/\n\n/", "", $v);
		}

		if ($current_site_type)
		{
			
			foreach($current_site_type as $field_name => $field_rules)
			{
				
				if ($field_name == 'next_page' || $field_name == 'stat')
					continue;
				
					
				if ($collected_data[$field_name])
				{
					
					$normalized_data[$field_name] = strtolower($this->data_validation->Normalize_Engine($collected_data[$field_name], $field_rules));
					
					if ($normalized_data[$field_name])
					{
						
						if (($field_rules['min'] && strlen($normalized_data[$field_name])<$field_rules['min']) || ($field_rules['max'] && strlen($normalized_data[$field_name])>$field_rules['max']))
						{
							$errors[$field_name] = $field_name;
						}
						
						if (!array_key_exists($field_name, $errors))
						{
							
							$val_response = $this->data_validation->Validate_Engine($normalized_data[$field_name], $field_rules);
							
							if (!$val_response['status'])
							{
								$errors[$field_name] = $field_name;
							}
							
						}
						
					}
					else
					{
						$errors[$field_name] = $field_name; 
					}
					
				}
				elseif ($field_rules['required'])
				{
					$errors[$field_name] = $field_name; 
				}
				
				
			}
		}

		if (is_array($normalized_data))
		{
			$normalized_data = array_merge($collected_data, $normalized_data);
		}
		else
		{
			$normalized_data = $collected_data;
		}

		$return->normalized_data = (object)$normalized_data;
		$return->errors = $errors;
		
		$this->transport->Add_Errors($errors);

		if ((boolean)sizeof($normalized_data))
			return $return;
		else
			return false;

	}	
}
