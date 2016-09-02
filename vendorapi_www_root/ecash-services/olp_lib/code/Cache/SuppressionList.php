<?php
require_once 'suppression_list.1.php';

/**
 * This class overrides the Suppress_List class and uses memcache to cache suppression list info.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Cache_SuppressionList extends Suppress_List
{
	/**
	 * Overloaded the Suppress_List Load() function to add memcache support for OLP. [BF]
	 *
	 * @param int $list_id the list id to load
	 * @param int $revision_id the revision id to load, leave NULL for the latest revision
	 */
	public function Load($list_id, $revision_id = NULL)
	{
		// Attempt to get it out of memcache first
		$memcache_key = 'SL:' . md5($revision_id == NULL ? $list_id : "$list_id:$revision_id");
		$rec = Cache_Memcache::getInstance()->get($memcache_key);
		
		if(!$rec)
		{
			if (is_numeric($list_id) && is_numeric($revision_id))
			{
				
				// get the specified revision
				$query = "SELECT 
						lists.list_id, 
						name, 
						lists.active, 
						field_name, 
						description,
						loan_action, 
						lists.date_created, 
						lists.date_modified, 
						revision_id, 
						list_revisions.status AS rev_status
					FROM 
						lists
					LEFT JOIN 
						list_revisions
					ON (list_revisions.list_id = lists.list_id)
					WHERE 
						lists.list_id='{$list_id}'
					AND 
						list_revisions.revision_id='{$revision_id}'";
				
			}
			elseif (is_numeric($list_id))
			{
				
				// get the highest/latest revision
				$query = "SELECT 
						lists.list_id, 
						name, 
						lists.active, 
						field_name, 
						description, 
						loan_action,
						lists.date_created, 
						lists.date_modified, 
						revision_id, 
						list_revisions.status AS rev_status
					FROM 
						lists
					LEFT JOIN 
						list_revisions 
					ON 
						(list_revisions.list_id=lists.list_id AND list_revisions.status='ACTIVE')
					WHERE
						lists.list_id='{$list_id}'
					LIMIT 1";
				
			}
			
			// run the query
			$result = $this->sql->Query($this->database, $query);
			
			if($rec = $this->sql->Fetch_Array_Row($result))
			{
				Cache_Memcache::getInstance()->set($memcache_key, $rec);
			}
		}
		
		if($rec)
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
				$key = "suppress_list_values:{$this->id}:{$this->revision_id}";
				$values = Cache_Memcache::getInstance()->get($key);

				if(!$values)
				{
					$values = $this->Fetch_List_Values($this->id, $this->revision_id);
					Cache_Memcache::getInstance()->set($key, $values);
				}
				
				$this->values = $values;
			}
		}
		else
		{
			throw new UnexpectedValueException(sprintf(
				'suppression list failed to load from db/cache: list id=%s, revision id=%s',
				$list_id,
				$revision_id)
			);
		}
	}
}
	
?>
