<?php

require_once("FraudPrototype.php");
require_once("FraudRule.php");
require_once(ECASH_COMMON_DIR. 'ECashApplication.php');


class FraudCheck
{	
	private $db;
	private $prototypes = array();
	private $rule_type;
	private $rule_type_sql;
	private $confirmed_sql;
	private $ignore_rule_sql;
		
	// =================================================
	// constructor - it.. constructs.
	public function __construct($db, $rule_type = NULL, $confirmed = NULL, FraudRule $changed_rule = NULL)
	// =================================================
	{
		$this->db = $db;
		$this->rule_type = $rule_type;

		$this->rule_type_sql = '';
		$this->confirmed_sql = '';
		$this->ignore_rule_sql = '';

		if($rule_type !== NULL)
			$this->rule_type_sql = "and r.rule_type = '{$rule_type}'";

		if($confirmed !== NULL)
			$this->confirmed_sql = 'and r.confirmed = ' . ($confirmed ? '1' : '0');

		if(!empty($changed_rule) &&
		   $changed_rule->FraudRuleID && //so we don't try to add this line if FraudRuleID is not set (new rule)
			(!$changed_rule->IsActive || //exclude a rule -- if we want to see the effects of turning it off
		   	 ($confirmed !== NULL && !$change_rule->IsConfirmed))) //or exclude a rule being unconfirmed
			$this->ignore_rule_sql = "and r.fraud_rule_id <> {$changed_rule->FraudRuleID}";
	}
		
	// =================================================
	// Main Function: Pass data to be tested for badstuffs
	public function processApplication(ECashApplication $app_object)
	// =================================================
	{
		// load rules into memory for SPEED
		$this->loadPrototypes();

		return $this->checkRules($app_object);
	}

	private function loadPrototypes()
	{
		
		if(empty($this->prototypes))
		{
			$query = "
				select
				c.field_name,
				c.field_comparison,
				c.prototype_id
				from fraud_condition c
				inner join fraud_rule r on (c.fraud_rule_id = r.fraud_rule_id)
				where r.active = 1
				{$this->confirmed_sql}
				{$this->rule_sql}
				{$this->ignore_rule_sql}
				group by c.field_name, c.field_comparison, c.prototype_id
				order by prototype_id
			";
			$result = $this->db->query($query);
				
			while ($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->prototypes[] = $row;
			}
			
			//print_r($this->prototypes);
		}
	}
	
	// =================================================
	// PRIVATE METHOD: buffers up the rules
	private function checkRules(ECashApplication $app)
	// =================================================
	{
			
		// our return object
		$protos = array();

		/**
		 * PROTOTYPE_ID NOTE:
		 *
		 * **Idea by John Hargrove** Implemented here by Justin Foell
		 * Prototypes are unique field_name combinations of
		 * conditions.  For instance 'first_name' and 'last_name' are
		 * one prototype, while 'first_name' by itself is also a
		 * different prototype.  The prototype also includes the
		 * comparison (equals, contains, etc.).
		 * 
		 * The prototype IDs here are tucked into the condition table.
		 * They are maintained by code by selecting for an existing
		 * matching prototype (based on field_name and count), and if
		 * not inserted by max(prototype_id)+1.  This is simpler in DB
		 * schema design, however it is up to the application to
		 * maintain a unique prototype_id <-> field_name relationship.
		 *
		 */

		$prototype_id = 0; //save the prototype_id to know when to create a new prototype
		$proto = NULL;
		// whether or not the current prototype can match this application
		$good = TRUE;
		$count = 0; //for saving the last prototype
		/**
		 *
		 * At first this loop may seem counter-intuitive.  Follow the
		 * whole thing carefully for two iterations (or even better
		 * through a ruleset_id change and then it should become more
		 * apparent -- it's built for speed to iterate all the condition
		 * rows ONCE.
		 * 
		 */		
		foreach($this->prototypes as $row)
		{
			$count++;
			if($row->prototype_id > $prototype_id)
			{
				//if good's still true from that last iteration, save it
				if($good == TRUE && $proto != NULL)
				{
					// After searching a rule id, do we still have a
					// TRUE?  if so, this means the all the fields in this
					// rule are also found in our application
					$protos[$proto->getID()] = $proto;
				}
				
				// prototype object for organizational purposes
				$proto = new FraudPrototype($row->prototype_id);
				//increment ID
				$prototype_id = $row->prototype_id;
				//reset to TRUE
				$good = TRUE;
			}

			// take each element, check to see if we do in fact have this data
			// if we find one element we dont have, mark this prototype as bad
			// otherwise continue searching the list
			if(empty($app->{$row->field_name}))
			{
				$good = FALSE;
			}
			else if($good == TRUE)
			{
				//echo "adding {$row->field_name} {$row->field_comparison} to " . $proto->getID(). "\n";
				$proto->addField($row->field_name, $row->field_comparison);
			}

			//for the last element
			if(count($this->prototypes) == $count && $good == TRUE)
			{
				//echo "adding last element";
				$protos[$proto->getID()] = $proto;				
			}
		}
						
		$queries = array();
		foreach($protos as $prototype_id => $prototype)
		{
			
			// query against each prototype that can be applied to the passed data
			$join_list = array();
				
			// selecting the rule, in the end..				
			$query = "
				SELECT
				r.*,
				c0.prototype_id
				FROM fraud_rule r
				";
				
			// exclude unpublished rules...
			$where_list = array("\nwhere r.active=1
				{$this->rule_type_sql}
				{$this->confirmed_sql}
				{$this->ignore_rule_sql}");
				
			// condition counter for generating alias names
			$cnt = 0;
			
			// add a set of conditions to the where clause for each possible rule condition
			
			foreach($prototype->fields as $field_name => $comparison)
			{
				$join_list[] = "inner join fraud_condition c{$cnt} ON (c{$cnt}.fraud_rule_id = r.fraud_rule_id)";
				$where_list[] = "and c{$cnt}.prototype_id='$prototype_id'";				
				$where_list[] = "and " . $prototype->formatSearch($field_name, $this->db->quote($app->$field_name), "c{$cnt}.field_value");
				$cnt++;
			}
				
			// put the pieces together
			$query = $query . join("\n", $join_list) . join("\n", $where_list);

			// add this query to our list of queries to run
			$queries[] = $query;
		}

		// our return object.. contains rule objects representing 'hits'			
		$violations = array(FraudRule::RULE_TYPE_FRAUD => array(), FraudRule::RULE_TYPE_RISK => array());
			
		// run all our queries, and place any results we get into our result object
		// justin likes unions
		$big_query = join("\nUNION\n", $queries);

		//if(strpos($app->name_first,'tss') !== FALSE)
		//	echo "<!-- {$big_query} -->";
		
		//incase we found no rules (likely of a certain type)
		if(!empty($big_query))
		{
			$rs=$this->db->query($big_query);
				
			while ( $row = $rs->fetch(PDO::FETCH_OBJ) )
			{
				$rule = new FraudRule($row->fraud_rule_id,
									  $row->date_modified,
									  $row->date_created,
									  $row->active,
									  $row->exp_date,
									  $row->rule_type,
									  $row->confirmed,
									  $row->name,
									  $row->comments);
				$rule->addCondition($protos[$row->prototype_id]);
				$violations[$rule->getRuleType()][] = $rule;
			}
		}

		// violate the user (sicko)
		if($this->rule_type != NULL)
			return($violations[$this->rule_type]);
		
		return($violations);
	}	
}
	
	
	
	
// Test Crap!!!!!!!!!11111111111111one //
/*
$sql = new MySQLi_1('monster.tss', 'ecash', 'lacosanostra', 'ldb_20070120', 3309);
//$sql = new MySQLi_1('ds08.tss', 'root', '', 'fraudcheck');

$fc = new FraudCheck($sql);
			
$app = new ECashApplication();
			
$app->name_first = "catrinatsstest";
$app->name_middle = "poo";
$app->name_last = "hudsontsstest";
$app->phone_home = "7024333863";
$app->phone_cell = "7605592114";
$app->street = "404 groft way";
$app->city = "henderson";
$app->state = "CA";
$app->zip_code = "89015";
$app->ssn = "548932174";
			
			
$result = $fc->processApplication($app);
print_r($result);
*/

?>
