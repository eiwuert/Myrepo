<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApplicationStatusList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_ApplicationStatusList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_ApplicationStatus($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}

		
		public function getOrderedBy(array $where_args, array $override_dbs = NULL, $orderedBy = 'null')
		{
			$query = "SELECT * FROM application_status " . self::buildWhere($where_args) . " order by $orderedBy";

			$item = new ECash_Models_ApplicationStatusList($this->getDatabaseInstance());
			
			$item->statement = $item->getDatabaseInstance()->queryPrepared($query, $where_args);
			
			return $item;
		}
		
		public function getTree()
		{
			$start = $this->getOrderedBy(array('name_short' => '*root', 'active_status' => 'active'), null, 'name_short ASC');
			$retval = $this->getBranches($start);
			return $retval['*root']['branches'];
		}
		
		protected function getBranches($branches)
		{
			if(count($branches) > 0)
			{
				$retval = array();
				foreach($branches as $branch)
				{
					$retval[$branch->name_short] = array('name' => $branch->name, 'depth' => $branch->level);
					$retval[$branch->name_short]['branches'] = $this->getBranches($this->getOrderedBy(array('application_status_parent_id' => $branch->application_status_id, 'active_status' => 'active'), null, 'name_short ASC'));
				}
				return $retval;
			} 
			else 
			{
				return array();
			}
		}
	}
?>
