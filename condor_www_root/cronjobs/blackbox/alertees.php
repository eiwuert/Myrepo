<?php
require_once('mysqli.1.php');
/*
 * Central class to store contact information about people for
 * use in the OLP_FAILOVER system.
 */
Alertee::Add('Stephan Soileau','stephan.soileau@sellingsource.com','7025238900');
Alertee::Add('Mike Genatempo','mike.genatempo@sellingsource.com',NULL);
Alertee::Add('Chris Barmonde','christopher.barmonde@sellingsource.com',NULL);
Alertee::Add('Brian Feaver','brian.feaver@sellingsource.com',NULL);
Alertee::Add('August Malson','august.malson@sellingsource.com',NULL);
Alertee::Add('Hope Pacariem','hope.pacariem@partnerweekly.com',NULL);
Alertee::Add('Jeff Fiegel','Jeff.Fiegel@SellingSource.com',NULL);
Alertee::Add('Devin Egan','devin.egan@sellingsource.com',NULL);
Alertee::Add('Mike G','mikeg@sellingsource.com',NULL);
Alertee::Add('olpcron','olpcron@sellingsource.com',NULL);
Alertee::Add('Matt Piper','matt.piper@sellingsource.com',NULL);
Alertee::Add('Chris Barmonde','christopher.barmonde@sellingsource.com',NULL);

/**
 * Stores alert information about users
 * @class Alertee
 * @brief Stores alert information about users
 * @version 1.0
 */
class Alertee
{
	private static $alertees = Array();

	/**
	 * Add a person and their email/sms info to the list of alertees
	 *
	 * @param string $name
	 * @param string $email
	 * @param string $sms
	 */
	public static function Add($name,$email,$sms)
	{
		$name = strtolower($name);
		$o = new stdClass();
		$o->email = $email;
		$o->sms = $sms;
		self::$alertees[$name] = $o;
	}
	/**
	 * return the email associated with a name
	 *
	 * @param string $name
	 * @return mixed
	 */
	public static function Get_Email($name)
	{
		$name = strtolower($name);
		if(is_object(self::$alertees[$name]))
		{
			return self::$alertees[$name]->email;
		}
		else
		{
			return NULL;
		}
	}
	/**
	 * Return the sms info associated with a name
	 *
	 * @param string $name
	 * @return string
	 */
	public static function Get_SMS($name)
	{
		if(is_object(self::$alertees[$name]))
		{
			return self::$alertees[$name]->sms;
		}
		else
		{
			return NULL;
		}
	}
}

