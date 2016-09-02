<?php

  /**
    Calculates US Bank Holidays
    Copyright (C) 2005  Justin Foell justin@foell.org

    Full Text: http://www.gnu.org/licenses/lgpl.txt

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

   */

class Bank_Holidays
{
	var $now;
	var $year;
	
	function Bank_Holidays()
	{
		$this->now = strtotime("now");
		$this->year = date("Y");
	}

	function Get_Holidays($format = NULL, $s = "-")
	{
		$holidays = array();
		$holidays[] = $this->New_Years_Day();
		$holidays[] = $this->MLK_Observed();
		$holidays[] = $this->Presidents_Day();
		$holidays[] = $this->Memorial_Day();
		$holidays[] = $this->Independence_Day();
		$holidays[] = $this->Labor_Day();
		$holidays[] = $this->Columbus_Day();
		$holidays[] = $this->Veterans_Day();
		$holidays[] = $this->Thanksgiving_Day();
		$holidays[] = $this->Christmas_Observed();
		
		// Not passing a format will cause the default timestamps to return
		if( !is_null($format) )
		{
			$format = strtolower($format);
			
			foreach($holidays as $key => $holiday)
			{
				switch($format)
				{
					case "us"; // mm-dd-yyyy
						$holidays[$key] = date("m{$s}d{$s}Y", $holiday);
					break;
					
					case "eur"; // dd-mm-yyyy
						$holidays[$key] = date("d{$s}m{$s}Y", $holiday);
					break;

					default:
					case "iso"; // yyyy-mm-dd
						$holidays[$key] = date("Y{$s}m{$s}d", $holiday);
					break;
					
					
				}
			}
		}
		
		return $holidays;
	}
	
	function New_Years_Day($year = NULL)
	{
		if($year == NULL)
			$year = $this->year;
		$new_years = strtotime("1 January {$year}");
		if($this->Less_Than_Now($new_years))
			return $this->New_Years_Day($this->year + 1);
		return $new_years;
	}

	function Nth_DOW($month, $day, $plus = NULL, $year = NULL)
	{
		if($year == NULL)
			$year = $this->year;
		$date = strtotime("1 {$month} {$year}");
		
		if(date("l", $date) != "{$day}")
		{
			$date = strtotime("this {$day}", $date);
		}
		
		if($plus != NULL)
		{
			$date = strtotime("+{$plus} weeks", $date);
		}
		
		if($this->Less_Than_Now($date))
			return $this->Nth_DOW($month, $day, $plus, $this->year + 1);
		return $date;
	}
	
	function MLK_Observed()
	{
		return $this->Nth_DOW('January', 'Monday', 2);
	}

	function Presidents_Day()
	{
		return $this->Nth_DOW('February', 'Monday', 2);		
	}

	function Memorial_Day($year = NULL)
	{
		if($year == NULL)
			$year = $this->year;		
		$may31 = strtotime("31 May {$year}");
		$memorial = NULL;
		if(date("D", $may31) == "Mon")
		{
			$memorial = $may31;
		}
		else
		{
			$memorial = strtotime("last Monday", $may31);
		}
		if($this->Less_Than_Now($memorial))
			return $this->Memorial_Day($this->year + 1);
		return $memorial;		
	}

	function Independence_Day($year = NULL)
	{
		if($year == NULL)
			$year = $this->year;
		$july4 = strtotime("4 July {$year}");
		if($this->Less_Than_Now($july4))
			return $this->Independence_Day($this->year + 1);
		return $july4;		
	}

	function Labor_Day($year = NULL)
	{
		return $this->Nth_DOW('September', 'Monday');		
	}

	function Columbus_Day($year = NULL)
	{
		return $this->Nth_DOW('October', 'Monday', 1);		
	}

	function Veterans_Day($year = NULL)
	{
		if($year == NULL)
			$year = $this->year;
		$veterans = strtotime("11 November {$year}");
		if($this->Less_Than_Now($veterans))
			return $this->Veterans_Day($this->year + 1);
		return $veterans;		
	}

	function Thanksgiving_Day($year = NULL)
	{
		return $this->Nth_DOW('November', 'Thursday', 3);		
	}

	function Christmas_Observed($year = NULL)
	{
		if($year == NULL)
			$year = $this->year;
		$xmas = strtotime("25 December {$year}");
		$observed = NULL;
		if(date("D", $xmas) == "Sat")
		{
			$observed = strtotime("24 December {$year}");
		}
		elseif(date("D", $xmas) == "Sun")
		{
			$observed = strtotime("26 December {$year}");
		}
		else
		{
			$observed = $xmas;
		}
		if($this->Less_Than_Now($observed))
			return $this->Christmas_Observed($this->year + 1);
		return $observed;
	}

	function Less_Than_Now($date)
	{
		return (($date < $this->now) && (date("m/d/Y", $date) != date("m/d/Y", $this->now)));
	}
}

?>