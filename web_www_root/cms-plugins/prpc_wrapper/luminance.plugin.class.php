<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/
require_once (INCLUDES_PATH.'prpc/client2.php');
class LuminancePluginprpc_wrapper
{
	private $lumRegistry;
	private $string_table = array();
	private $custom_string_table = array();
	private $model;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
	}

	public function getPermissionTypes()
	{
		return array(
		);
	}
}


