<?php

class int2Date
{

	var $month;
	var $day;
	var $thisyear;
	var $int2parse;
	var $fulldate;
	var $isleap = FALSE;

	function int2Date()
	{
		return TRUE;
	}

	function parseDate($int2parse, $thisyear, $type)
	{
	$this->int2parse = $int2parse;
	$this->thisyear = $thisyear;
	$this->type = $type;
	// Returns valid date from int(day of the year) up to 366
	// echo "int passed = $this->int2parse<BR>this year = $this->thisyear<BR>";
		if($this->thisyear == 2004 || $this->thisyear == 2008 || $this->thisyear == 2012 || $this->thisyear == 2016 || $this->thisyear == 2020)
		{
		$this->isleap = TRUE;
		// leap year
			if($this->int2parse <= 30)
			{
			// we're in January
				$this->month = 01;
				$this->day = (31 - (31 - $this->int2parse));

			} else if ($this->int2parse <= 59)
			{
			// We're in February
				$this->month = 02;
				$this->day = (29 - (59 - $this->int2parse));

			} else if ($this->int2parse <= 90)
			{
			// We're in March
				$this->month = 03;
				$this->day = (31 - (90 - $this->int2parse));
			} else if ($this->int2parse <= 120)
			{
			// We're in April
				$this->month = 04;
				$this->day = (30 - (120 - $this->int2parse));
			} else if ($this->int2parse <= 151)
			{
			// We're in May
				$this->month = 05;
				$this->day = (31 - (151 - $this->int2parse));
			} else if ($this->int2parse <= 181)
			{
			// We're in June
				$this->month = 06;
				$this->day = (30 - (181 - $this->int2parse));
			} else if ($this->int2parse <= 212)
			{
			// We're in July
				$this->month = 07;
				$this->day = (31 - (212 - $this->int2parse));
			} else if ($this->int2parse <= 243) {
			// We're in August
				$this->month = 08;
				$this->day = (31 - (242 - $this->int2parse));
			} else if ($this->int2parse <= 273) {
			// We're in September
				$this->month = 09;
				$this->day = (30 - (273 - $this->int2parse));
			} else if ($this->int2parse <= 304) {
			// We're in October
				$this->month = 10;
				$this->day = (31 - (304 - $this->int2parse));
			} else if ($this->int2parse <= 334) {
			// We're in November
				$this->month = 11;
				$this->day = (30 - (334 - $this->int2parse));
			} else if ($this->int2parse <= 366) {
			// We're in December
				$this->month = 12;
				$this->day = (31 - (366 - $this->int2parse));
			}

		} else
		{
	// not a leap year

			if($this->int2parse <= 30)
			{
			// we're in January
				$this->month = 01;
				$this->day = (31 - (31 - $this->int2parse));

			} else if ($this->int2parse <= 58)
			{
			// We're in February
				$this->month = 02;
				$this->day = (28 - (58 - $this->int2parse));

			} else if ($this->int2parse <= 89)
			{
			// We're in March

				$this->month = 03;
				$this->day = (31 - (89 - $this->int2parse));
			} else if ($this->int2parse <= 119)
			{
			// We're in April
				$this->month = 04;
				$this->day = (30 - (119 - $this->int2parse));
			} else if ($this->int2parse <= 150)
			{
			// We're in May
				$this->month = 05;
				$this->day = (31 - (150 - $this->int2parse));
			} else if ($this->int2parse <= 180)
			{
			// We're in June
				$this->month = 06;
				$this->day = (30 - (180 - $this->int2parse));
			} else if ($this->int2parse <= 211)
			{
			// We're in July
				$this->month = 07;
				$this->day = (31 - (211 - $this->int2parse));
			} else if ($this->int2parse <= 242) {
			// We're in August
				$this->month = 08;
				$this->day = (31 - (242 - $this->int2parse));
			} else if ($this->int2parse <= 272) {
			// We're in September
				$this->month = 09;
				$this->day = (30 - (272 - $this->int2parse));
			} else if ($this->int2parse <= 303) {
			// We're in October
				$this->month = 10;
				$this->day = (31 - (303 - $this->int2parse));
			} else if ($this->int2parse <= 333) {
			// We're in November
				$this->month = 11;
				$this->day = (30 - (333 - $this->int2parse));
			} else if ($this->int2parse <= 365) {
			// We're in December
			// echo "Dec - $int2parse<BR>";
				$this->month = 12;
				$this->day = (31 - (365 - $this->int2parse));
			}
		}

		if ($this->day <= 0)
		{

			$this->day = abs ($this->day);
			$this->day++;

			$this->int2parse = $this->day;
			if ($this->isleap)
			{
				echo "is a leap year<BR>";
				$this->int2parse = 367 - $this->int2parse;
			} else
			{
				echo "not a leap year - $this->int2parse<BR> ";
				$this->int2parse = 366 - $this->int2parse;
			}

			echo "int2parse = $this->int2parse<BR>";
			$this->thisyear--;

			$this->parseDate($this->int2parse, $this->thisyear, $this->type);
			$this->fulldate = $this->formatDate();
			return $this->fulldate;
		} else
		{

			$fulldate = $this->formatDate();
			return $fulldate;
		}


	}
	
	function formatDate()
	{

	        if ($this->month < 10 && substr($this->month, 0, 1) != 0)
		{
		// $rest = substr("abcdef", 0, 8); // returns "abcdef"
			$this->month = "0".$this->month;
		}
		if ($this->day < 10 && substr($this->day, 0, 1) != 0)
		{
			$this->day = "0".$this->day;
		}

		if ($this->type == "display")
		{
		// format date as "$month/$day/$thisyear"
			$this->fulldate = $this->month."/".$this->day."/".$this->thisyear;
		} else
		{
		// format date for query 'yyyymmdd'
			$this->fulldate = $this->thisyear.$this->month.$this->day;
		}


		return $this->fulldate;
	}
}
?>
