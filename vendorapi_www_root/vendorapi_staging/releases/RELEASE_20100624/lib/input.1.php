<?php

/*
	written by pizza
*/

require_once("diag.1.php");

Diag::Disable();

class Input
{
	var $src;

	# src is an array such as $_GET, $_POST or $_REQUEST
	function Input($src)
	{
		$this->src = $src;
		#Diag::Dump($this->src, "src");
	}
	# handle "var[a][b]
	function _SplitName($name)
	{
		if (strpos($name, '[') === false)
		{
			return array($name);
		}
		else
		{
			#Diag::Out("name:$name:");
			# assumes healthy "a[b][c]"
			$names = array_slice(preg_split('/(\]\[?|\[)/', $name), 0, -1);
			return $names;
		}
	}
	function _ResolveName($name)
	{
		$curr = $this->src;
		$names = $this->_SplitName($name);
		#Diag::Dump($names, "name array");
		reset($names);
		# search tree, like $POST["a"]["b"]["c"] from "a[b][c]"
		while (list($k,$v) = each($names))
		{
			if (isset($curr[$v]))
			{
				#Diag::Out("curr[$v]...");
				$curr = $curr[$v];
			}
			else
			{
				#Diag::Out("$v doesn't match anything in: ");
				#Diag::Dump($curr);
			}
		}
		#Diag::Out("curr: $curr!");
		if ($curr !== $this->src)
			return $curr;
		else
			return false;
	}
	function Display($attr, $ret=false)
	{
		assert(is_array($attr));
		assert(isset($attr["name"]));

		# set defaults
		$val = $this->_ResolveName($attr["name"]);
		$attr["type"] = (isset($attr["type"]) ? strtolower($attr["type"]) : "text");
		$attr["name"] = (isset($attr["name"]) ? $attr["name"] : $name);
		$attr["id"] = (isset($attr["id"]) ? $attr["id"] : $attr["name"]);
		$attr["value"] = isset($attr["value"]) ? $attr["value"] : $val;
		# type-specific code
		switch ($attr["type"])
		{
		case "text":
			if (!isset($attr["size"]))
				$attr["size"] = 20;
			if (!isset($attr["maxlength"]))
				$attr["maxlength"] = 255;
			break;
		case "checkbox":
			if ($val == $attr["value"])
				$attr["checked"] = "checked";
			break;
		case "radio":
			if ($this->src[$attr["name"]] == $attr["value"])
				$attr["selected"] = "selected";
			break;
		case "textarea":
			$out = "<textarea";
			break;
		}

		if (!isset($out))
			$out = "<input";

		reset($attr);
		while (list($k,$v) = each($attr))
			$out .= " $k=\"" . htmlspecialchars($v) . "\"";

		if ($attr["type"] == "textarea")
			$out .= "></textarea>";
		else
			$out .= " />";

		if ($ret)
			return $out;
		else
			echo $out;
	}
}

?>
