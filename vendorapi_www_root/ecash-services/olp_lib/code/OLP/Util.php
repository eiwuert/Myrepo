<?php
/**
 * Utility function for OLP.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_Util
{
	/**
	 * Pay period constants
	 */
	const PAY_PERIOD_WEEKLY = 'WEEKLY';
	const PAY_PERIOD_BI_WEEKLY = 'BI_WEEKLY';
	const PAY_PERIOD_FOUR_WEEKLY = 'FOUR_WEEKLY';
	const PAY_PERIOD_TWICE_MONTHLY = 'TWICE_MONTHLY';
	const PAY_PERIOD_MONTHLY = 'MONTHLY';
	
	/**
	 * Supported ENDIANness.
	 */
	const UNKNOWN_ENDIAN = 0;
	const LITTLE_ENDIAN = 1;
	const BIG_ENDIAN = 2;
	
	/**
	 * Calculates the net pay per pay period based on the monthly net.
	 *
	 * @param string $pay_period
	 * @param int $monthly_net
	 * @return int
	 */
	public static function calculatePayPeriodNet($pay_period, $monthly_net)
	{
		$monthly_net = (int)$monthly_net;
		$net_pay = 0;
		
		switch (strtoupper($pay_period))
		{
			case self::PAY_PERIOD_WEEKLY:
				$net_pay = ($monthly_net * 12) / 52;
				break;
			case self::PAY_PERIOD_BI_WEEKLY:
				$net_pay = ($monthly_net * 12) / 26;
				break;
			case self::PAY_PERIOD_FOUR_WEEKLY:
				$net_pay = ($monthly_net * 12) / 13;
				break;
			case self::PAY_PERIOD_TWICE_MONTHLY:
				$net_pay = $monthly_net / 2;
				break;
			case self::PAY_PERIOD_MONTHLY:
				$net_pay = $monthly_net;
				break;
			default:
				throw new InvalidArgumentException("pay period '$pay_period' is not valid");
		}
		
		return (int)round($net_pay);
	}
	
	/**
	 * Allows fancy data mapping of arrays. Does NOT merge in result with source data.
	 *
	 * @param array $source_data
	 * @param array $data_map
	 * @param bool $process_recursively
	 * @param bool $throw_exceptions
	 * @return array
	 */
	public static function dataMap(array $source_data, array $data_map, $process_recursively = FALSE, $throw_exceptions = TRUE)
	{
		$data = array();
		$data_previous = NULL;
		
		$steps = 0;
		$maximum_steps = 10;
		do
		{
			// Store our current data array to our previous value for recursion
			$data_previous = $data;
			
			foreach ($data_map AS $key => $actions)
			{
				// If key starts with a '@', assume this is a value built from others
				if ($key[0] == '@')
				{
					$key = substr($key, 1);
					
					$hits = preg_match_all('/(%%%([^%]+)%%%)/', $key, $matches);
					$replace_search = array();
					$replace_data = array();
					$valid = FALSE;
					for ($i = 0; $i < $hits; $i++)
					{
						$replace_search[] = $matches[1][$i];
						$replace_data[] = isset($source_data[$matches[2][$i]]) ? $source_data[$matches[2][$i]] : NULL;
						if (isset($source_data[$matches[2][$i]])) $valid = TRUE;
					}
					
					if ($valid)
					{
						$key = str_replace($replace_search, $replace_data, $key);
						
						$data = array_merge($data, self::dataMapProcessKey($key, $actions));
					}
				}
				// If key starts with a '!', assume it is an executable action
				elseif ($key[0] == '!')
				{
					if (preg_match('/^\!(\w+)\((.*)\)$/', $key, $matches))
					{
						switch ($matches[1])
						{
						}
					}
				}
				elseif (isset($source_data[$key]))
				{
					$data = array_merge($data, self::dataMapProcessKey($source_data[$key], $actions));
				}
			}
			
			// Tail recursion optimization
			$source_data = $data;
			
			// Increment our fail-safe
			$steps++;
			
			// As a protection against flip-flopping data maps, do not
			// process an array too many times.
			if ($steps == $maximum_steps)
			{
				if ($throw_exceptions)
				{
					throw new Exception("dataMap() processed a data mapping {$steps} times without reaching a stable result.");
				}
				break;
			}
		}
		while ($process_recursively && count(array_diff_assoc($data, $data_previous)));
		
		return $data;
	}
	
	/**
	 * Handles actions on one source value.
	 *
	 * @param string $source_data
	 * @param mixed $actions
	 * @return array
	 */
	protected static function dataMapProcessKey($source_data, $actions)
	{
		$data = array();
		
		// If an array, this key can map to multiple other actions
		if (is_array($actions))
		{
			foreach ($actions AS $action)
			{
				$data = array_merge($data, self::dataMapProcessKey($source_data, $action));
			}
		}
		// If an action begins with a '/' assume it is a regular expression with named subpatterns
		elseif ($actions[0] == '/')
		{
			if (preg_match($actions, $source_data, $matches))
			{
				foreach ($matches AS $match_name => $match_value)
				{
					if (!is_int($match_name))
					{
						$data[$match_name] = $match_value;
					}
				}
			}
		}
		else
		{
			$data[$actions] = $source_data;
		}
		
		return $data;
	}
	
	/**
	 * Advances some business days. Supports positive/negative days.
	 *
	 * @param string $date
	 * @param int $days
	 * @return string
	 */
	public function advanceBusinessDay($date, $days)
	{
		$timestamp = strtotime($date);
		
		// Grab bank holidays
		$holidays = new Date_BankHolidays_1($timestamp);
		$date_normalizer = new Date_Normalizer_1($holidays, $timestamp);
		
		// Advance X days
		if ($days >= 0)
		{
			$next_timestamp = $date_normalizer->advanceBusinessDays($timestamp, $days);
		}
		else
		{
			$next_timestamp = $date_normalizer->rewindBusinessDays($timestamp, -$days);
		}
		
		$next_date = date('Y-m-d', $next_timestamp);
		
		return $next_date;
	}

	/**
	 * Is today still a business day?
	 *
	 * @param string $property_short
	 * @param bool $forward
	 * @param string $current_time
	 * @return bool
	 */
	public function isTodayABusinessDay($property_short = NULL, $forward = TRUE, $current_time = NULL)
	{
		$timestamp = strtotime($current_time);
		if ($timestamp === FALSE)
		{
			$timestamp = time();
		}
		
		// Grab bank holidays
		$holidays = new Date_BankHolidays_1($timestamp);
		$date_normalizer = new Date_Normalizer_1($holidays, $timestamp);
		
		// Get when our business day ends. If not defined, assume business
		// day ends at the end of the day.
		$enterprise_data = EnterpriseData::isEnterprise($property_short) ? EnterpriseData::getEnterpriseData($property_short) : array();
		$business_days_ends = isset($enterprise_data['business_days_ends']) ? $enterprise_data['business_days_ends'] : 24;
		
		$is_today_a_business_day =
			// Is a weekday
			!(
				$date_normalizer->isWeekend($timestamp)
				|| $date_normalizer->isHoliday($timestamp)
			)
			&& (
				(
					// And during operating hours if jumping forward
					date('G', $timestamp) < $business_days_ends
					&& $forward
				)
				|| (
					// Or NOT during operating hours if jumping backwards
					date('G', $timestamp) >= $business_days_ends
					&& !$forward
				)
			)
		;
		
		return $is_today_a_business_day;
	}
	
	/**
	 * Normalize a string, probably from a file, that can be in different
	 * formats, like MAC line endings, or UTF16. All non-printable characters
	 * are removed.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function normalizeString($input)
	{
		return
			self::normalizeNonPrintable(
			self::normalizeLineEndings(
			self::normalizeUTF16(
				$input
		)));
	}
	
	/**
	 * Handles UTF16 to UTF8.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function normalizeUTF16($input)
	{
		// By default, return input
		$output = $input;
		
		if (strlen($input) >= 2)
		{
			$byte_0 = ord($input[0]);
			$byte_1 = ord($input[1]);
			
			$byte_order_marker = NULL;
			if ($byte_0 == 0xFF && $byte_1 == 0xFE)
			{
				$byte_order_marker = self::LITTLE_ENDIAN;
			}
			elseif ($byte_0 == 0xFE && $byte_1 == 0xFF)
			{
				$byte_order_marker = self::BIG_ENDIAN;
			}
			
			if ($byte_order_marker !== NULL)
			{
				$machine_byte_order = self::getEndian();
				
				if ($machine_byte_order !== self::UNKNOWN_ENDIAN)
				{
					$switch_byte_order = $machine_byte_order !== $byte_order_marker;
					
					// Loop through our input, switching byte order if needed,
					// and trying our best to convert unicode into ASCII
					$output = '';
					$length = strlen($input);
					for ($i = 2; $i < $length; $i += 2)
					{
						$char = ($switch_byte_order)
							? ord($input[$i    ]) << 8 | ord($input[$i + 1])
							: ord($input[$i + 1]) << 8 | ord($input[$i    ]);
						
						if ($char >= 0x0001 && $char <= 0x007F)
						{
							$output .= chr($char);
						}
						elseif ($char >= 0x007F)
						{
							$output .= chr(0xE0 | (($char >> 12) & 0x0F));
							$output .= chr(0x80 | (($char >>  6) & 0x3F));
							$output .= chr(0x80 | (($char      ) & 0x3F));
						}
						else
						{
							$output .= chr(0xC0 | (($char >> 12) & 0x1F));
							$output .= chr(0x80 | (($char      ) & 0x3F));
						}
					}
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Determines what endian we are running on.
	 *
	 * @return string
	 */
	public static function getEndian()
	{
		// Since our endianness won't change, cache the result
		static $result = NULL;
		
		if ($result === NULL)
		{
			$test_value = 0x6162797A;
			
			// Use machine endianness
			switch (pack('L', $test_value))
			{
				// Test if we are little-endian
				case pack('V', $test_value):
					$result = self::LITTLE_ENDIAN;
					break;
				
				// Test if we are big-endian
				case pack('N', $test_value):
					$result = self::BIG_ENDIAN;
					break;
				
				default:
					$result = self::UNKNOWN_ENDIAN;
					break;
			}
		}
		
		return $result;
	}
	
	/**
	 * Converts any line endings in string to PHP_EOL.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function normalizeLineEndings($input)
	{
		$output = preg_replace("/(\n\r|\r\n|\r|\n)/", PHP_EOL, $input);
		
		return $output;
	}
	
	/**
	 * Strip all non-printable characters.
	 *
	 * @param $input
	 * @return string
	 */
	public static function normalizeNonPrintable($input)
	{
		// For Perl, [:print:] is [:graph:] union [:space:], but in PCRE it isn't...
		$output = preg_replace('/[^[:graph:][:space:]]+/', '', $input);
		
		return $output;
	}
}
