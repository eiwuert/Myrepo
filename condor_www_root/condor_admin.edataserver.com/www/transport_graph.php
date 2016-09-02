<?php

/**
 * Actually generate the graph
 *
 */

	define('CACHE_DIR','/data/graph_cache/');
	$company_id = NULL;
	if($_GET['date'])
	{
		$start_timestamp = strtotime($_GET['date']);
		$start_date = date('Y-m-d', $start_timestamp);
		$end_date = date('Y-m-d', strtotime('+1 day', $start_timestamp));
	}
	else
	{
		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime('tomorrow'));
	}
	if(isset($_GET['company_id']))
	{
		$company_id = intval($_GET['company_id']);
	}
	if(isset($_GET['no_display']) && $_GET['no_display'])
	{
		$no_display = TRUE;
	}
	else 
	{
		$no_display = FALSE;
	}
	if(isset($_GET['no_cache']) && $_GET['no_cache'])
	{
		$caching = FALSE;
	}
	else 
	{
		$caching = TRUE;
	}
	if(isset($_GET['mode']))
	{
		$mode = $_GET['mode'];
	}
	else 
	{
		$mode = 'LIVE';
	}
	if(isset($_GET['plots']))
	{
		$plots = is_array($_GET['plots']) ? $_GET['plots'] : array($_GET['plots']);
	}
	else 
	{
		$plots = array();
	}
	if(isset($_GET['title']))
	{
		$title = $_GET['title'];
	}
	else 
	{
		$title = 'Transport Graph';
	}
	
	$file_name = CACHE_DIR.md5(serialize($plots)."_{$start_date}_{$end_date}_{$company_id}_".strtolower($mode)).".png";

	clearstatcache();
	if(file_exists($file_name) && $caching === TRUE)
	{
		if(date('Y-m-d') == $start_date)
		{
			if(filemtime($file_name) > (time() - 1800))
			{
				if($no_display === FALSE)
				{
					header('Content-Type: image/png');
					readfile($file_name);
				}
				exit;
			}
			else 
			{
				unlink($file_name);
			}
		}
		else 
		{
			if($no_display === FALSE)
			{
				header('Content-Type: image/png');
				readfile($file_name);
			}
			exit;
		}
	}
	require_once('/virtualhosts/condor_admin.edataserver.com/lib/transport_graph.php');
	
	/**
	 * Extend the JPGraph RGB stuff to add a few functions
	 * for validating/getting new colors
	 *
	 */
	class newRGB extends RGB
	{
		public function __construct()
		{
			parent::__construct();
		}
		/**
		 * Returns true if the color exists, false other wise
		 *
		 * @param unknown_type $str
		 * @return unknown
		 */
		public function ColorExists($str)
		{
			return isset($this->rgb_table[$str]);
		}
		/**
		 * Find a random color that is NOT in the
		 * array passed.
		 *
		 * @param array $not_in
		 * @return string
		 */
		public function Random($not_in = array())
		{
			$array_keys = array_keys($this->rgb_table);
			$cnt = count($array_keys) - 1;
			do 
			{
				$color = $array_keys[rand(0,$cnt)];
			}
			while(empty($color) && !in_array($color,$not_in));
			return $color;
		}
	}

	$graph = new Transport_Graph();
	$graph->setMode($mode);
	$graph->setTitle($title);
	$graph->setCompanyId($company_id);
	$graph->setStartDate($start_date);
	$graph->setEndDate($end_date);
	
	$rgb = new newRGB();
	$used_colors = array();
	foreach($plots as $plot)
	{
		$plot_data = explode(',',$plot);
		if(count($plot_data) == 4)
		{
			$color = $plot_data[2];	
			//if the color is random, or an invalid color, or previously used, change it.
			if(strcasecmp($color,'random') == 0 || !$rgb->ColorExists($color) ||
				 in_array($color,$used_colors))
			{
				$color = $rgb->Random($used_colors);
			}		
				 
			$used_colors[] = $color;
			$graph->Add_Plot($plot_data[0],$plot_data[1],$color,$plot_data[3]);
		}
	}
	$graph->Graph($file_name);	
	if($no_display === FALSE)
	{
		header('Content-Type: image/png');
		readfile($file_name);
	}
