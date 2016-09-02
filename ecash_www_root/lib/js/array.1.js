// array.1.js
// additions to the JavaScript Array object functionality
// written by David Bryant

Array.prototype.associative = array_associative;
Array.prototype.contains = array_contains;
Array.prototype.difference = array_difference;
Array.prototype.fill = array_fill;
Array.prototype.dump = array_dump;

// NOTE: this will return false if it is an associative array with a string
// that evaluates to an integer, such as "0";
function array_associative ()
{
	for (var i in this)
	{
		return isNaN (i);
	}
}

function array_contains (val)
{
	if (this.associative ())
	{
		for (var i in this)
		{
			if (this[i] == val)
			{
				return true;
			}
		}
	}
	else
	{
		for (var i = 0; i < this.length; i++)
		{
			if (this[i] == val)
			{
				return true;
			}
		}
	}
	return false;
}

function array_difference (filter_array)
{
	var output_array = new Array ();
	if (this.associative ())
	{
		for (var i in this)
		{
			if (!filter_array.contains (this[i]))
			{
				output_array[i] = this[i];
			}
		}
	}
	else
	{
		for (var i = 0; i < this.length; i++)
		{
			if (!filter_array.contains (this[i]))
			{
				output_array[output_array.length] = this[i];
			}
		}
	}
	return output_array;
}

/*
	fill () notes:
	The final two (optional) arguments are only used for filling associative
	arrays, and if present the function will use the start_idx as the first
	increment in the associative array key.  If the start_idx is 105, the
	associative_prefix is "foo_", and the zero_padding_amount is 4, then the
	resulting keys will be:

		foo_0105
		foo_0106
		foo_0107
		foo_0108
		and so on...

	If you want an underscore setting the prefix off from the numbers, as in
	the above example, you will need to supply it yourself.  If a zero_padding
	argument is not present or zero, no zero-padding will take place.
*/
function array_fill (start_idx, num_entries, fill_value, associative_prefix, zero_padding)
{
	if (associative_prefix)
	{
		for (var i = start_idx; i < (start_idx + num_entries); i++)
		{
			increment_str = ""+i;
			if (zero_padding && zero_padding > 0)
			{
				if (increment_str.length < zero_padding)
				{
					increment_str = new Array (zero_padding - increment_str.length+1).join ("0") + increment_str;
				}
			}
			this[associative_prefix+increment_str] = fill_value;
		}
	}
	else
	{
		for (var i = start_idx; i < (start_idx + num_entries); i++)
		{
			this[i] = fill_value;
		}
	}
}

// argument is optional; if present will write the dump to the
// screen for you.  Default is to just spit back the string.
function array_dump (write_to_document)
{
	var output_str = "<pre>\nArray {\n";
	// create an array that includes the new prototypes
	var proto_arr = new Array;
	// copy the names of the prototypes into the new array:
	for (var i in Array.prototype)
	{
		proto_arr[proto_arr.length] = i;
	}
	// output any property of the array that isn't a prototype
	for (var i in this)
	{
		if (!proto_arr.contains (i))
		{
			output_str += "\t["+i+"] =&gt; "+this[i]+"\n";
		}
	}
	output_str += "}\n</pre>\n";
	if (write_to_document)
	{
		document.write (output_str);
		return true;
	}
	else
	{
		return output_str;
	}
}
