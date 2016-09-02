<?php
/**
	@publicsection
	@public
	@fn return string truncate ($the_string, $break)
	@brief
		Word-breaks and truncates a string.

	Takes a string and word breaks according to a
	list of character lengths, truncating the string
	after the final word break length.  If truncation
	takes place (the original string is longer than
	the resulting string) an ellipsis (&#8230;) is appended.

	For example, if the string

		"Now is the time for all good men to come to the aid of their party."

	is run through the function with a break string of

	"15, 10"

	the result would be:

		"Now is the time<br />
		 for all &#8230;<br />

	@param $the_string string
		The string you want to break and truncate.  May contain line breaks.
	@param $break mixed
		Integer, comma-delimited string or array.  The character count(s) at
		which to perform the word breaks.  String will be truncated past the
		word break on the final value.
	@return string
	@todo
		- enable alternative break characters (such as pipe or semicolon)
		- allow patterns such as every line 30 characters except for the
			last line which should be 15 characters.
*/

function truncate ($the_string, $break)
{
	// strip out all line breaks, and remove external white spaces
	// and html or PHP tags
	$the_string = strip_tags (trim (str_replace ("\n", " ", $the_string)));
	// if $break's not an array, make it one
	if (!is_array ($break))
	{
		$break = explode (",", $break);
	}
	$truncated = array ();
	foreach ($break as $br)
	{
		// if the values in the break array are strings, make them
		// integers
		$br = intval (trim ($br));
		// wrap the string according to the first value in $break,
		// then explode into an array on the inserted line breaks
		$str_arr = explode ("\n", wordwrap ($the_string, $br, "\n"));
		// remove all line breaks (explode does not strip out the
		// character used to split a string)
		$str_arr = array_map ("trim", $str_arr);
		// grab and remove the first element of the array, placing it
		// in the truncated array.
		$truncated[] = array_shift ($str_arr);
		// cram the remaining parts of the original string back together
		// for further looping if required
		$the_string = implode (" ", $str_arr);
	}

	// Cram what we've got back together.
	$new_string = implode ("<br />\n", $truncated);
	// if the string was truncated, append an ellipsis
	$new_string .= strlen ($new_str) < strlen ($the_string) ? " &#8230;" : "";

	return $new_string;
}
?>