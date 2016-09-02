<?php

	class ECash_Models_CfeEvent extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

		public function loadAllBy(array $where_args = array(), array $override_dbs = NULL, $order_by = 'name asc')
		{
			$retval = null;
			$query = "SELECT * FROM cfe_event" . self::buildWhere($where_args) . ' order by ' . $order_by;

			if (($rs = $this->getDatabaseInstance(self::DB_INST_READ)->queryPrepared($query, $where_args)) !== FALSE)
			{
				$results = $rs->fetchAll();
				$retval = array();
				foreach($results as $result) {
					$temp = new self($this->getDatabaseInstance());
					$temp->fromDbRow($result);
					$retval[] = $temp;
				}
			}
			return $retval;
		}

		/**
		 * getColumns - returns an array of the columns on this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_event_id', 'name','short_name'
			);
			return $columns;
		}

		/**
		 * returns the primary key in an array
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_event_id');
		}

		/**
		 * gets the auto_increment field from the database
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'cfe_event_id';
		}

		/**
		 * gets the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_event';
		}
		
	}

?>