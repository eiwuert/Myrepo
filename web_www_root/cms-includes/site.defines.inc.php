<?php

    /**
     * Copyright 2009 - 2011 Color Shift, Inc.
     * 
     * @package Luminance v4.0
     *
     * This include is site specific and is loaded in index.php if it exists
     * 
     **/
    
    define('MULTI_LINGUAL', true);
    define('SITE_EMAIL', 'test@test.com');
	define('SITE_IDENTITY', 'The Test Site');
	
    // just a simple class to act as an enum and return the properties as an array
    // so they can be iterated
    class Enum {
	static function getDescription($class = __CLASS__, $value)
	{
	    if (class_exists($class))
	    {
		$obj = new $class();
		if ($obj)
		{
		    $r = new $obj();
		    return $r->descriptions[$value];
		}
	    }	
	}
	
	static function getConstants($class = __CLASS__)
	{
	    if (class_exists($class))
	    {
		$obj = new $class();
		if ($obj)
		{
		    $r = new ReflectionObject($obj);
		    $arr = $r->getConstants();
		    $rows = array();
		    if ($arr)
		    {
			foreach ($arr as $constant=>$value)
			{
			    $rows[] = array('constant'=>$constant, 'value'=>$value, 'name'=>$obj->descriptions[$value]);
			}
		    }
		    return $rows;
		}
	    }		
	    return array();
	}
    }
    
    $installed = lum_getInstalledPlugins();
    
    if (is_array($installed))
    {
	foreach ($installed as $plugin)
	{
	    $inc = PLUGINS_PATH.$plugin.'/site.defines.inc.php';
	    if (is_file($inc))
		require_once($inc);
	}
    }
    
    class CreditCardValidator
    {
    
        function _checkSum($ccnum)
        {
            $checksum = 0;
            for ($i=(2-(strlen($ccnum) % 2)); $i<=strlen($ccnum); $i+=2)
            {
                $checksum += (int)($ccnum{$i-1});
            }
            // Analyze odd digits in even length strings or even digits in odd length strings.
            for ($i=(strlen($ccnum)% 2) + 1; $i<strlen($ccnum); $i+=2) 
            {
              $digit = (int)($ccnum{$i-1}) * 2;
              if ($digit < 10) 
                  { $checksum += $digit; } 
              else 
                  { $checksum += ($digit-9); }
            }
            if (($checksum % 10) == 0) 
                return true; 
            else 
                return false;

        }

        function isVAlidCreditCard($ccnum,$type="",$returnobj=false)
        {
            $creditcard=array(  "visa"=>"/^4\d{3}-?\d{4}-?\d{4}-?\d{4}$/",
                                "mastercard"=>"/^5[1-5]\d{2}-?\d{4}-?\d{4}-?\d{4}$/",
                                "discover"=>"/^6011-?\d{4}-?\d{4}-?\d{4}$/",
                                "amex"=>"/^3[4,7]\d{13}$/",
                                "diners"=>"/^3[0,6,8]\d{12}$/",
                                "bankcard"=>"/^5610-?\d{4}-?\d{4}-?\d{4}$/",
                                "jcb"=>"/^[3088|3096|3112|3158|3337|3528]\d{12}$/",
                                "enroute"=>"/^[2014|2149]\d{11}$/",
                                "switch"=>"/^[4903|4911|4936|5641|6333|6759|6334|6767]\d{12}$/");
            if(empty($type))
            {
                $match=false;
                foreach($creditcard as $type=>$pattern)
                    if(preg_match($pattern,$ccnum)==1)
                    {
                        $match=true;
                        break;
                    }

                if(!$match)
                    return false;
                else
                {
                    if($returnobj)
                    {
                        $return=new stdclass;
                        $return->valid=$this->_checkSum($ccnum);
                        $return->ccnum=$ccnum;
                        $return->type=$type;
                        return $return;
                    }
                    else
                        return $this->_checkSum($ccnum);
                }
            
            }
            else
            {
                if(@preg_match($creditcard[strtolower(trim($type))],$ccnum)==0)
                    return false;
                else
                {
                    if($returnobj)
                    {
                        $return=new stdclass;
                        $return->valid=$this->_checkSum($ccnum);
                        $return->ccnum=$ccnum;
                        $return->type=$type;
                        return $return;
                    }
                    else
                        return $this->_checkSum($ccnum);
                }
            }
        }
    }     

?>