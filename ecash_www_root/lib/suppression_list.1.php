<?php
	
	class Suppress_List
	{
		
		// list info
		var $id = NULL;
		var $name;
		var $field;
		var $description;
		var $date_created;
		var $date_modified;
		var $revision_id;
		var $action;
		var $active;
		var $rev_status;
		
		// local state
		var $values = array();
		var $added = array();
		var $deleted = array();
		
		// just stuff...
		var $sql;
		var $database;
		
		function Suppress_List($sql, $database)
		{
			$this->sql = &$sql;
			$this->database = $database;
		}
		
		function __destruct()
		{
			unset($this->sql);
		}
		
		function ID()
		{
			return($this->id);
		}
		
		function Name($name = NULL)
		{
			
			if (!is_null($name)) $this->name = $name;
			else $name = $this->name;
			
			return($name);
			
		}
		
		function Field($field = NULL)
		{
			
			if (!is_null($field)) $this->field = $field;
			else $field = $this->field;
			
			return($field);
			
		}
		
		function Revision_ID()
		{
			return($this->revision_id);
		}
		
		function Description($description = NULL)
		{
			
			if (!is_null($description)) $this->description = $description;
			else $description = $this->description;
			
			return($description);
			
		}
		
		function Loan_Action($action = NULL)
		{
			
			if (!is_null($action)) $this->action = $action;
			else $action = $this->action;
			
			return($action);
			
		}
		
		function Active($active = NULL)
		{
			
			if (is_bool($active)) $this->active = $active;
			elseif (is_null($active)) $active = $this->active;
			else $active = NULL;
			
			return($active);
			
		}
		
		function Values()
		{
			return($this->values);
		}
		
		function Add($value)
		{
			
			if (is_array($value))
			{
				
				// store all values lowercase
				$value = array_map('strtolower', $value);
				$value = array_diff(array_map('trim', $value), array(''));
				
				// have we added these already?
				$do = array_unique(array_diff($value, $this->added));
				
				if (count($do))
				{
					
					// if we've deleted them, just mark them undeleted
					$this->deleted = array_diff($this->deleted, $do);
					
					// add any _real_ additions
					$do = array_diff($do, array_keys($this->values));
					$this->added = array_merge($this->added, $do);
					
				}
				
			}
			else
			{
				
				// store as lowercase
				$value = trim(strtolower($value));
				
				// have we added it already?
				if (($value != '') && (!isset($this->values[$value])) && (!in_array($value, $this->added)))
				{
					$this->added[] = $value;
				}
					
			}
			
		}
		
		function Remove($value)
		{
			
			if (is_array($value))
			{
				
				// lowercase
				$value = array_map('strtolower', $value);
				
				// have we deleted these already?
				$do = array_unique(array_diff($value, $this->deleted));
				
				if (count($do))
				{
					
					// if we're deleting something we added,
					// just remove it from the added list
					$this->added = array_diff($this->added, $do);
					
					// mark everything else as deleted
					$do = array_intersect($do, array_keys($this->values));
					$this->deleted = array_merge($this->deleted, $do);
					
				}
				
			}
			else
			{
				
				// lowercase
				$value = strtolower($value);
				
				// did we delete it already?
				if (isset($this->values[$value]) && (!in_array($value, $this->deleted)))
				{
					$this->deleted[] = $value;
				}
				
			}			
			
		}
		
		function Added($value = NULL)
		{
			
			if (!is_null($value))
			{
				
				if (is_array($value))
				{
					$value = array_map('strtolower', $value);
					$added = array_intersect($value, $this->added);
				}
				else
				{
					$value = strtolower($value);
					$added = in_array($value, $this->added);
				}
				
			}
			else
			{
				$added = $this->added;
			}
			
			return($added);
			
		}
		
		function Removed($value = NULL)
		{
			
			if (!is_null($value))
			{
				
				if (is_array($value))
				{
					$value = array_map('strtolower', $value);
					$deleted = array_intersect($value, $this->deleted);
				}
				else
				{
					$value = strtolower($value);
					$deleted = in_array($value, $this->deleted);
				}
				
			}
			else
			{
				$deleted = $this->deleted;
			}
			
			return($deleted);
			
		}
		
		function New_Values()
		{
			
			$values = array_keys($this->values);
			$values = array_merge($values, $this->added);
			$values = array_diff($values, $this->deleted);
			
			return($values);
			
		}
		
		function Load($list_id, $revision_id = NULL)
		{
			
			if (is_numeric($list_id) && is_numeric($revision_id))
			{
				
				// get the specified revision
				$query = "SELECT lists.list_id, name, lists.active, field_name, description,
					loan_action, lists.date_created, lists.date_modified, revision_id, revisions.status AS rev_status
					FROM lists, list_revisions WHERE list_id='{$list_id}'
					AND list_revisions.list_id=lists.list_id
					AND list_revisions.revision_id='{$revision_id}'";
				
			}
			elseif (is_numeric($list_id))
			{
				
				// get the highest/latest revision
				$query = "SELECT lists.list_id, name, lists.active, field_name, description, loan_action,
					lists.date_created, lists.date_modified, revision_id, list_revisions.status AS rev_status
					FROM lists
						LEFT JOIN list_revisions ON
							list_revisions.list_id=lists.list_id AND
							list_revisions.status='ACTIVE'
					WHERE
						lists.list_id='{$list_id}'
					LIMIT 1";
				
			}
			
			// run the query
			$result = $this->sql->Query($this->database, $query);
			
			if (($result !== FALSE) && (($rec = $this->sql->Fetch_Array_Row($result)) !== FALSE))
			{
				
				$this->id = (int)$rec['list_id'];
				$this->name = $rec['name'];
				$this->active = ($rec['active'] == 1);
				$this->rev_status = ($rec['rev_status'] == 1);
				$this->field = $rec['field_name'];
				$this->description = $rec['description'];
				$this->action = $rec['loan_action'];
				$this->date_created = strtotime($rec['date_created']);
				$this->date_modified = strtotime($rec['date_modified']);
				$this->revision_id = (int)$rec['revision_id'];
				
				if (is_numeric($this->revision_id))
				{
					// get our list values
					$this->values = $this->Fetch_List_Values($this->id, $this->revision_id);
				}
				
			}
			
			return;
			
		}
		
		function Save()
		{
			
			// start with what we have
			$this->id = $this->Save_List($this->id, $this->name, $this->field, $this->description, $this->action, $this->active);
			$values = $this->values;
			
			// did we make any changes?
			if ((!is_null($this->id)) && (count($this->added) || count($this->deleted)))
			{
				
				// local copy to work on
				$values = $this->values;
				
				if (count($this->added))
				{
					
					// get current IDs
					$added = $this->Fetch_Values($this->added);
					
					if (count($new = array_keys($added, FALSE)))
					{
						// create the new IDs
						$new = $this->Create_Values($new);
						$added = ($new + $added);
					}
					
					// one big happy family
					$values = ($values + $added);
					
				}
				
				if (count($this->deleted))
				{
					
					// remove these values
					foreach ($this->deleted as $value)
					{
						unset($values[$value]);
					}
					
				}
				
				// save our list values
				$this->revision_id = $this->Save_List_Values($this->id, $values);
				
				// reset our local state
				$this->values = $values;
				$this->added = array();
				$this->deleted = array();
				
			}
			
		}
		
		function Delete($list_id = NULL)
		{
			
			
			
		}
		
		function Save_List($id = NULL, $name, $field, $description, $action, $active)
		{
			
			if (is_null($id))
			{
				
				// create a new list
				$query = "INSERT INTO lists (name, field_name, description, loan_action, date_created, active)
					VALUES ('{$name}', '{$field}', '{$description}', '{$action}', NOW(), '".($active ? 1 : 0)."')";
				$result = $this->sql->Query($this->database, $query);
				
				if ($result !== FALSE)
				{
					$result = $this->sql->Insert_ID();
				}
				
			}
			elseif (is_numeric($id))
			{
				
				// update the list
				$query = "UPDATE lists SET name='{$name}', field_name='{$field}', description='{$description}',
					loan_action='{$action}', active='".($active ? 1 : 0)."' WHERE list_id='{$id}'";
				$this->sql->Query($this->database, $query);
				
				// that's how it's expected
				$result = $id;
				
			}
			
			return($result);
			
		}
		
		function Fetch_List_Values($list_id, $revision_id)
		{
			
			$query = "SELECT list_revision_values.value_id AS id, LOWER(value) AS value
				FROM list_revision_values, list_values
				WHERE list_revision_values.list_id='{$list_id}'
				AND	list_revision_values.revision_id='{$revision_id}'
				AND	list_values.value_id=list_revision_values.value_id";
			$result = $this->sql->Query($this->database, $query);
			
			$results = array();
			
			while ($rec = $this->sql->Fetch_Array_Row($result))
			{
				$results[$rec['value']] = $rec['id'];
			}
			
			return($results);
			
		}
		
		function Fetch_Values($values)
		{
			
			// prepare the data
			if (is_array($values))
			{
				// everything will be returned lowercase
				$values = array_unique(array_map('strtolower', $values));
				$find = array_map('mysql_escape_string', $values);
			}
			else
			{
				$values = strtolower($values);
				$find = mysql_escape_string($values);
			}
			
			// build our query
			$query = "SELECT value_id, LOWER(value) AS value FROM list_values
				WHERE value ".(is_array($find) ? "IN ('".implode("', '", $find)."')" : "='{$find}'");
			$result = $this->sql->Query($this->database, $query);
			
			$results = array();
			
			while ($rec = $this->sql->Fetch_Array_Row($result))
			{
				$results[$rec['value']] = $rec['value_id'];
				unset($values[array_search($rec['value'], $values)]);
			}
			
			// everything else is FALSE
			if (count($values))
			{
				$values = array_combine($values, array_fill(0, count($values), FALSE));
				$results = ($results + $values);
			}
			
			if (!is_array($values)) $results = reset($results);
			return($results);
			
		}
		
		function Save_List_Values($list_id, $values)
		{
			
			// create a new list revision
			$revision_id = $this->New_Revision($list_id);
			
			if ($revision_id !== FALSE)
			{
				
				// add our values to this new revision
				$query = "INSERT INTO list_revision_values (list_id, revision_id, value_id)
					VALUES ('{$list_id}', '{$revision_id}', '"
					.implode("'), ('{$list_id}', '{$revision_id}', '", $values)."')";
				$this->sql->Query($this->database, $query);
				
				// mark this revision as active
				$this->Activate_Revision($list_id, $revision_id);
				
			}
			
			return($revision_id);
			
		}
		
		function Create_Values($values)
		{
			
			// escape these values
			$insert = array_map('mysql_escape_string', $values);
			
			// insert them all
			$query = "INSERT INTO list_values (value, date_created)
				VALUES ('".implode("', NOW()), ('", $insert)."', NOW())";
			$result = $this->sql->Query($this->database, $query);
			
			// get the new IDs
			$results = $this->Fetch_Values($values);
			return($results);
			
		}
		
		function New_Revision($list_id)
		{
			
			$query = "INSERT INTO list_revisions (list_id, date_created)
				VALUES ('{$list_id}', NOW())";
			$result = $this->sql->Query($this->database, $query);
			
			// get our new revision ID
			$revision = $this->sql->Insert_ID();
			return($revision);
			
		}
		
		function Activate_Revision($list_id, $revision_id)
		{
			
			// set this revision active
			$query = "UPDATE list_revisions SET status = 'ACTIVE'
				WHERE list_id='{$list_id}' AND revision_id='{$revision_id}'";
			$result = $this->sql->Query($this->database, $query);
			
			if (($result !== FALSE) && ($this->sql->Affected_Row_Count() > 0))
			{
				
				// set all other revisions inactive
				$query = "UPDATE list_revisions SET status = 'INACTIVE'
					WHERE list_id='{$list_id}' AND status = 'ACTIVE'
					AND revision_id!='{$revision_id}'";
				$result = $this->sql->Query($this->database, $query);
				
			}
			
			$result = ($result !== FALSE);
			return($result);
			
		}
		
		function Translate_Wildcard($value)
		{
			
			// split our string into tokens
			$tokens = preg_split('/(?<!\\\)([*%?+])/', $value, NULL, PREG_SPLIT_DELIM_CAPTURE);
			
			// the regex pattern
			$pattern = '';
			
			foreach ($tokens as $index=>$token)
			{
				
				switch ($token)
				{
					
					// translate to regex
					case '+': $token = '.+'; break;
					case '*': $token = '.*'; break;
					case '%': $token = '.'; break;
					case '?': $token = '.?'; break;
					
					// add regex quoting
					default:
						$token = preg_replace('/\\\([\*%\?\+])/', '\\1', $token);
						$token = preg_quote($token);
						break;
						
				}
				
				// are we matching the beginning or end of a string?
				if ($index == 0) $token = '^'.$token;
				if ($index == (count($tokens) - 1)) $token .= '$';
				
				// add to our pattern
				$pattern .= $token;
				
			}
			
			// add regex delimiters
			$pattern = '/'.$pattern.'/';
			return($pattern);
			
		}
		
		// searches the suppression list for
		// a match to $value
		function Match($value, $values = NULL, $return = FALSE)
		{
			
			$matched = FALSE;
			
			if (!is_array($values)) $values = array_keys($this->values);
			
			if (count($values))
			{
				
				foreach ($values as $pattern)
				{
					
					// regex?
					if (preg_match('/^\/.*\/$/', $pattern))
					{
						// ignore errors here
						$matched = (@preg_match($pattern.'i', $value) !== 0);
					}
					// is this a wildcard pattern?
					elseif (preg_match('/(?<!\\\)[\*%\?\+]/', $pattern) !== 0)
					{
						// translate this into a regexp
						$preg = $this->Translate_Wildcard($pattern);
						$matched = (preg_match($preg.'i', $value) !== 0);
					}
					else
					{
						$matched = (strtolower((string)$pattern) === strtolower((string)$value));
					}
					
					// only need one match
					if ($matched)
					{
						// do they want the pattern returned?
						if ($return) $matched = $pattern;
						break;
					}
					
				}
				
			}
			
			return($matched);
			
		}
		
		function Fetch_List_Info($sql, $database, $only_active = NULL)
		{
			
			$query = (!is_null($only_active)) ? 'WHERE lists.active = '.($only_active ? 1 : 0) : '';
			
			$query = "SELECT lists.list_id AS id, lists.name, lists.field_name, lists.description,
				lists.loan_action, lists.active, COUNT(list_revision_values.value_id) AS count
				FROM lists
				LEFT JOIN list_revisions ON list_revisions.list_id=lists.list_id AND list_revisions.status = 'ACTIVE'
				LEFT JOIN list_revision_values ON list_revision_values.list_id=list_revisions.list_id
					AND list_revision_values.revision_id=list_revisions.revision_id
				{$query}
				GROUP BY lists.list_id";
			$result = $sql->Query($database, $query);
			
			$lists = array();
			
			while ($rec = $sql->Fetch_Array_Row($result))
			{
				$rec['active'] = ($rec['active'] == 1);
				$lists[$rec['id']] = $rec;
			}
			
			return($lists);
			
		}
		
		function Fetch_List_Names($sql, $database, $list_ids = NULL, $only_active = FALSE)
		{
			
			$query = "SELECT lists.list_id, lists.name FROM lists";
			if (is_array($list_ids)) $query .= " WHERE list_id IN ('".implode("', '", $list_ids)."')";
			
			$result = $sql->Query($database, $query);
			
			if (!is_a($result, 'Error_2'))
			{
				
				$lists = array();
				
				while ($rec = $sql->Fetch_Array_Row($result))
				{
					$lists[$rec['list_id']] = $rec['name'];
				}
				
			}
			else
			{
				$lists = FALSE;
			}
			
			return($lists);
			
		}
		
	}
	
	if (!function_exists('array_combine'))
	{
		
		function array_combine($keys, $values)
		{
			
			// must have the same number of entries,
			// and cannot be blank (per the PHP manual)
			if ((count($keys) == count($values)) && (!empty($keys)))
			{
				
				// make sure we're at the beginning
				reset($keys);
				reset($values);
				
				// build the new array
				$array = array();
				
				while(($key = each($keys)) && ($value = each($values)))
				{
					$array[$key['value']] = $value['value'];
				}
				
			}
			else
			{
				$array = FALSE;
			}
			
			return($array);
			
		}
		
	}
	
?>