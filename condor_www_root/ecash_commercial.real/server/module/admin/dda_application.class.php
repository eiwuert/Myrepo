<?php
require_once(SQL_LIB_DIR . "scheduling.func.php"); //mantis:4454
require_once(SQL_LIB_DIR.'fetch_status_map.func.php'); //mantis:4454

class dda_application extends dda
{
    public function get_resource_name()
    {
        $return = "Edit Applications";

        if(isset($_SESSION['dda_application']) && isset($_SESSION['dda_application']['id']))
        {
            $return .= ": #".$_SESSION['dda_application']['id'];
        }

        return($return);
    }

    private function html_search_page()
    {
        $return =   "<form>";
		$return .=		"<input type='hidden' name='dda_resource' value='application'>";

		if(in_array($this->server->company,array("ufc","ca","pcl","d1","ucl")))
		{
	        $return .=      $this->build_html_form_select(
                            "field", array(
                                "1" => "Find Application Id",
                                "2" => "Find Cashline Id",
                                ),
                            (isset($_SESSION['dda_application']['field'])) ? $_SESSION['dda_application']['field'] : NULL
                            );
		}
		else
		{
        	$return .=      $this->build_html_form_select(
                            "field", array(
                             	"1" => "Find Application Id",
				),
			    (isset($_SESSION['dda_application']['field'])) ? $_SESSION['dda_application']['field'] : NULL
			    );

		}
        $return .=      $this->build_html_form_input("value", (isset($_SESSION['dda_application']['value'])) ? $_SESSION['dda_application']['value'] : null);
        $return .=      "<input type='submit' value='Search'>";
        $return .=  "</form>";

        return($return);
    }

    private function html_save_changes()
    {
        if  (   !isset($this->request->save)
            ||  !$this->request->save
            ||  !isset($_SESSION['dda_application']['id'])
            )
        {
            return("");
        }

        $history = array();
        $history['action'] = 'edit';
        $history['request'] = $this->request;
        $history['application_id'] = $_SESSION['dda_application']['id'];
        $history['agent_id'] = $this->server->agent_id;

        $db = ECash_Config::getMasterDbConnection();
        $db->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->beginTransaction();

        try
        {
            $query = "
                SELECT  `application_status_id`
                    ,   `is_react`
                FROM    `application`
                WHERE   `application_id` = ".$db->quote($_SESSION['dda_application']['id'])."
                ";
            $history['before_query'] = $query;
            $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
            $history['before_data'] = $before;

            $query = "
                UPDATE  `application`
                    SET `application_status_id` = ".$db->quote($this->request->application_status_id)."
                    ,   `is_react`              = ".$db->quote($this->request->is_react)."
                WHERE   `application_id` = ".$db->quote($_SESSION['dda_application']['id'])."
                ";
            $history['update_query'] = $query;
            $result = $db->exec($query);
            $history['update_result'] = $result;

            $query = "
                UPDATE  `application`
                    SET `application_status_id` = ".$db->quote($before['application_status_id'])."
                    ,   `is_react`              = ".$db->quote($before['is_react'])."
                WHERE   `application_id` = ".$db->quote($_SESSION['dda_application']['id'])."
                ";
            $history['undo_query'] = $query;

            $this->save_history($history);

            $db->commit();
        }
        catch(Exception $e)
        {
            try
            {
                $db->rollBack();
            }
            catch(Exception $e2)
            {
            }

            return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Please tell an administrator:<br><span style='text-align: left;'><pre>".$e->getMessage()."</pre></span></div>");
        }

	//mantis:4454
	if($this->request->recreate_schedule == 'yes')
		Restore_Suspended_Events($history['application_id']);

        $return = "";
        if(!isset($this->request->undo) || !$this->request->undo)
        {
            $return .=  "<form>";
			$return .=		"<input type='hidden' name='dda_resource' value='application'>";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved<br>";
            $return .=          "<input type='hidden' name='save' value='1'>";
            $return .=          "<input type='hidden' name='undo' value='1'>";
            $return .=          "<input type='hidden' name='application_status_id' value='".htmlentities($before['application_status_id'])."'>";
            $return .=          "<input type='hidden' name='is_react' value='".htmlentities($before['is_react'])."'>";
            $return .=          "<input type='submit' value='Undo'>";
            $return .=      "</div>";
            $return .=  "</form>";
        }
        else
        {
		//mantis:4454
		$status_map = Fetch_Status_Map();
		$bankruptcy_array = array(
					Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map), 
					Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map)
					);

		if(in_array($history['request']->application_status_id, $bankruptcy_array))
			//Remove_Unregistered_Events_From_Schedule($history['application_id']);
			Remove_And_Suspend_Events_From_Schedule($history['application_id']);
		//end mantis:4454

            $return .=  "<div style='text-align: center; background-color: #FFFF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes reversed";
            $return .=  "</div>";
        }

        return($return);
    }

    private function get_application_status_tree($parent=NULL, $prefix = '')
    {
        $return = array();
		
        $db = ECash_Config::getMasterDbConnection();
        
        if(NULL === $parent)
        {
            $compare = "IS NULL";
        }
        else
        {
            $compare = "= $parent";
        }

        $query = "
            -- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
            SELECT  ast.application_status_id
                ,   ast.name
                ,   ast.name_short
                ,   asp.application_status_id IS NULL as leaf
            FROM    `application_status` AS ast
			LEFT OUTER JOIN application_status AS asp ON (ast.application_status_id = asp.application_status_parent_id)
            WHERE   ast.active_status = 'active'
            AND     ast.application_status_parent_id $compare
            ";
        try
        {
            $result = $db->Query($query);
            while ($row = $result->fetch(PDO::FETCH_ASSOC))
            {
				if ($row['leaf']) {
					$return[$row['application_status_id']] = ''
						. $prefix
						. '::'
						. $row['name']
						. ' ('
						. $row['name_short']
						. ')'
						;
				}
                $sub_results = $this->get_application_status_tree($row['application_status_id'],$prefix . '::' . $row['name']);
                if(!is_array($sub_results))
                {
                    return($sub_results);
                }
                foreach($sub_results as $key => $value)
                {
                    $return[$key] = $value;
                }
            }
        }
        catch(Exception $e)
        {
            return($e->getMessage());
        }

        return($return);
    }

    private function html_search_results()
    {
        if  (   !isset($_SESSION['dda_application']['field'])
            ||  !isset($_SESSION['dda_application']['value'])
            )
        {
            return("");
        }

        $db = ECash_Config::getMasterDbConnection();

        switch($_SESSION['dda_application']['field'])
        {
            case    '1' :   $field = 'application_id';      break;
            case    '2' :   $field = 'archive_cashline_id'; break;
            default     :   return("");
        }

        $value = $db->quote($_SESSION['dda_application']['value']);

        $query = "
            SELECT  `application_id`
                ,   `application_status_id`
                ,   `is_react`
            FROM    `application`
            WHERE   `$field` = $value
            ";
        try
        {
            $result = $db->query($query);
        }
        catch(Exception $e)
        {
            return("");
        }

        if(1 != $result->rowCount())
        {
            $return =   "<div style='background-color: #FFFF00; padding: 5px; text-align: center;'>No records found</span>";
        }
        else
        {
            $result = $result->fetch(PDO::FETCH_ASSOC);
            $_SESSION['dda_application']['id'] = $result['application_id'];

            $return  =  "<form>";
			$return .=		"<input type='hidden' name='dda_resource' value='application'>";
            $return .=      "<input type='hidden' name='save' value='1'>";
            $return .=      "<fieldset style='border: 1px solid #000000;'>";
            $return .=          "<dt>";
            $return .=              "Status";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('application_status_id',
                                        $this->get_application_status_tree(1),
                                        $result['application_status_id']
                                        );
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Is React";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('is_react',
                                        array(
                                            'no'    => 'No' ,
                                            'yes'   => 'Yes',
                                            ),
                                        $result['is_react']
                                        );
            $return .=          "</dd>";
		//mantis:4454
		$status_map = Fetch_Status_Map();
		$bankruptcy_array = array(
					Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map), 
					Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map)
					);

		if(in_array($result['application_status_id'], $bankruptcy_array))
		{	
			$return .=          "<dt>";
            		$return .=              "Recreate Schedule";
            		$return .=          "</dt>";

			$return .=          "<dd>";
            		$return .=              $this->build_html_form_select('recreate_schedule',
                                        					array(
                                            					'no'    => 'No' ,
                                            					'yes'   => 'Yes',
                                            				     	     )
                                        			      	      );
            		$return .=          "</dd>";
		}
		//end mantis:4454
            $return .=          "<dt>";
            $return .=              "<input type='submit' value='Save Changes'>";
            $return .=          "</dt>";
            $return .=      "</fieldset>";
            $return .=  "</form>";
        }

        return($return);
    }

    private function search()
    {
        if  (   isset($this->request->field)
            &&  isset($this->request->value)
            )
        {
            $_SESSION['dda_application']['field'] = $this->request->field;
            $_SESSION['dda_application']['value'] = $this->request->value;
        }

        $return  = "";
        $return .= $this->html_search_page();
        $return .= $this->html_save_changes();
        $return .= $this->html_search_results();

        return($return);
    }

    public function main()
    {
        $result = $this->search();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
