<?php

// written by pizza

// baseclass for all dropdown.n.whatever.php classes, use them instead, or write your own
// class to extend Dropdown... basically you just need to play with $this->key_vals in your
// class, and then just call Dropdown's display() method and it'll do all the dirty work

class Dropdown
{

	// NOTE: do not set these manually, use the class methods, you insensitive clod!
	var $name;			// 
	var $unselected;	// 
	var $attrs;			// custom attributes for the select
	var $key_vals;		// key->value pairs
	var $key_vals_callback;		// function for extracting display value from k->v pair array
	var $selected;		// all selected values
	var $xhtml;			// should we generate verbose, legal xhtml or 1/2 as long regularl old html?
	var $select_tags;	// whether to keep or omit <select></select> tags... only provide <option>s
	var $base_init = true;

	function Dropdown($opts=NULL)
	{

		# set default initial settings
		$this->_init();

		// set default values
		if (NULL === $opts)
		{
			return;
		}

		assert(is_array($opts));

		reset($opts);
		while (list($k,$v) = each($opts))
		{
			switch (strtoupper($k))
			{
			case "NAME":
				$this->setName($v);
				break;
			case "SIZE":
				$this->setSize($v);
				break;
			case "SELECTED":
				$this->setSelected($v);
				break;
			case "UNSELECTED":
				$this->setUnselected($v);
				break;
			case "MULTIPLE":
				$this->setMultiple($v);
				break;
			case "ATTRIBUTES":
			case "ATTRS":
				$this->setAttr($v);
				break;
			case "KEYVALS":
			case "KEY_VALS":
				$this->setKeyVals($v);
				break;
			case "SELECT_TAGS":
			case "TAGS":
				$this->setSelectTags($v);
				break;
			case "XHTML":
				$this->setXHTML($v);
				break;
			default:
				echo "Dropdown: unrecognized option '$k'...<br />\n";
				break;
			}
		}

	}

	function _init()
	{
		if (false === $this->base_init)
		{
			return;
		}
		$this->base_init = false;
		$this->name = "dropdown";
		$this->unselected = "(Select One)";
		$this->attrs = array("size" => 1);
		$this->key_vals_callback = NULL;
		$this->selected = array();
		$this->xhtml = true;
		$this->select_tags = true;
		$this->reset();
	}

	function reset()
	{
		$this->key_vals = array();
	}

	function setXHTML($bool)
	{
		$this->xhtml = (bool)$bool;
	}

	function setSelectTags($bool)
	{
		$this->select_tags = (bool)$bool;
	}

	function setName($name)
	{
		$this->name = $name;
	}

	function setSize($size)
	{
		$this->attrs["size"] = intval($size);
	}

	function setMultiple($bool)
	{
		if (true == (bool)$bool)
		{
			$this->attrs["multiple"] = "";
		}
		else
		{
			unset($this->attrs["multiple"]);
		}
	}

	// set custom attributes for this select
	function setAttr($attrs)
	{
		assert(is_array($attrs));
		$this->attrs = $attrs;
	}

	// the first, non-value, like "(Select One)"
	function setUnselected($key)
	{
		if (false === $key)
		{
			unset($this->unselected);
		}
		else
		{
			$this->unselected = $key;
		}
	}

	// set key->val pairs
	function setKeyVals($key_vals)
	{
		assert(is_array($key_vals));
		$this->key_vals = $key_vals;
	}

	function setKeyValsCallback($callback)
	{
		assert(function_exists($callback));
		$this->key_vals_callback = $callback;
	}

	// selected can be an array of values ot a single scalar value
	function setSelected($selected)
	{
		if (is_array($selected))
		{
			$this->selected = $selected;
		}
		else
		{
			$this->selected = array($selected);
		}
	}

	// if return=1 it the text will be returned, otherwise it will be output directly
	function display($return=false)
	{

		$out = "";

		if ($this->select_tags)
		{

			$out = "<select name=\"" . $this->name . "\"";
	
			// add attributes
			if (count($this->attrs) > 0)
			{
				reset($this->attrs);
				while (list($k, $v) = each($this->attrs))
				{
					$out .= " " . $k;
					if ($v)
					{
						$out .= "=\"" . htmlentities($v) . "\"";
					}
					else if ($this->xhtml)
					{
						// no need to htmlentities(), since k shouldn't need it since it's a legal html attribute
						$out .= "=\"" . $k . "\"";
					}
				}
			}
	
			// initial tag done
			$out .= ">";
		}

		// create the default "unselected" entry, possibly containing some witty remark
		if (isset($this->unselected))
		{
			$out .= "\n<option value=\"\">" . htmlentities($this->unselected);
			if ($this->xhtml)
			{
				$out .= "</option>";
			}
		}
		
		// generate values
		if (count($this->key_vals) > 0)
		{
			reset($this->key_vals);
			while (list($k, $v) = each($this->key_vals))
			{
				#echo "cb:$cb:\n";
				$val = ($this->key_vals_callback === NULL ? $v : call_user_func($this->key_vals_callback, $v));
				$out .= "\n<option value=\"" . htmlentities($k) . "\"";
				if (in_array($k, $this->selected))
				{
					$out .= " selected" . ($this->xhtml ? "=\"selected\"" : "");
				}
				$out .= ">" . htmlentities($val);
				if ($this->xhtml)
				{
					$out .= "</option>";
				}
			}
		}

		if ($this->select_tags)
		{
			// closing tag
			$out .= "\n</select>\n";
		}

		if ($return)
		{
			return $out;
		}
		else
		{
			echo $out;
		}
		
	}

}

?>
