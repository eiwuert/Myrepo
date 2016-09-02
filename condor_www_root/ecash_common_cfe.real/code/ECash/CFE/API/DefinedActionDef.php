<?php

	// Model of rule_action
	
	class ECash_CFE_API_DefinedActionDef extends ECash_Models_WritableModel
	{
		
		static public $DIRECTORY = 'Action/';
		static public $PREFIX = "ECash_CFE_Action_";
		
		public function __construct($db) {
			parent::__construct($db);
			self::$DIRECTORY = ECASH_COMMON_DIR . 'code/ECash/CFE/Action/';
		}
		

		
		/**
		 * fetches multiple rows by the conditions passed in the first parameter
		 *
		 * @param array $where_args
		 * @param array $override_dbs
		 * @return array of ECash_CFE_API_DefinedActionDef
		 */
		public function loadAllBy(array $where_args, array $override_dbs = NULL, $order_by = "name asc")
		{
			if(!isset($where_args['active_status'])) {
				$where_args['active_status'] = 'active';
			}
			$retval = null;
			$query = "SELECT * FROM cfe_action" . self::buildWhere($where_args) . " order by " . $order_by;



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
		 * returns an array of the columns in this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'cfe_action_id',
				'name'
			);
			return $columns;
		}

		/**
		 * returns an array of the primary key
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_action_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_action_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_action';
		}
		
		public function getParams() {
			//return array("test_var" => ECash_CFE_IAction::TYPE_BOOL, "test_var_2" => ECash_CFE_IAction::TYPE_STRING );
			return $this->getClassInstance()->getParameters();
		}
		
		public function getReferenceData($param_name) {
			return $this->getClassInstance()->getReferenceData($param_name);
		}
		
		public function getIsEcashOnly() {
			return $this->getClassInstance()->isEcashOnly();
		}
		
//		public function getActionClass() {
//			if(is_null($this->action_class)) {
//				$class_name = self::$PREFIX . $this->name;
//				if(!class_exists($class_name)) {
//					require(self::$DIRECTORY . $this->name . ".php");
//				}
//				if(!class_exists($class_name)) {
//					throw new Exception("Class " . $class_name . " does not exist and could not be required.");
//				}
//				
//			}
//		}
		
		public function getAllActionsFromDirectory() {
			$actions = $this->loadAllBy(array());
			$files = scandir(self::$DIRECTORY);
			$defined_actions = array();
			foreach($actions as $action) {
				$defined_actions[$action->cfe_action_id] = $action->name;
			}
			foreach($files as $file) {
				if(substr($file,0,1) == '.' || substr($file,-4) != '.php' || !is_file(self::$DIRECTORY . $file)) continue;
				$file = substr($file,0,-4);
				if( !($key = array_search($file, $defined_actions))) {
					require(self::$DIRECTORY . $file . '.php');
					$class_name = self::$PREFIX . $file;
					$class = new $class_name();
					/* @var $new_action ECash_CFE_API_DefinedActionDef */
					$new_action = new self($this->getDatabaseInstance());
					$new_action->name = $file;
					$new_action->insert();
				} else {
					unset($defined_actions[$key]);
				}
			}
			foreach($defined_actions as $action_id=>$action) {
				$new_action = new ECash_CFE_API_DefinedActionDef($this->getDatabaseInstance());
				$new_action->loadBy(array('cfe_action_id'=>$action_id));
				$new_action->delete();
			}
			return $this->loadAllBy(array());
		}
		
		/**
		 * gets the class instance of this action
		 *
		 * @param $name
		 * @return ECash_CFE_Base_BaseAction
		 */
		public function getClassInstance($name = null) {
			if(is_null($name)) {
				$name = $this->name;
			}
			$class_name = self::$PREFIX . $name;
			if(!class_exists($class_name)) 
			{
				$file_path = self::$DIRECTORY . $name . '.php';
				if(file_exists($file_path)) {
					require($file_path);
				} else {
					debug_print_backtrace();
					$this->delete();
					return null;
				}
			}
			if(!class_exists($class_name)) 
			{
				throw new Exception("Class doesn't exist.");
			}
			return new $class_name();
		}
		
		public function delete() {
			$this->active_status = 'inactive';
			$this->save();
		}
		
		public function insert() {
			$where = array("name" => $this->name, "active_status" => "inactive");
			if($this->loadBy($where)) {
				$this->active_status="active";
				$this->save();
			//	$this->cfe_action_id=$row->cfe_action_id;
			} else {
				parent::insert();
			}
		}
	}

?>
