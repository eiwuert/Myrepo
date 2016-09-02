<?php
	
	class Display_JPGraph_Bar extends Display_JPGraph
	{
		
		protected $use_gradient = false;
		
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
				
				// Display the bargraphs with gradients or as a solid color
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
				
				$this->graph->Add($barplot);
				unset($barplot);
				
			}
			
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