<?php
	
	/**
	 *
	 * Provides base JPGraph functionality: i.e., creating the Graph
	 * object, setting margins, etc.
	 *
	 */
	class Display_JPGraph implements iDisplay
	{
		
		protected $default_colors = array(
			'red', '#9bbeffff', '#77bb11', '#cc5511', '#006699',
			'orange', 'lightgray', 'lightgreen', 'lightblue', 'pink'
		);
		
		protected $graph;
		protected $colors;
		protected $use_gradient = false;
		
		protected $width = 800;
		protected $height = 600;
		
		protected $display_legend = TRUE;
		
		protected $scale_type = 'textlin';
		
		protected $margin_top = 40;
		protected $margin_left = 30;
		protected $margin_bottom = 30;
		protected $margin_right = 30;
		
		protected $major_interval = 1;
		protected $minor_interval = 1;
		
		protected $display_generate_date = FALSE;
		
		// not intended to be used by itself -- use one of
		// the derived classes!
		protected function __construct()
		{
		}
		
		/**
		 * Set the format for the Generation Date format
		 *
		 * @param date $date_format
		 */
		public function Display_Generate_Date($date_format)
		{
			$this->display_generate_date = $date_format;
		}
		public function Render(iReport $report, $file = NULL)
		{
			
			$scale_x = $report->Scale();
			$scale_x = $this->Adjust_Scale($scale_x);
			
			$labels = $report->Labels();
			
			$graph = new Graph($this->width, $this->height, '');
			$graph->SetMargin($this->margin_left, $this->margin_right, $this->margin_top, $this->margin_bottom);
			$graph->SetScale($this->scale_type, 0, 0, 0, count($scale_x));
			$graph->xgrid->SetColor('#000000@.8', '#222222@.8');
			$graph->ygrid->SetColor('#ffffff', '#eeeeee'); //#222222@.8
			$graph->xgrid->Show(TRUE, TRUE);
			$graph->xaxis->SetColor('#222222', '#808080');
			$graph->yaxis->SetColor('#222222', '#808080');
			$graph->ygrid->Show(TRUE, FALSE);
			//$graph->legend->SetFont(FF_VERDANA, FS_NORMAL, 10);
			$graph->SetMarginColor('#ffffff');
			$graph->SetBox(TRUE, '#c0c0c0', 3);
			$graph->SetFrame(TRUE, '#ffffff', 1);
			$graph->SetColor('#eeeeee');
			
			$graph->xaxis->SetTickLabels($labels);
			$graph->xaxis->SetFont(FF_ARIAL, FS_NORMAL, 8);
			$graph->yaxis->SetFont(FF_ARIAL, FS_NORMAL, 8);
			$graph->yaxis->HideFirstTickLabel();
			$graph->yaxis->scale->SetGrace(20);
			$graph->xaxis->scale->ticks->Set($this->major_interval, $this->minor_interval);
			
			$text = new Text($report->Title());
			$text->SetColor('#999999');
			$text->SetFont(FF_ARIAL, FS_NORMAL, 20);
			$text->SetPos((($this->width - $text->GetWidth($graph->img)) / 2), 0);
			$text->Show();
			
			$graph->AddText($text);
			
			//add text telling us when we generated the image to the bottom
			if($this->display_generate_date !== FALSE)
			{
            	$text = new Text('Generated At '.date($this->display_generate_date));
            	$text->SetColor('#000000@.4');
            	$text->SetFont(FF_ARIAL, FS_ITALIC,8);
            	$text->SetPos(10,$this->height - 9);
            	$text->Show();
            	$graph->AddText($text);
			}
			
			$this->graph = $graph;
			return;
			
		}
		
		public function Height($height = NULL)
		{
			
			if (is_numeric($height)) $this->height = (int)$height;
			return $this->height;
			
		}
		
		public function Width($width = NULL)
		{
			
			if (is_numeric($width)) $this->width = (int)$width;
			return $this->width;
			
		}
		
		public function Major_Interval($interval = NULL)
		{
			if (is_numeric($interval)) $this->major_interval = $interval;
			return $this->major_interval;
		}
		
		public function Minor_Interval($interval = NULL)
		{
			if (is_numeric($interval)) $this->minor_interval = $interval;
			return $this->minor_interval;
		}
		
		public function Margin($top, $left, $right, $bottom)
		{
			
			$this->margin_top = $top;
			$this->margin_left = $left;
			$this->margin_right = $right;
			$this->margin_bottom = $bottom;
			
			return;
			
		}
		
		public function Colors($colors)
		{
			
			if (is_array($colors)) $this->colors = $colors;
			return;
			
		}
		
		public function Display_Legend($display = NULL)
		{
			
			if (is_bool($display)) $this->display_legend = $display;
			return $this->display_legend;
			
		}
		
		public function Scale_Type($scale_type = NULL)
		{
			if(is_string($scale_type)) $this->scale_type = $scale_type;
			return $this->scale_type;
		}
		
		public function Use_Gradient($gradient = true)
		{
			if(is_bool($gradient)) $this->use_gradient = $gradient;
			return $this->use_gradient;
		}
		
		protected function Next_Color()
		{
			
			if (!is_array($this->colors) || (($color = next($this->colors)) === FALSE))
			{
				
				$color = next($this->default_colors);
				if ($color === FALSE) $color = reset($this->default_colors);
				
			}
			
			return $color;
			
		}
		
		protected function Adjust_Scale($scale)
		{
			return $scale;
		}
		
	}
	
?>