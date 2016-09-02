<?php

// written by pizza
// meant to display a dropdown list of files from a local directory

require_once("dropdown.1.php");

class Dropdown_Files extends Dropdown
{

	var $path;
	var $fullpath;
	var $showdotfiles;
	var $format;

	function Dropdown_Files($path=".")
	{
		$this->path = $this->fullpath = $this->format = "";
		$this->showdotfiles = false;
		parent::Dropdown();
		$this->SetPath($path);
	}

	function setPath($path)
	{
		assert(file_exists($path) && is_dir($path));
		$this->fullpath = realpath($path);
		$this->path = $path;
	}

	function showDotFiles($bool)
	{
		$this->showdotfiles = (bool)$bool;
	}

	# actually load the files before we display... happens automagically
	function _loadFiles()
	{
		if (false == ($dir = opendir($this->fullpath)))
		{
			if (!file_exists($this->fullpath))
			{
				die("path does not exist");
			}
			else if (!is_dir($this->fullpath))
			{
				die("path is not a directory");
			}
			else if (!is_readable($this->fullpath))
			{
				die("path is not readable");
			}
		}
		while (false !== ($filename = readdir($dir)))
		{
			if (("." == $filename || ".." == $filename) && false == $this->showdotfiles)
			{
					continue;
			}
			$this->key_vals[$filename] = $filename;
		}
		closedir($dir);

		#FIXME: do a default alpha sort on file list for now... make this configurable...
		ksort($this->key_vals);
	}

	function display($return=false)
	{
		$this->_loadfiles();
		return parent::display($return);
	}

}

?>
