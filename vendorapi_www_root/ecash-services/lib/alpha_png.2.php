<?php
/*
	@version
			2.0.0 2005-09-19 - David Bryant

	Updates:
	-09/19	Modified original version to fix problem with images stretching.  Did not want to break
	        anything already using this object.
*/

if (!defined ("IE_BROWSER"))
{
	define ("IE_BROWSER", strpos ($_SERVER["HTTP_USER_AGENT"], "MSIE"));
}

class alpha_png
{
	var $ie;
	var $img_dir;
	var $img;

	/**
	* @return bool
	* @param $image_dir string
	* @desc Constructor to initialize the object
	*/
	function alpha_png ($image_dir)
	{
		$this->ie = IE_BROWSER; //strpos ($_SERVER["HTTP_USER_AGENT"], "MSIE") ? true : false;
		$this->img_dir = $image_dir;
		return true;
	}

	/**
	* @return bool or string
	* @param $png_file string
	* @param $string_flag
	* @desc Echoes the browser-specific string required to display the transparent png file, or, optionally returns the string.
	*/
	function img ($png_file, $string_flag=false, $size_str=false)
	{
		if ($size_str)
		{
			$img_info = $size_str;
		}
		else
		{
			$img_info_arr = getImageSize ($this->img_dir."/".$png_file);
			$img_info = $img_info_arr[3];
		}
		if ($this->ie)
		{
			// IMPORTANT: for this to work in IE, the image directory MUST contain a transparent
			// GIF file called "spacer.gif".
			$this->img = "<img src=\"".$this->img_dir."/spacer.gif\" ".$img_info." style=\"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$this->img_dir."/".$png_file."',sizingMethod='image');\" alt=\"\" />";
		}
		else
		{
			$this->img = "<img src=\"".$this->img_dir."/".$png_file."\" ".$img_info." />";
		}
		if ($string_flag)
		{
			return $this->img;
		}
		else
		{
			echo $this->img;
		}
	}
}
?>