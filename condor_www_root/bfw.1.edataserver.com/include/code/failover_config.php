<?php
require_once(BFW_CODE_DIR . 'server.php');
require_once('mysqli.1.php');

class Failover_Config
{
	private static $data = Array();

	public static function Load_By_Name($names,$mode='RC')
	{
		$db_info = Server::Get_Server($mode,'BLACKBOX');
		if(strpos($db_info['host'],':') === FALSE)
		{
			$host = $db_info['host'];
			$port = empty($db_info['port']) ? 3306 : $db_info['port'];
		}
		else
		{
			list($host,$port) = explode(':',$db_info['host']);
		}

		$failover_db = new MySQLi_1($host,$db_info['user'],
			$db_info['password'],$db_info['db'],$port);
		$ret = Array();
		if(!is_array($names)) $names = Array($names);
		//first step is to find the ones we already know about
		foreach($names as $key=>$val)
		{
			if(isset(self::$data[$val]) && is_object(self::$data[$val]))
			{
				$ret[$val] = self::$data[$val];
				unset($names[$key]);
			}
			else
			{
				$names[$key] = "'".$failover_db->Escape_String($val)."'";
			}
		}
		$query = 'SELECT * from failover_data
			WHERE name IN ('.join(',',$names).')';
		$res = $failover_db->Query($query);
		while($row = $res->Fetch_Object_Row())
		{
			self::$data[$row->name] = $row;
			$ret[$row->name] = $row;
		}
		return $ret;
	}
	public static function RunConfig()
	{
		//array of 'boolean' style failover data to grab
		//format: data_key => Array(define,default_value)
		//where the 'define' is what to define the value as
		//for use through BFW and data_key is the 'name' in
		//the table
		$bool_data = Array(
			'EPM_COLLECT'      => Array('USE_EPM_COLLECT',TRUE),
			'USE_TRENDEX'      => Array('USE_TRENDEX',    FALSE),
			'USE_MONEYHELPER'  => Array('USE_MONEYHELPER',TRUE),
			'USE_DUAL_WRITE'   => Array('USE_DUAL_WRITE', FALSE),
			'DATAX_ABA'        => Array('USE_DATAX_ABA',  TRUE),
			'DATAX_IDV'        => Array('USE_DATAX_IDV',  TRUE),
			'MAINTENANCE_MODE' => Array('MAINTENANCE_MODE',FALSE),
			'DATAX_DOWN'       => Array('DATAX_DOWN',FALSE),
			'USE_GROOPZ'       => Array('USE_GROOPZ',TRUE),
			'DUPLICATE_LEAD_EXPIRE'	=> Array('DUPLICATE_LEAD_EXPIRE',TRUE),
			'OLP_DUAL_WRITE'   => Array('OLP_DUAL_WRITE',FALSE),
			'FRAUD_SCAN_EXPIRE'	=> Array('FRAUD_SCAN_EXPIRE', 48 * 60 * 60), // default: 2 days. GForge #3077 [DY]
			'STAT_SYSTEM_2'    => Array('STAT_SYSTEM_2', TRUE),
			'USE_STAT_LIBOLUTION' => Array('USE_STAT_LIBOLUTION', TRUE),
			'SECOND_LOAN_CAP_CLK' => Array('SECOND_LOAN_CAP_CLK', 0),
			'SECOND_LOAN_CAP_IMP' => Array('SECOND_LOAN_CAP_IMP', 0),
		);
		try
		{
			$names = array_keys($bool_data);
			$c_data = self::Load_By_Name($names,BFW_MODE);
			foreach($bool_data as $data_name=>$val)
			{
				if(isset($c_data[$data_name]))
				{
					//It's in the database, and set to true,
					//so define as true
					switch($val[0]) {
						case 'DUPLICATE_LEAD_EXPIRE':
						case 'FRAUD_SCAN_EXPIRE':
						case 'SECOND_LOAN_CAP_CLK':
						case 'SECOND_LOAN_CAP_IMP':
							define($val[0], $c_data[$data_name]->value);
							break;
						default:
							if(self::is_true($c_data[$data_name]->value)) {
								define($val[0], TRUE);
							} else {
								define($val[0], FALSE);
							}
							break;	
					}					
				}
				else
				{
					//it's not in the database so
					//default using the array thing
					
					define($val[0],$val[1]);
				}
			}
		}
		catch (Exception $e)
		{
			//if the database is down,
			//we're in maintenance mode.
			define('MAINTENANCE_MODE',true);
			return TRUE;
		}
	
		
	}
	public static function is_true($str)
	{
		$str = strtolower($str);
		if($str == 1 || $str == 'true' || $str === TRUE ||
			$str == 'enabled' || $str=='on')
			{
				return TRUE;
			}
		return FALSE;
	}
}
