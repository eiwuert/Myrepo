<?php
	
	class Display_JPGraph_Bar_Group extends Display_JPGraph
	{
		
		public function __construct()
		{
		}
		
		public function Render(iReport $report, $file = NULL)
		{
			
			parent::Render($report);
			
			$data_sets = $report->Data();
			
			$max_value = 0;
			
			foreach ($data_sets as $set)
			{
				
				$barplot = new BarPlot($set['data']);
				
				/*
					HACK ALERT!!! I need to specify a minimum maximum value
					for the y-axis scale.
				*/
				foreach($set['data'] as $value)
				{
					if($max_value < $value)
					{
						$max_value = $value;
					}
				}
				
				// choose a color
				$color = $this->Next_Color();
				
				$barplot->SetColor($color);
				
				if($this->use_gradient)
				{
					$barplot->SetFillGradient($color.'@.9', $color.'@.5', GRAD_VER);
				}
				else
				{
					$barplot->SetFillColor($color.'@.7');
				}

				$barplot->SetWidth(.75);
				
				if ($this->display_legend)
				{
					$barplot->SetLegend($set['title']);
				}
				
				$plots[] = &$barplot;
				unset($barplot);
				
			}
				
			if($max_value < 4)
			{
				$this->graph->yaxis->scale->SetAutoMax(4);
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