<?php

class xml_parser
{
	var $xml_data;
	var $current_container = array ();		// stack for container tags
	var $parser;
	var $disp;								// junk debug variable
	var $data;

	function xml_parser ($filename, $data=null)
	{
		// generic holder for data
		$this->data = $data;

		// load the xml data
		$this->xml_data = file_get_contents ($filename);

		// parse the data and kill the parser
		$this->initiate_parser ();
	}

	function initiate_parser ()
	{
		$this->parser = xml_parser_create ();
		xml_parser_set_option ($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object ($this->parser, &$this);
		xml_set_element_handler ($this->parser, array (&$this, "start_tag"), array (&$this, "end_tag"));
		xml_set_character_data_handler ($this->parser, array (&$this, "char_data"));
		xml_parse ($this->parser, $this->xml_data);
		xml_parser_free ($this->parser);
		unset ($this->xml_data);
	}

	function start_tag ($parser, $name, $attributes)
	{

	}

	function end_tag ($parser, $name)
	{

	}

	function char_data ($parser, $data)
	{

	}

	// This is generally not used except for debugging
	function display ()
	{
		echo $this->disp;
	}

}


?>
