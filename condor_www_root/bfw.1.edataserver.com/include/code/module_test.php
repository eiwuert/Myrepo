<?php
/**
 * @publicsection
 * @brief The Module Test will return the module for the server
 * 
 * Use the Get_Module function to return the module name based on the server name 
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Mar 29, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */
 
class Module_Test {
    /**
     * Unknown Module
     * 
     * Could not determine the module
     * @var int
     */
    public static $UNKNOWN = 0;
    
    /**
     * OLP Module
     * @var int
     */
    public static $OLP = 1; //start at 1 in case they use == instead of ===
    
    /**
     * CCS Module
     * @var int
     */
    public static $CCS = 2;
    
	public static $OCP = 3; // OCP module
    
    /**
     * @public
     * @brief Get Module
     * 
     * Gets the module. Returns false if no module
     * @return int/boolean
     */
    public static function Get_Module()
    {
        if(preg_match('/^(internal\.bfw|nw|rc\d*|demo\.bfw|doc|bfw|nms|olp)\./', $_SERVER['SERVER_NAME']))
        {
            return self::$OLP;
        }
        elseif(preg_match('/^(?:ocp)\./', $_SERVER['SERVER_NAME']))
        {
        	// Credit Card platform
        	return self::$OCP;
        }
        elseif(preg_match('/^(?:ccs|cfc)\./', $_SERVER['SERVER_NAME']))
        {
            return self::$CCS;
        }
        else
        {
            return self::$UNKNOWN;
        }
    }
    
    /**
     * @public
     * @brief Get Current Module As a String
     * 
     * Get the Module name as a string. Returns false if no module
     * @return string/boolean Current Module as string
     */
    public static function Get_Module_As_String()
    {
        $module = self::Get_Module();
        switch($module)
        {
            case self::$OLP:
                return "olp";
            case self::$CCS;
                return "ccs";
            case self::$OCP;
            	return 'ocp';
            default:
                return "unknown";
        }
    }
}
?>
