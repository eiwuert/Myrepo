<?php
/**
 * Class to handle building graphs transport
 * statistics. 
 *
 */
require_once('reported_exception.1.php');
require_once('/virtualhosts/lib/jpgraph/jpgraph.php');
require_once('/virtualhosts/lib/jpgraph/jpgraph_bar.php');


Reported_Exception::Add_Recipient('email','stephan.soileau@sellingsource.com');

class Transport_Graph
{

	private $sql;
	private $hours_to_graph;
	private $company_id;
	private $start_date;
	private $end_date;
	private $max_value;
	private $plots;
	private $title;
	private $graph;
	private $mode;
	
	
	public function __construct($mode = NULL)
	{
		$this->company_id = NULL;
		$this->setStartDate(date('Y-m-d 00:00:00'));
		$this->setEndDate(date('Y-m-d 00:00:00',strtotime('tomorrow')));
		$this->title = "Transport Graph";
		$this->plots = array();
		$this->graph = NULL;
		if($mode == NULL && defined('EXECUTION_MODE'))
		{
			$this->mode = $mode;
		}
		elseif($mode != NULL)
		{
			$this->mode = $mode;
		}
	}
		
	public function setMode($mode)
	{
		$this->mode = $mode;
	}
	
	/**
	 * Set the company id to use while building this graph
	 *
	 * @param int $id
	 */
	public function setCompanyId($id)
	{
		$this->company_id = $id;
	}
	
	/**
	 * Actually renders the graph and optionally write 
	 * it to file.
	 *
	 * @param file to write the thing to $file
	 */
	public function Graph($file = NULL)
	{
		$this->_setupGraph();
		$p_size = 1 / count($this->plots);
		foreach($this->plots as $plot)
		{
			$plot->SetWidth($p_size);
		}
		$group_plot = new GroupBarPlot($this->plots);
		$this->graph->Add($group_plot);
		if($file == NULL)
		{
			header('Content-Type: image/png');
			$this->graph->Stroke();
		}
		else
		{
			$this->graph->Stroke($file);
		}
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $transport
	 * @param string $status_type
	 * @param string $color
	 * @param string $legend
	 */
	public function Add_Plot($transport,$status_type,$color,$legend)
	{
		$data = $this->_gatherStats($transport,$status_type);
		if(is_array($data))
		{
			$plot = new BarPlot($data);
			$plot->SetLegend($legend);
			$plot->SetFillGradient($color.'@.9',$color.'@.2',GRAD_VER);
			$plot->SetColor('black','navy');
			$plot->SetWidth('1');
			$this->plots[] = $plot;
		}
	}
	
	/**
	 * Set the SQL object to an already 
	 * existing database instead of 
	 *
	 * @param MySQLi_1 $sql
	 */
	public function setSql(MySQLi_1 $sql)
	{
		$this->sql = $sql;
	}
	
	/**
	 * Set the startDate and recalculate the 
	 * number of hours we're graphing.
	 * 
	 * @param date $start_date
	 */
	public function setStartDate($start_date)
	{
		$start_time = strtotime($start_date);
		if(is_numeric($start_time))
		{
			$this->start_date = $start_date;
			$this->_calculateHoursToGraph();
			
		}
	}
	
	/**
	 * Assigns a title to this graph.
	 *
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * Set the endDate and recalculate the number of hours we're graphing
	 *
	 * @param date $end_date
	 */
	public function setEndDate($end_date)
	{
		$end_time = strtotime($end_date);
		if(is_numeric($end_time))
		{
			$this->end_date = $end_date;
			$this->_calculateHoursToGraph();
		}
	}
	
	/**
	 * Calculates the hours between the start/end date of the graph
	 *
	 */
	private function _calculateHoursToGraph()
	{
		$start_time = strtotime($this->start_date);
		$end_time = strtotime($this->end_date);
		if(is_numeric($end_time) && is_numeric($start_time))
		{
			$elapsed = $end_time - $start_time;
			if($elapsed > 0)
			{
				$hours = $elapsed / 3600;
				$this->hours_to_graph = $hours;
			}
		}
	}
	
	/**
	 * Returns an hour by hour break down of the documents
	 * sent via $transport that have a status with status_type
	 * $status_type.
	 *
	 * @param string $transport
	 * @param string $status_type
	 */
	private function _gatherStats($transport,$status_type)
	{
		$return = array();
		
		//If any of this is not numeric, it's not set
		//and we really can't proceed
		if(!is_numeric(strtotime($this->start_date)) || 
			!is_numeric(strtotime($this->end_date)) ||
			!is_numeric($this->hours_to_graph))
			{
				return FALSE;
			}
		
		//Initialize all hours to 0 before we
		//even really get started here.
		for($i = 0; $i < $this->hours_to_graph; $i++)
		{
			//if you set it to zero jpgraph will skip it
			//this will cause it to still graph as 0, but not 
			//skip the hour
			$return[$i] = 0.001;
		}
		$query = $this->_buildStatQuery($transport,$status_type);
		try 
		{
			$this->_dbConnect();
			$res = $this->sql->Query($query);
			//Seems weird but makes sure that it's the beginning of the day 
				$first_hour = strtotime(date('Y-m-d H:00:00',strtotime($this->start_date)));
			while($row = $res->Fetch_Object_Row())
			{
				$hour = strtotime($row->date_created);
							
				$key = ($hour - $first_hour) / 3600;
				if($row->cnt > $this->max_value)
				{
					$this->max_value = $row->cnt;
				}
				$return[$key] = $row->cnt;
			}
			return $return;
			
		}
		catch (Exception $e)
		{
			Reported_Exception::Report($e);
			return FALSE;
		}
		
	}
	
	/**
	 * Returns an array of status_ids for a particular
	 * status_type
	 *
	 * @param string $status_type
	 * @return array
	 */
	private function _getStatusIds($status_type)
	{
		$this->_dbConnect();
		$s_type = $this->sql->Escape_String($status_type);
		$return = array();
		$query = "
			SELECT
				dispatch_status_id 
			FROM
				dispatch_status
			WHERE
				type = '$s_type'
		";
		try 
		{
			$res = $this->sql->Query($query);
			while($row = $res->Fetch_Object_Row())
			{
				$return[] = $row->dispatch_status_id;
			}
			return $return;
		}
		catch (Exception $e)
		{
			Reported_Exception::Report($e);
			return FALSE;
		}
	}
	
	/**
	 * Take the transport and type and the other various 
	 * information that is set in the graph and use it
	 * build a query that will gather all the stats
	 *
	 * @param string $transport
	 * @param string $status_type
	 * @return string
	 */
	private function _buildStatQuery($transport,$status_type)
	{
		$this->_dbConnect();
		$s_transport = $this->sql->Escape_String($transport);
		$status_ids = $this->_getStatusIds($status_type);
		$query = "
			SELECT
				count(ds.document_id) as cnt,
				date_format(ds.date_created, '%Y-%m-%d %H:00:00') as date_created
			FROM
				document_dispatch ds
			JOIN 
				dispatch_history dh ON (ds.dispatch_history_id = dh.dispatch_history_id)
		";
		if(is_numeric($this->company_id))
		{
			$query = "
			$query
			JOIN 
				document doc ON (ds.document_id = doc.document_id)
			JOIN 
				condor_admin.agent ag ON (doc.user_id = ag.agent_id)
			";
					
		}
		$query .= "
			WHERE
				ds.date_created BETWEEN '{$this->start_date}' AND '{$this->end_date}'
			AND
				ds.transport = '$s_transport'";
		if(count($status_ids) > 0)
		{
			
			$query .= '
			AND
				dh.dispatch_status_id IN ('.join(',',$status_ids).')
			';
		}
		if(is_numeric($this->company_id))
		{
			$query .= "
				AND
					ag.company_id = {$this->company_id}
			";
		}
		$query .= ' GROUP BY date_format(ds.date_created,"%Y-%m-%d %H")';
		return $query;
	}
	
	/**
	 * Connect to the database as necessary.
	 *
	 */
	private function _dbConnect()
	{
		//if we're already connected to a database
		//don't reconnect as that'd be dumb
		list($host,$user,$pass,$db,$port) = $this->_getDbInfo($this->mode);
		if(!$this->sql instanceof MySQLi_1)
		{
			if(!class_exists('MySQLi_1'))
				require_once('mysqli.1.php');
			$this->sql = new MySQLi_1(
				$host,
				$user,
				$pass,
				$db,
				$port
			);
		}
	}
	
	/**
	 * Returns an array containing database 
	 * credentials for a particular mod
	 *
	 * @param string $mode
	 * @return array
	 */
	private function _getDbInfo($mode)
	{
		$mode = strtoupper($mode);
		switch ($mode)
		{
			case "RC": // The rc server, for cron/command line testing use
				return array(
					'db101.ept.tss',
					'condor',
					'andean',
					'condor',
					3313
				);
			case "LIVE":
				return array(
					'reader.condor2.ept.tss',
					'condor',
					'flyaway',
					'condor',
					3308
				);
			case "LOCAL":
			default:
				return array(
					'monster.tss',
					'condor',
					'flyaway',
					'condor',
					3311
				);
		}
	}
		
	/**
	 * Build the graph object and make it neato
	 *
	 */
	private function _setupGraph()
	{
		$time = strtotime($this->start_date);
		for($i = 0;$i < $this->hours_to_graph;$i++)
		{
			$labels[$i] = date('ha', ($time + (($i) * 3600)));
		}
		$graph = new Graph(800, 300,"auto");
		$graph->SetMargin(30, 30, 40, 30);
		$graph->SetScale('textint', 0, $this->max_value);
		$graph->xgrid->SetColor('#000000@.8', '#222222@.8');
		$graph->ygrid->SetColor('#ffffff', '#eeeeee'); //#222222@.8
		$graph->xgrid->Show(TRUE, TRUE);
		$graph->xaxis->SetColor('#222222', '#808080');
		$graph->yaxis->SetColor('#222222', '#808080');
		$graph->ygrid->Show(TRUE, FALSE);
		$graph->SetMarginColor('#ffffff');
		$graph->SetBox(TRUE, '#c0c0c0', 3);
		$graph->SetFrame(TRUE, '#ffffff', 1);
		$graph->SetColor('#eeeeee');

		$graph->xaxis->SetTickLabels($labels);
		$graph->xaxis->SetFont(FF_ARIAL, FS_NORMAL, 8);
		$graph->yaxis->SetFont(FF_ARIAL, FS_NORMAL, 8);
		$graph->yaxis->HideFirstTickLabel();
		$graph->yaxis->scale->SetGrace(20);
		$graph->xaxis->scale->ticks->Set(1 , 1);
		if($this->max_value < 15)
		{
			$graph->yaxis->scale->SetAutoMax(15);
		}

		$text = new Text($this->title);
		$text->SetColor('#999999');
		$text->SetFont(FF_ARIAL, FS_NORMAL, 20);
		$text->SetPos(((800 - $text->GetWidth($graph->img)) / 2), 0);
		$text->Show();
		$graph->AddText($text);
		
		$text = new Text('Generated At '.date('Y-m-d H:i:s'));
		$text->SetColor('#000000@.4');
		$text->SetFont(FF_ARIAL, FS_ITALIC,8);
		$text->SetPos(10,300 - 9);
		$text->Show();
		$graph->AddText($text);
		
		$this->graph = $graph;

	}
	
}