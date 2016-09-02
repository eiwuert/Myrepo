<?php
/**
        @publicsection
        @public
        @brief
            This class returns configuration info for the site
        
         This class will return configuration information for a site. It connects
         to the management database. It is a combination of the config.5 and init_5 
         classes. It forgoes the prpc call and connects directly to the database.
         
        @version
            1.0 2006-03-02 - Jason Gabriele
                -Initial release
        @todo  
 */
class Config_6
{
    private $sql;
    
    /**
     * The database name to use.
     *
     * @var string
     */
    protected $database;
	
    /**
        @publicsection
        @public
        @fn void __construct($sql)
        @brief
            Construct method
        
        The Constructor method
        
        @param $sql object A Database object
     */
	public function __construct($sql)
	{
        $this->sql = $sql;        
        $this->database = empty($sql->db_info["db"]) ? NULL : $sql->db_info["db"];
	}
	
	
	public function Return_Error($e)
	{
		return $e;
	}
	
    /**
        @publicsection
        @public 
        @fn object Get_Site_Config($license,$promo_id,$promo_sub_code,$page)
        @brief
            Get the sites configuration
        
        Returns the sites configuration as an object
        
        @param $license string 32+ character license
        @param $promo_id int Promo ID
        @param $promo_sub_code int Promo Sub Code
        @param $page int NO LONGER USED
        @return
            Returns the site configuration as an object
     */		
	public function Get_Site_Config ($license, $promo_id = NULL, $promo_sub_code = NULL)
	{
	
		// Simple validation on license key.
		if( !is_string($license) )
		{
			throw new Exception("License key should be a string 32 characters or longer.");
		}
		
		// If we do not have a proper promo_id change to the default of 10000
		if( is_null($promo_id) || !is_numeric($promo_id) )
		{
			$promo_id = 10000;
		}
		
		// If sub_code is null change to default of a blank string
		if( is_null($promo_sub_code) )
		{
			$promo_sub_code = "";
		}
		
		$query = "SELECT
                    license_map.*,
                    property_map.qualify,
                    property_map.legal_entity,
                    property_short
                FROM
                    license_map
                    LEFT JOIN property_map on license_map.property_id = property_map.property_id
                WHERE
                    license = '".mysql_escape_string ($license)."'";
        
        try
        {
            $result = $this->sql->Query($this->database, $query);
        }
        catch (Exception $e)
        {
            throw $e;
        }
        
        if( !$this->sql->Row_Count($result) )
        {
            throw new Exception("License key not found in database");
        }
        
        $lic_map = $this->sql->Fetch_Object_Row ($result);

        if ($lic_map->force_promo_id)
        {
            $promo_id = $lic_map->force_promo_id;
        }
        else
        {
            $promo_id = preg_replace ("/[^\d]/", "", $promo_id );
        }
        unset ($lic_map->force_promo_id);
        
        // Default the promo id
        if (! $promo_id > 1)
        {
            $promo_id = 10000;
        }
        

        $query = "select * from promo_data_map where promo_id = '".$promo_id."' limit 1";
        
        try
        {
            $result = $this->sql->Query($this->database, $query);
        }
        catch (Exception $e)
        {
            $this->Return_Error($e);
        }
                        
        if( $this->sql->Row_Count($result) )
        {
            $promo_map = $this->sql->Fetch_Object_Row ($result);
        }
        else 
        {
            $promo_map = new stdClass();
        }
                
        $promo_map->promo_id = $promo_id;
                
        $status = new stdClass ();
        $status->valid = "valid";
        
        if ($promo_map->promo_id != 10000)
        {
            foreach (array ("page_id") as $var)
            {
                if ($lic_map->$var != $promo_map->$var)
                {
                    $status->$var = $promo_map->$var;
                    $status->valid = "invalid";
                }
            }
        }
        
        if (strlen ($lic_map->run_state))
        {
            $lic_run_state = unserialize ($lic_map->run_state);
        }
        else
        {
            $lic_run_state = new stdClass ();
        }
        
        if (strlen ($lic_map->qualify))
        {
            $lic_map->qualify = unserialize ($lic_map->qualify);
        }
        else
        {
            $lic_map->qualify = new stdClass ();
        }
        
        if (strlen ($promo_map->run_state))
        {
            $promo_run_state = unserialize ($promo_map->run_state);
        }
        else
        {
            $promo_run_state = new stdClass ();
        }
        
		if(!empty($lic_run_state->exit_strategy_array))
		{
			if(is_array($lic_run_state->exit_strategy_array))
			{
				$ids = implode(',', $lic_run_state->exit_strategy_array);
			}
			else
			{
				$ids = $lic_run_state->exit_strategy_array;
			}

			if(!empty($ids))
			{
				$query = "SELECT
						es.id,
						es.strategy_type,
						es.name,
						es.type,
						es.link,
						es.image,
						es.redirect,
						es.triggers,
						c.id		AS coreg_id,
						c.name		AS coreg_name,
						c.image		AS coreg_image,
						c.copy		AS coreg_copy,
						c.active	AS coreg_active
					FROM exit_strategy es
					LEFT JOIN coreg c ON coreg_id = c.id
					WHERE es.id IN ({$ids})
						AND IF(es.strategy_type = 'coreg', c.active, 1) = 1";

				try
				{
					$exit_map = array();
					$result = $this->sql->Query($this->database, $query);

					while($row = $this->sql->Fetch_Object_Row ($result))
					{
						if(!empty($row->triggers))
						{
							$row->triggers = unserialize($row->triggers);
						}

						$exit_map[$row->id] = $row;
					}
				}
				catch (Exception $e)
				{
					$this->Return_Error($e);
				}
			}
        }
        
        
        $run_state = $this->Object_Merge ($lic_run_state, $promo_run_state);
        
        $config = new stdClass();
        
        $config->promo_id =  $promo_map->promo_id;
        $config->promo_sub_code = $promo_sub_code;
        $config->vendor_id = $promo_map->vendor_id;
        $config->promo_status = $status;
        $config->cost_action = $promo_map->cost_action;
        $config->exit_strategy = $exit_map;
        
        unset ($lic_map->run_state);
        
        $config->validation_fields = array();
        
        $return_obj = $this->Object_Merge($config, $lic_map);
        $return_obj = $this->Object_Merge($return_obj, $run_state);
        
        return $return_obj;
	}
    
    /**
        @publicsection
        @public
        @fn object Object_Merge($o1,$o2)
        @brief
            Merges two objects
            
        Method merges two objects
        
        @param $o1 object Object 1
        @param $o2 object Object 2
        @return 
            Merged Object
     */
    private function Object_Merge ($o1, $o2)
    {
        if (! is_object ($o1))
        {
            $o1 = new stdClass ();
        }
        if (! is_object ($o2))
        {
            $o2 = new stdClass ();
        }
        foreach ($o2 as $key => $val)
        {
            $o1->$key = $val;
        }
        
        return $o1;
    }
}
?>
