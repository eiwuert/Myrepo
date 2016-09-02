<?php
/**
 * NADA API
 * 
 * A set of functions to retrieve the value of a vehicle
 * 
 * 
 * @author Will!
 */
Class NADA_API
{
	protected $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Returns list of available years for vehicles
	 * 
	 * @param integer $minimum_year - The minimum year to return
	 * @return array of 4 digit numbers (19XX - 20XX)
	 */
	public function getYears($minimum_year = NULL)
	{
		$min_year = (! empty($minimum_year)) ? $minimum_year : 1998;
		
		$search_results = array();

		$query = "-- ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT DISTINCT
				nada_vehicle_description.vic_year
			FROM
				nada_vehicle_description
			ORDER BY 
				nada_vehicle_description.vic_year DESC ";
		
		$result = $this->db->Query($query);

		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($row->vic_year >= $min_year) $search_results[] = $row->vic_year;
		}
		
		return $search_results;

	}
	
	/**
	 * Get Makes available for specified year.
	 *
	 * @param int $year a 4 digit year to get all the available makes for
	 * @return array of key->value pairs.  makecode->makename
	 */
	public function getMakes($year=NULL)
	{
		$year_limitation = NULL;
		if($year)
		{
			$year_limitation = "WHERE nada_vehicle_description.vic_year = '{$year}'";
			$order = "nada_vehicle_description.make ASC";
		}
		else 
		{
			$order = "nada_vehicle_description.make ASC";
		}
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT DISTINCT
				nada_vehicle_description.vic_make,
				nada_vehicle_description.make
			FROM
				nada_vehicle_description
				{$year_limitation}
			ORDER BY 
				{$order}
			";
		$query_obj = $this->db->Query($query);
		
		
		$search_results = array();

		while($row = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			$search_results[$row->vic_make] = $row->make;
		}

		if ($search_results)
		{
		return $search_results;
		}
	}	

	/**
	 * Get series available for specified make and year.
	 *
	 * @param int $year the 4 digit year you wish to get the series for
	 * @param int $makeid the 2 digit make code that you wish to get the series for
	 * @return array of values with the code as the key and the description as the value
	 */
	public function getSeries($year, $makeid=NULL, $make_name = NULL)
	{
			
		$make = ($make_name) ? "(SELECT vic_make from nada_vehicle_description WHERE make='{$make_name}' LIMIT 1)" : "'{$makeid}'";
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT DISTINCT
				nada_vehicle_description.vic_series,
				nada_vehicle_description.series
			FROM
				nada_vehicle_description
			WHERE
				nada_vehicle_description.vic_year = '$year'
			AND
				nada_vehicle_description.vic_make = {$make}
			ORDER BY 
				nada_vehicle_description.series ASC
			";
		$query_obj = $this->db->Query($query);
		
		
		$search_results = array();

		while($row = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			$search_results[$row->vic_series] = $row->series;
		}

		if ($search_results)
		{
		return $search_results;
		}
	}	
				
	/**
	 * Gets list of available bodies for the specified year, make, and series
	 *
	 * @param int $year the 4 digit year you wish to get the bodies for
	 * @param int $makeid the 2 digit make code that you wish to get the bodies for
	 * @param int $seriesid the 2 digit series code that you wish to get the bodies for
	 * @return array of key=>value pairs with the body code as the key and body description as the value
	 */
	public function getBodies($year, $makeid=NULL, $seriesid=NULL, $make_name=NULL, $series_name=NULL)
	{
		// Allow for make/series description based lookup
		$make = ($make_name) ? "(SELECT vic_make from nada_vehicle_description WHERE make='{$make_name}' LIMIT 1)" : "'{$makeid}'";
		$series = ($series_name) ? "(SELECT vic_series from nada_vehicle_description WHERE series='{$series_name}' LIMIT 1)" : "'{$seriesid}'";
		
		if(!$make || !$series)
		{
			return;
		}
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT DISTINCT
				nada_vehicle_description.vic_body,
				nada_vehicle_description.body
			FROM
				nada_vehicle_description
			WHERE
				nada_vehicle_description.vic_year = '$year'
			AND
				nada_vehicle_description.vic_make = {$make}
			AND
				nada_vehicle_description.vic_series = {$series}
			ORDER BY 
				nada_vehicle_description.body ASC
			";
		$query_obj = $this->db->Query($query);
		
		
		$search_results = array();

		while($row = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			$search_results[$row->vic_body] = $row->body;
		}

		if ($search_results)
		{
		return $search_results;
		}
	}	

	/**
	 * Returns a list of available regions. 
	 *
	 * @return array of key=>value pairs with the region code that it belongs to as the key and the state/area's description as the value
	 */
	public function getRegions($only_states=FALSE)
	{
			
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT
				state_code,
				region_code
			FROM
				nada_state
			ORDER BY nada_state.state_name ASC
			";
		$query_obj = $this->db->Query($query);
		
		
		$search_results = array();

		while($row = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			if($only_states)
			{
				$search_results[$row->state_code] = $row->state_code;
			}
			else 
			{
				$search_results[$row->region_code][] = $row->state_code;
			}
		}

		if ($search_results)
		{
		return $search_results;
		}
	}	

	/**
	 * Gets JUST the value of a vehicle.
	 *
	 * @param int $year the 4 digit year of the vehicle
	 * @param int $makeid the 2 digit make code of the vehicle
	 * @param int $seriesid the 2 digit series code of the vehicle
	 * @param int $bodyid the 2 digit body code of the vehicle
	 * @param string $regionid the 1-2 digit region code to get the value for
	 * @param char $type the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in)
	 * @return int the value of the vehicle
	 */
	public function getValue($year, $makeid, $seriesid, $bodyid, $regionid = '01', $type = 'L', $state_code=NULL)
	{
		$region = ($state_code) ? "(SELECT region_code FROM nada_state WHERE state_code='{$state_code}' LIMIT 1)" : "'{$regionid}'";	
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT
				nada_vehicle_value.value
			FROM
				nada_vehicle_value
			WHERE
				nada_vehicle_value.vic_year = '$year'
			AND
				nada_vehicle_value.vic_make = '$makeid'
			AND
				nada_vehicle_value.vic_series = '$seriesid'
			AND
				nada_vehicle_value.vic_body = '$bodyid'
			AND 
				nada_vehicle_value.region = {$region}
			AND 
				nada_vehicle_value.value_type	= '$type'				
			";
		$query_obj = $this->db->Query($query);
		$search_results = array();
		$row = $query_obj->fetch(PDO::FETCH_OBJ);

		if ($row)
		{
		return $row->value;
		}
	}	

	/**
	 * Get's a vehicles description as well as its value based on its year, makeid,seriesid,bodyid,region, and value type
	 * or just its 10 digit VIC code 
	 *
	 * @param string $year the 4 digit year of the vehicle or the 10 digit VIC code of the vehicle
	 * @param int $makeid the 2 digit make code of the vehicle (Not needed if you're using the VIC code)
	 * @param int $seriesid the 2 digit series code of the vehicle (Not needed if you're using the VIC code)
	 * @param int $bodyid the 2 digit body code of the vehicle (Not needed if you're using the VIC code)
	 * @param string $regionid the 1-2 digit region code to get the value for (optional)
	 * @param char $type the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * @return an array with the vehicle's description, as well as value
	 * 
	 */
	public function getVehicle($year, $makeid=null, $seriesid=null, $bodyid=null, $region = '01', $valuetype="L")
	{
		
		if(strlen($year)>4)
		{
			$vic = $year;
			$makeid = substr($vic,0,2);
			$year = substr($vic,2,4);
			$seriesid = substr($vic,6,2);
			$bodyid = substr($vic,8,2);
		}
		
		$query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
		"
			SELECT
				nada_vehicle_description.vic_year,
				nada_vehicle_description.make,
				nada_vehicle_description.model,
				nada_vehicle_description.series,
				nada_vehicle_description.body,
				nada_vehicle_value.value
			FROM
				nada_vehicle_description

			JOIN
				nada_vehicle_value ON (nada_vehicle_description.vic_make = nada_vehicle_value.vic_make
										AND  nada_vehicle_description.vic_year = nada_vehicle_value.vic_year
										AND  nada_vehicle_description.vic_series = nada_vehicle_value.vic_series
										AND  nada_vehicle_description.vic_body = nada_vehicle_value.vic_body
										AND  nada_vehicle_value.region = '$region'
										AND	 nada_vehicle_value.value_type = '$valuetype'
										)				
				
			WHERE
				nada_vehicle_value.vic_year = '$year'
			AND
				nada_vehicle_value.vic_make = '$makeid'
			AND
				nada_vehicle_value.vic_series = '$seriesid'
			AND
				nada_vehicle_value.vic_body = '$bodyid'
			AND 
				nada_vehicle_value.region = '$region'
			AND 
				nada_vehicle_value.value_type	= '$valuetype'			
			
			";
		//echo $query;
		$query_obj = $this->db->Query($query);
		
		$search_results = array();

		$row = $query_obj->fetch(PDO::FETCH_OBJ);
		
		if ($row)
		{
		return $row;
		}
	}	

	/**
	 * Gets a Vehicle's information based on the 17 digit VIN# (actually 9 of the first 10 digits of it)
	 *
	 * @param string $vin the 17 digit VIN# (or just the first 10 digits)
	 * @param string $region the 1-2 digit region id code (optional)
	 * @param char $valuetype the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * 
	 * @return an array with the vehicle's description, as well as value
	 * 
	 */
        public function getVehicleByVin($vin, $regionid='01', $valuetype="L", $state_code=NULL)
        {
                $region = ($state_code) ? "(SELECT region_code FROM nada_state WHERE state_code='?' LIMIT 1)" : "?";

                $values = array();
                if ($state_code)
                {
                        $values[] = $state_code;
                }
                else
                {
                        $values[] = $regionid;
                }
                $vin_prefix = substr($vin,0,8).'*'.substr($vin,9,1);

                $query = //"-- ".__FILE__.":".__LINE__.":".__METHOD__."()
                "
                        SELECT
                                nada_vehicle_vin.vin_prefix,
                                nada_vehicle_description.vic_year,
                                nada_vehicle_description.make,
                                nada_vehicle_description.model,
                                nada_vehicle_description.series,
                                nada_vehicle_description.body,
                                nada_vehicle_value.value
                        FROM
                                nada_vehicle_vin
                        JOIN
                                nada_vehicle_description ON (nada_vehicle_vin.vic_make = nada_vehicle_description.vic_make
                                                                                AND  nada_vehicle_vin.vic_year = nada_vehicle_description.vic_year
                                                                                AND  nada_vehicle_vin.vic_series = nada_vehicle_description.vic_series
                                                                                AND  nada_vehicle_vin.vic_body = nada_vehicle_description.vic_body
                                                                                )
                        JOIN
                                nada_vehicle_value ON (nada_vehicle_description.vic_make = nada_vehicle_value.vic_make
                                                                                AND  nada_vehicle_description.vic_year = nada_vehicle_value.vic_year
                                                                                AND  nada_vehicle_description.vic_series = nada_vehicle_value.vic_series
                                                                                AND  nada_vehicle_description.vic_body = nada_vehicle_value.vic_body
                                                                                AND  nada_vehicle_value.region = {$region}
                                                                                AND      nada_vehicle_value.value_type = ?
                                                                                )

                        WHERE
                                nada_vehicle_vin.vin_prefix = ?

                        ";
                $values[] = $valuetype;
                $values[] = $vin_prefix;
                $query_obj = $this->db->queryPrepared($query, $values);

                $search_results = array();

                $row = $query_obj->fetch(PDO::FETCH_OBJ);

                if ($row)
                {
                return $row;
                }
        }

	/**
	 * Gets a VIC id values based on description data
	 *
	 * @param string $vin the 17 digit VIN# (or just the first 10 digits)
	 * @param string $region the 1-2 digit region id code (optional)
	 * @param char $valuetype the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * 
	 * @return an array with the vehicle's description, as well as value
	 * 
	 */
	        public function getVicData($make, $model, $series, $body, $year)
        {
                //echo "Getting vic data: {$make}- {$model}- {$series}- {$body}- {$year}";
                $query = "
                        SELECT
                                *
                        FROM
                                nada_vehicle_description
                        WHERE
                                make = ?
                        AND
                                model = ?
                        AND
                                series = ?
                        AND
                                body = ?
                        AND
                                vic_year = ?
                ";
                $values = array(
                        $make,
                        $model,
                        $series,
                        $body,
                        $year
                );
                $result = $this->db->queryPrepared($query, $values);
                $row = $result->fetch(PDO::FETCH_OBJ);

                if ($row)
                {
                        return $row;
                }
                else
                {
                        return NULL;
                }
        }

	/**
	 * Gets the value for a vehicle from description values
	 *
	 * @param string $make The description of the make of the vehicle
	 * @param string $region the 1-2 digit region id code (optional)
	 * @param char $valuetype the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * 
	 * @return an array with the vehicle's description, as well as value
	 * 
	 */
	public function getValueFromDescription($make, $model, $series, $body, $year)
	{
		$vic_data = $this->getVicData($make, $model, $series, $body, $year);

		$value = $this->getValue(
			empty($vic_data->vic_year) ? NULL : $vic_data->vic_year,
			empty($vic_data->vic_make) ? NULL : $vic_data->vic_make,
			empty($vic_data->vic_series) ? NULL : $vic_data->vic_series,
			empty($vic_data->vic_body) ? NULL : $vic_data->vic_body);
		
		return $value;
	}
}
