<?php
/**
 * vendor_post_url handler, aka dynamic_vendor_post
 *
 * Checks directly against the DB for posting URL's per vendor (currently added through 
 * the very ill-named webadmin2)
 * 
 * @author TF
 */
class Dynamic_Post_Singleton
{
	
	static private $instance;
		
	
	/**
	 * Database connection.
	 *
	 * @var MySQL_4
	 */
	protected $sql;
	
	/**
	 *	main construct
	 *
	 *	@param object $sql the sql object 
	 *	@return none
	 */
	public function __construct($sql)
	{
		$this->sql = Setup_DB::Get_Instance('blackbox', BFW_MODE);
		
	}
	
	/**
	 * Overrides the clone object. Private so that no one can clone this object.
	 *
	 */
	private function __clone() {}
	
	
	/**
	 * Returns an instance of the Dynamic_Post_Singleton class.
	 *
	 * @return object
	 */
	
	static public function Get_Instance($sql)
	{
		if ( !isset(self::$instance) )
		{
			self::$instance = new Dynamic_Post_Singleton($sql);
		}	
		return self::$instance;
	}
	
	/**
	 * Retrieve the posting URL from the database.
	 *
	 * @param string $target the property short of the current target
	 * @return array the reference data array
	 */
	public function getDynamicPostUrl($target)
	{
		$tmp="";
		$query = "
			SELECT
				post_url
			FROM
				rules as r
			JOIN
				target as t
					ON (t.target_id = r.target_id)
			WHERE
				t.property_short = '" . strtoupper($target) . "'
				AND r.status = 'ACTIVE'";
		try {
		$result = $this->sql->Query(NULL,$query);
		$tmp = $this->sql->Fetch_Column($result,'post_url');
		}
		catch (Exception $e){
			$tmp="";
		}
		return $tmp;
	}
		
	
}
?>