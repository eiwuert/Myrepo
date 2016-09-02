<?php

/**
 * Encapsulate all of the rounding algorithms.  
 *
 * To retrieve an algorithm, instantiate with a type and a
 * precision. If more algorithms are added it might make sense to
 * change this class to use more of a strategy pattern. I would have
 * done that to begin with, but it seems like these algorithms are
 * pretty much it...  Originially from
 * http://gforge.sellingsource.com/svn/ecash/ecash_common/branches/recash_prep/code/ECash/Scheduling/Rounder.php
 * 
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Scheduling
 */
class ECash_Transactions_Rounder
{
	/**
	 * Supported Algorithms: Standard
	 */
	const ALG_STANDARD = 'Standard';
	
	/**
	 * Supported Algorithms: Down
	 */
	const ALG_DOWN = 'Down';
	
	/**
	 * Supported Algorithms: To Even
	 */
	const ALG_TOEVEN = 'ToEven';
	
	const DEFAULT_TYPE = self::ALG_DOWN;
	const DEFAULT_PRECISION = 2;

	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var int
	 */
	protected $precision;

	/**
	 * Creates a new rounder
	 *
	 * @param string $type
	 * @param int $precision
	 */
	public function __construct($type = NULL, $precision = NULL)
	{
		$this->setType($type);
		$this->precision = $precision === NULL ? self::DEFAULT_PRECISION : $precision;
	}


	/**
	 * Used to convert business rule types to 'new' standard types if
	 * need be
	 */
	private function setType($type)
	{
		if($type === NULL)
		{
			$this->type = self::DEFAULT_TYPE;
			return;
		}
		
		$old_type = strtolower($type);
		$types = array('none', 'banker', 'up', 'default');
		if(in_array($old_type, $types))
		{
			switch($old_type)
			{
				case 'none':
					$this->type = self::ALG_DOWN;
					break;
			
				case 'banker':
					$this->type = self::ALG_TOEVEN;
					break;	
			
	   			case 'up':
					$this->type = self::ALG_UP;
					break;

				case 'default':
				default:
					$this->type = self::ALG_STANDARD;
					break;
			}
		}
		else
		{
			$this->type = $type;
		}
	}
	
	/**
	 * Rounds a value.
	 *
	 * @param float $value
	 * @return float
	 */
	public function round($value)
	{
		$method = 'round' . (
			(empty($this->type) || !method_exists($this, 'round' . $this->type))
			? self::ALG_STANDARD
			: $this->type
		);
		
		return $this->$method($value, $this->precision);
	}

	/**
	 * Always rounds the digit down/truncates the value, similar to a
	 * floor() operation, but can occur at the nth decimal place
	 *
	 * @param float $amount the unrounded amount
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded amount
	 */
	public function roundDown($amount, $decimal_place)
	{
		//bcadd truncates the amount past the decimal place specified
		return bcadd($amount, '0.0', $decimal_place);
	}
		
	/**
	 * Performs round-to-even (bankers') rounding on amount.
	 * This is identical to the common method of rounding except when the digit(s) 
	 * following the rounding digit start with a five and have no non-zero digits after it.
	 * The algorithm (taken from http://en.wikipedia.org/wiki/Rounding#Round-to-even_method) is:
	 * <ol>
     * <li>Decide which is the last digit to keep.</li>
     * <li>Increase it by 1 if the next digit is 6 or more, or a 5 followed by one or more non-zero digits.</li>
     * <li>Leave it the same if the next digit is 4 or less</li>
	 * <li>Otherwise, all that follows the last digit is a 5 and possibly trailing zeroes; 
     * 		then change the last digit to the nearest even digit. 
     * 		That is, increase the rounded digit if it is currently odd; leave it if it is already even.</li>
     * </ol>
	 *
     * @TODO Unit Test -- I just ported this code from the old Interest_Calculator [JustinF]
	 * @param float $amount the raw, unrounded amount
	 * @param int $decimal_place the number of decimals places you want to use when rounding
	 * @return float the bankers' rounded amount.
	 */
	public function roundToEven($amount,$decimal_place)
	{
		$format_str = '%01.' . ($decimal_place + 1) . 'f';
	    $money_str = sprintf($format_str, self::roundUp($amount, ($decimal_place + 1))); 
	    $last_pos = strlen($money_str)-1;   
	    if ($decimal_place == 0)
	    {
			$second_last_pos = strlen($money_str)-3; 
	    }
	    else 
	    {
			$second_last_pos = strlen($money_str)-2;                     
	    }
	    
	    if ($money_str[$last_pos] === '5')
	    {
			$money_str[$last_pos] = ((int)$money_str[$second_last_pos] & 1) ? '9' : '0'; 
	    }
	    return round($money_str, $decimal_place); 
	}

	/**
	 * This method exists to check for the fact that IEEE standards
	 * recommends that the display of a value, even if it does compare
	 * equal to zero, should preserve the sign.  This means round() can
	 * (and does) return '-0' in some cases.  I don't want to see that
	 * negative sign [JustinF]
	 * 
	 * @param float $amount the unrounded amount
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded amount
	 */	
	 public function roundStandard($amount, $decimal_place)
	 {
		 $val = round($amount,$decimal_place);
		 //this must be double-equal (not triple) to catch '-0'
		 if ($val == 0)
			 return 0;
		 return $val;
	 }		
}

?>