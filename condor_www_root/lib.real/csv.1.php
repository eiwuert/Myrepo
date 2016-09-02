<?php

/****************************************************************************

NAME:
	CSV

DESCRIPTION:
	class for creating csv files

WORK:
	0.01 release by your frizzle, pizzle

TODO:
	add checking to ensure all records have the same number of fields

EXAMPLE CODE:
	check the bottom of the file

# vim: set ts=4:

****************************************************************************/

# this currently doesn't actually do anything :P
define("CSV_DEBUG",			false);

define("CSV_QUOTE_DOUBLE",		1);
define("CSV_QUOTE_BACKWACK",	2);

define("CSV_NL_WIN",	"\r\n");
define("CSV_NL_UNIX",	"\n");
define("CSV_NL_MAC",	"\r");

define("CSV_KEEP_TITLES", 1);

class CSV
{

	var $_buf;			# internal buffer
	var $_fp;			# what filehandle should we write to?
	var $_flush;		# should we flush output every time we add a record?

	var $_nl = CSV_NL_UNIX;		# newline settings
	var $_force_quotes = false;	# use quotes always
	var $_quote_method = CSV_QUOTE_DOUBLE;	# how do we escape a quite? default is to double it up

	var $_titles;		# array of field titles for the csv header
	var $_record_count;

	function CSV($options=array())
	{

		# do this once upon creation, do not want to ->reset() it
		$this->setFlush(false);

		$this->reset();

		reset($options);
		while (list($k,$v) = each($options))
		{
			switch (strtoupper($k))
			{
			case "AUTOFLUSH":
			case "FLUSH":
				$this->setFlush($v);
				break;
			case "QUOTE":
			case "QUOTES":
				$this->setQuoteMethod($v);
				break;
			case "FORCEQUOTE":
			case "FORCEQUOTES":
				$this->forceQuotes($v);
				break;
			case "STREAM":
				$this->setStream($v);
				break;
			case "NL":
			case "NEWLINE":
			case "NEWLINES":
				$this->setNewline($v);
				break;
			case "TITLES":
			case "HEADER":
				$this->setTitles($v);
				break;
			}
		}

		return true;
	}

	function reset()
	{
		$opts = func_get_args();
		$this->_fp = NULL;
		if (in_array(CSV_REMEMBER_TITLES, $opts))
		{
			$this->setTitles($this->_titles);
		}
		else
		{
			$this->_titles = NULL;
		}
		$this->resetCount();
		$this->clearBuffer();
		return true;
	}

	# where do we want to output our csv? pass in a file handle, such as the results
	# of $fp = fopen("somefile", "w"); or NULL for stdout
	function setStream($fp=NULL)
	{
		assert(is_resource($fp));
		$this->_fp = $fp;
		return true;
	}

	function forceQuotes($bool)
	{
		$this->_force_quotes = (bool)$bool;
		return true;
	}

	function setFlush($bool)
	{
		$this->_flush = (bool)$bool;
		return true;
	}

	function setTitles($titles)
	{
		assert(is_array($titles));

		$this->_titles = $titles;
		$tmp_titles = $titles;
		$this->_quote_array($tmp_titles);
		$this->_set_title($tmp_titles);

		return true;
	}

	function setQuoteMethod($method)
	{
		$method = intval($method);
		assert(CSV_QUOTE_DOUBLE === $method || CSV_QUOTE_BACKWACK === $method);

		$this->_quote_method = $method;
		return true;
	}

	function setNewline($chars)
	{
		assert(CSV_NL_WIN == $chars || CSV_NL_UNIX == $chars || CSV_NL_MAC == $chars);
		$this->_nl = $chars;
		return true;
	}

	function getBuffer($reset=true)
	{
		$buf = $this->_buf;
		$this->clearBuffer();
		return $buf;
	}

	function getRecordCount()
	{
		return $this->_record_count;
	}

	function clearBuffer()
	{
		$this->_buf = "";
	}

	function resetCount()
	{
		$this->_record_count = 0;
	}

	# arr is the actual data
	# fields should be an-order set of the fields you want as a record, or empty for all
	# read one row from an array into our internal buffer
	function recordFromArray($arr, $fields=NULL)
	{
		assert(is_array($arr));
		assert(NULL === $fields || (is_array($fields) && count($fields) > 0));
		$vals = array();
		if (NULL === $fields)
		{
			# read all fields
			reset($arr);
			while (list($k,$v) = each($arr))
			{
				$vals[] = $v;
			}
		}
		else
		{
			# read only the specified fields
			for ($i = 0; $i < count($fields); $i++)
			{
				# comment out assertion to improve speed
				assert(isset($arr[$fields[$i]]));
				$vals[] = $arr[$fields[$i]];
			}
		}
		return $this->_add_raw_record($vals);
	}

	# read all rows from array into our internal buffer
	function recordsFromArray($arr, $fields=NULL)
	{
		assert(is_array($arr));
		assert(NULL === $fields || (is_array($fields) && count($fields) > 0));
		while (list($k, $v) = each($arr))
		{
			if (false === $this->recordFromArray($arr[$k], $fields))
			{
				return false;
			}
		}
		return true;
	}

	# read a single row from a raw mysql resultset
	function recordFromResultset($rs, $fields=NULL)
	{
		assert(is_resource($rs));
		if ($rec = mysql_fetch_assoc($rs))
		{
			return $this->recordFromArray($rec, $fields);
		}
		return false;
	}

	# read in records from a raw mysql resultset
	function recordsFromResultset($rs, $fields=NULL)
	{
		assert(is_resource($rs));
		while (false !== ($rec = mysql_fetch_assoc($rs)))
		{
			if (false === $this->recordFromArray($rec, $fields))
			{
				return false;
			}
		}
		return true;
	}

	# read resultset from $sql into my internal buffer, try to support our range of non-compatible db wrappers
	function recordsFromWrapper(&$sql, &$rs, $fields=NULL)
	{
		assert(is_object($sql));
		switch (strtoupper(get_class($sql))) {
		case "MYSQL_3":
			return $this->_recordsFromWrapperMySQL_3($sql, $rs, $fields);
			break;
		default:
			die(print_r(debug_backtrace(), 1) . "sorry, i don't support the '" . get_class($sql) . "' db wrapper, why don't you add the support?!");
			break;
		}
	}

	# output 
	function flush()
	{
		if (NULL === $this->_fp)
		{
			echo $this->_buf;
		}
		else
		{
			fwrite($this->_fp, $this->_buf);
		}
		$this->clearBuffer();
		return true;
	}

	function close()
	{
		return ($this->flush() && $this->reset());
	}

	/************* internal methods *****************/

	function _quote($val)
	{

		$val = trim($val);

		switch ($this->_quote_method)
		{
		case CSV_QUOTE_BACKWACK:
			$val = str_replace('"', '\"', $val);
			break;
		case CSV_QUOTE_DOUBLE:
		default:
			$val = str_replace('"', '""', $val);
			break;
		}

		if (
			$this->_force_quotes ||
			false !== strpos($val, ',') ||
			false !== strpos($val, '"') ||
			false !== strpos($val, "\n") ||
			false !== strpos($val, "\r")
		)
		{
			$val = '"' . $val . '"';
		}

		return $val;
	}

	function _quote_array(&$arr)
	{
		assert(is_array($arr));
		for ($i = 0; $i < count($arr); $i++)
		{
			$arr[$i] = $this->_quote($arr[$i]);
		}

		return true;
	}

	function _add_raw_record(&$arr)
	{
		if ($this->_quote_array($arr) && $this->_push_record($arr))
		{
			return count($arr);
		}
		else
		{
			return 0;
		}
	}

	function _set_title(&$arr)
	{
		$this->_unshift_record($arr);
		$this->_record_count--;
	}

	function _push_record(&$arr)
	{
		$this->_buf .= join(",", $arr) . $this->_nl;
		$this->_flush && $this->flush();
		$this->_record_count++;
		return true;
	}

	function _unshift_record(&$arr)
	{
		$this->_buf = join(",", $arr) . $this->_nl . $this->_buf;
		$this->_flush && $this->flush();
		$this->_record_count++;
		return true;
	}

	function __sleep()
	{
		$this->__pizza = "why would you serialize this object? don't you know that i'm loco?!?";
		return array_keys(array_merge(get_object_vars(&$this)));
	}


	function _recordsFromWrapperMySQL_3(&$sql, &$rs, $fields=NULL)
	{
		while (false !== ($rec = $sql->Fetch_Array_Row($rs)))
		{
			if (false === $this->recordFromArray($rec, $fields))
			{
				return false;
			}
		}
		return true;
	}

}

# don't do this on a production server, as any code that includes this file
# will run the example code and this will be very bad and you'll get canned
define("CSV_I_SWEAR_THIS_IS_A_DEV_BOX", false);

if (CSV_I_SWEAR_THIS_IS_A_DEV_BOX === true)
{

	# declare some dummy data
	$data = array(
		array(1,2,3,4,5)
		,array("a","b","c\"d","h,i","j")
		,array("","", "    trim me     ","","rtrim me    ")
		,array("new\nlines\nrule")
	);

	$csv = new CSV(
		# optionally pass key => val pair of args to constructor, perl-style, baby
		# saves you from doing 3 $csv->setXXX(); calls
		array(
			"FLUSH"		=> false	# do no automatically write each record to output... keep it all
									# keep it all stored internally... it's a juggle between speed and
									# memory usage
			,"QUOTES"	=> CSV_QUOTE_DOUBLE	# double-up quotes, that is the standard. this is default
			,"NEWLINES"	=> CSV_NL_UNIX # set unix newlines, this is by default
		)
	);

	# set field headers
	$csv->setTitles(array("field 1", "field2", "field 3", "field4", "field5"));
	# convert entire $data table to csv
	$csv->recordsFromArray($data);
	# convert just the first 3 fields of the first row
	$csv->recordFromArray($data[0], array(0,1,2));
	# flush internal buffers to output
	$csv->flush();

	# clear all settings, but keep the titles around
	$csv->reset(CSV_REMEMBER_TITLES);
	# turn autoflush on; write each record out as it is converted
	$csv->setFlush(true);
	# open a filehandle for writing
	$fp = fopen("/tmp/csv.output", "w") or die("Cannot open file for csv output!?!?");
	# tell csv to write to this file now instead of stdout
	$csv->setStream($fp);
	# convert first, 3rd and 5th fields of data to csv
	$csv->recordsFromArray($data, array(0,2,4));
	# close output stream
	fclose($fp);

}

?>
