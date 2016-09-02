<?php
/**
 * UTF-8 Convert
 * 
 * This class handles encoding and decoding UTF-8 data.
 * <b>Requires the XML extension</b>
 * <b>Note:</b>
 *  This class only works well if you first encode data with this class
 *  then decode that encoded data. This file cannot read UTF-8 data from a
 *  file for example and transform it into ISO format. The reason is that this 
 *  class will attempt to escape data so it can be transmitted via SOAP
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Apr 20, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
class UTF8_Convert
{
	/**
     * Decode
     * 
     * Decodes an array, object, or string to its ISO-8859-1 Format.
     * @param mixed Data
     * @param boolean Unescape data?
     * @return mixed
	 */
    public static function Decode($clean, $unescape = false)
    {
    	if(!is_string($clean) && !is_array($clean) && !is_object($clean)) return $clean;
        
        if(is_object($clean))
        {
            return self::Decode_Object($clean,$unescape);
        }
        elseif(is_array($clean))
        {
            return self::Decode_Array($clean,$unescape);
        }
        else
        {
            return self::Decode_String($clean,$unescape);
        }
    }
    
    /**
     * Decode String
     * 
     * Decodes a string which has been encoded
     * @param string Encoded string
     * @param boolean Unescape string
     * @return string Decoded ISO-8859-1 string
	 */
	public static function Decode_String($clean, $unescape = false) {
		if(!is_string($clean)) return false;
        
        if($unescape)
        {
        	//Put back amperands
            $u = str_replace("&amp;","&",$clean);
            //Now work on the special characters
            $u = preg_replace('/&#x(..);&#x(..);&#x(..);/','%\1%\2%\3',$u);
            $u = preg_replace('/&#x(..);&#x(..);/','%\1%\2',$u);
            $u = preg_replace('/&#x(..);/','%\1',$u);
            $clean = urldecode($u);
        }
        
        return utf8_decode($clean);
	}
    
    /**
     * Decode Array
     * 
     * Decodes an array which has been encoded
     * @param array Encoded Array
     * @param boolean Unescape Data
     * @return array Decoded ISO-8859-1 Array
     */
    public static function Decode_Array($clean, $unescape = false) {
    	if(!is_array($clean)) return false;
        
        foreach($clean as $clean_key => $clean_value)
        {
            if(is_object($clean_value))
            {
            	$clean[$clean_key] = self::Decode_Object($clean_value,$unescape);
            }
            elseif(is_array($clean_value))
            {
                $clean[$clean_key] = self::Decode_Array($clean_value,$unescape);
            }
            elseif(is_string($clean_value))
            {
                $clean[$clean_key] = self::Decode_String($clean_value,$unescape);
            }
        }
        
        return $clean;
    }

    /**
     * Decode Object
     * 
     * Decodes an object which has been encoded
     * @param object Encoded Object
     * @param boolean Unescape data
     * @return object Decoded ISO-8859-1 Object
     */
    public static function Decode_Object($clean, $unescape = false)
    {
    	if(!is_object($clean)) return false;
        
        foreach($clean as $clean_key => $clean_value)
        {
        	if(is_object($clean_value))
            {
            	$clean->{$clean_key} = self::Decode_Object($clean_value,$unescape);
            }
            elseif(is_array($clean_value))
            {
                $clean->{$clean_key} = self::Decode_Array($clean_value,$unescape);
            }
            elseif(is_string($clean_value))
            {
                $clean->{$clean_key} = self::Decode_String($clean_value,$unescape);
            }
        }
        
        return $clean;
    }

    /**
     * Encode
     * 
     * Encodes an array, object, or string to its UTF-8 form
     * @param mixed Data to be encoded
     * @param boolean Escape Data?
     * @return mixed
     */
    public static function Encode($dirty, $escape = false)
    {
    	if(!is_string($dirty) && !is_array($dirty) && !is_object($dirty)) return $dirty;
        
        if(is_object($dirty))
        {
            return self::Encode_Object($dirty,$escape);
        }
        elseif(is_array($dirty))
        {
            return self::Encode_Array($dirty,$escape);
        }
        else
        {
            return self::Encode_String($dirty,$escape);
        }
    }

    /**
     * Encode String
     * 
     * Encodes a string into UTF-8
     * @param string ISO-8859-1 string
     * @param boolean Escape Data
     * @return string UTF-8 string
     */
	public static function Encode_String($dirty, $escape = false)
    {
        if(!is_string($dirty)) return false;
        
    	$es = "";
        $max = strlen($dirty);
        for ($i = 0; $i < $max; $i++)
        {
            if ($dirty{$i} == "&")
            {
                $e = ($escape) ? "&amp;" : "&";
            }
            elseif ((ord($dirty{$i}) < 32) || (ord($dirty{$i}) > 127) || 
                    (ord($dirty{$i}) == 39) || (ord($dirty{$i}) == 34)) //single and double quotes
            {
            	$e = utf8_encode($dirty{$i});
            	if($escape)
                {
                    $e = urlencode($e);
                    $e = preg_replace('/\%(..)\%(..)\%(..)/','&#x\1;&#x\2;&#x\3;',$e);
                    $e = preg_replace('/\%(..)\%(..)/','&#x\1;&#x\2;',$e);
                    $e = preg_replace('/\%(..)/','&#x\1;',$e);
                }
            }
            else
            {
                $e = $dirty{$i};
            }
              
            $es .= $e;
        }
        
        return $es;
    }
    
    /**
     * Encode Array
     * 
     * Encodes an array into UTF-8
     * @param array ISO-8859-1 array
     * @param boolean Escape Data
     * @return array UTF-8 array
     */
	public static function Encode_Array($dirty, $escape = false)
    {
        if(!is_array($dirty)) return false;
        
        foreach($dirty as $dirty_key => $dirty_value)
        {
            if(is_object($dirty_value))
            {
            	$dirty[$dirty_key] = self::Encode_Object($dirty_value,$escape);
            }
            elseif(is_array($dirty_value))
            {
                $dirty[$dirty_key] = self::Encode_Array($dirty_value,$escape);
            }
            elseif(is_string($dirty_value))
            {
                $dirty[$dirty_key] = self::Encode_String($dirty_value,$escape);
            }
        }
        
        return $dirty;
    }
    
    /**
     * Encode Object
     * 
     * Encodes an object to its UTF-8 form
     * @param object Object to be encoded
     * @return object
     */
    public static function Encode_Object($dirty, $escape = false)
    {
    	if(!is_object($dirty)) return false;
        
        foreach($dirty as $dirty_key => $dirty_value)
        {
            if(is_object($dirty_value))
            {
                $dirty->{$dirty_key} = self::Encode_Object($dirty_value,$escape);
            }
            elseif(is_array($dirty_value))
            {
                $dirty->{$dirty_key} = self::Encode_Array($dirty_value,$escape);
            }
            elseif(is_string($dirty_value))
            {
                $dirty->{$dirty_key} = self::Encode_String($dirty_value,$escape);
            }
        }
        
        return $dirty;
    }
}
?>
