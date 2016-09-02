<? 
require_once(WWW_DIR.'config.php');
//require_once(LIB_DIR."mysqli.1e.php");
/**
 * NADA IMPORT (for the v5 flat files)
 * 
 * 
 * Populates all the NADA tables with data from NADA flat files.
 * 
 * 
 * Remember: Need to check and see if the rows have 1 or 2 extra bytes (VALIDATION NEEDED!)
 * 
 */

class NADA_Import_V5
{

	public function __construct(DB_Database_1 $mysqli)
	{
		define(auto_detect_line_endings,true);
		set_time_limit(0);
		$this->db = ECash::getMasterDb();
	}

	public function populateAll()
	{
		$current_dir = substr(__FILE__, 0 , strripos(__FILE__, "/") + 1);
		$this->populateVehicleDescription($current_dir . "VicDescriptions.DAT");
		$this->populateVehicleValues($current_dir . "VicValues.DAT");
		$this->populateVehicleSegments($current_dir . "VehicleSegments.DAT");
		$this->populateTruckDuties($current_dir . "TruckDuties.DAT");
		$this->populateVehicleAttributes($current_dir . "VicAttributes.DAT");
		$this->populateAttributeType($current_dir . "AttributeTypes.DAT");
		$this->populateAccessoryDescription($current_dir . "VacDescriptions.DAT");
		$this->populateAccessoryValue($current_dir . "VacValues.DAT");
		$this->populateAccessoryCategory($current_dir . "VacCategories.DAT");
		$this->populateAccessoryExclude($current_dir . "VacExcludes.DAT");
		$this->populateAccessoryInclude($current_dir . "VacIncludes.DAT");
		$this->populateAccessoryBodyInclude($current_dir . "VacBodyIncludes.DAT");
		$this->populateAccessoryBodyNotAvailable($current_dir . "VacBodyNotAvailables.DAT");
		$this->populateMileage($current_dir . "Mileage.DAT");
		$this->populateVIN($current_dir . "VinPrefix.DAT");
		$this->populateRegion($current_dir . "Regions.DAT");
		$this->populateState($current_dir . "States.DAT");
		$this->populateValueType($current_dir . "ValueTypes.DAT");
//		$this->Populate_Book_Flag("/virtualhosts/ecash_common/nada/BookFlags.DAT");
		$this->populateAccessoryVIN($current_dir . "VinVacs.DAT");
		$this->populateVINAlternateVehicle($current_dir . "VinAlternateVics.DAT");
		$this->populateGVWRating($current_dir . "GvwRatings.DAT");
		$this->populateTonCode($current_dir . "TonRatings.DAT");
	}

	public function clearTable($table)
	{
		$query = "DELETE FROM {$table}";
		$this->db->Query($query);
	}
	
	public function formattingCheck($contents,$type=null)
	{
		
		switch ($type)
		{
			default:

				if (is_numeric(substr($contents,0,6)))
				{
					return true;
				}
			break;
		}
		
		
		return false;
			
	}
	
	public function populateTonCode($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,15))
		{

			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$ton = array();


			$ton['period']					=	substr($contents,0,6);
			$ton['ton_code']				=	substr($contents,6,1);
			$ton['ton_rating']				=	substr($contents,7,5);
								
			$ton = $this->escapeData($ton);
			if(!$this->insertRecord('nada_ton_code',$ton))
			{
				return false;
			}
			

		}
		return true;
	}
		
	
	
	public function populateBookFlag($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,58))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$book = array();


			$book['period']					=	substr($contents,0,6);
			$book['book_flag']				=	substr($contents,6,1);
			$book['book_name']				=	substr($contents,7,56);
					
			$book = $this->escapeData($book);
			
			if(!$this->insertRecord('nada_book_flag',$book))
			{
				return false;
			}
			

		}
		return true;
	}
	
	
	
	
		
	public function populateGVWRating($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,22))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$gvw = array();


			$gvw['period']					=	substr($contents,0,6);
			$gvw['gvw_code']				=	substr($contents,6,1);
			$gvw['gvw_low']					=	substr($contents,7,6);
			$gvw['gvw_high']				=	substr($contents,13,6);
			
			$gvw = $this->escapeData($gvw);
			if(!$this->insertRecord('nada_gvw_rating',$gvw))
			{
				return false;
			}
		}
		return true;
	}
		
	
	
	
		
	public function populateVINAlternateVehicle($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,29))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$alt = array();


			$alt['period']					=	substr($contents,0,6);
			$alt['vic_make']				=	substr($contents,6,2);
			$alt['vic_year']				=	substr($contents,8,4);
			$alt['vic_series']				=	substr($contents,12,2);
			$alt['vic_body']				=	substr($contents,14,2);
			$alt['alt_make']				=	substr($contents,16,2);
			$alt['alt_year']				=	substr($contents,18,4);
			$alt['alt_series']				=	substr($contents,22,2);
			$alt['alt_body']				=	substr($contents,24,2);
					
			$alt = $this->escapeData($alt);

			if(!$this->insertRecord('nada_vehicle_alternate',$alt))
			{
				return false;
			}
		}
		return true;
	}
		
	
	
	
	public function populateAccessoryVIN($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,29))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$vin = array();


			$vin['period']					=	substr($contents,0,6);
			$vin['vin_prefix']				=	substr($contents,6,10);
			$vin['vin_sequence']			=	substr($contents,16,7);
			$vin['vin_vac']					=	substr($contents,23,3);
						
			

			
			$vin = $this->escapeData($vin);
			if(!$this->insertRecord('nada_accessory_vin',$vin))
			{
				return false;
			}
		}
		return true;
	}
		
	
	public function populateValueType($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,48))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$type = array();


			$type['period']						=	substr($contents,0,6);
			$type['value_type']					=	substr($contents,6,2);
			$type['book_flag']					=	substr($contents,8,1);
			$type['value_name']					=	substr($contents,9,36);
						
			

			
			$type = $this->escapeData($type);
			if(!$this->insertRecord('nada_value_type',$type))
			{
				return false;
			}
		}
		return true;
	}
	

	
	public function populateState($fileName)	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,63))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$state = array();


			$state['period']					=	substr($contents,0,6);
			$state['state_name']				=	substr($contents,6,50);
			$state['state_code']				=	substr($contents,56,2);
			$state['region_code']				=	substr($contents,58,2);
						
			

			
			$state = $this->escapeData($state);
			if(!$this->insertRecord('nada_state',$state))
			{
				return false;
			}

		}
		return true;
	}
	
	
	public function populateRegion($fileName)
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,61))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$region = array();


			$region['period']					=	substr($contents,0,6);
			$region['region_code']				=	substr($contents,6,2);
			$region['region_name']				=	substr($contents,8,50);
						
			

			
			$region = $this->escapeData($region);
			if(!$this->insertRecord('nada_region',$region))
			{
				return false;
			}
		}
		return true;
	}
	
	
	
	public function populateVIN($fileName)
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,40))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$vin = array();


			$vin['period']					=	substr($contents,0,6);
			$vin['vin_prefix']				=	substr($contents,6,10);
			$vin['vin_sequence']			=	substr($contents,16,7);
			$vin['vic_make']				=	substr($contents,23,2);
			$vin['vic_year']				=	substr($contents,25,4);
			$vin['vic_series']				=	substr($contents,29,2);
			$vin['vic_body']				=	substr($contents,31,2);
			$vin['gvw']						=	substr($contents,33,1);
			$vin['ton_rating_low']			=	substr($contents,34,1);
			$vin['ton_rating_high']			=	substr($contents,35,1);
			$vin['book_flag']				=	substr($contents,36,1);
			
		
			
			

			
			$vin = $this->escapeData($vin);
			if(!$this->insertRecord('nada_vehicle_vin',$vin))
			{
				return false;
			}
		}
return true;
	}
	
	
	
	
	public function populateMileage($fileName)
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,57))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$mileage = array();


			$mileage['period']					=	substr($contents,0,6);
			$mileage['vic_year']				=	substr($contents,6,4);
			$mileage['mileage_class']			=	substr($contents,10,1);
			$mileage['range_high']				=	substr($contents,11,7);
			$mileage['range_low']				=	substr($contents,18,7);
			$mileage['percent_flag']			=	substr($contents,25,1);
			$mileage['amount']					=	substr($contents,26,6);
			$mileage['ctg_range_high']			=	substr($contents,32,7);
			$mileage['ctg_range_low']			=	substr($contents,39,7);
			$mileage['ctg_low_adjust']			=	substr($contents,46,4);
			$mileage['ctg_high_adjust']			=	substr($contents,50,4);
		
			
			

			
			$mileage = $this->escapeData($mileage);
			if(!$this->insertRecord('nada_mileage',$mileage))
			{
				return false;
			}

		}
		return true;
	}
	
	
	public function populateAccessoryBodyNotAvailable($fileName)
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,22))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$na = array();


			$na['period']				=	substr($contents,0,6);
			$na['vic_make']				=	substr($contents,6,2);
			$na['vic_year']				=	substr($contents,8,4);
			$na['vic_series']			=	substr($contents,12,2);
			$na['vic_body']				=	substr($contents,14,2);
			$na['unavailable_vac']		=	substr($contents,16,3);
			
		
			
			

			
			$na = $this->escapeData($na);
			if(!$this->insertRecord('nada_accessory_body_unavailable',$na))
			{
				return false;
			}

		}
return true;	
	}
	
	
	public function populateAccessoryBodyInclude($fileName)
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,22))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$include = array();


			$include['period']				=	substr($contents,0,6);
			$include['vic_make']			=	substr($contents,6,2);
			$include['vic_year']			=	substr($contents,8,4);
			$include['vic_series']			=	substr($contents,12,2);
			$include['vic_body']			=	substr($contents,14,2);
			$include['included_vac']		=	substr($contents,16,3);
			
		
			
			

			
			$include = $this->escapeData($include);
			if(!$this->insertRecord('nada_accessory_body_include',$include))
			{
				return false;
			}
		}
return true;
	}
	
	
	
	
	public function populateAccessoryInclude($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,23))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$include = array();


			$include['period']						=	substr($contents,0,6);
			$include['vic_make']					=	substr($contents,6,2);
			$include['vic_year']					=	substr($contents,8,4);
			$include['option_table']				=	substr($contents,12,2);
			$include['vac']							=	substr($contents,14,3);
			$include['included_vac']				=	substr($contents,17,3);
						
			$include = $this->escapeData($include);
	if(!$this->insertRecord('nada_accessory_include',$include))
	{
		return false;
	}
		}
return true;
	}
	
	public function populateAccessoryExclude($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,23))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$exclude = array();


			$exclude['period']						=	substr($contents,0,6);
			$exclude['vic_make']					=	substr($contents,6,2);
			$exclude['vic_year']					=	substr($contents,8,4);
			$exclude['option_table']				=	substr($contents,12,2);
			$exclude['vac']							=	substr($contents,14,3);
			$exclude['excluded_vac']				=	substr($contents,17,3);
			
			
			$exclude = $this->escapeData($exclude);
			if(!$this->insertRecord('nada_accessory_exclude',$exclude))
			{
				return false;
			}
			
		}
		return true;
	}
	
	
	
	public function populateAccessoryCategory($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,48))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$category = array();


			$category['period']						=	substr($contents,0,6);
			$category['category_id']				=	substr($contents,6,3);
			$category['category_description']		=	substr($contents,9,36);
			
			
			
			$category = $this->escapeData($category);
			if(!$this->insertRecord('nada_accessory_category',$category))
			{
				return false;
			}

		}
return true;
	}
	
	
	
		public function populateAccessoryValue($fileName)	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,30))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$value = array();


			$value['period']					=	substr($contents,0,6);
			$value['vic_make']					=	substr($contents,6,2);
			$value['vic_year']					=	substr($contents,8,4);
			$value['option_table']				=	substr($contents,12,2);
			$value['vac']						=	substr($contents,14,3);
			$value['region']					=	substr($contents,17,2);
			$value['value_type']				=	substr($contents,19,2);
			$value['value']						=	substr($contents,21,6);
		
			
		
			
			$value = $this->escapeData($value);
			if(!$this->insertRecord('nada_accessory_value',$value))
			{
				return false;
			}
			
		}
		return true;
	}
	
	
		public function populateAccessoryDescription($fileName)
	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,51))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$accessory = array();


			$accessory['period']				=	substr($contents,0,6);
			$accessory['vac']					=	substr($contents,6,3);
			$accessory['accessory_description']	=	substr($contents,9,36);
			$accessory['accessory_category']	=	substr($contents,45,3);
		
			
			

			
			$accessory = $this->escapeData($accessory);
			if(!$this->insertRecord('nada_accessory_description',$accessory))
			{
				return false;
			}
			
		}
		return true;
	}
	
	
	public function populateAttributeType($fileName)
	
	{
	
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,48))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$attribute = array();


			$attribute['period']				=	substr($contents,0,6);
			$attribute['attribute_id']			=	substr($contents,6,3);
			$attribute['attribute_description']	=	substr($contents,9,36);
		
			
			

			
			$attribute = $this->escapeData($attribute);
			
			if(!$this->insertRecord('nada_attribute_type',$attribute))
			{
				return false;
			}
			
		}
		return true;
	}
	
	public function populateVehicleAttributes($fileName)
	{
		//note:  the file was empty, so I can't verify that the data matches up
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,277))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$attribute = array();


			$attribute['period']			=	substr($contents,0,6);
			$attribute['vic_make']			=	substr($contents,6,2);
			$attribute['vic_year']			=	substr($contents,8,4);
			$attribute['vic_series']		=	substr($contents,12,2);
			$attribute['vic_body']			=	substr($contents,14,2);
			$attribute['attribute_id']		=	substr($contents,16,3);
			$attribute['attribute_value']	=	substr($contents,19,255);
		
			
			

			
			$attribute = $this->escapeData($attribute);
			if(!$this->insertRecord('nada_vehicle_attribute',$attribute))
			{
				return false;
			}
		}
return true;
	}
	

	
	public function populateTruckDuties($fileName)
	
	{
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,46))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$duty = array();

			$duty['period']			=	substr($contents,0,6);
			$duty['duty_id']			=	substr($contents,6,1);
			$duty['duty_description']			=	substr($contents,7,36);
			
			

			
			$duty = $this->escapeData($duty);
			if(!$this->insertRecord('nada_truck_duty',$duty))
			{
				return false;
			}
		}
return true;
	}
	
	
	public function populateVehicleSegments($fileName)
	
	{
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,48))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$segment = array();

			$segment['period']			=	substr($contents,0,6);
			$segment['segment_id']			=	substr($contents,6,3);
			$segment['segment_description']			=	substr($contents,9,36);
			
			

			
			$segment = $this->escapeData($segment);
			
			if(!$this->insertRecord('nada_vehicle_segment',$segment))
			{
				return false;
			}
			
		}
		return true;
	}
	
	
	public function populateVehicleValues($fileName)
	
	{
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,29))
		{
			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$value = array();

			$value['period']			=	substr($contents,0,6);
			$value['vic_make']			=	substr($contents,6,2);
			$value['vic_year']			=	substr($contents,8,4);
			$value['vic_series']		=	substr($contents,12,2);
			$value['vic_body']			=	substr($contents,14,2);
			$value['region']			=	substr($contents,16,2);
			$value['value_type']		=	substr($contents,18,2);
			$value['value']				=	substr($contents,20,6);
			
			
				
			$value = $this->escapeData($value);
			if(!$this->insertRecord('nada_vehicle_value',$value))
			{
				return false;
			}
			
		}
		return true;
	}
	
	
	public function populateVehicleDescription($fileName)
	
	{
		$fp	= fopen($fileName, "r");
		
		while ($contents = fgets($fp,218))
		{

			if(!$this->formattingCheck($contents))
			{
				return false;
			}
			$description = array();

			$description['period']			=	substr($contents,0,6);
			$description['vic_make']		=	substr($contents,6,2);
			$description['vic_year']		=	substr($contents,8,4);
			$description['vic_series']		=	substr($contents,12,2);
			$description['vic_body']		=	substr($contents,14,2);
			$description['vid']				=	substr($contents,16,11);
			$description['make']			=	substr($contents,27,36);
			$description['model']			=	substr($contents,63,36);
			$description['series']			=	substr($contents,99,36);
			$description['body']			=	substr($contents,135,36);
			$description['vehicle_segment']	=	substr($contents,171,3);
			$description['model_code']		=	substr($contents,174,10);
			$description['msrp']			=	substr($contents,184,6);
			$description['weight']			=	substr($contents,190,6);
			$description['gvw']				=	substr($contents,196,6);
			$description['gcw']				=	substr($contents,202,6);
			$description['mileage_class']	=	substr($contents,208,1);
			$description['truck_duty']		=	substr($contents,209,1);
			$description['option_table']	=	substr($contents,210,2);
			$description['shared_table']	=	substr($contents,212,2);
			$description['book_flag']		=	substr($contents,214,1);
			
			

			
			$description = $this->escapeData($description);
						
			if(!$this->insertRecord('nada_vehicle_description',$description))
			{
				return false;
			}
			}
		return true;
	}
	
	
	
	
	public function Get_Query_Data($table_name, $field_array, $separator=',')
	{
		foreach ($field_array as $key => $val)
		{
			$stmt .= ((($chk) ? $separator : '')." $key = " . (((strpos($val, '(') !== FALSE)  && (strpos($val, ')') !== FALSE) && preg_match('/(date)|(_id)/', $key)) ?  $val : "'{$val}'"))."\n";
			$chk = true;
		}
		return $stmt;
	}

	public function insertRecord($table_name, $field_array)
	{
		$stmt = 'INSERT INTO '.$table_name.' SET ';

		$stmt .= $this->Get_Query_Data($table_name, $field_array, ',');

		$this->db->Query($stmt);
		$insert_id = $this->db->lastInsertId();

		return $insert_id;
	}
	
	public function escapeData($data)
	{
		// excluded data from being normalized
		$excluded_keys = array('authentication');

		foreach($data as $key => $sub_data)
		{
			if (!in_array($key, $excluded_keys))
			{

				if( is_array($sub_data) || is_object($sub_data) )
				{
					is_object($data) ? $escaped->{$key} = $this->escapeData($sub_data) : $escaped[$key] = $this->escapeData($sub_data);
				}
				else
				{
					is_object($data) ? $escaped->{$key} = $this->escapeValue($sub_data) : $escaped[$key] = $this->escapeValue($sub_data);
				}
			}
			else
			{
				$escaped[$key] = $sub_data;
			}
		}
		return $escaped;
	}

	public function escapeValue($value)
	{
		if((strpos($value, '(') !== FALSE)  && (strpos($value, ')') !== FALSE) )
		{
			return $value;
		}
		else
		{
			return mysql_escape_string($value);
		}
	}

	public function undoEscapeValue($value)
	{
		
		$value = html_entity_decode($value);
		$value = stripslashes($value);
		
		return $value;
		
	}
}
?>