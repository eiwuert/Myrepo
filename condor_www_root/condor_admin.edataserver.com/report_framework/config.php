<?php
	
	define('BASE_DIR', dirname(__FILE__));
	
	// helper stuff
	include(BASE_DIR.'/include/date_functions.php');
	
	// reporting classes
	include(BASE_DIR.'/include/interfaces.php');
	include(BASE_DIR.'/include/exceptions.php');
	
	/*
	include(BASE_DIR.'/include/source.interval.mysql.php');
	include(BASE_DIR.'/include/source.interval.cached.php');
	include(BASE_DIR.'/include/source.formula.php');
	include(BASE_DIR.'/include/source.cumulative.php');
	include(BASE_DIR.'/include/source.percent.php');
	include(BASE_DIR.'/include/source.smooth.php');
	include(BASE_DIR.'/include/source.ratio.php');
	include(BASE_DIR.'/include/range.interval.php');
	include(BASE_DIR.'/include/range.interval.average.php');
	include(BASE_DIR.'/include/report.interval.php');
	
	// displays
	include(BASE_DIR.'/include/display.jpgraph.php');
	include(BASE_DIR.'/include/display.jpgraph.bar.php');
	include(BASE_DIR.'/include/display.jpgraph.bar_group.php');
	include(BASE_DIR.'/include/display.jpgraph.bar_line.php');
	include(BASE_DIR.'/include/display.jpgraph.line.php');
	include(BASE_DIR.'/include/display.html.table.php');
	*/
	
	// jpgraph libraries
	require_once('/virtualhosts/lib/jpgraph/jpgraph.php');
	require_once('/virtualhosts/lib/jpgraph/jpgraph_line.php');
	require_once('/virtualhosts/lib/jpgraph/jpgraph_bar.php');
	
	function __autoload($class)
	{
		
		$last = strrpos($class, '_');
		
		if ($last !== FALSE)
		{
			$file = implode('.', array_reverse(explode('_', $class)));
		}
		else
		{
			$file = $class;
		}
		
		// try to include it
		$file = BASE_DIR.'/include/'.strtolower(str_replace('_', '.', $file)).'.php';
		require($file);
		
		return;
		
	}
	
	function Get_Now()
	{
		
		$sql = &MySQL_Pool::Connect('writer.olp.ept.tss', 'sellingsource', 'password', 'olp');
		
		$query = "SELECT UNIX_TIMESTAMP(NOW()) AS now";
		$result = $sql->Query($query);
		
		$now = (($rec = $result->Next()) ? $rec['now'] : time());
		return $now;
		
	}
	
	function Load_Source($name)
	{
		
		$file = BASE_DIR.'/sources/'.strtolower($name).'.php';
		
		$source = include($file);
		if (!is_object($source)) $source = FALSE;
		
		return $source;
		
	}
	
?>
