<?php
	
	class Display_JPGraph_Line extends Display_JPGraph
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
				
				// choose a color
				$color = $this->Next_Color();
				
				$plot = new LinePlot($set['data']);
				$plot->SetColor($color);
				
				if ($this->display_legend)
				{
					$barplot->SetLegend($set['title']);
				}
				
				$this->graph->Add($plot);
				unset($plot);
				
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
		
		protected function Adjust_Scale($scale)
		{
			
			array_pop($scale);
			return $scale;
			
		}
		
	}
	
?>