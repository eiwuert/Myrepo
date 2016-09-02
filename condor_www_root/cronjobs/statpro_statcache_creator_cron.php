<?php
	class Statpro_Cache
	{
		var $statpro_version;
		var $statpro_stats;
		var $space_key_array;
		var $enterprisepro_event;
		var $date_occured;
		var $error_msg;		
		var $row_count;
		
		function Statpro_Cache()
		{

		}
		
		function init()
		{
			$this->statpro_version = 2;
			$this->statpro_stats = array();
			$this->space_key_array = array();
			$this->enterprisepro_event = array();			
			$this->date_occured  = ($this->statpro_version ==1)? "date_created" : "date_occured";
			$this->row_count = 0;			
		}
		
		function Error($msg)
		{
			echo $msg."\n";
			$this->error_msg=$msg;
			return false;
		}
		
		function FetchStatProStats($current_date)
		{
			$this->init();
			//Create Database Connection to Statpro
			mysql_connect("db1.epointps.net:13307", "root", "llama");
			mysql_select_db("clk_statpro_data");
						
			$today = strtotime($current_date);
			$event_log_table = ($this->statpro_version == 1)? "event_log" : "event_log";
			$date_range = ($this->statpro_version == 1)? "'".date("Y-m-d 00:00:00", $today)."' AND '".date("Y-m-d 23:59:59", $today)."' " : strtotime(date("Ymd", $today))." AND ".(strtotime(date("Ymd 23:59:59", $today)))." ";
					
			$query = "SELECT '{$current_date}' as date_occured, space.space_key, event_type.event_type_key, COUNT(*) as total_count
						FROM 
							{$event_log_table}
						JOIN 
							event_type ON event_type.event_type_id = {$event_log_table}.event_type_id
						JOIN 
							space ON space.space_id = {$event_log_table}.space_id 
						WHERE 
							{$event_log_table}.{$this->date_occured} between {$date_range}
						GROUP BY 
							{$event_log_table}.space_id, {$event_log_table}.event_type_id;";
			$result = mysql_query($query);
		
			if (!$result) {
				return $this->Error(mysql_error()."\nDatabase query ERROR: $query\n");
			}

			while ($row = mysql_fetch_assoc($result)) 
			{
				$this->statpro_stats[] = $row;
				$this->space_key_array[] = $row['space_key'];
			}
			return true;
		}
		
		function FetchEnterpriseProInfo()
		{			
			mysql_connect("db1.epointps.net:13306", "root", "llama");
			mysql_select_db("enterprisepro");
			$query = "
					SELECT 
						space_key, page_id, promo_id, promo_sub_code 
					FROM 
						space_definition 
					WHERE 
						space_key in ('".implode("', '", $this->space_key_array)."')";
			
			$result = mysql_query($query);
			if (!$result) {
				return $this->Error(mysql_error()."\nDatabase query ERROR: $query\n");
			}		

			while ($row = mysql_fetch_assoc($result)) 
			{
				$this->enterprisepro_event[$row['space_key']] = $row;
			//	$demo[$row['page_id']][] = $row['space_id'];
			}
/*
			ksort($demo);			
			foreach ($demo as $key=>$list)
				echo "<br>".$key." = '".implode("', '",$list)."'<br>\n";*/
			return true;
		}
		
		function UpdateStatProCache($current_date)
		{	
//			mysql_connect("localhost", "root", "");
			mysql_connect("nightwing.tss", "jasons", "webadmin123");
			mysql_select_db("statpro_cache");		

			foreach ($this->statpro_stats as $stat)
			{
				//This converts Statpro stats back to regular named stats, otherwise webadmin1 stats would not work correctly				
				if ($stat['event_type_key']=="visitor") $stat['event_type_key']="visitors";
				if ($stat['event_type_key']=="prequal") $stat['event_type_key']="base";
				if ($stat['event_type_key']=="nms_prequal") $stat['event_type_key']="post";
				if ($stat['event_type_key']=="submit") $stat['event_type_key']="income";
				if ($stat['event_type_key']=="agree") $stat['event_type_key']="accepted";
						
				$epe = $this->enterprisepro_event[ $stat['space_key'] ];
				$year = date("Y",strtotime($current_date));
				$table = ($this->statpro_version ==1)? "v1cache{$year}" : "cache{$year}";
				$query = "
						REPLACE INTO 
							{$table} 
							(stat_date, page_id, promo_id, promo_sub_code, event, value) 
						VALUES 
							('{$current_date}', '{$epe['page_id']}', '{$epe['promo_id']}', '{$epe['promo_sub_code']}', '{$stat['event_type_key']}', '{$stat['total_count']}' ) ";
				$result = mysql_query($query);
				if (!$result) 
				{
					return $this->Error(mysql_error()."\nDatabase query ERROR: $query\n");
				}		
				$this->row_count++;	
			}
			return true;		
		}
		
		function FetchRowCount()
		{
			$count = $this->row_count;
			$this->row_count=0;
			return $count;
		}
		
		function runCacheUpdate($current_date)
		{
			if ($this->FetchStatProStats($current_date))
				if($this->FetchEnterpriseProInfo())
					if($this->UpdateStatProCache($current_date)){
						return true;
					}
			return false;
		}
		
		function updateRange($start_date=null, $stop_date=null)
		{			
			$unix_time = (!is_null($start_date))? strtotime($start_date." 00:00:00") : strtotime(date("Y-m-d 00:00:00"));
			$unix_stop_time = (!is_null($stop_date))? strtotime($stop_date." 23:59:59") : strtotime(date("Y-m-d 23:59:59"));
			while ($unix_time < $unix_stop_time) 
			{
				$start_time = time();			
				echo date("m/d/Y", $unix_time)."  ";
				if($this->runCacheUpdate(date("Y-m-d",$unix_time)))
				{						
					$unix_time += (24*3600);
					echo $this->FetchRowCount()."\n";
					echo "Total time ".(time()-$start_time)." seconds\n";
				//	print_r($this->statpro_stats);
				} else 
					return false;
			}
			return true;
		}
			
			
	}
	$test = new Statpro_Cache();
	$test->updateRange();
	//$test->updateRange("2005-08-30", "2005-08-30");
							
ta?>