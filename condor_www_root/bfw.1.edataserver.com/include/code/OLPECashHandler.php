<?php

class OLPECashHandler
{
	protected static $instances;
			
	protected static function getDatabase($mode, $property_short)
	{
		$db = Setup_DB::Get_PDO_Instance('mysql', $mode, $property_short);
		return $db;
	}
	public static function getECashAPI($property_short, $app_id, $mode = BFW_MODE)
	{
		$hash = md5('ecash_api'.$property_short.$app_id.$mode);
		if(!isset(self::$instances[$hash]))
		{
			$db = self::getDatabase($mode, $property_short);
			self::$instances[$hash] = eCash_API_2::Get_eCash_API($property_short, $db, $app_id);
		}
		return self::$instances[$hash];
	}
	
	public static function getLoanAmountCalculator($property_short, $mode = BFW_MODE)
	{
		$hash = md5('loancalc'.$property_short.$mode);
		if(!isset(self::$instances[$hash]))
		{
			$db = self::getDatabase($mode, $property_short);
			self::$instances[$hash] = LoanAmountCalculator::Get_Instance($db, $property_short);
		}
		return self::$instances[$hash];
	}
}
