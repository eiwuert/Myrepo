<?php

/*
 * This library is for creating a CAPTCHA (Completely Automated Public
 * Turing Test to Tell Computers and Humans Apart) using JpGraph's
 * antispam challenge library.  Requires JpGraph and GD.
 */

require_once("jpgraph/jpgraph_antispam.php");

class Captcha_1
{
	var $captcha;
	var $display_string;
	
	function Captcha_1($display_string = NULL)
	{
		$this->captcha = new AntiSpam();
		//don't allow custom strings if the "Set" method doesn't exist
		//it seemed to have disappeared between JpGraph 1.20.2 and 2.0
		if($display_string == NULL || !method_exists($this->captcha, 'Set'))
		{
			$this->display_string = $this->captcha->Rand(5);
		}
		else
		{
			$this->display_string = $display_string;
			$this->captcha->Set($display_string);
		}
	}

	function Display()
	{
		//this will return FALSE if there is illegal or no data to plot
		return $this->captcha->Stroke();
	}

	function Get_String()
	{
		//get the string that is being displayed
		return $this->display_string;
	}
}

?>