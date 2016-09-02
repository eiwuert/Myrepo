<?php
	
	class Display_JPGraph_Bar_Line extends Display_JPGraph
	{
		
		public function __construct()
		{
		}
		
		public function Render(iReport $report, $file = NULL)
		{
			
			parent::Render($report);
			
			$data_sets = $report->Data();
			
			foreach ($data_sets as $set)
			{
				
				$barplot = new BarPlot($set['data']);
				
				// choose a color
				$color = $this->Next_Color();
				
				$barplot->SetColor($color);
				$barplot->SetFillColor($color.'@.9');
				$barplot->SetWidth(.75);
				
				if ($this->display_legend)
				{
					$barplot->SetLegend($set['title']);
				}
				
				$plots[] = &$barplot;
				unset($barplot);
				
				$plot = new LinePlot($set['data']);
				$plot->SetColor($color);
				$this->graph->Add($plot);
				unset($plot);
				
			}
			
			$plot = new GroupBarPlot($plots);
			$this->graph->Add($plot);
			
			if ($file !== NULL)
			{
				$this->graph->Stroke($file);
			}
			else
			{
				$this->graph->Stroke();
			}
			
			return;
			
		}
		
	}
	
?>