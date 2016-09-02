<?php
/* -----------------------------------------

kronos.php
general date and time manipulation routines

written by David Bryant
January 6, 2006

----------------------------------------- */

// expects unix timestamps or dates in the format YYYY-MM-DD
// and only operates on bare dates, no fractions of days.
// returns an integer on success, false on failure
function days_apart ($from, $to)
{
	// date conversion mitigation
	$psychedelic_new_year = $from == "1969-12-31" || $to == "1969-12-31" ? true : false;

	// if they're strings, make them unix timestamps
	$from = is_string ($from) ? strtotime ($from) : $from;
	$to = is_string ($to) ? strtotime ($to) : $to;

	// if either date string is 1969-12-31, which evaluates to -1, add ten seconds to each
	// so they don't show up as strtotime failure in PHP versions prior to 5.1.0 (after which
	// point the failure return value is false).  this will preserve the relationship between them.
	// we'll assume in this very limited case that both dates are valid; if not the failed date
	// has evaluated to -1 if invalid and pre-5.1.0 anyway, so no harm no foul.
	if ($psychedelic_new_year && $from !== false && $to !== false)
	{
		$from += 10;
		$to += 10;
	}

	// now check for -1 if the version is prior to the change, and if so
	// change it to false
	$from = version_compare (PHP_VERSION, "5.1.0") < 0 && $from == -1 ? false : $from;
	$to = version_compare (PHP_VERSION, "5.1.0") < 0 && $to == -1 ? false : $to;

	// whew.  now we can get down to it
	if ($from && $to)
	{
		// restore the dates if required
		if ($psychedelic_new_year)
		{
			$from -= 10;
			$to -= 10;
		}

		// normalize the dates to eliminate partial days -- we go by the whole date only
		$from = strtotime (date ("Y-m-d", $from));
		$from = strtotime (date ("Y-m-d", $from));

		// make sure $to is in $from's future (or at least the same)
		if ($to < $from)
		{
			$temp = $from;
			$from = $to;
			$to = $temp;
		}

		// add 'em up and return
		$day_counter = 0;
		$current_day = $from;
		while ($current_day < $to)
		{
			$current_day = strtotime ("+1 day", $current_day);
			$day_counter++;
		}
		return $day_counter;
	}
	else
	{
		// at least one of the dates is bad
		return false;
	}
}


?>