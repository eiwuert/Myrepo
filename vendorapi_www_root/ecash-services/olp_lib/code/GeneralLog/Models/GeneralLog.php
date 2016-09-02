<?php 
	/**
	 * GeneralLog_Models_GeneralLog extends GeneralLog_Models_GeneralLogBase
	 * to allow for rolling tables
	 *
	 * @author Adam Englander <adam.englander@sellingsource.com>
	 */
	class GeneralLog_Models_GeneralLog extends GeneralLog_Models_GeneralLogBase 
	{
		/**
		 * 4-digit year
		 *
		 * @var integer
		 */
		protected $year;
		
		/**
		 * 2-digit month 
		 *
		 * @var integer
		 */
		protected $month;
		
		/**
		 * Construct
		 *
		 * @param DB_IConnection_1 $db
		 * @param integer $month 2-digit month 
		 * @param integer $year 4-digit year
		 */
		public function __construct(DB_IConnection_1 $db = NULL, $month = NULL, $year = NULL)
		{
			parent::__construct($db);
			
			$this->year = (is_null($year))?date('Y'):$year;
			$this->month = (is_null($month))?date('m'):$month;
			
		}
		
		/**
		 * Get table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'general_log_'.$this->year.'_'.$this->month;
		}
	}
?>