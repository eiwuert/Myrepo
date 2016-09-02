<?php

/**
 * GForge #3077: Fraud Scan
 * GForge #3837: API for Fraud Scan
 * 
 * Use lowercase Email address as (part of ) keys in memcache.
 * For each Email:
 * 		* If multi applications have same information, store only one application in memcache.
 * 		* For each email address, we store limited # of applications. (at most 4 applications would be stored)
 * 
 * @author Demin Yin
 * @see GForge #3077 - Fraud Scan
 * @see GForge #3837 - API for Fraud Scan
 * @see GForge #6016 - Fraud Scan - Field addition, Birthdate
 *
 */

define('FRAUD_SCAN_DEP_ACCOUNT', 		1);	// 2^0
define('FRAUD_SCAN_INCOME_FREQUENCY', 	2);	// 2^1
define('FRAUD_SCAN_INCOME_MONTHLY_NET', 4);	// 2^2
define('FRAUD_SCAN_DOB', 				8);	// 2^3

class FEmail {
	
	private $email;
	private $promo_id;
	private $promo_sub_code;
	
	/**
	 * 
	 * @param string $email
     * @param string $promo_id
     * @param string $promo_sub_code 
	 */
	public function __construct($email, $promo_id, $promo_sub_code = '') {
		$this->email = strtolower(trim($email));
		$this->promo_id = trim($promo_id);
		$this->promo_sub_code = trim($promo_sub_code);
	}
	
    /**
     * Get a property of this object.
     *
     * @param string $name
     * @return mixed
     */
    public function get($name) {
        return $this->$name;
    }
	
}

/**
 * Fraud application instance.
 *
 */
class FApplication {
	
	/**
	 * Application ID.
	 *
	 * @var int
	 */
	private $application_id;
	
	/**
	 * Email address of the application. ALWAYS in lower case.
	 *
	 * @var string
	 */
	private $email;
	
	/**
	 * Deposit account type of the application.
	 *
	 * @var int
	 */
	private $dep_account;
	
	/**
	 * Income frequency of the application.
	 *
	 * @var int
	 */
	private $income_frequency;
	
	/**
	 * Monthly net income of the application.
	 *
	 * @var int
	 */
	private $income_monthly_net;

	/**
	 * Date of birth of the application.
	 *
	 * @var string a date in 'YYYYmmdd' format which can be inserted into DB as an int value.
	 */
	private $dob;
		
	/**
	 * Different types of deposit account.
	 * 
	 * @var array
	 */
	private static $dep_accounts = array(
		0 => NULL,
		1 => 'FALSE',			// 1: Paper check from your employer (you deposit at the bank)
		2 => 'DD_CHECKING',		// 2: Electronic Deposit into Checking account
		3 => 'DD_SAVINGS',		// 3: Electronic Deposit into Savings account
		4 => 'NO_ACCOUNT',		// 4: I do not have a checking or savings account
		5 => 'OTHER',			// 5: one of the above
		);
	
	/**
	 * Different types of income frequency.
	 * 
	 * @var array
	 */
	private static $income_frequencies = array(
		0 => NULL,
		1 => 'WEEKLY',			// 1: Every Week
		2 => 'BI_WEEKLY',		// 2: Every Other Week
		3 => 'TWICE_MONTHLY',	// 3: Twice Per Month
		4 => 'MONTHLY',			// 4: Once Per Month
		);
			
	/**
	 * Constructor.
	 *
	 * 2 different ways to create an FApplication object.
	 * a).
	 * new FApplication(array('application_id' => '87908104', 'email' => 'Demin.Yin@SellingSource.com', ...));
	 * b).
	 * new FApplication($application_id, $email, $dep_account, $income_frequency, $income_monthly_net, $dob);
	 * new FApplication('87908104', 'Demin.Yin@SellingSource.com', 'DD_CHECKING', 'BI_WEEKLY', 6999, '19760909');
	 */
	public function __construct() {
		switch (TRUE) {
			case (func_num_args() == 1 && is_array(func_get_arg(0))):
				$data = func_get_arg(0);

				$this->application_id = intval($data['application_id']);
				$this->email = strtolower(trim($data['email']));
				$this->dep_account = intval(array_search(strtoupper($data['dep_account']), self::$dep_accounts));
				$this->income_frequency = intval(array_search(strtoupper($data['income_frequency']), self::$income_frequencies));
				$this->income_monthly_net = intval($data['income_monthly_net']);
				$this->dob = $data['dob'];
				break;
			case (func_num_args() == 6):
				list ($application_id, $email, $dep_account, $income_frequency, $income_monthly_net, $dob) = func_get_args();
		
				$this->application_id = intval($application_id);
				$this->email = strtolower(trim($email));
				$this->dep_account = intval(array_search(strtoupper($dep_account), self::$dep_accounts));
				$this->income_frequency = intval(array_search(strtoupper($income_frequency), self::$income_frequencies));
				$this->income_monthly_net = intval($income_monthly_net);
				$this->dob = $dob;				
				break;
			default:
				// Not implemented.
				break;
		}	
	}	

	/**
	 * Return a new FApplication object from an array (which is retrieved from memcache).
	 * 
	 * $this->__toObject(
	 * 		array(
	 * 			'application_id' => '87908104', 
	 * 			'dep_account' => 2, 
	 * 			'income_frequency' => 3, 
	 * 			'income_monthly_net' => 6999,
	 * 			'dob' => '19760909',
	 * 		), 
	 * 		$email
	 * );
	 * $this->__toObject(array('87908104', 2, 3, 6999, '19760909'), $email);
	 *
	 * @param array $cached_app 
	 * @param string $email email address.
	 * @return FApplication
	 */
	public static function __toObject($cached_app, $email) {
		if (isset($cached_app['application_id'])) {
			$application_id = $cached_app['application_id'];
			$dep_account = self::$dep_accounts[$cached_app['dep_account']];
			$income_frequency = self::$income_frequencies[$cached_app['income_frequency']];
			$income_monthly_net = $cached_app['income_monthly_net'];
			$dob = $cached_app['dob'];
		} else {
			$cached_app = array_values($cached_app);
			
			$application_id = $cached_app[0];
			$dep_account = self::$dep_accounts[$cached_app[1]];
			$income_frequency = self::$income_frequencies[$cached_app[2]];
			$income_monthly_net = $cached_app[3];
			$dob = $cached_app[4];
		}
		
		return new FApplication($application_id, $email, $dep_account, $income_frequency, $income_monthly_net, $dob);
	}
	
	/**
	 * Return an array repretation of this object. The array would be stored in memcache.
	 *
	 * @param $with_email boolean
	 * @return array
	 */
	public function __toArray($with_email = FALSE) {
		if ($with_email) {
			return array(
				$this->application_id,
				$this->email,
				$this->dep_account,
				$this->income_frequency,
				$this->income_monthly_net,
				$this->dob,
				);
		} else {
			return array(
				$this->application_id,
				$this->dep_account,
				$this->income_frequency,
				$this->income_monthly_net,
				$this->dob,
				);			
		}
	}
	
	/**
	 * Get a property of this object.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function get($name) {
		return $this->$name;
	}
	
	/**
	 * BI_WEEKLY and TWICE_MONTHLY are considered as same income frequency when doing fraud scan. 
	 * 
	 * @see GForge #4208 - Soap API clarification; income_frequency of BI_WEEKLY vs. TWICE_MONTHLY
	 * @param $app FApplication
	 * @return boolean TRUE if same; otherwise false.
	 */
	public function Is_Same_Income_Frequency(FApplication &$app) {
		$same_income_frequencies = array('BI_WEEKLY', 'TWICE_MONTHLY');
		
		$if1 = self::$income_frequencies[$this->get('income_frequency')];
		$if2 = self::$income_frequencies[$app->get('income_frequency')];
		
		if (($if1 == $if2) ||
			(in_array($if1, $same_income_frequencies) && in_array($if2, $same_income_frequencies))) 
		{
			return TRUE;
		} 
		else 
		{
			return FALSE;	
		}
	}
	
	/**
	 * If the month or day of DOB changes but year is the same, do not include on fraud scan. 
	 * 
	 * @see GForge #6016 Fraud Scan - Field addition, Birthdate 
	 * @param $app FApplication
	 * @return boolean TRUE if same; otherwise false.
	 */
	public function Is_Same_DOB(FApplication &$app) {
		return (floor($this->get('dob')/10000) == floor($app->get('dob')/10000));
	}
		
}

/**
 * Fraud scanner.
 * 
 * @see GForge #3077 - Fraud Scan
 */
class Fraud_Scan {
	
	/**
	 * Fraud fields.
	 *
	 * @var array
	 */
	private static $fraud_fields = array(
		FRAUD_SCAN_DEP_ACCOUNT => 'dep_account',
		FRAUD_SCAN_INCOME_FREQUENCY => 'income_frequency',
		FRAUD_SCAN_INCOME_MONTHLY_NET => 'income_monthly_net',
		FRAUD_SCAN_DOB => 'dob',
		);
		
	/**
	 * Prefix of a key in memcache.
	 *
	 * @var string
	 */
	private static $memcache_key_prefix = 'FS:';

	/**
	 * Test if given application is a fraud application or not.
	 * 
	 * Value stored in memcache (for $email = 'Demin.Yin@SellingSource.com'):
	 * 		key: fs:demin.yin@sellingsource.com
	 * 		value: 
	 * 			array(
	 * 				2, // diff
	 * 				array(
	 * 					array(87908104, 2, 4, 6999, '19760909'),
	 * 					array(87908107, 1, 3, 2999, '19760909'),
	 * 					)
	 * 				)
	 *
	 * @param FApplication $app
	 * @param MySQL_Wrapper $sql
	 * @param string $database database name.
	 * @return boolean false if not fraud; otherwise true.
	 */
	public static function Is_Fraud_App(FApplication $app, MySQL_Wrapper &$sql, &$database) {
		if (!defined('FRAUD_SCAN_EXPIRE') || (intval(FRAUD_SCAN_EXPIRE) == 0)) { // redundant checking process. but just leave it here for safe purpose.
			define('FRAUD_SCAN_EXPIRE', 48 * 60 * 60); // 2 days 
		}

		$memcache_key = self::Get_Memcache_Key($app);
		$cached_apps = Memcache_Singleton::Get_Instance()->get($memcache_key);
		
		switch (TRUE) {
			case (!$cached_apps):
			case (!is_array($cached_apps)):
				$cached_apps = array(0, array($app->__toArray()));
    		    Memcache_Singleton::Get_Instance()->set($memcache_key, $cached_apps, intval(FRAUD_SCAN_EXPIRE));
				break;
			default:
				list($diff, $apps) = $cached_apps;
				$fraud_fields = self::Reorder_Fraud_Field($diff); // purpose: to improve code efficiency.
				
				foreach ($apps as $val) {
					$app2 = FApplication::__toObject($val, $app->get('email'));
					
					if ($field = self::Comparison($app, $app2, $fraud_fields)) {
						if (!($diff & $field)) {
							$diff += $field;
							array_push($apps, $app->__toArray());
							$cached_apps = array_values(compact('diff', 'apps'));
                            Memcache_Singleton::Get_Instance()->set($memcache_key, $cached_apps, intval(FRAUD_SCAN_EXPIRE));
						}
												
						self::Insert_Fraud_Data_to_DB($app, $app2, $field, $sql, $database);
						return TRUE;
						break;
					}
				}
				break;
		}
		
		return FALSE;
	}
    
	/**
	 * Check if given email address has failed fraud scan or not.
	 * 
	 * @param FEmail $femail
     * @param MySQL_Wrapper $sql
     * @param string $database database name.
     * @return string If not failed, return an empty string; otherwise, return ONLY one field name which contains fraud data.
     */
    public static function Is_Fraud_Email(FEmail $femail, MySQL_Wrapper &$sql, &$database) {
        $memcache_key = self::Get_Memcache_Key($femail->get('email'));        
        $cached_apps = Memcache_Singleton::Get_Instance()->get($memcache_key);

        $field = '';
        switch (TRUE) {
            case (!$cached_apps):
            case (!is_array($cached_apps)):
                break;
            default:
                list($diff, $apps) = $cached_apps;
                
                if (is_array($apps) && (count($apps) > 1)) { // try to find out which field contains fraud values
                	$app1 = FApplication::__toObject($apps[0], $femail->get('email'));
                    $app2 = FApplication::__toObject($apps[1], $femail->get('email'));
                	
                	$field = self::Comparison($app1, $app2);
                }                
                break;
        }

        self::Insert_Fraud_Query_Data_to_DB($femail, $field, $sql, $database);
        return (empty($field) ? '' : self::$fraud_fields[$field]);
    }
    
	/**
	 * Get memcache key for given application/Email.
	 *
	 * @param string|array|FApplication $val
	 * @return string
	 */
	private static function Get_Memcache_Key($val) {
		switch (TRUE) {
			case ($val instanceof FApplication):
				$email = $val->get('email');
				break;
			case (is_array($val)):
				$email = $val['email'];
				break;
			default:
				$email = $val;
				break;
		}
		
		if ($email) {
			return strtolower(trim(self::$memcache_key_prefix . $email));
		} else {
			return NULL;
		}
	}
		
	/**
	 * Compare two applications, and return back one field which contains different values.
	 *
	 * @param FApplication $app1
	 * @param FApplication $app2
	 * @param array $fraud_fields reordered array of fraud fields. array(4, 2, 1)
	 * @return int 0 means 2 applications are the same.
	 */
	private function Comparison(FApplication $app1, FApplication $app2, $fraud_fields = NULL) 
	{
		if (!$fraud_fields) 
		{
			$fraud_fields = array_keys(self::$fraud_fields);
		}
		
		foreach ($fraud_fields as $val) 
		{
			$field = self::$fraud_fields[$val];
			
			if (($val == FRAUD_SCAN_INCOME_FREQUENCY)
				&& $app1->Is_Same_Income_Frequency($app2)) // GForge #4208 [DY]
			{ 
				continue;
			}
			
			if (($val == FRAUD_SCAN_DOB) && $app1->Is_Same_DOB($app2)) // GForge #6016 [DY]
			{ 
				continue;
			}
			
			if ($app1->get($field) != $app2->get($field)) 
			{
				return $val;
			}
		}
		
		return 0;
	}
		
	/**
	 * Reord fraud fields.
	 * 
	 * Input:
	 * 		Reorder_Fraud_Field(0) // none field contains different values
	 * 		Reorder_Fraud_Field(1) // field 1 contain different values
	 * 		Reorder_Fraud_Field(5) // field 1 and field 4 contain different values
	 * 		Reorder_Fraud_Field(7) // all fields contain different values
	 * Output:
	 * 		array(1, 2, 4, 8)
	 * 		array(2, 4, 1, 8)
	 * 		array(2, 1, 4, 8)
	 * 		array(1, 2, 4, 8)
	 *
	 * @param int $diff indexes of elements that contains different values.
	 * @return array
	 */
	private static function Reorder_Fraud_Field($diff) {
		switch ($diff) {
			case 0:
			case (pow(2, count(self::$fraud_fields)) - 1): // 7 if self::$fraud_fields has 3 elements
				return array_keys(self::$fraud_fields);
				break; // useless, but just leave it here.
			default:
				$arr1 = array_keys(self::$fraud_fields);
				$arr2 = array();

				foreach ($arr1 as $key => $field) {
					if ($field & $diff) {
						$arr2[] = $field;
						unset($arr1[$key]);
					}
				}
				
				return array_merge($arr1, $arr2);
				break; // useless, but just leave it here.
		}
	}	
	
	/**
	 * Insert fraud application to table 'fraud'.
	 *
	 *      INSERT INTO `fraud` (
	 *              `application_id`, 
	 *              `fraud_field`,
	 *              `value`,
	 *              `application_id2`,
	 *              `value2`)
	 *      VALUES (
	 *              87908104,
	 *              'income_frequency',
	 *              2,
	 *              87908107,
	 *              4)
	 *
	 * @param FApplication $new_app
	 * @param FApplication $old_app
	 * @param int $field fraud field. defind in constants.
	 * @param MySQL_Wrapper $sql
	 * @param string $database database name.
	 * @return boolean
	 */
	private static function Insert_Fraud_Data_to_DB(FApplication $new_app, FApplication $old_app, $field, MySQL_Wrapper &$sql, &$database) {
		$field_name = self::$fraud_fields[$field];
		
		$value = $new_app->get(self::$fraud_fields[$field]);
		$value2 = $old_app->get(self::$fraud_fields[$field]);
		
		if ($field == FRAUD_SCAN_DOB) {
			$value  = floor($value /10000); // Get year of birthdate only.
			$value2 = floor($value2/10000); // Get year of birthdate only.
		}
		
		$query = "
			INSERT INTO `fraud` (
				`application_id`, 
				`fraud_field`,
				`value`,
				`application_id2`,
				`value2`)
			VALUES (
				{$new_app->get('application_id')},
				'$field_name',
				{$value},
				{$old_app->get('application_id')},
				{$value2})"
		;

		$sql->Query($database, $query);	
		return ($sql->Affected_Row_Count() > 0) ? TRUE : FALSE;
	}
		
    /**
     * Insert fraud application to table 'fraud_query_log'.
     *
     * @param FEmail $femail
     * @param int $field fraud field. defind in constants.
     * @param MySQL_Wrapper $sql
     * @param string $database database name.
     * @return boolean
     */
    private static function Insert_Fraud_Query_Data_to_DB(FEmail &$femail, $field, MySQL_Wrapper &$sql, &$database) { 
        $email = mysql_real_escape_string($femail->get('email'), $sql->Connect());
        $promo_id = intval($femail->get('promo_id'));
        if ($femail->get('promo_sub_code')) { 
            $promo_sub_code = '\'' . mysql_real_escape_string($femail->get('promo_sub_code'), $sql->Connect()) . '\'';
        } else {
        	$promo_sub_code = 'NULL';
        }
       	$field_name = (empty($field)) ? '' : self::$fraud_fields[$field];
        
        $query = "
            INSERT INTO `fraud_query_log` (
                `query_id`, 
                `email`,
                `promo_id`,
                `promo_sub_code`,
                `result`,
                `created_date`)
            VALUES (
                '',
                '{$email}',
                '{$promo_id}',
                {$promo_sub_code},
                '{$field_name}',
                NOW())"
        ;

        $sql->Query($database, $query); 
        
        return ($sql->Affected_Row_Count() > 0) ? TRUE : FALSE;
    }
        	
}
